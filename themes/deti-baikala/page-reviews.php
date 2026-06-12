<?php
/**
 * Template Name: Отзывы
 */

get_header();

while ( have_posts() ) :
	the_post();
	?>

<div class="page-wrap">
  <main class="page-main">
    <div class="section-label"><?php esc_html_e( 'Люди о нас', 'deti-baikala' ); ?></div>
    <h1 class="page-title"><?php the_title(); ?></h1>
    <?php if ( get_the_excerpt() ) : ?>
      <p class="page-subtitle"><?php echo esc_html( get_the_excerpt() ); ?></p>
    <?php endif; ?>

    <?php
    $reviews = new WP_Query(
        array(
            'post_type'      => 'review_item',
            'posts_per_page' => -1,
            'orderby'        => 'menu_order',
            'order'          => 'ASC',
        )
    );
    ?>

    <?php if ( $reviews->have_posts() ) : ?>
      <div class="reviews-grid">
        <?php while ( $reviews->have_posts() ) : $reviews->the_post();
            $rating = get_post_meta( get_the_ID(), '_review_rating', true );
            $role   = get_post_meta( get_the_ID(), '_review_role', true );
            ?>
          <div class="review-card">
            <?php db_render_stars( $rating ); ?>
            <div class="review-card__quote">"</div>
            <div class="review-card__text"><?php echo wp_kses_post( get_the_content() ); ?></div>
            <div class="review-card__author">
              <?php db_render_review_avatar( get_the_ID() ); ?>
              <div>
                <div class="review-card__name"><?php the_title(); ?></div>
                <?php if ( $role ) : ?>
                  <div class="review-card__meta"><?php echo esc_html( $role ); ?></div>
                <?php endif; ?>
              </div>
            </div>
          </div>
        <?php endwhile; wp_reset_postdata(); ?>
      </div>
    <?php else : ?>
      <p><?php esc_html_e( 'Отзывы пока не добавлены.', 'deti-baikala' ); ?></p>
    <?php endif; ?>
  </main>

  <?php get_template_part( 'template-parts/sidebar' ); ?>
</div>

<?php
endwhile;

get_footer();
