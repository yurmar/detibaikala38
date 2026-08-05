<?php
/**
 * Дети Байкала — functions.php
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'DB_THEME_VERSION', '1.0.9' );
define( 'DB_THEME_DIR', get_template_directory() );
define( 'DB_THEME_URI', get_template_directory_uri() );

/* ------------------------------------------------------------------ */
/* Theme setup                                                          */
/* ------------------------------------------------------------------ */
function db_theme_setup() {
	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'html5', array( 'search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script' ) );
	add_theme_support( 'automatic-feed-links' );
	add_theme_support( 'responsive-embeds' );
	add_theme_support(
		'custom-logo',
		array(
			'height'      => 44,
			'width'       => 44,
			'flex-height' => true,
			'flex-width'  => true,
		)
	);

	register_nav_menus(
		array(
			'primary'         => __( 'Главное меню', 'deti-baikala' ),
			'footer-sections' => __( 'Подвал — Разделы', 'deti-baikala' ),
			'footer-projects' => __( 'Подвал — Проекты', 'deti-baikala' ),
		)
	);
}
add_action( 'after_setup_theme', 'db_theme_setup' );

/* ------------------------------------------------------------------ */
/* Widget areas                                                         */
/* ------------------------------------------------------------------ */
function db_widgets_init() {
	register_sidebar(
		array(
			'name'          => __( 'Боковая колонка — страницы', 'deti-baikala' ),
			'id'            => 'sidebar-page',
			'description'   => __( 'Используется на страницах: О фонде, Контакты, Проекты, Кампании, Отчёты, Отзывы, СМИ о фонде, одиночная новость.', 'deti-baikala' ),
			'before_widget' => '<div class="sidebar-widget %2$s">',
			'after_widget'  => '</div>',
			'before_title'  => '<div class="sidebar-widget__title">',
			'after_title'   => '</div>',
		)
	);

	register_sidebar(
		array(
			'name'          => __( 'Боковая колонка — новости', 'deti-baikala' ),
			'id'            => 'sidebar-news',
			'description'   => __( 'Используется на странице архива новостей.', 'deti-baikala' ),
			'before_widget' => '<div class="sidebar-widget %2$s">',
			'after_widget'  => '</div>',
			'before_title'  => '<div class="sidebar-widget__title">',
			'after_title'   => '</div>',
		)
	);
}
add_action( 'widgets_init', 'db_widgets_init' );

/* ------------------------------------------------------------------ */
/* Scripts & styles                                                     */
/* ------------------------------------------------------------------ */
function db_enqueue_assets() {
	wp_enqueue_style( 'db-fonts', 'https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800;900&family=Inter:wght@400;500;600;700&display=swap', array(), null );
	wp_enqueue_style( 'deti-baikala-style', get_stylesheet_uri(), array(), DB_THEME_VERSION );

	wp_enqueue_script( 'deti-baikala-main', DB_THEME_URI . '/assets/js/main.js', array(), DB_THEME_VERSION, true );

	if ( is_page_template( 'page-about.php' ) ) {
		wp_enqueue_script( 'db-team-modal', DB_THEME_URI . '/assets/js/team-modal.js', array(), DB_THEME_VERSION, true );
	}

	if ( is_singular() && comments_open() ) {
		wp_enqueue_script( 'comment-reply' );
	}
}
add_action( 'wp_enqueue_scripts', 'db_enqueue_assets' );

function db_enqueue_block_editor_assets() {
	wp_enqueue_script(
		'db-team-format',
		DB_THEME_URI . '/assets/js/team-format.js',
		array( 'wp-rich-text', 'wp-block-editor', 'wp-components', 'wp-element', 'wp-api-fetch' ),
		DB_THEME_VERSION,
		true
	);
}
add_action( 'enqueue_block_editor_assets', 'db_enqueue_block_editor_assets' );

function db_register_team_meta() {
	register_post_meta(
		'team_member',
		'_team_role',
		array(
			'type'         => 'string',
			'single'       => true,
			'show_in_rest' => true,
		)
	);
	register_post_meta(
		'team_member',
		'_team_description',
		array(
			'type'         => 'string',
			'single'       => true,
			'show_in_rest' => true,
		)
	);
}
add_action( 'init', 'db_register_team_meta' );

/**
 * Preconnect-хинты для Google Fonts.
 */
function db_resource_hints( $urls, $relation_type ) {
	if ( 'preconnect' === $relation_type ) {
		$urls[] = array(
			'href' => 'https://fonts.googleapis.com',
		);
		$urls[] = array(
			'href'        => 'https://fonts.gstatic.com',
			'crossorigin' => true,
		);
	}
	return $urls;
}
add_filter( 'wp_resource_hints', 'db_resource_hints', 10, 2 );

/**
 * Раннее выставление data-theme до рендера страницы — убирает "мигание" темы.
 */
function db_early_theme_script() {
	?>
	<script>
	(function(){
		try {
			var t = localStorage.getItem('theme') || 'light';
			document.documentElement.setAttribute('data-theme', t);
		} catch(e) {}
	})();
	</script>
	<?php
}
add_action( 'wp_head', 'db_early_theme_script', 0 );

/* ------------------------------------------------------------------ */
/* Подключение модулей темы                                             */
/* ------------------------------------------------------------------ */
require DB_THEME_DIR . '/inc/cpt.php';
require DB_THEME_DIR . '/inc/metaboxes.php';
require DB_THEME_DIR . '/inc/customizer.php';
require DB_THEME_DIR . '/inc/widgets.php';
require DB_THEME_DIR . '/inc/contact-form.php';
require DB_THEME_DIR . '/inc/nastavnik-form.php';
require DB_THEME_DIR . '/inc/template-tags.php';
require DB_THEME_DIR . '/inc/leyka-helpers.php';

/* ------------------------------------------------------------------ */
/* Сидирование категорий и таксономий при активации темы                */
/* ------------------------------------------------------------------ */
function db_theme_activation() {
	// Категории "Новости" и "СМИ о фонде"
	if ( ! term_exists( 'novosti', 'category' ) ) {
		wp_insert_term( 'Новости', 'category', array( 'slug' => 'novosti' ) );
	}
	if ( ! term_exists( 'smi-o-fonde', 'category' ) ) {
		wp_insert_term( 'СМИ о фонде', 'category', array( 'slug' => 'smi-o-fonde' ) );
	}

	// Термины таксономии "Год отчёта" — 2018–2024
	if ( taxonomy_exists( 'report_year' ) ) {
		for ( $year = 2018; $year <= 2024; $year++ ) {
			if ( ! term_exists( (string) $year, 'report_year' ) ) {
				wp_insert_term( (string) $year, 'report_year', array( 'slug' => (string) $year ) );
			}
		}
	}

	flush_rewrite_rules();
}
add_action( 'after_switch_theme', 'db_theme_activation' );

/* ------------------------------------------------------------------ */
/* Прочее                                                               */
/* ------------------------------------------------------------------ */

// Обрезка текста для excerpt-карточек
function db_excerpt( $text, $length = 120 ) {
	$text = wp_strip_all_tags( $text );
	if ( mb_strlen( $text ) <= $length ) {
		return $text;
	}
	return mb_substr( $text, 0, $length ) . '…';
}

// Длина авто-excerpt
function db_excerpt_length( $length ) {
	return 30;
}
add_filter( 'excerpt_length', 'db_excerpt_length' );

function db_excerpt_more( $more ) {
	return '…';
}
add_filter( 'excerpt_more', 'db_excerpt_more' );

/**
 * Главная лента записей (Настройки → Чтение → "Страница записей") выводит
 * только записи из категории "Новости" (slug novosti).
 */
function db_home_query( $query ) {
	if ( ! is_admin() && $query->is_main_query() && $query->is_home() ) {
		$query->set( 'category_name', 'novosti' );
		$query->set( 'posts_per_page', 9 );
	}
}
add_action( 'pre_get_posts', 'db_home_query' );

/**
 * Уменьшаем логотип на странице "Технические работы" (плагин Maintenance) —
 * загруженная картинка слишком крупная.
 */
function db_maintenance_logo_style() {
	?>
	<style>
		.logo-box img{width:120px;height:auto;max-width:120px}
	</style>
	<?php
}
add_action( 'load_custom_style', 'db_maintenance_logo_style', 30 );
