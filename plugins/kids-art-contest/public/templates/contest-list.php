<?php
/**
 * Список конкурсов (макет, экран 7). Переменная: $ids.
 */
defined( 'ABSPATH' ) || exit;
global $wpdb;
$order = array( 'submission' => 0, 'voting' => 0, 'upcoming' => 1, 'finished' => 2, 'results' => 2 );
usort( $ids, static fn( $a, $b ) => ( $order[ KAC_Phase::get( (int) $a ) ] ?? 3 ) <=> ( $order[ KAC_Phase::get( (int) $b ) ] ?? 3 ) );
?>
<div class="kac kac-contests">
	<?php if ( ! $ids ) : ?><p class="empty"><?php esc_html_e( 'Конкурсов пока нет.', 'kids-art-contest' ); ?></p><?php endif; ?>
	<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(min(100%,420px),1fr));gap:16px">
	<?php foreach ( $ids as $id ) :
		$id    = (int) $id;
		$ph    = KAC_Phase::get( $id );
		$d     = KAC_Phase::dates( $id );
		$works = (int) KAC_Contest::work_counts( $id )['publish'];
		$votes = (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM ' . KAC_Voting::table() . " WHERE contest_id = %d AND status <> 'annulled'", $id ) );
		$img   = get_the_post_thumbnail_url( $id, 'medium_large' );
		$badge = array(
			'submission' => array( 'live', __( 'Идёт приём работ', 'kids-art-contest' ) ),
			'voting'     => array( 'live', __( 'Идёт голосование', 'kids-art-contest' ) ),
			'upcoming'   => array( 'soon', sprintf( __( 'Скоро · старт %s', 'kids-art-contest' ), kac_date( $d['submit_start'] ?: $d['vote_start'], 'j F' ) ) ),
			'finished'   => array( 'done', __( 'Подводим итоги', 'kids-art-contest' ) ),
			'results'    => array( 'done', __( 'Итоги подведены', 'kids-art-contest' ) ),
		)[ $ph ] ?? array( 'soon', '' );
		$snap = KAC_Phase::RESULTS === $ph ? KAC_Results::get_snapshot( $id ) : null;
		$wins = array();
		if ( $snap ) {
			foreach ( $snap['groups'] as $g ) {
				if ( ! empty( $g['items'][0] ) ) {
					$wins[] = (int) $g['items'][0]['work_id'];
				}
			}
			$wins = array_slice( $wins, 0, 3 );
		}
		?>
		<div class="wcard archive-card">
			<div class="pic" style="<?php echo $img ? 'background-image:url(' . esc_url( $img ) . ')' : 'background:#f3efe6'; ?>"></div>
			<div class="body" style="padding:16px;gap:8px">
				<span class="badge <?php echo esc_attr( $badge[0] ); ?>"><?php echo esc_html( $badge[1] ); ?></span>
				<h3 style="font-size:19px"><?php echo esc_html( get_the_title( $id ) ); ?></h3>
				<div class="who"><?php echo esc_html( Kac_Format::range( $d['submit_start'] ?: $d['vote_start'], max( $d['vote_end'], $d['submit_end'] ) ) . ' · ' . KAC_Contest::min_age( $id ) . '–' . KAC_Contest::max_age( $id ) . ' ' . __( 'лет', 'kids-art-contest' ) ); ?></div>
				<div class="who"><?php echo esc_html( 'upcoming' === $ph ? __( 'Приём работ ещё не начался', 'kids-art-contest' ) : sprintf( __( '%1$s работ · %2$s голосов', 'kids-art-contest' ), number_format_i18n( $works ), number_format_i18n( $votes ) ) ); ?></div>
				<?php if ( $wins ) : ?><div class="win"><?php foreach ( $wins as $i => $wid ) : $u = get_the_post_thumbnail_url( $wid, 'kac_thumb' ); if ( $u ) : ?><img src="<?php echo esc_url( $u ); ?>" alt=""><?php endif; endforeach; ?><span style="font-size:12.5px;color:#888"><?php esc_html_e( 'победители', 'kids-art-contest' ); ?></span></div><?php endif; ?>
				<div style="margin-top:auto"><a class="btn <?php echo in_array( $ph, array( 'submission', 'voting' ), true ) ? 'primary' : 'ghost'; ?> sm" href="<?php echo esc_url( get_permalink( $id ) ); ?>"><?php echo esc_html( in_array( $ph, array( 'submission', 'voting' ), true ) ? __( 'Участвовать', 'kids-art-contest' ) : ( $snap ? __( 'Смотреть итоги', 'kids-art-contest' ) : __( 'Подробнее', 'kids-art-contest' ) ) ); ?></a></div>
			</div>
		</div>
	<?php endforeach; ?>
	</div>
</div>
