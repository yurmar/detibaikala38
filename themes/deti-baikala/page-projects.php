<?php
/**
 * Template Name: Проекты
 */

get_header();

while ( have_posts() ) :
	the_post();
	?>

<div class="page-wrap">
  <main class="page-main">
    <div class="section-label"><?php esc_html_e( 'Деятельность', 'deti-baikala' ); ?></div>
    <h1 class="page-title"><?php the_title(); ?></h1>
    <?php if ( get_the_excerpt() ) : ?>
      <p class="page-subtitle"><?php echo esc_html( get_the_excerpt() ); ?></p>
    <?php endif; ?>

    <?php
    $projects = new WP_Query(
        array(
            'post_type'      => 'project',
            'posts_per_page' => -1,
            'orderby'        => 'menu_order',
            'order'          => 'ASC',
        )
    );
    ?>

    <?php if ( $projects->have_posts() ) : ?>
      <div class="projects-grid">
        <?php while ( $projects->have_posts() ) : $projects->the_post();
            $badge = get_post_meta( get_the_ID(), '_project_badge', true );
            $stat  = get_post_meta( get_the_ID(), '_project_stat', true );
            ?>
          <div class="project-card">
            <div class="project-card__img">
              <?php if ( $badge ) : ?>
                <span class="project-card__badge"><?php echo esc_html( $badge ); ?></span>
              <?php endif; ?>
              <?php if ( has_post_thumbnail() ) : ?>
                <?php the_post_thumbnail( 'medium_large' ); ?>
              <?php else : ?>
                <img src="/wp-content/uploads/2026/06/db38_placeholder_news.jpg" alt="">
              <?php endif; ?>
            </div>
            <div class="project-card__body">
              <div class="project-card__title"><?php the_title(); ?></div>
              <div class="project-card__text"><?php echo esc_html( db_excerpt( get_the_excerpt(), 160 ) ); ?></div>
              <div class="project-card__footer">
                <?php if ( $stat ) : ?>
                  <span><?php echo esc_html( $stat ); ?></span>
                <?php endif; ?>
                <a href="<?php the_permalink(); ?>" class="btn btn-outline btn-sm"><?php esc_html_e( 'Подробнее', 'deti-baikala' ); ?></a>
              </div>
            </div>
          </div>
        <?php endwhile; wp_reset_postdata(); ?>
      </div>
    <?php else : ?>
      <p><?php esc_html_e( 'Проекты пока не добавлены.', 'deti-baikala' ); ?></p>
    <?php endif; ?>
  </main>

  <?php get_template_part( 'template-parts/sidebar' ); ?>
</div>

<?php
endwhile;

get_footer();
