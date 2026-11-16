<?php
/**
 * Архив конкурсов /konkursy/ (макет, экран 7).
 */
defined( 'ABSPATH' ) || exit;

KAC_Frontend::need_assets();
$ids = get_posts( array( 'post_type' => 'kac_contest', 'post_status' => 'publish', 'numberposts' => 100, 'fields' => 'ids', 'orderby' => 'date', 'order' => 'DESC' ) );
$inner = static function () use ( $ids ) {
	echo '<div class="kac"><div class="wrap"><h2 style="font-size:30px;font-weight:900;margin:0 0 16px">' . esc_html__( 'Конкурсы детского рисунка', 'kids-art-contest' ) . '</h2>';
	kac_template( 'contest-list', array( 'ids' => $ids ) );
	echo '</div></div>';
};

if ( wp_is_block_theme() ) {
	?><!doctype html>
	<html <?php language_attributes(); ?>><head><meta charset="<?php bloginfo( 'charset' ); ?>"><meta name="viewport" content="width=device-width, initial-scale=1"><?php wp_head(); ?></head>
	<body <?php body_class(); ?>><?php wp_body_open(); ?>
	<main class="kac-standalone"><?php $inner(); ?></main>
	<?php wp_footer(); ?></body></html>
	<?php
	return;
}
get_header();
?>
<main id="primary" class="site-main kac-archive"><?php $inner(); ?></main>
<?php
get_footer();
