<?php
/**
 * Карточка работы (макет: .wcard). Переменные: $d (данные работы), $vote ('open' | 'soon' | 'closed' | 'none'), $vote_note.
 */
defined( 'ABSPATH' ) || exit;
$who = trim( $d['name'] . ( $d['age'] ? ', ' . Kac_Format::age( $d['age'] ) : '' ) ) . ( $d['group'] ? ' · ' . $d['group'] : '' );
?>
<article class="wcard" id="work-<?php echo (int) $d['id']; ?>" data-id="<?php echo (int) $d['id']; ?>"
	data-number="<?php echo esc_attr( str_pad( (string) $d['number'], 3, '0', STR_PAD_LEFT ) ); ?>"
	data-title="<?php echo esc_attr( $d['title'] ); ?>"
	data-desc="<?php echo esc_attr( $d['desc'] ); ?>"
	data-author="<?php echo esc_attr( trim( $d['name'] . ', ' . Kac_Format::age( (int) $d['age'] ), ', ' ) ); ?>"
	data-group="<?php echo esc_attr( $d['group'] ); ?>"
	data-tech="<?php echo esc_attr( $d['tech'] ); ?>"
	data-large="<?php echo esc_url( (string) $d['large'] ); ?>"
	data-url="<?php echo esc_url( $d['url'] ); ?>"
	data-votes="<?php echo null === $d['votes'] ? '' : (int) $d['votes']; ?>"
	data-vstate="<?php echo esc_attr( $vote ); ?>">
	<div class="img" data-open="<?php echo (int) $d['id']; ?>" role="button" tabindex="0" aria-label="<?php echo esc_attr( sprintf( /* translators: %s: title */ __( 'Открыть рисунок «%s»', 'kids-art-contest' ), $d['title'] ) ); ?>">
		<?php if ( $d['thumb'] ) : ?><img src="<?php echo esc_url( $d['thumb'] ); ?>" alt="<?php echo esc_attr( $d['title'] ); ?>" loading="lazy"><?php endif; ?>
		<span class="num">№<?php echo esc_html( str_pad( (string) $d['number'], 3, '0', STR_PAD_LEFT ) ); ?></span><span class="zoom">🔍</span>
	</div>
	<div class="body">
		<h3><?php echo esc_html( $d['title'] ); ?></h3>
		<div class="who"><?php echo esc_html( $who ); ?></div>
		<div class="foot">
			<?php if ( null !== $d['votes'] ) : ?><span class="votes" data-vc="<?php echo (int) $d['id']; ?>">❤ <?php echo esc_html( number_format_i18n( (int) $d['votes'] ) ); ?></span><?php endif; ?>
			<?php if ( 'open' === $vote ) : ?>
				<button type="button" class="vbtn" data-vote="<?php echo (int) $d['id']; ?>"><?php echo esc_html( (string) kac_setting( 'vote_button_text', 'Проголосовать за работу' ) ); ?></button>
			<?php elseif ( 'soon' === $vote || 'closed' === $vote ) : ?>
				<button type="button" class="vbtn closed" disabled><?php echo esc_html( $vote_note ); ?></button>
			<?php endif; ?>
		</div>
	</div>
</article>
