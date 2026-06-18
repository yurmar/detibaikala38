<?php
/**
 * Дети Байкала — безопасные обёртки для интеграции с плагином «Лейка».
 *
 * Все функции проверяют наличие плагина (function_exists/class_exists) и
 * откатываются на post meta, чтобы тема не ломалась без активного плагина.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Получить последние N кампаний.
 *
 * @param int $n
 * @return WP_Query
 */
function db_get_campaigns( $n = 3 ) {
	return new WP_Query(
		array(
			'post_type'      => 'leyka_campaign',
			'posts_per_page' => $n,
			'post_status'    => 'publish',
			'orderby'        => 'date',
			'order'          => 'DESC',
		)
	);
}

/**
 * Получить данные о прогрессе сбора кампании.
 *
 * @param int $post_id
 * @return array { collected, target, percent, collected_text, target_text }
 */
function db_campaign_progress( $post_id ) {
	$collected = 0;
	$target    = 0;

	if ( class_exists( 'Leyka_Campaign' ) ) {
		try {
			$campaign = new Leyka_Campaign( $post_id );
			if ( $campaign && $campaign->id ) {
				$collected = (float) $campaign->total_funded;
				$target    = (float) $campaign->target;
			}
		} catch ( Throwable $e ) {
			$collected = 0;
			$target    = 0;
		}
	}

	// Фолбэк на post meta, если данные Лейки недоступны.
	if ( ! $collected ) {
		$collected = (float) get_post_meta( $post_id, '_leyka_collected_amount', true );
	}
	if ( ! $target ) {
		$target = (float) get_post_meta( $post_id, '_leyka_target_amount', true );
	}

	$percent = $target > 0 ? min( 100, round( ( $collected / $target ) * 100 ) ) : 0;

	return array(
		'collected'      => $collected,
		'target'         => $target,
		'percent'        => $percent,
		'collected_text' => number_format_i18n( $collected, 0 ) . ' ₽',
		'target_text'    => number_format_i18n( $target, 0 ) . ' ₽',
	);
}

/**
 * Категория (тег сбора) кампании — для бейджа карточки на главной.
 */
function db_campaign_tag( $post_id ) {
	$terms = get_the_terms( $post_id, 'leyka_campaign_type' );
	if ( $terms && ! is_wp_error( $terms ) ) {
		return $terms[0]->name;
	}
	return __( 'Сбор', 'deti-baikala' );
}

/**
 * Проверка активности плагина «Лейка».
 */
function db_leyka_active() {
	return post_type_exists( 'leyka_campaign' );
}

/**
 * Мета-бокс выбора кампании для страниц с формой пожертвования.
 */
add_action( 'add_meta_boxes', function () {
	if ( ! db_leyka_active() ) {
		return;
	}
	add_meta_box(
		'db_donate_campaign',
		__( 'Кампания для пожертвования', 'deti-baikala' ),
		'db_render_campaign_metabox',
		'page',
		'side',
		'default'
	);
} );

function db_render_campaign_metabox( $post ) {
	wp_nonce_field( 'db_save_donate_campaign', 'db_donate_campaign_nonce' );

	$saved = get_post_meta( $post->ID, '_db_donate_campaign_id', true );

	$campaigns = get_posts( array(
		'post_type'      => 'leyka_campaign',
		'post_status'    => 'publish',
		'posts_per_page' => -1,
		'orderby'        => 'title',
		'order'          => 'ASC',
	) );

	echo '<select name="_db_donate_campaign_id" id="_db_donate_campaign_id" style="width:100%">';
	echo '<option value="">' . esc_html__( '— не выбрана —', 'deti-baikala' ) . '</option>';
	foreach ( $campaigns as $campaign ) {
		echo '<option value="' . esc_attr( $campaign->ID ) . '" ' . selected( $saved, $campaign->ID, false ) . '>' . esc_html( $campaign->post_title ) . '</option>';
	}
	echo '</select>';
	echo '<p class="description" style="margin-top:6px">' . esc_html__( 'Выберите кампанию, форма которой отобразится на этой странице.', 'deti-baikala' ) . '</p>';
}

add_action( 'save_post_page', function ( $post_id ) {
	if ( ! isset( $_POST['db_donate_campaign_nonce'] ) ) {
		return;
	}
	if ( ! wp_verify_nonce( $_POST['db_donate_campaign_nonce'], 'db_save_donate_campaign' ) ) {
		return;
	}
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	$campaign_id = isset( $_POST['_db_donate_campaign_id'] ) ? absint( $_POST['_db_donate_campaign_id'] ) : 0;

	if ( $campaign_id ) {
		update_post_meta( $post_id, '_db_donate_campaign_id', $campaign_id );
	} else {
		delete_post_meta( $post_id, '_db_donate_campaign_id' );
	}
} );
