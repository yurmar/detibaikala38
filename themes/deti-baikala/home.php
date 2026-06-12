<?php
/**
 * Архив "Новости" (страница записей, категория novosti).
 */

get_header();
?>

<div class="page-wrap">
  <main class="page-main">
    <div class="section-label"><?php esc_html_e( 'Фонд', 'deti-baikala' ); ?></div>
    <h1 class="page-title"><?php esc_html_e( 'Новости фонда', 'deti-baikala' ); ?></h1>
    <p class="page-subtitle"><?php esc_html_e( 'Следите за последними событиями и историями успеха', 'deti-baikala' ); ?></p>

    <?php if ( have_posts() ) : ?>
      <div class="news-page-grid">
        <?php
        while ( have_posts() ) :
            the_post();
            get_template_part( 'template-parts/news-card' );
        endwhile;
        ?>
      </div>

      <div class="pagination">
        <?php
        echo paginate_links(
            array(
                'prev_text' => '← ' . __( 'Назад', 'deti-baikala' ),
                'next_text' => __( 'Далее', 'deti-baikala' ) . ' →',
            )
        );
        ?>
      </div>
    <?php else : ?>
      <p><?php esc_html_e( 'Записей пока нет.', 'deti-baikala' ); ?></p>
    <?php endif; ?>
  </main>

  <?php get_template_part( 'template-parts/sidebar' ); ?>
</div>

<?php get_footer(); ?>
