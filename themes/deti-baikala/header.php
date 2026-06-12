<!DOCTYPE html>
<html <?php language_attributes(); ?> data-theme="light">
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<!-- ===== HEADER ===== -->
<header class="header" id="header">
  <div class="container">
    <div class="header__inner">
      <div class="logo">
        <?php db_render_logo(); ?>
      </div>
      <nav class="nav">
        <?php db_render_primary_nav(); ?>
      </nav>
      <div class="header__actions">
        <?php db_render_theme_toggle(); ?>
      </div>
      <button class="burger" id="burgerBtn" aria-label="<?php esc_attr_e( 'Меню', 'deti-baikala' ); ?>">
        <span></span><span></span><span></span>
      </button>
    </div>
  </div>
</header>

<!-- Mobile nav -->
<nav class="mobile-nav" id="mobileNav">
  <?php db_render_primary_nav(); ?>
</nav>
