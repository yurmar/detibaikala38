<?php
/**
 * Карточка кампании по сбору средств (Лейка).
 */

get_header();

while ( have_posts() ) :
	the_post();

	$back_url = db_get_page_by_template( 'page-campaigns.php' );
	$progress = db_campaign_progress( get_the_ID() );
	?>

<div class="page-wrap">
  <main class="page-main">
    <?php if ( $back_url ) : ?>
      <a href="<?php echo esc_url( $back_url ); ?>" class="article-back"><?php esc_html_e( '← Все кампании', 'deti-baikala' ); ?></a>
    <?php endif; ?>

    <div class="section-label"><?php echo esc_html( db_campaign_tag( get_the_ID() ) ); ?></div>
    <h1 class="page-title"><?php the_title(); ?></h1>

    <?php if ( has_post_thumbnail() ) : ?>
      <div class="article-img"><?php the_post_thumbnail( 'large' ); ?></div>
    <?php endif; ?>

    <?php if ( $progress['target'] > 0 ) : ?>
      <div class="campaign-progress">
        <div class="campaign-amounts">
          <span class="campaign-amounts__raised"><?php echo esc_html( $progress['collected_text'] ); ?></span>
          <span class="campaign-amounts__goal"><?php printf( esc_html__( 'из %s', 'deti-baikala' ), esc_html( $progress['target_text'] ) ); ?></span>
        </div>
        <div class="progress-bar"><div class="progress-bar__fill" style="width:<?php echo esc_attr( $progress['percent'] ); ?>%"></div></div>
      </div>
    <?php endif; ?>

    <div class="article-body">
      <?php the_content(); ?>
    </div>

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
