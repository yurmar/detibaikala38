<?php
/**
 * CPT конкурса и работы, статусы работ, мета.
 *
 * @package KidsArtContest
 */

defined( 'ABSPATH' ) || exit;

class KAC_Post_Types {

	public static function init(): void {
		add_action( 'init', array( __CLASS__, 'register' ) );
		add_filter( 'use_block_editor_for_post_type', array( __CLASS__, 'disable_block_editor' ), 10, 2 );
	}

	/**
	 * Gutenberg для конкурса отключён (классический редактор + метабоксы).
	 */
	public static function disable_block_editor( bool $use, string $post_type ): bool {
		return 'kac_contest' === $post_type ? false : $use;
	}

	public static function register(): void {
		self::register_contest();
		self::register_work();
		self::register_statuses();
		self::register_meta();
	}

	private static function register_contest(): void {
		register_post_type(
			'kac_contest',
			array(
				'labels'          => array(
					'name'          => __( 'Конкурсы', 'kids-art-contest' ),
					'singular_name' => __( 'Конкурс', 'kids-art-contest' ),
					'add_new_item'  => __( 'Добавить конкурс', 'kids-art-contest' ),
					'edit_item'     => __( 'Редактировать конкурс', 'kids-art-contest' ),
					'all_items'     => __( 'Все конкурсы', 'kids-art-contest' ),
					'featured_image'        => __( 'Обложка конкурса', 'kids-art-contest' ),
					'set_featured_image'    => __( 'Задать обложку', 'kids-art-contest' ),
					'remove_featured_image' => __( 'Удалить обложку', 'kids-art-contest' ),
					'use_featured_image'    => __( 'Использовать как обложку', 'kids-art-contest' ),
				),
				'public'          => true,
				'show_in_menu'    => false, // Меню плагина — в admin/ (шаг 2).
				'show_in_rest'    => false,
				'has_archive'     => 'konkursy',
				'rewrite'         => array( 'slug' => 'konkurs', 'with_front' => false ),
				'supports'        => array( 'title', 'editor', 'thumbnail' ),
				'capability_type' => 'post',
				'capabilities'    => self::caps( 'kac_manage_contests' ),
				'map_meta_cap'    => true,
			)
		);
	}

	private static function register_work(): void {
		register_post_type(
			'kac_work',
			array(
				'labels'              => array(
					'name'          => __( 'Работы', 'kids-art-contest' ),
					'singular_name' => __( 'Работа', 'kids-art-contest' ),
					'edit_item'     => __( 'Работа', 'kids-art-contest' ),
				),
				'public'              => true,
				'exclude_from_search' => true,
				'show_in_menu'        => false,
				'show_in_rest'        => false, // Публичный REST — собственный /kac/v1.
				'has_archive'         => false,
				'hierarchical'        => false,
				'rewrite'             => array( 'slug' => 'rabota', 'with_front' => false ),
				'supports'            => array( 'title', 'editor', 'thumbnail' ),
				'capability_type'     => 'post',
				'capabilities'        => array_merge(
					self::caps( 'kac_edit_works' ),
					array(
						'create_posts'  => 'do_not_allow', // Работы создаются только формой.
						'delete_posts'  => 'kac_manage_contests',
						'delete_others_posts'    => 'kac_manage_contests',
						'delete_private_posts'   => 'kac_manage_contests',
						'delete_published_posts' => 'kac_manage_contests',
					)
				),
				'map_meta_cap'        => true,
			)
		);
	}

	/**
	 * Все примитивные права CPT сводятся к одному праву плагина.
	 */
	private static function caps( string $cap ): array {
		$keys = array(
			'edit_posts', 'edit_others_posts',
			'publish_posts', 'read_private_posts', 'delete_posts', 'delete_private_posts',
			'delete_published_posts', 'delete_others_posts', 'edit_private_posts',
			'edit_published_posts', 'create_posts',
		);
		$caps = array();
		foreach ( $keys as $key ) {
			$caps[ $key ] = $cap;
		}
		return $caps;
	}

	/**
	 * Статусы работ: на модерации / отклонена / дисквалифицирована.
	 * Одобренная работа — обычный publish.
	 */
	private static function register_statuses(): void {
		$common = array(
			'public'                    => false,
			'internal'                  => false,
			'protected'                 => true,
			'exclude_from_search'       => false, // Иначе post_status => 'any' не находит работы.
			'show_in_admin_all_list'    => true,
			'show_in_admin_status_list' => true,
		);

		register_post_status(
			'kac_pending',
			array_merge(
				$common,
				array(
					'label'       => __( 'На модерации', 'kids-art-contest' ),
					/* translators: %s: count */
					'label_count' => _n_noop( 'На модерации <span class="count">(%s)</span>', 'На модерации <span class="count">(%s)</span>', 'kids-art-contest' ),
				)
			)
		);
		register_post_status(
			'kac_rejected',
			array_merge(
				$common,
				array(
					'label'       => __( 'Отклонена', 'kids-art-contest' ),
					'label_count' => _n_noop( 'Отклонена <span class="count">(%s)</span>', 'Отклонено <span class="count">(%s)</span>', 'kids-art-contest' ),
				)
			)
		);
		register_post_status(
			'kac_disqualified',
			array_merge(
				$common,
				array(
					'label'       => __( 'Дисквалифицирована', 'kids-art-contest' ),
					'label_count' => _n_noop( 'Дисквалифицирована <span class="count">(%s)</span>', 'Дисквалифицировано <span class="count">(%s)</span>', 'kids-art-contest' ),
				)
			)
		);
	}

	/**
	 * Мета-поля (защищённые, наружу через REST не отдаются).
	 */
	private static function register_meta(): void {
		$int_keys = array(
			'_kac_submit_start', '_kac_submit_end', '_kac_vote_start', '_kac_vote_end',
			'_kac_min_age', '_kac_max_age', '_kac_prize_places', '_kac_max_works',
			'_kac_vote_limit', '_kac_results_published', '_kac_rules_file',
		);
		foreach ( $int_keys as $key ) {
			register_post_meta( 'kac_contest', $key, array( 'type' => 'integer', 'single' => true, 'sanitize_callback' => 'absint', 'show_in_rest' => false ) );
		}

		$bool_keys = array( '_kac_vote_with_submit', '_kac_show_leaders' );
		foreach ( $bool_keys as $key ) {
			register_post_meta( 'kac_contest', $key, array( 'type' => 'boolean', 'single' => true, 'sanitize_callback' => 'rest_sanitize_boolean', 'show_in_rest' => false ) );
		}

		$enums = array(
			'_kac_vote_mode'    => array( 'per_work', 'per_contest', 'per_group', 'n_per_contest' ),
			'_kac_show_votes'   => array( 'always', 'after_end', 'never' ),
			'_kac_results_mode' => array( 'auto', 'manual' ),
		);
		foreach ( $enums as $key => $allowed ) {
			register_post_meta(
				'kac_contest',
				$key,
				array(
					'type'              => 'string',
					'single'            => true,
					'show_in_rest'      => false,
					'sanitize_callback' => static function ( $value ) use ( $allowed ) {
						return in_array( $value, $allowed, true ) ? $value : $allowed[0];
					},
				)
			);
		}

		foreach ( array( '_kac_ticket_prefix', '_kac_age_groups', '_kac_upload_rules', '_kac_results' ) as $key ) {
			register_post_meta( 'kac_contest', $key, array( 'type' => 'string', 'single' => true, 'show_in_rest' => false ) );
		}

		$work_int = array( '_kac_number', '_kac_age', '_kac_votes', '_kac_resubmit_of' );
		foreach ( $work_int as $key ) {
			register_post_meta( 'kac_work', $key, array( 'type' => 'integer', 'single' => true, 'sanitize_callback' => 'absint', 'show_in_rest' => false ) );
		}
		foreach ( array( '_kac_group', '_kac_public_name', '_kac_technique', '_kac_dhash', '_kac_original_path', '_kac_reject_reason', '_kac_note' ) as $key ) {
			register_post_meta( 'kac_work', $key, array( 'type' => 'string', 'single' => true, 'sanitize_callback' => 'sanitize_text_field', 'show_in_rest' => false ) );
		}
		register_post_meta( 'kac_work', '_kac_allow_resubmit', array( 'type' => 'boolean', 'single' => true, 'sanitize_callback' => 'rest_sanitize_boolean', 'show_in_rest' => false ) );
	}
}
