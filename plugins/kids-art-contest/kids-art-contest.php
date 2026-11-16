<?php
/**
 * Plugin Name:       Конкурс детского рисунка
 * Description:       Конкурсы детских рисунков: приём работ с модерацией, зрительское голосование без регистрации с защитой от накрутки, итоги и победители.
 * Version:           1.0.1
 * Requires at least: 6.3
 * Requires PHP:      8.0
 * Author:            —
 * Text Domain:       kids-art-contest
 * Domain Path:       /languages
 *
 * Спецификация — docs/SPEC.md, макет всех экранов — mockups/index.html.
 */

defined( 'ABSPATH' ) || exit;

define( 'KAC_VERSION', '1.0.1' );
define( 'KAC_DB_VERSION', '2' );
define( 'KAC_FILE', __FILE__ );
define( 'KAC_DIR', plugin_dir_path( __FILE__ ) );
define( 'KAC_URL', plugin_dir_url( __FILE__ ) );

require_once KAC_DIR . 'includes/helpers.php';
foreach ( array( 'phase', 'log', 'contest', 'security', 'captcha', 'post-types', 'participants', 'image', 'mailer', 'submission', 'voting', 'moderation', 'results', 'gallery', 'rest', 'frontend', 'blocks', 'activator', 'plugin' ) as $kac_class ) {
	require_once KAC_DIR . 'includes/class-' . $kac_class . '.php';
}

register_activation_hook( __FILE__, array( 'KAC_Activator', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'KAC_Activator', 'deactivate' ) );

KAC_Plugin::instance()->boot();
