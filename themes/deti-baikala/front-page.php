<?php
/**
 * Главная страница.
 */

get_header();

$hero_bg = get_theme_mod( 'db_hero_bg', '' );
?>

<!-- ===== HERO ===== -->
<section class="hero" id="home">
  <div class="hero__bg">
    <?php if ( $hero_bg ) : ?>
      <img class="hero__bg-img" src="<?php echo esc_url( $hero_bg ); ?>" alt="" aria-hidden="true">
    <?php endif; ?>
  </div>
  <div class="container">
    <div class="hero__content reveal">
      <h1 class="hero__title">
        <?php echo esc_html( get_theme_mod( 'db_hero_title', '' ) ); ?>
        <?php $accent = get_theme_mod( 'db_hero_title_accent', '' ); if ( $accent ) : ?>
          <span><?php echo esc_html( $accent ); ?></span>
        <?php endif; ?>
      </h1>
      <?php $hero_text = get_theme_mod( 'db_hero_text', '' ); if ( $hero_text ) : ?>
        <p class="hero__text"><?php echo esc_html( $hero_text ); ?></p>
      <?php endif; ?>
      <div class="hero__actions">
        <?php
        $btn1_text = get_theme_mod( 'db_hero_btn1_text', '' );
        $btn1_url  = get_theme_mod( 'db_hero_btn1_url', '' );
        $btn2_text = get_theme_mod( 'db_hero_btn2_text', '' );
        $btn2_url  = get_theme_mod( 'db_hero_btn2_url', '' );
        if ( $btn1_text ) :
            ?>
            <a href="<?php echo esc_url( $btn1_url ? $btn1_url : '#' ); ?>" class="btn btn-primary"><?php echo esc_html( $btn1_text ); ?></a>
        <?php endif;
        if ( $btn2_text ) :
            ?>
            <a href="<?php echo esc_url( $btn2_url ? $btn2_url : '#' ); ?>" class="btn btn-outline"><?php echo esc_html( $btn2_text ); ?></a>
        <?php endif; ?>
      </div>
    </div>
  </div>
</section>

<!-- Wave divider -->
<div class="wave-divider" style="background:var(--bg-primary)">
  <svg viewBox="0 0 1440 60" preserveAspectRatio="none"><path d="M0,30 C240,60 480,0 720,30 C960,60 1200,0 1440,30 L1440,60 L0,60 Z" fill="var(--bg-secondary)"/></svg>
</div>

<!-- ===== STATS ===== -->
<section class="section stats" id="results">
  <div class="container">
    <div class="section-head reveal">
      <div class="section-label"><?php echo esc_html( get_theme_mod( 'db_stats_label', '' ) ); ?></div>
      <h2><?php echo esc_html( get_theme_mod( 'db_stats_title', '' ) ); ?></h2>
      <?php $stats_text = get_theme_mod( 'db_stats_text', '' ); if ( $stats_text ) : ?>
        <p><?php echo esc_html( $stats_text ); ?></p>
      <?php endif; ?>
    </div>
    <div class="stats__grid">
      <?php for ( $i = 1; $i <= 7; $i++ ) :
          $num   = get_theme_mod( "db_stat_{$i}_num", '' );
          $label = get_theme_mod( "db_stat_{$i}_label", '' );
          if ( '' === $num && '' === $label ) {
              continue;
          }
          $delay = ( ( $i - 1 ) % 6 ) + 1;
          ?>
        <div class="stat-card reveal reveal-delay-<?php echo esc_attr( $delay ); ?>">
          <div class="stat-num"><?php echo esc_html( $num ); ?></div>
          <div class="stat-label"><?php echo nl2br( esc_html( $label ) ); ?></div>
        </div>
      <?php endfor; ?>
    </div>
  </div>
</section>

<!-- Wave divider reversed -->
<div class="wave-divider" style="background:var(--bg-secondary);transform:rotate(180deg)">
  <svg viewBox="0 0 1440 60" preserveAspectRatio="none"><path d="M0,30 C240,60 480,0 720,30 C960,60 1200,0 1440,30 L1440,60 L0,60 Z" fill="var(--bg-primary)"/></svg>
</div>

<?php
$rutube_title = get_theme_mod( 'db_rutube_title', '' );
$rutube_url   = get_theme_mod( 'db_rutube_url', '' );
if ( $rutube_url ) :
?>
<!-- Rutube -->
<div class="hero__rutube reveal reveal-delay-3">
  <a href="<?php echo esc_url( $rutube_url ); ?>" class="rutube-link" target="_blank" rel="noopener">
    <div class="rutube-icon">▶</div>
    <div>
      <div style="font-size:0.78rem;color:var(--text-muted);margin-bottom:0.1rem"><?php echo esc_html( get_theme_mod( 'db_rutube_subtitle', '' ) ); ?></div>
      <div style="color:var(--text-primary)"><?php echo esc_html( $rutube_title ); ?></div>
    </div>
  </a>
</div>
<?php endif; ?>

<?php if ( db_leyka_active() ) :
    $campaigns = db_get_campaigns( 3 );
    if ( $campaigns->have_posts() ) :
?>
<!-- ===== DONATE ===== -->
<section class="section" id="donate">
  <div class="container">
    <div class="section-head reveal">
      <div class="section-label"><?php esc_html_e( 'Пожертвовать', 'deti-baikala' ); ?></div>
      <h2><?php esc_html_e( 'Поддержите наши кампании', 'deti-baikala' ); ?></h2>
      <p><?php esc_html_e( 'Ваше пожертвование напрямую помогает детям и семьям Иркутской области.', 'deti-baikala' ); ?></p>
    </div>
    <div class="donate__grid">
      <?php
      $i = 0;
      while ( $campaigns->have_posts() ) :
          $campaigns->the_post();
          $i++;
          ?>
        <div class="donate-card reveal reveal-delay-<?php echo esc_attr( $i ); ?>">
          <div class="donate-card__tag"><?php echo esc_html( db_campaign_tag( get_the_ID() ) ); ?></div>
          <div class="donate-card__title"><?php the_title(); ?></div>
          <div class="donate-card__text"><?php echo esc_html( db_excerpt( get_the_excerpt(), 220 ) ); ?></div>
          <a href="<?php the_permalink(); ?>" class="btn btn-primary"><?php esc_html_e( 'Поддержать', 'deti-baikala' ); ?></a>
        </div>
      <?php endwhile; wp_reset_postdata(); ?>
    </div>
  </div>
</section>
<?php endif; endif; ?>

<?php
$banner_show = get_theme_mod( 'db_banner_show', false );
if ( $banner_show ) :
    $banner_image    = get_theme_mod( 'db_banner_image', '' );
    $banner_title    = get_theme_mod( 'db_banner_title', '' );
    $banner_text     = get_theme_mod( 'db_banner_text', '' );
    $banner_btn_text = get_theme_mod( 'db_banner_btn_text', '' );
    $banner_btn_url  = get_theme_mod( 'db_banner_btn_url', '' );
?>
<!-- ===== BANNER ===== -->
<div class="ns-banner reveal">
  <div class="ns-banner__bg">
    <?php if ( $banner_image ) : ?>
      <img src="<?php echo esc_url( $banner_image ); ?>" alt="" aria-hidden="true">
    <?php endif; ?>
  </div>
  <div class="ns-banner__content">
    <?php if ( $banner_title ) : ?>
      <h2 class="ns-banner__title"><?php echo esc_html( $banner_title ); ?></h2>
    <?php endif; ?>
    <?php if ( $banner_text ) : ?>
      <p class="ns-banner__text"><?php echo nl2br( esc_html( $banner_text ) ); ?></p>
    <?php endif; ?>
    <?php if ( $banner_btn_text ) : ?>
      <a href="<?php echo esc_url( $banner_btn_url ? $banner_btn_url : '#' ); ?>" class="btn btn-primary">
        <?php echo esc_html( $banner_btn_text ); ?>
      </a>
    <?php endif; ?>
  </div>
</div>
<?php endif; ?>

<?php
$news_query = new WP_Query(
    array(
        'post_type'      => 'post',
        'posts_per_page' => 3,
        'category_name'  => 'news',
    )
);
if ( $news_query->have_posts() ) :
?>
<!-- ===== NEWS SECTION ===== -->
<section class="section section--alt" id="news-home">
  <div class="container">
    <div class="section-head reveal">
      <div class="section-label"><?php esc_html_e( 'Новости фонда', 'deti-baikala' ); ?></div>
      <h2><?php esc_html_e( 'Последние новости', 'deti-baikala' ); ?></h2>
    </div>
    <div class="news__grid">
      <?php
      $i = 0;
      while ( $news_query->have_posts() ) :
          $news_query->the_post();
          $i++;
          get_template_part( 'template-parts/news-card', null, array( 'delay_class' => 'reveal-delay-' . $i ) );
      endwhile;
      wp_reset_postdata();
      ?>
    </div>
    <div style="text-align:center;margin-top:2.5rem" class="reveal">
      <a href="/category/news/" class="btn btn-outline"><?php esc_html_e( 'Все новости', 'deti-baikala' ); ?></a>
    </div>
  </div>
</section>
<?php endif; ?>

<?php
$partners = new WP_Query(
    array(
        'post_type'      => 'partner',
        'posts_per_page' => -1,
    )
);
if ( $partners->have_posts() ) :
?>
<!-- ===== PARTNERS ===== -->
<section class="section partners" id="partners">
  <div class="container">
    <div class="section-head reveal">
      <div class="section-label"><?php esc_html_e( 'Партнёры', 'deti-baikala' ); ?></div>
      <h2><?php esc_html_e( 'Наши партнёры', 'deti-baikala' ); ?></h2>
      <p><?php esc_html_e( 'Вместе мы меняем жизни детей к лучшему', 'deti-baikala' ); ?></p>
    </div>
  </div>
  <div class="partners__track-wrap reveal">
    <div class="partners__track">
      <?php
      // Выводим список дважды подряд для бесшовной CSS-анимации ленты.
      for ( $loop = 0; $loop < 2; $loop++ ) :
          while ( $partners->have_posts() ) :
              $partners->the_post();
              $url = get_post_meta( get_the_ID(), '_partner_url', true );
              $logo = has_post_thumbnail() ? get_the_post_thumbnail( get_the_ID(), 'medium' ) : esc_html( get_the_title() );
              if ( $url ) {
                  printf( '<a class="partner-logo" href="%s" target="_blank" rel="noopener">%s</a>', esc_url( $url ), $logo );
              } else {
                  printf( '<div class="partner-logo">%s</div>', $logo );
              }
          endwhile;
          $partners->rewind_posts();
      endfor;
      wp_reset_postdata();
      ?>
    </div>
  </div>
</section>
<?php endif; ?>

<?php get_footer(); ?>
