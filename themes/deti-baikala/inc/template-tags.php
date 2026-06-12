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
		echo '<span class="logo__icon">';
		the_custom_logo();
		echo '</span>';
	} else {
		echo '<div class="logo__icon">' . esc_html( get_theme_mod( 'db_logo_initials', 'ДБ' ) ) . '</div>';
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
 * Иконки соцсетей из Customizer.
 */
function db_render_social_icons() {
	$socials = array(
		'vk'       => array( 'label' => 'VK', 'icon' => 'vk' ),
		'telegram' => array( 'label' => 'TG', 'icon' => 'telegram' ),
		'ok'       => array( 'label' => 'OK', 'icon' => 'ok' ),
		'rutube'   => array( 'label' => 'RU', 'icon' => 'rutube' ),
		'youtube'  => array( 'label' => 'YT', 'icon' => 'youtube' ),
	);

	foreach ( $socials as $key => $data ) {
		$url = get_theme_mod( 'db_social_' . $key );
		if ( empty( $url ) ) {
			continue;
		}
		printf(
			'<a class="social-icon" href="%s" target="_blank" rel="noopener" aria-label="%s">%s</a>',
			esc_url( $url ),
			esc_attr( $data['label'] ),
			esc_html( $data['label'] )
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
	<button class="theme-btn" id="themeToggle" title="<?php esc_attr_e( 'Сменить тему', 'deti-baikala' ); ?>">☀️</button>
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
