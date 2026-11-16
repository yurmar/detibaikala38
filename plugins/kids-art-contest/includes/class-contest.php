<?php
defined( 'ABSPATH' ) || exit;

/**
 * Помощники для данных конкурса.
 */
class KAC_Contest {

	public static function min_age( int $id ): int {
		$v = get_post_meta( $id, '_kac_min_age', true );
		return '' === $v ? 3 : (int) $v;
	}

	public static function max_age( int $id ): int {
		$v = get_post_meta( $id, '_kac_max_age', true );
		return '' === $v ? 17 : (int) $v;
	}

	/**
	 * Возрастные группы [{key,label,from,to}]. Если не заданы — одна общая.
	 */
	public static function groups( int $id ): array {
		$raw    = get_post_meta( $id, '_kac_age_groups', true );
		$groups = is_string( $raw ) && '' !== $raw ? json_decode( $raw, true ) : array();
		if ( ! is_array( $groups ) || ! $groups ) {
			return array(
				array(
					'key'   => 'all',
					'label' => __( 'Все возрасты', 'kids-art-contest' ),
					'from'  => self::min_age( $id ),
					'to'    => self::max_age( $id ),
				),
			);
		}
		return array_values( $groups );
	}

	public static function group_for_age( int $id, int $age ): ?array {
		foreach ( self::groups( $id ) as $g ) {
			if ( $age >= (int) $g['from'] && $age <= (int) $g['to'] ) {
				return $g;
			}
		}
		return null;
	}

	public static function group_label( int $id, string $key ): string {
		foreach ( self::groups( $id ) as $g ) {
			if ( $g['key'] === $key ) {
				return (string) $g['label'];
			}
		}
		return $key;
	}

	/**
	 * Проверка групп: не пересекаются и покрывают весь диапазон.
	 *
	 * @return true|string true или текст ошибки.
	 */
	public static function validate_groups( array $groups, int $min, int $max ) {
		if ( $min > $max ) {
			return __( 'Минимальный возраст больше максимального.', 'kids-art-contest' );
		}
		if ( ! $groups ) {
			return true;
		}
		usort( $groups, static fn( $a, $b ) => (int) $a['from'] <=> (int) $b['from'] );
		$expected = $min;
		foreach ( $groups as $g ) {
			if ( (int) $g['from'] > (int) $g['to'] ) {
				return sprintf( /* translators: %s: group name */ __( 'Группа «%s»: «от» больше «до».', 'kids-art-contest' ), $g['label'] );
			}
			if ( (int) $g['from'] < $expected ) {
				return sprintf( __( 'Группа «%s» пересекается с предыдущей.', 'kids-art-contest' ), $g['label'] );
			}
			if ( (int) $g['from'] > $expected ) {
				return sprintf( __( 'Возраст %d не входит ни в одну группу.', 'kids-art-contest' ), $expected );
			}
			$expected = (int) $g['to'] + 1;
		}
		if ( $expected - 1 !== $max ) {
			return sprintf( __( 'Группы должны доходить до максимального возраста (%d).', 'kids-art-contest' ), $max );
		}
		return true;
	}

	/**
	 * Правила загрузки: глобальные, переопределённые конкурсом.
	 */
	public static function upload_rules( int $id ): array {
		$rules = array(
			'max_bytes' => (int) kac_setting( 'upload_max_bytes' ),
			'min_w'     => (int) kac_setting( 'upload_min_w' ),
			'min_h'     => (int) kac_setting( 'upload_min_h' ),
			'max_w'     => (int) kac_setting( 'upload_max_w' ),
			'max_h'     => (int) kac_setting( 'upload_max_h' ),
			'formats'   => (array) kac_setting( 'upload_formats' ),
		);
		$raw = get_post_meta( $id, '_kac_upload_rules', true );
		$own = is_string( $raw ) && '' !== $raw ? json_decode( $raw, true ) : array();
		if ( is_array( $own ) ) {
			foreach ( array( 'max_bytes', 'min_w', 'min_h', 'max_w', 'max_h' ) as $k ) {
				if ( ! empty( $own[ $k ] ) ) {
					$rules[ $k ] = (int) $own[ $k ];
				}
			}
			if ( ! empty( $own['formats'] ) ) {
				$rules['formats'] = (array) $own['formats'];
			}
		}
		return (array) apply_filters( 'kac_upload_rules', $rules, $id );
	}

	public static function ticket( int $contest_id, int $number ): string {
		$prefix = (string) get_post_meta( $contest_id, '_kac_ticket_prefix', true );
		$prefix = '' === $prefix ? 'ЗС' : $prefix;
		return sprintf( '%s-%04d', $prefix, $number );
	}

	/**
	 * Следующий порядковый номер работы в конкурсе (под блокировкой).
	 */
	public static function next_number( int $contest_id ): int {
		global $wpdb;
		$lock = 'kac_seq_' . $contest_id;
		$wpdb->query( $wpdb->prepare( 'SELECT GET_LOCK(%s, 5)', $lock ) );
		$n = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT MAX(CAST(m.meta_value AS UNSIGNED)) FROM {$wpdb->postmeta} m
				 JOIN {$wpdb->posts} p ON p.ID = m.post_id
				 WHERE m.meta_key = '_kac_number' AND p.post_parent = %d AND p.post_type = 'kac_work'",
				$contest_id
			)
		) + 1;
		return $n; // Блокировка снимается в release_number() после создания записи.
	}

	public static function release_number( int $contest_id ): void {
		global $wpdb;
		$wpdb->query( $wpdb->prepare( 'SELECT RELEASE_LOCK(%s)', 'kac_seq_' . $contest_id ) );
	}

	public static function show_votes( int $contest_id ): bool {
		$mode = (string) get_post_meta( $contest_id, '_kac_show_votes', true );
		$mode = $mode ? $mode : 'always';
		if ( 'always' === $mode ) {
			return true;
		}
		if ( 'after_end' === $mode ) {
			return KAC_Phase::RESULTS === KAC_Phase::get( $contest_id );
		}
		return false;
	}

	public static function show_leaders( int $contest_id ): bool {
		$v = get_post_meta( $contest_id, '_kac_show_leaders', true );
		return '' === $v ? true : (bool) $v;
	}

	public static function prize_places( int $contest_id ): int {
		$v = (int) get_post_meta( $contest_id, '_kac_prize_places', true );
		return $v > 0 ? $v : 3;
	}

	public static function max_works( int $contest_id ): int {
		$v = (int) get_post_meta( $contest_id, '_kac_max_works', true );
		return $v > 0 ? $v : 1;
	}

	public static function work_counts( int $contest_id ): array {
		global $wpdb;
		$rows = $wpdb->get_results(
			$wpdb->prepare( "SELECT post_status, COUNT(*) c FROM {$wpdb->posts} WHERE post_type='kac_work' AND post_parent=%d GROUP BY post_status", $contest_id ),
			OBJECT_K
		);
		$out = array();
		foreach ( array( 'kac_pending', 'publish', 'kac_rejected', 'kac_disqualified' ) as $s ) {
			$out[ $s ] = isset( $rows[ $s ] ) ? (int) $rows[ $s ]->c : 0;
		}
		return $out;
	}
}
