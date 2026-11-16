<?php
defined( 'ABSPATH' ) || exit;

/**
 * Решения модератора по работе.
 */
class KAC_Moderation {

	/**
	 * @param string $action approve|reject|disqualify|pending
	 * @return string|WP_Error новый статус
	 */
	public static function apply( int $work_id, string $action, array $opts = array() ) {
		$w = get_post( $work_id );
		if ( ! $w || 'kac_work' !== $w->post_type ) {
			return new WP_Error( 'not_found', __( 'Работа не найдена.', 'kids-art-contest' ), array( 'status' => 404 ) );
		}
		$map = array(
			'approve'    => 'publish',
			'reject'     => 'kac_rejected',
			'disqualify' => 'kac_disqualified',
			'pending'    => 'kac_pending',
		);
		if ( ! isset( $map[ $action ] ) ) {
			return new WP_Error( 'bad_action', __( 'Неизвестное действие.', 'kids-art-contest' ), array( 'status' => 400 ) );
		}
		if ( 'disqualify' === $action && ! current_user_can( 'kac_moderate_works' ) ) {
			return new WP_Error( 'forbidden', 'Forbidden', array( 'status' => 403 ) );
		}
		$old    = $w->post_status;
		$status = $map[ $action ];
		if ( $old === $status ) {
			return $status;
		}

		wp_update_post( array( 'ID' => $work_id, 'post_status' => $status ) );
		$reason = (string) ( $opts['reason'] ?? '' );
		$notify = ! empty( $opts['notify'] );

		if ( 'reject' === $action || 'disqualify' === $action ) {
			update_post_meta( $work_id, '_kac_reject_reason', $reason );
			update_post_meta( $work_id, '_kac_allow_resubmit', ! empty( $opts['allow_resubmit'] ) && 'reject' === $action ? 1 : 0 );
		}
		KAC_Log::add( 'work', $work_id, $action, array( 'from' => $old, 'reason' => $reason ) );
		// Счётчик голосов — по таблице, если работа вернулась в игру.
		if ( 'approve' === $action ) {
			KAC_Voting::recount( $work_id );
		}

		switch ( $action ) {
			case 'approve':
				do_action( 'kac_work_approved', $work_id );
				if ( $notify ) {
					KAC_Mailer::send( 'approved', $work_id );
				}
				break;
			case 'reject':
				do_action( 'kac_work_rejected', $work_id, $reason );
				if ( $notify ) {
					$extra = array();
					if ( ! empty( $opts['allow_resubmit'] ) ) {
						$extra['resubmit_url'] = KAC_Submission::resubmit_url( $work_id );
					}
					KAC_Mailer::send( 'rejected', $work_id, $extra );
				}
				break;
			case 'disqualify':
				do_action( 'kac_work_disqualified', $work_id, $reason );
				break;
		}
		return $status;
	}
}
