<?php
defined( 'ABSPATH' ) || exit;

/**
 * Итоги конкурса (макет, экран 15).
 */
class KAC_Results_Page {

	public static function init(): void {
		add_action( 'admin_post_kac_results_action', array( __CLASS__, 'action' ) );
	}

	public static function action(): void {
		$cid = isset( $_POST['contest'] ) ? absint( $_POST['contest'] ) : 0; // phpcs:ignore
		check_admin_referer( 'kac_results_' . $cid );
		if ( ! current_user_can( 'kac_publish_results' ) || 'kac_contest' !== get_post_type( $cid ) ) {
			wp_die( esc_html__( 'Нет доступа.', 'kids-art-contest' ), '', array( 'response' => 403 ) );
		}
		$act = isset( $_POST['do'] ) ? sanitize_key( $_POST['do'] ) : ''; // phpcs:ignore
		$msg = $act;
		switch ( $act ) {
			case 'recalc':
				KAC_Results::snapshot( $cid );
				KAC_Log::add( 'contest', $cid, 'results_recalc' );
				break;
			case 'publish':
				KAC_Results::publish( $cid, true );
				break;
			case 'unpublish':
				delete_post_meta( $cid, '_kac_results_published' );
				KAC_Log::add( 'contest', $cid, 'results_unpublished' );
				break;
			case 'winners':
				KAC_Results::notify_winners( $cid );
				break;
			case 'up':
			case 'down':
				KAC_Results::move( $cid, sanitize_text_field( wp_unslash( $_POST['group'] ?? '' ) ), absint( $_POST['work'] ?? 0 ), 'up' === $act ? -1 : 1 ); // phpcs:ignore
				break;
		}
		wp_safe_redirect( add_query_arg( array( 'page' => 'kac-results', 'contest' => $cid, 'done' => $msg, 'tab' => sanitize_text_field( wp_unslash( $_POST['group'] ?? '' ) ) ), admin_url( 'admin.php' ) ) ); // phpcs:ignore
		exit;
	}

	public static function render(): void {
		if ( ! current_user_can( 'kac_publish_results' ) ) {
			wp_die( esc_html__( 'Нет доступа.', 'kids-art-contest' ) );
		}
		global $wpdb;
		$cid = isset( $_GET['contest'] ) ? absint( $_GET['contest'] ) : 0; // phpcs:ignore
		if ( ! $cid ) {
			$first = get_posts( array( 'post_type' => 'kac_contest', 'post_status' => 'any', 'numberposts' => 1, 'fields' => 'ids', 'orderby' => 'date', 'order' => 'DESC' ) );
			$cid   = $first ? (int) $first[0] : 0;
		}
		$done = isset( $_GET['done'] ) ? sanitize_key( $_GET['done'] ) : ''; // phpcs:ignore
		$tab  = isset( $_GET['tab'] ) ? sanitize_text_field( wp_unslash( $_GET['tab'] ) ) : ''; // phpcs:ignore
		?>
		<div class="wrap kac-a">
			<?php if ( ! $cid ) : ?><h1 class="kh"><?php esc_html_e( 'Итоги', 'kids-art-contest' ); ?></h1><p><?php esc_html_e( 'Нет конкурсов.', 'kids-art-contest' ); ?></p></div><?php return; endif; ?>
			<?php
			$snap      = KAC_Results::get_snapshot( $cid );
			$published = (int) get_post_meta( $cid, '_kac_results_published', true );
			$phase     = KAC_Phase::get( $cid );
			$d         = KAC_Phase::dates( $cid );
			$end       = max( $d['submit_end'], $d['vote_end'] );
			$pill      = $published ? '<span class="pill purple">' . esc_html__( 'Итоги опубликованы', 'kids-art-contest' ) . '</span>' : ( $snap ? '<span class="pill orange">' . esc_html__( 'Ждёт публикации', 'kids-art-contest' ) . '</span>' : '<span class="pill gray">' . esc_html( KAC_Admin_Menu::phase_label( $phase ) ) . '</span>' );
			?>
			<h1 class="kh"><?php echo esc_html( sprintf( __( 'Итоги: %s', 'kids-art-contest' ), get_the_title( $cid ) ) ); ?> <?php echo $pill; // phpcs:ignore WordPress.Security.EscapeOutput ?>
				<form method="get" style="display:inline"><input type="hidden" name="page" value="kac-results"><?php echo str_replace( '<select', '<select class="wpi" onchange="this.form.submit()"', KAC_Admin_Menu::contest_select( $cid, 'contest' ) ); // phpcs:ignore ?></form></h1>
			<?php if ( $done ) : ?><div class="knotice ok"><?php esc_html_e( 'Готово.', 'kids-art-contest' ); ?></div><?php endif; ?>

			<?php if ( $snap && ! $published && $end && $end <= time() ) : ?>
				<div class="knotice warn"><?php echo wp_kses_post( sprintf( __( 'Голосование завершилось <b>%s</b>. Результаты заморожены. Проверьте таблицу и нажмите «Опубликовать итоги».', 'kids-art-contest' ), esc_html( kac_date( $end ) ) ) ); ?></div>
			<?php endif; ?>
			<?php
			$groups = $snap ? array_filter( $snap['groups'], static fn( $g ) => ! empty( $g['items'] ) ) : array();
			foreach ( $groups as $gk => $g ) {
				$it = $g['items'];
				for ( $i = 0; $i < count( $it ) - 1; $i++ ) {
					if ( $it[ $i ]['votes'] === $it[ $i + 1 ]['votes'] && $it[ $i ]['votes'] > 0 && $it[ $i ]['place'] <= KAC_Contest::prize_places( $cid ) + 1 ) {
						$n1 = str_pad( (string) (int) get_post_meta( $it[ $i ]['work_id'], '_kac_number', true ), 3, '0', STR_PAD_LEFT );
						$n2 = str_pad( (string) (int) get_post_meta( $it[ $i + 1 ]['work_id'], '_kac_number', true ), 3, '0', STR_PAD_LEFT );
						echo '<div class="knotice err"><b>' . esc_html__( 'Равенство голосов', 'kids-art-contest' ) . '</b> ' . esc_html( sprintf( 'в группе %1$s: работы №%2$s и №%3$s набрали по %4$d. Выше — №%2$s по выбранному правилу; порядок можно изменить стрелками ↑↓ в таблице.', $g['label'], $n1, $n2, $it[ $i ]['votes'] ) ) . '</div>';
					}
				}
			}
			?>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:flex;gap:8px;margin-bottom:14px;flex-wrap:wrap">
				<input type="hidden" name="action" value="kac_results_action"><input type="hidden" name="contest" value="<?php echo (int) $cid; ?>"><?php wp_nonce_field( 'kac_results_' . $cid ); ?>
				<?php if ( $snap && ! $published ) : ?><button class="wpbtn primary" name="do" value="publish" data-confirm="<?php esc_attr_e( 'Опубликовать итоги? Призёрам уйдут письма.', 'kids-art-contest' ); ?>">📣 <?php esc_html_e( 'Опубликовать итоги', 'kids-art-contest' ); ?></button><?php elseif ( $published ) : ?><button class="wpbtn" name="do" value="unpublish">↩ <?php esc_html_e( 'Снять с публикации', 'kids-art-contest' ); ?></button><?php endif; ?>
				<button class="wpbtn" name="do" value="recalc" data-confirm="<?php esc_attr_e( 'Пересчитать итоги? Ручная правка порядка будет сброшена.', 'kids-art-contest' ); ?>">↻ <?php esc_html_e( 'Пересчитать', 'kids-art-contest' ); ?></button>
				<?php if ( current_user_can( 'kac_export' ) ) : ?><a class="wpbtn" href="<?php echo esc_url( KAC_Admin_Menu::export_url( $cid, 'xlsx' ) ); ?>">⬇ XLSX</a><a class="wpbtn" href="<?php echo esc_url( KAC_Admin_Menu::export_url( $cid, 'csv' ) ); ?>">⬇ CSV</a><?php endif; ?>
				<?php if ( $snap ) : ?><button class="wpbtn" name="do" value="winners" data-confirm="<?php esc_attr_e( 'Отправить письма призёрам?', 'kids-art-contest' ); ?>">✉ <?php esc_html_e( 'Письма призёрам', 'kids-art-contest' ); ?></button><?php endif; ?>
			</form>

			<?php if ( ! $snap ) : ?>
				<p style="color:#646970"><?php esc_html_e( 'Снимок итогов ещё не сделан. Нажмите «Пересчитать» — или он создастся автоматически в момент окончания голосования.', 'kids-art-contest' ); ?></p>
			<?php else :
				$first = array_key_first( $groups );
				$tab   = isset( $groups[ $tab ] ) ? $tab : $first;
				$see_pd = current_user_can( 'kac_view_personal_data' );
				?>
				<div class="wptabs" id="kac-restabs"><?php foreach ( $groups as $gk => $g ) : ?><a class="<?php echo (string) $gk === (string) $tab ? 'on' : ''; ?>" data-g="<?php echo esc_attr( (string) $gk ); ?>"><?php echo esc_html( $g['label'] ); ?></a><?php endforeach; ?></div>
				<?php foreach ( $groups as $gk => $g ) : ?>
				<div class="stab <?php echo (string) $gk === (string) $tab ? 'on' : ''; ?>" data-g="<?php echo esc_attr( (string) $gk ); ?>" <?php echo (string) $gk === (string) $tab ? '' : 'hidden'; ?>>
					<table class="wpt"><thead><tr><th><?php esc_html_e( 'Место', 'kids-art-contest' ); ?></th><th></th><th><?php esc_html_e( 'Работа', 'kids-art-contest' ); ?></th><th><?php echo esc_html( $see_pd ? __( 'Участник (полное ФИО)', 'kids-art-contest' ) : __( 'Участник', 'kids-art-contest' ) ); ?></th><?php if ( $see_pd ) : ?><th><?php esc_html_e( 'Представитель / телефон', 'kids-art-contest' ); ?></th><?php endif; ?><th><?php esc_html_e( 'Голоса', 'kids-art-contest' ); ?></th><th><?php esc_html_e( 'Аннулир.', 'kids-art-contest' ); ?></th><th></th></tr></thead><tbody>
					<?php foreach ( $g['items'] as $i => $it ) :
						$wid  = (int) $it['work_id'];
						$p    = $see_pd ? KAC_Participants::get( $wid ) : null;
						$ann  = (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM ' . KAC_Voting::table() . " WHERE work_id = %d AND status = 'annulled'", $wid ) );
						$u    = get_the_post_thumbnail_url( $wid, 'thumbnail' );
						$medal = array( 1 => '🥇 1', 2 => '🥈 2', 3 => '🥉 3' )[ $it['place'] ] ?? (string) $it['place'];
						?>
						<tr>
							<td><b><?php echo esc_html( $medal ); ?></b></td>
							<td><?php if ( $u ) : ?><img class="thumb" src="<?php echo esc_url( $u ); ?>" alt=""><?php endif; ?></td>
							<td><a class="ttl" style="font-size:13px" href="<?php echo esc_url( admin_url( 'admin.php?page=kac-work&id=' . $wid ) ); ?>">№<?php echo esc_html( str_pad( (string) (int) get_post_meta( $wid, '_kac_number', true ), 3, '0', STR_PAD_LEFT ) . ' ' . get_the_title( $wid ) ); ?></a></td>
							<td><?php echo $p && $p->child_last ? esc_html( trim( "{$p->child_last} {$p->child_first} {$p->child_middle}" ) . ', ' . (int) $p->child_age ) : esc_html( (string) get_post_meta( $wid, '_kac_public_name', true ) ); ?></td>
							<?php if ( $see_pd ) : ?><td><?php echo $p && $p->parent_last ? esc_html( trim( $p->parent_last . ' ' . mb_substr( $p->parent_first, 0, 1 ) . '.' ) . ' · ' . KAC_Work_Page::fmt_phone( $p->phone ) ) : '—'; ?></td><?php endif; ?>
							<td><b><?php echo (int) $it['votes']; ?></b></td>
							<td><?php echo $ann ? '<span class="pill red">' . (int) $ann . '</span>' : '—'; ?></td>
							<td>
								<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline-flex;gap:3px">
									<input type="hidden" name="action" value="kac_results_action"><input type="hidden" name="contest" value="<?php echo (int) $cid; ?>"><input type="hidden" name="group" value="<?php echo esc_attr( (string) $gk ); ?>"><input type="hidden" name="work" value="<?php echo (int) $wid; ?>"><?php wp_nonce_field( 'kac_results_' . $cid ); ?>
									<button class="wpbtn sm" name="do" value="up" <?php disabled( 0 === $i ); ?>>↑</button><button class="wpbtn sm" name="do" value="down" <?php disabled( $i === count( $g['items'] ) - 1 ); ?>>↓</button>
								</form>
							</td>
						</tr>
					<?php endforeach; ?>
					</tbody></table>
				</div>
				<?php endforeach; ?>
			<?php endif; ?>
		</div>
		<?php
	}
}
