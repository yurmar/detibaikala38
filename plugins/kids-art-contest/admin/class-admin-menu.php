<?php
defined( 'ABSPATH' ) || exit;

/**
 * Меню админки, ассеты, перенаправления.
 */
class KAC_Admin_Menu {

	const ROOT = 'edit.php?post_type=kac_contest';

	public static function init(): void {
		add_action( 'admin_menu', array( __CLASS__, 'menu' ) );
		add_action( 'admin_notices', array( __CLASS__, 'last_error_notice' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'assets' ) );
		add_filter( 'login_redirect', array( __CLASS__, 'login_redirect' ), 10, 3 );
		add_filter( 'parent_file', array( __CLASS__, 'parent_file' ) );
		add_filter( 'submenu_file', array( __CLASS__, 'submenu_file' ) );
		add_action( 'load-post.php', array( __CLASS__, 'redirect_work_edit' ) );
		add_action( 'admin_post_kac_export', array( __CLASS__, 'handle_export' ) );
		add_action( 'admin_head', array( __CLASS__, 'menu_icon_css' ) );
		add_action( 'admin_bar_menu', array( __CLASS__, 'admin_bar' ), 80 );
		add_action( 'wp_dashboard_setup', array( __CLASS__, 'dashboard_widget' ) );
	}

	public static function phase_label( string $phase ): string {
		$l = array(
			'draft'      => __( 'Черновик', 'kids-art-contest' ),
			'upcoming'   => __( 'Скоро', 'kids-art-contest' ),
			'submission' => __( 'Приём работ', 'kids-art-contest' ),
			'voting'     => __( 'Голосование', 'kids-art-contest' ),
			'finished'   => __( 'Подведение итогов', 'kids-art-contest' ),
			'results'    => __( 'Итоги опубликованы', 'kids-art-contest' ),
		);
		return $l[ $phase ] ?? $phase;
	}

	public static function status_label( string $status ): string {
		$l = array(
			'kac_pending'      => __( 'На модерации', 'kids-art-contest' ),
			'publish'          => __( 'Одобрена', 'kids-art-contest' ),
			'kac_rejected'     => __( 'Отклонена', 'kids-art-contest' ),
			'kac_disqualified' => __( 'Дисквалифицирована', 'kids-art-contest' ),
		);
		return $l[ $status ] ?? $status;
	}

	public static function menu(): void {
		$pending = KAC_Mailer::pending_count();
		$alerts  = current_user_can( 'kac_view_votes' ) ? KAC_Log::new_alerts_count() : 0;
		$badge   = static fn( int $n ) => $n ? ' <span class="awaiting-mod">' . $n . '</span>' : '';

		$full = current_user_can( 'kac_manage_contests' );
		if ( $full ) {
			$parent = self::ROOT;
			add_menu_page( __( 'Конкурс рисунков', 'kids-art-contest' ), __( 'Конкурс рисунков', 'kids-art-contest' ), 'kac_manage_contests', $parent, '', 'dashicons-art', 26 );
		} else {
			$parent = 'kac-moderation';
			add_menu_page( __( 'Конкурс рисунков', 'kids-art-contest' ), __( 'Конкурс рисунков', 'kids-art-contest' ) . $badge( $pending ), 'kac_moderate_works', $parent, array( 'KAC_Moderation_Page', 'render' ), 'dashicons-art', 26 );
		}

		if ( $full ) {
			add_submenu_page( $parent, __( 'Модерация', 'kids-art-contest' ), __( 'Модерация', 'kids-art-contest' ) . $badge( $pending ), 'kac_moderate_works', 'kac-moderation', array( 'KAC_Moderation_Page', 'render' ) );
		}
		add_submenu_page( $parent, __( 'Все работы', 'kids-art-contest' ), __( 'Все работы', 'kids-art-contest' ), 'kac_edit_works', 'kac-works', array( 'KAC_Work_Page', 'render_list' ) );
		add_submenu_page( $parent, __( 'Голоса', 'kids-art-contest' ), __( 'Голоса', 'kids-art-contest' ) . $badge( $alerts ), 'kac_view_votes', 'kac-votes', array( 'KAC_Votes_Page', 'render' ) );
		add_submenu_page( $parent, __( 'Итоги', 'kids-art-contest' ), __( 'Итоги', 'kids-art-contest' ), 'kac_publish_results', 'kac-results', array( 'KAC_Results_Page', 'render' ) );
		add_submenu_page( $parent, __( 'Настройки', 'kids-art-contest' ), __( 'Настройки', 'kids-art-contest' ), 'kac_manage_settings', 'kac-settings', array( 'KAC_Settings_Page', 'render' ) );
		global $submenu;
		if ( $full && isset( $submenu[ $parent ][0] ) ) {
			$submenu[ $parent ][0][0] = __( 'Конкурсы', 'kids-art-contest' ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride
		}
		// Скрытая страница карточки работы.
		add_submenu_page( 'options.php', __( 'Карточка работы', 'kids-art-contest' ), __( 'Карточка работы', 'kids-art-contest' ), 'kac_edit_works', 'kac-work', array( 'KAC_Work_Page', 'render_card' ) );
	}

	/** Последняя ошибка при отправке рисунка (для диагностики). */
	public static function last_error_notice(): void {
		$e = get_option( 'kac_last_error' );
		if ( ! $e || ! current_user_can( 'kac_moderate_works' ) ) {
			return;
		}
		if ( isset( $_GET['kac_clear_error'] ) && check_admin_referer( 'kac_clear_error' ) ) { // phpcs:ignore
			delete_option( 'kac_last_error' );
			return;
		}
		$screen = get_current_screen();
		if ( ! $screen || false === strpos( (string) $screen->id, 'kac' ) ) {
			return;
		}
		printf(
			'<div class="notice notice-error"><p><b>%s</b> %s — %s (%s). <a href="%s">%s</a></p></div>',
			esc_html__( 'Сбой при отправке рисунка:', 'kids-art-contest' ),
			esc_html( wp_date( 'd.m.Y H:i', (int) $e['time'] ) ),
			esc_html( (string) $e['msg'] ),
			esc_html( (string) $e['file'] ),
			esc_url( wp_nonce_url( add_query_arg( 'kac_clear_error', 1 ), 'kac_clear_error' ) ),
			esc_html__( 'Скрыть', 'kids-art-contest' )
		);
	}

	public static function menu_icon_css(): void {
		echo '<style>#adminmenu .awaiting-mod{margin-left:4px}</style>';
	}

	/** Счётчик «На модерации» в верхней панели (макет, экран 17). */
	public static function admin_bar( WP_Admin_Bar $bar ): void {
		if ( ! current_user_can( 'kac_moderate_works' ) ) {
			return;
		}
		$n = KAC_Mailer::pending_count();
		$bar->add_node(
			array(
				'id'    => 'kac-pending',
				'title' => '🎨 ' . esc_html__( 'На модерации', 'kids-art-contest' ) . ( $n ? ' <span class="ab-label" style="background:#d63638;color:#fff;border-radius:9px;padding:0 6px;font-size:11px;margin-left:4px">' . (int) $n . '</span>' : '' ),
				'href'  => admin_url( 'admin.php?page=kac-moderation' ),
			)
		);
	}

	/** Приветствие модератора на «Консоли» (макет, экран 17). */
	public static function dashboard_widget(): void {
		if ( ! current_user_can( 'kac_moderate_works' ) ) {
			return;
		}
		wp_add_dashboard_widget( 'kac_dashboard', '🎨 ' . __( 'Конкурс рисунков', 'kids-art-contest' ), array( __CLASS__, 'render_dashboard' ) );
	}

	public static function render_dashboard(): void {
		global $wpdb;
		$n      = KAC_Mailer::pending_count();
		$oldest = $wpdb->get_var( "SELECT MIN(post_date_gmt) FROM {$wpdb->posts} WHERE post_type='kac_work' AND post_status='kac_pending'" );
		$mine   = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->prefix}kac_log WHERE user_id = %d AND action IN ('approve','reject','disqualify') AND created_at > %s", get_current_user_id(), gmdate( 'Y-m-d H:i:s', time() - WEEK_IN_SECONDS ) ) );
		wp_enqueue_style( 'kac-admin', KAC_URL . 'admin/assets/admin.css', array(), KAC_VERSION );
		echo '<div class="kac-a"><div class="kpis" style="grid-template-columns:repeat(3,1fr)">';
		printf( '<div class="kpi warn"><b>%d</b><span>%s</span></div>', $n, esc_html__( 'работ ждут проверки', 'kids-art-contest' ) );
		printf( '<div class="kpi"><b>%s</b><span>%s</span></div>', $oldest ? esc_html( human_time_diff( strtotime( $oldest . ' UTC' ) ) ) : '—', esc_html__( 'самая старая в очереди', 'kids-art-contest' ) );
		printf( '<div class="kpi ok"><b>%d</b><span>%s</span></div>', $mine, esc_html__( 'проверено вами за неделю', 'kids-art-contest' ) );
		echo '</div><a class="wpbtn primary" href="' . esc_url( admin_url( 'admin.php?page=kac-moderation' ) ) . '">' . esc_html__( 'Начать модерацию →', 'kids-art-contest' ) . '</a></div>';
	}

	public static function parent_file( $parent_file ) {
		global $plugin_page, $current_screen;
		if ( in_array( $plugin_page, array( 'kac-work' ), true ) && ! current_user_can( 'kac_manage_contests' ) ) {
			return 'kac-moderation';
		}
		if ( 'kac-work' === $plugin_page && current_user_can( 'kac_manage_contests' ) ) {
			return self::ROOT;
		}
		return $parent_file;
	}

	public static function submenu_file( $submenu_file ) {
		global $plugin_page;
		if ( 'kac-work' === $plugin_page ) {
			return 'kac-works';
		}
		return $submenu_file;
	}

	/**
	 * Редактирование работы идёт через собственную карточку.
	 */
	public static function redirect_work_edit(): void {
		if ( isset( $_GET['post'], $_GET['action'] ) && 'edit' === $_GET['action'] && 'kac_work' === get_post_type( absint( $_GET['post'] ) ) ) { // phpcs:ignore
			wp_safe_redirect( admin_url( 'admin.php?page=kac-work&id=' . absint( $_GET['post'] ) ) ); // phpcs:ignore
			exit;
		}
	}

	/**
	 * Модератор после входа попадает на страницу модерации.
	 */
	public static function login_redirect( $redirect_to, $requested, $user ) {
		if ( $user instanceof WP_User && in_array( 'kac_moderator', (array) $user->roles, true ) && empty( $requested ) ) {
			return admin_url( 'admin.php?page=kac-moderation' );
		}
		return $redirect_to;
	}

	public static function assets( string $hook ): void {
		$screen = get_current_screen();
		$ours   = $screen && ( false !== strpos( (string) $screen->id, 'kac' ) || in_array( $screen->post_type, array( 'kac_contest', 'kac_work' ), true ) );
		if ( ! $ours ) {
			return;
		}
		wp_enqueue_style( 'kac-admin', KAC_URL . 'admin/assets/admin.css', array(), KAC_VERSION );
		wp_enqueue_script( 'kac-admin', KAC_URL . 'admin/assets/admin.js', array( 'wp-api-fetch' ), KAC_VERSION, true );
		wp_localize_script(
			'kac-admin',
			'KACA',
			array(
				'rest'   => esc_url_raw( rest_url( 'kac/v1' ) ),
				'nonce'  => wp_create_nonce( 'wp_rest' ),
				'reasons' => array_values( array_filter( array_map( 'trim', explode( "\n", (string) kac_setting( 'reject_reasons' ) ) ) ) ),
				'i18n'   => array(
					'confirmAnnul'   => __( 'Аннулировать выбранные голоса?', 'kids-art-contest' ),
					'confirmPublish' => __( 'Опубликовать итоги? Призёрам уйдут письма.', 'kids-art-contest' ),
					'confirmRecalc'  => __( 'Пересчитать итоги? Ручная правка порядка будет сброшена.', 'kids-art-contest' ),
					'error'          => __( 'Ошибка. Попробуйте ещё раз.', 'kids-art-contest' ),
					'chooseRules'    => __( 'Выберите файл положения (PDF)', 'kids-art-contest' ),
					'use'            => __( 'Выбрать', 'kids-art-contest' ),
					'warnChange'     => __( 'В конкурсе уже есть работы. Изменение возрастных групп или режима голосования может изменить их распределение. Продолжить?', 'kids-art-contest' ),
				),
			)
		);
		if ( $screen && 'kac_contest' === $screen->post_type ) {
			wp_enqueue_media();
		}
	}

	/**
	 * Экспорт (CSV/XLSX) через admin-post: скачивание файла.
	 */
	public static function handle_export(): void {
		$cid = isset( $_GET['contest'] ) ? absint( $_GET['contest'] ) : 0;
		check_admin_referer( 'kac_export_' . $cid );
		if ( ! current_user_can( 'kac_export' ) || 'kac_contest' !== get_post_type( $cid ) ) {
			wp_die( esc_html__( 'Нет доступа.', 'kids-art-contest' ), '', array( 'response' => 403 ) );
		}
		KAC_Results::download( $cid, isset( $_GET['format'] ) && 'xlsx' === $_GET['format'] ? 'xlsx' : 'csv' );
	}

	public static function export_url( int $cid, string $format ): string {
		return wp_nonce_url( admin_url( 'admin-post.php?action=kac_export&contest=' . $cid . '&format=' . $format ), 'kac_export_' . $cid );
	}

	/** Выпадающий выбор конкурса. */
	public static function contest_select( int $selected, string $name = 'contest', bool $all = false ): string {
		$contests = get_posts( array( 'post_type' => 'kac_contest', 'post_status' => array( 'publish', 'draft', 'pending', 'future', 'private' ), 'numberposts' => -1, 'orderby' => 'date', 'order' => 'DESC' ) );
		$out      = '<select name="' . esc_attr( $name ) . '">';
		if ( $all ) {
			$out .= '<option value="0">' . esc_html__( 'Все конкурсы', 'kids-art-contest' ) . '</option>';
		}
		foreach ( $contests as $c ) {
			$out .= '<option value="' . (int) $c->ID . '"' . selected( $selected, $c->ID, false ) . '>' . esc_html( get_the_title( $c ) ) . '</option>';
		}
		return $out . '</select>';
	}
}
