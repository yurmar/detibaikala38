<?php
defined( 'ABSPATH' ) || exit;

/**
 * Проверка и обработка загружаемых рисунков.
 */
class KAC_Image {

	const ALLOWED = array(
		'jpg'  => 'image/jpeg',
		'png'  => 'image/png',
		'webp' => 'image/webp',
	);

	public static function init(): void {
		add_action( 'after_setup_theme', array( __CLASS__, 'add_sizes' ) );
		add_action( 'admin_post_kac_original', array( __CLASS__, 'serve_original' ) );
	}

	public static function add_sizes(): void {
		add_image_size( 'kac_thumb', 600, 600, true );
		add_image_size( 'kac_large', 1600, 1600, false );
	}

	/**
	 * Валидация файла из $_FILES.
	 *
	 * @return array|WP_Error [ext, mime, width, height]
	 */
	public static function validate( array $file, array $rules ) {
		if ( empty( $file['tmp_name'] ) || ! apply_filters( 'kac_is_uploaded_file', is_uploaded_file( $file['tmp_name'] ), $file['tmp_name'] ) ) {
			return new WP_Error( 'kac_file_missing', __( 'Файл рисунка не получен.', 'kids-art-contest' ) );
		}
		if ( UPLOAD_ERR_OK !== (int) $file['error'] ) {
			return new WP_Error( 'kac_file_error', __( 'Не удалось загрузить файл. Попробуйте ещё раз.', 'kids-art-contest' ) );
		}
		if ( (int) $file['size'] > $rules['max_bytes'] ) {
			return new WP_Error(
				'kac_file_size',
				sprintf( /* translators: %s: size */ __( 'Файл слишком большой. Максимум — %s.', 'kids-art-contest' ), size_format( $rules['max_bytes'] ) )
			);
		}

		$allowed = array();
		foreach ( $rules['formats'] as $ext ) {
			if ( isset( self::ALLOWED[ $ext ] ) ) {
				$allowed[ $ext ] = self::ALLOWED[ $ext ];
			}
		}
		$mimes = array();
		foreach ( $allowed as $ext => $mime ) {
			$mimes[ 'jpg' === $ext ? 'jpg|jpeg' : $ext ] = $mime;
		}

		$check = wp_check_filetype_and_ext( $file['tmp_name'], $file['name'], $mimes );
		$finfo = function_exists( 'finfo_open' ) ? finfo_open( FILEINFO_MIME_TYPE ) : false;
		$real  = $finfo ? finfo_file( $finfo, $file['tmp_name'] ) : $check['type'];
		if ( $finfo ) {
			finfo_close( $finfo );
		}
		if ( empty( $check['ext'] ) || ! in_array( $real, $allowed, true ) ) {
			return new WP_Error(
				'kac_file_type',
				sprintf( __( 'Допустимые форматы: %s.', 'kids-art-contest' ), strtoupper( implode( ', ', array_keys( $allowed ) ) ) )
			);
		}

		$info = @getimagesize( $file['tmp_name'] ); // phpcs:ignore WordPress.PHP.NoSilencedErrors
		if ( ! $info ) {
			return new WP_Error( 'kac_file_type', __( 'Файл не похож на изображение.', 'kids-art-contest' ) );
		}
		list( $w, $h ) = $info;
		if ( $w < $rules['min_w'] || $h < $rules['min_h'] ) {
			return new WP_Error(
				'kac_file_dims',
				sprintf( __( 'Изображение %1$d×%2$d px — слишком маленькое, нужно от %3$d×%4$d.', 'kids-art-contest' ), $w, $h, $rules['min_w'], $rules['min_h'] )
			);
		}
		if ( $w > $rules['max_w'] || $h > $rules['max_h'] ) {
			return new WP_Error(
				'kac_file_dims',
				sprintf( __( 'Изображение %1$d×%2$d px — слишком большое, нужно до %3$d×%4$d.', 'kids-art-contest' ), $w, $h, $rules['max_w'], $rules['max_h'] )
			);
		}

		return array(
			'ext'    => 'jpeg' === $check['ext'] ? 'jpg' : $check['ext'],
			'mime'   => $real,
			'width'  => $w,
			'height' => $h,
		);
	}

	/**
	 * Закрытая папка для оригиналов.
	 */
	public static function private_dir( int $contest_id ): string {
		$u    = wp_upload_dir();
		$base = trailingslashit( $u['basedir'] ) . 'kac-private';
		$dir  = $base . '/' . $contest_id;
		if ( ! is_dir( $dir ) ) {
			wp_mkdir_p( $dir );
		}
		if ( ! file_exists( $base . '/.htaccess' ) ) {
			file_put_contents( $base . '/.htaccess', "<IfModule mod_authz_core.c>\nRequire all denied\n</IfModule>\n<IfModule !mod_authz_core.c>\nOrder allow,deny\nDeny from all\n</IfModule>\n" ); // phpcs:ignore
			file_put_contents( $base . '/index.php', "<?php // Silence is golden.\n" ); // phpcs:ignore
		}
		return $dir;
	}

	/**
	 * Обрабатывает файл: сохраняет оригинал без EXIF в закрытую папку и создаёт
	 * публичное вложение (kac_thumb / kac_large).
	 *
	 * @return array|WP_Error [attachment_id, original_path, dhash]
	 */
	public static function process( array $file, array $meta, int $contest_id, int $work_id ) {
		require_once ABSPATH . 'wp-admin/includes/image.php';
		require_once ABSPATH . 'wp-admin/includes/file.php';
		wp_raise_memory_limit( 'image' );

		$ext  = $meta['ext'];
		$name = wp_generate_password( 20, false ) . '.' . $ext;
		$dir  = self::private_dir( $contest_id );
		$orig = $dir . '/' . $name;

		// Переносим во временный файл и перекодируем: EXIF исчезает, ориентация применяется.
		$editor = wp_get_image_editor( $file['tmp_name'] );
		if ( is_wp_error( $editor ) ) {
			return new WP_Error( 'kac_image_editor', __( 'Не удалось обработать изображение.', 'kids-art-contest' ) );
		}
		if ( method_exists( $editor, 'maybe_exif_rotate' ) ) {
			$editor->maybe_exif_rotate();
		}
		if ( kac_setting( 'strip_exif' ) && method_exists( $editor, 'strip_meta' ) ) {
			$editor->strip_meta(); // Imagick: убрать EXIF/профили (GD теряет их при перекодировании).
		}
		$editor->set_quality( 92 );
		$saved = $editor->save( $orig, $meta['mime'] );
		if ( is_wp_error( $saved ) ) {
			return new WP_Error( 'kac_image_save', __( 'Не удалось сохранить изображение.', 'kids-art-contest' ) );
		}
		$orig = $saved['path'];

		// Публичная копия: до 1600 px, JPEG/WEBP/PNG как у оригинала.
		$pub_editor = wp_get_image_editor( $orig );
		if ( is_wp_error( $pub_editor ) ) {
			return new WP_Error( 'kac_image_editor', __( 'Не удалось обработать изображение.', 'kids-art-contest' ) );
		}
		$pub_editor->resize( 1600, 1600, false );
		$pub_editor->set_quality( 88 );
		$tmp = wp_tempnam( $name );
		$out = $pub_editor->save( $tmp . '.' . $ext, $meta['mime'] );
		@unlink( $tmp ); // phpcs:ignore
		if ( is_wp_error( $out ) ) {
			return new WP_Error( 'kac_image_save', __( 'Не удалось сохранить изображение.', 'kids-art-contest' ) );
		}

		$upload = wp_upload_bits( 'kac-' . $work_id . '-' . wp_generate_password( 6, false ) . '.' . $ext, null, file_get_contents( $out['path'] ) ); // phpcs:ignore
		@unlink( $out['path'] ); // phpcs:ignore
		if ( ! empty( $upload['error'] ) ) {
			return new WP_Error( 'kac_image_save', $upload['error'] );
		}

		$att_id = wp_insert_attachment(
			array(
				'post_mime_type' => $meta['mime'],
				'post_title'     => 'kac-' . $work_id,
				'post_status'    => 'inherit',
			),
			$upload['file'],
			$work_id
		);
		if ( is_wp_error( $att_id ) || ! $att_id ) {
			return new WP_Error( 'kac_image_save', __( 'Не удалось создать вложение.', 'kids-art-contest' ) );
		}
		wp_update_attachment_metadata( $att_id, wp_generate_attachment_metadata( $att_id, $upload['file'] ) );

		return array(
			'attachment_id' => (int) $att_id,
			'original_path' => $orig,
			'dhash'         => self::dhash( $upload['file'] ), // Публичная копия ≤1600 px: полный оригинал в GD съедает память.
		);
	}

	/**
	 * dHash 64 бита → 16 hex-символов. Пусто, если нет GD.
	 */
	public static function dhash( string $path ): string {
		if ( ! function_exists( 'imagecreatefromstring' ) ) {
			return '';
		}
		$data = file_get_contents( $path ); // phpcs:ignore
		$img  = $data ? @imagecreatefromstring( $data ) : false; // phpcs:ignore
		if ( ! $img ) {
			return '';
		}
		$small = imagecreatetruecolor( 9, 8 );
		imagecopyresampled( $small, $img, 0, 0, 0, 0, 9, 8, imagesx( $img ), imagesy( $img ) );
		imagefilter( $small, IMG_FILTER_GRAYSCALE );
		$bits = '';
		for ( $y = 0; $y < 8; $y++ ) {
			for ( $x = 0; $x < 8; $x++ ) {
				$bits .= ( imagecolorat( $small, $x, $y ) & 0xFF ) > ( imagecolorat( $small, $x + 1, $y ) & 0xFF ) ? '1' : '0';
			}
		}
		$hex = '';
		foreach ( str_split( $bits, 4 ) as $nibble ) {
			$hex .= dechex( bindec( $nibble ) );
		}
		return $hex;
	}

	public static function hamming( string $a, string $b ): int {
		if ( 16 !== strlen( $a ) || 16 !== strlen( $b ) ) {
			return 64;
		}
		$d = 0;
		for ( $i = 0; $i < 16; $i += 4 ) {
			$x  = hexdec( substr( $a, $i, 4 ) ) ^ hexdec( substr( $b, $i, 4 ) );
			$d += substr_count( decbin( $x ), '1' );
		}
		return $d;
	}

	/**
	 * Работы того же конкурса, похожие по dHash.
	 *
	 * @return int[]
	 */
	public static function find_duplicates( int $contest_id, string $dhash, int $exclude = 0 ): array {
		global $wpdb;
		if ( '' === $dhash ) {
			return array();
		}
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT p.ID, m.meta_value h FROM {$wpdb->posts} p JOIN {$wpdb->postmeta} m ON m.post_id = p.ID AND m.meta_key = '_kac_dhash'
				 WHERE p.post_type = 'kac_work' AND p.post_parent = %d AND p.ID <> %d AND p.post_status <> 'trash'",
				$contest_id,
				$exclude
			)
		);
		$out = array();
		foreach ( $rows as $r ) {
			if ( self::hamming( $dhash, (string) $r->h ) <= 5 ) {
				$out[] = (int) $r->ID;
			}
		}
		return $out;
	}

	/**
	 * Поворот оригинала и публичных копий на 90° (вправо при $angle=-90).
	 */
	public static function rotate( int $work_id, int $angle ): bool {
		$orig = (string) get_post_meta( $work_id, '_kac_original_path', true );
		$att  = (int) get_post_thumbnail_id( $work_id );
		$ok   = false;
		foreach ( array_filter( array( $orig, $att ? get_attached_file( $att ) : '' ) ) as $path ) {
			if ( ! file_exists( $path ) ) {
				continue;
			}
			$ed = wp_get_image_editor( $path );
			if ( is_wp_error( $ed ) ) {
				continue;
			}
			$ed->rotate( $angle );
			$res = $ed->save( $path );
			$ok  = ! is_wp_error( $res ) || $ok;
		}
		if ( $att && $ok ) {
			require_once ABSPATH . 'wp-admin/includes/image.php';
			$file = get_attached_file( $att );
			$old  = wp_get_attachment_metadata( $att );
			if ( ! empty( $old['sizes'] ) ) {
				foreach ( $old['sizes'] as $s ) {
					@unlink( dirname( $file ) . '/' . $s['file'] ); // phpcs:ignore
				}
			}
			wp_update_attachment_metadata( $att, wp_generate_attachment_metadata( $att, $file ) );
		}
		return $ok;
	}

	/**
	 * Отдаёт оригинал модератору через PHP.
	 */
	public static function serve_original(): void {
		$work_id = isset( $_GET['work'] ) ? absint( $_GET['work'] ) : 0;
		if ( ! current_user_can( 'kac_moderate_works' ) && ! current_user_can( 'kac_edit_works' ) ) {
			wp_die( esc_html__( 'Нет доступа.', 'kids-art-contest' ), '', array( 'response' => 403 ) );
		}
		check_admin_referer( 'kac_original_' . $work_id );
		$path = (string) get_post_meta( $work_id, '_kac_original_path', true );
		$base = realpath( trailingslashit( wp_upload_dir()['basedir'] ) . 'kac-private' );
		$real = $path ? realpath( $path ) : false;
		if ( ! $real || ! $base || 0 !== strpos( $real, $base ) ) {
			wp_die( esc_html__( 'Файл не найден.', 'kids-art-contest' ), '', array( 'response' => 404 ) );
		}
		$type = wp_check_filetype( $real );
		nocache_headers();
		header( 'Content-Type: ' . ( $type['type'] ?: 'application/octet-stream' ) );
		header( 'Content-Length: ' . filesize( $real ) );
		if ( ! empty( $_GET['download'] ) ) {
			header( 'Content-Disposition: attachment; filename="kac-' . $work_id . '.' . $type['ext'] . '"' );
		}
		readfile( $real ); // phpcs:ignore
		exit;
	}

	public static function original_url( int $work_id, bool $download = false ): string {
		$url = admin_url( 'admin-post.php?action=kac_original&work=' . $work_id . ( $download ? '&download=1' : '' ) );
		return wp_nonce_url( $url, 'kac_original_' . $work_id );
	}
}
