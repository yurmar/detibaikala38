<?php
/**
 * Plugin Name: Deti Baikala Importer
 * Description: Импорт новостей с archive.org в рубрику «Новости» (ID=1)
 * Version: 1.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'DBI_CATEGORY_ID', 1 );
define( 'DBI_BASE_ARCHIVE', 'https://web.archive.org/web/20241214102943' );
define( 'DBI_BASE_URL', 'https://detibaikala.com' );
define( 'DBI_TOTAL_PAGES', 59 );
define( 'DBI_OPTION_URLS', 'dbi_collected_urls' );
define( 'DBI_OPTION_DONE', 'dbi_imported_urls' );
define( 'DBI_OPTION_LOG', 'dbi_import_log' );

// ──────────────────────────────────────────────
// Admin menu
// ──────────────────────────────────────────────

add_action( 'admin_menu', function () {
    add_management_page(
        'Импорт Deti Baikala',
        'Deti Baikala Import',
        'manage_options',
        'dbi-importer',
        'dbi_admin_page'
    );
} );

function dbi_admin_page() {
    $urls      = get_option( DBI_OPTION_URLS, [] );
    $done      = get_option( DBI_OPTION_DONE, [] );
    $log       = get_option( DBI_OPTION_LOG, [] );
    $total     = count( $urls );
    $done_cnt  = count( $done );
    $remaining = array_values( array_diff( $urls, $done ) );
    ?>
    <div class="wrap">
        <h1>Импорт новостей с detibaikala.com</h1>

        <p>
            <strong>Шаг 1:</strong> Собрать все ссылки на статьи (59 страниц категории).<br>
            <strong>Шаг 2:</strong> Импортировать статьи по одной.
        </p>

        <?php if ( $total === 0 ): ?>
            <p>Ссылки ещё не собраны.</p>
            <button id="btn-collect" class="button button-primary">Шаг 1: Собрать ссылки</button>
        <?php else: ?>
            <p>
                Собрано ссылок: <strong><?= $total ?></strong><br>
                Импортировано: <strong><?= $done_cnt ?></strong> / <?= $total ?><br>
                Осталось: <strong><?= count( $remaining ) ?></strong>
            </p>
            <?php if ( count( $remaining ) > 0 ): ?>
                <button id="btn-import" class="button button-primary">Шаг 2: Импортировать</button>
                <button id="btn-stop" class="button" style="display:none">Остановить</button>
            <?php else: ?>
                <p style="color:green"><strong>Импорт завершён!</strong></p>
            <?php endif; ?>
            <button id="btn-reset" class="button button-secondary" style="margin-left:20px">Сбросить всё</button>
        <?php endif; ?>

        <div id="dbi-progress" style="margin-top:15px;display:none">
            <div style="background:#e0e0e0;height:20px;border-radius:4px;width:500px">
                <div id="dbi-bar" style="background:#0073aa;height:20px;border-radius:4px;width:0;transition:width 0.3s"></div>
            </div>
            <p id="dbi-status"></p>
        </div>

        <?php if ( ! empty( $log ) ): ?>
            <h3>Лог (последние 50 записей)</h3>
            <div style="max-height:300px;overflow-y:auto;background:#f9f9f9;padding:10px;font-size:12px;font-family:monospace">
                <?php foreach ( array_slice( array_reverse( $log ), 0, 50 ) as $entry ): ?>
                    <div><?= esc_html( $entry ) ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <script>
    (function($){
        const ajax = '<?= admin_url('admin-ajax.php') ?>';
        const nonce = '<?= wp_create_nonce('dbi_nonce') ?>';
        let running = false;

        $('#btn-collect').on('click', function(){
            $(this).prop('disabled', true).text('Сбор...');
            $('#dbi-progress').show();
            collectPage(1, 0, []);
        });

        function collectPage(page, total, allUrls) {
            $.post(ajax, {action:'dbi_collect_page', nonce, page}, function(r){
                if (!r.success) {
                    $('#dbi-status').text('Ошибка на стр. ' + page + ': ' + r.data);
                    return;
                }
                allUrls = allUrls.concat(r.data.urls);
                total += r.data.urls.length;
                $('#dbi-status').text('Страница ' + page + '/' + <?= DBI_TOTAL_PAGES ?> + ' — найдено ' + r.data.urls.length + ' ссылок');
                updateBar(page, <?= DBI_TOTAL_PAGES ?>);
                if (page < <?= DBI_TOTAL_PAGES ?>) {
                    collectPage(page + 1, total, allUrls);
                } else {
                    $('#dbi-status').text('Готово! Найдено ссылок: ' + allUrls.length + '. Перезагрузите страницу.');
                    location.reload();
                }
            }).fail(function(){
                // retry
                setTimeout(function(){ collectPage(page, total, allUrls); }, 3000);
            });
        }

        // ── Import ──
        let importQueue = <?= json_encode( $remaining ) ?>;
        let totalToImport = importQueue.length;
        let importedCount = <?= $done_cnt ?>;
        let grandTotal = <?= $total ?>;

        $('#btn-import').on('click', function(){
            running = true;
            $(this).hide();
            $('#btn-stop').show();
            $('#dbi-progress').show();
            processNext();
        });

        $('#btn-stop').on('click', function(){
            running = false;
            $(this).hide();
            $('#btn-import').show();
            $('#dbi-status').text('Остановлено. Прогресс сохранён.');
        });

        function processNext() {
            if (!running || importQueue.length === 0) {
                if (importQueue.length === 0) {
                    $('#dbi-status').text('Импорт завершён!');
                    location.reload();
                }
                return;
            }
            const url = importQueue.shift();
            $.post(ajax, {action:'dbi_import_post', nonce, url}, function(r){
                importedCount++;
                const msg = r.success
                    ? '✓ ' + r.data.title
                    : '✗ ' + url + ' → ' + r.data;
                $('#dbi-status').text('[' + importedCount + '/' + grandTotal + '] ' + msg);
                updateBar(importedCount, grandTotal);
                setTimeout(processNext, 800);
            }).fail(function(){
                importQueue.unshift(url);
                setTimeout(processNext, 5000);
            });
        }

        function updateBar(cur, max) {
            const pct = Math.round(cur / max * 100);
            $('#dbi-bar').css('width', pct + '%');
        }

        $('#btn-reset').on('click', function(){
            if (!confirm('Сбросить весь прогресс и собранные ссылки?')) return;
            $.post(ajax, {action:'dbi_reset', nonce}, function(){
                location.reload();
            });
        });
    })(jQuery);
    </script>
    <?php
}

// ──────────────────────────────────────────────
// AJAX: Collect URLs from one category page
// ──────────────────────────────────────────────

add_action( 'wp_ajax_dbi_collect_page', function () {
    check_ajax_referer( 'dbi_nonce', 'nonce' );
    if ( ! current_user_can( 'manage_options' ) ) wp_die();

    $page = absint( $_POST['page'] ?? 1 );

    if ( $page === 1 ) {
        $url = DBI_BASE_ARCHIVE . '/https://detibaikala.com/category/news/';
    } else {
        $url = DBI_BASE_ARCHIVE . '/https://detibaikala.com/category/news/page/' . $page . '/';
    }

    $html = dbi_fetch( $url );
    if ( is_wp_error( $html ) ) {
        wp_send_json_error( $html->get_error_message() );
    }

    $links = dbi_extract_article_links( $html );

    // Merge with existing saved URLs (dedup)
    $existing = get_option( DBI_OPTION_URLS, [] );
    $merged   = array_values( array_unique( array_merge( $existing, $links ) ) );
    update_option( DBI_OPTION_URLS, $merged, false );

    wp_send_json_success( [ 'urls' => $links, 'page' => $page ] );
} );

// ──────────────────────────────────────────────
// AJAX: Import one post
// ──────────────────────────────────────────────

add_action( 'wp_ajax_dbi_import_post', function () {
    check_ajax_referer( 'dbi_nonce', 'nonce' );
    if ( ! current_user_can( 'manage_options' ) ) wp_die();

    $url = esc_url_raw( $_POST['url'] ?? '' );
    if ( ! $url ) wp_send_json_error( 'Пустой URL' );

    // Check if already imported
    $done = get_option( DBI_OPTION_DONE, [] );
    if ( in_array( $url, $done, true ) ) {
        wp_send_json_success( [ 'title' => 'Уже импортирован', 'skipped' => true ] );
    }

    $html = dbi_fetch( $url );
    if ( is_wp_error( $html ) ) {
        dbi_log( 'ОШИБКА ЗАГРУЗКИ: ' . $url . ' — ' . $html->get_error_message() );
        wp_send_json_error( $html->get_error_message() );
    }

    $data = dbi_parse_article( $html, $url );
    if ( ! $data ) {
        dbi_log( 'ОШИБКА ПАРСИНГА: ' . $url );
        wp_send_json_error( 'Не удалось распарсить статью' );
    }

    // Check for duplicate by title
    $existing = get_page_by_title( $data['title'], OBJECT, 'post' );
    if ( $existing ) {
        dbi_log( 'ДУБЛЬ: ' . $data['title'] );
        $done[] = $url;
        update_option( DBI_OPTION_DONE, $done, false );
        wp_send_json_success( [ 'title' => $data['title'] . ' (дубль, пропущен)', 'skipped' => true ] );
    }

    // Create post
    $post_id = wp_insert_post( [
        'post_title'    => $data['title'],
        'post_content'  => $data['content'],
        'post_status'   => 'publish',
        'post_date'     => $data['date'],
        'post_category' => [ DBI_CATEGORY_ID ],
        'post_type'     => 'post',
    ], true );

    if ( is_wp_error( $post_id ) ) {
        dbi_log( 'ОШИБКА СОЗДАНИЯ ПОСТА: ' . $data['title'] . ' — ' . $post_id->get_error_message() );
        wp_send_json_error( $post_id->get_error_message() );
    }

    // Upload and set featured image
    if ( ! empty( $data['image_url'] ) ) {
        $attachment_id = dbi_upload_image( $data['image_url'], $post_id, $data['image_name'] );
        if ( $attachment_id && ! is_wp_error( $attachment_id ) ) {
            set_post_thumbnail( $post_id, $attachment_id );
        }
    }

    $done[] = $url;
    update_option( DBI_OPTION_DONE, $done, false );
    dbi_log( 'OK: ' . $data['title'] . ' [post_id=' . $post_id . ']' );

    wp_send_json_success( [ 'title' => $data['title'], 'post_id' => $post_id ] );
} );

// ──────────────────────────────────────────────
// AJAX: Reset
// ──────────────────────────────────────────────

add_action( 'wp_ajax_dbi_reset', function () {
    check_ajax_referer( 'dbi_nonce', 'nonce' );
    if ( ! current_user_can( 'manage_options' ) ) wp_die();

    delete_option( DBI_OPTION_URLS );
    delete_option( DBI_OPTION_DONE );
    delete_option( DBI_OPTION_LOG );

    wp_send_json_success();
} );

// ──────────────────────────────────────────────
// Helpers
// ──────────────────────────────────────────────

/**
 * Fetch URL with appropriate headers for archive.org
 */
function dbi_fetch( string $url ): string|WP_Error {
    $response = wp_remote_get( $url, [
        'timeout'    => 30,
        'user-agent' => 'Mozilla/5.0 (compatible; WordPress importer)',
        'headers'    => [ 'Accept-Encoding' => 'identity' ],
    ] );

    if ( is_wp_error( $response ) ) return $response;

    $code = wp_remote_retrieve_response_code( $response );
    if ( $code !== 200 ) {
        return new WP_Error( 'http_error', 'HTTP ' . $code . ' для ' . $url );
    }

    return wp_remote_retrieve_body( $response );
}

/**
 * Extract article URLs from a category page HTML.
 * Returns full archive.org URLs.
 *
 * Card structure:
 *   <div class="card news-item">
 *     <h5 class="card-title center"><a href="...">Title</a></h5>
 *   </div>
 */
function dbi_extract_article_links( string $html ): array {
    $dom = new DOMDocument();
    @$dom->loadHTML( mb_convert_encoding( $html, 'HTML-ENTITIES', 'UTF-8' ) );
    $xpath = new DOMXPath( $dom );

    $links = [];

    // Primary: title links inside .card-title inside .news-item
    $nodes = $xpath->query( '//*[contains(@class,"news-item")]//*[contains(@class,"card-title")]//a[@href]' );

    // Fallback: any link inside .news-item
    if ( ! $nodes || $nodes->length === 0 ) {
        $nodes = $xpath->query( '//*[contains(@class,"news-item")]//a[@href]' );
    }

    if ( $nodes ) {
        foreach ( $nodes as $node ) {
            $href = trim( $node->getAttribute( 'href' ) );
            if ( ! $href ) continue;

            $normalized = dbi_normalize_url( $href );

            // Must be a detibaikala.com article link (single-slug path)
            if ( strpos( $normalized, 'detibaikala.com' ) === false ) continue;
            if ( ! preg_match( '~detibaikala\.com(/[^?&#\s]+)~', $normalized, $m ) ) continue;

            $path = rtrim( $m[1], '/' );

            // Skip system/archive paths
            if ( ! $path ) continue;
            foreach ( [ '/category/', '/tag/', '/author/', '/page/', '/feed/', '/wp-', '/attachment/' ] as $ex ) {
                if ( strpos( $path, $ex ) !== false ) continue 2;
            }

            // Article slugs are a single path segment: /some-slug
            if ( substr_count( $path, '/' ) !== 1 ) continue;

            $links[] = $normalized;
        }
    }

    return array_values( array_unique( $links ) );
}

/**
 * Ensure URL is an archive.org absolute URL
 */
function dbi_normalize_url( string $href ): string {
    // Already full archive.org URL
    if ( strpos( $href, 'web.archive.org' ) !== false ) {
        // Strip /im_/ etc from image paths
        return $href;
    }

    // Relative archive path: /web/TIMESTAMP/https://...
    if ( preg_match( '#^/web/\d+(?:im_)?/https?://#', $href ) ) {
        return 'https://web.archive.org' . $href;
    }

    // Absolute URL of original site
    if ( strpos( $href, 'detibaikala.com' ) !== false ) {
        $path = parse_url( $href, PHP_URL_PATH );
        return DBI_BASE_ARCHIVE . '/https://detibaikala.com' . $path;
    }

    // Relative path
    if ( strpos( $href, '/' ) === 0 ) {
        return DBI_BASE_ARCHIVE . '/https://detibaikala.com' . $href;
    }

    return $href;
}

/**
 * Parse a single article page and return structured data.
 *
 * Exact structure (from live HTML inspection):
 *   .col-md-9
 *     <h2 style="margin-bottom: 20px">Title</h2>
 *     <div style="color: #a9a9a9; ...">11.5.2024</div>
 *     <div class="center img-thum"><img ...></div>
 *     <p>...</p>
 *     <blockquote class="wp-block-quote ...">...</blockquote>
 *     <figure ...> (excluded)
 */
function dbi_parse_article( string $html, string $source_url ): ?array {
    $dom = new DOMDocument();
    @$dom->loadHTML( mb_convert_encoding( $html, 'HTML-ENTITIES', 'UTF-8' ) );
    $xpath = new DOMXPath( $dom );

    // ── Container: .col-md-9 ──
    $container = $xpath->query( '//*[contains(@class,"col-md-9")]' )->item( 0 );
    if ( ! $container ) {
        // Fallback: use body
        $container = $xpath->query( '//body' )->item( 0 );
    }

    // ── Title: first <h2> inside container ──
    $title = '';
    $h2 = $xpath->query( './/h2', $container )->item( 0 );
    if ( $h2 ) {
        $title = trim( $h2->textContent );
    }
    if ( ! $title ) return null;

    // ── Date: <div style="color: #a9a9a9 ..."> ──
    $date_raw = '';
    $divs = $xpath->query( './/div[@style]', $container );
    foreach ( $divs as $div ) {
        $style = $div->getAttribute( 'style' );
        if ( strpos( $style, '#a9a9a9' ) !== false || strpos( $style, 'a9a9a9' ) !== false ) {
            $date_raw = trim( $div->textContent );
            break;
        }
    }
    $date = dbi_parse_date( $date_raw );

    // ── Featured image: div.center.img-thum > img ──
    $image_url  = '';
    $image_name = '';
    $img_node = $xpath->query( './/*[contains(@class,"img-thum")]//img', $container )->item( 0 );
    if ( $img_node ) {
        $src = $img_node->getAttribute( 'src' );
        if ( $src ) {
            $image_url = dbi_normalize_image_url( $src );
        }
    }
    // Fallback: og:image
    if ( ! $image_url ) {
        $og = $xpath->query( '//meta[@property="og:image"]/@content' )->item( 0 );
        if ( $og ) {
            $image_url = dbi_normalize_image_url( $og->nodeValue );
        }
    }
    if ( $image_url ) {
        $image_name = basename( parse_url( $image_url, PHP_URL_PATH ) );
    }

    // ── Content: only <p> and <blockquote> (not inside <figure> or <blockquote>) ──
    $content = '';
    $content_nodes = $xpath->query(
        './/p[not(ancestor::figure) and not(ancestor::blockquote)] | .//blockquote[not(ancestor::figure)]',
        $container
    );
    foreach ( $content_nodes as $node ) {
        $content .= dbi_node_to_html( $dom, $node );
    }

    return compact( 'title', 'date', 'content', 'image_url', 'image_name' );
}

/**
 * Serialize a DOMNode to HTML string, fixing archive.org image/link URLs
 */
function dbi_node_to_html( DOMDocument $dom, DOMNode $node ): string {
    // Fix img src inside this node
    $imgs = $node->getElementsByTagName( 'img' );
    foreach ( $imgs as $img ) {
        $src = $img->getAttribute( 'src' );
        if ( $src ) $img->setAttribute( 'src', dbi_normalize_image_url( $src ) );
        $img->removeAttribute( 'srcset' );
    }

    // Fix anchor hrefs
    $anchors = $node->getElementsByTagName( 'a' );
    foreach ( $anchors as $a ) {
        $href = $a->getAttribute( 'href' );
        if ( preg_match( '#/web/\d+(?:im_)?/(https?://.+)$#', $href, $m ) ) {
            $a->setAttribute( 'href', $m[1] );
        }
    }

    return $dom->saveHTML( $node );
}

/**
 * Convert archive.org image URL to a fetchable direct image URL
 */
function dbi_normalize_image_url( string $src ): string {
    if ( ! $src ) return '';

    // Already absolute non-archive URL
    if ( strpos( $src, 'web.archive.org' ) === false && strpos( $src, 'http' ) === 0 ) {
        return $src;
    }

    // archive.org image URL: /web/TIMESTAMP/https://...  or /web/TIMESTAMPim_/https://...
    if ( preg_match( '#/web/(\d+)(?:im_)?/(https?://.+)$#', $src, $m ) ) {
        // Use im_ version to get original image without Wayback Machine toolbar injection
        return 'https://web.archive.org/web/' . $m[1] . 'im_/' . $m[2];
    }

    // Relative path
    if ( strpos( $src, '/' ) === 0 ) {
        return 'https://web.archive.org' . $src;
    }

    return $src;
}

/**
 * Parse date to MySQL datetime.
 * Handles the site's format: "11.5.2024" (D.M.YYYY or DD.MM.YYYY)
 */
function dbi_parse_date( string $raw ): string {
    $raw = trim( $raw );
    if ( ! $raw ) return current_time( 'mysql' );

    // Primary format used on site: "11.5.2024" or "1.12.2024"
    if ( preg_match( '/^(\d{1,2})\.(\d{1,2})\.(\d{4})$/', $raw, $m ) ) {
        return sprintf( '%04d-%02d-%02d 00:00:00', $m[3], $m[2], $m[1] );
    }

    // ISO datetime: "2024-05-11" or "2024-05-11T10:00:00"
    if ( preg_match( '/(\d{4}-\d{2}-\d{2})/', $raw, $m ) ) {
        return $m[1] . ' 00:00:00';
    }

    // Russian month names: "11 мая 2024"
    $ru_months = [
        'января' => '01', 'февраля' => '02', 'марта' => '03',
        'апреля' => '04', 'мая' => '05', 'июня' => '06',
        'июля' => '07', 'августа' => '08', 'сентября' => '09',
        'октября' => '10', 'ноября' => '11', 'декабря' => '12',
    ];
    $lower = mb_strtolower( $raw );
    foreach ( $ru_months as $ru => $num ) {
        if ( strpos( $lower, $ru ) !== false ) {
            $lower = str_replace( $ru, $num, $lower );
            if ( preg_match( '/(\d{1,2})\s+(\d{2})\s+(\d{4})/', $lower, $m ) ) {
                return sprintf( '%04d-%02d-%02d 00:00:00', $m[3], $m[2], $m[1] );
            }
        }
    }

    // Last resort
    $ts = strtotime( $raw );
    return $ts ? date( 'Y-m-d H:i:s', $ts ) : current_time( 'mysql' );
}

/**
 * Download remote image and attach to post
 */
function dbi_upload_image( string $url, int $post_id, string $filename ): int|WP_Error|false {
    require_once ABSPATH . 'wp-admin/includes/media.php';
    require_once ABSPATH . 'wp-admin/includes/file.php';
    require_once ABSPATH . 'wp-admin/includes/image.php';

    $response = wp_remote_get( $url, [
        'timeout'    => 30,
        'user-agent' => 'Mozilla/5.0 (compatible; WordPress importer)',
    ] );

    if ( is_wp_error( $response ) ) return $response;

    $code = wp_remote_retrieve_response_code( $response );
    if ( $code !== 200 ) return false;

    $body         = wp_remote_retrieve_body( $response );
    $content_type = wp_remote_retrieve_header( $response, 'content-type' );

    // Determine extension from content-type or filename
    $ext = '';
    if ( strpos( $content_type, 'jpeg' ) !== false || strpos( $content_type, 'jpg' ) !== false ) {
        $ext = 'jpg';
    } elseif ( strpos( $content_type, 'png' ) !== false ) {
        $ext = 'png';
    } elseif ( strpos( $content_type, 'webp' ) !== false ) {
        $ext = 'webp';
    } elseif ( strpos( $content_type, 'gif' ) !== false ) {
        $ext = 'gif';
    } else {
        $ext = pathinfo( $filename, PATHINFO_EXTENSION ) ?: 'jpg';
    }

    if ( ! $filename ) {
        $filename = 'image-' . $post_id . '.' . $ext;
    } elseif ( ! pathinfo( $filename, PATHINFO_EXTENSION ) ) {
        $filename .= '.' . $ext;
    }

    $upload = wp_upload_bits( sanitize_file_name( $filename ), null, $body );
    if ( $upload['error'] ) {
        return new WP_Error( 'upload_error', $upload['error'] );
    }

    $attachment = [
        'post_mime_type' => $content_type,
        'post_title'     => sanitize_file_name( pathinfo( $filename, PATHINFO_FILENAME ) ),
        'post_content'   => '',
        'post_status'    => 'inherit',
    ];

    $attach_id = wp_insert_attachment( $attachment, $upload['file'], $post_id );
    if ( is_wp_error( $attach_id ) ) return $attach_id;

    $attach_data = wp_generate_attachment_metadata( $attach_id, $upload['file'] );
    wp_update_attachment_metadata( $attach_id, $attach_data );

    return $attach_id;
}

/**
 * Append to import log (keep last 200 entries)
 */
function dbi_log( string $message ): void {
    $log   = get_option( DBI_OPTION_LOG, [] );
    $log[] = date( 'Y-m-d H:i:s' ) . ' ' . $message;
    if ( count( $log ) > 200 ) {
        $log = array_slice( $log, -200 );
    }
    update_option( DBI_OPTION_LOG, $log, false );
}
