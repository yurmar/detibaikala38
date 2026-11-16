<?php
defined( 'ABSPATH' ) || exit;

/**
 * Письма: шаблоны с подстановками.
 */
class KAC_Mailer {

	public static function init(): void {
		add_action( 'kac_send_batch', array( __CLASS__, 'send_batch' ), 10, 2 );
		add_action( 'kac_moderator_digest', array( __CLASS__, 'send_digest' ) );
	}

	/**
	 * Шаблоны по умолчанию (тексты из макета, экран 9).
	 */
	public static function default_templates(): array {
		return array(
			'received'  => array(
				'subject' => 'Заявка {ticket} принята — «{contest_title}»',
				'heading' => 'Рисунок получен! 🎨',
				'body'    => "Здравствуйте, {parent_first_name}!\n\nРабота «{work_title}» (автор — {child_first_name}, {age}) отправлена на проверку. Обычно это занимает до 2 рабочих дней. Мы напишем, как только рисунок появится в галерее.\n\nНомер заявки: {ticket}",
				'button'  => array( '', '', '#ff6b5b' ),
			),
			'approved'  => array(
				'subject' => 'Работа опубликована — можно голосовать!',
				'heading' => 'Рисунок участвует в конкурсе ✅',
				'body'    => "«{work_title}» прошёл модерацию и опубликован. Поделитесь ссылкой с друзьями и близкими — голосование открыто до {vote_end}.\n\nСсылка: {work_url}",
				'button'  => array( 'Открыть работу', '{work_url}', '#ff6b5b' ),
			),
			'rejected'  => array(
				'subject' => 'Работу нужно исправить',
				'heading' => 'Работа не прошла модерацию',
				'body'    => "К сожалению, рисунок «{work_title}» не может быть опубликован.\n\nПричина: {reject_reason}\n\n{resubmit_text}",
				'button'  => array( '{resubmit_label}', '{resubmit_url}', '#ff6b5b' ),
			),
			'winner'    => array(
				'subject' => 'Поздравляем! {place} место в конкурсе',
				'heading' => '🏆 {child_first_name} — победитель!',
				'body'    => "Работа «{work_title}» заняла {place} место в группе {age_group}, набрав {votes} голосов.\n\nМы свяжемся с вами по телефону, чтобы договориться о вручении диплома и подарка.",
				'button'  => array( 'Смотреть итоги', '{results_url}', '#ff6b5b' ),
			),
			'results'   => array(
				'subject' => 'Итоги конкурса «{contest_title}»',
				'heading' => 'Итоги конкурса',
				'body'    => 'Спасибо за участие! Работа «{work_title}» набрала {votes} голосов. Итоги и победители — на сайте.',
				'button'  => array( 'Смотреть итоги', '{results_url}', '#ff6b5b' ),
			),
			'moderator' => array(
				'subject' => 'Новая работа на модерации ({pending_count} в очереди)',
				'heading' => 'Новая работа',
				'body'    => 'Поступила работа «{work_title}» в конкурс «{contest_title}».',
				'button'  => array( 'Перейти к модерации', '{moderation_url}', '#2271b1' ),
			),
			'spike'     => array(
				'subject' => 'Всплеск голосов: {work_title}',
				'heading' => 'Подозрительная активность',
				'body'    => 'Антифрод заметил подозрительную активность в конкурсе «{contest_title}»: {alert_text}.',
				'button'  => array( 'Открыть журнал голосов', '{votes_url}', '#2271b1' ),
			),
		);
	}

	public static function labels(): array {
		return array(
			'received'  => array( 'Заявка получена', 'Представитель' ),
			'approved'  => array( 'Работа опубликована', 'Представитель' ),
			'rejected'  => array( 'Работа отклонена', 'Представитель' ),
			'winner'    => array( 'Поздравление призёра', 'Представитель' ),
			'results'   => array( 'Итоги (всем)', 'Представители' ),
			'moderator' => array( 'Новая работа', 'Модераторы' ),
			'spike'     => array( 'Всплеск голосов', 'Администратор' ),
		);
	}

	public static function enabled( string $key ): bool {
		switch ( $key ) {
			case 'results':
				return (bool) kac_setting( 'mail_results_all' );
			case 'moderator':
				return 'off' !== (string) kac_setting( 'moderator_notify', 'instant' );
			default:
				return (bool) kac_setting( "mail_{$key}_enabled", 1 );
		}
	}

	public static function template( string $key ): array {
		$defaults = self::default_templates();
		$base     = $defaults[ $key ] ?? array( 'subject' => '', 'body' => '' );
		$subject  = (string) kac_setting( "mail_{$key}_subject", '' );
		$body     = (string) kac_setting( "mail_{$key}_body", '' );
		return array(
			'subject' => '' !== $subject ? $subject : $base['subject'],
			'body'    => '' !== $body ? $body : $base['body'],
		);
	}

	public static function placeholders( int $work_id, array $extra = array() ): array {
		$work       = get_post( $work_id );
		$contest_id = $work ? (int) $work->post_parent : 0;
		$p          = $work ? KAC_Participants::get( $work_id ) : null;
		$d          = KAC_Phase::dates( $contest_id );
		$fmt        = static fn( $ts ) => $ts ? wp_date( 'j F Y, H:i', $ts ) : '—';
		$group      = $work ? KAC_Contest::group_label( $contest_id, (string) get_post_meta( $work_id, '_kac_group', true ) ) : '';
		$n          = (int) get_post_meta( $work_id, '_kac_number', true );
		$ph         = array(
			'{contest_title}'     => $contest_id ? get_the_title( $contest_id ) : '',
			'{ticket}'            => $contest_id ? KAC_Contest::ticket( $contest_id, $n ) : '',
			'{work_title}'        => $work ? $work->post_title : '',
			'{work_url}'          => $work ? kac_work_url( $work_id ) : '',
			'{child_first_name}'  => $p ? $p->child_first : '',
			'{age}'               => $p ? self::age_text( (int) $p->child_age ) : '',
			'{age_group}'         => $group,
			'{parent_first_name}' => $p ? $p->parent_first : '',
			'{reject_reason}'     => (string) get_post_meta( $work_id, '_kac_reject_reason', true ),
			'{submit_end}'        => $fmt( $d['submit_end'] ),
			'{vote_end}'          => $fmt( $d['vote_end'] ),
			'{place}'             => '',
			'{votes}'             => (string) (int) get_post_meta( $work_id, '_kac_votes', true ),
			'{pending_count}'     => (string) self::pending_count(),
			'{results_url}'       => $contest_id ? get_permalink( $contest_id ) : '',
			'{moderation_url}'    => admin_url( 'admin.php?page=kac-moderation' ),
			'{alert_text}'        => '',
			'{votes_url}'         => admin_url( 'admin.php?page=kac-votes' ),
			'{resubmit_text}'     => '',
		);
		foreach ( $extra as $k => $v ) {
			$ph[ '{' . trim( $k, '{}' ) . '}' ] = (string) $v;
		}
		return (array) apply_filters( 'kac_email_placeholders', $ph, $work_id );
	}

	public static function age_text( int $age ): string {
		$m = $age % 10;
		$h = $age % 100;
		if ( $h >= 11 && $h <= 14 ) {
			return $age . ' лет';
		}
		if ( 1 === $m ) {
			return $age . ' год';
		}
		return ( $m >= 2 && $m <= 4 ) ? $age . ' года' : $age . ' лет';
	}

	public static function pending_count(): int {
		global $wpdb;
		return (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type='kac_work' AND post_status='kac_pending'" );
	}

	/**
	 * Отправка HTML-письма по шаблону. Адресат по умолчанию — email представителя.
	 */
	public static function send( string $key, int $work_id, array $extra = array(), string $to = '' ): bool {
		if ( ! self::enabled( $key ) ) {
			return false;
		}
		if ( '' === $to ) {
			$p  = KAC_Participants::get( $work_id );
			$to = $p ? $p->email : '';
		}
		if ( ! is_email( $to ) ) {
			return false;
		}
		$def = self::default_templates()[ $key ] ?? null;
		if ( ! $def ) {
			return false;
		}
		$tpl = self::template( $key );
		$ph  = self::placeholders( $work_id, $extra );
		if ( 'rejected' === $key ) {
			$allow = ! empty( $extra['resubmit_url'] );
			$d     = KAC_Phase::dates( (int) get_post_field( 'post_parent', $work_id ) );
			$ph['{resubmit_text}']  = $allow ? 'Вы можете исправить работу и отправить её снова до ' . kac_date( $d['submit_end'] ) . '.' : '';
			$ph['{resubmit_label}'] = $allow ? 'Отправить заново' : '';
			$ph['{resubmit_url}']   = $allow ? (string) $extra['resubmit_url'] : '';
		}
		$esc   = array_map( 'esc_html', $ph );
		$subj  = wp_specialchars_decode( strtr( $tpl['subject'], $ph ) );
		$head  = strtr( esc_html( $def['heading'] ), $esc );
		$html  = '';
		foreach ( preg_split( '/\n{2,}/', trim( strtr( esc_html( $tpl['body'] ), $esc ) ) ) as $para ) {
			$para = trim( $para );
			if ( '' === $para ) {
				continue;
			}
			if ( 0 === strpos( $para, 'Причина:' ) ) {
				$html .= '<div style="background:#fff4f2;border-left:4px solid #ff6b5b;padding:8px 12px;border-radius:6px;margin:8px 0"><b>Причина:</b>' . nl2br( substr( $para, strlen( 'Причина:' ) ) ) . '</div>';
			} elseif ( 0 === strpos( $para, 'Ссылка:' ) || 0 === strpos( $para, 'Номер заявки:' ) ) {
				$html .= '<p style="color:#888;font-size:12.5px;margin:0 0 12px">' . nl2br( $para ) . '</p>';
			} else {
				$html .= '<p style="margin:0 0 12px">' . nl2br( $para ) . '</p>';
			}
		}
		$btn = array( 'label' => strtr( $def['button'][0], $ph ), 'url' => strtr( $def['button'][1], $ph ), 'color' => $def['button'][2] );
		ob_start();
		$file = locate_template( 'kids-art-contest/emails/base.php' );
		include $file ? $file : KAC_DIR . 'public/templates/emails/base.php';
		$message = (string) ob_get_clean();
		$sent = wp_mail( $to, $subj, $message, array( 'Content-Type: text/html; charset=UTF-8' ) );
		if ( $sent && $work_id && in_array( $key, array( 'received', 'approved', 'rejected', 'winner', 'results' ), true ) ) {
			KAC_Log::add( 'work', $work_id, 'mail_' . $key );
		}
		return $sent;
	}

	/**
	 * Новая работа → модераторам (сразу или дайджестом).
	 */
	public static function notify_moderators( int $work_id ): void {
		$mode = (string) kac_setting( 'moderator_notify', 'instant' );
		if ( 'instant' !== $mode ) {
			return;
		}
		$work = get_post( $work_id );
		foreach ( self::moderator_emails( $work ? (int) $work->post_parent : 0 ) as $email ) {
			self::send( 'moderator', $work_id, array(), $email );
		}
	}

	public static function send_digest(): void {
		if ( 'digest' !== kac_setting( 'moderator_notify', 'instant' ) ) {
			return;
		}
		$count = self::pending_count();
		$last  = (int) get_option( 'kac_digest_last_count', 0 );
		if ( $count > 0 && $count !== $last ) {
			$body = sprintf( "На модерации работ: %d.\n%s", $count, admin_url( 'admin.php?page=kac-moderation' ) );
			foreach ( self::moderator_emails( 0 ) as $email ) {
				wp_mail( $email, 'Работы ждут модерации', $body );
			}
		}
		update_option( 'kac_digest_last_count', $count, false );
	}

	/**
	 * Email модераторов: назначенные на конкурс или все с правом модерации.
	 *
	 * @return string[]
	 */
	public static function moderator_emails( int $contest_id ): array {
		$ids = $contest_id ? (array) get_post_meta( $contest_id, '_kac_moderators', true ) : array();
		$ids = array_filter( array_map( 'absint', $ids ) );
		if ( $ids ) {
			$users = get_users( array( 'include' => $ids, 'fields' => array( 'user_email' ) ) );
		} else {
			$users = get_users( array( 'role__in' => array( 'kac_moderator', 'administrator' ), 'fields' => array( 'user_email' ) ) );
		}
		return array_values( array_unique( wp_list_pluck( $users, 'user_email' ) ) );
	}

	public static function admin_emails(): array {
		$custom = array_filter( array_map( 'trim', explode( ',', (string) kac_setting( 'alert_emails', '' ) ) ), 'is_email' );
		return $custom ? array_values( $custom ) : array( get_option( 'admin_email' ) );
	}

	/**
	 * Массовая рассылка порциями через cron.
	 */
	public static function queue_bulk( string $key, array $work_ids ): void {
		$chunks = array_chunk( $work_ids, 40 );
		foreach ( $chunks as $i => $chunk ) {
			wp_schedule_single_event( time() + 30 + $i * 60, 'kac_send_batch', array( $key, $chunk ) );
		}
	}

	public static function send_batch( string $key, array $work_ids ): void {
		foreach ( $work_ids as $wid ) {
			self::send( $key, (int) $wid );
		}
	}
}
