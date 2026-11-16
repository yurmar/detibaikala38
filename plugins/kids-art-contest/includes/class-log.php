<?php
defined( 'ABSPATH' ) || exit;

/**
 * Журнал действий и тревоги антифрода.
 */
class KAC_Log {

	public static function add( string $type, int $object_id, string $action, array $data = array() ): void {
		global $wpdb;
		$wpdb->insert(
			$wpdb->prefix . 'kac_log',
			array(
				'object_type' => $type,
				'object_id'   => $object_id,
				'user_id'     => get_current_user_id(),
				'action'      => $action,
				'data'        => wp_json_encode( $data ),
				'created_at'  => gmdate( 'Y-m-d H:i:s' ),
			)
		);
	}

	public static function for_object( string $type, int $object_id, int $limit = 100 ): array {
		global $wpdb;
		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}kac_log WHERE object_type = %s AND object_id = %d ORDER BY id DESC LIMIT %d",
				$type,
				$object_id,
				$limit
			)
		);
	}

	public static function alert( string $type, int $contest_id, int $work_id, array $data = array() ): int {
		global $wpdb;
		$wpdb->insert(
			$wpdb->prefix . 'kac_alerts',
			array(
				'type'       => $type,
				'contest_id' => $contest_id,
				'work_id'    => $work_id,
				'data'       => wp_json_encode( $data ),
				'status'     => 'new',
				'created_at' => gmdate( 'Y-m-d H:i:s' ),
			)
		);
		return (int) $wpdb->insert_id;
	}

	public static function new_alerts_count(): int {
		global $wpdb;
		return (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}kac_alerts WHERE status = 'new'" );
	}
}
