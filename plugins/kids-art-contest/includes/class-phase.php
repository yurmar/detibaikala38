<?php
/**
 * Фазы конкурса, вычисляются по датам на лету.
 *
 * draft → upcoming → submission → voting → finished → results
 *
 * @package KidsArtContest
 */

defined( 'ABSPATH' ) || exit;

class KAC_Phase {

	const DRAFT      = 'draft';
	const UPCOMING   = 'upcoming';
	const SUBMISSION = 'submission';
	const VOTING     = 'voting';
	const FINISHED   = 'finished';
	const RESULTS    = 'results';

	/**
	 * Даты конкурса (UTC timestamp, 0 — не задана).
	 *
	 * @param int $contest_id ID конкурса.
	 * @return array{submit_start:int,submit_end:int,vote_start:int,vote_end:int,vote_with_submit:bool}
	 */
	public static function dates( int $contest_id ): array {
		$submit_start = (int) get_post_meta( $contest_id, '_kac_submit_start', true );
		$submit_end   = (int) get_post_meta( $contest_id, '_kac_submit_end', true );
		$vote_start   = (int) get_post_meta( $contest_id, '_kac_vote_start', true );
		$vote_end     = (int) get_post_meta( $contest_id, '_kac_vote_end', true );
		$with_submit  = (bool) get_post_meta( $contest_id, '_kac_vote_with_submit', true );

		// Голосование вместе с приёмом: по умолчанию окно голосования = окно приёма.
		if ( $with_submit ) {
			$vote_start = $vote_start ? $vote_start : $submit_start;
		}

		return array(
			'submit_start'     => $submit_start,
			'submit_end'       => $submit_end,
			'vote_start'       => $vote_start,
			'vote_end'         => $vote_end,
			'vote_with_submit' => $with_submit,
		);
	}

	/**
	 * Текущая фаза.
	 *
	 * Промежуток между концом приёма и началом голосования считается
	 * «upcoming» (ждём старта голосования): форма и голоса закрыты.
	 *
	 * @param int      $contest_id ID конкурса.
	 * @param int|null $now        Момент времени (для тестов), по умолчанию сейчас.
	 * @return string
	 */
	public static function get( int $contest_id, ?int $now = null ): string {
		$post = get_post( $contest_id );
		if ( ! $post || 'kac_contest' !== $post->post_type || 'publish' !== $post->post_status ) {
			return self::DRAFT;
		}

		$now = $now ?? time();
		$d   = self::dates( $contest_id );

		$in_submit = $d['submit_start'] && $now >= $d['submit_start'] && ( ! $d['submit_end'] || $now < $d['submit_end'] );
		$in_vote   = $d['vote_start'] && $now >= $d['vote_start'] && ( ! $d['vote_end'] || $now < $d['vote_end'] );

		if ( $in_submit ) {
			return self::SUBMISSION;
		}
		if ( $in_vote ) {
			return self::VOTING;
		}

		$last_end = max( $d['submit_end'], $d['vote_end'] );
		if ( $last_end && $now >= $last_end ) {
			return get_post_meta( $contest_id, '_kac_results_published', true ) ? self::RESULTS : self::FINISHED;
		}

		return self::UPCOMING;
	}

	/**
	 * Открыт ли приём работ.
	 */
	public static function can_submit( int $contest_id, ?int $now = null ): bool {
		return self::SUBMISSION === self::get( $contest_id, $now );
	}

	/**
	 * Открыто ли голосование (в том числе во время приёма работ).
	 */
	public static function can_vote( int $contest_id, ?int $now = null ): bool {
		$phase = self::get( $contest_id, $now );
		if ( ! in_array( $phase, array( self::SUBMISSION, self::VOTING ), true ) ) {
			return false;
		}
		$now = $now ?? time();
		$d   = self::dates( $contest_id );
		return $d['vote_start'] && $now >= $d['vote_start'] && ( ! $d['vote_end'] || $now < $d['vote_end'] );
	}
}
