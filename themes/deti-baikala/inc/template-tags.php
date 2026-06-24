<?php
/**
 * Дети Байкала — общие шаблонные функции.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Логотип в шапке — кастомный логотип или текстовая заглушка.
 */
function db_render_logo( $with_text = false ) {
	if ( has_custom_logo() ) {
		echo '<span class="logo__icon logo__icon--image">';
		the_custom_logo();
		echo '</span>';
	} else {
		echo '<a href="' . esc_url( home_url( '/' ) ) . '" class="logo__icon">' . esc_html( get_theme_mod( 'db_logo_initials', 'ДБ' ) ) . '</a>';
	}

	if ( $with_text ) {
		?>
		<div class="logo__text">
			<span class="logo__title"><?php echo esc_html( get_theme_mod( 'db_logo_title', get_bloginfo( 'name' ) ) ); ?></span>
			<span class="logo__sub"><?php echo esc_html( get_theme_mod( 'db_logo_subtitle', __( 'Благотворительный фонд', 'deti-baikala' ) ) ); ?></span>
		</div>
		<?php
	}
}

/**
 * SVG-иконка по ключу платформы.
 */
function db_get_social_icon_svg( $icon ) {
	$icons = array(
		'vk'       => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M15.684 0H8.316C1.592 0 0 1.592 0 8.316v7.368C0 22.408 1.592 24 8.316 24h7.368C22.408 24 24 22.408 24 15.684V8.316C24 1.592 22.408 0 15.684 0zm3.692 17.123h-1.744c-.66 0-.864-.525-2.05-1.727-1.033-.99-1.49-.897-1.744-.897-.356 0-.458.102-.458.593v1.575c0 .424-.135.678-1.253.678-1.846 0-3.896-1.118-5.335-3.202C4.624 10.857 4.03 8.57 4.03 8.096c0-.254.102-.491.593-.491H6.37c.44 0 .61.203.78.677.863 2.49 2.303 4.675 2.896 4.675.22 0 .322-.102.322-.66V9.721c-.068-1.186-.695-1.287-.695-1.71 0-.203.17-.407.44-.407H12.9c.373 0 .508.203.508.643v3.473c0 .373.17.508.271.508.22 0 .407-.135.813-.542 1.27-1.422 2.168-3.625 2.168-3.625.12-.254.322-.491.762-.491h1.744c.525 0 .643.27.525.643-.22 1.016-.728 1.727-2.507 3.726-.83 1.067-.338 1.287 0 1.727.745.915 2.32 2.845 2.32 3.84 0 .542-.27.845-.847.845z"/></svg>',
		'ok'       => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 0C5.373 0 0 5.373 0 12s5.373 12 12 12 12-5.373 12-12S18.627 0 12 0zm0 5a3 3 0 1 1 0 6 3 3 0 0 1 0-6zm5 9.56c-.297.459-1.043.754-2.263.754l-.03.001 2.512 2.512a1 1 0 1 1-1.414 1.414L13.5 16.938 11.195 19.24a1 1 0 1 1-1.414-1.414l2.513-2.512h-.03c-1.22 0-1.966-.295-2.264-.754a1 1 0 0 1 0-1.12c.298-.458 1.044-.753 2.264-.753H12h2.264c1.22 0 1.966.295 2.263.754a1 1 0 0 1 0 1.12z"/></svg>',
		'max'      => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M15.684 0H8.316C1.592 0 0 1.592 0 8.316v7.368C0 22.408 1.592 24 8.316 24h7.368C22.408 24 24 22.408 24 15.684V8.316C24 1.592 22.408 0 15.684 0zM5 7.5h2l5 5 5-5h2v9h-2V10l-5 5-5-5v6.5H5z"/></svg>',
		'telegram' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M11.944 0A12 12 0 0 0 0 12a12 12 0 0 0 12 12 12 12 0 0 0 12-12A12 12 0 0 0 12 0a12 12 0 0 0-.056 0zm4.962 7.224c.1-.002.321.023.465.14a.506.506 0 0 1 .171.325c.016.093.036.306.02.472-.18 1.898-.96 6.502-1.36 8.627-.168.9-.499 1.201-.82 1.23-.696.065-1.225-.46-1.9-.902-1.056-.693-1.653-1.124-2.678-1.8-1.185-.78-.417-1.21.258-1.91.177-.184 3.247-2.977 3.307-3.23.007-.032.014-.15-.056-.212s-.174-.041-.249-.024c-.106.024-1.793 1.14-5.061 3.345-.48.33-.913.49-1.302.48-.428-.008-1.252-.241-1.865-.44-.752-.245-1.349-.374-1.297-.789.027-.216.325-.437.893-.663 3.498-1.524 5.83-2.529 6.998-3.014 3.332-1.386 4.025-1.627 4.476-1.635z"/></svg>',
		'youtube'  => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/></svg>',
		'rutube'   => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 0C5.373 0 0 5.373 0 12s5.373 12 12 12 12-5.373 12-12S18.627 0 12 0zM8 7h5a3 3 0 0 1 0 6h-1l2.5 4H12l-2.5-4H10v4H8V7zm2 2v3h3a1.5 1.5 0 0 0 0-3h-3z"/></svg>',
	);
	return isset( $icons[ $icon ] ) ? $icons[ $icon ] : '';
}

/**
 * Иконки соцсетей из Customizer.
 */
function db_render_social_icons() {
	$valid_icons = array( 'vk', 'ok', 'max', 'telegram', 'youtube', 'rutube' );

	$configurable = array(
		'vk'  => array( 'label' => 'ВКонтакте',    'default_icon' => 'vk' ),
		'ok'  => array( 'label' => 'Одноклассники', 'default_icon' => 'ok' ),
		'max' => array( 'label' => 'MAX',           'default_icon' => 'max' ),
	);

	$fixed = array(
		'telegram' => 'Telegram',
		'youtube'  => 'YouTube',
		'rutube'   => 'Rutube',
	);

	foreach ( $configurable as $key => $data ) {
		$url = get_theme_mod( 'db_social_' . $key, '' );
		if ( empty( $url ) ) {
			continue;
		}
		$icon = get_theme_mod( 'db_social_' . $key . '_icon', $data['default_icon'] );
		if ( ! in_array( $icon, $valid_icons, true ) ) {
			$icon = $data['default_icon'];
		}
		printf(
			'<a class="social-icon" href="%s" target="_blank" rel="noopener" aria-label="%s" title="%s">%s</a>',
			esc_url( $url ),
			esc_attr( $data['label'] ),
			esc_attr( $data['label'] ),
			db_get_social_icon_svg( $icon )
		);
	}

	foreach ( $fixed as $key => $label ) {
		$url = get_theme_mod( 'db_social_' . $key, '' );
		if ( empty( $url ) ) {
			continue;
		}
		printf(
			'<a class="social-icon" href="%s" target="_blank" rel="noopener" aria-label="%s" title="%s">%s</a>',
			esc_url( $url ),
			esc_attr( $label ),
			esc_attr( $label ),
			db_get_social_icon_svg( $key )
		);
	}
}

/**
 * Блок реквизитов организации.
 */
function db_render_requisites() {
	$fields = array(
		'db_req_org_name'    => __( 'Организация', 'deti-baikala' ),
		'db_req_inn'         => __( 'ИНН', 'deti-baikala' ),
		'db_req_kpp'         => __( 'КПП', 'deti-baikala' ),
		'db_req_ogrn'        => __( 'ОГРН', 'deti-baikala' ),
		'db_req_okved'       => __( 'ОКВЭД', 'deti-baikala' ),
		'db_req_account'     => __( 'Р/с', 'deti-baikala' ),
		'db_req_bank'        => __( 'Банк', 'deti-baikala' ),
		'db_req_bik'         => __( 'БИК', 'deti-baikala' ),
		'db_req_corr_account'=> __( 'Корр. счёт', 'deti-baikala' ),
	);

	$has_values = false;
	foreach ( $fields as $key => $label ) {
		if ( get_theme_mod( $key ) ) {
			$has_values = true;
			break;
		}
	}
	if ( ! $has_values ) {
		return;
	}
	?>
	<div class="requisites-block">
		<h3><?php esc_html_e( 'Реквизиты', 'deti-baikala' ); ?></h3>
		<div class="requisites-grid">
			<?php foreach ( $fields as $key => $label ) :
				$value = get_theme_mod( $key );
				if ( ! $value ) continue;
				?>
				<div>
					<div class="requisites-grid__label"><?php echo esc_html( $label ); ?></div>
					<div><?php echo esc_html( $value ); ?></div>
				</div>
			<?php endforeach; ?>
		</div>
	<?php
	$charter_id = get_theme_mod( 'db_charter_pdf' );
	if ( $charter_id ) {
		$charter_url = wp_get_attachment_url( $charter_id );
		if ( $charter_url ) {
			?>
			<a class="btn btn-secondary" href="<?php echo esc_url( $charter_url ); ?>" target="_blank" rel="noopener" style="margin-top:1.25rem;display:inline-flex">
				<?php esc_html_e( 'Скачать Устав (PDF)', 'deti-baikala' ); ?>
			</a>
			<?php
		}
	}
	?>
	</div>
	<?php
}

/**
 * Текст копирайта с подстановкой %year%.
 */
function db_render_copyright() {
	$text = get_theme_mod( 'db_copyright_text', '© %year% Благотворительный фонд «Дети Байкала». Все права защищены.' );
	echo esc_html( str_replace( '%year%', date_i18n( 'Y' ), $text ) );
}

/**
 * Платёжный бейдж в подвале.
 */
function db_render_payment_badge() {
	if ( ! get_theme_mod( 'db_show_payment_badge', false ) ) {
		return;
	}
	$text    = get_theme_mod( 'db_payment_badge_text', 'МегаФон' );
	$subtext = get_theme_mod( 'db_payment_badge_subtext', '' );
	?>
	<div class="megafon-badge">
		<span class="megafon-logo"><?php echo esc_html( $text ); ?></span>
		<?php if ( $subtext ) : ?><span><?php echo esc_html( $subtext ); ?></span><?php endif; ?>
	</div>
	<?php
}

/**
 * Карточка отзыва — аватар: фото или инициалы из заголовка.
 */
function db_render_review_avatar( $post_id ) {
	if ( has_post_thumbnail( $post_id ) ) {
		echo '<div class="review-card__avatar">' . get_the_post_thumbnail( $post_id, 'thumbnail' ) . '</div>';
		return;
	}
	$title  = get_the_title( $post_id );
	$words  = preg_split( '/\s+/u', trim( $title ) );
	$initials = '';
	foreach ( $words as $word ) {
		if ( $word !== '' ) {
			$initials .= mb_substr( $word, 0, 1 );
		}
		if ( mb_strlen( $initials ) >= 2 ) {
			break;
		}
	}
	echo '<div class="review-card__avatar">' . esc_html( mb_strtoupper( $initials ) ) . '</div>';
}

/**
 * Найти URL страницы, которой назначен указанный файл шаблона.
 */
function db_get_page_by_template( $template_file ) {
	$pages = get_posts(
		array(
			'post_type'   => 'page',
			'meta_key'    => '_wp_page_template',
			'meta_value'  => $template_file,
			'numberposts' => 1,
		)
	);
	return $pages ? get_permalink( $pages[0] ) : '';
}

/**
 * Звёзды рейтинга отзыва.
 */
function db_render_stars( $rating ) {
	$rating = max( 1, min( 5, (int) $rating ) );
	echo '<div class="stars">' . esc_html( str_repeat( '★', $rating ) . str_repeat( '☆', 5 - $rating ) ) . '</div>';
}

/**
 * Кнопка переключения светлой/тёмной темы.
 */
function db_render_theme_toggle() {
	?>
	<button class="theme-btn" id="themeToggle" title="<?php esc_attr_e( 'Сменить тему', 'deti-baikala' ); ?>">
		<svg class="theme-btn__icon theme-btn__icon--sun" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M4.93 19.07l1.41-1.41M17.66 6.34l1.41-1.41"/></svg>
		<svg class="theme-btn__icon theme-btn__icon--moon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>
	</button>
	<?php
}

/**
 * Узкий walker для главного/мобильного меню — выводит плоские <a class="nav__link">
 * без <ul>/<li>, как в исходной вёрстке.
 */
class DB_Walker_Nav_Menu extends Walker_Nav_Menu {
	public function start_lvl( &$output, $depth = 0, $args = null ) {}
	public function end_lvl( &$output, $depth = 0, $args = null ) {}

	public function start_el( &$output, $item, $depth = 0, $args = null, $id = 0 ) {
		$classes = empty( $item->classes ) ? array() : (array) $item->classes;
		$classes[] = 'nav__link';
		if ( in_array( 'current-menu-item', $classes, true ) || in_array( 'current_page_item', $classes, true ) ) {
			$classes[] = 'active';
		}
		$class_names = implode( ' ', array_unique( array_filter( $classes ) ) );

		$output .= sprintf(
			'<a href="%s" class="%s">%s</a>',
			esc_url( $item->url ),
			esc_attr( $class_names ),
			esc_html( $item->title )
		);
	}

	public function end_el( &$output, $item, $depth = 0, $args = null ) {}
}

/**
 * Главное меню (или резервный набор ссылок, если меню не назначено).
 */
function db_render_primary_nav( $extra_class = '' ) {
	if ( has_nav_menu( 'primary' ) ) {
		wp_nav_menu(
			array(
				'theme_location' => 'primary',
				'container'      => false,
				'items_wrap'     => '%3$s',
				'walker'         => new DB_Walker_Nav_Menu(),
				'fallback_cb'    => false,
			)
		);
		return;
	}

	$fallback = array(
		__( 'Главная', 'deti-baikala' )      => home_url( '/' ),
		__( 'Новости', 'deti-baikala' )      => home_url( '/novosti/' ),
		__( 'О фонде', 'deti-baikala' )      => home_url( '/o-fonde/' ),
		__( 'СМИ о фонде', 'deti-baikala' )  => home_url( '/smi-o-fonde/' ),
		__( 'Отзывы', 'deti-baikala' )       => home_url( '/otzyvy/' ),
		__( 'Проекты', 'deti-baikala' )      => home_url( '/proekty/' ),
		__( 'Кампании', 'deti-baikala' )     => home_url( '/kampanii/' ),
		__( 'Отчёты', 'deti-baikala' )       => home_url( '/otchety/' ),
		__( 'Контакты', 'deti-baikala' )     => home_url( '/kontakty/' ),
	);

	foreach ( $fallback as $label => $url ) {
		printf( '<a href="%s" class="nav__link">%s</a>', esc_url( $url ), esc_html( $label ) );
	}
}
