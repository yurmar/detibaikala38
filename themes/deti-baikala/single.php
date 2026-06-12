<?php
/**
 * Одна запись из категории "Новости" или "СМИ о фонде".
 */

get_header();

while ( have_posts() ) :
	the_post();

	$is_media   = has_category( 'smi-o-fonde' );
	$section    = $is_media ? __( 'СМИ о фонде', 'deti-baikala' ) : __( 'Новости фонда', 'deti-baikala' );
	$back_url   = $is_media ? db_get_page_by_template( 'page-media.php' ) : get_permalink( get_option( 'page_for_posts' ) );
	$back_label = $is_media ? __( '← Все материалы', 'deti-baikala' ) : __( '← Все новости', 'deti-baikala' );
	$categories = get_the_category();
	?>

<div class="page-wrap">
  <main class="page-main">
    <?php if ( $back_url ) : ?>
      <a href="<?php echo esc_url( $back_url ); ?>" class="article-back"><?php echo esc_html( $back_label ); ?></a>
    <?php endif; ?>

    <div class="section-label"><?php echo esc_html( $section ); ?></div>
    <h1 class="page-title"><?php the_title(); ?></h1>

    <div class="article-meta">
      <?php if ( ! empty( $categories ) ) : ?>
        <span class="article-meta__tag"><?php echo esc_html( $categories[0]->name ); ?></span>
      <?php endif; ?>
      <span><?php echo esc_html( get_the_date() ); ?></span>
      <span>·</span>
      <span><?php esc_html_e( 'Автор:', 'deti-baikala' ); ?> <?php the_author(); ?></span>
    </div>

    <?php if ( has_post_thumbnail() ) : ?>
      <div class="article-img"><?php the_post_thumbnail( 'large' ); ?></div>
    <?php endif; ?>

    <div class="article-body">
      <?php the_content(); ?>
    </div>

    <?php
    $tags = get_the_tags();
    if ( $tags ) :
    ?>
      <div class="article-tags">
        <?php foreach ( $tags as $tag ) : ?>
          <span class="article-tag">#<?php echo esc_html( str_replace( ' ', '_', $tag->name ) ); ?></span>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <div class="article-share">
      <span class="article-share__label"><?php esc_html_e( 'Поделиться:', 'deti-baikala' ); ?></span>
      <?php db_render_social_icons(); ?>
    </div>

    <?php
    $related = new WP_Query(
        array(
            'post_type'      => 'post',
            'category__in'   => wp_list_pluck( $categories, 'term_id' ),
            'posts_per_page' => 3,
            'post__not_in'   => array( get_the_ID() ),
        )
    );
    if ( $related->have_posts() ) :
    ?>
      <div class="related-news">
        <div class="related-news__title"><?php esc_html_e( 'Похожие новости', 'deti-baikala' ); ?></div>
        <div class="related-news__grid">
          <?php
          while ( $related->have_posts() ) :
              $related->the_post();
              get_template_part( 'template-parts/news-card' );
          endwhile;
          wp_reset_postdata();
          ?>
        </div>
      </div>
    <?php endif; ?>
  </main>

  <?php get_template_part( 'template-parts/sidebar' ); ?>
</div>

<?php
endwhile;

get_footer();
