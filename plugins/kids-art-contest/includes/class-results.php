<?php
defined( 'ABSPATH' ) || exit;

/**
 * Итоги: подсчёт мест, снимок, публикация, экспорт.
 */
class KAC_Results {

	public static function init(): void {
		add_action( 'kac_finalize_contest', array( __CLASS__, 'finalize' ) );
		add_action( 'kac_phase_change', array( __CLASS__, 'on_phase_change' ) );
		add_action( 'kac_tick', array( __CLASS__, 'tick' ) );
	}

	/**
	 * Расчёт рейтинга по группам (без сохранения).
	 *
	 * @return array groups => [ key => [label, items[ [work_id, votes, place] ] ] ]
	 */
	public static function compute( int $contest_id ): array {
		global $wpdb;
		$scope = '';
		$tie   = (string) get_post_meta( $contest_id, '_kac_tie_mode', true );
		$tie   = $tie ? $tie : (string) kac_setting( 'tie_mode', 'earlier_reached' );

		$works = get_posts(
			array(
				'post_type'   => 'kac_work',
				'post_status' => 'publish',
				'post_parent' => $contest_id,
				'numberposts' => -1,
				'orderby'     => 'ID',
				'order'       => 'ASC',
			)
		);

		$rows = array();
		foreach ( $works as $w ) {
			$votes = (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM ' . KAC_Voting::table() . " WHERE work_id = %d AND status <> 'annulled'", $w->ID ) );
			$reach = '';
			if ( $votes > 0 && 'earlier_reached' === $tie ) {
				$reach = (string) $wpdb->get_var( $wpdb->prepare( 'SELECT created_at FROM ' . KAC_Voting::table() . " WHERE work_id = %d AND status <> 'annulled' ORDER BY created_at ASC, id ASC LIMIT 1 OFFSET %d", $w->ID, $votes - 1 ) );
			}
			$rows[] = array(
				'work_id' => $w->ID,
				'group'   => 'overall' === $scope ? 'all' : (string) get_post_meta( $w->ID, '_kac_group', true ),
				'votes'   => $votes,
				'reach'   => $reach,
				'sent'    => $w->post_date_gmt,
			);
		}

		$groups = array();
		foreach ( $rows as $r ) {
			$groups[ $r['group'] ][] = $r;
		}

		$out = array();
		foreach ( $groups as $key => $items ) {
			usort(
				$items,
				static function ( $a, $b ) use ( $tie ) {
					if ( $a['votes'] !== $b['votes'] ) {
						return $b['votes'] <=> $a['votes'];
					}
					if ( 'share' === $tie ) {
						return 0;
					}
					return 'earlier_sent' === $tie ? strcmp( $a['sent'], $b['sent'] ) : strcmp( $a['reach'], $b['reach'] );
				}
			);
			$place = 0;
			$prev  = null;
			$list  = array();
			foreach ( $items as $i => $it ) {
				if ( 'share' === $tie && null !== $prev && $prev === $it['votes'] ) {
					$p = $place; // Делят место.
				} else {
					$p = 'share' === $tie ? $i + 1 : $i + 1;
				}
				$place = $p;
				$prev  = $it['votes'];
				$list[] = array( 'work_id' => $it['work_id'], 'votes' => $it['votes'], 'place' => $p );
			}
			$out[ $key ] = array(
				'label' => 'all' === $key ? __( 'Общий зачёт', 'kids-art-contest' ) : KAC_Contest::group_label( $contest_id, (string) $key ),
				'items' => $list,
			);
		}

		// Порядок групп — как в настройках конкурса.
		$ordered = array();
		foreach ( KAC_Contest::groups( $contest_id ) as $g ) {
			if ( isset( $out[ $g['key'] ] ) ) {
				$ordered[ $g['key'] ] = $out[ $g['key'] ];
			}
		}
		foreach ( $out as $k => $v ) {
			if ( ! isset( $ordered[ $k ] ) ) {
				$ordered[ $k ] = $v;
			}
		}
		return (array) apply_filters( 'kac_results_computed', $ordered, $contest_id );
	}

	/**
	 * Снимок итогов в мету конкурса.
	 */
	public static function snapshot( int $contest_id ): array {
		$groups = self::compute( $contest_id );
		$snap   = array( 'generated' => time(), 'groups' => $groups );
		update_post_meta( $contest_id, '_kac_results', wp_json_encode( $snap ) );
		delete_post_meta( $contest_id, '_kac_results_manual' );
		return $snap;
	}

	public static function get_snapshot( int $contest_id ): ?array {
		$raw = get_post_meta( $contest_id, '_kac_results', true );
		$s   = is_string( $raw ) && '' !== $raw ? json_decode( $raw, true ) : null;
		return is_array( $s ) ? $s : null;
	}

	/**
	 * Ручная правка порядка: передвинуть работу в группе на шаг вверх/вниз.
	 */
	public static function move( int $contest_id, string $group, int $work_id, int $dir ): void {
		$snap = self::get_snapshot( $contest_id );
		if ( ! $snap || empty( $snap['groups'][ $group ] ) ) {
			return;
		}
		$items = &$snap['groups'][ $group ]['items'];
		$idx   = array_search( $work_id, array_column( $items, 'work_id' ), true );
		$to    = $idx + $dir;
		if ( false === $idx || $to < 0 || $to >= count( $items ) ) {
			return;
		}
		$tmp           = $items[ $idx ];
		$items[ $idx ] = $items[ $to ];
		$items[ $to ]  = $tmp;
		foreach ( $items as $i => &$it ) {
			$it['place'] = $i + 1;
		}
		unset( $it );
		update_post_meta( $contest_id, '_kac_results', wp_json_encode( $snap ) );
	}

	public static function publish( int $contest_id, bool $notify = true ): void {
		if ( ! self::get_snapshot( $contest_id ) ) {
			self::snapshot( $contest_id );
		}
		update_post_meta( $contest_id, '_kac_results_published', time() );
		KAC_Log::add( 'contest', $contest_id, 'results_published' );
		do_action( 'kac_results_published', $contest_id );
		kac_purge_page_cache( $contest_id );

		if ( $notify ) {
			self::notify_winners( $contest_id );
		}
	}

	public static function notify_winners( int $contest_id ): void {
		$snap   = self::get_snapshot( $contest_id );
		$places = KAC_Contest::prize_places( $contest_id );
		if ( ! $snap ) {
			return;
		}
		$all = array();
		foreach ( $snap['groups'] as $g ) {
			foreach ( $g['items'] as $it ) {
				$all[] = (int) $it['work_id'];
				if ( $it['place'] <= $places && $it['votes'] > 0 ) {
					KAC_Mailer::send( 'winner', (int) $it['work_id'], array( 'place' => $it['place'], 'votes' => $it['votes'] ) );
				}
			}
		}
		if ( kac_setting( 'mail_results_all' ) ) {
			KAC_Mailer::queue_bulk( 'results', $all );
		}
	}

	/* ---- Cron ---- */

	public static function finalize( $contest_id ): void {
		$contest_id = (int) $contest_id;
		if ( ! $contest_id || 'publish' !== get_post_status( $contest_id ) ) {
			return;
		}
		$d   = KAC_Phase::dates( $contest_id );
		$end = max( $d['submit_end'], $d['vote_end'] );
		if ( ! $end || $end > time() || get_post_meta( $contest_id, '_kac_results_published', true ) ) {
			return;
		}
		if ( ! self::get_snapshot( $contest_id ) ) {
			self::snapshot( $contest_id );
			KAC_Log::add( 'contest', $contest_id, 'results_snapshot' );
		}
		if ( 'auto' === ( get_post_meta( $contest_id, '_kac_results_mode', true ) ?: 'auto' ) ) {
			self::publish( $contest_id );
		}
	}

	public static function on_phase_change( $contest_id ): void {
		$contest_id = (int) $contest_id;
		do_action( 'kac_phase_changed', $contest_id, KAC_Phase::get( $contest_id ) );
		self::finalize( $contest_id );
		kac_purge_page_cache( $contest_id );
	}

	/**
	 * Страховка: раз в час добиваем завершившиеся конкурсы без снимка.
	 */
	public static function tick(): void {
		$ids = get_posts( array( 'post_type' => 'kac_contest', 'post_status' => 'publish', 'numberposts' => -1, 'fields' => 'ids' ) );
		foreach ( $ids as $id ) {
			self::finalize( (int) $id );
		}
	}

	/**
	 * Расписание событий по датам конкурса.
	 */
	public static function schedule( int $contest_id ): void {
		wp_clear_scheduled_hook( 'kac_phase_change', array( $contest_id ) );
		wp_clear_scheduled_hook( 'kac_finalize_contest', array( $contest_id ) );
		$d = KAC_Phase::dates( $contest_id );
		foreach ( array( $d['submit_start'], $d['submit_end'], $d['vote_start'], $d['vote_end'] ) as $ts ) {
			if ( $ts > time() ) {
				wp_schedule_single_event( $ts + 5, 'kac_phase_change', array( $contest_id ) );
			}
		}
		$end = max( $d['submit_end'], $d['vote_end'] );
		if ( $end > time() ) {
			wp_schedule_single_event( $end + 10, 'kac_finalize_contest', array( $contest_id ) );
		}
	}

	/* ---- Экспорт ---- */

	public static function export_rows( int $contest_id ): array {
		global $wpdb;
		$rows = array(
			array( '№', 'Заявка', 'Название', 'Статус', 'Группа', 'Возраст', 'Голосов', 'Ребёнок', 'Город', 'Школа/студия', 'Педагог', 'Представитель', 'Кем приходится', 'Email', 'Телефон', 'Дата заявки' ),
		);
		$snap  = self::get_snapshot( $contest_id );
		$place = array();
		if ( $snap ) {
			foreach ( $snap['groups'] as $g ) {
				foreach ( $g['items'] as $it ) {
					$place[ $it['work_id'] ] = $it['place'];
				}
			}
			$rows[0][] = 'Место';
		}
		$works = get_posts( array( 'post_type' => 'kac_work', 'post_status' => array( 'publish', 'kac_pending', 'kac_rejected', 'kac_disqualified' ), 'post_parent' => $contest_id, 'numberposts' => -1, 'orderby' => 'ID', 'order' => 'ASC' ) );
		$labels = array( 'publish' => 'Одобрена', 'kac_pending' => 'На модерации', 'kac_rejected' => 'Отклонена', 'kac_disqualified' => 'Дисквалифицирована' );
		foreach ( $works as $w ) {
			$p = KAC_Participants::get( $w->ID );
			$n = (int) get_post_meta( $w->ID, '_kac_number', true );
			$r = array(
				$n,
				KAC_Contest::ticket( $contest_id, $n ),
				$w->post_title,
				$labels[ $w->post_status ] ?? $w->post_status,
				KAC_Contest::group_label( $contest_id, (string) get_post_meta( $w->ID, '_kac_group', true ) ),
				(int) get_post_meta( $w->ID, '_kac_age', true ),
				(int) get_post_meta( $w->ID, '_kac_votes', true ),
				$p ? trim( "{$p->child_last} {$p->child_first} {$p->child_middle}" ) : '',
				$p ? $p->city : '',
				$p ? $p->school : '',
				$p ? $p->teacher : '',
				$p ? trim( "{$p->parent_last} {$p->parent_first} {$p->parent_middle}" ) : '',
				$p ? $p->parent_relation : '',
				$p ? $p->email : '',
				$p ? '+' . $p->phone : '',
				$w->post_date,
			);
			if ( $snap ) {
				$r[] = $place[ $w->ID ] ?? '';
			}
			$rows[] = $r;
		}
		return $rows;
	}

	public static function download( int $contest_id, string $format ): void {
		$rows = self::export_rows( $contest_id );
		$name = sanitize_file_name( 'kac-' . $contest_id . '-' . gmdate( 'Ymd-His' ) );
		KAC_Log::add( 'contest', $contest_id, 'export', array( 'format' => $format ) );
		if ( 'xlsx' === $format && class_exists( 'ZipArchive' ) ) {
			self::send_xlsx( $rows, $name . '.xlsx' );
		}
		self::send_csv( $rows, $name . '.csv' );
	}

	public static function send_csv( array $rows, string $filename ): void {
		nocache_headers();
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
		$out = fopen( 'php://output', 'w' ); // phpcs:ignore
		fwrite( $out, "\xEF\xBB\xBF" ); // phpcs:ignore -- BOM для Excel.
		foreach ( $rows as $r ) {
			fputcsv( $out, array_map( array( __CLASS__, 'csv_safe' ), $r ), ';' ); // phpcs:ignore
		}
		fclose( $out ); // phpcs:ignore
		exit;
	}

	/** Защита от CSV-инъекций формул. */
	public static function csv_safe( $v ) {
		$v = (string) $v;
		return ( '' !== $v && in_array( $v[0], array( '=', '+', '-', '@' ), true ) && ! is_numeric( $v ) ) ? "'" . $v : $v;
	}

	private static function send_xlsx( array $rows, string $filename ): void {
		$col = static function ( int $i ): string {
			$s = '';
			for ( $i++; $i > 0; $i = intdiv( $i - 1, 26 ) ) {
				$s = chr( 65 + ( $i - 1 ) % 26 ) . $s;
			}
			return $s;
		};
		$xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetData>';
		foreach ( $rows as $ri => $r ) {
			$xml .= '<row r="' . ( $ri + 1 ) . '">';
			foreach ( array_values( $r ) as $ci => $v ) {
				$ref = $col( $ci ) . ( $ri + 1 );
				if ( is_int( $v ) || ( is_numeric( $v ) && ! preg_match( '/^0\d/', (string) $v ) && strlen( (string) $v ) < 12 ) ) {
					$xml .= '<c r="' . $ref . '"><v>' . $v . '</v></c>';
				} else {
					$xml .= '<c r="' . $ref . '" t="inlineStr"><is><t xml:space="preserve">' . htmlspecialchars( (string) $v, ENT_XML1 | ENT_QUOTES, 'UTF-8' ) . '</t></is></c>';
				}
			}
			$xml .= '</row>';
		}
		$xml .= '</sheetData></worksheet>';

		$tmp = wp_tempnam( 'kac-xlsx' );
		$zip = new ZipArchive();
		$zip->open( $tmp, ZipArchive::OVERWRITE );
		$zip->addFromString( '[Content_Types].xml', '<?xml version="1.0" encoding="UTF-8"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/></Types>' );
		$zip->addFromString( '_rels/.rels', '<?xml version="1.0" encoding="UTF-8"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>' );
		$zip->addFromString( 'xl/workbook.xml', '<?xml version="1.0" encoding="UTF-8"?><workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets><sheet name="Работы" sheetId="1" r:id="rId1"/></sheets></workbook>' );
		$zip->addFromString( 'xl/_rels/workbook.xml.rels', '<?xml version="1.0" encoding="UTF-8"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/></Relationships>' );
		$zip->addFromString( 'xl/worksheets/sheet1.xml', $xml );
		$zip->close();

		nocache_headers();
		header( 'Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' );
		header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
		header( 'Content-Length: ' . filesize( $tmp ) );
		readfile( $tmp ); // phpcs:ignore
		@unlink( $tmp ); // phpcs:ignore
		exit;
	}
}
