<?php
/**
 * Одна запись из категории "Новости" или "СМИ о фонде".
 */

get_header();

while ( have_posts() ) :
	the_post();

	$is_media   = has_category( 'smi' );
	$categories = get_the_category();
	$back_url   = $is_media ? db_get_page_by_template( 'page-media.php' ) : ( ! empty( $categories ) ? get_category_link( $categories[0]->term_id ) : get_permalink( get_option( 'page_for_posts' ) ) );
	$back_label = $is_media ? __( '← Все материалы', 'deti-baikala' ) : __( '← Все новости', 'deti-baikala' );
	?>

<div class="page-wrap">
  <main class="page-main">
    <?php if ( $back_url ) : ?>
      <a href="<?php echo esc_url( $back_url ); ?>" class="article-back"><?php echo esc_html( $back_label ); ?></a>
    <?php endif; ?>

    <h1 class="page-title"><?php the_title(); ?></h1>

    <div class="article-meta">
      <?php if ( ! empty( $categories ) ) : ?>
        <a class="article-meta__tag" href="<?php echo esc_url( get_category_link( $categories[0]->term_id ) ); ?>"><?php echo esc_html( $categories[0]->name ); ?></a>
      <?php endif; ?>
      <span><?php echo esc_html( get_the_date() ); ?></span>
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

  </main>

  <?php get_template_part( 'template-parts/sidebar' ); ?>
</div>

<?php
endwhile;

get_footer();
