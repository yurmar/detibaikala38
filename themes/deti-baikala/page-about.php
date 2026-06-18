<?php
/**
 * Template Name: О фонде
 */

get_header();

while ( have_posts() ) :
	the_post();
	?>

<div class="page-wrap">
  <main class="page-main">
    <div class="section-label"><?php esc_html_e( 'Фонд', 'deti-baikala' ); ?></div>
    <h1 class="page-title"><?php the_title(); ?></h1>
    <?php if ( has_excerpt() ) : ?>
      <p class="page-subtitle"><?php echo esc_html( get_the_excerpt() ); ?></p>
    <?php endif; ?>

    <div class="about-text-wrap">
      <?php the_content(); ?>
    </div>

    <?php
    $team = new WP_Query(
        array(
            'post_type'      => 'team_member',
            'posts_per_page' => -1,
            'orderby'        => 'menu_order',
            'order'          => 'ASC',
        )
    );
    if ( $team->have_posts() ) :
    ?>
      <h2 style="margin-bottom:1.5rem;margin-top:3rem"><?php esc_html_e( 'Наша команда', 'deti-baikala' ); ?></h2>
      <div class="team-grid">
        <?php while ( $team->have_posts() ) : $team->the_post(); ?>
          <div class="team-card">
            <div class="team-card__photo">
              <?php if ( has_post_thumbnail() ) : ?>
                <?php the_post_thumbnail( 'thumbnail' ); ?>
              <?php else : ?>
                <?php echo esc_html( get_post_meta( get_the_ID(), '_team_avatar_emoji', true ) ?: '🧑' ); ?>
              <?php endif; ?>
            </div>
            <div class="team-card__name"><?php the_title(); ?></div>
            <div class="team-card__role"><?php echo esc_html( get_post_meta( get_the_ID(), '_team_role', true ) ); ?></div>
          </div>
        <?php endwhile; wp_reset_postdata(); ?>
      </div>
    <?php endif; ?>

    <?php
    $docs = new WP_Query(
        array(
            'post_type'      => 'fund_document',
            'posts_per_page' => -1,
            'orderby'        => 'menu_order',
            'order'          => 'ASC',
        )
    );
    if ( $docs->have_posts() ) :
    ?>
      <h2 style="margin-bottom:1rem;margin-top:3rem"><?php esc_html_e( 'Документы и сертификаты', 'deti-baikala' ); ?></h2>
      <p style="color:var(--text-secondary);font-size:0.88rem;margin-bottom:1.5rem"><?php esc_html_e( 'Свидетельства о регистрации, лицензии, грамоты и благодарственные письма', 'deti-baikala' ); ?></p>
      <div class="docs-grid">
        <?php while ( $docs->have_posts() ) : $docs->the_post();
            $file_id  = get_post_meta( get_the_ID(), '_doc_file', true );
            $file_url = $file_id ? wp_get_attachment_url( $file_id ) : '';
            $icon     = get_post_meta( get_the_ID(), '_doc_icon', true ) ?: '📄';
            ?>
          <?php if ( $file_url ) : ?>
            <a class="doc-card" href="<?php echo esc_url( $file_url ); ?>" target="_blank" rel="noopener">
          <?php else : ?>
            <div class="doc-card">
          <?php endif; ?>
              <div class="doc-card__icon"><?php echo esc_html( $icon ); ?></div>
              <div class="doc-card__name"><?php the_title(); ?></div>
          <?php if ( $file_url ) : ?>
            </a>
          <?php else : ?>
            </div>
          <?php endif; ?>
        <?php endwhile; wp_reset_postdata(); ?>
      </div>
    <?php endif; ?>

    <?php
    $donate_campaign_id = absint( get_post_meta( get_the_ID(), '_db_donate_campaign_id', true ) );
    if ( db_leyka_active() && $donate_campaign_id ) :
    ?>
      <div class="donate-form" id="donate">
        <h3><?php esc_html_e( 'Сделать пожертвование', 'deti-baikala' ); ?></h3>
        <?php echo do_shortcode( '[leyka_donation_form id="' . $donate_campaign_id . '"]' ); ?>
      </div>
    <?php endif; ?>
  </main>

  <?php get_template_part( 'template-parts/sidebar' ); ?>
</div>

<?php
endwhile;

get_footer();
