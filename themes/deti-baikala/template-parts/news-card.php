<?php
/**
 * Карточка новости/материала СМИ.
 *
 * @var array $args ['delay_class' => 'reveal-delay-1']
 */

$delay_class = isset( $args['delay_class'] ) ? $args['delay_class'] : '';
?>
<div class="news-card reveal <?php echo esc_attr( $delay_class ); ?>">
  <div class="news-card__img">
    <?php if ( has_post_thumbnail() ) : ?>
      <?php the_post_thumbnail( 'medium_large' ); ?>
    <?php else : ?>
      <img src="/wp-content/uploads/2026/06/db38_placeholder_news.jpg" alt="">
    <?php endif; ?>
  </div>
  <div class="news-card__body">
    <div class="news-card__date"><?php echo esc_html( get_the_date() ); ?></div>
    <div class="news-card__title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></div>
    <div class="news-card__text"><?php echo esc_html( db_excerpt( get_the_excerpt(), 120 ) ); ?></div>
    <div class="news-card__footer">
      <a href="<?php the_permalink(); ?>" class="btn btn-outline btn-sm"><?php esc_html_e( 'Подробнее', 'deti-baikala' ); ?></a>
    </div>
  </div>
</div>
