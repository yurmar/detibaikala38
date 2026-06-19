<?php
/**
 * Template Name: Наставник
 *
 * Страница проекта «Наставник» по мотивам nastavnik38.ru.
 * Весь контент управляется через метабоксы в админке.
 */

get_header();

$pid    = get_the_ID();
$ns     = static function ( $key, $default = '' ) use ( $pid ) {
	$val = get_post_meta( $pid, $key, true );
	return '' !== $val ? $val : $default;
};
$ns_img = static function ( $key ) use ( $pid ) {
	$id = get_post_meta( $pid, $key, true );
	return $id ? wp_get_attachment_url( absint( $id ) ) : '';
};

?>

<!-- ===== INTRO ===== -->
<?php
$intro_img  = $ns_img( '_ns_intro_img' );
$intro_text = $ns( '_ns_intro_text' );
if ( $intro_img || $intro_text ) :
?>
<section class="ns-intro">
  <div class="container">
    <div class="ns-intro__grid reveal">
      <?php if ( $intro_img ) : ?>
      <div class="ns-intro__img">
        <img src="<?php echo esc_url( $intro_img ); ?>" alt="">
      </div>
      <?php endif; ?>
      <?php if ( $intro_text ) : ?>
      <div class="ns-intro__text"><?php echo nl2br( esc_html( $intro_text ) ); ?></div>
      <?php endif; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<!-- ===== ОСНОВНОЙ КОНТЕНТ СТРАНИЦЫ ===== -->
<?php
$page_content = apply_filters( 'the_content', get_the_content() );
if ( $page_content ) :
?>
<section class="section ns-page-content">
  <div class="container">
    <div class="ns-page-content__body reveal">
      <?php echo $page_content; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<!-- ===== БАННЕР 1 ===== -->
<?php
$b1_bg    = $ns_img( '_ns_banner1_bg' );
$b1_title = $ns( '_ns_banner1_title' );
$b1_text  = $ns( '_ns_banner1_text' );
$b1_btn   = $ns( '_ns_banner1_btn_text' );
$b1_url   = $ns( '_ns_banner1_btn_url' );
if ( $b1_title ) :
?>
<section class="ns-banner">
  <div class="ns-banner__bg">
    <?php if ( $b1_bg ) : ?>
    <img src="<?php echo esc_url( $b1_bg ); ?>" alt="" aria-hidden="true">
    <?php endif; ?>
  </div>
  <div class="ns-banner__content reveal">
    <p class="ns-banner__title"><?php echo nl2br( esc_html( $b1_title ) ); ?></p>
    <?php if ( $b1_text ) : ?>
    <p class="ns-banner__text"><?php echo nl2br( esc_html( $b1_text ) ); ?></p>
    <?php endif; ?>
    <?php if ( $b1_btn ) : ?>
    <a href="<?php echo esc_url( $b1_url ?: '#' ); ?>" class="btn btn-primary"><?php echo esc_html( $b1_btn ); ?></a>
    <?php endif; ?>
  </div>
</section>
<?php endif; ?>

<!-- ===== FEATURES ===== -->
<?php
$feat_label = $ns( '_ns_features_label' );
$feat_title = $ns( '_ns_features_title' );
$feat_btn   = $ns( '_ns_features_btn_text' );
$feat_url   = $ns( '_ns_features_btn_url' );

$features = array();
for ( $i = 1; $i <= 4; $i++ ) {
	$icon_id  = get_post_meta( $pid, "_ns_feat{$i}_icon", true );
	$icon_url = $icon_id ? wp_get_attachment_url( absint( $icon_id ) ) : '';
	$title    = $ns( "_ns_feat{$i}_title" );
	$text     = $ns( "_ns_feat{$i}_text" );
	if ( $icon_url || $title || $text ) {
		$features[] = array( 'icon_url' => $icon_url, 'title' => $title, 'text' => $text );
	}
}

if ( $feat_title || $features ) :
?>
<section class="section section--alt">
  <div class="container">
    <?php if ( $feat_title ) : ?>
    <div class="section-head reveal">
      <?php if ( $feat_label ) : ?>
      <div class="section-label"><?php echo esc_html( $feat_label ); ?></div>
      <?php endif; ?>
      <h2><?php echo esc_html( $feat_title ); ?></h2>
    </div>
    <?php endif; ?>

    <?php if ( $features ) : ?>
    <div class="ns-features__grid">
      <?php foreach ( $features as $idx => $feat ) : ?>
      <div class="ns-feature-card reveal reveal-delay-<?php echo esc_attr( $idx + 1 ); ?>">
        <?php if ( $feat['icon_url'] ) : ?>
        <div class="ns-feature-card__icon">
          <img src="<?php echo esc_url( $feat['icon_url'] ); ?>" alt="">
        </div>
        <?php endif; ?>
        <?php if ( $feat['title'] ) : ?>
        <div class="ns-feature-card__title"><?php echo nl2br( esc_html( $feat['title'] ) ); ?></div>
        <?php endif; ?>
        <?php if ( $feat['text'] ) : ?>
        <div class="ns-feature-card__text"><?php echo nl2br( esc_html( $feat['text'] ) ); ?></div>
        <?php endif; ?>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <?php if ( $feat_btn ) : ?>
    <div class="ns-actions reveal">
      <a href="<?php echo esc_url( $feat_url ?: '#' ); ?>" class="btn btn-outline"><?php echo esc_html( $feat_btn ); ?></a>
    </div>
    <?php endif; ?>
  </div>
</section>
<?php endif; ?>

<!-- ===== БАННЕР 2 (результаты) ===== -->
<?php
$res_bg   = $ns_img( '_ns_results_bg' );
$res_text = $ns( '_ns_results_text' );
if ( $res_text ) :
?>
<section class="ns-banner">
  <div class="ns-banner__bg">
    <?php if ( $res_bg ) : ?>
    <img src="<?php echo esc_url( $res_bg ); ?>" alt="" aria-hidden="true">
    <?php endif; ?>
  </div>
  <div class="ns-banner__content reveal">
    <blockquote class="ns-banner__quote"><?php echo nl2br( esc_html( $res_text ) ); ?></blockquote>
  </div>
</section>
<?php endif; ?>

<!-- ===== КАК ЭТО РАБОТАЕТ ===== -->
<?php
$steps_label = $ns( '_ns_steps_label' );
$steps_title = $ns( '_ns_steps_title' );
$steps_btn   = $ns( '_ns_steps_btn_text' );
$steps_url   = $ns( '_ns_steps_btn_url' );

$steps = array();
for ( $i = 1; $i <= 5; $i++ ) {
	$t = $ns( "_ns_step{$i}" );
	if ( $t ) {
		$steps[] = $t;
	}
}

if ( $steps_title || $steps ) :
?>
<section class="section">
  <div class="container">
    <?php if ( $steps_title ) : ?>
    <div class="section-head reveal">
      <?php if ( $steps_label ) : ?>
      <div class="section-label"><?php echo esc_html( $steps_label ); ?></div>
      <?php endif; ?>
      <h2><?php echo esc_html( $steps_title ); ?></h2>
    </div>
    <?php endif; ?>

    <?php if ( $steps ) : ?>
    <div class="ns-steps__list">
      <?php foreach ( $steps as $idx => $step ) : ?>
      <div class="ns-step reveal reveal-delay-<?php echo esc_attr( min( $idx + 1, 6 ) ); ?>">
        <div class="ns-step__num"><?php echo esc_html( $idx + 1 ); ?>.</div>
        <div class="ns-step__text"><?php echo nl2br( esc_html( $step ) ); ?></div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <?php if ( $steps_btn ) : ?>
    <div class="ns-actions reveal">
      <a href="<?php echo esc_url( $steps_url ?: '#' ); ?>" class="btn btn-primary"><?php echo esc_html( $steps_btn ); ?></a>
    </div>
    <?php endif; ?>
  </div>
</section>
<?php endif; ?>

<!-- ===== ФИНАЛЬНЫЙ БАННЕР ===== -->
<?php
$final_bg   = $ns_img( '_ns_final_bg' );
$final_text = $ns( '_ns_final_text' );
if ( $final_text ) :
?>
<section class="ns-banner">
  <div class="ns-banner__bg">
    <?php if ( $final_bg ) : ?>
    <img src="<?php echo esc_url( $final_bg ); ?>" alt="" aria-hidden="true">
    <?php endif; ?>
  </div>
  <div class="ns-banner__content reveal">
    <blockquote class="ns-banner__quote"><?php echo nl2br( esc_html( $final_text ) ); ?></blockquote>
  </div>
</section>
<?php endif; ?>

<?php get_footer(); ?>
