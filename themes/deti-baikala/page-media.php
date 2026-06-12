<?php
/**
 * Template Name: СМИ о фонде
 *
 * Страница со списком публикаций из категории "smi-o-fonde".
 */

get_header();

$paged = get_query_var( 'paged' ) ? get_query_var( 'paged' ) : ( get_query_var( 'page' ) ? get_query_var( 'page' ) : 1 );

$media_query = new WP_Query(
	array(
		'post_type'      => 'post',
		'category_name'  => 'smi-o-fonde',
		'posts_per_page' => 9,
		'paged'          => $paged,
	)
);
?>

<div class="page-wrap">
  <main class="page-main">
    <?php while ( have_posts() ) : the_post(); ?>
      <div class="section-label"><?php esc_html_e( 'Фонд', 'deti-baikala' ); ?></div>
      <h1 class="page-title"><?php the_title(); ?></h1>
      <?php if ( get_the_content() ) : ?>
        <div class="page-subtitle"><?php the_content(); ?></div>
      <?php endif; ?>
    <?php endwhile; ?>

    <?php if ( $media_query->have_posts() ) : ?>
      <div class="news-page-grid">
        <?php
        while ( $media_query->have_posts() ) :
            $media_query->the_post();
            get_template_part( 'template-parts/news-card' );
        endwhile;
        wp_reset_postdata();
        ?>
      </div>

      <div class="pagination">
        <?php
        echo paginate_links(
            array(
                'base'      => str_replace( PHP_INT_MAX, '%#%', esc_url( get_pagenum_link( PHP_INT_MAX ) ) ),
                'total'     => $media_query->max_num_pages,
                'current'   => $paged,
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
