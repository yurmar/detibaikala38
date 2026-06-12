<?php
/**
 * Стандартный шаблон страницы.
 */

get_header();

while ( have_posts() ) :
	the_post();
	?>

<div class="page-wrap">
  <main class="page-main">
    <h1 class="page-title"><?php the_title(); ?></h1>

    <?php if ( has_post_thumbnail() ) : ?>
      <div class="article-img"><?php the_post_thumbnail( 'large' ); ?></div>
    <?php endif; ?>

    <div class="article-body">
      <?php the_content(); ?>
    </div>
  </main>

  <?php get_template_part( 'template-parts/sidebar' ); ?>
</div>

<?php
endwhile;

get_footer();
