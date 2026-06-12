<?php
/**
 * Карточка проекта.
 */

get_header();

while ( have_posts() ) :
	the_post();

	$back_url = db_get_page_by_template( 'page-projects.php' );
	$stat     = get_post_meta( get_the_ID(), '_project_stat', true );
	$badge    = get_post_meta( get_the_ID(), '_project_badge', true );
	?>

<div class="page-wrap">
  <main class="page-main">
    <?php if ( $back_url ) : ?>
      <a href="<?php echo esc_url( $back_url ); ?>" class="article-back"><?php esc_html_e( '← Все проекты', 'deti-baikala' ); ?></a>
    <?php endif; ?>

    <div class="section-label"><?php esc_html_e( 'Проект', 'deti-baikala' ); ?></div>
    <h1 class="page-title"><?php the_title(); ?></h1>

    <div class="article-meta">
      <?php if ( $badge ) : ?>
        <span class="article-meta__tag"><?php echo esc_html( $badge ); ?></span>
      <?php endif; ?>
      <?php if ( $stat ) : ?>
        <span><?php echo esc_html( $stat ); ?></span>
      <?php endif; ?>
    </div>

    <?php if ( has_post_thumbnail() ) : ?>
      <div class="article-img"><?php the_post_thumbnail( 'large' ); ?></div>
    <?php endif; ?>

    <div class="article-body">
      <?php the_content(); ?>
    </div>

    <?php if ( db_leyka_active() ) : ?>
      <div class="donate-form" id="donate">
        <h3><?php esc_html_e( 'Поддержать проект', 'deti-baikala' ); ?></h3>
        <?php echo do_shortcode( '[leyka_donation_form]' ); ?>
      </div>
    <?php endif; ?>

    <div class="article-share">
      <span class="article-share__label"><?php esc_html_e( 'Поделиться:', 'deti-baikala' ); ?></span>
      <?php db_render_social_icons(); ?>
    </div>
  </main>

  <?php get_template_part( 'template-parts/sidebar' ); ?>
</div>

<?php
endwhile;

get_footer();
