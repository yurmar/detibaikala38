<?php
defined( 'ABSPATH' ) || exit;

/**
 * Голоса и антинакрутка (макет, экран 14).
 */
class KAC_Votes_Page {

	public static function init(): void {
		add_action( 'admin_post_kac_votes_csv', array( __CLASS__, 'csv' ) );
	}

	public static function csv(): void {
		$cid = isset( $_GET['contest'] ) ? absint( $_GET['contest'] ) : 0;
		check_admin_referer( 'kac_votes_csv_' . $cid );
		if ( ! current_user_can( 'kac_view_votes' ) || ! current_user_can( 'kac_export' ) ) {
			wp_die( esc_html__( 'Нет доступа.', 'kids-art-contest' ), '', array( 'response' => 403 ) );
		}
		global $wpdb;
		$rows = $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . KAC_Voting::table() . ' WHERE contest_id = %d ORDER BY id ASC', $cid ) );
		$out  = array( array( 'ID', 'Время (UTC)', 'Работа', 'Название', 'IP (маска)', 'Браузер', 'Риск', 'Статус' ) );
		foreach ( $rows as $r ) {
			$out[] = array( $r->id, $r->created_at, (int) get_post_meta( $r->work_id, '_kac_number', true ), get_the_title( $r->work_id ), $r->ip_masked, $r->ua_label, $r->risk, $r->status );
		}
		KAC_Log::add( 'contest', $cid, 'votes_export' );
		KAC_Results::send_csv( $out, 'kac-votes-' . $cid . '.csv' );
	}

	private static function risk_pill( int $risk ): string {
		if ( $risk >= 60 ) {
			return '<span class="pill red">' . esc_html( sprintf( 'высокий %d', $risk ) ) . '</span>';
		}
		return $risk >= 30 ? '<span class="pill orange">' . esc_html( sprintf( 'средний %d', $risk ) ) . '</span>' : '<span class="pill green">' . esc_html( sprintf( 'низкий %d', $risk ) ) . '</span>';
	}

	public static function render(): void {
		if ( ! current_user_can( 'kac_view_votes' ) ) {
			wp_die( esc_html__( 'Нет доступа.', 'kids-art-contest' ) );
		}
		global $wpdb;
		$t   = KAC_Voting::table();
		$cid = isset( $_GET['contest'] ) ? absint( $_GET['contest'] ) : 0; // phpcs:ignore
		if ( ! $cid ) {
			$first = get_posts( array( 'post_type' => 'kac_contest', 'post_status' => 'any', 'numberposts' => 1, 'fields' => 'ids', 'orderby' => 'date', 'order' => 'DESC' ) );
			$cid   = $first ? (int) $first[0] : 0;
		}
		$vstatus = isset( $_GET['vstatus'] ) ? sanitize_key( $_GET['vstatus'] ) : ''; // phpcs:ignore
		$vrisk   = isset( $_GET['vrisk'] ) ? sanitize_key( $_GET['vrisk'] ) : ''; // phpcs:ignore
		$vq      = isset( $_GET['vq'] ) ? sanitize_text_field( wp_unslash( $_GET['vq'] ) ) : ''; // phpcs:ignore
		$vwork   = isset( $_GET['work'] ) ? absint( $_GET['work'] ) : 0; // phpcs:ignore
		$paged   = max( 1, isset( $_GET['paged'] ) ? absint( $_GET['paged'] ) : 1 ); // phpcs:ignore
		$per     = 50;

		$sum = $wpdb->get_row( $wpdb->prepare( "SELECT COUNT(*) total, COUNT(DISTINCT voter_hash) voters, SUM(status='suspicious') susp, SUM(status='annulled') ann FROM $t WHERE contest_id = %d", $cid ) );
		$where = $wpdb->prepare( 'contest_id = %d', $cid );
		if ( 'valid' === $vstatus ) {
			$where .= " AND status = 'valid'";
		} elseif ( 'suspicious' === $vstatus ) {
			$where .= " AND status = 'suspicious'";
		} elseif ( 'annulled' === $vstatus ) {
			$where .= " AND status = 'annulled'";
		}
		if ( 'high' === $vrisk ) {
			$where .= ' AND risk >= 60';
		} elseif ( 'mid' === $vrisk ) {
			$where .= ' AND risk >= 30 AND risk < 60';
		} elseif ( 'low' === $vrisk ) {
			$where .= ' AND risk < 30';
		}
		if ( $vwork ) {
			$where .= $wpdb->prepare( ' AND work_id = %d', $vwork );
		}
		if ( '' !== $vq ) {
			if ( preg_match( '/^[№#]?\s*(\d+)$/u', $vq, $m ) ) {
				$ids    = array_map( 'intval', $wpdb->get_col( $wpdb->prepare( "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_kac_number' AND CAST(meta_value AS UNSIGNED) = %d", (int) $m[1] ) ) );
				$where .= $ids ? ' AND work_id IN (' . implode( ',', $ids ) . ')' : ' AND 1=0';
			} else {
				$where .= $wpdb->prepare( ' AND ip_masked LIKE %s', '%' . $wpdb->esc_like( $vq ) . '%' );
			}
		}
		$total_rows = (int) $wpdb->get_var( "SELECT COUNT(*) FROM $t WHERE $where" ); // phpcs:ignore
		$rows       = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $t WHERE $where ORDER BY id DESC LIMIT %d OFFSET %d", $per, ( $paged - 1 ) * $per ) ); // phpcs:ignore

		// 72 часа.
		$hours = array();
		for ( $i = 71; $i >= 0; $i-- ) {
			$hours[ gmdate( 'Y-m-d H:00', time() - $i * HOUR_IN_SECONDS ) ] = 0;
		}
		foreach ( $wpdb->get_results( $wpdb->prepare( "SELECT DATE_FORMAT(created_at, '%%Y-%%m-%%d %%H:00') h, COUNT(*) c FROM $t WHERE contest_id = %d AND created_at > %s GROUP BY h", $cid, gmdate( 'Y-m-d H:i:s', time() - 72 * HOUR_IN_SECONDS ) ) ) as $h ) {
			if ( isset( $hours[ $h->h ] ) ) {
				$hours[ $h->h ] = (int) $h->c;
			}
		}
		$max    = max( 1, max( $hours ) );
		$sorted = array_values( $hours );
		sort( $sorted );
		$median = $sorted[ (int) ( count( $sorted ) / 2 ) ];
		$alerts = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}kac_alerts WHERE contest_id = %d AND status = 'new' ORDER BY id DESC LIMIT 5", $cid ) );
		?>
		<div class="wrap kac-a kac-votes-page">
			<h1 class="kh"><?php esc_html_e( 'Голоса', 'kids-art-contest' ); ?>
				<form method="get" style="display:inline"><input type="hidden" name="page" value="kac-votes"><?php echo str_replace( '<select', '<select class="wpi" onchange="this.form.submit()"', KAC_Admin_Menu::contest_select( $cid, 'contest' ) ); // phpcs:ignore ?></form>
				<?php if ( current_user_can( 'kac_export' ) && $cid ) : ?><a class="wpbtn" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=kac_votes_csv&contest=' . $cid ), 'kac_votes_csv_' . $cid ) ); ?>">⬇ <?php esc_html_e( 'Экспорт CSV', 'kids-art-contest' ); ?></a><?php endif; ?></h1>

			<div class="kpis">
				<div class="kpi"><b><?php echo esc_html( number_format_i18n( (int) $sum->total ) ); ?></b><span><?php esc_html_e( 'всего голосов', 'kids-art-contest' ); ?></span></div>
				<div class="kpi ok"><b><?php echo esc_html( number_format_i18n( (int) $sum->voters ) ); ?></b><span><?php esc_html_e( 'уникальных посетителей', 'kids-art-contest' ); ?></span></div>
				<div class="kpi warn"><b><?php echo (int) $sum->susp; ?></b><span><?php esc_html_e( 'подозрительных (на проверке)', 'kids-art-contest' ); ?></span></div>
				<div class="kpi err"><b><?php echo (int) $sum->ann; ?></b><span><?php esc_html_e( 'аннулировано', 'kids-art-contest' ); ?></span></div>
			</div>

			<?php foreach ( $alerts as $a ) : $d = json_decode( (string) $a->data, true ) ?: array(); $wnum = str_pad( (string) (int) get_post_meta( (int) $a->work_id, '_kac_number', true ), 3, '0', STR_PAD_LEFT ); ?>
				<div class="knotice err" data-alert="<?php echo (int) $a->id; ?>">
					<b>⚠ <?php echo 'spike' === $a->type ? esc_html__( 'Всплеск:', 'kids-art-contest' ) : esc_html__( 'Одна подсеть:', 'kids-art-contest' ); ?></b>
					<?php echo esc_html( sprintf( 'работа №%1$s «%2$s» получила ', $wnum, get_the_title( (int) $a->work_id ) ) ); ?><b><?php echo esc_html( sprintf( '%1$d голосов за %2$d минут', $d['votes'] ?? 0, $d['minutes'] ?? 0 ) ); ?></b>.
					<a class="wpbtn sm" href="<?php echo esc_url( admin_url( 'admin.php?page=kac-votes&contest=' . $cid . '&work=' . (int) $a->work_id ) ); ?>"><?php esc_html_e( 'Показать голоса', 'kids-art-contest' ); ?></a>
					<?php if ( current_user_can( 'kac_annul_votes' ) ) : ?><button type="button" class="wpbtn sm danger" data-alert-annul="<?php echo (int) $a->id; ?>"><?php echo esc_html( sprintf( 'Аннулировать %d', $d['votes'] ?? 0 ) ); ?></button><?php endif; ?>
					<button type="button" class="wpbtn sm" data-alert-dismiss="<?php echo (int) $a->id; ?>"><?php esc_html_e( 'Это нормально', 'kids-art-contest' ); ?></button>
				</div>
			<?php endforeach; ?>

			<div class="kpb"><h2><?php esc_html_e( 'Голоса по часам, последние 3 суток', 'kids-art-contest' ); ?></h2><div class="in"><div class="chart"><?php foreach ( $hours as $h => $c ) : ?><i class="<?php echo $c > max( 20, $median * 4 ) ? 'hot' : ''; ?>" title="<?php echo esc_attr( $h . ' UTC: ' . $c ); ?>" style="height:<?php echo (int) max( 2, round( 100 * $c / $max ) ); ?>%"></i><?php endforeach; ?></div></div></div>

			<form method="get" class="bulk" id="kac-vfilters">
				<input type="hidden" name="page" value="kac-votes"><input type="hidden" name="contest" value="<?php echo (int) $cid; ?>"><?php if ( $vwork ) : ?><input type="hidden" name="work" value="<?php echo (int) $vwork; ?>"><?php endif; ?>
				<select class="wpi" name="vrisk"><option value=""><?php esc_html_e( 'Риск: все', 'kids-art-contest' ); ?></option><option value="high" <?php selected( $vrisk, 'high' ); ?>><?php esc_html_e( 'Высокий', 'kids-art-contest' ); ?></option><option value="mid" <?php selected( $vrisk, 'mid' ); ?>><?php esc_html_e( 'Средний', 'kids-art-contest' ); ?></option><option value="low" <?php selected( $vrisk, 'low' ); ?>><?php esc_html_e( 'Низкий', 'kids-art-contest' ); ?></option></select>
				<select class="wpi" name="vstatus"><option value=""><?php esc_html_e( 'Статус: все', 'kids-art-contest' ); ?></option><option value="valid" <?php selected( $vstatus, 'valid' ); ?>><?php esc_html_e( 'Учтён', 'kids-art-contest' ); ?></option><option value="suspicious" <?php selected( $vstatus, 'suspicious' ); ?>><?php esc_html_e( 'На проверке', 'kids-art-contest' ); ?></option><option value="annulled" <?php selected( $vstatus, 'annulled' ); ?>><?php esc_html_e( 'Аннулирован', 'kids-art-contest' ); ?></option></select>
				<input class="wpi w200" name="vq" value="<?php echo esc_attr( $vq ); ?>" placeholder="<?php esc_attr_e( 'Работа №, IP', 'kids-art-contest' ); ?>">
				<span style="flex:1"></span>
				<?php if ( current_user_can( 'kac_annul_votes' ) ) : ?>
				<select class="wpi" id="kac-vote-action"><option value=""><?php esc_html_e( 'Действия', 'kids-art-contest' ); ?></option><option value="annul"><?php esc_html_e( 'Аннулировать', 'kids-art-contest' ); ?></option><option value="restore"><?php esc_html_e( 'Восстановить', 'kids-art-contest' ); ?></option></select>
				<button type="button" class="wpbtn" id="kac-vote-apply"><?php esc_html_e( 'Применить', 'kids-art-contest' ); ?></button>
				<?php endif; ?>
			</form>
			<table class="wpt">
				<thead><tr><th style="width:24px"><input type="checkbox" id="kac-vote-all"></th><th><?php esc_html_e( 'Время', 'kids-art-contest' ); ?></th><th><?php esc_html_e( 'Работа', 'kids-art-contest' ); ?></th><th>IP</th><th><?php esc_html_e( 'Посетитель', 'kids-art-contest' ); ?></th><th><?php esc_html_e( 'Браузер', 'kids-art-contest' ); ?></th><th><?php esc_html_e( 'Риск', 'kids-art-contest' ); ?></th><th><?php esc_html_e( 'Статус', 'kids-art-contest' ); ?></th></tr></thead>
				<tbody>
				<?php foreach ( $rows as $r ) :
					$stat = array( 'valid' => '<span class="pill green">учтён</span>', 'suspicious' => '<span class="pill orange">на проверке</span>', 'annulled' => '<span class="pill red">аннулирован</span>' )[ $r->status ] ?? esc_html( $r->status );
					?>
					<tr>
						<td><input type="checkbox" class="kac-vote-pick" value="<?php echo (int) $r->id; ?>"></td>
						<td><?php echo esc_html( get_date_from_gmt( $r->created_at, 'd.m H:i:s' ) ); ?></td>
						<td><a href="<?php echo esc_url( admin_url( 'admin.php?page=kac-work&id=' . (int) $r->work_id ) ); ?>">№<?php echo esc_html( str_pad( (string) (int) get_post_meta( (int) $r->work_id, '_kac_number', true ), 3, '0', STR_PAD_LEFT ) . ' ' . get_the_title( (int) $r->work_id ) ); ?></a></td>
						<td><?php echo esc_html( $r->ip_masked ?: '—' ); ?></td>
						<td><code style="font-size:11.5px"><?php echo esc_html( substr( $r->voter_hash, 0, 8 ) ); ?></code></td>
						<td><?php echo esc_html( $r->ua_label ?: '—' ); ?></td>
						<td><?php echo self::risk_pill( (int) $r->risk ); // phpcs:ignore WordPress.Security.EscapeOutput ?></td>
						<td><?php echo $stat; // phpcs:ignore WordPress.Security.EscapeOutput ?></td>
					</tr>
				<?php endforeach; ?>
				<?php if ( ! $rows ) : ?><tr><td colspan="8"><?php esc_html_e( 'Голосов нет.', 'kids-art-contest' ); ?></td></tr><?php endif; ?>
				</tbody>
			</table>
			<div class="tablenav"><div class="tablenav-pages"><?php echo wp_kses_post( (string) paginate_links( array( 'base' => add_query_arg( 'paged', '%#%' ), 'format' => '', 'current' => $paged, 'total' => max( 1, (int) ceil( $total_rows / $per ) ) ) ) ); ?></div></div>
		</div>
		<?php
	}
}
