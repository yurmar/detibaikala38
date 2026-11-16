<?php
defined( 'ABSPATH' ) || exit;

/**
 * Выборка и отрисовка работ для галереи.
 */
class KAC_Gallery {

	public static function per_page(): int {
		return max( 1, (int) kac_setting( 'per_page', 12 ) );
	}

	/**
	 * @return array{posts:WP_Post[],total:int,pages:int}
	 */
	public static function query( int $contest_id, array $a = array() ): array {
		$a = wp_parse_args(
			$a,
			array( 'group' => '', 'sort' => 'new', 'page' => 1, 's' => '', 'seed' => 1, 'per_page' => self::per_page() )
		);
		$votes_visible = KAC_Contest::show_votes( $contest_id );
		$sort          = in_array( $a['sort'], array( 'new', 'popular', 'random' ), true ) ? $a['sort'] : 'new';
		if ( 'popular' === $sort && ! $votes_visible ) {
			$sort = 'new';
		}

		$args = array(
			'post_type'      => 'kac_work',
			'post_status'    => 'publish',
			'post_parent'    => $contest_id,
			'posts_per_page' => (int) $a['per_page'],
			'paged'          => max( 1, (int) $a['page'] ),
			'no_found_rows'  => false,
		);
		$meta = array();
		if ( '' !== $a['group'] ) {
			$meta[] = array( 'key' => '_kac_group', 'value' => sanitize_key( $a['group'] ) );
		}
		$s = trim( (string) $a['s'] );
		if ( '' !== $s ) {
			$num = ltrim( preg_replace( '/\D+/', '', $s ), '0' );
			if ( '' !== $num && preg_match( '/^[№#]?\s*\d+$/u', $s ) ) {
				$meta[] = array( 'key' => '_kac_number', 'value' => (int) $num, 'type' => 'NUMERIC' );
			} else {
				$args['s'] = $s;
			}
		}
		if ( $meta ) {
			$args['meta_query'] = $meta; // phpcs:ignore WordPress.DB.SlowDBQuery
		}

		if ( 'popular' === $sort ) {
			$args['meta_key'] = '_kac_votes'; // phpcs:ignore WordPress.DB.SlowDBQuery
			$args['orderby']  = array( 'meta_value_num' => 'DESC', 'ID' => 'ASC' );
		} elseif ( 'random' === $sort ) {
			$seed = absint( $a['seed'] );
			add_filter(
				'posts_orderby',
				$cb = static function () use ( $seed ) {
					return 'RAND(' . $seed . ')';
				}
			);
		} else {
			$args['orderby'] = array( 'date' => 'DESC', 'ID' => 'DESC' );
		}

		$args  = (array) apply_filters( 'kac_gallery_query_args', $args, $contest_id );
		$query = new WP_Query( $args );
		if ( isset( $cb ) ) {
			remove_filter( 'posts_orderby', $cb );
		}
		return array(
			'posts' => $query->posts,
			'total' => (int) $query->found_posts,
			'pages' => (int) $query->max_num_pages,
		);
	}

	public static function work_data( WP_Post $w ): array {
		$cid   = (int) $w->post_parent;
		$thumb = get_the_post_thumbnail_url( $w->ID, 'kac_thumb' ) ?: get_the_post_thumbnail_url( $w->ID, 'medium' );
		$large = get_the_post_thumbnail_url( $w->ID, 'kac_large' ) ?: get_the_post_thumbnail_url( $w->ID, 'full' );
		$age   = (int) get_post_meta( $w->ID, '_kac_age', true );
		$gkey  = (string) get_post_meta( $w->ID, '_kac_group', true );
		return array(
			'id'     => $w->ID,
			'number' => (int) get_post_meta( $w->ID, '_kac_number', true ),
			'title'  => get_the_title( $w ),
			'desc'   => $w->post_content,
			'name'   => (string) get_post_meta( $w->ID, '_kac_public_name', true ),
			'age'    => $age,
			'group'  => KAC_Contest::group_label( $cid, $gkey ),
			'thumb'  => $thumb,
			'large'  => $large,
			'votes'  => KAC_Contest::show_votes( $cid ) ? (int) get_post_meta( $w->ID, '_kac_votes', true ) : null,
			'url'    => get_permalink( $w ),
			'tech'   => (string) get_post_meta( $w->ID, '_kac_technique', true ),
		);
	}

	/**
	 * Состояние кнопки голосования: open | soon | closed | none.
	 */
	public static function vote_state( int $contest_id ): array {
		if ( KAC_Phase::can_vote( $contest_id ) ) {
			return array( 'open', '' );
		}
		$d   = KAC_Phase::dates( $contest_id );
		$now = time();
		if ( $d['vote_start'] && $now < $d['vote_start'] ) {
			return array( 'soon', sprintf( __( 'Голосование с %s', 'kids-art-contest' ), kac_date( $d['vote_start'], 'j F' ) ) );
		}
		if ( $d['vote_end'] && $now >= $d['vote_end'] ) {
			return array( 'closed', __( 'Голосование завершено', 'kids-art-contest' ) );
		}
		return array( 'none', '' );
	}

	public static function card( WP_Post $w, array $state, bool $show_btn = true ): string {
		$d = self::work_data( $w );
		ob_start();
		kac_template( 'card', array( 'd' => $d, 'vote' => $show_btn ? $state[0] : 'none', 'vote_note' => $state[1] ) );
		return (string) ob_get_clean();
	}

	public static function cards_html( array $posts, int $contest_id, bool $vote_btn = true ): string {
		$state = self::vote_state( $contest_id );
		$out   = '';
		foreach ( $posts as $w ) {
			$out .= self::card( $w, $state, $vote_btn );
		}
		return $out;
	}

	/**
	 * Топ-N работ по голосам.
	 */
	public static function leaders( int $contest_id, string $group = '', int $limit = 5 ): array {
		$r = self::query( $contest_id, array( 'group' => $group, 'sort' => 'popular', 'per_page' => $limit ) );
		return $r['posts'];
	}
}
