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
			'key'         => '_team_avatar_emoji',
			'label'       => __( 'Эмодзи-плейсхолдер', 'deti-baikala' ),
			'type'        => 'emoji',
			'default'     => '🧑',
			'description' => __( 'Используется, если фото не загружено.', 'deti-baikala' ),
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
