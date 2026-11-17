<?php
/**
 * Отдельная страница плагина «Конкурс рисунков» (без сайдбара и оформления новости).
 * Содержимое выводит плагин через the_content().
 */

get_header();

while ( have_posts() ) :
	the_post();
	?>

<div class="kac-wrap">
  <?php the_content(); ?>
</div>

<?php
endwhile;

get_footer();
