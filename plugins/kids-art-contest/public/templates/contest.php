<?php
/**
 * Страница конкурса (макет, экран 1). Переменные: $contest_id, $with_content.
 */
defined( 'ABSPATH' ) || exit;

$phase  = KAC_Phase::get( $contest_id );
$d      = KAC_Phase::dates( $contest_id );
$groups = KAC_Contest::groups( $contest_id );
$base   = get_permalink( $contest_id );
$can_vote = KAC_Phase::can_vote( $contest_id );

// Режим «форма»: ?kac=submit.
if ( 'submission' === $phase && isset( $_GET['kac'] ) && 'submit' === $_GET['kac'] ) { // phpcs:ignore WordPress.Security.NonceVerification
	kac_template( 'form', array( 'contest_id' => $contest_id, 'standalone' => false ) );
	return;
}

if ( KAC_Phase::RESULTS === $phase ) {
	kac_template( 'results', array( 'contest_id' => $contest_id ) );
	return;
}

$rules_id  = (int) get_post_meta( $contest_id, '_kac_rules_file', true );
$rules_url = $rules_id ? wp_get_attachment_url( $rules_id ) : '';
list( $target, $target_label ) = Kac_Format::countdown_target( $contest_id );

$badge = array(
	'upcoming'   => array( 'soon', __( 'Скоро', 'kids-art-contest' ) . ( $target ? ' · ' . sprintf( __( 'старт %s', 'kids-art-contest' ), kac_date( $target, 'j F' ) ) : '' ) ),
	'submission' => array( 'live', $can_vote ? __( 'Идёт приём работ и голосование', 'kids-art-contest' ) : __( 'Идёт приём работ', 'kids-art-contest' ) ),
	'voting'     => array( 'live', __( 'Идёт голосование', 'kids-art-contest' ) ),
	'finished'   => array( 'done', __( 'Подводим итоги', 'kids-art-contest' ) ),
);
$bd = $badge[ $phase ] ?? array( 'soon', '' );

$counts   = KAC_Contest::work_counts( $contest_id );
$votes_n  = (int) $GLOBALS['wpdb']->get_var( $GLOBALS['wpdb']->prepare( 'SELECT COUNT(*) FROM ' . KAC_Voting::table() . " WHERE contest_id = %d AND status <> 'annulled'", $contest_id ) );
$show_votes = KAC_Contest::show_votes( $contest_id );
$show_leaders = KAC_Contest::show_leaders( $contest_id ) && $show_votes && in_array( $phase, array( 'submission', 'voting' ), true );
$now = time();

// Фазы для полоски: Приём / Голосование / Итоги.
$sub_end = $d['submit_end'];
$vot_end = $d['vote_end'];
$st_sub  = ( $sub_end && $now >= $sub_end ) ? 'done' : ( ( $d['submit_start'] && $now >= $d['submit_start'] ) ? 'on' : '' );
$st_vot  = ( $vot_end && $now >= $vot_end ) ? 'done' : ( $can_vote || ( $d['vote_start'] && $now >= $d['vote_start'] ) ? 'on' : '' );
$st_res  = in_array( $phase, array( 'finished', 'results' ), true ) ? 'on' : '';

$ages = sprintf( '%d–%d', KAC_Contest::min_age( $contest_id ), KAC_Contest::max_age( $contest_id ) );
$seed = wp_rand( 1, 999999 );
$res  = KAC_Gallery::query( $contest_id, array( 'sort' => 'new' ) );
$submit_url = add_query_arg( 'kac', 'submit', $base );
?>
<section class="kac kac-contest" data-contest="<?php echo (int) $contest_id; ?>" data-phase="<?php echo esc_attr( $phase ); ?>">
	<div class="hero">
		<div>
			<span class="badge <?php echo esc_attr( $bd[0] ); ?>"><?php echo esc_html( $bd[1] ); ?></span>
			<h1><?php echo esc_html( get_the_title( $contest_id ) ); ?></h1>
			<?php if ( $with_content ) : ?>
				<div class="desc"><?php echo wp_kses_post( wpautop( get_post_field( 'post_content', $contest_id ) ) ); ?></div>
			<?php endif; ?>
			<div class="chips">
				<?php if ( $d['submit_start'] || $d['submit_end'] ) : ?><span class="chip">📅 <?php echo esc_html( sprintf( __( 'Приём работ: %s', 'kids-art-contest' ), Kac_Format::range( $d['submit_start'], $d['submit_end'] ) ) ); ?></span><?php endif; ?>
				<?php if ( $d['vote_end'] ) : ?><span class="chip">🗳 <?php echo esc_html( sprintf( __( 'Голосование: до %s', 'kids-art-contest' ), kac_date( $d['vote_end'], 'j F' ) ) ); ?></span><?php endif; ?>
				<span class="chip">🧒 <?php echo esc_html( sprintf( __( 'Возраст: %s лет', 'kids-art-contest' ), $ages ) ); ?></span>
			</div>
			<div class="actions">
				<?php if ( 'submission' === $phase ) : ?><a class="btn primary" href="<?php echo esc_url( $submit_url ); ?>">🎨 <?php esc_html_e( 'Отправить рисунок', 'kids-art-contest' ); ?></a><?php endif; ?>
				<?php if ( $rules_url ) : ?><a class="btn ghost" href="<?php echo esc_url( $rules_url ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'Правила конкурса', 'kids-art-contest' ); ?></a><?php endif; ?>
			</div>
		</div>
		<div class="countdown">
			<?php if ( $target ) : ?>
				<div class="lbl"><?php echo esc_html( $target_label ); ?></div>
				<div class="cd" data-until="<?php echo (int) $target; ?>"><div><b>0</b><span><?php esc_html_e( 'дней', 'kids-art-contest' ); ?></span></div><div><b>00</b><span><?php esc_html_e( 'часов', 'kids-art-contest' ); ?></span></div><div><b>00</b><span><?php esc_html_e( 'минут', 'kids-art-contest' ); ?></span></div></div>
			<?php else : ?>
				<div class="lbl"><?php echo esc_html( 'finished' === $phase ? __( 'Приём работ и голосование завершены', 'kids-art-contest' ) : '' ); ?></div>
			<?php endif; ?>
			<div class="stats">
				<div><b><?php echo (int) $counts['publish']; ?></b><span><?php esc_html_e( 'работ', 'kids-art-contest' ); ?></span></div>
				<?php if ( $show_votes ) : ?><div><b><?php echo esc_html( number_format_i18n( $votes_n ) ); ?></b><span><?php esc_html_e( 'голосов', 'kids-art-contest' ); ?></span></div><?php endif; ?>
				<div><b><?php echo (int) count( $groups ); ?></b><span><?php echo esc_html( _n( 'группа', 'группы', count( $groups ), 'kids-art-contest' ) ); ?></span></div>
			</div>
			<div class="phase">
				<div class="<?php echo esc_attr( $st_sub ); ?>"><?php esc_html_e( 'Приём', 'kids-art-contest' ); ?></div>
				<div class="<?php echo esc_attr( $st_vot ); ?>"><?php esc_html_e( 'Голосование', 'kids-art-contest' ); ?></div>
				<div class="<?php echo esc_attr( $st_res ); ?>"><?php echo esc_html( __( 'Итоги', 'kids-art-contest' ) . ( $vot_end || $sub_end ? ' ' . kac_date( max( $vot_end, $sub_end ) + DAY_IN_SECONDS, 'd.m' ) : '' ) ); ?></div>
			</div>
		</div>
	</div>

	<?php if ( 'upcoming' !== $phase ) : ?>
	<div class="steps4">
		<div><i style="background:#ff6b5b">1</i><span><b><?php esc_html_e( 'Нарисуйте', 'kids-art-contest' ); ?></b><?php esc_html_e( 'Любые материалы: карандаши, краски, фломастеры', 'kids-art-contest' ); ?></span></div>
		<div><i style="background:#ffc23d">2</i><span><b><?php esc_html_e( 'Сфотографируйте', 'kids-art-contest' ); ?></b><?php esc_html_e( 'При дневном свете, рисунок целиком', 'kids-art-contest' ); ?></span></div>
		<div><i style="background:#22b88f">3</i><span><b><?php esc_html_e( 'Отправьте', 'kids-art-contest' ); ?></b><?php esc_html_e( 'Модератор проверит работу за 1–2 дня', 'kids-art-contest' ); ?></span></div>
		<div><i style="background:#4aa8ff">4</i><span><b><?php esc_html_e( 'Голосуйте', 'kids-art-contest' ); ?></b><?php esc_html_e( 'Позовите друзей поддержать работу', 'kids-art-contest' ); ?></span></div>
	</div>

	<div class="wrap">
		<div class="gal-layout"<?php echo ( $show_leaders || 'submission' === $phase ) ? '' : ' style="grid-template-columns:minmax(0,1fr)"'; ?>>
			<div>
				<div class="kac-gallery" data-contest="<?php echo (int) $contest_id; ?>" data-seed="<?php echo (int) $seed; ?>" data-page="1" data-pages="<?php echo (int) $res['pages']; ?>">
					<div class="toolbar">
						<?php if ( count( $groups ) > 1 ) : ?>
						<div class="tabs" role="tablist">
							<button type="button" class="on" data-g=""><?php esc_html_e( 'Все', 'kids-art-contest' ); ?></button>
							<?php foreach ( $groups as $g ) : ?><button type="button" data-g="<?php echo esc_attr( $g['key'] ); ?>"><?php echo esc_html( $g['label'] ); ?></button><?php endforeach; ?>
						</div>
						<?php endif; ?>
						<select class="sel" aria-label="<?php esc_attr_e( 'Сортировка', 'kids-art-contest' ); ?>">
							<option value="new"><?php esc_html_e( 'Сначала новые', 'kids-art-contest' ); ?></option>
							<?php if ( $show_votes ) : ?><option value="popular"><?php esc_html_e( 'Популярные', 'kids-art-contest' ); ?></option><?php endif; ?>
							<option value="random"><?php esc_html_e( 'Случайный порядок', 'kids-art-contest' ); ?></option>
						</select>
						<input class="search" type="search" placeholder="<?php esc_attr_e( 'Поиск по названию или №', 'kids-art-contest' ); ?>" aria-label="<?php esc_attr_e( 'Поиск', 'kids-art-contest' ); ?>">
					</div>
					<div class="grid" aria-live="polite"><?php echo KAC_Gallery::cards_html( $res['posts'], $contest_id ); // phpcs:ignore WordPress.Security.EscapeOutput ?></div>
					<p class="empty" <?php echo $res['total'] ? 'hidden' : ''; ?>><?php esc_html_e( 'Работ пока нет', 'kids-art-contest' ); ?></p>
					<div class="more" <?php echo $res['pages'] > 1 ? '' : 'hidden'; ?>><button type="button" class="btn ghost kac-more"><?php esc_html_e( 'Показать ещё работы', 'kids-art-contest' ); ?></button></div>
				</div>
			</div>
			<?php if ( $show_leaders || 'submission' === $phase ) : ?>
			<aside>
				<?php if ( $show_leaders ) { kac_template( 'leaders', array( 'contest_id' => $contest_id ) ); } ?>
				<?php if ( 'submission' === $phase ) : ?>
				<div class="side-card" style="margin-top:14px;background:#fff4e4;border-color:#f3dfbd">
					<h4><?php esc_html_e( 'Хотите участвовать?', 'kids-art-contest' ); ?></h4>
					<p style="margin:0 0 12px;font-size:13.5px;color:#666;line-height:1.5"><?php echo esc_html( sprintf( /* translators: %s: time left */ __( 'Приём работ открыт ещё %s. Работ от одного участника: %d.', 'kids-art-contest' ), human_time_diff( $now, $d['submit_end'] ?: $now ), KAC_Contest::max_works( $contest_id ) ) ); ?></p>
					<a class="btn primary sm block" href="<?php echo esc_url( $submit_url ); ?>"><?php esc_html_e( 'Отправить рисунок', 'kids-art-contest' ); ?></a>
				</div>
				<?php endif; ?>
			</aside>
			<?php endif; ?>
		</div>
	</div>
	<?php endif; ?>

	<?php if ( 'submission' === $phase ) : ?>
		<div class="sticky-cta fixed"><a class="btn primary block" href="<?php echo esc_url( $submit_url ); ?>">🎨 <?php esc_html_e( 'Отправить рисунок', 'kids-art-contest' ); ?></a></div>
	<?php endif; ?>
</section>
