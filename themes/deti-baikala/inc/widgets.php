<?php
/**
 * Дети Байкала — кастомные виджеты.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Виджет "Активные сборы" — выводит кампании Лейки с прогресс-баром.
 */
class DB_Active_Campaigns_Widget extends WP_Widget {

	public function __construct() {
		parent::__construct(
			'db_active_campaigns',
			__( 'Дети Байкала: Активные сборы', 'deti-baikala' ),
			array( 'description' => __( 'Список активных кампаний по сбору средств (плагин «Лейка»).', 'deti-baikala' ) )
		);
	}

	public function widget( $args, $instance ) {
		$title = ! empty( $instance['title'] ) ? $instance['title'] : __( 'Активные сборы', 'deti-baikala' );
		$count = ! empty( $instance['count'] ) ? absint( $instance['count'] ) : 3;

		if ( ! post_type_exists( 'leyka_campaign' ) ) {
			return;
		}

		$campaigns = db_get_campaigns( $count );

		if ( ! $campaigns->have_posts() ) {
			return;
		}

		echo $args['before_widget'];
		if ( $title ) {
			echo $args['before_title'] . esc_html( $title ) . $args['after_title'];
		}

		while ( $campaigns->have_posts() ) {
			$campaigns->the_post();
			$progress = db_campaign_progress( get_the_ID() );
			?>
			<div class="sidebar-campaign">
				<div class="sidebar-campaign__title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></div>
				<div class="progress-bar"><div class="progress-bar__fill" style="width:<?php echo esc_attr( $progress['percent'] ); ?>%"></div></div>
				<div class="sidebar-campaign__meta">
					<span><?php echo esc_html( $progress['collected_text'] ); ?></span>
					<span><?php echo esc_html( $progress['percent'] ); ?>%</span>
				</div>
			</div>
			<?php
		}
		wp_reset_postdata();

		echo $args['after_widget'];
	}

	public function form( $instance ) {
		$title = isset( $instance['title'] ) ? $instance['title'] : __( 'Активные сборы', 'deti-baikala' );
		$count = isset( $instance['count'] ) ? absint( $instance['count'] ) : 3;
		?>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"><?php esc_html_e( 'Заголовок:', 'deti-baikala' ); ?></label>
			<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" />
		</p>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'count' ) ); ?>"><?php esc_html_e( 'Количество кампаний:', 'deti-baikala' ); ?></label>
			<input class="small-text" id="<?php echo esc_attr( $this->get_field_id( 'count' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'count' ) ); ?>" type="number" min="1" max="10" value="<?php echo esc_attr( $count ); ?>" />
		</p>
		<?php
	}

	public function update( $new_instance, $old_instance ) {
		$instance          = array();
		$instance['title'] = sanitize_text_field( $new_instance['title'] );
		$instance['count'] = absint( $new_instance['count'] );
		return $instance;
	}
}

/**
 * Виджет "Рекламный блок" — изображение + текст + ссылка.
 */
class DB_Ad_Block_Widget extends WP_Widget {

	public function __construct() {
		parent::__construct(
			'db_ad_block',
			__( 'Дети Байкала: Рекламный блок', 'deti-baikala' ),
			array( 'description' => __( 'Изображение/баннер с подписью и ссылкой.', 'deti-baikala' ) )
		);
	}

	public function widget( $args, $instance ) {
		$title = ! empty( $instance['title'] ) ? $instance['title'] : '';
		$text  = ! empty( $instance['text'] ) ? $instance['text'] : __( 'Рекламный блок', 'deti-baikala' );
		$image = ! empty( $instance['image'] ) ? $instance['image'] : '';
		$url   = ! empty( $instance['url'] ) ? $instance['url'] : '';

		echo $args['before_widget'];
		if ( $title ) {
			echo $args['before_title'] . esc_html( $title ) . $args['after_title'];
		}

		$content = '<div class="sidebar-ad">';
		if ( $image ) {
			$content .= '<img src="' . esc_url( $image ) . '" alt="" />';
		} else {
			$content .= '<span>' . esc_html( $text ) . '</span>';
		}
		$content .= '</div>';

		if ( $url ) {
			echo '<a href="' . esc_url( $url ) . '">' . $content . '</a>';
		} else {
			echo $content;
		}

		echo $args['after_widget'];
	}

	public function form( $instance ) {
		$title = isset( $instance['title'] ) ? $instance['title'] : '';
		$text  = isset( $instance['text'] ) ? $instance['text'] : __( 'Рекламный блок', 'deti-baikala' );
		$image = isset( $instance['image'] ) ? $instance['image'] : '';
		$url   = isset( $instance['url'] ) ? $instance['url'] : '';
		?>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"><?php esc_html_e( 'Заголовок:', 'deti-baikala' ); ?></label>
			<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" />
		</p>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'text' ) ); ?>"><?php esc_html_e( 'Текст-заглушка (если без картинки):', 'deti-baikala' ); ?></label>
			<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'text' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'text' ) ); ?>" type="text" value="<?php echo esc_attr( $text ); ?>" />
		</p>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'image' ) ); ?>"><?php esc_html_e( 'Изображение (URL):', 'deti-baikala' ); ?></label>
			<input class="widefat db-ad-image" id="<?php echo esc_attr( $this->get_field_id( 'image' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'image' ) ); ?>" type="text" value="<?php echo esc_attr( $image ); ?>" />
			<button type="button" class="button db-ad-image-select"><?php esc_html_e( 'Выбрать изображение', 'deti-baikala' ); ?></button>
		</p>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'url' ) ); ?>"><?php esc_html_e( 'Ссылка:', 'deti-baikala' ); ?></label>
			<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'url' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'url' ) ); ?>" type="text" value="<?php echo esc_attr( $url ); ?>" />
		</p>
		<?php
	}

	public function update( $new_instance, $old_instance ) {
		$instance           = array();
		$instance['title']  = sanitize_text_field( $new_instance['title'] );
		$instance['text']   = sanitize_text_field( $new_instance['text'] );
		$instance['image']  = esc_url_raw( $new_instance['image'] );
		$instance['url']    = esc_url_raw( $new_instance['url'] );
		return $instance;
	}
}

function db_register_widgets() {
	register_widget( 'DB_Active_Campaigns_Widget' );
	register_widget( 'DB_Ad_Block_Widget' );
}
add_action( 'widgets_init', 'db_register_widgets' );

/**
 * Медиа-загрузчик для поля изображения в виджете "Рекламный блок".
 */
function db_widget_admin_assets( $hook ) {
	if ( 'widgets.php' !== $hook && 'customize.php' !== $hook ) {
		return;
	}
	wp_enqueue_media();
	wp_add_inline_script(
		'jquery-core',
		"jQuery(function($){
			$(document).on('click', '.db-ad-image-select', function(e){
				e.preventDefault();
				var input = $(this).siblings('.db-ad-image');
				var frame = wp.media({ title: '" . esc_js( __( 'Выберите изображение', 'deti-baikala' ) ) . "', multiple: false });
				frame.on('select', function(){
					var att = frame.state().get('selection').first().toJSON();
					input.val(att.url).trigger('change');
				});
				frame.open();
			});
		});"
	);
}
add_action( 'admin_enqueue_scripts', 'db_widget_admin_assets' );
