<?php
/**
 * Дети Байкала — регистрация типов записей и таксономий.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

function db_register_post_types() {

	// Проекты
	register_post_type(
		'project',
		array(
			'labels' => array(
				'name'               => __( 'Проекты', 'deti-baikala' ),
				'singular_name'      => __( 'Проект', 'deti-baikala' ),
				'add_new_item'       => __( 'Добавить проект', 'deti-baikala' ),
				'edit_item'          => __( 'Редактировать проект', 'deti-baikala' ),
				'all_items'          => __( 'Все проекты', 'deti-baikala' ),
				'menu_name'          => __( 'Проекты', 'deti-baikala' ),
			),
			'public'        => true,
			'has_archive'   => false,
			'rewrite'       => array( 'slug' => 'projects' ),
			'menu_icon'     => 'dashicons-heart',
			'supports'      => array( 'title', 'editor', 'thumbnail', 'excerpt' ),
			'show_in_rest'  => true,
		)
	);

	// Отзывы
	register_post_type(
		'review_item',
		array(
			'labels' => array(
				'name'          => __( 'Отзывы', 'deti-baikala' ),
				'singular_name' => __( 'Отзыв', 'deti-baikala' ),
				'add_new_item'  => __( 'Добавить отзыв', 'deti-baikala' ),
				'edit_item'     => __( 'Редактировать отзыв', 'deti-baikala' ),
				'all_items'     => __( 'Все отзывы', 'deti-baikala' ),
				'menu_name'     => __( 'Отзывы', 'deti-baikala' ),
			),
			'public'             => false,
			'publicly_queryable' => false,
			'show_ui'            => true,
			'show_in_menu'       => true,
			'has_archive'        => false,
			'menu_icon'          => 'dashicons-format-quote',
			'supports'           => array( 'title', 'editor', 'thumbnail' ),
		)
	);

	// Отчёты
	register_post_type(
		'report_item',
		array(
			'labels' => array(
				'name'          => __( 'Отчёты', 'deti-baikala' ),
				'singular_name' => __( 'Отчёт', 'deti-baikala' ),
				'add_new_item'  => __( 'Добавить отчёт', 'deti-baikala' ),
				'edit_item'     => __( 'Редактировать отчёт', 'deti-baikala' ),
				'all_items'     => __( 'Все отчёты', 'deti-baikala' ),
				'menu_name'     => __( 'Отчёты', 'deti-baikala' ),
			),
			'public'             => false,
			'publicly_queryable' => false,
			'show_ui'            => true,
			'show_in_menu'       => true,
			'has_archive'        => false,
			'menu_icon'          => 'dashicons-media-document',
			'supports'           => array( 'title' ),
		)
	);

	// Команда
	register_post_type(
		'team_member',
		array(
			'labels' => array(
				'name'          => __( 'Команда', 'deti-baikala' ),
				'singular_name' => __( 'Сотрудник', 'deti-baikala' ),
				'add_new_item'  => __( 'Добавить сотрудника', 'deti-baikala' ),
				'edit_item'     => __( 'Редактировать сотрудника', 'deti-baikala' ),
				'all_items'     => __( 'Команда', 'deti-baikala' ),
				'menu_name'     => __( 'Команда', 'deti-baikala' ),
			),
			'public'             => false,
			'publicly_queryable' => false,
			'show_ui'            => true,
			'show_in_menu'       => true,
			'show_in_rest'       => true,
			'rest_base'          => 'team_member',
			'has_archive'        => false,
			'menu_icon'          => 'dashicons-groups',
			'supports'           => array( 'title', 'thumbnail' ),
		)
	);

	// Документы и сертификаты
	register_post_type(
		'fund_document',
		array(
			'labels' => array(
				'name'          => __( 'Документы', 'deti-baikala' ),
				'singular_name' => __( 'Документ', 'deti-baikala' ),
				'add_new_item'  => __( 'Добавить документ', 'deti-baikala' ),
				'edit_item'     => __( 'Редактировать документ', 'deti-baikala' ),
				'all_items'     => __( 'Документы и сертификаты', 'deti-baikala' ),
				'menu_name'     => __( 'Документы', 'deti-baikala' ),
			),
			'public'             => false,
			'publicly_queryable' => false,
			'show_ui'            => true,
			'show_in_menu'       => true,
			'has_archive'        => false,
			'menu_icon'          => 'dashicons-media-default',
			'supports'           => array( 'title' ),
		)
	);

	// Анкеты кандидатов — Наставник
	register_post_type(
		'nastavnik_candidate',
		array(
			'labels' => array(
				'name'          => 'Анкеты кандидатов',
				'singular_name' => 'Анкета кандидата',
				'add_new_item'  => 'Добавить анкету',
				'edit_item'     => 'Просмотр анкеты',
				'all_items'     => 'Все анкеты',
				'menu_name'     => 'Анкеты — Наставник',
			),
			'public'             => false,
			'publicly_queryable' => false,
			'show_ui'            => true,
			'show_in_menu'       => true,
			'has_archive'        => false,
			'menu_icon'          => 'dashicons-id',
			'supports'           => array( 'title' ),
			'capability_type'    => 'post',
			'capabilities'       => array(
				'create_posts' => 'do_not_allow',
			),
			'map_meta_cap'       => true,
		)
	);

	// Партнёры
	register_post_type(
		'partner',
		array(
			'labels' => array(
				'name'          => __( 'Партнёры', 'deti-baikala' ),
				'singular_name' => __( 'Партнёр', 'deti-baikala' ),
				'add_new_item'  => __( 'Добавить партнёра', 'deti-baikala' ),
				'edit_item'     => __( 'Редактировать партнёра', 'deti-baikala' ),
				'all_items'     => __( 'Партнёры', 'deti-baikala' ),
				'menu_name'     => __( 'Партнёры', 'deti-baikala' ),
			),
			'public'             => false,
			'publicly_queryable' => false,
			'show_ui'            => true,
			'show_in_menu'       => true,
			'has_archive'        => false,
			'menu_icon'          => 'dashicons-businessman',
			'supports'           => array( 'title', 'thumbnail' ),
		)
	);
}
add_action( 'init', 'db_register_post_types' );

/**
 * Таксономия "Год отчёта" для CPT report_item.
 */
function db_register_taxonomies() {
	register_taxonomy(
		'report_year',
		'report_item',
		array(
			'labels' => array(
				'name'          => __( 'Год отчёта', 'deti-baikala' ),
				'singular_name' => __( 'Год', 'deti-baikala' ),
				'add_new_item'  => __( 'Добавить год', 'deti-baikala' ),
				'all_items'     => __( 'Все года', 'deti-baikala' ),
			),
			'hierarchical'      => false,
			'public'            => false,
			'show_ui'           => true,
			'show_admin_column' => true,
			'show_in_rest'      => true,
			'rewrite'           => false,
		)
	);
}
add_action( 'init', 'db_register_taxonomies' );
