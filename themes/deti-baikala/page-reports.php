<?php
/**
 * Template Name: Отчёты
 */

get_header();

while ( have_posts() ) :
	the_post();

	$years = get_terms(
		array(
			'taxonomy'   => 'report_year',
			'hide_empty' => true,
			'orderby'    => 'name',
			'order'      => 'DESC',
		)
	);
	?>

<div class="page-wrap">
  <main class="page-main">
    <div class="section-label"><?php esc_html_e( 'Прозрачность', 'deti-baikala' ); ?></div>
    <h1 class="page-title"><?php the_title(); ?></h1>
    <?php if ( get_the_excerpt() ) : ?>
      <p class="page-subtitle"><?php echo esc_html( get_the_excerpt() ); ?></p>
    <?php endif; ?>

    <?php if ( ! empty( $years ) && ! is_wp_error( $years ) ) : ?>
      <div class="reports-years" id="yearBtns">
        <?php foreach ( $years as $i => $year ) : ?>
          <button class="year-btn<?php echo 0 === $i ? ' active' : ''; ?>" data-year="<?php echo esc_attr( $year->slug ); ?>"><?php echo esc_html( $year->name ); ?></button>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <?php
    $reports = new WP_Query(
        array(
            'post_type'      => 'report_item',
            'posts_per_page' => -1,
            'orderby'        => 'menu_order',
            'order'          => 'ASC',
        )
    );
    ?>

    <?php if ( $reports->have_posts() ) : ?>
      <div class="reports-list" id="reportsList">
        <?php while ( $reports->have_posts() ) : $reports->the_post();
            $icon     = get_post_meta( get_the_ID(), '_report_icon', true ) ?: '📊';
            $date     = get_post_meta( get_the_ID(), '_report_date', true );
            $file_id  = get_post_meta( get_the_ID(), '_report_file', true );
            $file_url = $file_id ? wp_get_attachment_url( $file_id ) : '';
            $terms    = get_the_terms( get_the_ID(), 'report_year' );
            $year     = ( $terms && ! is_wp_error( $terms ) ) ? $terms[0]->slug : '';
            ?>
          <div class="report-item" data-year="<?php echo esc_attr( $year ); ?>">
            <div class="report-item__icon"><?php echo esc_html( $icon ); ?></div>
            <div class="report-item__info">
              <div class="report-item__name"><?php the_title(); ?></div>
              <?php if ( $date ) : ?>
                <div class="report-item__date"><?php echo esc_html( $date ); ?></div>
              <?php endif; ?>
            </div>
            <?php if ( $file_url ) : ?>
              <a class="report-item__dl" href="<?php echo esc_url( $file_url ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'Скачать PDF', 'deti-baikala' ); ?></a>
            <?php else : ?>
              <span class="report-item__dl"><?php esc_html_e( 'Скачать PDF', 'deti-baikala' ); ?></span>
            <?php endif; ?>
          </div>
        <?php endwhile; wp_reset_postdata(); ?>
      </div>
    <?php else : ?>
      <p><?php esc_html_e( 'Отчёты пока не добавлены.', 'deti-baikala' ); ?></p>
    <?php endif; ?>
  </main>

  <?php get_template_part( 'template-parts/sidebar' ); ?>
</div>

<?php
endwhile;

get_footer();
