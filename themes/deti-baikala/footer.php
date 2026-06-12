<!-- ===== FOOTER ===== -->
<footer class="footer">
  <div class="container">
    <div class="footer__grid">
      <div class="footer__brand">
        <div class="logo">
          <?php db_render_logo( true ); ?>
        </div>
        <p><?php echo nl2br( esc_html( get_theme_mod( 'db_footer_text', '' ) ) ); ?></p>
        <div class="footer__socials">
          <?php db_render_social_icons(); ?>
        </div>
      </div>

      <div>
        <div class="footer__col-title"><?php esc_html_e( 'Разделы', 'deti-baikala' ); ?></div>
        <div class="footer__links">
          <?php
          if ( has_nav_menu( 'footer-sections' ) ) {
              wp_nav_menu(
                  array(
                      'theme_location' => 'footer-sections',
                      'container'      => false,
                      'items_wrap'     => '%3$s',
                      'walker'         => new DB_Walker_Nav_Menu(),
                  )
              );
          }
          ?>
        </div>
      </div>

      <div>
        <div class="footer__col-title"><?php esc_html_e( 'Проекты', 'deti-baikala' ); ?></div>
        <div class="footer__links">
          <?php
          if ( has_nav_menu( 'footer-projects' ) ) {
              wp_nav_menu(
                  array(
                      'theme_location' => 'footer-projects',
                      'container'      => false,
                      'items_wrap'     => '%3$s',
                      'walker'         => new DB_Walker_Nav_Menu(),
                  )
              );
          } else {
              $projects = new WP_Query(
                  array(
                      'post_type'      => 'project',
                      'posts_per_page' => 5,
                  )
              );
              while ( $projects->have_posts() ) {
                  $projects->the_post();
                  printf( '<a href="%s">%s</a>', esc_url( get_permalink() ), esc_html( get_the_title() ) );
              }
              wp_reset_postdata();
          }
          ?>
        </div>
      </div>

      <div>
        <div class="footer__col-title"><?php esc_html_e( 'Контакты', 'deti-baikala' ); ?></div>
        <?php if ( get_theme_mod( 'db_contact_address' ) ) : ?>
        <div class="footer__contact-item">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
          <span><?php echo esc_html( get_theme_mod( 'db_contact_address' ) ); ?></span>
        </div>
        <?php endif; ?>
        <?php if ( get_theme_mod( 'db_contact_phone' ) ) : ?>
        <div class="footer__contact-item">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 13.3a19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 3.6 2.44h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L7.91 10a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
          <span><?php echo esc_html( get_theme_mod( 'db_contact_phone' ) ); ?></span>
        </div>
        <?php endif; ?>
        <?php if ( get_theme_mod( 'db_contact_email' ) ) : ?>
        <div class="footer__contact-item">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
          <span><?php echo esc_html( get_theme_mod( 'db_contact_email' ) ); ?></span>
        </div>
        <?php endif; ?>
      </div>
    </div>

    <div class="footer__bottom">
      <div class="footer__copy"><?php db_render_copyright(); ?></div>
      <div class="footer__right">
        <?php db_render_payment_badge(); ?>
        <a href="https://yurmar.ru" class="footer__credit" target="_blank" rel="noopener"><?php esc_html_e( 'Разработано YurMar', 'deti-baikala' ); ?></a>
      </div>
    </div>
  </div>
</footer>

<!-- Scroll to top -->
<button class="scroll-top" id="scrollTop" title="<?php esc_attr_e( 'Наверх', 'deti-baikala' ); ?>">↑</button>

<?php wp_footer(); ?>
</body>
</html>
