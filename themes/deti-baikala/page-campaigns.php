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
      <div class="campaigns-grid">
        <?php echo do_shortcode( '[leyka_campaigns]' ); ?>
      </div>

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
