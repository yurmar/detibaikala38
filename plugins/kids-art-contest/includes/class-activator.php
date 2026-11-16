<?php
/**
 * Активация: таблицы, роль и права, rewrite.
 *
 * @package KidsArtContest
 */

defined( 'ABSPATH' ) || exit;

class KAC_Activator {

	public static function activate(): void {
		self::install();
		KAC_Post_Types::register();
		flush_rewrite_rules();
	}

	public static function deactivate(): void {
		foreach ( array( 'kac_detect', 'kac_tick', 'kac_moderator_digest', 'kac_cleanup' ) as $hook ) {
			wp_clear_scheduled_hook( $hook );
		}
		flush_rewrite_rules();
	}

	/**
	 * Идемпотентная установка/обновление схемы и прав.
	 */
	public static function install(): void {
		self::create_tables();
		self::setup_roles();
		update_option( 'kac_db_version', KAC_DB_VERSION );
		add_option( 'kac_settings', kac_default_settings() );
	}

	private static function create_tables(): void {
		global $wpdb;
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$charset = $wpdb->get_charset_collate();
		$p       = $wpdb->prefix;

		// dbDelta: каждое поле с новой строки, два пробела после PRIMARY KEY.
		$sql = array();

		$sql[] = "CREATE TABLE {$p}kac_participants (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  work_id bigint(20) unsigned NOT NULL,
  child_last varchar(100) NOT NULL DEFAULT '',
  child_first varchar(100) NOT NULL DEFAULT '',
  child_middle varchar(100) NOT NULL DEFAULT '',
  child_age tinyint(3) unsigned NOT NULL DEFAULT 0,
  city varchar(190) NOT NULL DEFAULT '',
  school varchar(190) NOT NULL DEFAULT '',
  teacher varchar(190) NOT NULL DEFAULT '',
  parent_last varchar(100) NOT NULL DEFAULT '',
  parent_first varchar(100) NOT NULL DEFAULT '',
  parent_middle varchar(100) NOT NULL DEFAULT '',
  parent_relation varchar(40) NOT NULL DEFAULT '',
  email varchar(190) NOT NULL DEFAULT '',
  phone varchar(20) NOT NULL DEFAULT '',
  consents longtext NULL,
  consent_version smallint(5) unsigned NOT NULL DEFAULT 0,
  newsletter tinyint(1) NOT NULL DEFAULT 0,
  ip_hash char(64) NOT NULL DEFAULT '',
  created_at datetime NOT NULL,
  PRIMARY KEY  (id),
  UNIQUE KEY work_id (work_id),
  KEY email (email)
) $charset;";

		$sql[] = "CREATE TABLE {$p}kac_votes (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  contest_id bigint(20) unsigned NOT NULL,
  work_id bigint(20) unsigned NOT NULL,
  voter_hash char(64) NOT NULL,
  fp_hash char(64) NOT NULL DEFAULT '',
  ip_hash char(64) NOT NULL DEFAULT '',
  ip_masked varchar(45) NOT NULL DEFAULT '',
  subnet_hash char(64) NOT NULL DEFAULT '',
  ua_hash char(32) NOT NULL DEFAULT '',
  ua_label varchar(60) NOT NULL DEFAULT '',
  risk tinyint(3) unsigned NOT NULL DEFAULT 0,
  status enum('valid','suspicious','annulled') NOT NULL DEFAULT 'valid',
  created_at datetime NOT NULL,
  PRIMARY KEY  (id),
  UNIQUE KEY uniq_vote (work_id,voter_hash),
  KEY contest_voter (contest_id,voter_hash),
  KEY work_time (work_id,created_at),
  KEY ip_time (ip_hash,created_at)
) $charset;";

		$sql[] = "CREATE TABLE {$p}kac_log (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  object_type varchar(30) NOT NULL,
  object_id bigint(20) unsigned NOT NULL DEFAULT 0,
  user_id bigint(20) unsigned NOT NULL DEFAULT 0,
  action varchar(60) NOT NULL,
  data longtext NULL,
  created_at datetime NOT NULL,
  PRIMARY KEY  (id),
  KEY object (object_type,object_id),
  KEY created_at (created_at)
) $charset;";

		$sql[] = "CREATE TABLE {$p}kac_alerts (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  type varchar(40) NOT NULL,
  contest_id bigint(20) unsigned NOT NULL DEFAULT 0,
  work_id bigint(20) unsigned NOT NULL DEFAULT 0,
  data longtext NULL,
  status varchar(20) NOT NULL DEFAULT 'new',
  created_at datetime NOT NULL,
  PRIMARY KEY  (id),
  KEY contest_status (contest_id,status)
) $charset;";

		foreach ( $sql as $statement ) {
			dbDelta( $statement );
		}
	}

	/**
	 * Роль «Модератор конкурса» и права администратора.
	 * Права выдаются только если ещё не заданы, чтобы не затирать настройки.
	 */
	public static function setup_roles(): void {
		$admin = get_role( 'administrator' );
		if ( $admin ) {
			foreach ( kac_all_caps() as $cap ) {
				$admin->add_cap( $cap );
			}
		}

		if ( ! get_role( 'kac_moderator' ) ) {
			$caps = array( 'read' => true );
			foreach ( kac_moderator_default_caps() as $cap ) {
				$caps[ $cap ] = true;
			}
			add_role( 'kac_moderator', __( 'Модератор конкурса', 'kids-art-contest' ), $caps );
		}
	}

	/**
	 * Полное удаление ролей и прав (uninstall).
	 */
	public static function remove_roles(): void {
		remove_role( 'kac_moderator' );
		$admin = get_role( 'administrator' );
		if ( $admin ) {
			foreach ( kac_all_caps() as $cap ) {
				$admin->remove_cap( $cap );
			}
		}
	}
}
