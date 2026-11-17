<?php
defined( 'ABSPATH' ) || exit;

/**
 * Приём работы: валидация, создание записи, письма.
 */
class KAC_Submission {

	/**
	 * Обработка POST /works.
	 */
	public static function handle( WP_REST_Request $req ) {
		// Диагностика: фатальную ошибку PHP (память, таймаут) запоминаем, чтобы админ видел причину.
		register_shutdown_function(
			static function () {
				$e = error_get_last();
				if ( $e && in_array( $e['type'], array( E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR ), true ) ) {
					update_option( 'kac_last_error', array( 'time' => time(), 'msg' => $e['message'], 'file' => basename( (string) $e['file'] ) . ':' . $e['line'] ), false );
				}
			}
		);
		try {
			return self::do_handle( $req );
		} catch ( \Throwable $t ) {
			update_option( 'kac_last_error', array( 'time' => time(), 'msg' => $t->getMessage(), 'file' => basename( $t->getFile() ) . ':' . $t->getLine() ), false );
			return new WP_Error( 'kac_exception', __( 'Не удалось обработать отправку. Попробуйте ещё раз или уменьшите размер рисунка.', 'kids-art-contest' ), array( 'status' => 500 ) );
		}
	}

	private static function do_handle( WP_REST_Request $req ) {
		global $wpdb;

		if ( ! KAC_Security::check_nonce( (string) $req->get_param( 'kac_nonce' ) ) ) {
			return new WP_Error( 'kac_nonce', __( 'Страница устарела. Обновите её и повторите отправку.', 'kids-art-contest' ), array( 'status' => 403 ) );
		}
		// Honeypot.
		if ( '' !== (string) $req->get_param( 'website' ) ) {
			return array( 'ok' => true, 'ticket' => '' ); // Тихо «успех» для ботов.
		}

		$contest_id = absint( $req->get_param( 'contest_id' ) );
		if ( ! KAC_Phase::can_submit( $contest_id ) ) {
			return new WP_Error( 'kac_closed', __( 'Приём работ сейчас закрыт.', 'kids-art-contest' ), array( 'status' => 403 ) );
		}

		$ip_hash = KAC_Security::hash( KAC_Security::ip() );
		// Неудачные попытки лимит не расходуют: счётчик растёт только после успешной отправки.
		if ( KAC_Security::rate_exceeded( 'submit_' . $ip_hash, 3 ) ) {
			return new WP_Error( 'kac_rate', __( 'Слишком много отправок. Попробуйте через час.', 'kids-art-contest' ), array( 'status' => 429 ) );
		}

		if ( kac_setting( 'captcha_submit' ) && ! KAC_Captcha::verify( $req->get_params() ) ) {
			return new WP_Error( 'kac_captcha', __( 'Неверный ответ на проверочный вопрос.', 'kids-art-contest' ), array( 'status' => 400 ) );
		}

		$f = self::collect_fields( $req );
		$errors = self::validate_fields( $f, $contest_id );
		if ( $errors ) {
			return new WP_Error( 'kac_invalid', __( 'Проверьте поля формы.', 'kids-art-contest' ), array( 'status' => 400, 'fields' => $errors ) );
		}

		// Лимит работ на участника (email + ФИО ребёнка).
		$existing = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->prefix}kac_participants pt JOIN {$wpdb->posts} p ON p.ID = pt.work_id
				 WHERE p.post_parent = %d AND p.post_status IN ('kac_pending','publish') AND pt.email = %s
				 AND pt.child_last = %s AND pt.child_first = %s",
				$contest_id,
				$f['email'],
				$f['child_last'],
				$f['child_first']
			)
		);
		$resubmit_of = absint( $req->get_param( 'resubmit_of' ) );
		if ( $resubmit_of && ! self::resubmit_ok( $resubmit_of, $contest_id, (string) $req->get_param( 'resubmit_key' ) ) ) {
			$resubmit_of = 0;
		}
		if ( $existing >= KAC_Contest::max_works( $contest_id ) ) {
			return new WP_Error( 'kac_limit', __( 'Вы уже отправили максимальное число работ этого ребёнка на конкурс.', 'kids-art-contest' ), array( 'status' => 409 ) );
		}

		// Файл.
		$files = $req->get_file_params();
		$file  = $files['image'] ?? null;
		if ( ! $file ) {
			return new WP_Error( 'kac_invalid', __( 'Загрузите рисунок.', 'kids-art-contest' ), array( 'status' => 400, 'fields' => array( 'image' => __( 'Загрузите рисунок.', 'kids-art-contest' ) ) ) );
		}
		$meta = KAC_Image::validate( $file, KAC_Contest::upload_rules( $contest_id ) );
		if ( is_wp_error( $meta ) ) {
			return new WP_Error( $meta->get_error_code(), $meta->get_error_message(), array( 'status' => 400, 'fields' => array( 'image' => $meta->get_error_message() ) ) );
		}

		// Обработка крупных фото требует памяти и времени.
		wp_raise_memory_limit( 'image' );
		if ( function_exists( 'set_time_limit' ) ) {
			@set_time_limit( 120 ); // phpcs:ignore WordPress.PHP.NoSilencedErrors
		}

		$group  = KAC_Contest::group_for_age( $contest_id, $f['age'] );
		$number = KAC_Contest::next_number( $contest_id );
		$work_id = wp_insert_post(
			array(
				'post_type'    => 'kac_work',
				'post_status'  => 'kac_pending',
				'post_parent'  => $contest_id,
				'post_title'   => $f['title'],
				'post_content' => $f['description'],
				'post_author'  => 0,
			),
			true
		);
		KAC_Contest::release_number( $contest_id );
		if ( is_wp_error( $work_id ) ) {
			return new WP_Error( 'kac_save', __( 'Не удалось сохранить работу.', 'kids-art-contest' ), array( 'status' => 500 ) );
		}

		// Если процесс упадёт (память, таймаут) до конца — не оставляем «пустую» работу на модерации.
		$finished = false;
		register_shutdown_function(
			static function () use ( &$finished, $work_id ) {
				if ( ! $finished ) {
					wp_delete_post( $work_id, true );
				}
			}
		);

		$img = KAC_Image::process( $file, $meta, $contest_id, $work_id );
		if ( is_wp_error( $img ) ) {
			wp_delete_post( $work_id, true );
			return new WP_Error( $img->get_error_code(), $img->get_error_message(), array( 'status' => 500 ) );
		}
		set_post_thumbnail( $work_id, $img['attachment_id'] );

		update_post_meta( $work_id, '_kac_number', $number );
		update_post_meta( $work_id, '_kac_age', $f['age'] );
		update_post_meta( $work_id, '_kac_group', $group ? $group['key'] : '' );
		update_post_meta( $work_id, '_kac_public_name', kac_public_name( $f['child_first'], $f['child_last'] ) );
		update_post_meta( $work_id, '_kac_technique', $f['technique'] );
		update_post_meta( $work_id, '_kac_votes', 0 );
		update_post_meta( $work_id, '_kac_dhash', kac_setting( 'check_duplicates' ) ? $img['dhash'] : '' );
		update_post_meta( $work_id, '_kac_file_info', array( 'name' => sanitize_file_name( (string) ( $file['name'] ?? '' ) ), 'w' => $meta['width'], 'h' => $meta['height'], 'bytes' => (int) ( $file['size'] ?? 0 ) ) );
		update_post_meta( $work_id, '_kac_original_path', $img['original_path'] );
		if ( $resubmit_of ) {
			update_post_meta( $work_id, '_kac_resubmit_of', $resubmit_of );
			update_post_meta( $resubmit_of, '_kac_allow_resubmit', 0 );
		}

		KAC_Participants::insert(
			$work_id,
			array(
				'child_last'      => $f['child_last'],
				'child_first'     => $f['child_first'],
				'child_middle'    => $f['child_middle'],
				'child_age'       => $f['age'],
				'city'            => $f['city'],
				'school'          => $f['school'],
				'teacher'         => $f['teacher'],
				'parent_last'     => $f['parent_last'],
				'parent_first'    => $f['parent_first'],
				'parent_middle'   => $f['parent_middle'],
				'parent_relation' => $f['parent_relation'],
				'email'           => $f['email'],
				'phone'           => $f['phone'],
				'consents'        => wp_json_encode( $f['consents'] ),
				'consent_version' => (int) kac_setting( 'consent_version', 1 ),
				'newsletter'      => $f['newsletter'] ? 1 : 0,
				'ip_hash'         => $ip_hash,
			)
		);

		$finished = true;
		KAC_Security::rate_limit( 'submit_' . $ip_hash, 3, HOUR_IN_SECONDS );

		KAC_Log::add( 'work', $work_id, 'submitted', array( 'contest' => $contest_id ) );
		do_action( 'kac_work_submitted', $work_id, $contest_id );

		try {
			KAC_Mailer::send( 'received', $work_id );
			KAC_Mailer::notify_moderators( $work_id );
		} catch ( \Throwable $e ) {
			KAC_Log::add( 'work', $work_id, 'mail_failed', array( 'error' => $e->getMessage() ) );
		}

		return array(
			'ok'     => true,
			'ticket' => KAC_Contest::ticket( $contest_id, $number ),
			'email'  => Kac_Format::mask_email( $f['email'] ),
			'title'  => $f['title'],
			'date'   => wp_date( 'j F, H:i' ),
		);
	}

	private static function collect_fields( WP_REST_Request $req ): array {
		$t = static fn( $k ) => trim( sanitize_text_field( (string) $req->get_param( $k ) ) );
		$phone = preg_replace( '/\D+/', '', (string) $req->get_param( 'phone' ) );
		if ( 11 === strlen( $phone ) && '8' === $phone[0] ) {
			$phone = '7' . substr( $phone, 1 );
		}
		return array(
			'title'           => mb_substr( $t( 'title' ), 0, 60 ),
			'description'     => mb_substr( sanitize_textarea_field( (string) $req->get_param( 'description' ) ), 0, 500 ),
			'technique'       => mb_substr( $t( 'technique' ), 0, 80 ),
			'child_last'      => $t( 'child_last' ),
			'child_first'     => $t( 'child_first' ),
			'child_middle'    => $t( 'child_middle' ),
			'age'             => absint( $req->get_param( 'age' ) ),
			'city'            => $t( 'city' ),
			'school'          => $t( 'school' ),
			'teacher'         => $t( 'teacher' ),
			'parent_last'     => $t( 'parent_last' ),
			'parent_first'    => $t( 'parent_first' ),
			'parent_middle'   => $t( 'parent_middle' ),
			'parent_relation' => $t( 'parent_relation' ),
			'email'           => sanitize_email( (string) $req->get_param( 'email' ) ),
			'phone'           => $phone,
			'newsletter'      => (bool) $req->get_param( 'newsletter' ),
			'consents'        => array(
				'personal' => (bool) $req->get_param( 'consent_personal' ),
				'publish'  => (bool) $req->get_param( 'consent_publish' ),
				'rules'    => (bool) $req->get_param( 'consent_rules' ),
				'at'       => gmdate( 'c' ),
			),
		);
	}

	/**
	 * Режим поля формы: hidden | optional | required.
	 */
	public static function field_mode( string $field ): string {
		$m = (string) kac_setting( 'f_' . $field, 'optional' );
		return in_array( $m, array( 'hidden', 'optional', 'required' ), true ) ? $m : 'optional';
	}

	private static function validate_fields( array $f, int $contest_id ): array {
		$e   = array();
		$req = __( 'Обязательное поле.', 'kids-art-contest' );
		foreach ( array( 'title', 'child_last', 'child_first', 'parent_last', 'parent_first', 'parent_relation' ) as $k ) {
			if ( '' === $f[ $k ] ) {
				$e[ $k ] = $req;
			}
		}
		$map = array( 'child_middle' => 'child_middle', 'city' => 'city', 'school' => 'school', 'teacher' => 'teacher', 'technique' => 'technique', 'description' => 'description' );
		foreach ( $map as $key => $setting ) {
			if ( 'required' === self::field_mode( $setting ) && '' === $f[ $key ] ) {
				$e[ $key ] = $req;
			}
		}
		if ( ! $f['age'] ) {
			$e['age'] = $req;
		} elseif ( $f['age'] < KAC_Contest::min_age( $contest_id ) || $f['age'] > KAC_Contest::max_age( $contest_id ) || ! KAC_Contest::group_for_age( $contest_id, $f['age'] ) ) {
			$e['age'] = sprintf( __( 'Возраст участников: от %1$d до %2$d лет.', 'kids-art-contest' ), KAC_Contest::min_age( $contest_id ), KAC_Contest::max_age( $contest_id ) );
		}
		if ( ! is_email( $f['email'] ) ) {
			$e['email'] = __( 'Проверьте email: должен быть вида name@example.ru', 'kids-art-contest' );
		}
		if ( ! preg_match( '/^7\d{10}$/', $f['phone'] ) ) {
			$e['phone'] = __( 'Введите номер полностью: 10 цифр после +7', 'kids-art-contest' );
		}
		if ( ! $f['consents']['personal'] ) {
			$e['consent_personal'] = __( 'Необходимо согласие на обработку персональных данных.', 'kids-art-contest' );
		}
		if ( ! $f['consents']['publish'] ) {
			$e['consent_publish'] = __( 'Необходимо согласие на публикацию рисунка.', 'kids-art-contest' );
		}
		if ( ! $f['consents']['rules'] ) {
			$e['consent_rules'] = __( 'Необходимо принять правила конкурса.', 'kids-art-contest' );
		}
		return (array) apply_filters( 'kac_validate_submission', $e, $f, $contest_id );
	}

	/* ---- Повторная отправка отклонённой работы ---- */

	public static function resubmit_key( int $work_id ): string {
		return substr( KAC_Security::hash( 'resubmit|' . $work_id ), 0, 20 );
	}

	public static function resubmit_ok( int $work_id, int $contest_id, string $key ): bool {
		$w = get_post( $work_id );
		return $w && 'kac_work' === $w->post_type && (int) $w->post_parent === $contest_id
			&& 'kac_rejected' === $w->post_status
			&& get_post_meta( $work_id, '_kac_allow_resubmit', true )
			&& hash_equals( self::resubmit_key( $work_id ), $key );
	}

	public static function resubmit_url( int $work_id ): string {
		$w = get_post( $work_id );
		return add_query_arg(
			array(
				'kac_resubmit' => $work_id,
				'k'            => self::resubmit_key( $work_id ),
			),
			get_permalink( $w->post_parent ) ) . '#kac-form';
	}
}
