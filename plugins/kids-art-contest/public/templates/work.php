<?php
/**
 * Страница работы с OG-тегами. Переменная: $work (WP_Post).
 */
defined( 'ABSPATH' ) || exit;
$d   = KAC_Gallery::work_data( $work );
$cid = (int) $work->post_parent;
$st  = KAC_Gallery::vote_state( $cid );
$g   = $d['group'];
?>
<div class="kac work-page" data-contest="<?php echo (int) $cid; ?>">
	<div class="lb-box">
		<div class="lb-stage"><?php if ( $d['large'] ) : ?><img src="<?php echo esc_url( $d['large'] ); ?>" alt="<?php echo esc_attr( $d['title'] ); ?>"><?php endif; ?></div>
		<div class="lb-info">
			<span style="font-size:12.5px;color:#999;font-weight:800"><?php echo esc_html( sprintf( __( 'РАБОТА №%s', 'kids-art-contest' ), str_pad( (string) $d['number'], 3, '0', STR_PAD_LEFT ) ) ); ?></span>
			<h3><?php echo esc_html( $d['title'] ); ?></h3>
			<div class="who"><?php esc_html_e( 'Автор:', 'kids-art-contest' ); ?> <b><?php echo esc_html( $d['name'] ); ?></b>, <?php echo esc_html( Kac_Format::age( (int) $d['age'] ) ); ?></div>
			<div class="lb-meta"><?php if ( $g ) : ?><span><?php echo esc_html( $g ); ?></span><?php endif; ?><?php if ( $d['tech'] ) : ?><span><?php echo esc_html( $d['tech'] ); ?></span><?php endif; ?></div>
			<?php if ( $d['desc'] ) : ?><p><?php echo esc_html( $d['desc'] ); ?></p><?php endif; ?>
			<?php if ( null !== $d['votes'] ) : ?><div class="lb-votes"><b data-lbv="<?php echo (int) $d['id']; ?>"><?php echo (int) $d['votes']; ?></b><span><?php esc_html_e( 'голосов', 'kids-art-contest' ); ?></span></div><?php endif; ?>
			<?php if ( 'open' === $st[0] ) : ?>
				<button type="button" class="vbtn big" data-vote="<?php echo (int) $d['id']; ?>"><?php echo esc_html( (string) kac_setting( 'vote_button_text', 'Проголосовать за работу' ) ); ?></button>
				<div style="font-size:12.5px;color:#999;text-align:center"><?php esc_html_e( 'Голосовать можно один раз за каждую работу', 'kids-art-contest' ); ?></div>
			<?php elseif ( $st[1] ) : ?>
				<button type="button" class="vbtn big closed" disabled><?php echo esc_html( $st[1] ); ?></button>
			<?php endif; ?>
			<div class="share" data-url="<?php echo esc_url( $d['url'] ); ?>" data-title="<?php echo esc_attr( $d['title'] ); ?>"><button type="button" data-share="link">🔗 <?php esc_html_e( 'Ссылка', 'kids-art-contest' ); ?></button><button type="button" data-share="vk">VK</button><button type="button" data-share="tg">Telegram</button></div>
			<a class="btn ghost sm" href="<?php echo esc_url( get_permalink( $cid ) . '#work-' . $d['id'] ); ?>" style="align-self:flex-start"><?php esc_html_e( 'Все работы конкурса', 'kids-art-contest' ); ?></a>
		</div>
	</div>
</div>
