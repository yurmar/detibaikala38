<?php
/**
 * Лидеры (макет: .side-card + .leader). Переменные: $contest_id.
 */
defined( 'ABSPATH' ) || exit;
$posts = KAC_Gallery::leaders( $contest_id, '', 5 );
if ( ! $posts ) {
	return;
}
$cls = array( 'g', 's', 'b' );
?>
<div class="side-card kac-leaders" data-contest="<?php echo (int) $contest_id; ?>">
	<h4>🏆 <?php esc_html_e( 'Лидеры голосования', 'kids-art-contest' ); ?></h4>
	<div>
	<?php foreach ( $posts as $i => $w ) : $d = KAC_Gallery::work_data( $w ); ?>
		<div class="leader" data-open="<?php echo (int) $d['id']; ?>" style="cursor:pointer">
			<span class="pl <?php echo esc_attr( $cls[ $i ] ?? '' ); ?>"><?php echo (int) ( $i + 1 ); ?></span>
			<?php if ( $d['thumb'] ) : ?><img src="<?php echo esc_url( $d['thumb'] ); ?>" alt="" loading="lazy"><?php endif; ?>
			<span class="t"><b><?php echo esc_html( $d['title'] ); ?></b><span><?php echo esc_html( $d['name'] . ', ' . Kac_Format::age( (int) $d['age'] ) ); ?></span></span>
			<span class="v"><?php echo (int) $d['votes']; ?></span>
		</div>
	<?php endforeach; ?>
	</div>
</div>
