<?php
/**
 * Дети Байкала — обработчик анкеты «Присоединиться — Наставник».
 * Сохраняет анкету как CPT nastavnik_candidate и отправляет уведомление.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/* ===== ОБРАБОТЧИК ФОРМЫ ===== */

function db_handle_nastavnik_form() {
	if ( ! isset( $_POST['db_nastavnik_nonce'] ) || ! wp_verify_nonce( $_POST['db_nastavnik_nonce'], 'db_nastavnik_form' ) ) {
		wp_die( 'Ошибка проверки формы. Обновите страницу и попробуйте снова.' );
	}

	$redirect = wp_get_referer() ?: home_url( '/' );

	/* -- Санитизация полей -- */
	$f = array(
		'surname'              => sanitize_text_field( wp_unslash( $_POST['your-surname'] ?? '' ) ),
		'name'                 => sanitize_text_field( wp_unslash( $_POST['your-name'] ?? '' ) ),
		'patronymic'           => sanitize_text_field( wp_unslash( $_POST['your-patronymic'] ?? '' ) ),
		'residential_address'  => sanitize_text_field( wp_unslash( $_POST['your-residential-address'] ?? '' ) ),
		'phone'                => sanitize_text_field( wp_unslash( $_POST['your-phone'] ?? '' ) ),
		'email'                => sanitize_email( wp_unslash( $_POST['your-email'] ?? '' ) ),
		'birthday_day'         => absint( $_POST['your-birthday-day'] ?? 0 ),
		'birthday_month'       => sanitize_text_field( wp_unslash( $_POST['your-birthday-month'] ?? '' ) ),
		'birthday_year'        => absint( $_POST['your-birthday-year'] ?? 0 ),
		'worship'              => sanitize_text_field( wp_unslash( $_POST['your-worship'] ?? '' ) ),
		'citizenship'          => sanitize_text_field( wp_unslash( $_POST['citizenship'] ?? 'Да' ) ),
		'health'               => sanitize_text_field( wp_unslash( $_POST['health'] ?? '' ) ),
		'disease'              => sanitize_text_field( wp_unslash( $_POST['your-disease'] ?? '' ) ),
		'organization'         => sanitize_text_field( wp_unslash( $_POST['your-organization'] ?? '' ) ),
		'post'                 => sanitize_text_field( wp_unslash( $_POST['your-post'] ?? '' ) ),
		'duty'                 => sanitize_textarea_field( wp_unslash( $_POST['your-duty'] ?? '' ) ),
		'work_schedule'        => sanitize_text_field( wp_unslash( $_POST['your-work-schedule'] ?? '' ) ),
		'hobby'                => sanitize_textarea_field( wp_unslash( $_POST['your-hobby'] ?? '' ) ),
		'family'               => sanitize_text_field( wp_unslash( $_POST['family'] ?? '' ) ),
		'property'             => sanitize_text_field( wp_unslash( $_POST['your-property'] ?? '' ) ),
		'why'                  => sanitize_textarea_field( wp_unslash( $_POST['your-why'] ?? '' ) ),
		'property_character'   => sanitize_textarea_field( wp_unslash( $_POST['your-property-character'] ?? '' ) ),
		'characterized'        => sanitize_textarea_field( wp_unslash( $_POST['your-characterized'] ?? '' ) ),
		'help'                 => sanitize_textarea_field( wp_unslash( $_POST['your-help'] ?? '' ) ),
		'expectations_age'     => sanitize_text_field( wp_unslash( $_POST['your-expectations_age'] ?? '' ) ),
		'expectations_gender'  => sanitize_text_field( wp_unslash( $_POST['your-expectations_gender'] ?? '' ) ),
		'nature'               => sanitize_textarea_field( wp_unslash( $_POST['your-nature'] ?? '' ) ),
		'visit'                => sanitize_text_field( wp_unslash( $_POST['your-visit'] ?? '' ) ),
		'interaction'          => sanitize_text_field( wp_unslash( $_POST['interaction'] ?? 'Да' ) ),
		'alcohol'              => sanitize_text_field( wp_unslash( $_POST['your-alcohol'] ?? '' ) ),
		'tobacco'              => sanitize_text_field( wp_unslash( $_POST['your-tobacco'] ?? '' ) ),
		'psychotropic'         => sanitize_text_field( wp_unslash( $_POST['your-psychotropic'] ?? '' ) ),
		'narcotic'             => sanitize_text_field( wp_unslash( $_POST['your-narcotic'] ?? '' ) ),
		'convicted'            => sanitize_text_field( wp_unslash( $_POST['your-convicted'] ?? '' ) ),
		'deprivation'          => sanitize_text_field( wp_unslash( $_POST['your-deprivation'] ?? '' ) ),
		'report'               => sanitize_text_field( wp_unslash( $_POST['report'] ?? 'Да' ) ),
		'get'                  => sanitize_text_field( wp_unslash( $_POST['your-get'] ?? '' ) ),
		'agreement'            => isset( $_POST['agreement'] ) ? 1 : 0,
		'personal_data'        => isset( $_POST['personal_data'] ) ? 1 : 0,
	);

	/* -- Серверная проверка обязательных полей -- */
	$required = array( 'surname', 'name', 'patronymic', 'phone', 'email', 'worship', 'health',
		'organization', 'post', 'duty', 'work_schedule', 'hobby', 'family', 'property', 'why',
		'property_character', 'characterized', 'help', 'expectations_age', 'expectations_gender',
		'nature', 'visit', 'alcohol', 'tobacco', 'psychotropic', 'narcotic', 'convicted',
		'deprivation', 'get' );

	foreach ( $required as $field ) {
		if ( empty( $f[ $field ] ) ) {
			wp_safe_redirect( add_query_arg( 'nastavnik_sent', 'error', $redirect ) . '#nastavnik-form' );
			exit;
		}
	}

	if ( ! $f['agreement'] || ! $f['personal_data'] ) {
		wp_safe_redirect( add_query_arg( 'nastavnik_sent', 'error', $redirect ) . '#nastavnik-form' );
		exit;
	}

	/* -- Динамические массивы -- */
	$education = array();
	if ( ! empty( $_POST['education'] ) && is_array( $_POST['education'] ) ) {
		foreach ( $_POST['education'] as $edu ) {
			$education[] = array(
				'title'       => sanitize_text_field( wp_unslash( $edu['title'] ?? '' ) ),
				'institution' => sanitize_text_field( wp_unslash( $edu['institution'] ?? '' ) ),
				'specialty'   => sanitize_text_field( wp_unslash( $edu['specialty'] ?? '' ) ),
			);
		}
	}

	$family_members = array();
	if ( ! empty( $_POST['familyname'] ) && is_array( $_POST['familyname'] ) ) {
		foreach ( $_POST['familyname'] as $member ) {
			$family_members[] = array(
				'name'   => sanitize_text_field( wp_unslash( $member['name'] ?? '' ) ),
				'gender' => sanitize_text_field( wp_unslash( $member['gender'] ?? '' ) ),
				'age'    => sanitize_text_field( wp_unslash( $member['age'] ?? '' ) ),
				'status' => sanitize_text_field( wp_unslash( $member['status'] ?? '' ) ),
			);
		}
	}

	$experience = array();
	if ( ! empty( $_POST['experiencetitle'] ) && is_array( $_POST['experiencetitle'] ) ) {
		foreach ( $_POST['experiencetitle'] as $exp ) {
			$experience[] = array(
				'title' => sanitize_text_field( wp_unslash( $exp['title'] ?? '' ) ),
				'post'  => sanitize_text_field( wp_unslash( $exp['post'] ?? '' ) ),
				'duty'  => sanitize_textarea_field( wp_unslash( $exp['duty'] ?? '' ) ),
				'age'   => sanitize_text_field( wp_unslash( $exp['age'] ?? '' ) ),
			);
		}
	}

	/* -- Создание записи -- */
	$post_title = trim( $f['surname'] . ' ' . $f['name'] . ' ' . $f['patronymic'] );
	$post_id    = wp_insert_post( array(
		'post_title'  => $post_title,
		'post_type'   => 'nastavnik_candidate',
		'post_status' => 'publish',
	) );

	if ( is_wp_error( $post_id ) ) {
		wp_safe_redirect( add_query_arg( 'nastavnik_sent', 'error', $redirect ) . '#nastavnik-form' );
		exit;
	}

	/* -- Сохранение мета-данных -- */
	foreach ( $f as $key => $value ) {
		update_post_meta( $post_id, '_nc_' . $key, $value );
	}
	update_post_meta( $post_id, '_nc_education',       $education );
	update_post_meta( $post_id, '_nc_family_members',  $family_members );
	update_post_meta( $post_id, '_nc_experience',      $experience );
	update_post_meta( $post_id, '_nc_submitted_at',    current_time( 'mysql' ) );

	/* -- Отправка email -- */
	$birthday = $f['birthday_day'] . ' ' . $f['birthday_month'] . ' ' . $f['birthday_year'];

	$body  = "АНКЕТА КАНДИДАТА\n";
	$body .= str_repeat( '=', 50 ) . "\n";
	$body .= 'Дата подачи: ' . current_time( 'd.m.Y H:i' ) . "\n\n";

	$body .= "1. ОБЩИЕ СВЕДЕНИЯ:\n";
	$body .= "Фамилия: {$f['surname']}\n";
	$body .= "Имя: {$f['name']}\n";
	$body .= "Отчество: {$f['patronymic']}\n";
	$body .= "Адрес: {$f['residential_address']}\n";
	$body .= "Телефон: {$f['phone']}\n";
	$body .= "Email: {$f['email']}\n";
	$body .= "Дата рождения: {$birthday}\n";
	$body .= "Вероисповедание: {$f['worship']}\n";
	$body .= "Гражданство РФ: {$f['citizenship']}\n\n";

	$body .= "2. СОСТОЯНИЕ ЗДОРОВЬЯ:\n";
	$body .= "Оценка: {$f['health']}\n";
	$body .= "Заболевания: {$f['disease']}\n\n";

	$body .= "3. ОБРАЗОВАНИЕ:\n";
	foreach ( $education as $i => $edu ) {
		$body .= ( $i + 1 ) . ". {$edu['title']} | {$edu['institution']} | {$edu['specialty']}\n";
	}
	$body .= "\n";

	$body .= "4. ТРУДОУСТРОЙСТВО:\n";
	$body .= "Организация: {$f['organization']}\n";
	$body .= "Должность: {$f['post']}\n";
	$body .= "Обязанности: {$f['duty']}\n";
	$body .= "График: {$f['work_schedule']}\n\n";

	$body .= "5. ИНТЕРЕСЫ, ХОББИ:\n{$f['hobby']}\n\n";

	$body .= "6. СЕМЕЙНОЕ ПОЛОЖЕНИЕ:\n";
	$body .= "Статус: {$f['family']}\n";
	foreach ( $family_members as $i => $m ) {
		$body .= ( $i + 1 ) . ". {$m['name']} | {$m['gender']} | {$m['age']} лет | {$m['status']}\n";
	}
	$body .= "\n";

	$body .= "7. ОПЫТ РАБОТЫ С ДЕТЬМИ:\n";
	foreach ( $experience as $i => $exp ) {
		$body .= ( $i + 1 ) . ". {$exp['title']} | {$exp['post']} | {$exp['duty']} | возраст детей: {$exp['age']}\n";
	}
	$body .= "\n";

	$body .= "8. ВОПРОСЫ:\n";
	$body .= "Роль в программе: {$f['property']}\n";
	$body .= "Почему хотите стать наставником: {$f['why']}\n";
	$body .= "Качества характера: {$f['property_character']}\n";
	$body .= "Самохарактеристика: {$f['characterized']}\n";
	$body .= "Почему хотите помогать: {$f['help']}\n";
	$body .= "Ожидаемый возраст ребёнка: {$f['expectations_age']}\n";
	$body .= "Пол ребёнка: {$f['expectations_gender']}\n";
	$body .= "Характер ребёнка: {$f['nature']}\n";
	$body .= "Как часто навещать: {$f['visit']}\n";
	$body .= "Взаимодействие с ребёнком с ОВЗ: {$f['interaction']}\n\n";

	$body .= "9. БЕЗОПАСНОСТЬ:\n";
	$body .= "Алкоголь: {$f['alcohol']}\n";
	$body .= "Табак: {$f['tobacco']}\n";
	$body .= "Психотропные вещества: {$f['psychotropic']}\n";
	$body .= "Наркотики: {$f['narcotic']}\n";
	$body .= "Судимости: {$f['convicted']}\n";
	$body .= "Лишение прав: {$f['deprivation']}\n";
	$body .= "Заполнять отчёты: {$f['report']}\n";
	$body .= "Откуда узнали: {$f['get']}\n\n";

	$body .= "СОГЛАСИЯ:\n";
	$body .= 'Личное заявление: ' . ( $f['agreement'] ? 'Подписано' : 'Не подписано' ) . "\n";
	$body .= 'Согласие на обработку ПД: ' . ( $f['personal_data'] ? 'Дано' : 'Не дано' ) . "\n\n";

	$body .= 'Просмотреть анкету: ' . admin_url( 'post.php?post=' . $post_id . '&action=edit' );

	$headers = array(
		'From: Наставник <admin@nastavnik38.ru>',
		'Content-Type: text/plain; charset=UTF-8',
	);

	wp_mail( 'Elena_s2002@rambler.ru', 'Добавлена анкета кандидата', $body, $headers );

	wp_safe_redirect( add_query_arg( 'nastavnik_sent', 'success', $redirect ) . '#nastavnik-form' );
	exit;
}
add_action( 'admin_post_db_nastavnik_form', 'db_handle_nastavnik_form' );
add_action( 'admin_post_nopriv_db_nastavnik_form', 'db_handle_nastavnik_form' );


/* ===== ADMIN: КОЛОНКИ ДЛЯ СПИСКА АНКЕТ ===== */

function db_nc_admin_columns( $columns ) {
	unset( $columns['date'] );
	$columns['nc_phone']    = 'Телефон';
	$columns['nc_email']    = 'Email';
	$columns['nc_property'] = 'Роль';
	$columns['nc_date']     = 'Дата подачи';
	return $columns;
}
add_filter( 'manage_nastavnik_candidate_posts_columns', 'db_nc_admin_columns' );

function db_nc_admin_column_values( $column, $post_id ) {
	switch ( $column ) {
		case 'nc_phone':
			echo esc_html( get_post_meta( $post_id, '_nc_phone', true ) );
			break;
		case 'nc_email':
			echo esc_html( get_post_meta( $post_id, '_nc_email', true ) );
			break;
		case 'nc_property':
			echo esc_html( get_post_meta( $post_id, '_nc_property', true ) );
			break;
		case 'nc_date':
			$date = get_post_meta( $post_id, '_nc_submitted_at', true );
			echo esc_html( $date ? date_i18n( 'd.m.Y H:i', strtotime( $date ) ) : '—' );
			break;
	}
}
add_action( 'manage_nastavnik_candidate_posts_custom_column', 'db_nc_admin_column_values', 10, 2 );


/* ===== ADMIN: МЕТА-БОКс ПРОСМОТРА АНКЕТЫ ===== */

function db_nc_register_metabox() {
	add_meta_box(
		'nc_data',
		'Данные анкеты',
		'db_nc_render_metabox',
		'nastavnik_candidate',
		'normal',
		'high'
	);
}
add_action( 'add_meta_boxes', 'db_nc_register_metabox' );

function db_nc_render_metabox( $post ) {
	$m = function( $key ) use ( $post ) {
		return get_post_meta( $post->ID, '_nc_' . $key, true );
	};

	$birthday = $m('birthday_day') . ' ' . $m('birthday_month') . ' ' . $m('birthday_year');

	$rows = array(
		'1. ОБЩИЕ СВЕДЕНИЯ' => null,
		'Фамилия'           => $m('surname'),
		'Имя'               => $m('name'),
		'Отчество'          => $m('patronymic'),
		'Адрес'             => $m('residential_address'),
		'Телефон'           => $m('phone'),
		'Email'             => $m('email'),
		'Дата рождения'     => $birthday,
		'Вероисповедание'   => $m('worship'),
		'Гражданство РФ'    => $m('citizenship'),

		'2. СОСТОЯНИЕ ЗДОРОВЬЯ' => null,
		'Оценка здоровья'       => $m('health'),
		'Заболевания'           => $m('disease'),

		'4. ТРУДОУСТРОЙСТВО'    => null,
		'Организация'           => $m('organization'),
		'Должность'             => $m('post'),
		'Обязанности'           => $m('duty'),
		'График работы'         => $m('work_schedule'),

		'5. ИНТЕРЕСЫ, ХОББИ' => null,
		'Хобби'              => $m('hobby'),

		'6. СЕМЕЙНОЕ ПОЛОЖЕНИЕ' => null,
		'Статус'                => $m('family'),

		'8. ВОПРОСЫ'              => null,
		'Роль в программе'        => $m('property'),
		'Почему наставником'      => $m('why'),
		'Качества характера'      => $m('property_character'),
		'Самохарактеристика'      => $m('characterized'),
		'Почему помогать'         => $m('help'),
		'Ожидаемый возраст'       => $m('expectations_age'),
		'Пол ребёнка'             => $m('expectations_gender'),
		'Характер ребёнка'        => $m('nature'),
		'Как часто навещать'      => $m('visit'),
		'Взаимодействие с ОВЗ'   => $m('interaction'),

		'9. БЕЗОПАСНОСТЬ'     => null,
		'Алкоголь'            => $m('alcohol'),
		'Табак'               => $m('tobacco'),
		'Психотропные'        => $m('psychotropic'),
		'Наркотики'           => $m('narcotic'),
		'Судимости'           => $m('convicted'),
		'Лишение прав'        => $m('deprivation'),
		'Заполнять отчёты'    => $m('report'),
		'Откуда узнали'       => $m('get'),

		'СОГЛАСИЯ'            => null,
		'Личное заявление'    => $m('agreement') ? 'Подписано' : 'Не подписано',
		'Согласие на ПД'      => $m('personal_data') ? 'Дано' : 'Не дано',
	);

	echo '<style>.nc-mb-table{width:100%;border-collapse:collapse;font-size:13px}.nc-mb-table td{padding:6px 10px;border-bottom:1px solid #eee;vertical-align:top}.nc-mb-table .nc-mb-head{background:#f0f0f0;font-weight:bold;color:#555;padding:8px 10px;border-top:2px solid #ccc}.nc-mb-table .nc-mb-key{width:220px;color:#666;font-weight:500}.nc-mb-dynamic{margin-top:10px;padding:8px;background:#fafafa;border:1px solid #eee;border-radius:4px;font-size:12px}</style>';
	echo '<table class="nc-mb-table">';

	foreach ( $rows as $label => $value ) {
		if ( is_null( $value ) ) {
			echo '<tr><td class="nc-mb-head" colspan="2">' . esc_html( $label ) . '</td></tr>';
		} else {
			echo '<tr><td class="nc-mb-key">' . esc_html( $label ) . '</td><td>' . nl2br( esc_html( $value ) ) . '</td></tr>';
		}
	}

	/* -- Образование -- */
	$education = $m('education');
	echo '<tr><td class="nc-mb-head" colspan="2">3. ОБРАЗОВАНИЕ</td></tr>';
	echo '<tr><td colspan="2">';
	if ( $education && is_array( $education ) ) {
		foreach ( $education as $i => $edu ) {
			echo '<div class="nc-mb-dynamic"><strong>' . ( $i + 1 ) . '.</strong> '
				. esc_html( $edu['title'] ) . ' | '
				. esc_html( $edu['institution'] ) . ' | '
				. esc_html( $edu['specialty'] ) . '</div>';
		}
	} else {
		echo '<em style="color:#aaa">—</em>';
	}
	echo '</td></tr>';

	/* -- Члены семьи -- */
	$family_members = $m('family_members');
	echo '<tr><td class="nc-mb-head" colspan="2">Члены семьи</td></tr>';
	echo '<tr><td colspan="2">';
	if ( $family_members && is_array( $family_members ) ) {
		foreach ( $family_members as $i => $member ) {
			echo '<div class="nc-mb-dynamic"><strong>' . ( $i + 1 ) . '.</strong> '
				. esc_html( $member['name'] ) . ' | '
				. esc_html( $member['gender'] ) . ' | '
				. esc_html( $member['age'] ) . ' лет | '
				. esc_html( $member['status'] ) . '</div>';
		}
	} else {
		echo '<em style="color:#aaa">—</em>';
	}
	echo '</td></tr>';

	/* -- Опыт работы с детьми -- */
	$experience = $m('experience');
	echo '<tr><td class="nc-mb-head" colspan="2">7. ОПЫТ РАБОТЫ С ДЕТЬМИ</td></tr>';
	echo '<tr><td colspan="2">';
	if ( $experience && is_array( $experience ) ) {
		foreach ( $experience as $i => $exp ) {
			echo '<div class="nc-mb-dynamic"><strong>' . ( $i + 1 ) . '.</strong> '
				. esc_html( $exp['title'] ) . ' | '
				. esc_html( $exp['post'] ) . ' | '
				. esc_html( $exp['duty'] ) . ' | возраст детей: '
				. esc_html( $exp['age'] ) . '</div>';
		}
	} else {
		echo '<em style="color:#aaa">—</em>';
	}
	echo '</td></tr>';

	echo '</table>';
}
