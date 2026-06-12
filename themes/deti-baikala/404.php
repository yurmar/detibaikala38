<?php
/**
 * 404 — страница не найдена.
 */

get_header();
?>

<div class="page-wrap">
  <main class="page-main" style="text-align:center;padding:4rem 0">
    <div class="section-label"><?php esc_html_e( 'Ошибка 404', 'deti-baikala' ); ?></div>
    <h1 class="page-title"><?php esc_html_e( 'Страница не найдена', 'deti-baikala' ); ?></h1>
    <p class="page-subtitle"><?php esc_html_e( 'Возможно, страница была удалена или адрес введён с ошибкой.', 'deti-baikala' ); ?></p>
    <p style="margin-top:2rem">
      <a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="btn btn-primary"><?php esc_html_e( 'На главную', 'deti-baikala' ); ?></a>
    </p>
    <div style="max-width:480px;margin:2rem auto 0">
      <?php get_search_form(); ?>
    </div>
  </main>
</div>

<?php
get_footer();
