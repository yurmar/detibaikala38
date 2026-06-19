<?php
/**
 * Дети Байкала — Customizer.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

function db_customize_register( $wp_customize ) {

	$wp_customize->add_panel(
		'db_theme_options',
		array(
			'title'    => __( 'Дети Байкала — настройки', 'deti-baikala' ),
			'priority' => 30,
		)
	);

	/* ---------------------------- HERO ---------------------------- */
	$wp_customize->add_section(
		'db_hero',
		array(
			'title' => __( 'Главный экран (Hero)', 'deti-baikala' ),
			'panel' => 'db_theme_options',
		)
	);

	db_add_setting( $wp_customize, 'db_hero_title', __( 'Заголовок', 'deti-baikala' ), 'textarea', 'db_hero', 'Лучший способ сделать детей хорошими —' );
	db_add_setting( $wp_customize, 'db_hero_title_accent', __( 'Акцентная часть заголовка', 'deti-baikala' ), 'text', 'db_hero', 'это сделать их счастливыми' );
	db_add_setting( $wp_customize, 'db_hero_text', __( 'Текст под заголовком', 'deti-baikala' ), 'textarea', 'db_hero', 'Благотворительный фонд «Дети Байкала» помогает детям-сиротам, семьям с детьми-инвалидами и многодетным семьям Иркутской области с 2012 года.' );
	db_add_setting( $wp_customize, 'db_hero_bg', __( 'Фоновое изображение', 'deti-baikala' ), 'image', 'db_hero', '' );
	db_add_setting( $wp_customize, 'db_hero_btn1_text', __( 'Кнопка 1 — текст', 'deti-baikala' ), 'text', 'db_hero', 'Помочь сейчас' );
	db_add_setting( $wp_customize, 'db_hero_btn1_url', __( 'Кнопка 1 — ссылка', 'deti-baikala' ), 'url', 'db_hero', '#donate' );
	db_add_setting( $wp_customize, 'db_hero_btn2_text', __( 'Кнопка 2 — текст', 'deti-baikala' ), 'text', 'db_hero', 'Наши проекты' );
	db_add_setting( $wp_customize, 'db_hero_btn2_url', __( 'Кнопка 2 — ссылка', 'deti-baikala' ), 'url', 'db_hero', '' );

	db_add_setting( $wp_customize, 'db_rutube_title', __( 'Rutube блок — заголовок', 'deti-baikala' ), 'text', 'db_hero', 'Rutube — видеоканал фонда' );
	db_add_setting( $wp_customize, 'db_rutube_subtitle', __( 'Rutube блок — подпись', 'deti-baikala' ), 'text', 'db_hero', 'Смотреть на' );
	db_add_setting( $wp_customize, 'db_rutube_url', __( 'Rutube блок — ссылка', 'deti-baikala' ), 'url', 'db_hero', '' );

	/* ---------------------------- STATS ---------------------------- */
	$wp_customize->add_section(
		'db_stats',
		array(
			'title' => __( 'Статистика на главной', 'deti-baikala' ),
			'panel' => 'db_theme_options',
		)
	);

	db_add_setting( $wp_customize, 'db_stats_label', __( 'Надпись-метка', 'deti-baikala' ), 'text', 'db_stats', 'Наши результаты' );
	db_add_setting( $wp_customize, 'db_stats_title', __( 'Заголовок секции', 'deti-baikala' ), 'text', 'db_stats', 'Цифры, которые говорят сами за себя' );
	db_add_setting( $wp_customize, 'db_stats_text', __( 'Текст под заголовком', 'deti-baikala' ), 'textarea', 'db_stats', 'Каждое число — это изменённая жизнь. Вместе мы делаем больше.' );

	$stats_defaults = array(
		1 => array( 'num' => '1 855', 'label' => "Видеоанкет снято\nдля детей, ищущих семью" ),
		2 => array( 'num' => '658', 'label' => "Детей нашли родителей по проекту\n«Подари ребёнку семью»" ),
		3 => array( 'num' => '2 435 990', 'label' => "Рублей собрано в помощь\nдетям с инвалидностью" ),
		4 => array( 'num' => '70', 'label' => "Подростков обрели старших товарищей\nв проекте «Наставник»" ),
		5 => array( 'num' => '250', 'label' => "Волонтёров постоянно вовлечены\nв деятельность фонда" ),
		6 => array( 'num' => '597', 'label' => "Многодетных семей с приёмными детьми\nполучили помощь при сборах в школу" ),
		7 => array( 'num' => '715', 'label' => "Человек прошли обучение\nв Школе ответственных взрослых" ),
	);

	for ( $i = 1; $i <= 7; $i++ ) {
		db_add_setting( $wp_customize, "db_stat_{$i}_num", sprintf( __( 'Карточка %d — число', 'deti-baikala' ), $i ), 'text', 'db_stats', $stats_defaults[ $i ]['num'] );
		db_add_setting( $wp_customize, "db_stat_{$i}_label", sprintf( __( 'Карточка %d — подпись (новая строка = <br>)', 'deti-baikala' ), $i ), 'textarea', 'db_stats', $stats_defaults[ $i ]['label'] );
	}

	/* ---------------------------- BANNER ---------------------------- */
	$wp_customize->add_section(
		'db_banner',
		array(
			'title' => __( 'Баннер на главной (после «Пожертвовать»)', 'deti-baikala' ),
			'panel' => 'db_theme_options',
		)
	);

	db_add_setting( $wp_customize, 'db_banner_show', __( 'Показывать баннер', 'deti-baikala' ), 'checkbox', 'db_banner', false );
	db_add_setting( $wp_customize, 'db_banner_image', __( 'Фоновое изображение', 'deti-baikala' ), 'image', 'db_banner', '' );
	db_add_setting( $wp_customize, 'db_banner_title', __( 'Заголовок', 'deti-baikala' ), 'text', 'db_banner', '' );
	db_add_setting( $wp_customize, 'db_banner_text', __( 'Текст / описание', 'deti-baikala' ), 'textarea', 'db_banner', '' );
	db_add_setting( $wp_customize, 'db_banner_btn_text', __( 'Кнопка — текст', 'deti-baikala' ), 'text', 'db_banner', '' );
	db_add_setting( $wp_customize, 'db_banner_btn_url', __( 'Кнопка — ссылка', 'deti-baikala' ), 'url', 'db_banner', '' );

	/* ---------------------------- CONTACTS ---------------------------- */
	$wp_customize->add_section(
		'db_contacts',
		array(
			'title' => __( 'Контакты', 'deti-baikala' ),
			'panel' => 'db_theme_options',
		)
	);

	db_add_setting( $wp_customize, 'db_contact_address', __( 'Адрес', 'deti-baikala' ), 'textarea', 'db_contacts', '664011, г. Иркутск, ул. Байкальская, 317, оф. 12' );
	db_add_setting( $wp_customize, 'db_contact_phone', __( 'Телефон', 'deti-baikala' ), 'text', 'db_contacts', '+7 (3952) 00-00-00' );
	db_add_setting( $wp_customize, 'db_contact_hours', __( 'Режим работы', 'deti-baikala' ), 'text', 'db_contacts', 'Пн–Пт, 9:00–18:00' );
	db_add_setting( $wp_customize, 'db_contact_email', __( 'Email общий', 'deti-baikala' ), 'text', 'db_contacts', 'info@detibaikala.ru' );
	db_add_setting( $wp_customize, 'db_contact_email_partners', __( 'Email для партнёров', 'deti-baikala' ), 'text', 'db_contacts', 'partners@detibaikala.ru' );
	db_add_setting( $wp_customize, 'db_contact_email_press', __( 'Email для прессы', 'deti-baikala' ), 'text', 'db_contacts', 'press@detibaikala.ru' );
	db_add_setting( $wp_customize, 'db_contact_email_volunteer', __( 'Email для волонтёров', 'deti-baikala' ), 'text', 'db_contacts', 'volunteer@detibaikala.ru' );

	/* ---------------------------- SOCIAL ---------------------------- */
	$wp_customize->add_section(
		'db_social',
		array(
			'title' => __( 'Социальные сети', 'deti-baikala' ),
			'panel' => 'db_theme_options',
		)
	);

	db_add_setting( $wp_customize, 'db_social_vk', __( 'ВКонтакте', 'deti-baikala' ), 'url', 'db_social', '' );
	db_add_setting( $wp_customize, 'db_social_telegram', __( 'Telegram', 'deti-baikala' ), 'url', 'db_social', '' );
	db_add_setting( $wp_customize, 'db_social_ok', __( 'Одноклассники', 'deti-baikala' ), 'url', 'db_social', '' );
	db_add_setting( $wp_customize, 'db_social_rutube', __( 'Rutube', 'deti-baikala' ), 'url', 'db_social', '' );
	db_add_setting( $wp_customize, 'db_social_youtube', __( 'YouTube', 'deti-baikala' ), 'url', 'db_social', '' );

	/* ---------------------------- FOOTER ---------------------------- */
	$wp_customize->add_section(
		'db_footer',
		array(
			'title' => __( 'Подвал сайта', 'deti-baikala' ),
			'panel' => 'db_theme_options',
		)
	);

	db_add_setting( $wp_customize, 'db_footer_text', __( 'Описание фонда', 'deti-baikala' ), 'textarea', 'db_footer', 'Помогаем детям-сиротам, семьям с детьми-инвалидами и многодетным семьям Иркутской области. Свидетельство о регистрации НКО № 38-3015.' );
	db_add_setting( $wp_customize, 'db_copyright_text', __( 'Текст копирайта', 'deti-baikala' ), 'text', 'db_footer', '© %year% Благотворительный фонд «Дети Байкала». Все права защищены.' );

	/* ---------------------------- REQUISITES ---------------------------- */
	$wp_customize->add_section(
		'db_requisites',
		array(
			'title' => __( 'Реквизиты организации', 'deti-baikala' ),
			'panel' => 'db_theme_options',
		)
	);

	db_add_setting( $wp_customize, 'db_req_org_name', __( 'Наименование организации', 'deti-baikala' ), 'text', 'db_requisites', 'Благотворительный фонд «Дети Байкала»' );
	db_add_setting( $wp_customize, 'db_req_inn', __( 'ИНН', 'deti-baikala' ), 'text', 'db_requisites', '3811000001' );
	db_add_setting( $wp_customize, 'db_req_kpp', __( 'КПП', 'deti-baikala' ), 'text', 'db_requisites', '381101001' );
	db_add_setting( $wp_customize, 'db_req_ogrn', __( 'ОГРН', 'deti-baikala' ), 'text', 'db_requisites', '1123800000001' );
	db_add_setting( $wp_customize, 'db_req_account', __( 'Расчётный счёт', 'deti-baikala' ), 'text', 'db_requisites', '40703810000000000000' );
	db_add_setting( $wp_customize, 'db_req_bank', __( 'Банк', 'deti-baikala' ), 'text', 'db_requisites', 'ПАО Сбербанк, г. Иркутск' );
	db_add_setting( $wp_customize, 'db_req_bik', __( 'БИК', 'deti-baikala' ), 'text', 'db_requisites', '042520607' );
	db_add_setting( $wp_customize, 'db_req_corr_account', __( 'Корр. счёт', 'deti-baikala' ), 'text', 'db_requisites', '30101810900000000607' );

	/* ---------------------------- PAYMENT BADGE ---------------------------- */
	$wp_customize->add_section(
		'db_payment',
		array(
			'title' => __( 'Платёжный бейдж', 'deti-baikala' ),
			'panel' => 'db_theme_options',
		)
	);

	db_add_setting( $wp_customize, 'db_show_payment_badge', __( 'Показывать бейдж', 'deti-baikala' ), 'checkbox', 'db_payment', true );
	db_add_setting( $wp_customize, 'db_payment_badge_text', __( 'Название платёжной системы', 'deti-baikala' ), 'text', 'db_payment', 'МегаФон' );
	db_add_setting( $wp_customize, 'db_payment_badge_subtext', __( 'Текст рядом', 'deti-baikala' ), 'text', 'db_payment', 'Оферта оператора · Комиссия с абонента — 0%' );

	/* ---------------------------- LOGO ---------------------------- */
	$wp_customize->add_section(
		'db_logo',
		array(
			'title' => __( 'Логотип', 'deti-baikala' ),
			'panel' => 'db_theme_options',
		)
	);

	db_add_setting( $wp_customize, 'db_logo_initials', __( 'Текст-заглушка логотипа', 'deti-baikala' ), 'text', 'db_logo', 'ДБ', __( 'Используется, если кастомный логотип не загружен.', 'deti-baikala' ) );
	db_add_setting( $wp_customize, 'db_logo_title', __( 'Название организации в логотипе', 'deti-baikala' ), 'text', 'db_logo', 'Дети Байкала' );
	db_add_setting( $wp_customize, 'db_logo_subtitle', __( 'Подпись под названием', 'deti-baikala' ), 'text', 'db_logo', 'Благотворительный фонд' );
}
add_action( 'customize_register', 'db_customize_register' );

/**
 * Хелпер для регистрации настроек + контролов с автоматическим sanitize.
 */
function db_add_setting( $wp_customize, $id, $label, $type, $section, $default = '', $description = '' ) {
	$sanitize_map = array(
		'text'     => 'sanitize_text_field',
		'textarea' => 'sanitize_textarea_field',
		'url'      => 'esc_url_raw',
		'checkbox' => 'db_sanitize_checkbox',
		'image'    => 'esc_url_raw',
	);

	$wp_customize->add_setting(
		$id,
		array(
			'default'           => $default,
			'sanitize_callback' => isset( $sanitize_map[ $type ] ) ? $sanitize_map[ $type ] : 'sanitize_text_field',
			'transport'         => 'refresh',
		)
	);

	$args = array(
		'label'       => $label,
		'section'     => $section,
		'settings'    => $id,
		'description' => $description,
	);

	if ( 'textarea' === $type ) {
		$args['type'] = 'textarea';
		$wp_customize->add_control( $id, $args );
	} elseif ( 'checkbox' === $type ) {
		$args['type'] = 'checkbox';
		$wp_customize->add_control( $id, $args );
	} elseif ( 'image' === $type ) {
		$wp_customize->add_control(
			new WP_Customize_Image_Control( $wp_customize, $id, $args )
		);
	} else {
		$args['type'] = ( 'url' === $type ) ? 'url' : 'text';
		$wp_customize->add_control( $id, $args );
	}
}

function db_sanitize_checkbox( $checked ) {
	return ( isset( $checked ) && true === $checked ) || '1' === $checked || 1 === $checked;
}
