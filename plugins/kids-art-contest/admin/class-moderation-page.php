<?php
defined( 'ABSPATH' ) || exit;

/**
 * Модерация работ (макет, экран 12): плитки, фильтры, массовые действия, горячие клавиши.
 */
class KAC_Moderation_Page {

	const PER_PAGE = 24;

	public static function render(): void {
		if ( ! current_user_can( 'kac_moderate_works' ) ) {
			wp_die( esc_html__( 'Нет доступа.', 'kids-art-contest' ) );
		}
		global $wpdb;

		$status  = isset( $_GET['status'] ) ? sanitize_key( $_GET['status'] ) : 'kac_pending'; // phpcs:ignore
		$all     = array( 'kac_pending', 'publish', 'kac_rejected', 'kac_disqualified' );
		$status  = ( 'all' === $status || in_array( $status, $all, true ) ) ? $status : 'kac_pending';
		$contest = isset( $_GET['contest'] ) ? absint( $_GET['contest'] ) : 0; // phpcs:ignore
		$group   = isset( $_GET['group'] ) ? sanitize_key( $_GET['group'] ) : ''; // phpcs:ignore
		$search  = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : ''; // phpcs:ignore
		$paged   = max( 1, isset( $_GET['paged'] ) ? absint( $_GET['paged'] ) : 1 ); // phpcs:ignore
		$see_pd  = current_user_can( 'kac_view_personal_data' );

		$args = array(
			'post_type'      => 'kac_work',
			'post_status'    => 'all' === $status ? $all : $status,
			'posts_per_page' => self::PER_PAGE,
			'paged'          => $paged,
			'orderby'        => 'ID',
			'order'          => 'kac_pending' === $status ? 'ASC' : 'DESC', // Очередь FIFO.
		);
		if ( $contest ) {
			$args['post_parent'] = $contest;
		}
		if ( '' !== $group ) {
			$args['meta_query'] = array( array( 'key' => '_kac_group', 'value' => $group ) ); // phpcs:ignore
		}
		if ( '' !== $search ) {
			$ids = array();
			if ( preg_match( '/^[№#]?\s*(\d+)$/u', $search, $m ) ) {
				$ids = array_map( 'intval', $wpdb->get_col( $wpdb->prepare( "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_kac_number' AND CAST(meta_value AS UNSIGNED) = %d", (int) $m[1] ) ) );
			}
			$like = '%' . $wpdb->esc_like( $search ) . '%';
			$ids  = array_merge(
				$ids,
				array_map( 'intval', $wpdb->get_col( $wpdb->prepare( "SELECT ID FROM {$wpdb->posts} WHERE post_type = 'kac_work' AND post_title LIKE %s", $like ) ) ),
				$see_pd ? array_map( 'intval', $wpdb->get_col( $wpdb->prepare( 'SELECT work_id FROM ' . KAC_Participants::table() . ' WHERE email LIKE %s OR child_last LIKE %s OR child_first LIKE %s OR parent_last LIKE %s', $like, $like, $like, $like ) ) ) : array()
			);
			$args['post__in'] = $ids ? array_unique( $ids ) : array( 0 );
		}
		$q = new WP_Query( $args );

		$where = $contest ? $wpdb->prepare( ' AND post_parent = %d', $contest ) : '';
		$rows  = $wpdb->get_results( "SELECT post_status, COUNT(*) c FROM {$wpdb->posts} WHERE post_type='kac_work'{$where} GROUP BY post_status", OBJECT_K ); // phpcs:ignore
		$cnt   = static fn( string $k ) => isset( $rows[ $k ] ) ? (int) $rows[ $k ]->c : 0;
		$total = array_sum( array_map( $cnt, $all ) );
		$tabs  = array(
			'kac_pending'      => array( __( 'На модерации', 'kids-art-contest' ), $cnt( 'kac_pending' ) ),
			'publish'          => array( __( 'Одобрены', 'kids-art-contest' ), $cnt( 'publish' ) ),
			'kac_rejected'     => array( __( 'Отклонены', 'kids-art-contest' ), $cnt( 'kac_rejected' ) ),
			'kac_disqualified' => array( __( 'Дисквалифицированы', 'kids-art-contest' ), $cnt( 'kac_disqualified' ) ),
			'all'              => array( __( 'Все', 'kids-art-contest' ), $total ),
		);
		$qs = static fn( array $a ) => esc_url( add_query_arg( array_filter( array_merge( array( 'page' => 'kac-moderation', 'contest' => $contest, 'group' => $group, 's' => $search ), $a ) ), admin_url( 'admin.php' ) ) );
		?>
		<div class="wrap kac-a kac-mod" data-status="<?php echo esc_attr( $status ); ?>">
			<h1 class="kh"><?php esc_html_e( 'Модерация работ', 'kids-art-contest' ); ?> <?php if ( $cnt( 'kac_pending' ) ) : ?><span class="pill orange" id="kac-pendcnt"><?php echo esc_html( sprintf( __( '%d ждут проверки', 'kids-art-contest' ), $cnt( 'kac_pending' ) ) ); ?></span><?php endif; ?></h1>
			<div class="sub3">
				<?php $i = 0; foreach ( $tabs as $k => $t ) : ?>
					<a href="<?php echo $qs( array( 'status' => $k ) ); // phpcs:ignore ?>" class="<?php echo $status === $k ? 'cur' : ''; ?>"><?php echo esc_html( $t[0] ); ?> (<?php echo (int) $t[1]; ?>)</a><?php echo ++$i < count( $tabs ) ? ' | ' : ''; ?>
				<?php endforeach; ?>
			</div>

			<form method="get" class="bulk" id="kac-filters">
				<input type="hidden" name="page" value="kac-moderation"><input type="hidden" name="status" value="<?php echo esc_attr( $status ); ?>">
				<?php echo str_replace( '<select', '<select class="wpi"', KAC_Admin_Menu::contest_select( $contest, 'contest', true ) ); // phpcs:ignore ?>
				<select class="wpi" name="group">
					<option value=""><?php esc_html_e( 'Все группы', 'kids-art-contest' ); ?></option>
					<?php
					$seen = array();
					foreach ( $contest ? array( $contest ) : get_posts( array( 'post_type' => 'kac_contest', 'numberposts' => -1, 'fields' => 'ids', 'post_status' => 'any' ) ) as $cid ) {
						foreach ( KAC_Contest::groups( (int) $cid ) as $g ) {
							if ( isset( $seen[ $g['key'] ] ) ) {
								continue;
							}
							$seen[ $g['key'] ] = 1;
							echo '<option value="' . esc_attr( $g['key'] ) . '"' . selected( $group, $g['key'], false ) . '>' . esc_html( $g['label'] ) . '</option>';
						}
					}
					?>
				</select>
				<input class="wpi w200" type="search" name="s" value="<?php echo esc_attr( $search ); ?>" placeholder="<?php echo esc_attr( $see_pd ? __( 'Поиск: ФИО, email, №', 'kids-art-contest' ) : __( 'Поиск: название, №', 'kids-art-contest' ) ); ?>">
				<span style="flex:1"></span>
				<label class="ck" style="margin:0"><input type="checkbox" id="kac-pickall"> <?php esc_html_e( 'Выбрать все', 'kids-art-contest' ); ?></label>
				<select class="wpi" id="kac-bulk-action"><option value=""><?php esc_html_e( 'Действия', 'kids-art-contest' ); ?></option><option value="approve"><?php esc_html_e( 'Одобрить', 'kids-art-contest' ); ?></option><option value="reject"><?php esc_html_e( 'Отклонить…', 'kids-art-contest' ); ?></option><option value="disqualify"><?php esc_html_e( 'Дисквалифицировать…', 'kids-art-contest' ); ?></option></select>
				<button type="button" class="wpbtn" id="kac-bulk-apply"><?php esc_html_e( 'Применить', 'kids-art-contest' ); ?></button>
			</form>

			<?php if ( ! $q->have_posts() ) : ?><p style="color:#646970;font-size:15px;padding:24px 0">🎉 <?php esc_html_e( 'Здесь пусто.', 'kids-art-contest' ); ?></p><?php endif; ?>

			<div class="mgrid" id="kac-mgrid">
				<?php foreach ( $q->posts as $w ) { self::tile( $w, $see_pd ); } ?>
			</div>

			<div style="margin-top:12px;color:#646970;font-size:12px"><?php esc_html_e( 'Горячие клавиши:', 'kids-art-contest' ); ?> <span class="kbd">A</span> <?php esc_html_e( 'одобрить', 'kids-art-contest' ); ?> · <span class="kbd">R</span> <?php esc_html_e( 'отклонить', 'kids-art-contest' ); ?> · <span class="kbd">Space</span> <?php esc_html_e( 'увеличить', 'kids-art-contest' ); ?> · <span class="kbd">←</span><span class="kbd">→</span> <?php esc_html_e( 'следующая', 'kids-art-contest' ); ?></div>
			<div class="tablenav"><div class="tablenav-pages"><?php echo wp_kses_post( (string) paginate_links( array( 'base' => add_query_arg( 'paged', '%#%' ), 'format' => '', 'current' => $paged, 'total' => max( 1, (int) $q->max_num_pages ), 'prev_text' => '‹', 'next_text' => '›' ) ) ); ?></div></div>

			<div class="modal-bg" id="kac-rejmodal" hidden><div class="modal">
				<h3 id="kac-rejtitle"><?php esc_html_e( 'Отклонить работу', 'kids-art-contest' ); ?></h3>
				<div style="font-size:13px;color:#646970;margin-bottom:8px"><?php esc_html_e( 'Причина будет отправлена законному представителю по email.', 'kids-art-contest' ); ?></div>
				<div id="kac-reasons"></div>
				<label class="r"><input type="radio" name="kac_rr" value="__other"> <?php esc_html_e( 'Другое:', 'kids-art-contest' ); ?></label>
				<textarea class="wpi w100" id="kac-reason" rows="2" style="padding:6px 8px;margin:4px 0 8px" placeholder="<?php esc_attr_e( 'Комментарий для родителя (необязательно)', 'kids-art-contest' ); ?>"></textarea>
				<label class="ck" id="kac-resubmit-wrap"><input type="checkbox" id="kac-allow-resubmit" checked> <?php esc_html_e( 'Разрешить отправить исправленную работу повторно', 'kids-art-contest' ); ?></label>
				<label class="ck"><input type="checkbox" id="kac-notify" checked> <?php esc_html_e( 'Уведомить по email', 'kids-art-contest' ); ?></label>
				<div style="display:flex;justify-content:flex-end;gap:8px;margin-top:12px"><button type="button" class="wpbtn" id="kac-rej-cancel"><?php esc_html_e( 'Отмена', 'kids-art-contest' ); ?></button><button type="button" class="wpbtn primary" id="kac-rej-ok" style="background:#d63638;border-color:#d63638"><?php esc_html_e( 'Отклонить', 'kids-art-contest' ); ?></button></div>
			</div></div>
			<div class="pv" id="kac-pv"><img alt=""></div>
		</div>
		<?php
	}

	private static function tile( WP_Post $w, bool $see_pd ): void {
		$thumb = get_the_post_thumbnail_url( $w->ID, 'kac_thumb' ) ?: get_the_post_thumbnail_url( $w->ID, 'medium' );
		$large = get_the_post_thumbnail_url( $w->ID, 'kac_large' ) ?: $thumb;
		$cid   = (int) $w->post_parent;
		$age   = (int) get_post_meta( $w->ID, '_kac_age', true );
		$gkey  = (string) get_post_meta( $w->ID, '_kac_group', true );
		$info  = (array) get_post_meta( $w->ID, '_kac_file_info', true );
		$p     = $see_pd ? KAC_Participants::get( $w->ID ) : null;
		$name  = $p && '' !== $p->child_last ? trim( "{$p->child_last} {$p->child_first} {$p->child_middle}" ) : (string) get_post_meta( $w->ID, '_kac_public_name', true );
		$hints = self::hints( $w, $cid, $age, $gkey, $p );
		$status = $w->post_status;
		?>
		<div class="mcard" data-id="<?php echo (int) $w->ID; ?>" data-title="<?php echo esc_attr( get_the_title( $w ) ); ?>" data-large="<?php echo esc_url( (string) $large ); ?>">
			<div class="im"><?php if ( $thumb ) : ?><img src="<?php echo esc_url( $thumb ); ?>" alt="" loading="lazy"><?php endif; ?><input type="checkbox" class="cb kac-pick">
				<div class="techi"><?php if ( ! empty( $info['w'] ) ) : ?><span><?php echo (int) $info['w']; ?>×<?php echo (int) $info['h']; ?></span><span><?php echo esc_html( size_format( (int) $info['bytes'], 1 ) ); ?></span><?php endif; ?></div></div>
			<div class="bd"><a class="tt" href="<?php echo esc_url( admin_url( 'admin.php?page=kac-work&id=' . $w->ID ) ); ?>"><?php echo esc_html( get_the_title( $w ) ); ?></a>
				<span class="mt"><?php echo esc_html( $name ); ?></span><br>
				<span class="mt"><?php echo esc_html( KAC_Mailer::age_text( $age ) . ' · ' . KAC_Contest::group_label( $cid, $gkey ) . ' · ' . sprintf( __( '%s назад', 'kids-art-contest' ), human_time_diff( strtotime( $w->post_date_gmt . ' UTC' ) ) ) ); ?></span>
				<?php foreach ( $hints as $h ) : ?><div class="warnl">⚠ <?php echo esc_html( $h ); ?></div><?php endforeach; ?>
				<?php if ( in_array( $status, array( 'kac_rejected', 'kac_disqualified' ), true ) ) : $r = (string) get_post_meta( $w->ID, '_kac_reject_reason', true ); if ( $r ) : ?><div class="warnl" style="background:#fbe0e0;color:#8a1f1f"><?php echo esc_html( $r ); ?></div><?php endif; endif; ?>
			</div>
			<div class="acts">
				<?php if ( 'publish' !== $status ) : ?><button type="button" class="wpbtn ok sm" data-act="approve">✓ <?php esc_html_e( 'Одобрить', 'kids-art-contest' ); ?></button><?php else : ?><button type="button" class="wpbtn sm" data-act="pending">↺ <?php esc_html_e( 'В очередь', 'kids-art-contest' ); ?></button><?php endif; ?>
				<?php if ( in_array( $status, array( 'kac_pending', 'publish' ), true ) ) : ?><button type="button" class="wpbtn danger sm" data-act="reject">✕ <?php esc_html_e( 'Отклонить', 'kids-art-contest' ); ?></button><?php else : ?><button type="button" class="wpbtn sm" data-act="pending">↺ <?php esc_html_e( 'В очередь', 'kids-art-contest' ); ?></button><?php endif; ?>
			</div>
		</div>
		<?php
	}

	/**
	 * Подсказки на плитке: дубликат, много работ с одного email, возраст на границе группы.
	 */
	private static function hints( WP_Post $w, int $cid, int $age, string $gkey, ?object $p ): array {
		global $wpdb;
		$out  = array();
		$hash = (string) get_post_meta( $w->ID, '_kac_dhash', true );
		$dup  = KAC_Image::find_duplicates( $cid, $hash, $w->ID );
		if ( $dup ) {
			$other = (string) get_post_meta( $dup[0], '_kac_dhash', true );
			$sim   = (int) round( 100 * ( 64 - KAC_Image::hamming( $hash, $other ) ) / 64 );
			$out[] = sprintf( 'Похожее изображение: №%03d (%d%%)', (int) get_post_meta( $dup[0], '_kac_number', true ), $sim );
		}
		if ( $p && '' !== $p->email ) {
			$n = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->prefix}kac_participants pt JOIN {$wpdb->posts} p ON p.ID = pt.work_id WHERE p.post_parent = %d AND pt.email = %s AND p.ID <> %d AND p.post_status <> 'trash'", $cid, $p->email, $w->ID ) );
			if ( $n >= 1 ) {
				$out[] = sprintf( '%d %s с одного email', $n + 1, 1 === $n ? 'работы' : 'работ(ы)' );
			}
		}
		$groups = KAC_Contest::groups( $cid );
		if ( count( $groups ) > 1 ) {
			foreach ( $groups as $g ) {
				if ( $g['key'] === $gkey && ( $age === (int) $g['from'] || $age === (int) $g['to'] ) ) {
					$out[] = sprintf( 'Возраст %d — граница группы', $age );
				}
			}
		}
		return $out;
	}
}
