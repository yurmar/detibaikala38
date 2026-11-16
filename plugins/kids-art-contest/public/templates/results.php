<?php
/**
 * Итоги (макет, экран 6). Переменная: $contest_id.
 */
defined( 'ABSPATH' ) || exit;
global $wpdb;
$phase = KAC_Phase::get( $contest_id );
$snap  = KAC_Phase::RESULTS === $phase ? KAC_Results::get_snapshot( $contest_id ) : null;
if ( ! $snap ) {
	echo '<div class="kac"><p class="empty">' . esc_html__( 'Итоги ещё не опубликованы.', 'kids-art-contest' ) . '</p></div>';
	return;
}
$show_votes = KAC_Contest::show_votes( $contest_id );
$works_n    = (int) KAC_Contest::work_counts( $contest_id )['publish'];
$voters_n   = (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(DISTINCT voter_hash) FROM ' . KAC_Voting::table() . " WHERE contest_id = %d AND status <> 'annulled'", $contest_id ) );
$groups     = array_filter( $snap['groups'], static fn( $g ) => ! empty( $g['items'] ) );
$first      = array_key_first( $groups );
$pod        = static function ( $it, int $place, string $cls, bool $crown ) use ( $show_votes ) {
	if ( ! $it ) {
		echo '<div></div>';
		return;
	}
	$w = get_post( (int) $it['work_id'] );
	if ( ! $w ) {
		echo '<div></div>';
		return;
	}
	$d = KAC_Gallery::work_data( $w );
	?>
	<div class="pod <?php echo esc_attr( $cls ); ?>">
		<?php if ( $crown ) : ?><div class="crown">👑</div><?php endif; ?>
		<div class="pic" data-open="<?php echo (int) $d['id']; ?>"><?php if ( $d['thumb'] ) : ?><img src="<?php echo esc_url( $d['thumb'] ); ?>" alt="<?php echo esc_attr( $d['title'] ); ?>"><?php endif; ?><span class="medal"><?php echo (int) $place; ?></span></div>
		<div class="info"><h3><?php echo esc_html( $d['title'] ); ?></h3><div class="who"><?php echo esc_html( $d['name'] . ', ' . Kac_Format::age( (int) $d['age'] ) ); ?></div><?php if ( $show_votes ) : ?><div class="vv">❤ <?php echo esc_html( sprintf( __( '%s голосов', 'kids-art-contest' ), number_format_i18n( (int) $it['votes'] ) ) ); ?></div><?php endif; ?></div>
		<div class="stand"><?php echo (int) $place; ?></div>
	</div>
	<?php
};
?>
<section class="kac kac-results" data-contest="<?php echo (int) $contest_id; ?>">
	<div class="res-hero">
		<span class="badge done"><?php esc_html_e( 'Конкурс завершён', 'kids-art-contest' ); ?></span>
		<h1><?php echo esc_html( sprintf( __( 'Итоги «%s»', 'kids-art-contest' ), get_the_title( $contest_id ) ) ); ?></h1>
		<p><?php echo esc_html( sprintf( __( 'Спасибо всем %1$s участникам и %2$s голосовавшим! Поздравляем победителей 🎉', 'kids-art-contest' ), number_format_i18n( $works_n ), number_format_i18n( $voters_n ) ) ); ?></p>
		<?php if ( count( $groups ) > 1 ) : ?>
		<div class="toolbar" style="justify-content:center;margin:16px 0 0"><div class="tabs kac-restabs">
			<?php foreach ( $groups as $gk => $g ) : ?><button type="button" class="<?php echo $gk === $first ? 'on' : ''; ?>" data-g="<?php echo esc_attr( (string) $gk ); ?>"><?php echo esc_html( $g['label'] ); ?></button><?php endforeach; ?>
		</div></div>
		<?php endif; ?>
	</div>

	<?php foreach ( $groups as $gk => $g ) : $items = $g['items']; $max = max( 1, (int) ( $items[0]['votes'] ?? 1 ) ); ?>
	<div class="res-tab-pane" data-g="<?php echo esc_attr( (string) $gk ); ?>" <?php echo $gk === $first ? '' : 'hidden'; ?>>
		<div class="podium">
			<?php $pod( $items[1] ?? null, 2, 'p2', false ); $pod( $items[0] ?? null, 1, 'p1', true ); $pod( $items[2] ?? null, 3, 'p3', false ); ?>
		</div>
		<div class="wrap">
			<?php $rows = array_slice( $items, 3, 7 ); if ( $rows ) : ?>
			<h3 style="font-size:20px;font-weight:900;margin:10px 0 12px"><?php esc_html_e( 'Лидеры голосования', 'kids-art-contest' ); ?></h3>
			<div class="ltable">
				<?php foreach ( $rows as $i => $it ) : $w = get_post( (int) $it['work_id'] ); if ( ! $w ) { continue; } $d = KAC_Gallery::work_data( $w ); ?>
				<div class="lrow" data-open="<?php echo (int) $d['id']; ?>" style="cursor:pointer">
					<span class="pl"><?php echo (int) $it['place']; ?></span>
					<?php if ( $d['thumb'] ) : ?><img src="<?php echo esc_url( $d['thumb'] ); ?>" alt="" loading="lazy"><?php else : ?><span></span><?php endif; ?>
					<span><b><?php echo esc_html( $d['title'] ); ?></b><br><span style="color:#888;font-size:13px"><?php echo esc_html( $d['name'] . ', ' . Kac_Format::age( (int) $d['age'] ) ); ?></span></span>
					<?php if ( $show_votes ) : ?><div class="bar"><i style="width:<?php echo (int) round( 100 * $it['votes'] / $max ); ?>%"></i></div><b style="color:var(--coral);text-align:right"><?php echo (int) $it['votes']; ?></b><?php else : ?><div></div><span></span><?php endif; ?>
				</div>
				<?php endforeach; ?>
			</div>
			<?php endif; ?>
			<h3 style="font-size:20px;font-weight:900;margin:26px 0 12px"><?php esc_html_e( 'Все работы группы', 'kids-art-contest' ); ?></h3>
			<div class="grid kac-gallery-static">
				<?php
				$posts = array();
				foreach ( $items as $it ) {
					$w = get_post( (int) $it['work_id'] );
					if ( $w ) {
						$posts[] = $w;
					}
				}
				echo KAC_Gallery::cards_html( array_slice( $posts, 0, 60 ), $contest_id, false ); // phpcs:ignore WordPress.Security.EscapeOutput
				?>
			</div>
		</div>
	</div>
	<?php endforeach; ?>
</section>
