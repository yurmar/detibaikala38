<?php
/**
 * Загрузчик плагина.
 *
 * @package KidsArtContest
 */

defined( 'ABSPATH' ) || exit;

final class KAC_Plugin {

	private static ?KAC_Plugin $instance = null;

	public static function instance(): KAC_Plugin {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	public function boot(): void {
		add_action( 'plugins_loaded', array( $this, 'on_plugins_loaded' ) );
		add_filter( 'cron_schedules', array( $this, 'cron_schedules' ) );
		add_action( 'init', array( $this, 'ensure_cron' ) );

		KAC_Post_Types::init();
		KAC_Image::init();
		KAC_Participants::init();
		KAC_Mailer::init();
		KAC_Voting::init();
		KAC_Results::init();
		KAC_REST::init();
		KAC_Frontend::init();
		KAC_Blocks::init();

		if ( is_admin() ) {
			foreach ( array( 'admin-menu', 'contest-metaboxes', 'moderation-page', 'work-page', 'votes-page', 'results-page', 'settings-page' ) as $f ) {
				require_once KAC_DIR . 'admin/class-' . $f . '.php';
			}
			KAC_Admin_Menu::init();
			KAC_Contest_Metaboxes::init();
			KAC_Votes_Page::init();
			KAC_Results_Page::init();
			KAC_Settings_Page::init();
		}
	}

	public function cron_schedules( array $s ): array {
		$s['kac_10min'] = array( 'interval' => 600, 'display' => 'Каждые 10 минут (Конкурс рисунка)' );
		return $s;
	}

	/**
	 * Регулярные задачи: детекторы накрутки, страховка итогов, очистка ПД, дайджест модераторам.
	 */
	public function ensure_cron(): void {
		$jobs = array(
			'kac_detect'           => 'kac_10min',
			'kac_tick'             => 'hourly',
			'kac_moderator_digest' => 'hourly',
			'kac_cleanup'          => 'daily',
		);
		foreach ( $jobs as $hook => $recurrence ) {
			if ( ! wp_next_scheduled( $hook ) ) {
				wp_schedule_event( time() + 120, $recurrence, $hook );
			}
		}
	}

	public function on_plugins_loaded(): void {
		load_plugin_textdomain( 'kids-art-contest', false, dirname( plugin_basename( KAC_FILE ) ) . '/languages' );

		// Одноразово выдаём существующей роли модератора все права (все разделы меню).
		if ( '2' !== get_option( 'kac_roles_ver' ) ) {
			$mod = get_role( 'kac_moderator' );
			if ( $mod ) {
				foreach ( kac_all_caps() as $cap ) {
					$mod->add_cap( $cap );
				}
			}
			update_option( 'kac_roles_ver', '2' );
		}

		// Обновление схемы БД после апдейта плагина.
		if ( get_option( 'kac_db_version' ) !== KAC_DB_VERSION ) {
			KAC_Activator::install();
		}
	}
}
