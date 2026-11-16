<?php
/**
 * Общие функции плагина.
 *
 * @package KidsArtContest
 */

defined( 'ABSPATH' ) || exit;

/**
 * Значения настроек по умолчанию (опция kac_settings).
 *
 * @return array
 */
function kac_default_settings(): array {
	return array(
		'delete_data_on_uninstall' => 0,
		// Голосование и защита.
		'use_fingerprint'       => 1,
		'ip_limit_enabled'      => 1,
		'vote_ip_daily_limit'   => 3,
		'captcha_suspicious'    => 1,
		'captcha_always'        => 0,
		'captcha_provider'      => 'builtin',
		'captcha_site_key'      => '',
		'captcha_submit'        => 0,
		'risk_threshold'        => 60,
		'spike_threshold'       => 30,
		'trust_proxy'           => 0,
		'alert_emails'          => '',
		'tie_mode'              => 'earlier_reached',
		// Файлы.
		'upload_max_bytes'      => 2 * 1024 * 1024,
		'upload_min_w'          => 1080,
		'upload_min_h'          => 1080,
		'upload_max_w'          => 1920,
		'upload_max_h'          => 1920,
		'upload_formats'        => array( 'jpg', 'png', 'webp' ),
		'client_shrink'         => 1,
		'strip_exif'            => 1,
		'check_duplicates'      => 1,
		// Форма: hidden | optional | required.
		'f_child_middle'        => 'optional',
		'f_city'                => 'required',
		'f_school'              => 'optional',
		'f_teacher'             => 'optional',
		'f_technique'           => 'optional',
		'f_description'         => 'optional',
		'public_name_format'    => '{first} {last_initial}.',
		'relations'             => "Мама\nПапа\nОпекун / попечитель\nДругой законный представитель",
		// Письма.
		'moderator_notify'      => 'instant',
		'mail_received_enabled' => 1,
		'mail_approved_enabled' => 1,
		'mail_rejected_enabled' => 1,
		'mail_winner_enabled'   => 1,
		'mail_results_all'      => 0,
		'mail_spike_enabled'    => 1,
		'reject_reasons'        => "Плохое качество фото (размыто, обрезано, блики)\nРисунок не соответствует теме конкурса\nРабота выполнена не ребёнком / скопирована\nВозраст не соответствует условиям\nНедопустимое содержание",
		// Персональные данные.
		'consent_version'       => 1,
		'consent_personal_text' => 'Я законный представитель ребёнка и даю согласие на обработку персональных данных — моих и ребёнка — в соответствии с Политикой конфиденциальности (152‑ФЗ).',
		'consent_publish_text'  => 'Согласен(на) на публикацию рисунка на сайте с указанием имени, первой буквы фамилии и возраста автора.',
		'consent_rules_text'    => 'Подтверждаю, что рисунок выполнен ребёнком самостоятельно, и принимаю Правила конкурса.',
		'privacy_page_id'       => 0,
		'retention_pd_days'     => 180,
		'retention_ip_days'     => 90,
		// Внешний вид.
		'accent_color'          => '#ff6b5b',
		'ok_color'              => '#22b88f',
		'columns'               => 3,
		'per_page'              => 12,
		'font'                  => 'theme',
		'vote_button_text'      => 'Проголосовать за работу',
	);
}

/**
 * Читает значение из настроек плагина.
 *
 * @param string $key     Ключ.
 * @param mixed  $default Значение, если ключа нет и в умолчаниях.
 * @return mixed
 */
function kac_setting( string $key, $default = null ) {
	$saved = get_option( 'kac_settings', array() ); // get_option кеширует сам.
	$all   = array_merge( kac_default_settings(), is_array( $saved ) ? $saved : array() );
	return array_key_exists( $key, $all ) ? $all[ $key ] : $default;
}

/**
 * Фаза конкурса — единственный источник правды.
 *
 * @param int $contest_id ID конкурса.
 * @return string draft|upcoming|submission|voting|finished|results
 */
function kac_get_phase( int $contest_id ): string {
	return KAC_Phase::get( $contest_id );
}

/**
 * Публичное имя участника: «Анна И.».
 *
 * @param string $first Имя.
 * @param string $last  Фамилия.
 * @return string
 */
function kac_public_name( string $first, string $last ): string {
	$first = trim( $first );
	$last  = trim( $last );
	$initial = '' !== $last ? mb_strtoupper( mb_substr( $last, 0, 1 ) ) : '';
	$name  = strtr(
		(string) kac_setting( 'public_name_format' ),
		array(
			'{first}'        => $first,
			'{last}'         => $last,
			'{last_initial}' => $initial,
		)
	);
	$name = trim( preg_replace( '/\s+/u', ' ', $name ) );
	$name = str_replace( ' .', '', $name );
	return (string) apply_filters( 'kac_public_name', $name, $first, $last );
}

/**
 * Список всех прав плагина.
 *
 * @return string[]
 */
function kac_all_caps(): array {
	return array(
		'kac_manage_contests',
		'kac_moderate_works',
		'kac_edit_works',
		'kac_view_personal_data',
		'kac_view_votes',
		'kac_annul_votes',
		'kac_publish_results',
		'kac_export',
		'kac_manage_settings',
	);
}

/**
 * Права роли «Модератор конкурса» по умолчанию.
 *
 * @return string[]
 */
function kac_moderator_default_caps(): array {
	return array(
		'kac_moderate_works',
		'kac_edit_works',
		'kac_view_personal_data',
		'kac_view_votes',
	);
}

/**
 * Подключает шаблон: сначала из темы ({theme}/kids-art-contest/), затем из плагина.
 */
function kac_locate_template( string $name ): string {
	$theme = locate_template( array( 'kids-art-contest/' . $name . '.php' ) );
	$path  = $theme ? $theme : KAC_DIR . 'public/templates/' . $name . '.php';
	return (string) apply_filters( 'kac_template_path', $path, $name );
}

function kac_template( string $name, array $vars = array() ): void {
	$file = kac_locate_template( $name );
	if ( ! is_readable( $file ) ) {
		return;
	}
	extract( $vars, EXTR_SKIP ); // phpcs:ignore WordPress.PHP.DontExtract
	include $file;
}

function kac_work_url( int $work_id ): string {
	return (string) get_permalink( $work_id );
}

/**
 * Дата/время в часовом поясе сайта.
 */
function kac_date( int $ts, string $format = 'j F Y, H:i' ): string {
	return $ts ? wp_date( $format, $ts ) : '—';
}

/**
 * Сбрасывает кеш страницы конкурса в популярных плагинах кеширования.
 */
function kac_purge_page_cache( int $post_id ): void {
	if ( function_exists( 'rocket_clean_post' ) ) {
		rocket_clean_post( $post_id );
	}
	if ( function_exists( 'w3tc_flush_post' ) ) {
		w3tc_flush_post( $post_id );
	}
	if ( function_exists( 'wp_cache_post_change' ) ) {
		wp_cache_post_change( $post_id );
	}
	if ( has_action( 'litespeed_purge_post' ) ) {
		do_action( 'litespeed_purge_post', $post_id );
	}
	clean_post_cache( $post_id );
	do_action( 'kac_purge_cache', $post_id );
}

/**
 * Ссылка на политику конфиденциальности: выбранная в настройках страница или страница политики WordPress.
 */
function kac_privacy_url(): string {
	$id = (int) kac_setting( 'privacy_page_id' );
	if ( $id && 'publish' === get_post_status( $id ) ) {
		return (string) get_permalink( $id );
	}
	return (string) get_privacy_policy_url();
}
