<?php
/**
 * Template Name: Кампании
 */

get_header();

while ( have_posts() ) :
	the_post();
	?>

<div class="page-wrap">
  <main class="page-main">
    <div class="section-label"><?php esc_html_e( 'Поддержите нас', 'deti-baikala' ); ?></div>
    <h1 class="page-title"><?php the_title(); ?></h1>
    <?php if ( get_the_excerpt() ) : ?>
      <p class="page-subtitle"><?php echo esc_html( get_the_excerpt() ); ?></p>
    <?php endif; ?>

    <?php if ( get_the_content() ) : ?>
      <div class="about-text-wrap"><?php the_content(); ?></div>
    <?php endif; ?>

    <?php if ( db_leyka_active() ) : ?>
      <?php
      $campaigns_q = new WP_Query( array(
          'post_type'      => 'leyka_campaign',
          'posts_per_page' => -1,
          'post_status'    => 'publish',
          'orderby'        => 'date',
          'order'          => 'DESC',
      ) );
      if ( $campaigns_q->have_posts() ) : ?>
      <div class="projects-grid campaigns-grid">
        <?php while ( $campaigns_q->have_posts() ) : $campaigns_q->the_post();
            $progress = db_campaign_progress( get_the_ID() );
            ?>
          <div class="project-card campaign-card">
            <div class="project-card__img">
              <?php if ( has_post_thumbnail() ) : ?>
                <?php the_post_thumbnail( 'medium_large' ); ?>
              <?php else : ?>
                <img src="/wp-content/uploads/2026/06/db38_placeholder_news.jpg" alt="">
              <?php endif; ?>
            </div>
            <div class="project-card__body">
              <div class="project-card__title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></div>
              <div class="project-card__text"><?php echo esc_html( db_excerpt( get_the_excerpt(), 160 ) ); ?></div>
              <?php if ( $progress['target'] > 0 ) : ?>
                <div class="progress-bar" style="margin:0.75rem 0">
                  <div class="progress-bar__fill" style="width:<?php echo esc_attr( $progress['percent'] ); ?>%"></div>
                </div>
                <div class="sidebar-campaign__meta" style="margin-bottom:0.75rem">
                  <span><?php echo esc_html( $progress['collected_text'] ); ?></span>
                  <span><?php echo esc_html( $progress['percent'] ); ?>%</span>
                </div>
              <?php endif; ?>
              <div class="project-card__footer">
                <a href="<?php the_permalink(); ?>" class="btn btn-outline btn-sm"><?php esc_html_e( 'Подробнее', 'deti-baikala' ); ?></a>
              </div>
            </div>
          </div>
        <?php endwhile; wp_reset_postdata(); ?>
      </div>
      <?php endif; ?>

      <div class="donate-form" id="donate-form" style="margin-top:3rem">
        <h3><?php esc_html_e( 'Сделать пожертвование', 'deti-baikala' ); ?></h3>
        <?php echo do_shortcode( '[leyka_donation_form]' ); ?>
      </div>
    <?php else : ?>
      <p><?php esc_html_e( 'Для отображения кампаний установите и активируйте плагин «Лейка».', 'deti-baikala' ); ?></p>
    <?php endif; ?>
  </main>

  <?php get_template_part( 'template-parts/sidebar' ); ?>
</div>

<?php
endwhile;

get_footer();
