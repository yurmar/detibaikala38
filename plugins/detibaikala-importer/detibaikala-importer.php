<?php
/**
 * Plugin Name: Deti Baikala Importer
 * Description: Импорт новостей с detibaikala.com в рубрику «Новости» (ID=1)
 * Version: 2.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'DBI_CATEGORY_ID',  1 );
define( 'DBI_BASE_URL',     'https://detibaikala.com/category/news/' );
define( 'DBI_TOTAL_PAGES',  68 );
define( 'DBI_OPTION_URLS',  'dbi_collected_urls' );
define( 'DBI_OPTION_DONE',  'dbi_imported_urls' );
define( 'DBI_OPTION_LOG',   'dbi_import_log' );

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

    // Строим массив всех 68 URL для сбора
    $collect_pages = [ DBI_BASE_URL ];
    for ( $i = 2; $i <= DBI_TOTAL_PAGES; $i++ ) {
        $collect_pages[] = 'https://detibaikala.com/category/news/page/' . $i . '/';
    }
    ?>
    <div class="wrap">
        <h1>Импорт новостей с detibaikala.com</h1>

        <!-- ── Шаг 1: сбор ссылок ── -->
        <div style="background:#fff;border:1px solid #c3c4c7;padding:16px 20px;margin-bottom:20px;max-width:760px">
            <h3 style="margin-top:0">Шаг 1 — Собрать ссылки со всех страниц категории</h3>
            <p style="color:#666;margin-top:0">
                Автоматически обойдёт все <?= DBI_TOTAL_PAGES ?> страниц
                <code>detibaikala.com/category/news/</code> и соберёт ссылки на статьи.
            </p>

            <button id="btn-collect-all" class="button button-primary">Собрать все ссылки (<?= DBI_TOTAL_PAGES ?> страниц)</button>
            <button id="btn-collect-stop" class="button" style="display:none;margin-left:8px">Остановить</button>

            <div id="dbi-collect-progress" style="margin-top:12px;display:none">
                <div style="background:#e0e0e0;height:20px;border-radius:4px;width:500px">
                    <div id="dbi-collect-bar" style="background:#0073aa;height:20px;border-radius:4px;width:0;transition:width 0.3s"></div>
                </div>
                <p id="dbi-collect-status" style="margin-bottom:0"></p>
            </div>
        </div>

        <!-- ── Шаг 2: импорт ── -->
        <div style="background:#fff;border:1px solid #c3c4c7;padding:16px 20px;margin-bottom:20px;max-width:760px">
            <h3 style="margin-top:0">Шаг 2 — Импортировать собранные статьи</h3>
            <p>
                Собрано ссылок: <strong id="dbi-step2-total"><?= $total ?></strong><br>
                Импортировано: <strong id="dbi-step2-done"><?= $done_cnt ?></strong> / <span id="dbi-step2-total2"><?= $total ?></span><br>
                Осталось: <strong id="dbi-step2-remaining"><?= count( $remaining ) ?></strong>
            </p>

            <button id="btn-import" class="button button-primary"<?= count( $remaining ) === 0 ? ' style="display:none"' : '' ?>>Импортировать</button>
            <button id="btn-stop" class="button" style="display:none;margin-left:8px">Остановить</button>
            <p id="dbi-step2-done-msg" style="color:green;margin:0<?= ( $total > 0 && count( $remaining ) === 0 ) ? '' : ';display:none' ?>"><strong>Все собранные статьи импортированы!</strong></p>
            <p id="dbi-step2-empty-msg" style="color:#888;margin:0<?= $total === 0 ? '' : ';display:none' ?>">Сначала соберите ссылки на шаге 1.</p>

            <button id="btn-reset" class="button button-secondary" style="margin-top:12px">Сбросить всё</button>

            <div id="dbi-import-progress" style="margin-top:12px;display:none">
                <div style="background:#e0e0e0;height:20px;border-radius:4px;width:500px">
                    <div id="dbi-bar" style="background:#0073aa;height:20px;border-radius:4px;width:0;transition:width 0.3s"></div>
                </div>
                <p id="dbi-import-status" style="margin-bottom:0"></p>
            </div>
        </div>

        <!-- ── Диагностика ── -->
        <div style="background:#fff;border:1px solid #c3c4c7;padding:16px 20px;margin-bottom:20px;max-width:760px">
            <h3 style="margin-top:0">Диагностика — проверить URL</h3>
            <div style="display:flex;gap:8px;align-items:flex-start">
                <input id="dbi-debug-url" type="url" style="width:560px" class="regular-text"
                       placeholder="https://detibaikala.com/category/news/">
                <button id="btn-debug" class="button">Проверить</button>
            </div>
            <pre id="dbi-debug-result" style="margin-top:8px;background:#f0f0f0;padding:8px;font-size:12px;white-space:pre-wrap;display:none"></pre>
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
        const ajax  = '<?= admin_url('admin-ajax.php') ?>';
        const nonce = '<?= wp_create_nonce('dbi_nonce') ?>';
        let running = false;

        // ── Шаг 1: собрать ссылки со всех страниц ──
        const collectPages = <?= json_encode( $collect_pages ) ?>;
        let collectQueue   = [];
        let collectRunning = false;

        $('#btn-collect-all').on('click', function(){
            if (collectRunning) return;
            collectRunning = true;
            collectQueue   = collectPages.slice();
            $(this).prop('disabled', true);
            $('#btn-collect-stop').show();
            $('#dbi-collect-progress').show();
            collectNext();
        });

        $('#btn-collect-stop').on('click', function(){
            collectRunning = false;
            $(this).hide();
            $('#btn-collect-all').prop('disabled', false);
            const done = collectPages.length - collectQueue.length;
            $('#dbi-collect-status').css('color','#666').text('Остановлено после ' + done + ' страниц.');
        });

        function collectNext() {
            if (!collectRunning || collectQueue.length === 0) {
                if (collectQueue.length === 0 && collectRunning) {
                    collectRunning = false;
                    $('#btn-collect-all').prop('disabled', false);
                    $('#btn-collect-stop').hide();
                    $('#dbi-collect-status').css('color','#2a7a2a').text('✓ Все ' + collectPages.length + ' страниц обработаны!');
                    dbiRefreshStep2();
                }
                return;
            }

            const url = collectQueue.shift();
            const pageNum = collectPages.length - collectQueue.length;
            $('#dbi-collect-status').css('color','#666').text('Страница ' + pageNum + ' из ' + collectPages.length + ': ' + url);
            $('#dbi-collect-bar').css('width', Math.round(pageNum / collectPages.length * 100) + '%');

            $.post(ajax, {action:'dbi_collect_page', nonce, url}, function(r){
                const msg = r.success
                    ? 'Стр. ' + pageNum + '/' + collectPages.length + ' — найдено: ' + r.data.found + ', всего: ' + r.data.total
                    : 'Стр. ' + pageNum + ' ОШИБКА: ' + r.data;
                $('#dbi-collect-status').css('color', r.success ? '#666' : '#c00').text(msg);
                setTimeout(collectNext, 600);
            }).fail(function(){
                // Retry this page after 5s
                collectQueue.unshift(url);
                $('#dbi-collect-status').css('color','#c00').text('Ошибка сети на стр. ' + pageNum + ', повтор через 5с...');
                setTimeout(collectNext, 5000);
            });
        }

        // ── Шаг 2: импорт ──
        let importQueue   = <?= json_encode( $remaining ) ?>;
        let importedCount = <?= $done_cnt ?>;
        let grandTotal    = <?= $total ?>;

        $('#btn-import').on('click', function(){
            running = true;
            $(this).hide();
            $('#btn-stop').show();
            $('#dbi-import-progress').show();
            processNext();
        });

        $('#btn-stop').on('click', function(){
            running = false;
            $(this).hide();
            $('#btn-import').show();
            $('#dbi-import-status').text('Остановлено. Прогресс сохранён.');
        });

        function processNext() {
            if (!running || importQueue.length === 0) {
                if (importQueue.length === 0) {
                    $('#dbi-import-status').text('Импорт завершён!');
                    setTimeout(function(){ location.reload(); }, 1500);
                }
                return;
            }
            const url = importQueue.shift();
            $.post(ajax, {action:'dbi_import_post', nonce, url}, function(r){
                importedCount++;
                const icon = r.success ? '✓' : '✗';
                const msg  = r.success ? r.data.title : (url + ' → ' + r.data);
                $('#dbi-import-status').text('[' + importedCount + '/' + grandTotal + '] ' + icon + ' ' + msg);
                $('#dbi-bar').css('width', Math.round(importedCount / grandTotal * 100) + '%');
                setTimeout(processNext, 800);
            }).fail(function(){
                importQueue.unshift(url);
                setTimeout(processNext, 5000);
            });
        }

        $('#btn-reset').on('click', function(){
            if (!confirm('Сбросить весь прогресс и собранные ссылки?')) return;
            $.post(ajax, {action:'dbi_reset', nonce}, function(){
                location.reload();
            });
        });

        // ── Диагностика ──
        $('#btn-debug').on('click', function(){
            const url = $('#dbi-debug-url').val().trim();
            if (!url) { alert('Введите URL'); return; }
            $(this).prop('disabled', true).text('Загружаю...');
            $('#dbi-debug-result').hide().text('');
            $.post(ajax, {action:'dbi_debug_fetch', nonce, url}, function(r){
                $('#btn-debug').prop('disabled', false).text('Проверить');
                $('#dbi-debug-result').show().text(JSON.stringify(r.data || r, null, 2));
            }).fail(function(){
                $('#btn-debug').prop('disabled', false).text('Проверить');
                $('#dbi-debug-result').show().text('Ошибка сети');
            });
        });

        function dbiRefreshStep2() {
            $.post(ajax, {action:'dbi_get_status', nonce}, function(r){
                if (!r.success) return;
                importQueue   = r.data.remaining;
                importedCount = r.data.done;
                grandTotal    = r.data.total;

                $('#dbi-step2-total').text(grandTotal);
                $('#dbi-step2-total2').text(grandTotal);
                $('#dbi-step2-done').text(importedCount);
                $('#dbi-step2-remaining').text(importQueue.length);

                if (importQueue.length > 0) {
                    if (!running) $('#btn-import').show();
                    $('#dbi-step2-done-msg').hide();
                    $('#dbi-step2-empty-msg').hide();
                } else if (grandTotal > 0) {
                    if (!running) $('#btn-import').hide();
                    $('#dbi-step2-done-msg').show();
                    $('#dbi-step2-empty-msg').hide();
                } else {
                    $('#btn-import').hide();
                    $('#dbi-step2-done-msg').hide();
                    $('#dbi-step2-empty-msg').show();
                }
            });
        }
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

    $url = esc_url_raw( trim( $_POST['url'] ?? '' ) );
    if ( ! $url ) {
        wp_send_json_error( 'Пустой URL страницы' );
    }

    $html = dbi_fetch( $url );
    if ( is_wp_error( $html ) ) {
        wp_send_json_error( $html->get_error_message() );
    }

    $links    = dbi_extract_article_links( $html );
    $existing = get_option( DBI_OPTION_URLS, [] );
    $merged   = array_values( array_unique( array_merge( $existing, $links ) ) );
    update_option( DBI_OPTION_URLS, $merged, false );

    wp_send_json_success( [
        'found' => count( $links ),
        'total' => count( $merged ),
    ] );
} );

// ──────────────────────────────────────────────
// AJAX: Debug — fetch URL and return raw diagnostic info
// ──────────────────────────────────────────────

add_action( 'wp_ajax_dbi_debug_fetch', function () {
    check_ajax_referer( 'dbi_nonce', 'nonce' );
    if ( ! current_user_can( 'manage_options' ) ) wp_die();

    $url = esc_url_raw( $_POST['url'] ?? '' );
    if ( ! $url ) wp_send_json_error( 'Пустой URL' );

    $response = wp_remote_get( $url, [
        'timeout'    => 30,
        'user-agent' => 'Mozilla/5.0 (compatible; WordPress importer)',
        'headers'    => [ 'Accept-Encoding' => 'identity' ],
    ] );

    if ( is_wp_error( $response ) ) {
        wp_send_json_error( 'WP_Error: ' . $response->get_error_message() );
    }

    $code = wp_remote_retrieve_response_code( $response );
    $body = wp_remote_retrieve_body( $response );
    $len  = strlen( $body );

    $dom = new DOMDocument();
    @$dom->loadHTML( mb_convert_encoding( $body, 'HTML-ENTITIES', 'UTF-8' ) );
    $xpath = new DOMXPath( $dom );

    $h2_nodes = $xpath->query( '//h2' );
    $h2_texts = [];
    foreach ( $h2_nodes as $node ) {
        $h2_texts[] = mb_substr( trim( $node->textContent ), 0, 80 );
    }

    $h1_nodes = $xpath->query( '//h1' );
    $h1_texts = [];
    foreach ( $h1_nodes as $node ) {
        $h1_texts[] = mb_substr( trim( $node->textContent ), 0, 80 );
    }

    $title_node = $xpath->query( '//title' )->item( 0 );
    $page_title = $title_node ? trim( $title_node->textContent ) : '';

    $links      = dbi_extract_article_links( $body );
    $col_md9    = $xpath->query( '//*[contains(@class,"col-md-9")]' )->length;
    $news_items = $xpath->query( '//*[contains(@class,"news-item")]' )->length;

    wp_send_json_success( [
        'http_code'    => $code,
        'body_length'  => $len,
        'page_title'   => $page_title,
        'has_col_md9'  => $col_md9,
        'news_items'   => $news_items,
        'links_found'  => count( $links ),
        'links'        => array_slice( $links, 0, 10 ),
        'h1'           => $h1_texts,
        'h2'           => array_slice( $h2_texts, 0, 10 ),
        'body_snippet' => mb_substr( $body, 0, 500 ),
    ] );
} );

// ──────────────────────────────────────────────
// AJAX: Get current status
// ──────────────────────────────────────────────

add_action( 'wp_ajax_dbi_get_status', function () {
    check_ajax_referer( 'dbi_nonce', 'nonce' );
    if ( ! current_user_can( 'manage_options' ) ) wp_die();

    $urls      = get_option( DBI_OPTION_URLS, [] );
    $done      = get_option( DBI_OPTION_DONE, [] );
    $remaining = array_values( array_diff( $urls, $done ) );

    wp_send_json_success( [
        'total'     => count( $urls ),
        'done'      => count( $done ),
        'remaining' => $remaining,
    ] );
} );

// ──────────────────────────────────────────────
// AJAX: Import one post
// ──────────────────────────────────────────────

add_action( 'wp_ajax_dbi_import_post', function () {
    check_ajax_referer( 'dbi_nonce', 'nonce' );
    if ( ! current_user_can( 'manage_options' ) ) wp_die();

    $url = esc_url_raw( $_POST['url'] ?? '' );
    if ( ! $url ) wp_send_json_error( 'Пустой URL' );

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
        $snippet = mb_substr( substr( strip_tags( $html ), 0, 300 ), 0, 200 );
        dbi_log( 'ОШИБКА ПАРСИНГА: ' . $url . ' | ' . $snippet );
        wp_send_json_error( 'Не удалось распарсить статью' );
    }

    // Проверка дубля: заголовок + дата + хеш текста
    if ( dbi_is_duplicate( $data['title'], $data['date'], $data['content'] ) ) {
        dbi_log( 'ДУБЛЬ: ' . $data['title'] );
        $done[] = $url;
        update_option( DBI_OPTION_DONE, $done, false );
        wp_send_json_success( [ 'title' => $data['title'] . ' (дубль, пропущен)', 'skipped' => true ] );
    }

    $post_id = wp_insert_post( [
        'post_title'    => $data['title'],
        'post_content'  => $data['content'],
        'post_status'   => 'publish',
        'post_date'     => $data['date'],
        'post_category' => [ DBI_CATEGORY_ID ],
        'post_type'     => 'post',
    ], true );

    if ( is_wp_error( $post_id ) ) {
        dbi_log( 'ОШИБКА ПОСТА: ' . $data['title'] . ' — ' . $post_id->get_error_message() );
        wp_send_json_error( $post_id->get_error_message() );
    }

    if ( ! empty( $data['image_url'] ) ) {
        $attach_id = dbi_upload_image( $data['image_url'], $post_id, $data['image_name'] );
        if ( $attach_id && ! is_wp_error( $attach_id ) ) {
            set_post_thumbnail( $post_id, $attach_id );
        }
    } else {
        // Нет картинки — использовать logokb.png из медиатеки
        $q = new WP_Query( [
            'post_type'      => 'attachment',
            'post_status'    => 'inherit',
            'posts_per_page' => 1,
            'no_found_rows'  => true,
            'meta_query'     => [ [
                'key'     => '_wp_attached_file',
                'value'   => 'logokb.png',
                'compare' => 'LIKE',
            ] ],
        ] );
        if ( $q->have_posts() ) {
            set_post_thumbnail( $post_id, $q->posts[0]->ID );
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
 * Преобразует href в полный URL detibaikala.com.
 */
function dbi_to_absolute( string $href ): string {
    if ( ! $href ) return '';
    if ( strpos( $href, 'http' ) === 0 ) return $href;
    if ( strpos( $href, '//' ) === 0 ) return 'https:' . $href;
    if ( strpos( $href, '/' ) === 0 ) return 'https://detibaikala.com' . $href;
    return $href;
}

/**
 * Extract article URLs from a category page HTML.
 *
 * Tries multiple selectors in order:
 *   1. .news-item .card-title a  — текущая тема detibaikala.com
 *   2. .news-item a              — широкий вариант той же темы
 *   3. article h2 a / article h3 a — стандарт WordPress
 *   4. .entry-title a            — стандарт WordPress
 */
function dbi_extract_article_links( string $html ): array {
    $dom = new DOMDocument();
    @$dom->loadHTML( mb_convert_encoding( $html, 'HTML-ENTITIES', 'UTF-8' ) );
    $xpath = new DOMXPath( $dom );

    $nodes = $xpath->query( '//*[contains(@class,"news-item")]//*[contains(@class,"card-title")]//a[@href]' );
    if ( ! $nodes || $nodes->length === 0 ) {
        $nodes = $xpath->query( '//*[contains(@class,"news-item")]//a[@href]' );
    }
    if ( ! $nodes || $nodes->length === 0 ) {
        $nodes = $xpath->query( '//article//h2//a[@href] | //article//h3//a[@href]' );
    }
    if ( ! $nodes || $nodes->length === 0 ) {
        $nodes = $xpath->query( '//*[contains(@class,"entry-title")]//a[@href]' );
    }

    $links = [];
    if ( $nodes ) {
        foreach ( $nodes as $node ) {
            $href = trim( $node->getAttribute( 'href' ) );
            if ( ! $href ) continue;

            $full = dbi_to_absolute( $href );
            if ( strpos( $full, 'detibaikala.com' ) === false ) continue;
            if ( ! preg_match( '~detibaikala\.com(/[^?&#\s]+)~', $full, $m ) ) continue;

            $path = rtrim( $m[1], '/' );
            if ( ! $path ) continue;

            foreach ( [ '/category/', '/tag/', '/author/', '/page/', '/feed/', '/wp-', '/attachment/' ] as $ex ) {
                if ( strpos( $path, $ex ) !== false ) continue 2;
            }

            if ( substr_count( $path, '/' ) !== 1 ) continue;

            $links[] = $full;
        }
    }

    return array_values( array_unique( $links ) );
}

/**
 * Проверка дубля: заголовок + дата + MD5 текста.
 */
function dbi_is_duplicate( string $title, string $date, string $content ): bool {
    $date_str = substr( $date, 0, 10 );

    $query = new WP_Query( [
        'post_type'      => 'post',
        'post_status'    => [ 'publish', 'draft', 'private' ],
        'title'          => $title,
        'date_query'     => [ [
            'year'  => (int) substr( $date_str, 0, 4 ),
            'month' => (int) substr( $date_str, 5, 2 ),
            'day'   => (int) substr( $date_str, 8, 2 ),
        ] ],
        'posts_per_page' => 5,
        'no_found_rows'  => true,
    ] );

    if ( ! $query->have_posts() ) return false;

    $new_hash = md5( preg_replace( '/\s+/', ' ', wp_strip_all_tags( $content ) ) );

    foreach ( $query->posts as $post ) {
        $existing_hash = md5( preg_replace( '/\s+/', ' ', wp_strip_all_tags( $post->post_content ) ) );
        if ( $existing_hash === $new_hash ) return true;
    }

    return false;
}

/**
 * Fetch URL.
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
 * Parse a single article page from detibaikala.com.
 *
 * Title:   .col-md-9 h2  →  h1.entry-title  →  h1
 * Date:    div[style*='a9a9a9']  →  time[datetime]  →  .entry-date
 * Image:   .img-thum img  →  og:image  →  .wp-post-image
 * Content: all direct children of the container, skipping header elements,
 *          scripts, and social widgets. Removes "Больше фотографий." links.
 *          Falls back to <p>/<blockquote> scan if nothing found.
 */
function dbi_parse_article( string $html, string $source_url ): ?array {
    $dom = new DOMDocument();
    @$dom->loadHTML( mb_convert_encoding( $html, 'HTML-ENTITIES', 'UTF-8' ) );
    $xpath = new DOMXPath( $dom );

    // Контейнер: col-md-9 с h2 внутри
    $container = null;
    foreach ( $xpath->query( '//*[contains(@class,"col-md-9")]' ) as $node ) {
        if ( $xpath->query( './/h2', $node )->length > 0 ) {
            $container = $node;
            break;
        }
    }
    if ( ! $container ) {
        foreach ( $xpath->query( '//article | //*[contains(@class,"entry-content")] | //*[contains(@class,"post-content")]' ) as $node ) {
            if ( $xpath->query( './/h1 | .//h2', $node )->length > 0 ) {
                $container = $node;
                break;
            }
        }
    }
    if ( ! $container ) {
        $container = $xpath->query( '//body' )->item( 0 );
    }

    // Заголовок
    $title   = '';
    $h2_node = $xpath->query( './/h2', $container )->item( 0 );
    if ( $h2_node ) $title = trim( $h2_node->textContent );
    if ( ! $title ) {
        $h1 = $xpath->query( '//h1[contains(@class,"entry-title")] | //h1' )->item( 0 );
        if ( $h1 ) $title = trim( $h1->textContent );
    }
    if ( ! $title ) return null;

    // Дата (сохраняем ссылку на узел, чтобы пропустить его при сборе контента)
    $date_raw  = '';
    $date_node = null;
    foreach ( $xpath->query( './/div[@style]', $container ) as $div ) {
        if ( strpos( $div->getAttribute( 'style' ), 'a9a9a9' ) !== false ) {
            $date_raw  = trim( $div->textContent );
            $date_node = $div;
            break;
        }
    }
    if ( ! $date_raw ) {
        $time_node = $xpath->query( '//time[@datetime]' )->item( 0 );
        if ( $time_node ) $date_raw = $time_node->getAttribute( 'datetime' );
    }
    if ( ! $date_raw ) {
        foreach ( $xpath->query( '//*[contains(@class,"entry-date")] | //*[contains(@class,"post-date")]' ) as $node ) {
            $date_raw = trim( $node->textContent );
            if ( $date_raw ) break;
        }
    }
    $date = dbi_parse_date( $date_raw );

    // Обложка (миниатюра поста)
    $image_url     = $image_name = '';
    $img_thum_node = $xpath->query( './/*[contains(@class,"img-thum")]', $container )->item( 0 );
    $cover_img     = $img_thum_node ? $xpath->query( './/img', $img_thum_node )->item( 0 ) : null;
    if ( ! $cover_img ) {
        $cover_img = $xpath->query( '//*[contains(@class,"wp-post-image")]' )->item( 0 );
    }
    if ( $cover_img ) {
        $src = $cover_img->getAttribute( 'src' );
        if ( $src ) $image_url = dbi_normalize_image_url( $src );
    }
    if ( ! $image_url ) {
        $og = $xpath->query( '//meta[@property="og:image"]/@content' )->item( 0 );
        if ( $og ) $image_url = dbi_normalize_image_url( $og->nodeValue );
    }
    if ( $image_url ) $image_name = basename( parse_url( $image_url, PHP_URL_PATH ) );

    // Контент: обходим прямые дочерние элементы контейнера.
    // Пропускаем заголовочные узлы (h2, дата, обложка), скрипты и виджеты шеринга.
    // Удаляем ссылки «Больше фотографий.».
    $skip_nodes     = array_filter( [ $h2_node, $date_node, $img_thum_node ] );
    $junk_substrings = [ 'uptolike', 'sharing', 'addthis', 'yashare', 'adsbygoogle', 'sharedaddy' ];

    $content = '';
    foreach ( $container->childNodes as $child ) {
        if ( $child->nodeType !== XML_ELEMENT_NODE ) continue;

        // Пропускаем заголовочные элементы
        $is_header = false;
        foreach ( $skip_nodes as $skip ) {
            if ( $child->isSameNode( $skip ) ) { $is_header = true; break; }
        }
        if ( $is_header ) continue;

        // Пропускаем скрипты и стили
        if ( in_array( strtolower( $child->nodeName ), [ 'script', 'style', 'noscript' ], true ) ) continue;

        // Пропускаем виджеты шеринга/рекламы
        $cls = strtolower( $child->getAttribute( 'class' ) );
        $id  = strtolower( $child->getAttribute( 'id' ) );
        $is_junk = false;
        foreach ( $junk_substrings as $junk ) {
            if ( strpos( $cls, $junk ) !== false || strpos( $id, $junk ) !== false ) {
                $is_junk = true;
                break;
            }
        }
        if ( $is_junk ) continue;

        // Удаляем ссылки «Больше фотографий.»
        $links_to_remove = [];
        foreach ( $xpath->query( './/a', $child ) as $a ) {
            if ( mb_stripos( trim( $a->textContent ), 'Больше фотографий' ) !== false ) {
                $links_to_remove[] = $a;
            }
        }
        foreach ( $links_to_remove as $a ) {
            if ( $a->parentNode ) $a->parentNode->removeChild( $a );
        }

        // Включаем только элементы с текстом или изображениями
        $has_img = $xpath->query( './/img', $child )->length > 0;
        if ( trim( $child->textContent ) === '' && ! $has_img ) continue;

        $content .= dbi_node_to_html( $dom, $child );
    }

    // Fallback: если контейнер использует <p>/<blockquote> (другая структура)
    if ( trim( strip_tags( $content ) ) === '' && strpos( $content, '<img' ) === false ) {
        foreach ( $xpath->query(
            './/p[not(ancestor::figure) and not(ancestor::blockquote)] | .//blockquote[not(ancestor::figure)]',
            $container
        ) as $node ) {
            $content .= dbi_node_to_html( $dom, $node );
        }
    }

    return compact( 'title', 'date', 'content', 'image_url', 'image_name' );
}

function dbi_node_to_html( DOMDocument $dom, DOMNode $node ): string {
    foreach ( $node->getElementsByTagName( 'img' ) as $img ) {
        $src = $img->getAttribute( 'src' );
        if ( $src ) $img->setAttribute( 'src', dbi_normalize_image_url( $src ) );
        $img->removeAttribute( 'srcset' );
    }
    foreach ( $node->getElementsByTagName( 'a' ) as $a ) {
        $href = $a->getAttribute( 'href' );
        if ( $href && strpos( $href, '/' ) === 0 ) {
            $a->setAttribute( 'href', 'https://detibaikala.com' . $href );
        }
    }
    return $dom->saveHTML( $node );
}

function dbi_normalize_image_url( string $src ): string {
    if ( ! $src ) return '';
    if ( strpos( $src, 'http' ) === 0 ) return $src;
    if ( strpos( $src, '//' ) === 0 ) return 'https:' . $src;
    if ( strpos( $src, '/' ) === 0 ) return 'https://detibaikala.com' . $src;
    return $src;
}

function dbi_parse_date( string $raw ): string {
    $raw = trim( $raw );
    if ( ! $raw ) return current_time( 'mysql' );

    if ( preg_match( '/^(\d{1,2})\.(\d{1,2})\.(\d{4})$/', $raw, $m ) ) {
        return sprintf( '%04d-%02d-%02d 00:00:00', $m[3], $m[1], $m[2] );
    }
    if ( preg_match( '/(\d{4}-\d{2}-\d{2})/', $raw, $m ) ) {
        return $m[1] . ' 00:00:00';
    }

    $ru = [
        'января'=>'01','февраля'=>'02','марта'=>'03','апреля'=>'04',
        'мая'=>'05','июня'=>'06','июля'=>'07','августа'=>'08',
        'сентября'=>'09','октября'=>'10','ноября'=>'11','декабря'=>'12',
    ];
    $lower = mb_strtolower( $raw );
    foreach ( $ru as $name => $num ) {
        if ( strpos( $lower, $name ) !== false ) {
            $lower = str_replace( $name, $num, $lower );
            if ( preg_match( '/(\d{1,2})\s+(\d{2})\s+(\d{4})/', $lower, $m ) ) {
                return sprintf( '%04d-%02d-%02d 00:00:00', $m[3], $m[2], $m[1] );
            }
        }
    }

    $ts = strtotime( $raw );
    return $ts ? date( 'Y-m-d H:i:s', $ts ) : current_time( 'mysql' );
}

function dbi_upload_image( string $url, int $post_id, string $filename ): int|WP_Error|false {
    require_once ABSPATH . 'wp-admin/includes/media.php';
    require_once ABSPATH . 'wp-admin/includes/file.php';
    require_once ABSPATH . 'wp-admin/includes/image.php';

    $response = wp_remote_get( $url, [
        'timeout'    => 30,
        'user-agent' => 'Mozilla/5.0 (compatible; WordPress importer)',
    ] );

    if ( is_wp_error( $response ) ) return $response;
    if ( wp_remote_retrieve_response_code( $response ) !== 200 ) return false;

    $body         = wp_remote_retrieve_body( $response );
    $content_type = wp_remote_retrieve_header( $response, 'content-type' );

    $ext = 'jpg';
    foreach ( [ 'jpeg'=>'jpg','jpg'=>'jpg','png'=>'png','webp'=>'webp','gif'=>'gif' ] as $needle => $e ) {
        if ( strpos( $content_type, $needle ) !== false ) { $ext = $e; break; }
    }

    if ( ! $filename ) {
        $filename = 'image-' . $post_id . '.' . $ext;
    } elseif ( ! pathinfo( $filename, PATHINFO_EXTENSION ) ) {
        $filename .= '.' . $ext;
    }

    $upload = wp_upload_bits( sanitize_file_name( $filename ), null, $body );
    if ( $upload['error'] ) return new WP_Error( 'upload_error', $upload['error'] );

    $attach_id = wp_insert_attachment( [
        'post_mime_type' => $content_type,
        'post_title'     => sanitize_file_name( pathinfo( $filename, PATHINFO_FILENAME ) ),
        'post_content'   => '',
        'post_status'    => 'inherit',
    ], $upload['file'], $post_id );

    if ( is_wp_error( $attach_id ) ) return $attach_id;

    wp_update_attachment_metadata( $attach_id, wp_generate_attachment_metadata( $attach_id, $upload['file'] ) );
    return $attach_id;
}

function dbi_log( string $message ): void {
    $log   = get_option( DBI_OPTION_LOG, [] );
    $log[] = date( 'Y-m-d H:i:s' ) . ' ' . $message;
    if ( count( $log ) > 200 ) $log = array_slice( $log, -200 );
    update_option( DBI_OPTION_LOG, $log, false );
}
