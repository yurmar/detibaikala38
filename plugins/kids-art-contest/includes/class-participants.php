<?php
defined( 'ABSPATH' ) || exit;

/**
 * Персональные данные участников (отдельная таблица), экспорт/удаление/срок хранения.
 */
class KAC_Participants {

	public static function init(): void {
		add_filter( 'wp_privacy_personal_data_exporters', array( __CLASS__, 'register_exporter' ) );
		add_filter( 'wp_privacy_personal_data_erasers', array( __CLASS__, 'register_eraser' ) );
		add_action( 'kac_cleanup', array( __CLASS__, 'cleanup' ) );
		add_action( 'before_delete_post', array( __CLASS__, 'on_delete_work' ) );
	}

	/**
	 * При окончательном удалении работы стираем вложения, оригинал, ПД и голоса.
	 */
	public static function on_delete_work( int $post_id ): void {
		global $wpdb;
		if ( 'kac_work' !== get_post_type( $post_id ) ) {
			return;
		}
		$atts = get_posts( array( 'post_type' => 'attachment', 'post_parent' => $post_id, 'post_status' => 'any', 'numberposts' => -1, 'fields' => 'ids' ) );
		foreach ( $atts as $att ) {
			wp_delete_attachment( (int) $att, true );
		}
		$orig = (string) get_post_meta( $post_id, '_kac_original_path', true );
		if ( $orig && file_exists( $orig ) && false !== strpos( $orig, 'kac-private' ) ) {
			@unlink( $orig ); // phpcs:ignore
		}
		$wpdb->delete( self::table(), array( 'work_id' => $post_id ) );
		$wpdb->delete( $wpdb->prefix . 'kac_votes', array( 'work_id' => $post_id ) );
	}

	public static function table(): string {
		global $wpdb;
		return $wpdb->prefix . 'kac_participants';
	}

	public static function get( int $work_id, bool $log_view = false ): ?object {
		global $wpdb;
		$row = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . self::table() . ' WHERE work_id = %d', $work_id ) );
		if ( $row && $log_view ) {
			KAC_Log::add( 'work', $work_id, 'view_personal_data' );
		}
		return $row ?: null;
	}

	public static function insert( int $work_id, array $data ): void {
		global $wpdb;
		$data['work_id']    = $work_id;
		$data['created_at'] = gmdate( 'Y-m-d H:i:s' );
		$wpdb->insert( self::table(), $data );
	}

	public static function update( int $work_id, array $data ): void {
		global $wpdb;
		$wpdb->update( self::table(), $data, array( 'work_id' => $work_id ) );
	}

	/* ---- Privacy tools WordPress ---- */

	public static function register_exporter( array $exporters ): array {
		$exporters['kids-art-contest'] = array(
			'exporter_friendly_name' => __( 'Конкурс детского рисунка', 'kids-art-contest' ),
			'callback'               => array( __CLASS__, 'export' ),
		);
		return $exporters;
	}

	public static function register_eraser( array $erasers ): array {
		$erasers['kids-art-contest'] = array(
			'eraser_friendly_name' => __( 'Конкурс детского рисунка', 'kids-art-contest' ),
			'callback'             => array( __CLASS__, 'erase' ),
		);
		return $erasers;
	}

	public static function export( string $email ): array {
		global $wpdb;
		$rows  = $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . self::table() . ' WHERE email = %s', $email ) );
		$items = array();
		foreach ( $rows as $r ) {
			$work    = get_post( (int) $r->work_id );
			$items[] = array(
				'group_id'    => 'kac_participants',
				'group_label' => __( 'Участие в конкурсе рисунка', 'kids-art-contest' ),
				'item_id'     => 'kac-' . $r->id,
				'data'        => array(
					array( 'name' => __( 'Работа', 'kids-art-contest' ), 'value' => $work ? $work->post_title : '' ),
					array( 'name' => __( 'Ребёнок', 'kids-art-contest' ), 'value' => trim( "{$r->child_last} {$r->child_first} {$r->child_middle}" ) ),
					array( 'name' => __( 'Возраст', 'kids-art-contest' ), 'value' => $r->child_age ),
					array( 'name' => __( 'Город', 'kids-art-contest' ), 'value' => $r->city ),
					array( 'name' => __( 'Школа / студия', 'kids-art-contest' ), 'value' => $r->school ),
					array( 'name' => __( 'Педагог', 'kids-art-contest' ), 'value' => $r->teacher ),
					array( 'name' => __( 'Представитель', 'kids-art-contest' ), 'value' => trim( "{$r->parent_last} {$r->parent_first} {$r->parent_middle}" ) ),
					array( 'name' => 'Email', 'value' => $r->email ),
					array( 'name' => __( 'Телефон', 'kids-art-contest' ), 'value' => $r->phone ),
					array( 'name' => __( 'Дата заявки', 'kids-art-contest' ), 'value' => $r->created_at ),
				),
			);
		}
		return array( 'data' => $items, 'done' => true );
	}

	public static function erase( string $email ): array {
		global $wpdb;
		$rows    = $wpdb->get_results( $wpdb->prepare( 'SELECT work_id FROM ' . self::table() . ' WHERE email = %s', $email ) );
		$removed = false;
		foreach ( $rows as $r ) {
			self::anonymize( (int) $r->work_id );
			wp_update_post( array( 'ID' => (int) $r->work_id, 'post_status' => 'kac_disqualified' ) );
			KAC_Log::add( 'work', (int) $r->work_id, 'erased_personal_data' );
			$removed = true;
		}
		return array( 'items_removed' => $removed, 'items_retained' => false, 'messages' => array(), 'done' => true );
	}

	/**
	 * Стирает ПД, оставляя строку (связь с работой и возраст для итогов).
	 */
	public static function anonymize( int $work_id ): void {
		global $wpdb;
		$wpdb->update(
			self::table(),
			array(
				'child_last' => '', 'child_first' => '', 'child_middle' => '', 'city' => '', 'school' => '', 'teacher' => '',
				'parent_last' => '', 'parent_first' => '', 'parent_middle' => '', 'parent_relation' => '',
				'email' => '', 'phone' => '', 'ip_hash' => '',
			),
			array( 'work_id' => $work_id )
		);
		delete_post_meta( $work_id, '_kac_public_name' );
		update_post_meta( $work_id, '_kac_public_name', __( 'Участник', 'kids-art-contest' ) );
	}

	/**
	 * Автоудаление ПД и IP через настраиваемый срок после окончания конкурса.
	 */
	public static function cleanup(): void {
		global $wpdb;
		$pd_days = (int) kac_setting( 'retention_pd_days' );
		$ip_days = (int) kac_setting( 'retention_ip_days' );
		$contests = get_posts( array( 'post_type' => 'kac_contest', 'post_status' => 'publish', 'numberposts' => -1, 'fields' => 'ids' ) );
		foreach ( $contests as $cid ) {
			$d   = KAC_Phase::dates( (int) $cid );
			$end = max( $d['submit_end'], $d['vote_end'] );
			if ( ! $end || $end > time() ) {
				continue;
			}
			if ( $ip_days > 0 && time() > $end + $ip_days * DAY_IN_SECONDS ) {
				$wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->prefix}kac_votes SET ip_hash = '', ip_masked = '', subnet_hash = '', ua_hash = '' WHERE contest_id = %d AND ip_hash <> ''", $cid ) );
				$wpdb->query( $wpdb->prepare( "UPDATE " . self::table() . " pt JOIN {$wpdb->posts} p ON p.ID = pt.work_id SET pt.ip_hash = '' WHERE p.post_parent = %d", $cid ) );
			}
			if ( $pd_days > 0 && time() > $end + $pd_days * DAY_IN_SECONDS ) {
				$ids = $wpdb->get_col( $wpdb->prepare( 'SELECT pt.work_id FROM ' . self::table() . " pt JOIN {$wpdb->posts} p ON p.ID = pt.work_id WHERE p.post_parent = %d AND pt.email <> ''", $cid ) );
				foreach ( $ids as $wid ) {
					self::anonymize( (int) $wid );
				}
			}
		}
	}
}
