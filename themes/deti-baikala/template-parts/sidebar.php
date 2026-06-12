<?php
/**
 * Боковая колонка для "вторых" страниц (about/contacts/projects/campaigns/
 * reports/reviews/media/single новости). Виджет-зона "sidebar-page".
 */
?>
<aside class="sidebar">
  <?php if ( is_active_sidebar( 'sidebar-page' ) ) : ?>
    <?php dynamic_sidebar( 'sidebar-page' ); ?>
  <?php else : ?>
    <div class="sidebar-widget">
      <div class="sidebar-ad">
        <span><?php esc_html_e( 'Рекламный блок', 'deti-baikala' ); ?></span>
      </div>
    </div>

    <div class="sidebar-widget">
      <div class="sidebar-widget__title"><?php esc_html_e( 'Последние новости', 'deti-baikala' ); ?></div>
      <?php
      $recent = new WP_Query(
          array(
              'post_type'      => 'post',
              'posts_per_page' => 3,
              'category_name'  => 'novosti',
          )
      );
      while ( $recent->have_posts() ) :
          $recent->the_post();
          ?>
          <div class="sidebar-news-item">
            <div class="sidebar-news-item__img">
              <?php if ( has_post_thumbnail() ) the_post_thumbnail( 'thumbnail' ); ?>
            </div>
            <div class="sidebar-news-item__text">
              <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
              <div class="sidebar-news-item__date"><?php echo esc_html( get_the_date() ); ?></div>
            </div>
          </div>
          <?php
      endwhile;
      wp_reset_postdata();
      ?>
    </div>

    <?php if ( db_leyka_active() ) : ?>
    <div class="sidebar-widget">
      <div class="sidebar-widget__title"><?php esc_html_e( 'Активные сборы', 'deti-baikala' ); ?></div>
      <?php
      $campaigns = db_get_campaigns( 3 );
      while ( $campaigns->have_posts() ) :
          $campaigns->the_post();
          $progress = db_campaign_progress( get_the_ID() );
          ?>
          <div class="sidebar-campaign">
            <div class="sidebar-campaign__title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></div>
            <div class="progress-bar"><div class="progress-bar__fill" style="width:<?php echo esc_attr( $progress['percent'] ); ?>%"></div></div>
            <div class="sidebar-campaign__meta">
              <span><?php echo esc_html( $progress['collected_text'] ); ?></span>
              <span><?php echo esc_html( $progress['percent'] ); ?>%</span>
            </div>
          </div>
          <?php
      endwhile;
      wp_reset_postdata();
      ?>
    </div>
    <?php endif; ?>

    <div class="sidebar-widget">
      <div class="sidebar-ad">
        <span><?php esc_html_e( 'Рекламный блок', 'deti-baikala' ); ?></span>
      </div>
    </div>
  <?php endif; ?>
</aside>
