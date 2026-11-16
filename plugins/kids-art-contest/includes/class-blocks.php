<?php
defined( 'ABSPATH' ) || exit;

/**
 * Серверные блоки редактора: конкурс, форма, итоги (без сборки, обёртки над шорткодами).
 */
class KAC_Blocks {

	const BLOCKS = array(
		'contest' => array( 'title' => 'Конкурс рисунков', 'shortcode' => 'kac_contest' ),
		'form'    => array( 'title' => 'Форма подачи рисунка', 'shortcode' => 'kac_submit_form' ),
		'results' => array( 'title' => 'Итоги конкурса', 'shortcode' => 'kac_results' ),
	);

	public static function init(): void {
		add_action( 'init', array( __CLASS__, 'register' ), 20 );
	}

	public static function register(): void {
		wp_register_script(
			'kac-blocks',
			KAC_URL . 'blocks/blocks.js',
			array( 'wp-blocks', 'wp-element', 'wp-components', 'wp-block-editor', 'wp-server-side-render', 'wp-i18n' ),
			KAC_VERSION,
			true
		);
		$contests = array( array( 'value' => 0, 'label' => '— выберите конкурс —' ) );
		foreach ( get_posts( array( 'post_type' => 'kac_contest', 'post_status' => 'publish', 'numberposts' => 100 ) ) as $c ) {
			$contests[] = array( 'value' => $c->ID, 'label' => get_the_title( $c ) );
		}
		wp_localize_script( 'kac-blocks', 'KACBlocks', array( 'contests' => $contests, 'blocks' => self::BLOCKS ) );

		foreach ( self::BLOCKS as $slug => $def ) {
			register_block_type(
				'kids-art-contest/' . $slug,
				array(
					'api_version'     => 3,
					'title'           => $def['title'],
					'category'        => 'widgets',
					'icon'            => 'art',
					'attributes'      => array( 'contestId' => array( 'type' => 'number', 'default' => 0 ) ),
					'editor_script'   => 'kac-blocks',
					'render_callback' => static function ( $attrs ) use ( $def ) {
						$id = isset( $attrs['contestId'] ) ? absint( $attrs['contestId'] ) : 0;
						if ( ! $id ) {
							return current_user_can( 'edit_posts' ) ? '<p>' . esc_html__( 'Выберите конкурс в настройках блока.', 'kids-art-contest' ) . '</p>' : '';
						}
						return do_shortcode( '[' . $def['shortcode'] . ' id="' . $id . '"]' );
					},
				)
			);
		}
	}
}
