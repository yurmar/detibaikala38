<?php
defined( 'ABSPATH' ) || exit;

/**
 * Редактор конкурса (макет, экран 11), список конкурсов (экран 10), дублирование.
 */
class KAC_Contest_Metaboxes {

	public static function init(): void {
		add_action( 'add_meta_boxes_kac_contest', array( __CLASS__, 'add' ) );
		add_action( 'save_post_kac_contest', array( __CLASS__, 'save' ), 10, 2 );
		add_action( 'admin_notices', array( __CLASS__, 'notices' ) );
		add_filter( 'manage_kac_contest_posts_columns', array( __CLASS__, 'columns' ) );
		add_action( 'manage_kac_contest_posts_custom_column', array( __CLASS__, 'column' ), 10, 2 );
		add_filter( 'post_row_actions', array( __CLASS__, 'row_actions' ), 10, 2 );
		add_action( 'admin_post_kac_duplicate', array( __CLASS__, 'duplicate' ) );
		add_action( 'all_admin_notices', array( __CLASS__, 'kpis' ) );
		add_filter( 'views_edit-kac_contest', array( __CLASS__, 'views' ) );
		add_action( 'pre_get_posts', array( __CLASS__, 'filter_phase' ) );
		add_action( 'before_delete_post', array( __CLASS__, 'on_delete' ) );
	}

	/* ---------- Метабоксы ---------- */

	public static function add(): void {
		add_meta_box( 'kac_dates', '<span>📅 ' . esc_html__( 'Даты конкурса', 'kids-art-contest' ) . '</span>', array( __CLASS__, 'box_dates' ), 'kac_contest', 'normal', 'high' );
		add_meta_box( 'kac_people', '<span>🧒 ' . esc_html__( 'Участники и возраст', 'kids-art-contest' ) . '</span>', array( __CLASS__, 'box_people' ), 'kac_contest', 'normal', 'high' );
		add_meta_box( 'kac_files', '<span>🖼 ' . esc_html__( 'Требования к файлу', 'kids-art-contest' ) . '</span> <span style="font-weight:400;color:#646970;font-size:12px">' . esc_html__( 'по умолчанию из настроек', 'kids-art-contest' ) . '</span>', array( __CLASS__, 'box_files' ), 'kac_contest', 'normal', 'default' );
		add_meta_box( 'kac_voting', '<span>🗳 ' . esc_html__( 'Голосование', 'kids-art-contest' ) . '</span>', array( __CLASS__, 'box_voting' ), 'kac_contest', 'normal', 'default' );
		add_meta_box( 'kac_shortcodes', __( 'Шорткоды', 'kids-art-contest' ), array( __CLASS__, 'box_shortcodes' ), 'kac_contest', 'side', 'default' );
		add_meta_box( 'kac_rules', __( 'Правила (PDF)', 'kids-art-contest' ), array( __CLASS__, 'box_rules' ), 'kac_contest', 'side', 'default' );
		add_meta_box( 'kac_moderators', __( 'Модераторы', 'kids-art-contest' ), array( __CLASS__, 'box_moderators' ), 'kac_contest', 'side', 'default' );
	}

	private static function dt( int $ts ): string {
		return $ts ? wp_date( 'd.m.Y H:i', $ts ) : '';
	}

	public static function box_dates( WP_Post $post ): void {
		wp_nonce_field( 'kac_contest_save', 'kac_contest_nonce' );
		$d      = KAC_Phase::dates( $post->ID );
		$raw_vs = (int) get_post_meta( $post->ID, '_kac_vote_start', true );
		$mode   = (string) ( get_post_meta( $post->ID, '_kac_results_mode', true ) ?: 'auto' );
		$total  = max( 1, max( $d['vote_end'], $d['submit_end'] ) - ( $d['submit_start'] ?: time() ) );
		$seg    = static fn( int $a, int $b ) => $a && $b && $b > $a ? max( 8, (int) round( 100 * ( $b - $a ) / $total ) ) : 0;
		$f_sub  = $seg( $d['submit_start'], $d['submit_end'] );
		$f_vot  = $seg( $d['vote_start'], $d['vote_end'] );
		?>
		<div class="kac-a">
			<div class="wpf"><label><?php esc_html_e( 'Приём работ', 'kids-art-contest' ); ?></label><div><input class="wpi kac-dt" type="text" maxlength="16" placeholder="ДД.ММ.ГГГГ ЧЧ:ММ" autocomplete="off" name="kac_submit_start" value="<?php echo esc_attr( self::dt( $d['submit_start'] ) ); ?>"> — <input class="wpi kac-dt" type="text" maxlength="16" placeholder="ДД.ММ.ГГГГ ЧЧ:ММ" autocomplete="off" name="kac_submit_end" value="<?php echo esc_attr( self::dt( $d['submit_end'] ) ); ?>"></div></div>
			<div class="wpf"><label><?php esc_html_e( 'Голосование', 'kids-art-contest' ); ?></label><div><input class="wpi kac-dt" type="text" maxlength="16" placeholder="ДД.ММ.ГГГГ ЧЧ:ММ" autocomplete="off" name="kac_vote_start" value="<?php echo esc_attr( self::dt( $raw_vs ) ); ?>"> — <input class="wpi kac-dt" type="text" maxlength="16" placeholder="ДД.ММ.ГГГГ ЧЧ:ММ" autocomplete="off" name="kac_vote_end" value="<?php echo esc_attr( self::dt( $d['vote_end'] ) ); ?>"></div>
				<div class="d"><label class="ck"><input type="checkbox" name="kac_vote_with_submit" value="1" <?php checked( $d['vote_with_submit'] ); ?>> <?php esc_html_e( 'Голосование начинается вместе с приёмом работ (за работу можно голосовать сразу после одобрения)', 'kids-art-contest' ); ?></label></div></div>
			<div class="wpf"><label><?php esc_html_e( 'Публикация итогов', 'kids-art-contest' ); ?></label><div><select class="wpi" name="kac_results_mode"><option value="auto" <?php selected( $mode, 'auto' ); ?>><?php esc_html_e( 'Автоматически после окончания голосования', 'kids-art-contest' ); ?></option><option value="manual" <?php selected( $mode, 'manual' ); ?>><?php esc_html_e( 'Вручную модератором', 'kids-art-contest' ); ?></option></select></div></div>
			<?php if ( $f_sub || $f_vot ) : ?>
			<div class="tl">
				<?php if ( $f_sub ) : ?><div style="background:#2271b1;flex:<?php echo (int) $f_sub; ?>"><?php echo esc_html( 'Приём ' . kac_date( $d['submit_start'], 'd.m' ) . '–' . kac_date( $d['submit_end'], 'd.m' ) ); ?></div><?php endif; ?>
				<?php if ( $f_vot ) : ?><div style="background:#00a32a;flex:<?php echo (int) $f_vot; ?>"><?php echo esc_html( 'Голосование ' . kac_date( $d['vote_start'], 'd.m' ) . '–' . kac_date( $d['vote_end'], 'd.m' ) ); ?></div><?php endif; ?>
				<div style="background:#8c5bd6;flex:12"><?php esc_html_e( 'Итоги', 'kids-art-contest' ); ?></div>
			</div>
			<?php endif; ?>
			<div style="font-size:12px;color:#646970;margin-top:6px"><?php printf( esc_html__( 'Часовой пояс сайта: %s. Изменить в «Настройки → Общие».', 'kids-art-contest' ), esc_html( wp_timezone_string() ) ); ?> · <strong><?php esc_html_e( 'Текущая фаза:', 'kids-art-contest' ); ?></strong> <?php echo esc_html( KAC_Admin_Menu::phase_label( KAC_Phase::get( $post->ID ) ) ); ?></div>
		</div>
		<?php
	}

	public static function box_people( WP_Post $post ): void {
		$min    = KAC_Contest::min_age( $post->ID );
		$max    = KAC_Contest::max_age( $post->ID );
		$raw    = get_post_meta( $post->ID, '_kac_age_groups', true );
		$groups = is_string( $raw ) && '' !== $raw ? (array) json_decode( $raw, true ) : array();
		$prefix = (string) ( get_post_meta( $post->ID, '_kac_ticket_prefix', true ) ?: 'ЗС' );
		$has_works = array_sum( KAC_Contest::work_counts( $post->ID ) ) > 0;
		?>
		<div class="kac-a" data-has-works="<?php echo $has_works ? '1' : '0'; ?>">
			<div class="wpf"><label><?php esc_html_e( 'Возраст участников', 'kids-art-contest' ); ?></label><div><?php esc_html_e( 'от', 'kids-art-contest' ); ?> <input class="wpi w80" type="number" min="0" max="99" name="kac_min_age" value="<?php echo (int) $min; ?>"> <?php esc_html_e( 'до', 'kids-art-contest' ); ?> <input class="wpi w80" type="number" min="0" max="99" name="kac_max_age" value="<?php echo (int) $max; ?>"> <?php esc_html_e( 'лет', 'kids-art-contest' ); ?></div></div>
			<div class="wpf wpf-top"><label><?php esc_html_e( 'Возрастные группы', 'kids-art-contest' ); ?></label><div>
				<div class="rep h"><span><?php esc_html_e( 'Название', 'kids-art-contest' ); ?></span><span><?php esc_html_e( 'От', 'kids-art-contest' ); ?></span><span><?php esc_html_e( 'До', 'kids-art-contest' ); ?></span><span></span></div>
				<div id="kac-groups">
				<?php foreach ( $groups as $i => $g ) : ?>
					<div class="rep"><input class="wpi w100" type="text" name="kac_groups[<?php echo (int) $i; ?>][label]" value="<?php echo esc_attr( $g['label'] ); ?>"><input class="wpi" type="number" min="0" name="kac_groups[<?php echo (int) $i; ?>][from]" value="<?php echo (int) $g['from']; ?>"><input class="wpi" type="number" min="0" name="kac_groups[<?php echo (int) $i; ?>][to]" value="<?php echo (int) $g['to']; ?>"><span class="kac-group-del" style="color:#b32d2e;cursor:pointer">✕</span></div>
				<?php endforeach; ?>
				</div>
				<button type="button" class="wpbtn sm kac-group-add">+ <?php esc_html_e( 'Добавить группу', 'kids-art-contest' ); ?></button>
				<div class="d" style="margin-top:6px"><?php esc_html_e( 'Оставьте одну группу, если зачёт общий.', 'kids-art-contest' ); ?></div></div></div>
			<div class="wpf"><label><?php esc_html_e( 'Призовых мест в группе', 'kids-art-contest' ); ?></label><div><input class="wpi w80" type="number" min="1" max="10" name="kac_prize_places" value="<?php echo (int) KAC_Contest::prize_places( $post->ID ); ?>"></div></div>
			<div class="wpf"><label><?php esc_html_e( 'Работ от одного участника', 'kids-art-contest' ); ?></label><div><input class="wpi w80" type="number" min="1" max="20" name="kac_max_works" value="<?php echo (int) KAC_Contest::max_works( $post->ID ); ?>"> <span style="color:#646970"><?php esc_html_e( 'считается по паре «email + ФИО ребёнка»', 'kids-art-contest' ); ?></span></div></div>
			<div class="wpf"><label><?php esc_html_e( 'Префикс номера заявки', 'kids-art-contest' ); ?></label><div><input class="wpi w80" type="text" maxlength="8" name="kac_ticket_prefix" value="<?php echo esc_attr( $prefix ); ?>"> <span style="color:#646970">→ <?php echo esc_html( $prefix ); ?>‑0458</span></div></div>
			<script type="text/template" id="kac-group-tpl"><div class="rep"><input class="wpi w100" type="text" name="kac_groups[__i__][label]"><input class="wpi" type="number" min="0" name="kac_groups[__i__][from]"><input class="wpi" type="number" min="0" name="kac_groups[__i__][to]"><span class="kac-group-del" style="color:#b32d2e;cursor:pointer">✕</span></div></script>
		</div>
		<?php
	}

	public static function box_files( WP_Post $post ): void {
		$raw = get_post_meta( $post->ID, '_kac_upload_rules', true );
		$own = is_string( $raw ) && '' !== $raw ? (array) json_decode( $raw, true ) : array();
		$g   = static fn( string $k ) => ! empty( $own[ $k ] ) ? (int) $own[ $k ] : '';
		?>
		<div class="kac-a">
			<div class="wpf"><label><?php esc_html_e( 'Максимальный размер', 'kids-art-contest' ); ?></label><div><input class="wpi w80" type="number" step="0.5" min="0.1" name="kac_up_max_mb" value="<?php echo ! empty( $own['max_bytes'] ) ? esc_attr( round( $own['max_bytes'] / 1048576, 2 ) ) : ''; ?>" placeholder="<?php echo esc_attr( round( (int) kac_setting( 'upload_max_bytes' ) / 1048576, 2 ) ); ?>"> <?php esc_html_e( 'МБ', 'kids-art-contest' ); ?></div></div>
			<div class="wpf"><label><?php esc_html_e( 'Размер изображения', 'kids-art-contest' ); ?></label><div><?php esc_html_e( 'от', 'kids-art-contest' ); ?> <input class="wpi w80" type="number" min="100" name="kac_up_min_w" value="<?php echo esc_attr( (string) $g( 'min_w' ) ); ?>" placeholder="<?php echo (int) kac_setting( 'upload_min_w' ); ?>">×<input class="wpi w80" type="number" min="100" name="kac_up_min_h" value="<?php echo esc_attr( (string) $g( 'min_h' ) ); ?>" placeholder="<?php echo (int) kac_setting( 'upload_min_h' ); ?>"> <?php esc_html_e( 'до', 'kids-art-contest' ); ?> <input class="wpi w80" type="number" min="100" name="kac_up_max_w" value="<?php echo esc_attr( (string) $g( 'max_w' ) ); ?>" placeholder="<?php echo (int) kac_setting( 'upload_max_w' ); ?>">×<input class="wpi w80" type="number" min="100" name="kac_up_max_h" value="<?php echo esc_attr( (string) $g( 'max_h' ) ); ?>" placeholder="<?php echo (int) kac_setting( 'upload_max_h' ); ?>"> px</div></div>
			<div class="wpf"><label><?php esc_html_e( 'Форматы', 'kids-art-contest' ); ?></label><div>
				<?php foreach ( array( 'jpg' => 'JPG', 'png' => 'PNG', 'webp' => 'WEBP' ) as $k => $label ) : ?>
					<label class="ck" style="display:inline-flex;margin-right:12px"><input type="checkbox" name="kac_up_formats[]" value="<?php echo esc_attr( $k ); ?>" <?php checked( ! empty( $own['formats'] ) ? in_array( $k, $own['formats'], true ) : in_array( $k, (array) kac_setting( 'upload_formats' ), true ) ); ?>> <?php echo esc_html( $label ); ?></label>
				<?php endforeach; ?>
			</div></div>
		</div>
		<?php
	}

	public static function box_voting( WP_Post $post ): void {
		$mode   = (string) ( get_post_meta( $post->ID, '_kac_vote_mode', true ) ?: 'per_work' );
		$limit  = (int) ( get_post_meta( $post->ID, '_kac_vote_limit', true ) ?: 5 );
		$show   = (string) ( get_post_meta( $post->ID, '_kac_show_votes', true ) ?: 'always' );
		$lead   = get_post_meta( $post->ID, '_kac_show_leaders', true );
		?>
		<div class="kac-a" id="kac-voting-box" data-mode="<?php echo esc_attr( $mode ); ?>">
			<div class="wpf wpf-top"><label><?php esc_html_e( 'Сколько можно голосовать', 'kids-art-contest' ); ?></label><div>
				<label class="ck"><input type="radio" name="kac_vote_mode" value="per_work" <?php checked( $mode, 'per_work' ); ?>> <?php esc_html_e( '1 голос за каждую работу', 'kids-art-contest' ); ?></label>
				<label class="ck"><input type="radio" name="kac_vote_mode" value="per_contest" <?php checked( $mode, 'per_contest' ); ?>> <?php esc_html_e( '1 голос на весь конкурс', 'kids-art-contest' ); ?></label>
				<label class="ck"><input type="radio" name="kac_vote_mode" value="per_group" <?php checked( $mode, 'per_group' ); ?>> <?php esc_html_e( '1 голос в каждой возрастной группе', 'kids-art-contest' ); ?></label>
				<label class="ck"><input type="radio" name="kac_vote_mode" value="n_per_contest" <?php checked( $mode, 'n_per_contest' ); ?>> <input class="wpi w80" type="number" min="1" name="kac_vote_limit" value="<?php echo (int) $limit; ?>" style="min-height:24px"> <?php esc_html_e( 'голосов на весь конкурс', 'kids-art-contest' ); ?></label></div></div>
			<div class="wpf"><label><?php esc_html_e( 'Показывать число голосов', 'kids-art-contest' ); ?></label><div><select class="wpi" name="kac_show_votes"><option value="always" <?php selected( $show, 'always' ); ?>><?php esc_html_e( 'Всегда', 'kids-art-contest' ); ?></option><option value="after_end" <?php selected( $show, 'after_end' ); ?>><?php esc_html_e( 'Только после окончания', 'kids-art-contest' ); ?></option><option value="never" <?php selected( $show, 'never' ); ?>><?php esc_html_e( 'Не показывать', 'kids-art-contest' ); ?></option></select></div></div>
			<div class="wpf"><label><?php esc_html_e( 'Блок «Лидеры»', 'kids-art-contest' ); ?></label><div><label class="ck"><input type="checkbox" name="kac_show_leaders" value="1" <?php checked( '' === $lead ? true : (bool) $lead ); ?>> <?php esc_html_e( 'Показывать топ‑5 во время голосования', 'kids-art-contest' ); ?></label></div></div>
		</div>
		<?php
	}

	public static function box_shortcodes( WP_Post $post ): void {
		if ( 'auto-draft' === $post->post_status ) {
			echo '<p>' . esc_html__( 'Сохраните конкурс, чтобы получить шорткоды.', 'kids-art-contest' ) . '</p>';
			return;
		}
		$rows = array(
			'kac_contest'     => __( 'Конкурс целиком:', 'kids-art-contest' ),
			'kac_submit_form' => __( 'Только форма:', 'kids-art-contest' ),
			'kac_results'     => __( 'Только итоги:', 'kids-art-contest' ),
		);
		echo '<div class="kac-a" style="font-size:12.5px;line-height:1.9">';
		foreach ( $rows as $sc => $label ) {
			$code = '[' . $sc . ' id="' . $post->ID . '"]';
			echo esc_html( $label ) . '<br><code class="shortcode">' . esc_html( $code ) . '</code> <a href="#" class="kac-copy" data-copy="' . esc_attr( $code ) . '" title="' . esc_attr__( 'Копировать', 'kids-art-contest' ) . '">📋</a><br>';
		}
		echo '</div>';
	}

	public static function box_rules( WP_Post $post ): void {
		$id  = (int) get_post_meta( $post->ID, '_kac_rules_file', true );
		$url = $id ? wp_get_attachment_url( $id ) : '';
		?>
		<div class="kac-a" style="font-size:12.5px">
			<input type="hidden" name="kac_rules_file" id="kac_rules_file" value="<?php echo (int) $id; ?>">
			<span id="kac_rules_name"><?php echo $url ? '📄 <a href="' . esc_url( $url ) . '" target="_blank">' . esc_html( basename( $url ) ) . '</a>' : esc_html__( 'Файл не выбран', 'kids-art-contest' ); ?></span><br>
			<a href="#" id="kac_rules_pick" style="color:#2271b1"><?php echo $url ? esc_html__( 'Заменить', 'kids-art-contest' ) : esc_html__( 'Выбрать PDF', 'kids-art-contest' ); ?></a> · <a href="#" id="kac_rules_clear" style="color:#b32d2e"><?php esc_html_e( 'Убрать', 'kids-art-contest' ); ?></a>
		</div>
		<?php
	}

	public static function box_moderators( WP_Post $post ): void {
		$sel   = array_map( 'absint', (array) get_post_meta( $post->ID, '_kac_moderators', true ) );
		$users = get_users( array( 'role__in' => array( 'kac_moderator', 'administrator' ) ) );
		echo '<div class="kac-a" style="font-size:12.5px">';
		foreach ( $users as $u ) {
			echo '<label class="ck" style="margin:2px 0"><input type="checkbox" name="kac_moderators[]" value="' . (int) $u->ID . '" ' . checked( in_array( $u->ID, $sel, true ), true, false ) . '> ' . esc_html( $u->display_name ) . '</label>';
		}
		echo '<span style="color:#646970">' . esc_html__( 'Все пользователи с ролью «Модератор конкурса». Можно ограничить конкретными — они получают письма о новых работах.', 'kids-art-contest' ) . '</span></div>';
	}

	/* ---------- Сохранение ---------- */

	private static function ts( string $key ): int {
		$v = isset( $_POST[ $key ] ) ? sanitize_text_field( wp_unslash( $_POST[ $key ] ) ) : ''; // phpcs:ignore
		if ( '' === $v ) {
			return 0;
		}
		$dt  = DateTime::createFromFormat( 'd.m.Y H:i', $v, wp_timezone() );
		$err = DateTime::getLastErrors();
		if ( ! $dt || ( $err && ( $err['warning_count'] || $err['error_count'] ) ) ) {
			$dt = null;
			if ( preg_match( '/^\d{4}-\d{2}-\d{2}[T ]\d{2}:\d{2}$/', $v ) ) { // Запасной вариант: ISO.
				$dt = date_create( $v, wp_timezone() ) ?: null;
			}
		}
		if ( ! $dt ) {
			self::$bad_date = true;
			return 0;
		}
		return $dt->getTimestamp();
	}

	private static bool $bad_date = false;

	public static function save( int $post_id, WP_Post $post ): void {
		if ( ! isset( $_POST['kac_contest_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['kac_contest_nonce'] ) ), 'kac_contest_save' ) ) {
			return;
		}
		if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || ! current_user_can( 'kac_manage_contests' ) || wp_is_post_revision( $post_id ) ) {
			return;
		}
		$errors = array();
		self::$bad_date = false;

		$ss = self::ts( 'kac_submit_start' );
		$se = self::ts( 'kac_submit_end' );
		$vs = self::ts( 'kac_vote_start' );
		$ve = self::ts( 'kac_vote_end' );
		$vw = ! empty( $_POST['kac_vote_with_submit'] ) ? 1 : 0; // phpcs:ignore
		if ( self::$bad_date ) {
			$errors[] = __( 'Дата указана неверно. Формат: ДД.ММ.ГГГГ ЧЧ:ММ, например 01.11.2026 00:00.', 'kids-art-contest' );
		}
		if ( $ss && $se && $se <= $ss ) {
			$errors[] = __( 'Конец приёма работ должен быть позже начала.', 'kids-art-contest' );
		}
		if ( $vs && $ve && $ve <= $vs ) {
			$errors[] = __( 'Конец голосования должен быть позже начала.', 'kids-art-contest' );
		}
		if ( $se && $ve && $ve < $se ) {
			$errors[] = __( 'Голосование не может закончиться раньше конца приёма работ.', 'kids-art-contest' );
		}
		if ( ! $vw && $vs && $se && $vs < $se ) {
			$errors[] = __( 'Голосование раньше конца приёма работ — отметьте «Голосование начинается вместе с приёмом работ».', 'kids-art-contest' );
		}
		if ( $vs && $ss && $vs < $ss ) {
			$errors[] = __( 'Голосование не может начаться раньше приёма работ.', 'kids-art-contest' );
		}
		if ( ! $errors ) {
			update_post_meta( $post_id, '_kac_submit_start', $ss );
			update_post_meta( $post_id, '_kac_submit_end', $se );
			update_post_meta( $post_id, '_kac_vote_start', $vs );
			update_post_meta( $post_id, '_kac_vote_end', $ve );
			update_post_meta( $post_id, '_kac_vote_with_submit', $vw );
		}

		$min = isset( $_POST['kac_min_age'] ) ? absint( $_POST['kac_min_age'] ) : 3; // phpcs:ignore
		$max = isset( $_POST['kac_max_age'] ) ? absint( $_POST['kac_max_age'] ) : 17; // phpcs:ignore
		$groups = array();
		if ( ! empty( $_POST['kac_groups'] ) && is_array( $_POST['kac_groups'] ) ) { // phpcs:ignore
			foreach ( wp_unslash( $_POST['kac_groups'] ) as $g ) { // phpcs:ignore
				$label = sanitize_text_field( $g['label'] ?? '' );
				if ( '' === $label && '' === ( $g['from'] ?? '' ) ) {
					continue;
				}
				$from     = absint( $g['from'] ?? 0 );
				$to       = absint( $g['to'] ?? 0 );
				$groups[] = array(
					'key'   => 'g' . $from . '_' . $to,
					'label' => '' !== $label ? $label : sprintf( '%d–%d', $from, $to ),
					'from'  => $from,
					'to'    => $to,
				);
			}
		}
		$valid = KAC_Contest::validate_groups( $groups, $min, $max );
		if ( true !== $valid ) {
			$errors[] = $valid;
		} else {
			usort( $groups, static fn( $a, $b ) => $a['from'] <=> $b['from'] );
			update_post_meta( $post_id, '_kac_min_age', $min );
			update_post_meta( $post_id, '_kac_max_age', $max );
			update_post_meta( $post_id, '_kac_age_groups', $groups ? wp_json_encode( $groups, JSON_UNESCAPED_UNICODE ) : '' );
		}

		update_post_meta( $post_id, '_kac_prize_places', isset( $_POST['kac_prize_places'] ) ? max( 1, absint( $_POST['kac_prize_places'] ) ) : 3 ); // phpcs:ignore
		update_post_meta( $post_id, '_kac_max_works', isset( $_POST['kac_max_works'] ) ? max( 1, absint( $_POST['kac_max_works'] ) ) : 1 ); // phpcs:ignore
		update_post_meta( $post_id, '_kac_ticket_prefix', isset( $_POST['kac_ticket_prefix'] ) ? sanitize_text_field( wp_unslash( $_POST['kac_ticket_prefix'] ) ) : 'ЗС' ); // phpcs:ignore

		$rules = array();
		if ( ! empty( $_POST['kac_up_max_mb'] ) ) { // phpcs:ignore
			$rules['max_bytes'] = (int) round( (float) $_POST['kac_up_max_mb'] * 1048576 ); // phpcs:ignore
		}
		foreach ( array( 'min_w', 'min_h', 'max_w', 'max_h' ) as $k ) {
			if ( ! empty( $_POST[ 'kac_up_' . $k ] ) ) { // phpcs:ignore
				$rules[ $k ] = absint( $_POST[ 'kac_up_' . $k ] ); // phpcs:ignore
			}
		}
		if ( ! empty( $_POST['kac_up_formats'] ) ) { // phpcs:ignore
			$f = array_values( array_intersect( array( 'jpg', 'png', 'webp' ), array_map( 'sanitize_key', (array) $_POST['kac_up_formats'] ) ) ); // phpcs:ignore
			if ( $f !== array_values( (array) kac_setting( 'upload_formats' ) ) ) {
				$rules['formats'] = $f;
			}
		}
		$eff = array_merge( array( 'min_w' => (int) kac_setting( 'upload_min_w' ), 'min_h' => (int) kac_setting( 'upload_min_h' ), 'max_w' => (int) kac_setting( 'upload_max_w' ), 'max_h' => (int) kac_setting( 'upload_max_h' ) ), $rules );
		if ( $eff['min_w'] > $eff['max_w'] || $eff['min_h'] > $eff['max_h'] ) {
			$errors[] = __( 'Минимальный размер изображения больше максимального.', 'kids-art-contest' );
		} else {
			update_post_meta( $post_id, '_kac_upload_rules', $rules ? wp_json_encode( $rules ) : '' );
		}

		foreach ( array( 'kac_vote_mode' => array( 'per_work', 'per_contest', 'per_group', 'n_per_contest' ), 'kac_show_votes' => array( 'always', 'after_end', 'never' ), 'kac_results_mode' => array( 'auto', 'manual' ) ) as $f => $allowed ) {
			if ( isset( $_POST[ $f ] ) ) { // phpcs:ignore
				$val = sanitize_key( wp_unslash( $_POST[ $f ] ) ); // phpcs:ignore
				update_post_meta( $post_id, '_' . $f, in_array( $val, $allowed, true ) ? $val : $allowed[0] );
			}
		}
		update_post_meta( $post_id, '_kac_vote_limit', isset( $_POST['kac_vote_limit'] ) ? max( 1, absint( $_POST['kac_vote_limit'] ) ) : 5 ); // phpcs:ignore
		update_post_meta( $post_id, '_kac_show_leaders', empty( $_POST['kac_show_leaders'] ) ? 0 : 1 ); // phpcs:ignore
		update_post_meta( $post_id, '_kac_rules_file', isset( $_POST['kac_rules_file'] ) ? absint( $_POST['kac_rules_file'] ) : 0 ); // phpcs:ignore
		update_post_meta( $post_id, '_kac_moderators', isset( $_POST['kac_moderators'] ) ? array_map( 'absint', (array) $_POST['kac_moderators'] ) : array() ); // phpcs:ignore

		KAC_Results::schedule( $post_id );

		if ( $errors ) {
			set_transient( 'kac_errors_' . get_current_user_id(), $errors, 120 );
		}
	}

	public static function notices(): void {
		$key = 'kac_errors_' . get_current_user_id();
		$e   = get_transient( $key );
		if ( $e ) {
			delete_transient( $key );
			echo '<div class="notice notice-error"><p><strong>' . esc_html__( 'Часть настроек конкурса не сохранена:', 'kids-art-contest' ) . '</strong></p><ul style="list-style:disc;margin-left:20px">';
			foreach ( $e as $m ) {
				echo '<li>' . esc_html( $m ) . '</li>';
			}
			echo '</ul></div>';
		}
	}

	public static function on_delete( int $post_id ): void {
		if ( 'kac_contest' === get_post_type( $post_id ) ) {
			wp_clear_scheduled_hook( 'kac_phase_change', array( $post_id ) );
			wp_clear_scheduled_hook( 'kac_finalize_contest', array( $post_id ) );
		}
	}

	/* ---------- Список конкурсов (макет, экран 10) ---------- */

	public static function phase_pill( int $id ): string {
		$phase = KAC_Phase::get( $id );
		$map   = array(
			'draft'      => array( 'gray', __( 'Черновик', 'kids-art-contest' ) ),
			'upcoming'   => array( 'blue', __( 'Запланирован', 'kids-art-contest' ) ),
			'submission' => array( 'green', KAC_Phase::can_vote( $id ) ? __( 'Приём + голосование', 'kids-art-contest' ) : __( 'Приём работ', 'kids-art-contest' ) ),
			'voting'     => array( 'green', __( 'Голосование', 'kids-art-contest' ) ),
			'finished'   => array( 'orange', __( 'Ждёт публикации итогов', 'kids-art-contest' ) ),
			'results'    => array( 'purple', __( 'Итоги опубликованы', 'kids-art-contest' ) ),
		);
		$p = $map[ $phase ] ?? array( 'gray', $phase );
		return '<span class="pill ' . esc_attr( $p[0] ) . '">' . esc_html( $p[1] ) . '</span>';
	}

	public static function columns( array $cols ): array {
		return array(
			'cb'        => $cols['cb'],
			'title'     => __( 'Название', 'kids-art-contest' ),
			'kac_phase' => __( 'Статус', 'kids-art-contest' ),
			'kac_sub'   => __( 'Приём работ', 'kids-art-contest' ),
			'kac_vote'  => __( 'Голосование', 'kids-art-contest' ),
			'kac_age'   => __( 'Возраст', 'kids-art-contest' ),
			'kac_works' => __( 'Работы', 'kids-art-contest' ),
			'kac_votes' => __( 'Голоса', 'kids-art-contest' ),
			'kac_sc'    => __( 'Шорткод', 'kids-art-contest' ),
		);
	}

	public static function column( string $col, int $id ): void {
		global $wpdb;
		$d = KAC_Phase::dates( $id );
		echo '<span class="kac-a">';
		switch ( $col ) {
			case 'kac_phase':
				echo self::phase_pill( $id ); // phpcs:ignore WordPress.Security.EscapeOutput
				break;
			case 'kac_sub':
				echo $d['submit_start'] ? esc_html( kac_date( $d['submit_start'], 'd.m' ) . ' – ' . kac_date( $d['submit_end'], 'd.m.Y' ) ) : '—';
				break;
			case 'kac_vote':
				echo $d['vote_start'] ? esc_html( kac_date( $d['vote_start'], 'd.m' ) . ' – ' . kac_date( $d['vote_end'], 'd.m.Y' ) ) : '—';
				break;
			case 'kac_age':
				echo esc_html( KAC_Contest::min_age( $id ) . '–' . KAC_Contest::max_age( $id ) );
				break;
			case 'kac_works':
				$c = KAC_Contest::work_counts( $id );
				echo $c['publish'] || $c['kac_pending'] ? '<b>' . (int) $c['publish'] . '</b>' : '—';
				if ( $c['kac_pending'] ) {
					echo ' <span class="pill orange">+' . (int) $c['kac_pending'] . ' ' . esc_html__( 'на модерации', 'kids-art-contest' ) . '</span>';
				}
				break;
			case 'kac_votes':
				$n = (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM ' . KAC_Voting::table() . " WHERE contest_id = %d AND status <> 'annulled'", $id ) );
				echo $n ? esc_html( number_format_i18n( $n ) ) : '—';
				break;
			case 'kac_sc':
				$code = '[kac_contest id="' . $id . '"]';
				echo '<code class="shortcode">' . esc_html( $code ) . '</code> <a href="#" class="kac-copy" data-copy="' . esc_attr( $code ) . '">📋</a>';
				break;
		}
		echo '</span>';
	}

	public static function row_actions( array $actions, WP_Post $post ): array {
		if ( 'kac_contest' !== $post->post_type ) {
			return $actions;
		}
		$out = array();
		if ( isset( $actions['edit'] ) ) {
			$out['edit'] = $actions['edit'];
		}
		if ( current_user_can( 'kac_moderate_works' ) ) {
			$out['kac_works'] = '<a href="' . esc_url( admin_url( 'admin.php?page=kac-works&contest=' . $post->ID ) ) . '">' . esc_html__( 'Работы', 'kids-art-contest' ) . '</a>';
		}
		if ( current_user_can( 'kac_publish_results' ) ) {
			$out['kac_results'] = '<a href="' . esc_url( admin_url( 'admin.php?page=kac-results&contest=' . $post->ID ) ) . '">' . esc_html__( 'Итоги', 'kids-art-contest' ) . '</a>';
		}
		if ( isset( $actions['view'] ) ) {
			$out['view'] = $actions['view'];
		}
		if ( current_user_can( 'kac_manage_contests' ) ) {
			$out['kac_dup'] = '<a href="' . esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=kac_duplicate&id=' . $post->ID ), 'kac_duplicate_' . $post->ID ) ) . '">' . esc_html__( 'Дублировать', 'kids-art-contest' ) . '</a>';
		}
		foreach ( array( 'trash', 'delete', 'untrash' ) as $k ) {
			if ( isset( $actions[ $k ] ) ) {
				$out[ $k ] = $actions[ $k ];
			}
		}
		return $out;
	}

	public static function duplicate(): void {
		$id = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;
		check_admin_referer( 'kac_duplicate_' . $id );
		if ( ! current_user_can( 'kac_manage_contests' ) || 'kac_contest' !== get_post_type( $id ) ) {
			wp_die( esc_html__( 'Нет доступа.', 'kids-art-contest' ), '', array( 'response' => 403 ) );
		}
		$src = get_post( $id );
		$new = wp_insert_post( array( 'post_type' => 'kac_contest', 'post_status' => 'draft', 'post_title' => $src->post_title . ' (копия)', 'post_content' => $src->post_content ) );
		if ( $new && ! is_wp_error( $new ) ) {
			foreach ( array( '_kac_min_age', '_kac_max_age', '_kac_age_groups', '_kac_prize_places', '_kac_max_works', '_kac_ticket_prefix', '_kac_upload_rules', '_kac_vote_mode', '_kac_vote_limit', '_kac_show_votes', '_kac_show_leaders', '_kac_results_mode', '_kac_rules_file', '_kac_vote_with_submit', '_kac_moderators' ) as $k ) {
				$v = get_post_meta( $id, $k, true );
				if ( '' !== $v && array() !== $v ) {
					update_post_meta( $new, $k, $v );
				}
			}
			$thumb = get_post_thumbnail_id( $id );
			if ( $thumb ) {
				set_post_thumbnail( $new, $thumb );
			}
			wp_safe_redirect( admin_url( 'post.php?post=' . $new . '&action=edit' ) );
			exit;
		}
		wp_safe_redirect( admin_url( 'edit.php?post_type=kac_contest' ) );
		exit;
	}

	/** Плашки-сводка над списком (макет: .kpis). */
	public static function kpis(): void {
		global $wpdb;
		$screen = get_current_screen();
		if ( ! $screen || 'edit-kac_contest' !== $screen->id ) {
			return;
		}
		$pending = KAC_Mailer::pending_count();
		$today   = (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM ' . KAC_Voting::table() . ' WHERE created_at > %s', gmdate( 'Y-m-d H:i:s', strtotime( 'today' ) ) ) );
		$susp    = (int) $wpdb->get_var( 'SELECT COUNT(*) FROM ' . KAC_Voting::table() . " WHERE status = 'suspicious'" );
		$active  = 0;
		foreach ( get_posts( array( 'post_type' => 'kac_contest', 'post_status' => 'publish', 'numberposts' => -1, 'fields' => 'ids' ) ) as $cid ) {
			if ( in_array( KAC_Phase::get( (int) $cid ), array( 'submission', 'voting' ), true ) ) {
				$active += (int) KAC_Contest::work_counts( (int) $cid )['publish'];
			}
		}
		echo '<div class="kac-a"><div class="kpis" style="margin-top:14px">';
		printf( '<a class="kpi warn" href="%s" style="text-decoration:none;color:inherit"><b>%d</b><span>%s →</span></a>', esc_url( admin_url( 'admin.php?page=kac-moderation' ) ), $pending, esc_html__( 'работ ждут модерации', 'kids-art-contest' ) );
		printf( '<div class="kpi"><b>%d</b><span>%s</span></div>', $active, esc_html__( 'работ в активном конкурсе', 'kids-art-contest' ) );
		printf( '<div class="kpi ok"><b>%d</b><span>%s</span></div>', $today, esc_html( _n( 'голос сегодня', 'голосов сегодня', $today, 'kids-art-contest' ) ) );
		printf( '<a class="kpi err" href="%s" style="text-decoration:none;color:inherit"><b>%d</b><span>%s →</span></a>', esc_url( admin_url( 'admin.php?page=kac-votes&vstatus=suspicious' ) ), $susp, esc_html__( 'подозрительных голосов', 'kids-art-contest' ) );
		echo '</div></div>';
	}

	/** Вкладки «Все | Идут | Запланированы | Завершены | Черновики». */
	public static function views( array $views ): array {
		$ids   = get_posts( array( 'post_type' => 'kac_contest', 'post_status' => array( 'publish', 'draft', 'pending', 'future', 'private' ), 'numberposts' => -1, 'fields' => 'ids' ) );
		$count = array( 'all' => 0, 'active' => 0, 'upcoming' => 0, 'finished' => 0, 'draft' => 0 );
		foreach ( $ids as $id ) {
			$count['all']++;
			$ph = KAC_Phase::get( (int) $id );
			if ( in_array( $ph, array( 'submission', 'voting' ), true ) ) {
				$count['active']++;
			} elseif ( 'upcoming' === $ph ) {
				$count['upcoming']++;
			} elseif ( in_array( $ph, array( 'finished', 'results' ), true ) ) {
				$count['finished']++;
			} else {
				$count['draft']++;
			}
		}
		$cur   = isset( $_GET['kac_phase'] ) ? sanitize_key( $_GET['kac_phase'] ) : ''; // phpcs:ignore
		$items = array( '' => array( 'all', 'Все' ), 'active' => array( 'active', 'Идут' ), 'upcoming' => array( 'upcoming', 'Запланированы' ), 'finished' => array( 'finished', 'Завершены' ), 'draft' => array( 'draft', 'Черновики' ) );
		$out   = array();
		foreach ( $items as $k => $v ) {
			$url = '' === $k ? admin_url( 'edit.php?post_type=kac_contest' ) : admin_url( 'edit.php?post_type=kac_contest&kac_phase=' . $k );
			$out[ 'kac_' . $k ] = '<a href="' . esc_url( $url ) . '"' . ( $cur === $k ? ' class="current"' : '' ) . '>' . esc_html( $v[1] ) . ' <span class="count">(' . (int) $count[ $v[0] ] . ')</span></a>';
		}
		return $out;
	}

	public static function filter_phase( WP_Query $q ): void {
		if ( ! is_admin() || ! $q->is_main_query() || 'kac_contest' !== $q->get( 'post_type' ) || empty( $_GET['kac_phase'] ) ) { // phpcs:ignore
			return;
		}
		$want = sanitize_key( $_GET['kac_phase'] ); // phpcs:ignore
		$map  = array(
			'active'   => array( 'submission', 'voting' ),
			'upcoming' => array( 'upcoming' ),
			'finished' => array( 'finished', 'results' ),
			'draft'    => array( 'draft' ),
		);
		if ( ! isset( $map[ $want ] ) ) {
			return;
		}
		$ids = array();
		foreach ( get_posts( array( 'post_type' => 'kac_contest', 'post_status' => array( 'publish', 'draft', 'pending', 'future', 'private' ), 'numberposts' => -1, 'fields' => 'ids' ) ) as $id ) {
			if ( in_array( KAC_Phase::get( (int) $id ), $map[ $want ], true ) ) {
				$ids[] = (int) $id;
			}
		}
		$q->set( 'post__in', $ids ? $ids : array( 0 ) );
	}
}
