<?php
defined( 'ABSPATH' ) || exit;

/**
 * «Все работы» и карточка работы (макет, экран 13).
 */
class KAC_Work_Page {

	/* ---------- Список ---------- */

	public static function render_list(): void {
		if ( ! current_user_can( 'kac_edit_works' ) ) {
			wp_die( esc_html__( 'Нет доступа.', 'kids-art-contest' ) );
		}
		$contest = isset( $_GET['contest'] ) ? absint( $_GET['contest'] ) : 0; // phpcs:ignore
		$status  = isset( $_GET['status'] ) ? sanitize_key( $_GET['status'] ) : ''; // phpcs:ignore
		$search  = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : ''; // phpcs:ignore
		$paged   = max( 1, isset( $_GET['paged'] ) ? absint( $_GET['paged'] ) : 1 ); // phpcs:ignore
		$all     = array( 'publish', 'kac_pending', 'kac_rejected', 'kac_disqualified' );

		$args = array(
			'post_type'      => 'kac_work',
			'post_status'    => in_array( $status, $all, true ) ? $status : $all,
			'posts_per_page' => 30,
			'paged'          => $paged,
			'orderby'        => 'ID',
			'order'          => 'DESC',
		);
		if ( $contest ) {
			$args['post_parent'] = $contest;
		}
		if ( '' !== $search ) {
			if ( preg_match( '/^[№#]?\s*(\d+)$/u', $search, $m ) ) {
				$args['meta_query'] = array( array( 'key' => '_kac_number', 'value' => (int) $m[1], 'type' => 'NUMERIC' ) ); // phpcs:ignore
			} else {
				$args['s'] = $search;
			}
		}
		$q = new WP_Query( $args );
		$pill = array( 'publish' => 'green', 'kac_pending' => 'orange', 'kac_rejected' => 'red', 'kac_disqualified' => 'red' );
		?>
		<div class="wrap kac-a">
			<h1 class="kh"><?php esc_html_e( 'Все работы', 'kids-art-contest' ); ?></h1>
			<form method="get" class="bulk">
				<input type="hidden" name="page" value="kac-works">
				<?php echo str_replace( '<select', '<select class="wpi"', KAC_Admin_Menu::contest_select( $contest, 'contest', true ) ); // phpcs:ignore ?>
				<select class="wpi" name="status"><option value=""><?php esc_html_e( 'Все статусы', 'kids-art-contest' ); ?></option>
					<?php foreach ( $all as $s ) : ?><option value="<?php echo esc_attr( $s ); ?>" <?php selected( $status, $s ); ?>><?php echo esc_html( KAC_Admin_Menu::status_label( $s ) ); ?></option><?php endforeach; ?>
				</select>
				<input class="wpi w200" type="search" name="s" value="<?php echo esc_attr( $search ); ?>" placeholder="<?php esc_attr_e( 'Название или №', 'kids-art-contest' ); ?>">
				<button class="wpbtn"><?php esc_html_e( 'Фильтр', 'kids-art-contest' ); ?></button>
			</form>
			<table class="wpt">
				<thead><tr><th>№</th><th></th><th><?php esc_html_e( 'Название', 'kids-art-contest' ); ?></th><th><?php esc_html_e( 'Конкурс', 'kids-art-contest' ); ?></th><th><?php esc_html_e( 'Группа', 'kids-art-contest' ); ?></th><th><?php esc_html_e( 'Статус', 'kids-art-contest' ); ?></th><th><?php esc_html_e( 'Голоса', 'kids-art-contest' ); ?></th><th><?php esc_html_e( 'Дата', 'kids-art-contest' ); ?></th></tr></thead>
				<tbody>
				<?php foreach ( $q->posts as $w ) : $cid = (int) $w->post_parent; $u = get_the_post_thumbnail_url( $w->ID, 'thumbnail' ); ?>
					<tr>
						<td><?php echo esc_html( str_pad( (string) (int) get_post_meta( $w->ID, '_kac_number', true ), 3, '0', STR_PAD_LEFT ) ); ?></td>
						<td><?php if ( $u ) : ?><img class="thumb" src="<?php echo esc_url( $u ); ?>" alt=""><?php endif; ?></td>
						<td><a class="ttl" href="<?php echo esc_url( admin_url( 'admin.php?page=kac-work&id=' . $w->ID ) ); ?>"><?php echo esc_html( get_the_title( $w ) ); ?></a><div class="ra"><?php echo esc_html( (string) get_post_meta( $w->ID, '_kac_public_name', true ) ); ?></div></td>
						<td><?php echo esc_html( get_the_title( $cid ) ); ?></td>
						<td><?php echo esc_html( KAC_Contest::group_label( $cid, (string) get_post_meta( $w->ID, '_kac_group', true ) ) ); ?></td>
						<td><span class="pill <?php echo esc_attr( $pill[ $w->post_status ] ?? 'gray' ); ?>"><?php echo esc_html( KAC_Admin_Menu::status_label( $w->post_status ) ); ?></span></td>
						<td><?php echo (int) get_post_meta( $w->ID, '_kac_votes', true ); ?></td>
						<td><?php echo esc_html( get_date_from_gmt( $w->post_date_gmt, 'd.m.Y H:i' ) ); ?></td>
					</tr>
				<?php endforeach; ?>
				<?php if ( ! $q->have_posts() ) : ?><tr><td colspan="8"><?php esc_html_e( 'Работ нет.', 'kids-art-contest' ); ?></td></tr><?php endif; ?>
				</tbody>
			</table>
			<div class="tablenav"><div class="tablenav-pages"><?php echo wp_kses_post( (string) paginate_links( array( 'base' => add_query_arg( 'paged', '%#%' ), 'format' => '', 'current' => $paged, 'total' => max( 1, (int) $q->max_num_pages ) ) ) ); ?></div></div>
		</div>
		<?php
	}

	/* ---------- Карточка ---------- */

	public static function render_card(): void {
		global $wpdb;
		$id = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0; // phpcs:ignore
		$w  = get_post( $id );
		if ( ! $w || 'kac_work' !== $w->post_type || ! current_user_can( 'kac_edit_works' ) ) {
			wp_die( esc_html__( 'Работа не найдена.', 'kids-art-contest' ) );
		}
		$cid    = (int) $w->post_parent;
		$num    = str_pad( (string) (int) get_post_meta( $id, '_kac_number', true ), 3, '0', STR_PAD_LEFT );
		$img    = get_the_post_thumbnail_url( $id, 'kac_large' ) ?: get_the_post_thumbnail_url( $id, 'full' );
		$see_pd = current_user_can( 'kac_view_personal_data' );
		$p      = $see_pd ? KAC_Participants::get( $id, true ) : null;
		$status = $w->post_status;
		$gkey   = (string) get_post_meta( $id, '_kac_group', true );
		$age    = (int) get_post_meta( $id, '_kac_age', true );
		$orig   = (string) get_post_meta( $id, '_kac_original_path', true );
		$info   = (array) get_post_meta( $id, '_kac_file_info', true );
		$t      = KAC_Voting::table();
		$valid  = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $t WHERE work_id = %d AND status <> 'annulled'", $id ) );
		$annul  = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $t WHERE work_id = %d AND status = 'annulled'", $id ) );
		$rank   = 1 + (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->posts} p JOIN {$wpdb->postmeta} g ON g.post_id = p.ID AND g.meta_key = '_kac_group' AND g.meta_value = %s JOIN {$wpdb->postmeta} v ON v.post_id = p.ID AND v.meta_key = '_kac_votes' WHERE p.post_parent = %d AND p.post_status = 'publish' AND CAST(v.meta_value AS UNSIGNED) > %d", $gkey, $cid, $valid ) );
		$days   = $wpdb->get_results( $wpdb->prepare( "SELECT DATE(created_at) d, COUNT(*) c FROM $t WHERE work_id = %d AND status <> 'annulled' GROUP BY d ORDER BY d ASC LIMIT 30", $id ) );
		$dmax   = 1;
		foreach ( $days as $r ) {
			$dmax = max( $dmax, (int) $r->c );
		}
		$dups  = KAC_Image::find_duplicates( $cid, (string) get_post_meta( $id, '_kac_dhash', true ), $id );
		$pill  = array( 'publish' => 'green', 'kac_pending' => 'orange', 'kac_rejected' => 'red', 'kac_disqualified' => 'red' );
		?>
		<div class="wrap kac-a kac-card-page" data-id="<?php echo (int) $id; ?>">
			<div style="font-size:12.5px;color:#646970"><a style="color:#2271b1" href="<?php echo esc_url( admin_url( 'admin.php?page=kac-moderation' ) ); ?>">← <?php esc_html_e( 'Модерация', 'kids-art-contest' ); ?></a> / <?php echo esc_html( get_the_title( $cid ) ); ?></div>
			<h1 class="kh"><?php echo esc_html( sprintf( __( 'Работа №%1$s «%2$s»', 'kids-art-contest' ), $num, get_the_title( $w ) ) ); ?> <span class="pill <?php echo esc_attr( $pill[ $status ] ?? 'gray' ); ?>"><?php echo esc_html( KAC_Admin_Menu::status_label( $status ) ); ?></span></h1>
			<div class="kcols" style="grid-template-columns:minmax(0,1fr) 340px">
				<div>
					<div class="kpb"><div class="in" style="padding:0;background:#1d2327;text-align:center;position:relative"><?php if ( $img ) : ?><img id="kac-work-img" src="<?php echo esc_url( $img ); ?>" alt="" style="max-width:100%;max-height:460px;display:block;margin:0 auto;cursor:zoom-in"><?php endif; ?>
						<div style="position:absolute;left:10px;bottom:10px;display:flex;gap:6px">
							<?php if ( $orig ) : ?><a class="wpbtn sm" target="_blank" href="<?php echo esc_url( KAC_Image::original_url( $id ) ); ?>">⤢ <?php esc_html_e( 'Открыть оригинал', 'kids-art-contest' ); ?></a><?php endif; ?>
							<button type="button" class="wpbtn sm" data-rotate="right">↻ <?php esc_html_e( 'Повернуть', 'kids-art-contest' ); ?></button>
							<?php if ( $orig ) : ?><a class="wpbtn sm" href="<?php echo esc_url( KAC_Image::original_url( $id, true ) ); ?>">⬇ <?php esc_html_e( 'Скачать', 'kids-art-contest' ); ?></a><?php endif; ?>
						</div></div></div>

					<div class="kpb"><h2><?php esc_html_e( 'Описание работы', 'kids-art-contest' ); ?> <button type="button" class="wpbtn sm" id="kac-edit-toggle"><?php esc_html_e( 'Редактировать', 'kids-art-contest' ); ?></button></h2><div class="in">
						<dl class="dl" id="kac-desc-view">
							<dt><?php esc_html_e( 'Название', 'kids-art-contest' ); ?></dt><dd><?php echo esc_html( $w->post_title ); ?></dd>
							<dt><?php esc_html_e( 'Описание', 'kids-art-contest' ); ?></dt><dd><?php echo $w->post_content ? esc_html( $w->post_content ) : '—'; ?></dd>
							<dt><?php esc_html_e( 'Техника', 'kids-art-contest' ); ?></dt><dd><?php echo esc_html( (string) get_post_meta( $id, '_kac_technique', true ) ?: '—' ); ?></dd>
							<dt><?php esc_html_e( 'Группа', 'kids-art-contest' ); ?></dt><dd><?php echo esc_html( KAC_Contest::group_label( $cid, $gkey ) ); ?></dd>
						</dl>
						<div id="kac-desc-edit" hidden>
							<div class="wpf"><label><?php esc_html_e( 'Название', 'kids-art-contest' ); ?></label><input class="wpi w100" id="kac-f-title" value="<?php echo esc_attr( $w->post_title ); ?>"></div>
							<div class="wpf wpf-top"><label><?php esc_html_e( 'Описание', 'kids-art-contest' ); ?></label><textarea class="wpi w100" rows="3" id="kac-f-desc" style="padding:6px"><?php echo esc_textarea( $w->post_content ); ?></textarea></div>
							<div class="wpf"><label><?php esc_html_e( 'Техника', 'kids-art-contest' ); ?></label><input class="wpi w100" id="kac-f-tech" value="<?php echo esc_attr( (string) get_post_meta( $id, '_kac_technique', true ) ); ?>"></div>
							<button type="button" class="wpbtn primary" id="kac-save-work"><?php esc_html_e( 'Сохранить', 'kids-art-contest' ); ?></button>
						</div>
					</div></div>

					<?php if ( current_user_can( 'kac_view_votes' ) ) : ?>
					<div class="kpb"><h2><?php esc_html_e( 'Голоса', 'kids-art-contest' ); ?> <span style="font-weight:400;font-size:12.5px"><a style="color:#2271b1" href="<?php echo esc_url( admin_url( 'admin.php?page=kac-votes&contest=' . $cid ) ); ?>"><?php esc_html_e( 'Журнал голосов →', 'kids-art-contest' ); ?></a></span></h2><div class="in">
						<div style="display:flex;gap:24px;margin-bottom:6px"><div><b style="font-size:22px"><?php echo (int) $valid; ?></b><br><span style="color:#646970"><?php esc_html_e( 'учтено', 'kids-art-contest' ); ?></span></div><div><b style="font-size:22px;color:#d63638"><?php echo (int) $annul; ?></b><br><span style="color:#646970"><?php esc_html_e( 'аннулировано', 'kids-art-contest' ); ?></span></div><div><b style="font-size:22px"><?php echo 'publish' === $status ? (int) $rank : '—'; ?></b><br><span style="color:#646970"><?php esc_html_e( 'место в группе', 'kids-art-contest' ); ?></span></div></div>
						<div class="chart"><?php foreach ( $days as $r ) : ?><i title="<?php echo esc_attr( $r->d . ': ' . $r->c ); ?>" style="height:<?php echo (int) max( 4, round( 100 * $r->c / $dmax ) ); ?>%"></i><?php endforeach; ?></div>
						<div style="font-size:11.5px;color:#646970;display:flex;justify-content:space-between"><span><?php echo $days ? esc_html( wp_date( 'd.m', strtotime( $days[0]->d ) ) ) : ''; ?></span><span><?php esc_html_e( 'голоса по дням', 'kids-art-contest' ); ?></span><span><?php echo $days ? esc_html( wp_date( 'd.m', strtotime( end( $days )->d ) ) ) : ''; ?></span></div>
					</div></div>
					<?php endif; ?>

					<div class="kpb"><h2><?php esc_html_e( 'История', 'kids-art-contest' ); ?></h2><div class="in log">
						<?php foreach ( KAC_Log::for_object( 'work', $id, 60 ) as $l ) : $u = $l->user_id ? get_userdata( (int) $l->user_id ) : null; ?>
							<div><span><?php echo esc_html( get_date_from_gmt( $l->created_at, 'd.m H:i' ) ); ?></span><div style="display:block;border:0;padding:0"><?php echo $u ? '<b>' . esc_html( $u->display_name ) . '</b> ' : ''; ?><?php echo esc_html( self::action_label( $l ) ); ?></div></div>
						<?php endforeach; ?>
					</div></div>
				</div>

				<div>
					<?php if ( current_user_can( 'kac_moderate_works' ) ) : ?>
					<div class="kpb"><h2><?php esc_html_e( 'Решение', 'kids-art-contest' ); ?></h2><div class="in kac-decision" style="display:grid;gap:6px">
						<?php if ( 'publish' === $status ) : ?><span class="wpbtn ok" style="justify-content:center">✓ <?php esc_html_e( 'Одобрена', 'kids-art-contest' ); ?></span><?php else : ?><button type="button" class="wpbtn ok" style="justify-content:center" data-act="approve">✓ <?php esc_html_e( 'Одобрить', 'kids-art-contest' ); ?></button><?php endif; ?>
						<?php if ( 'kac_rejected' !== $status ) : ?><button type="button" class="wpbtn danger" style="justify-content:center" data-act="reject"><?php esc_html_e( 'Отклонить…', 'kids-art-contest' ); ?></button><?php endif; ?>
						<?php if ( 'kac_disqualified' !== $status ) : ?><button type="button" class="wpbtn danger" style="justify-content:center" data-act="disqualify"><?php echo 'publish' === $status ? esc_html__( 'Дисквалифицировать (после одобрения)', 'kids-art-contest' ) : esc_html__( 'Дисквалифицировать', 'kids-art-contest' ); ?></button><?php endif; ?>
						<?php if ( 'kac_pending' !== $status ) : ?><button type="button" class="wpbtn" style="justify-content:center" data-act="pending"><?php esc_html_e( 'Вернуть в очередь', 'kids-art-contest' ); ?></button><?php endif; ?>
						<button type="button" class="wpbtn danger" style="justify-content:center;margin-top:8px" data-act="delete">🗑 <?php esc_html_e( 'Удалить работу', 'kids-art-contest' ); ?></button>
						<span style="font-size:12px;color:#646970"><?php esc_html_e( 'Дисквалификация убирает работу из галереи и рейтинга, голоса сохраняются в журнале. Удаление безвозвратно стирает работу, файлы, данные участника и голоса.', 'kids-art-contest' ); ?></span>
						<?php $reason = (string) get_post_meta( $id, '_kac_reject_reason', true ); if ( $reason ) : ?><span style="font-size:12px;color:#b32d2e"><?php echo esc_html( 'Причина: ' . $reason ); ?></span><?php endif; ?>
					</div></div>
					<?php endif; ?>

					<div class="kpb"><h2><?php esc_html_e( 'Участник', 'kids-art-contest' ); ?></h2><div class="in"><?php if ( ! $see_pd ) : ?><p><?php esc_html_e( 'Нет права просмотра персональных данных.', 'kids-art-contest' ); ?></p><?php else : ?>
						<dl class="dl" style="grid-template-columns:110px 1fr">
							<dt><?php esc_html_e( 'ФИО', 'kids-art-contest' ); ?></dt><dd><b><?php echo $p ? esc_html( trim( "{$p->child_last} {$p->child_first} {$p->child_middle}" ) ?: '—' ) : '—'; ?></b></dd>
							<dt><?php esc_html_e( 'На сайте', 'kids-art-contest' ); ?></dt><dd><?php echo esc_html( (string) get_post_meta( $id, '_kac_public_name', true ) ); ?></dd>
							<dt><?php esc_html_e( 'Возраст', 'kids-art-contest' ); ?></dt><dd><?php echo esc_html( KAC_Mailer::age_text( $age ) ); ?></dd>
							<dt><?php esc_html_e( 'Город', 'kids-art-contest' ); ?></dt><dd><?php echo esc_html( $p ? ( $p->city ?: '—' ) : '—' ); ?></dd>
							<dt><?php esc_html_e( 'Школа', 'kids-art-contest' ); ?></dt><dd><?php echo esc_html( $p ? ( $p->school ?: '—' ) : '—' ); ?></dd>
							<dt><?php esc_html_e( 'Педагог', 'kids-art-contest' ); ?></dt><dd><?php echo esc_html( $p ? ( $p->teacher ?: '—' ) : '—' ); ?></dd></dl>
					<?php endif; ?></div></div>

					<?php if ( $see_pd ) : ?>
					<div class="kpb"><h2><?php esc_html_e( 'Законный представитель', 'kids-art-contest' ); ?></h2><div class="in"><?php if ( $p ) : ?><dl class="dl" style="grid-template-columns:110px 1fr">
						<dt><?php esc_html_e( 'ФИО', 'kids-art-contest' ); ?></dt><dd><?php echo esc_html( trim( "{$p->parent_last} {$p->parent_first} {$p->parent_middle}" ) ?: '—' ); ?></dd>
						<dt><?php esc_html_e( 'Кем приходится', 'kids-art-contest' ); ?></dt><dd><?php echo esc_html( $p->parent_relation ?: '—' ); ?></dd>
						<dt>Email</dt><dd><?php echo $p->email ? '<a style="color:#2271b1" href="mailto:' . esc_attr( $p->email ) . '">' . esc_html( $p->email ) . '</a>' : '—'; ?></dd>
						<dt><?php esc_html_e( 'Телефон', 'kids-art-contest' ); ?></dt><dd><?php echo $p->phone ? '<a style="color:#2271b1" href="tel:+' . esc_attr( $p->phone ) . '">' . esc_html( self::fmt_phone( $p->phone ) ) . '</a>' : '—'; ?></dd></dl>
					<?php else : ?><?php esc_html_e( 'Данные удалены или отсутствуют.', 'kids-art-contest' ); ?><?php endif; ?></div></div>
					<?php endif; ?>

					<div class="kpb"><h2><?php esc_html_e( 'Файл', 'kids-art-contest' ); ?></h2><div class="in"><dl class="dl" style="grid-template-columns:110px 1fr">
						<dt><?php esc_html_e( 'Исходник', 'kids-art-contest' ); ?></dt><dd><?php echo esc_html( ! empty( $info['name'] ) ? $info['name'] : '—' ); ?></dd>
						<dt><?php esc_html_e( 'Размер', 'kids-art-contest' ); ?></dt><dd><?php echo ! empty( $info['w'] ) ? esc_html( size_format( (int) $info['bytes'], 2 ) . ' · ' . $info['w'] . '×' . $info['h'] ) : '—'; ?></dd>
						<dt>EXIF</dt><dd><?php echo kac_setting( 'strip_exif' ) ? esc_html__( 'удалён', 'kids-art-contest' ) . ' ✓' : esc_html__( 'сохранён', 'kids-art-contest' ); ?></dd>
						<dt><?php esc_html_e( 'Дубликаты', 'kids-art-contest' ); ?></dt><dd><?php if ( $dups ) : ?><span style="color:#d63638;font-weight:700"><?php echo esc_html( 'найдено: №' . implode( ', №', array_map( static fn( $d ) => str_pad( (string) (int) get_post_meta( $d, '_kac_number', true ), 3, '0', STR_PAD_LEFT ), $dups ) ) ); ?></span><?php else : ?><span class="yes"><?php esc_html_e( 'не найдено', 'kids-art-contest' ); ?></span><?php endif; ?></dd></dl></div></div>

					<?php if ( $p ) : $c = json_decode( (string) $p->consents, true ) ?: array(); $at = ! empty( $c['at'] ) ? wp_date( 'd.m H:i', strtotime( $c['at'] ) ) : ''; ?>
					<div class="kpb"><h2><?php esc_html_e( 'Согласия', 'kids-art-contest' ); ?></h2><div class="in" style="font-size:12.5px;line-height:1.8">
						<?php echo ! empty( $c['personal'] ) ? '✓' : '✕'; ?> <?php esc_html_e( 'Обработка ПД', 'kids-art-contest' ); ?> — <?php echo esc_html( $at . ' (' . sprintf( __( 'версия %d', 'kids-art-contest' ), (int) $p->consent_version ) . ')' ); ?><br>
						<?php echo ! empty( $c['publish'] ) ? '✓' : '✕'; ?> <?php esc_html_e( 'Публикация работы', 'kids-art-contest' ); ?> — <?php echo esc_html( $at ); ?><br>
						<?php echo ! empty( $c['rules'] ) ? '✓' : '✕'; ?> <?php esc_html_e( 'Правила конкурса', 'kids-art-contest' ); ?> — <?php echo esc_html( $at ); ?><br>
						<?php if ( $p->newsletter ) : ?>✓ <?php esc_html_e( 'Рассылка новостей', 'kids-art-contest' ); else : ?><span class="no">✕ <?php esc_html_e( 'Рассылка новостей', 'kids-art-contest' ); ?></span><?php endif; ?>
					</div></div>
					<?php endif; ?>

					<div class="kpb"><h2><?php esc_html_e( 'Заметка модераторов', 'kids-art-contest' ); ?></h2><div class="in"><textarea class="wpi w100" rows="2" id="kac-f-note" style="padding:6px" placeholder="<?php esc_attr_e( 'Видна только в админке', 'kids-art-contest' ); ?>"><?php echo esc_textarea( (string) get_post_meta( $id, '_kac_note', true ) ); ?></textarea><p><button type="button" class="wpbtn sm" id="kac-save-note"><?php esc_html_e( 'Сохранить заметку', 'kids-art-contest' ); ?></button></p></div></div>
				</div>
			</div>

			<div class="modal-bg" id="kac-rejmodal" hidden><div class="modal">
				<h3 id="kac-rejtitle"></h3>
				<div id="kac-reasons"></div>
				<label class="r"><input type="radio" name="kac_rr" value="__other"> <?php esc_html_e( 'Другое:', 'kids-art-contest' ); ?></label>
				<textarea class="wpi w100" id="kac-reason" rows="2" style="padding:6px 8px;margin:4px 0 8px" placeholder="<?php esc_attr_e( 'Комментарий для родителя (необязательно)', 'kids-art-contest' ); ?>"></textarea>
				<label class="ck" id="kac-resubmit-wrap"><input type="checkbox" id="kac-allow-resubmit" checked> <?php esc_html_e( 'Разрешить отправить исправленную работу повторно', 'kids-art-contest' ); ?></label>
				<label class="ck"><input type="checkbox" id="kac-notify" checked> <?php esc_html_e( 'Уведомить по email', 'kids-art-contest' ); ?></label>
				<div style="display:flex;justify-content:flex-end;gap:8px;margin-top:12px"><button type="button" class="wpbtn" id="kac-rej-cancel"><?php esc_html_e( 'Отмена', 'kids-art-contest' ); ?></button><button type="button" class="wpbtn primary" id="kac-rej-ok" style="background:#d63638;border-color:#d63638"><?php esc_html_e( 'Подтвердить', 'kids-art-contest' ); ?></button></div>
			</div></div>
			<div class="pv" id="kac-pv"><img alt=""></div>
		</div>
		<?php
	}

	public static function fmt_phone( string $d ): string {
		return 11 === strlen( $d ) ? sprintf( '+%s (%s) %s-%s-%s', $d[0], substr( $d, 1, 3 ), substr( $d, 4, 3 ), substr( $d, 7, 2 ), substr( $d, 9, 2 ) ) : '+' . $d;
	}

	private static function action_label( object $l ): string {
		$data = json_decode( (string) $l->data, true ) ?: array();
		$map  = array(
			'submitted'            => 'Работа отправлена',
			'approve'              => 'одобрил(а) работу',
			'reject'               => 'отклонил(а) работу' . ( ! empty( $data['reason'] ) ? ': ' . $data['reason'] : '' ),
			'disqualify'           => 'дисквалифицировал(а) работу' . ( ! empty( $data['reason'] ) ? ': ' . $data['reason'] : '' ),
			'pending'              => 'вернул(а) работу в очередь',
			'edited'               => 'исправил(а) описание',
			'rotated'              => 'повернул(а) изображение',
			'view_personal_data'   => 'просмотрел(а) персональные данные',
			'erased_personal_data' => 'Персональные данные удалены',
			'mail_received'        => 'Письмо «Заявка получена» отправлено',
			'mail_approved'        => 'Письмо «Работа опубликована» отправлено',
			'mail_rejected'        => 'Письмо «Работа отклонена» отправлено',
			'mail_winner'          => 'Письмо «Поздравление призёра» отправлено',
			'mail_results'         => 'Письмо «Итоги» отправлено',
		);
		return $map[ $l->action ] ?? $l->action;
	}
}
