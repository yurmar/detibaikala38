<?php
/**
 * Дети Байкала — универсальный механизм метабоксов (без ACF).
 *
 * db_register_metabox( $post_type, $box_id, $box_title, $fields )
 * $fields — массив описаний полей:
 *   [
 *     'key'     => '_project_badge',
 *     'label'   => 'Бейдж',
 *     'type'    => 'text' | 'textarea' | 'number' | 'media' | 'select' | 'emoji',
 *     'default' => '',
 *     'options' => [ 'val' => 'Label', ... ], // для select
 *     'min'/'max' => для number,
 *   ]
 */

if ( ! defined( 'ABSPATH' ) ) exit;

function db_register_metabox( $post_type, $box_id, $box_title, $fields ) {
	add_action(
		'add_meta_boxes',
		function () use ( $post_type, $box_id, $box_title, $fields ) {
			add_meta_box(
				$box_id,
				$box_title,
				function ( $post ) use ( $fields, $box_id ) {
					wp_nonce_field( 'db_save_' . $box_id, 'db_' . $box_id . '_nonce' );
					echo '<table class="form-table">';
					foreach ( $fields as $field ) {
						db_render_metabox_field( $post, $field );
					}
					echo '</table>';
				},
				$post_type,
				'normal',
				'default'
			);
		}
	);

	add_action(
		'save_post_' . $post_type,
		function ( $post_id ) use ( $box_id, $fields ) {
			if ( ! isset( $_POST[ 'db_' . $box_id . '_nonce' ] ) ) {
				return;
			}
			if ( ! wp_verify_nonce( $_POST[ 'db_' . $box_id . '_nonce' ], 'db_save_' . $box_id ) ) {
				return;
			}
			if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
				return;
			}
			if ( ! current_user_can( 'edit_post', $post_id ) ) {
				return;
			}

			foreach ( $fields as $field ) {
				db_save_metabox_field( $post_id, $field );
			}
		}
	);
}

function db_render_metabox_field( $post, $field ) {
	$key     = $field['key'];
	$type    = $field['type'];
	$label   = $field['label'];
	$value   = get_post_meta( $post->ID, $key, true );
	$default = isset( $field['default'] ) ? $field['default'] : '';
	if ( '' === $value && '' !== $default ) {
		$value = $default;
	}

	echo '<tr><th scope="row"><label for="' . esc_attr( $key ) . '">' . esc_html( $label ) . '</label></th><td>';

	switch ( $type ) {
		case 'textarea':
			echo '<textarea id="' . esc_attr( $key ) . '" name="' . esc_attr( $key ) . '" rows="4" class="large-text">' . esc_textarea( $value ) . '</textarea>';
			break;

		case 'number':
			$min = isset( $field['min'] ) ? ' min="' . esc_attr( $field['min'] ) . '"' : '';
			$max = isset( $field['max'] ) ? ' max="' . esc_attr( $field['max'] ) . '"' : '';
			echo '<input type="number" id="' . esc_attr( $key ) . '" name="' . esc_attr( $key ) . '" value="' . esc_attr( $value ) . '"' . $min . $max . ' class="small-text" />';
			break;

		case 'select':
			echo '<select id="' . esc_attr( $key ) . '" name="' . esc_attr( $key ) . '">';
			foreach ( $field['options'] as $val => $text ) {
				echo '<option value="' . esc_attr( $val ) . '" ' . selected( $value, $val, false ) . '>' . esc_html( $text ) . '</option>';
			}
			echo '</select>';
			break;

		case 'media':
			$img_url = $value ? wp_get_attachment_url( $value ) : '';
			echo '<div class="db-media-field">';
			echo '<input type="hidden" class="db-media-field__id" id="' . esc_attr( $key ) . '" name="' . esc_attr( $key ) . '" value="' . esc_attr( $value ) . '" />';
			echo '<div class="db-media-field__preview" style="margin-bottom:8px;">';
			if ( $img_url ) {
				echo '<img src="' . esc_url( $img_url ) . '" style="max-width:150px;height:auto;display:block;" />';
			}
			echo '</div>';
			echo '<button type="button" class="button db-media-field__select">' . esc_html__( 'Выбрать файл', 'deti-baikala' ) . '</button> ';
			echo '<button type="button" class="button db-media-field__remove">' . esc_html__( 'Удалить', 'deti-baikala' ) . '</button>';
			echo '</div>';
			break;

		case 'emoji':
			echo '<input type="text" id="' . esc_attr( $key ) . '" name="' . esc_attr( $key ) . '" value="' . esc_attr( $value ) . '" class="small-text" maxlength="8" />';
			echo '<p class="description">' . esc_html__( 'Можно указать эмодзи, символ или короткий код.', 'deti-baikala' ) . '</p>';
			break;

		default: // text
			echo '<input type="text" id="' . esc_attr( $key ) . '" name="' . esc_attr( $key ) . '" value="' . esc_attr( $value ) . '" class="regular-text" />';
			break;
	}

	if ( ! empty( $field['description'] ) && 'emoji' !== $type ) {
		echo '<p class="description">' . esc_html( $field['description'] ) . '</p>';
	}

	echo '</td></tr>';
}

function db_save_metabox_field( $post_id, $field ) {
	$key  = $field['key'];
	$type = $field['type'];

	if ( ! isset( $_POST[ $key ] ) ) {
		if ( 'number' === $type || 'media' === $type ) {
			delete_post_meta( $post_id, $key );
		}
		return;
	}

	$raw = wp_unslash( $_POST[ $key ] );

	switch ( $type ) {
		case 'textarea':
			$value = sanitize_textarea_field( $raw );
			break;
		case 'number':
			$value = is_numeric( $raw ) ? floatval( $raw ) : '';
			break;
		case 'media':
			$value = absint( $raw );
			break;
		case 'select':
			$value = sanitize_text_field( $raw );
			break;
		case 'emoji':
			$value = sanitize_text_field( $raw );
			break;
		default:
			$value = sanitize_text_field( $raw );
			break;
	}

	if ( '' === $value || null === $value ) {
		delete_post_meta( $post_id, $key );
	} else {
		update_post_meta( $post_id, $key, $value );
	}
}

/**
 * Подключение медиа-загрузчика WP для полей типа "media".
 */
function db_metabox_admin_assets( $hook ) {
	if ( 'post.php' !== $hook && 'post-new.php' !== $hook ) {
		return;
	}
	wp_enqueue_media();
	wp_add_inline_script(
		'jquery-core',
		"jQuery(function($){
			$(document).on('click', '.db-media-field__select', function(e){
				e.preventDefault();
				var wrap = $(this).closest('.db-media-field');
				var frame = wp.media({ title: '" . esc_js( __( 'Выберите файл', 'deti-baikala' ) ) . "', multiple: false });
				frame.on('select', function(){
					var att = frame.state().get('selection').first().toJSON();
					wrap.find('.db-media-field__id').val(att.id);
					var url = att.sizes && att.sizes.thumbnail ? att.sizes.thumbnail.url : att.url;
					wrap.find('.db-media-field__preview').html('<img src=\"' + url + '\" style=\"max-width:150px;height:auto;display:block;\" />');
				});
				frame.open();
			});
			$(document).on('click', '.db-media-field__remove', function(e){
				e.preventDefault();
				var wrap = $(this).closest('.db-media-field');
				wrap.find('.db-media-field__id').val('');
				wrap.find('.db-media-field__preview').html('');
			});
		});"
	);
}
add_action( 'admin_enqueue_scripts', 'db_metabox_admin_assets' );

/* ------------------------------------------------------------------ */
/* Регистрация полей для каждого CPT                                    */
/* ------------------------------------------------------------------ */

// Проекты
db_register_metabox(
	'project',
	'db_project_fields',
	__( 'Параметры проекта', 'deti-baikala' ),
	array(
		array(
			'key'         => '_project_badge',
			'label'       => __( 'Бейдж', 'deti-baikala' ),
			'type'        => 'text',
			'description' => __( 'Например: Флагман, Активный, Образование, Партнёрский, Помощь, Сезонный', 'deti-baikala' ),
		),
		array(
			'key'         => '_project_stat',
			'label'       => __( 'Показатель (нижняя строка карточки)', 'deti-baikala' ),
			'type'        => 'text',
			'description' => __( 'Например: 658 детей нашли семью', 'deti-baikala' ),
		),
	)
);

// Отзывы
db_register_metabox(
	'review_item',
	'db_review_fields',
	__( 'Параметры отзыва', 'deti-baikala' ),
	array(
		array(
			'key'     => '_review_rating',
			'label'   => __( 'Оценка (1–5)', 'deti-baikala' ),
			'type'    => 'number',
			'min'     => 1,
			'max'     => 5,
			'default' => 5,
		),
		array(
			'key'         => '_review_role',
			'label'       => __( 'Должность / роль', 'deti-baikala' ),
			'type'        => 'text',
			'description' => __( 'Например: Приёмные родители, г. Иркутск', 'deti-baikala' ),
		),
	)
);

// Отчёты
db_register_metabox(
	'report_item',
	'db_report_fields',
	__( 'Параметры отчёта', 'deti-baikala' ),
	array(
		array(
			'key'     => '_report_icon',
			'label'   => __( 'Иконка (эмодзи)', 'deti-baikala' ),
			'type'    => 'emoji',
			'default' => '📊',
		),
		array(
			'key'         => '_report_date',
			'label'       => __( 'Дата публикации', 'deti-baikala' ),
			'type'        => 'text',
			'description' => __( 'Например: Опубликован: 15 марта 2024', 'deti-baikala' ),
		),
		array(
			'key'   => '_report_file',
			'label' => __( 'Файл отчёта (PDF)', 'deti-baikala' ),
			'type'  => 'media',
		),
	)
);

// Команда
db_register_metabox(
	'team_member',
	'db_team_fields',
	__( 'Параметры сотрудника', 'deti-baikala' ),
	array(
		array(
			'key'         => '_team_role',
			'label'       => __( 'Должность', 'deti-baikala' ),
			'type'        => 'text',
			'description' => __( 'Например: Директор фонда', 'deti-baikala' ),
		),
		array(
			'key'         => '_team_description',
			'label'       => __( 'Описание', 'deti-baikala' ),
			'type'        => 'textarea',
			'description' => __( 'Краткая биография или описание сотрудника. Отображается в модальном окне.', 'deti-baikala' ),
		),
	)
);

// Документы
db_register_metabox(
	'fund_document',
	'db_document_fields',
	__( 'Параметры документа', 'deti-baikala' ),
	array(
		array(
			'key'     => '_doc_icon',
			'label'   => __( 'Иконка (эмодзи)', 'deti-baikala' ),
			'type'    => 'emoji',
			'default' => '📄',
		),
		array(
			'key'         => '_doc_file',
			'label'       => __( 'Файл документа', 'deti-baikala' ),
			'type'        => 'media',
			'description' => __( 'PDF или изображение.', 'deti-baikala' ),
		),
	)
);

// Партнёры
db_register_metabox(
	'partner',
	'db_partner_fields',
	__( 'Параметры партнёра', 'deti-baikala' ),
	array(
		array(
			'key'         => '_partner_url',
			'label'       => __( 'Ссылка на сайт партнёра', 'deti-baikala' ),
			'type'        => 'text',
			'description' => __( 'Необязательно. Логотип берётся из миниатюры записи.', 'deti-baikala' ),
		),
	)
);

/* ------------------------------------------------------------------ */
/* Метабоксы для страниц с определёнными шаблонами                     */
/* ------------------------------------------------------------------ */

/**
 * Регистрирует метабокс только для страниц с нужным шаблоном.
 */
function db_register_page_template_metabox( $template_file, $box_id, $box_title, $fields ) {
	// Показываем метабокс только на нужном шаблоне.
	add_action(
		'add_meta_boxes',
		function ( $post_type, $post ) use ( $template_file, $box_id, $box_title, $fields ) {
			if ( 'page' !== $post_type ) {
				return;
			}
			if ( get_post_meta( $post->ID, '_wp_page_template', true ) !== $template_file ) {
				return;
			}
			add_meta_box(
				$box_id,
				$box_title,
				function ( $post ) use ( $fields, $box_id ) {
					wp_nonce_field( 'db_save_' . $box_id, 'db_' . $box_id . '_nonce' );
					echo '<table class="form-table">';
					foreach ( $fields as $field ) {
						db_render_metabox_field( $post, $field );
					}
					echo '</table>';
				},
				'page',
				'normal',
				'default'
			);
		},
		10,
		2
	);

	// Сохраняем только если шаблон совпадает.
	add_action(
		'save_post_page',
		function ( $post_id ) use ( $template_file, $box_id, $fields ) {
			if ( ! isset( $_POST[ 'db_' . $box_id . '_nonce' ] ) ) {
				return;
			}
			if ( ! wp_verify_nonce( $_POST[ 'db_' . $box_id . '_nonce' ], 'db_save_' . $box_id ) ) {
				return;
			}
			if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
				return;
			}
			if ( ! current_user_can( 'edit_post', $post_id ) ) {
				return;
			}
			if ( get_post_meta( $post_id, '_wp_page_template', true ) !== $template_file ) {
				return;
			}
			foreach ( $fields as $field ) {
				db_save_metabox_field( $post_id, $field );
			}
		}
	);
}

// Страница «Наставник»
db_register_page_template_metabox(
	'page-nastavnik.php',
	'db_nastavnik_fields',
	__( 'Содержимое страницы «Наставник»', 'deti-baikala' ),
	array(

		// --- Вступление ---
		array(
			'key'         => '_ns_intro_img',
			'label'       => __( 'Вступление: изображение', 'deti-baikala' ),
			'type'        => 'media',
			'description' => __( 'Картинка слева (широкий баннер, горизонтальное фото).', 'deti-baikala' ),
		),
		array(
			'key'   => '_ns_intro_text',
			'label' => __( 'Вступление: текст', 'deti-baikala' ),
			'type'  => 'textarea',
		),

		// --- Баннер 1 ---
		array(
			'key'         => '_ns_banner1_bg',
			'label'       => __( 'Баннер 1: фоновое изображение', 'deti-baikala' ),
			'type'        => 'media',
			'description' => __( 'Затемняется автоматически.', 'deti-baikala' ),
		),
		array(
			'key'   => '_ns_banner1_title',
			'label' => __( 'Баннер 1: заголовок', 'deti-baikala' ),
			'type'  => 'textarea',
		),
		array(
			'key'   => '_ns_banner1_btn_text',
			'label' => __( 'Баннер 1: текст кнопки', 'deti-baikala' ),
			'type'  => 'text',
		),
		array(
			'key'   => '_ns_banner1_btn_url',
			'label' => __( 'Баннер 1: ссылка кнопки', 'deti-baikala' ),
			'type'  => 'text',
		),

		// --- Особенности проекта ---
		array(
			'key'         => '_ns_features_label',
			'label'       => __( 'Особенности: метка (маленькая)', 'deti-baikala' ),
			'type'        => 'text',
			'description' => __( 'Например: «Что такое проект».', 'deti-baikala' ),
		),
		array(
			'key'   => '_ns_features_title',
			'label' => __( 'Особенности: заголовок раздела', 'deti-baikala' ),
			'type'  => 'text',
		),
		array( 'key' => '_ns_feat1_icon',  'label' => __( 'Особенность 1: иконка (эмодзи)', 'deti-baikala' ), 'type' => 'emoji' ),
		array( 'key' => '_ns_feat1_title', 'label' => __( 'Особенность 1: заголовок', 'deti-baikala' ),       'type' => 'text' ),
		array( 'key' => '_ns_feat1_text',  'label' => __( 'Особенность 1: текст', 'deti-baikala' ),            'type' => 'textarea' ),
		array( 'key' => '_ns_feat2_icon',  'label' => __( 'Особенность 2: иконка (эмодзи)', 'deti-baikala' ), 'type' => 'emoji' ),
		array( 'key' => '_ns_feat2_title', 'label' => __( 'Особенность 2: заголовок', 'deti-baikala' ),       'type' => 'text' ),
		array( 'key' => '_ns_feat2_text',  'label' => __( 'Особенность 2: текст', 'deti-baikala' ),            'type' => 'textarea' ),
		array( 'key' => '_ns_feat3_icon',  'label' => __( 'Особенность 3: иконка (эмодзи)', 'deti-baikala' ), 'type' => 'emoji' ),
		array( 'key' => '_ns_feat3_title', 'label' => __( 'Особенность 3: заголовок', 'deti-baikala' ),       'type' => 'text' ),
		array( 'key' => '_ns_feat3_text',  'label' => __( 'Особенность 3: текст', 'deti-baikala' ),            'type' => 'textarea' ),
		array( 'key' => '_ns_feat4_icon',  'label' => __( 'Особенность 4: иконка (эмодзи)', 'deti-baikala' ), 'type' => 'emoji' ),
		array( 'key' => '_ns_feat4_title', 'label' => __( 'Особенность 4: заголовок', 'deti-baikala' ),       'type' => 'text' ),
		array( 'key' => '_ns_feat4_text',  'label' => __( 'Особенность 4: текст', 'deti-baikala' ),            'type' => 'textarea' ),
		array(
			'key'   => '_ns_features_btn_text',
			'label' => __( 'Особенности: текст кнопки', 'deti-baikala' ),
			'type'  => 'text',
		),
		array(
			'key'   => '_ns_features_btn_url',
			'label' => __( 'Особенности: ссылка кнопки', 'deti-baikala' ),
			'type'  => 'text',
		),

		// --- Баннер 2 (результаты) ---
		array(
			'key'         => '_ns_results_bg',
			'label'       => __( 'Баннер 2: фоновое изображение', 'deti-baikala' ),
			'type'        => 'media',
			'description' => __( 'Затемняется автоматически.', 'deti-baikala' ),
		),
		array(
			'key'         => '_ns_results_text',
			'label'       => __( 'Баннер 2: текст (цитата / результат)', 'deti-baikala' ),
			'type'        => 'textarea',
		),

		// --- Как это работает ---
		array(
			'key'         => '_ns_steps_label',
			'label'       => __( 'Шаги: метка (маленькая)', 'deti-baikala' ),
			'type'        => 'text',
			'description' => __( 'Например: «Как это работает».', 'deti-baikala' ),
		),
		array(
			'key'   => '_ns_steps_title',
			'label' => __( 'Шаги: заголовок раздела', 'deti-baikala' ),
			'type'  => 'text',
		),
		array( 'key' => '_ns_step1', 'label' => __( 'Шаг 1', 'deti-baikala' ), 'type' => 'textarea' ),
		array( 'key' => '_ns_step2', 'label' => __( 'Шаг 2', 'deti-baikala' ), 'type' => 'textarea' ),
		array( 'key' => '_ns_step3', 'label' => __( 'Шаг 3', 'deti-baikala' ), 'type' => 'textarea' ),
		array( 'key' => '_ns_step4', 'label' => __( 'Шаг 4', 'deti-baikala' ), 'type' => 'textarea' ),
		array( 'key' => '_ns_step5', 'label' => __( 'Шаг 5', 'deti-baikala' ), 'type' => 'textarea' ),
		array(
			'key'   => '_ns_steps_btn_text',
			'label' => __( 'Шаги: текст кнопки', 'deti-baikala' ),
			'type'  => 'text',
		),
		array(
			'key'   => '_ns_steps_btn_url',
			'label' => __( 'Шаги: ссылка кнопки', 'deti-baikala' ),
			'type'  => 'text',
		),

		// --- Финальный баннер ---
		array(
			'key'         => '_ns_final_bg',
			'label'       => __( 'Финальный баннер: фоновое изображение', 'deti-baikala' ),
			'type'        => 'media',
			'description' => __( 'Затемняется автоматически.', 'deti-baikala' ),
		),
		array(
			'key'   => '_ns_final_text',
			'label' => __( 'Финальный баннер: текст', 'deti-baikala' ),
			'type'  => 'textarea',
		),
	)
);
