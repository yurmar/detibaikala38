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
			$campaign = Leyka_Campaign::get_by_id( $post_id );
			if ( $campaign ) {
				if ( method_exists( $campaign, 'collected_amount' ) ) {
					$collected = (float) $campaign->collected_amount;
				}
				if ( method_exists( $campaign, 'target_amount' ) ) {
					$target = (float) $campaign->target_amount;
				}
			}
		} catch ( Exception $e ) {
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
