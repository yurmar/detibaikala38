<?php
defined( 'ABSPATH' ) || exit;

/**
 * REST API /wp-json/kac/v1
 */
class KAC_REST {

	const NS = 'kac/v1';

	public static function init(): void {
		add_action( 'rest_api_init', array( __CLASS__, 'routes' ) );
	}

	public static function routes(): void {
		$pub = '__return_true';

		register_rest_route( self::NS, '/token', array( 'methods' => 'GET', 'callback' => array( __CLASS__, 'token' ), 'permission_callback' => $pub ) );
		register_rest_route( self::NS, '/captcha', array( 'methods' => 'GET', 'callback' => array( __CLASS__, 'captcha' ), 'permission_callback' => $pub ) );
		register_rest_route( self::NS, '/contests/(?P<id>\d+)', array( 'methods' => 'GET', 'callback' => array( __CLASS__, 'contest' ), 'permission_callback' => $pub ) );
		register_rest_route( self::NS, '/contests/(?P<id>\d+)/works', array( 'methods' => 'GET', 'callback' => array( __CLASS__, 'works' ), 'permission_callback' => $pub ) );
		register_rest_route( self::NS, '/contests/(?P<id>\d+)/leaders', array( 'methods' => 'GET', 'callback' => array( __CLASS__, 'leaders' ), 'permission_callback' => $pub ) );
		register_rest_route( self::NS, '/contests/(?P<id>\d+)/my-votes', array( 'methods' => 'GET', 'callback' => array( __CLASS__, 'my_votes' ), 'permission_callback' => $pub ) );
		register_rest_route( self::NS, '/works', array( 'methods' => 'POST', 'callback' => array( 'KAC_Submission', 'handle' ), 'permission_callback' => $pub ) );
		register_rest_route( self::NS, '/works/(?P<id>\d+)/vote', array( 'methods' => 'POST', 'callback' => array( 'KAC_Voting', 'cast' ), 'permission_callback' => $pub ) );

		$can = static fn( string $cap ) => static fn() => current_user_can( $cap );
		register_rest_route( self::NS, '/admin/works/(?P<id>\d+)/moderate', array( 'methods' => 'POST', 'callback' => array( __CLASS__, 'moderate' ), 'permission_callback' => $can( 'kac_moderate_works' ) ) );
		register_rest_route( self::NS, '/admin/works/(?P<id>\d+)', array( 'methods' => 'PATCH', 'callback' => array( __CLASS__, 'patch_work' ), 'permission_callback' => $can( 'kac_edit_works' ) ) );
		register_rest_route( self::NS, '/admin/works/(?P<id>\d+)/rotate', array( 'methods' => 'POST', 'callback' => array( __CLASS__, 'rotate' ), 'permission_callback' => $can( 'kac_edit_works' ) ) );
		register_rest_route( self::NS, '/admin/votes/annul', array( 'methods' => 'POST', 'callback' => array( __CLASS__, 'annul' ), 'permission_callback' => $can( 'kac_annul_votes' ) ) );
		register_rest_route( self::NS, '/admin/votes/restore', array( 'methods' => 'POST', 'callback' => array( __CLASS__, 'restore' ), 'permission_callback' => $can( 'kac_annul_votes' ) ) );
		register_rest_route( self::NS, '/admin/alerts/(?P<id>\d+)/dismiss', array( 'methods' => 'POST', 'callback' => array( __CLASS__, 'alert_dismiss' ), 'permission_callback' => $can( 'kac_view_votes' ) ) );
		register_rest_route( self::NS, '/admin/alerts/(?P<id>\d+)/annul', array( 'methods' => 'POST', 'callback' => array( __CLASS__, 'alert_annul' ), 'permission_callback' => $can( 'kac_annul_votes' ) ) );
		register_rest_route( self::NS, '/admin/contests/(?P<id>\d+)/results/recalc', array( 'methods' => 'POST', 'callback' => array( __CLASS__, 'recalc' ), 'permission_callback' => $can( 'kac_publish_results' ) ) );
		register_rest_route( self::NS, '/admin/contests/(?P<id>\d+)/results/publish', array( 'methods' => 'POST', 'callback' => array( __CLASS__, 'publish' ), 'permission_callback' => $can( 'kac_publish_results' ) ) );
		register_rest_route( self::NS, '/admin/contests/(?P<id>\d+)/export', array( 'methods' => 'GET', 'callback' => array( __CLASS__, 'export' ), 'permission_callback' => $can( 'kac_export' ) ) );
	}

	private static function nocache( $data ) {
		$res = rest_ensure_response( $data );
		$res->header( 'Cache-Control', 'no-store, max-age=0' );
		return $res;
	}

	public static function token() {
		return self::nocache( array( 'nonce' => KAC_Security::make_nonce() ) );
	}

	public static function captcha() {
		return self::nocache( array_merge( KAC_Captcha::client_config(), 'builtin' === KAC_Captcha::provider() ? KAC_Security::captcha_challenge() : array() ) );
	}

	public static function contest( WP_REST_Request $req ) {
		$id = absint( $req['id'] );
		$p  = get_post( $id );
		if ( ! $p || 'kac_contest' !== $p->post_type || 'publish' !== $p->post_status ) {
			return new WP_Error( 'not_found', __( 'Конкурс не найден.', 'kids-art-contest' ), array( 'status' => 404 ) );
		}
		$d = KAC_Phase::dates( $id );
		return self::nocache(
			array(
				'id'         => $id,
				'title'      => get_the_title( $id ),
				'phase'      => KAC_Phase::get( $id ),
				'dates'      => $d,
				'groups'     => KAC_Contest::groups( $id ),
				'can_vote'   => KAC_Phase::can_vote( $id ),
				'can_submit' => KAC_Phase::can_submit( $id ),
				'prizes'     => KAC_Contest::prize_places( $id ),
			)
		);
	}

	private static function public_contest( int $id ): bool {
		$p = get_post( $id );
		return $p && 'kac_contest' === $p->post_type && 'publish' === $p->post_status;
	}

	public static function works( WP_REST_Request $req ) {
		$id = absint( $req['id'] );
		if ( ! self::public_contest( $id ) ) {
			return new WP_Error( 'not_found', __( 'Конкурс не найден.', 'kids-art-contest' ), array( 'status' => 404 ) );
		}
		$r = KAC_Gallery::query(
			$id,
			array(
				'group' => (string) $req->get_param( 'group' ),
				'sort'  => (string) $req->get_param( 'sort' ),
				'page'  => (int) $req->get_param( 'page' ),
				's'     => (string) $req->get_param( 's' ),
				'seed'  => (int) $req->get_param( 'seed' ),
			)
		);
		$phase = KAC_Phase::get( $id );
		return self::nocache(
			array(
				'html'  => KAC_Gallery::cards_html( $r['posts'], $id, KAC_Phase::RESULTS !== $phase ),
				'total' => $r['total'],
				'pages' => $r['pages'],
				'page'  => max( 1, (int) $req->get_param( 'page' ) ),
			)
		);
	}

	public static function leaders( WP_REST_Request $req ) {
		$id = absint( $req['id'] );
		if ( ! self::public_contest( $id ) || ! KAC_Contest::show_votes( $id ) || ! KAC_Contest::show_leaders( $id ) ) {
			return self::nocache( array( 'items' => array() ) );
		}
		$items = array();
		foreach ( KAC_Gallery::leaders( $id, (string) $req->get_param( 'group' ), 5 ) as $w ) {
			$items[] = KAC_Gallery::work_data( $w );
		}
		return self::nocache( array( 'items' => $items ) );
	}

	public static function my_votes( WP_REST_Request $req ) {
		return self::nocache( array( 'works' => KAC_Voting::my_votes( absint( $req['id'] ), KAC_Voting::voter_token( $req ) ) ) );
	}

	/* ---- Admin ---- */

	public static function moderate( WP_REST_Request $req ) {
		$res = KAC_Moderation::apply(
			absint( $req['id'] ),
			(string) $req->get_param( 'action' ),
			array(
				'reason'          => sanitize_textarea_field( (string) $req->get_param( 'reason' ) ),
				'allow_resubmit' => (bool) $req->get_param( 'allow_resubmit' ),
				'notify'          => null === $req->get_param( 'notify' ) ? true : (bool) $req->get_param( 'notify' ),
			)
		);
		return is_wp_error( $res ) ? $res : array( 'ok' => true, 'status' => $res );
	}

	public static function patch_work( WP_REST_Request $req ) {
		$id = absint( $req['id'] );
		$w  = get_post( $id );
		if ( ! $w || 'kac_work' !== $w->post_type ) {
			return new WP_Error( 'not_found', 'Not found', array( 'status' => 404 ) );
		}
		$upd = array( 'ID' => $id );
		if ( null !== $req->get_param( 'title' ) ) {
			$upd['post_title'] = sanitize_text_field( (string) $req->get_param( 'title' ) );
		}
		if ( null !== $req->get_param( 'description' ) ) {
			$upd['post_content'] = sanitize_textarea_field( (string) $req->get_param( 'description' ) );
		}
		wp_update_post( $upd );
		if ( null !== $req->get_param( 'technique' ) ) {
			update_post_meta( $id, '_kac_technique', sanitize_text_field( (string) $req->get_param( 'technique' ) ) );
		}
		if ( null !== $req->get_param( 'note' ) ) {
			update_post_meta( $id, '_kac_note', sanitize_textarea_field( (string) $req->get_param( 'note' ) ) );
		}
		KAC_Log::add( 'work', $id, 'edited', array_diff_key( $upd, array( 'ID' => 1 ) ) );
		return array( 'ok' => true );
	}

	public static function rotate( WP_REST_Request $req ) {
		$id    = absint( $req['id'] );
		if ( 'kac_work' !== get_post_type( $id ) ) {
			return new WP_Error( 'not_found', 'Not found', array( 'status' => 404 ) );
		}
		$angle = 'left' === $req->get_param( 'dir' ) ? 90 : -90;
		$ok    = KAC_Image::rotate( $id, $angle );
		KAC_Log::add( 'work', $id, 'rotated' );
		return array( 'ok' => $ok, 'thumb' => get_the_post_thumbnail_url( $id, 'kac_thumb' ) . '?v=' . time() );
	}

	public static function annul( WP_REST_Request $req ) {
		return array( 'ok' => true, 'changed' => KAC_Voting::set_status( (array) $req->get_param( 'ids' ), 'annulled' ) );
	}

	public static function restore( WP_REST_Request $req ) {
		return array( 'ok' => true, 'changed' => KAC_Voting::set_status( (array) $req->get_param( 'ids' ), 'valid' ) );
	}

	public static function alert_dismiss( WP_REST_Request $req ) {
		global $wpdb;
		$wpdb->update( $wpdb->prefix . 'kac_alerts', array( 'status' => 'read' ), array( 'id' => absint( $req['id'] ) ) );
		return array( 'ok' => true );
	}

	public static function alert_annul( WP_REST_Request $req ) {
		global $wpdb;
		$a = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}kac_alerts WHERE id = %d", absint( $req['id'] ) ) );
		if ( ! $a ) {
			return new WP_Error( 'not_found', 'Not found', array( 'status' => 404 ) );
		}
		$d    = json_decode( (string) $a->data, true ) ?: array();
		$mins = max( 10, (int) ( $d['minutes'] ?? 10 ) );
		$ids  = $wpdb->get_col( $wpdb->prepare( 'SELECT id FROM ' . KAC_Voting::table() . " WHERE work_id = %d AND status <> 'annulled' AND created_at > %s", $a->work_id, gmdate( 'Y-m-d H:i:s', strtotime( $a->created_at . ' UTC' ) - $mins * 60 - 600 ) ) );
		$n    = KAC_Voting::set_status( $ids, 'annulled' );
		$wpdb->update( $wpdb->prefix . 'kac_alerts', array( 'status' => 'read' ), array( 'id' => (int) $a->id ) );
		return array( 'ok' => true, 'changed' => $n );
	}

	private static function contest_or_404( int $id ) {
		return 'kac_contest' === get_post_type( $id ) ? true : new WP_Error( 'not_found', 'Not found', array( 'status' => 404 ) );
	}

	public static function recalc( WP_REST_Request $req ) {
		$ok = self::contest_or_404( absint( $req['id'] ) );
		if ( is_wp_error( $ok ) ) {
			return $ok;
		}
		$s = KAC_Results::snapshot( absint( $req['id'] ) );
		return array( 'ok' => true, 'generated' => $s['generated'] );
	}

	public static function publish( WP_REST_Request $req ) {
		$ok = self::contest_or_404( absint( $req['id'] ) );
		if ( is_wp_error( $ok ) ) {
			return $ok;
		}
		KAC_Results::publish( absint( $req['id'] ), null === $req->get_param( 'notify' ) ? true : (bool) $req->get_param( 'notify' ) );
		return array( 'ok' => true );
	}

	public static function export( WP_REST_Request $req ) {
		$ok = self::contest_or_404( absint( $req['id'] ) );
		if ( is_wp_error( $ok ) ) {
			return $ok;
		}
		KAC_Results::download( absint( $req['id'] ), 'xlsx' === $req->get_param( 'format' ) ? 'xlsx' : 'csv' );
		return null;
	}
}
