<?php
defined( 'ABSPATH' ) || exit;

/**
 * Публичная часть: шорткоды, шаблоны, ассеты, OG-теги.
 */
class KAC_Frontend {

	private static bool $assets_needed = false;
	private static bool $rendering = false;

	public static function init(): void {
		add_shortcode( 'kac_contest', array( __CLASS__, 'sc_contest' ) );
		add_shortcode( 'kac_submit_form', array( __CLASS__, 'sc_form' ) );
		add_shortcode( 'kac_results', array( __CLASS__, 'sc_results' ) );
		add_shortcode( 'kac_contests', array( __CLASS__, 'sc_contests' ) );

		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'register_assets' ) );
		add_filter( 'the_content', array( __CLASS__, 'filter_content' ), 20 );
		add_filter( 'template_include', array( __CLASS__, 'archive_template' ) );
		add_action( 'wp_head', array( __CLASS__, 'og_tags' ), 5 );
		add_filter( 'document_title_parts', array( __CLASS__, 'title_parts' ) );
		add_action( 'wp_footer', array( __CLASS__, 'footer_overlays' ), 5 );
		add_filter( 'the_title', array( __CLASS__, 'hide_contest_title' ), 10, 2 );
		add_filter( 'body_class', array( __CLASS__, 'body_class' ) );
		add_action( 'send_headers', array( __CLASS__, 'maybe_nocache' ) );
		add_action( 'template_redirect', array( __CLASS__, 'guard_work' ) );
	}

	/* ---------- Ассеты ---------- */

	public static function register_assets(): void {
		$u = KAC_URL . 'public/assets/';
		$v = KAC_VERSION;
		if ( 'nunito' === kac_setting( 'font' ) ) {
			wp_register_style( 'kac-font', 'https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800;900&display=swap', array(), null ); // phpcs:ignore WordPress.WP.EnqueueResourceParameters
		}
		wp_register_style( 'kac', $u . 'kac.css', 'nunito' === kac_setting( 'font' ) ? array( 'kac-font' ) : array(), $v );
		wp_add_inline_style( 'kac', self::inline_css() );

		wp_register_script( 'kac-core', $u . 'core.js', array(), $v, true );
		wp_register_script( 'kac-captcha', $u . 'captcha.js', array( 'kac-core' ), $v, true );
		wp_register_script( 'kac-fp', $u . 'fp.js', array( 'kac-core' ), $v, true );
		wp_register_script( 'kac-vote', $u . 'vote.js', array( 'kac-core', 'kac-fp', 'kac-captcha' ), $v, true );
		wp_register_script( 'kac-lightbox', $u . 'lightbox.js', array( 'kac-core', 'kac-vote' ), $v, true );
		wp_register_script( 'kac-gallery', $u . 'gallery.js', array( 'kac-core', 'kac-lightbox' ), $v, true );
		wp_register_script( 'kac-masks', $u . 'masks.js', array(), $v, true );
		wp_register_script( 'kac-form', $u . 'form.js', array( 'kac-core', 'kac-masks', 'kac-captcha' ), $v, true );

		wp_localize_script(
			'kac-core',
			'KAC',
			array(
				'rest'    => esc_url_raw( rest_url( 'kac/v1' ) ),
				'captcha' => KAC_Captcha::client_config(),
				'i18n'    => array(
					'voted'     => __( 'Голос учтён', 'kids-art-contest' ),
					'voted2'    => __( 'Вы уже голосовали', 'kids-art-contest' ),
					'thanks'    => __( 'Спасибо! Ваш голос учтён 💛', 'kids-art-contest' ),
					'already'   => __( 'Вы уже голосовали за эту работу', 'kids-art-contest' ),
					'vote'      => (string) kac_setting( 'vote_button_text', 'Проголосовать за работу' ),
					'error'     => __( 'Что-то пошло не так. Попробуйте ещё раз.', 'kids-art-contest' ),
					'copied'    => __( 'Ссылка скопирована', 'kids-art-contest' ),
					'captcha'   => __( 'Подтвердите, что вы не робот. Сколько будет', 'kids-art-contest' ),
					'send'      => __( 'Готово', 'kids-art-contest' ),
					'nothing'   => __( 'Ничего не найдено', 'kids-art-contest' ),
					'empty'     => __( 'Работ пока нет', 'kids-art-contest' ),
					'required'  => __( 'Заполните отмеченные поля', 'kids-art-contest' ),
					'days'      => __( 'дней', 'kids-art-contest' ),
					'hours'     => __( 'часов', 'kids-art-contest' ),
					'minutes'   => __( 'минут', 'kids-art-contest' ),
					'next'      => __( 'Далее →', 'kids-art-contest' ),
					'submit'    => __( 'Отправить на модерацию', 'kids-art-contest' ),
					'sending'   => __( 'Отправляем…', 'kids-art-contest' ),
					'step'      => __( 'Шаг %1$d из %2$d', 'kids-art-contest' ),
					'photo'     => __( 'Название работы', 'kids-art-contest' ),
					'author'    => __( 'Автор:', 'kids-art-contest' ),
					'workno'    => __( 'РАБОТА №', 'kids-art-contest' ),
					'votesWord' => __( 'голосов', 'kids-art-contest' ),
					'hint'      => __( 'Голосовать можно один раз за каждую работу', 'kids-art-contest' ),
					'link'      => __( 'Ссылка', 'kids-art-contest' ),
					'keys'      => __( '← → листать · Esc закрыть · клик по рисунку — увеличить', 'kids-art-contest' ),
					'close'     => __( 'Закрыть', 'kids-art-contest' ),
				),
			)
		);
	}

	private static function inline_css(): string {
		$accent  = sanitize_hex_color( (string) kac_setting( 'accent_color' ) ) ?: '#ff6b5b';
		$ok      = sanitize_hex_color( (string) kac_setting( 'ok_color' ) ) ?: '#22b88f';
		$cols    = max( 1, min( 5, (int) kac_setting( 'columns', 3 ) ) );
		$font    = 'nunito' === kac_setting( 'font' ) ? '"Nunito","Segoe UI",system-ui,sans-serif' : 'inherit';
		$darker  = '#' . implode( '', array_map( static fn( $c ) => str_pad( dechex( (int) max( 0, hexdec( $c ) * 0.88 ) ), 2, '0', STR_PAD_LEFT ), str_split( ltrim( $accent, '#' ) ?: '000000', 2 ) ) );
		if ( 4 === strlen( $accent ) ) {
			$darker = $accent;
		}
		return ".kac{--coral:{$accent};--coral-d:{$darker};--mint:{$ok};--kac-cols:{$cols};--kac-font:{$font}}";
	}

	public static function need_assets( array $extra = array() ): void {
		self::$assets_needed = true;
		wp_enqueue_style( 'kac' );
		wp_enqueue_script( 'kac-core' );
		foreach ( $extra as $h ) {
			wp_enqueue_script( $h );
		}
	}

	public static function body_class( array $c ): array {
		if ( is_singular( array( 'kac_contest', 'kac_work' ) ) || is_post_type_archive( 'kac_contest' ) ) {
			$c[] = 'kac-page';
		}
		return $c;
	}

	/** Страницы конкурса зависят от дат и счётчиков — не кешируем HTML на стороне браузера/CDN. */
	public static function maybe_nocache(): void {
		if ( ! is_admin() && ( is_singular( array( 'kac_contest', 'kac_work' ) ) || is_post_type_archive( 'kac_contest' ) ) ) {
			header( 'Cache-Control: no-cache, must-revalidate, max-age=0' );
		}
	}

	/* ---------- Шорткоды ---------- */

	public static function sc_contest( $atts ): string {
		$a  = shortcode_atts( array( 'id' => 0 ), $atts, 'kac_contest' );
		$id = absint( $a['id'] );
		return self::render_contest( $id, true );
	}

	public static function sc_form( $atts ): string {
		$a  = shortcode_atts( array( 'id' => 0 ), $atts, 'kac_submit_form' );
		$id = absint( $a['id'] );
		if ( ! self::is_public_contest( $id ) ) {
			return '';
		}
		self::need_assets( array( 'kac-form' ) );
		return self::render( 'form', array( 'contest_id' => $id, 'standalone' => true ) );
	}

	public static function sc_results( $atts ): string {
		$a  = shortcode_atts( array( 'id' => 0 ), $atts, 'kac_results' );
		$id = absint( $a['id'] );
		if ( ! self::is_public_contest( $id ) ) {
			return '';
		}
		self::need_assets( array( 'kac-gallery' ) );
		return self::render( 'results', array( 'contest_id' => $id ) );
	}

	public static function sc_contests( $atts ): string {
		$a = shortcode_atts( array( 'status' => 'all', 'per_page' => 9 ), $atts, 'kac_contests' );
		self::need_assets();
		$ids = get_posts( array( 'post_type' => 'kac_contest', 'post_status' => 'publish', 'numberposts' => 100, 'fields' => 'ids', 'orderby' => 'date', 'order' => 'DESC' ) );
		$map = array(
			'active'   => array( 'submission', 'voting' ),
			'upcoming' => array( 'upcoming' ),
			'finished' => array( 'finished', 'results' ),
		);
		if ( isset( $map[ $a['status'] ] ) ) {
			$ids = array_filter( $ids, static fn( $i ) => in_array( KAC_Phase::get( (int) $i ), $map[ $a['status'] ], true ) );
		}
		$ids = array_slice( array_values( $ids ), 0, max( 1, (int) $a['per_page'] ) );
		return self::render( 'contest-list', array( 'ids' => $ids ) );
	}

	/* ---------- Шаблоны ---------- */

	public static function render( string $tpl, array $vars = array() ): string {
		self::$rendering = true; // Внутри наших шаблонов заголовок конкурса не скрываем.
		ob_start();
		kac_template( $tpl, $vars );
		$out = (string) ob_get_clean();
		self::$rendering = false;
		return $out;
	}

	public static function is_public_contest( int $id ): bool {
		$p = $id ? get_post( $id ) : null;
		return $p && 'kac_contest' === $p->post_type && 'publish' === $p->post_status;
	}

	public static function render_contest( int $id, bool $with_content = false ): string {
		if ( ! self::is_public_contest( $id ) ) {
			return '';
		}
		self::need_assets( array( 'kac-gallery', 'kac-form' ) );
		return self::render( 'contest', array( 'contest_id' => $id, 'with_content' => $with_content ) );
	}

	public static function filter_content( string $content ): string {
		if ( ! in_the_loop() || ! is_main_query() ) {
			return $content;
		}
		if ( is_singular( 'kac_contest' ) ) {
			return self::render_contest( (int) get_the_ID(), true );
		}
		if ( is_singular( 'kac_work' ) ) {
			self::need_assets( array( 'kac-vote' ) );
			return self::render( 'work', array( 'work' => get_post() ) );
		}
		return $content;
	}

	public static function archive_template( string $template ): string {
		if ( is_post_type_archive( 'kac_contest' ) ) {
			$own = locate_template( array( 'kids-art-contest/archive-kac_contest.php', 'archive-kac_contest.php' ) );
			if ( $own ) {
				return $own;
			}
			return KAC_DIR . 'public/templates/archive-kac_contest.php';
		}
		return $template;
	}

	/* ---------- SEO / соцсети ---------- */

	public static function og_tags(): void {
		if ( ! is_singular( 'kac_work' ) ) {
			return;
		}
		$w   = get_post();
		$img = get_the_post_thumbnail_url( $w, 'kac_large' ) ?: get_the_post_thumbnail_url( $w, 'full' );
		$d   = KAC_Gallery::work_data( $w );
		$desc = wp_trim_words( $w->post_content ?: sprintf( '%s, %s', $d['name'], Kac_Format::age( $d['age'] ) ), 30 );
		echo '<meta property="og:type" content="article">' . "\n";
		echo '<meta property="og:title" content="' . esc_attr( $d['title'] . ' — ' . get_the_title( $w->post_parent ) ) . '">' . "\n";
		echo '<meta property="og:description" content="' . esc_attr( $desc ) . '">' . "\n";
		echo '<meta property="og:url" content="' . esc_url( get_permalink( $w ) ) . '">' . "\n";
		if ( $img ) {
			echo '<meta property="og:image" content="' . esc_url( $img ) . '">' . "\n";
			echo '<meta name="twitter:card" content="summary_large_image">' . "\n";
		}
	}

	public static function title_parts( array $parts ): array {
		if ( is_singular( 'kac_work' ) ) {
			$w            = get_post();
			$parts['title'] = get_the_title( $w ) . ' — ' . get_the_title( $w->post_parent );
		}
		return $parts;
	}

	/** Работы чернового/удалённого конкурса не показываем. */
	public static function guard_work(): void {
		if ( is_singular( 'kac_work' ) && ! current_user_can( 'kac_moderate_works' ) && ! self::is_public_contest( (int) wp_get_post_parent_id( (int) get_queried_object_id() ) ) ) {
			global $wp_query;
			$wp_query->set_404();
			status_header( 404 );
			nocache_headers();
		}
	}

	/** Контейнер лайтбокса и уведомлений (в макете: #lb и .toast). */
	public static function footer_overlays(): void {
		if ( ! self::$assets_needed ) {
			return;
		}
		echo '<div class="kac"><div class="lb" id="kac-lb" hidden></div><div class="toast" id="kac-toast" role="status"></div></div>';
	}

	/** На странице конкурса заголовок уже есть в шапке блока (как в макете), заголовок темы убираем. */
	public static function hide_contest_title( $title, $id = 0 ) {
		if ( ! self::$rendering && ! is_admin() && in_the_loop() && is_main_query() && is_singular( 'kac_contest' ) && (int) $id === (int) get_queried_object_id() ) {
			return '';
		}
		return $title;
	}
}

/**
 * Небольшие форматтеры для шаблонов.
 */
class Kac_Format {
	/** «1–30 ноября» или «1 ноября – 5 декабря». */
	public static function range( int $from, int $to ): string {
		if ( ! $from || ! $to ) {
			return kac_date( $from ?: $to, 'j F' );
		}
		if ( wp_date( 'mY', $from ) === wp_date( 'mY', $to ) ) {
			return wp_date( 'j', $from ) . '–' . wp_date( 'j F', $to );
		}
		return wp_date( 'j F', $from ) . ' – ' . wp_date( 'j F', $to );
	}

	public static function mask_email( string $email ): string {
		$p = explode( '@', $email );
		if ( 2 !== count( $p ) ) {
			return $email;
		}
		$l = $p[0];
		return ( strlen( $l ) > 2 ? $l[0] . str_repeat( '*', max( 1, strlen( $l ) - 2 ) ) . substr( $l, -1 ) : $l[0] . '*' ) . '@' . $p[1];
	}

	public static function age( int $age ): string {
		return KAC_Mailer::age_text( $age );
	}

	public static function countdown_target( int $contest_id ): array {
		$d     = KAC_Phase::dates( $contest_id );
		$phase = KAC_Phase::get( $contest_id );
		$now   = time();
		switch ( $phase ) {
			case 'upcoming':
				$ts = $d['submit_start'] > $now ? $d['submit_start'] : $d['vote_start'];
				return array( $ts, __( 'До старта приёма работ', 'kids-art-contest' ) );
			case 'submission':
				return array( $d['submit_end'], __( 'До конца приёма работ', 'kids-art-contest' ) );
			case 'voting':
				return array( $d['vote_end'], __( 'До конца голосования', 'kids-art-contest' ) );
		}
		return array( 0, '' );
	}
}
