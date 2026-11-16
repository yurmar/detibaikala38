<?php
defined( 'ABSPATH' ) || exit;

/**
 * Голосование без регистрации и антифрод.
 */
class KAC_Voting {

	public static function init(): void {
		add_action( 'kac_detect', array( __CLASS__, 'detect' ) );
	}

	public static function table(): string {
		global $wpdb;
		return $wpdb->prefix . 'kac_votes';
	}

	public static function voter_token( WP_REST_Request $req ): string {
		$t = (string) $req->get_header( 'x-kac-voter' );
		if ( '' === $t ) {
			$t = (string) $req->get_param( 'voter' );
		}
		return preg_match( '/^[a-f0-9-]{36}$/i', $t ) ? strtolower( $t ) : '';
	}

	/**
	 * POST /works/{id}/vote
	 */
	public static function cast( WP_REST_Request $req ) {
		global $wpdb;

		if ( ! KAC_Security::check_nonce( (string) $req->get_header( 'x-kac-nonce' ) ) ) {
			return new WP_Error( 'nonce', __( 'Страница устарела. Обновите её.', 'kids-art-contest' ), array( 'status' => 403 ) );
		}

		$work_id = absint( $req['id'] );
		$work    = get_post( $work_id );
		if ( ! $work || 'kac_work' !== $work->post_type || 'publish' !== $work->post_status ) {
			return new WP_Error( 'voting_closed', __( 'Работа недоступна для голосования.', 'kids-art-contest' ), array( 'status' => 403 ) );
		}
		$contest_id = (int) $work->post_parent;
		if ( ! KAC_Phase::can_vote( $contest_id ) ) {
			return new WP_Error( 'voting_closed', __( 'Голосование сейчас закрыто.', 'kids-art-contest' ), array( 'status' => 403 ) );
		}

		$token = self::voter_token( $req );
		if ( '' === $token ) {
			return new WP_Error( 'no_voter', __( 'Не удалось определить браузер. Включите cookie и повторите.', 'kids-art-contest' ), array( 'status' => 400 ) );
		}

		$voter_hash = KAC_Security::hash( 'v|' . $contest_id . '|' . $token );
		$ip         = KAC_Security::ip();
		$ip_hash    = KAC_Security::hash( 'ip|' . $ip );
		$fp         = kac_setting( 'use_fingerprint' ) ? substr( sanitize_text_field( (string) $req->get_param( 'fp' ) ), 0, 400 ) : '';
		$fp_hash    = '' === $fp ? '' : KAC_Security::hash( 'fp|' . $fp );
		$ua         = KAC_Security::user_agent();
		$group      = (string) get_post_meta( $work_id, '_kac_group', true );

		$allowed = apply_filters( 'kac_vote_allowed', true, $work_id, $contest_id );
		if ( true !== $allowed ) {
			return new WP_Error( 'vote_denied', is_string( $allowed ) ? $allowed : __( 'Голос не принят.', 'kids-art-contest' ), array( 'status' => 403 ) );
		}

		$lock = 'kac_v_' . $contest_id . '_' . substr( $voter_hash, 0, 24 );
		$wpdb->query( $wpdb->prepare( 'SELECT GET_LOCK(%s, 5)', $lock ) );
		try {
			$err = self::check_limits( $contest_id, $work_id, $group, $voter_hash, $fp_hash, $ip_hash );
			if ( $err ) {
				return $err;
			}

			$risk      = self::risk( $work_id, $ip_hash, KAC_Security::subnet( $ip ), $ua, (int) $req->get_param( 'ix' ) );
			$status    = 'valid';
			$high      = $risk >= (int) kac_setting( 'risk_threshold', 60 );
			$need_cap  = kac_setting( 'captcha_always' ) || ( $high && kac_setting( 'captcha_suspicious' ) );
			if ( $need_cap ) {
				if ( ! KAC_Captcha::verify( $req->get_params() ) ) {
					return new WP_Error( 'captcha_required', __( 'Подтвердите, что вы не робот.', 'kids-art-contest' ), array( 'status' => 403, 'captcha' => KAC_Captcha::client_config() ) );
				}
			}
			if ( $high ) {
				$status = 'suspicious';
			}

			$inserted = $wpdb->insert(
				self::table(),
				array(
					'contest_id'  => $contest_id,
					'work_id'     => $work_id,
					'voter_hash'  => $voter_hash,
					'fp_hash'     => $fp_hash,
					'ip_hash'     => $ip_hash,
					'ip_masked'   => KAC_Security::mask_ip( $ip ),
					'subnet_hash' => KAC_Security::hash( 'sn|' . KAC_Security::subnet( $ip ) ),
					'ua_hash'     => md5( $ua ),
					'ua_label'    => self::ua_label( $ua ),
					'risk'        => min( 100, $risk ),
					'status'      => $status,
					'created_at'  => gmdate( 'Y-m-d H:i:s' ),
				)
			);
			if ( ! $inserted ) {
				return new WP_Error( 'already_voted', __( 'Вы уже голосовали за эту работу.', 'kids-art-contest' ), array( 'status' => 409 ) );
			}
			self::bump( $work_id, 1 );
		} finally {
			$wpdb->query( $wpdb->prepare( 'SELECT RELEASE_LOCK(%s)', $lock ) );
		}

		do_action( 'kac_vote_cast', $work_id, $contest_id, $status );

		return array(
			'ok'    => true,
			'votes' => KAC_Contest::show_votes( $contest_id ) ? (int) get_post_meta( $work_id, '_kac_votes', true ) : null,
		);
	}

	/**
	 * Проверки режима голосования, отпечатка и лимита IP.
	 */
	private static function check_limits( int $contest_id, int $work_id, string $group, string $voter_hash, string $fp_hash, string $ip_hash ): ?WP_Error {
		global $wpdb;
		$t    = self::table();
		$mode = (string) get_post_meta( $contest_id, '_kac_vote_mode', true );
		$mode = $mode ? $mode : 'per_work';
		$dup  = new WP_Error( 'already_voted', __( 'Вы уже голосовали за эту работу.', 'kids-art-contest' ), array( 'status' => 409 ) );

		if ( (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $t WHERE work_id = %d AND voter_hash = %s", $work_id, $voter_hash ) ) ) {
			return $dup;
		}

		if ( in_array( $mode, array( 'per_contest', 'n_per_contest' ), true ) ) {
			$limit = 'per_contest' === $mode ? 1 : max( 1, (int) get_post_meta( $contest_id, '_kac_vote_limit', true ) );
			$used  = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $t WHERE contest_id = %d AND voter_hash = %s AND status <> 'annulled'", $contest_id, $voter_hash ) );
			if ( $used >= $limit ) {
				return new WP_Error( 'already_voted', sprintf( __( 'Вы использовали все голоса в этом конкурсе (%d).', 'kids-art-contest' ), $limit ), array( 'status' => 409 ) );
			}
		} elseif ( 'per_group' === $mode ) {
			$used = (int) $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*) FROM $t v JOIN {$wpdb->postmeta} m ON m.post_id = v.work_id AND m.meta_key = '_kac_group'
					 WHERE v.contest_id = %d AND v.voter_hash = %s AND v.status <> 'annulled' AND m.meta_value = %s",
					$contest_id,
					$voter_hash,
					$group
				)
			);
			if ( $used >= 1 ) {
				return new WP_Error( 'already_voted', __( 'В этой возрастной группе вы уже проголосовали.', 'kids-art-contest' ), array( 'status' => 409 ) );
			}
		}

		if ( '' !== $fp_hash && (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $t WHERE work_id = %d AND fp_hash = %s AND ip_hash = %s AND status <> 'annulled'", $work_id, $fp_hash, $ip_hash ) ) ) {
			return $dup;
		}

		$ip_limit = max( 1, (int) kac_setting( 'vote_ip_daily_limit', 3 ) );
		$since    = gmdate( 'Y-m-d H:i:s', time() - DAY_IN_SECONDS );
		if ( kac_setting( 'ip_limit_enabled' ) && (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $t WHERE work_id = %d AND ip_hash = %s AND created_at > %s", $work_id, $ip_hash, $since ) ) >= $ip_limit ) {
			return new WP_Error( 'ip_limit', __( 'С вашей сети уже поступило много голосов за эту работу. Попробуйте позже.', 'kids-art-contest' ), array( 'status' => 429 ) );
		}
		return null;
	}

	/**
	 * Оценка риска 0–100.
	 */
	private static function risk( int $work_id, string $ip_hash, string $subnet, string $ua, int $interaction ): int {
		global $wpdb;
		$t     = self::table();
		$risk  = 0;
		$spike = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $t WHERE work_id = %d AND created_at > %s", $work_id, gmdate( 'Y-m-d H:i:s', time() - 600 ) ) );
		if ( $spike >= 30 ) {
			$risk += 50;
		} elseif ( $spike >= 15 ) {
			$risk += 30;
		}
		$subnet_hash = KAC_Security::hash( 'sn|' . $subnet );
		$same_net    = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $t WHERE work_id = %d AND subnet_hash = %s AND created_at > %s", $work_id, $subnet_hash, gmdate( 'Y-m-d H:i:s', time() - HOUR_IN_SECONDS ) ) );
		if ( $same_net >= 5 ) {
			$risk += 30;
		}
		if ( '' === $ua || preg_match( '/headless|phantomjs|curl|python|wget|selenium|puppeteer|playwright|bot|spider|crawl/i', $ua ) ) {
			$risk += 60;
		}
		if ( $interaction < 1 ) {
			$risk += 25;
		}
		return (int) apply_filters( 'kac_vote_risk_score', min( 100, $risk ), $work_id, $ip_hash );
	}

	/**
	 * Короткая подпись браузера для журнала: «Chrome / Android».
	 */
	public static function ua_label( string $ua ): string {
		if ( '' === $ua ) {
			return 'Не определён';
		}
		if ( preg_match( '/headless|phantomjs|selenium|puppeteer|playwright/i', $ua ) ) {
			return 'Headless-браузер';
		}
		$browser = 'Браузер';
		foreach ( array( 'YaBrowser' => 'Яндекс Браузер', 'Edg' => 'Edge', 'OPR' => 'Opera', 'Firefox' => 'Firefox', 'Chrome' => 'Chrome', 'Safari' => 'Safari', 'curl' => 'curl', 'python' => 'Python' ) as $k => $label ) {
			if ( false !== stripos( $ua, $k ) ) {
				$browser = $label;
				break;
			}
		}
		$os = 'ОС';
		foreach ( array( 'Android' => 'Android', 'iPhone' => 'iOS', 'iPad' => 'iOS', 'Windows' => 'Windows', 'Mac OS' => 'macOS', 'Linux' => 'Linux' ) as $k => $label ) {
			if ( false !== stripos( $ua, $k ) ) {
				$os = $label;
				break;
			}
		}
		return $browser . ' / ' . $os;
	}

	public static function bump( int $work_id, int $delta ): void {
		global $wpdb;
		$exists = $wpdb->get_var( $wpdb->prepare( "SELECT meta_id FROM {$wpdb->postmeta} WHERE post_id = %d AND meta_key = '_kac_votes'", $work_id ) );
		if ( ! $exists ) {
			add_post_meta( $work_id, '_kac_votes', 0, true );
		}
		$wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->postmeta} SET meta_value = GREATEST(0, CAST(meta_value AS SIGNED) + %d) WHERE post_id = %d AND meta_key = '_kac_votes'", $delta, $work_id ) );
		wp_cache_delete( $work_id, 'post_meta' );
	}

	/**
	 * Пересчитывает счётчик работы по таблице голосов (без аннулированных).
	 */
	public static function recount( int $work_id ): int {
		global $wpdb;
		$n = (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM ' . self::table() . " WHERE work_id = %d AND status <> 'annulled'", $work_id ) );
		update_post_meta( $work_id, '_kac_votes', $n );
		return $n;
	}

	/**
	 * Аннулирование / восстановление голосов.
	 */
	public static function set_status( array $ids, string $status ): int {
		global $wpdb;
		$ids = array_filter( array_map( 'absint', $ids ) );
		if ( ! $ids || ! in_array( $status, array( 'valid', 'annulled', 'suspicious' ), true ) ) {
			return 0;
		}
		$in    = implode( ',', $ids );
		$works = $wpdb->get_col( 'SELECT DISTINCT work_id FROM ' . self::table() . " WHERE id IN ($in)" );
		$n     = (int) $wpdb->query( $wpdb->prepare( 'UPDATE ' . self::table() . " SET status = %s WHERE id IN ($in)", $status ) );
		foreach ( $works as $wid ) {
			self::recount( (int) $wid );
		}
		KAC_Log::add( 'votes', 0, 'annulled' === $status ? 'annul' : 'restore', array( 'ids' => $ids ) );
		do_action( 'annulled' === $status ? 'kac_vote_annulled' : 'kac_vote_restored', $ids );
		return $n;
	}

	/**
	 * Идентификаторы работ, за которые проголосовал токен.
	 */
	public static function my_votes( int $contest_id, string $token ): array {
		global $wpdb;
		if ( '' === $token ) {
			return array();
		}
		$hash = KAC_Security::hash( 'v|' . $contest_id . '|' . $token );
		return array_map( 'intval', $wpdb->get_col( $wpdb->prepare( 'SELECT work_id FROM ' . self::table() . " WHERE contest_id = %d AND voter_hash = %s AND status <> 'annulled'", $contest_id, $hash ) ) );
	}

	/**
	 * Детекторы (cron каждые 10 минут): всплески и кластеры подсетей.
	 */
	public static function detect(): void {
		global $wpdb;
		$t     = self::table();
		$since = gmdate( 'Y-m-d H:i:s', time() - 600 );
		$limit = max( 5, (int) kac_setting( 'spike_threshold', 30 ) );

		$rows = $wpdb->get_results( $wpdb->prepare( "SELECT work_id, contest_id, COUNT(*) c FROM $t WHERE created_at > %s GROUP BY work_id, contest_id HAVING c >= %d", $since, $limit ) );
		foreach ( $rows as $r ) {
			self::raise( 'spike', (int) $r->contest_id, (int) $r->work_id, array( 'votes' => (int) $r->c, 'minutes' => 10 ) );
		}

		$rows = $wpdb->get_results( $wpdb->prepare( "SELECT work_id, contest_id, subnet_hash, COUNT(*) c FROM $t WHERE created_at > %s AND status <> 'annulled' GROUP BY work_id, contest_id, subnet_hash HAVING c >= %d", gmdate( 'Y-m-d H:i:s', time() - HOUR_IN_SECONDS ), 10 ) );
		foreach ( $rows as $r ) {
			self::raise( 'subnet', (int) $r->contest_id, (int) $r->work_id, array( 'votes' => (int) $r->c, 'minutes' => 60 ) );
		}
	}

	private static function raise( string $type, int $contest_id, int $work_id, array $data ): void {
		global $wpdb;
		$recent = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->prefix}kac_alerts WHERE type = %s AND work_id = %d AND created_at > %s", $type, $work_id, gmdate( 'Y-m-d H:i:s', time() - HOUR_IN_SECONDS ) ) );
		if ( $recent ) {
			return;
		}
		KAC_Log::alert( $type, $contest_id, $work_id, $data );
		$text = 'spike' === $type
			? sprintf( 'за работу получено %d голосов за %d минут', $data['votes'], $data['minutes'] )
			: sprintf( '%d голосов из одной подсети за %d минут', $data['votes'], $data['minutes'] );
		foreach ( KAC_Mailer::admin_emails() as $email ) {
			KAC_Mailer::send( 'spike', $work_id, array( 'alert_text' => $text ), $email );
		}
	}
}
