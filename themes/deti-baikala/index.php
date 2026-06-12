<?php
/**
 * Базовый шаблон (fallback), обязателен для WordPress.
 */

get_header();
?>

<div class="page-wrap">
  <main class="page-main">
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
