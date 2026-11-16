<?php
/**
 * Удаление плагина. Данные удаляются только при включённой настройке.
 *
 * @package KidsArtContest
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

$kac_settings = get_option( 'kac_settings', array() );
if ( empty( $kac_settings['delete_data_on_uninstall'] ) ) {
	return;
}

global $wpdb;

// Записи конкурсов и работ (включая мета).
$kac_ids = $wpdb->get_col( "SELECT ID FROM {$wpdb->posts} WHERE post_type IN ('kac_contest','kac_work')" );
foreach ( $kac_ids as $kac_id ) {
	foreach ( get_posts( array( 'post_type' => 'attachment', 'post_parent' => (int) $kac_id, 'post_status' => 'any', 'numberposts' => -1, 'fields' => 'ids' ) ) as $kac_att ) {
		wp_delete_attachment( (int) $kac_att, true );
	}
	wp_delete_post( (int) $kac_id, true );
}
wp_clear_scheduled_hook( 'kac_detect' );
wp_clear_scheduled_hook( 'kac_tick' );
wp_clear_scheduled_hook( 'kac_moderator_digest' );
wp_clear_scheduled_hook( 'kac_cleanup' );

foreach ( array( 'kac_participants', 'kac_votes', 'kac_log', 'kac_alerts' ) as $kac_table ) {
	$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}{$kac_table}" );
}

delete_option( 'kac_settings' );
delete_option( 'kac_db_version' );

// Роль и права.
remove_role( 'kac_moderator' );
$kac_admin = get_role( 'administrator' );
if ( $kac_admin ) {
	foreach ( array( 'kac_manage_contests', 'kac_moderate_works', 'kac_edit_works', 'kac_view_personal_data', 'kac_view_votes', 'kac_annul_votes', 'kac_publish_results', 'kac_export', 'kac_manage_settings' ) as $kac_cap ) {
		$kac_admin->remove_cap( $kac_cap );
	}
}

// Закрытые оригиналы.
$kac_uploads = wp_upload_dir();
$kac_private = trailingslashit( $kac_uploads['basedir'] ) . 'kac-private';
if ( is_dir( $kac_private ) ) {
	$kac_it = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $kac_private, FilesystemIterator::SKIP_DOTS ), RecursiveIteratorIterator::CHILD_FIRST );
	foreach ( $kac_it as $kac_file ) {
		$kac_file->isDir() ? rmdir( $kac_file->getPathname() ) : unlink( $kac_file->getPathname() );
	}
	rmdir( $kac_private );
}
