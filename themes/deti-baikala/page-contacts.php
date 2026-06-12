<?php
/**
 * Template Name: Контакты
 */

get_header();

while ( have_posts() ) :
	the_post();
	?>

<div class="page-wrap">
  <main class="page-main">
    <div class="section-label"><?php esc_html_e( 'Связаться с нами', 'deti-baikala' ); ?></div>
    <h1 class="page-title"><?php the_title(); ?></h1>
    <?php if ( get_the_excerpt() ) : ?>
      <p class="page-subtitle"><?php echo esc_html( get_the_excerpt() ); ?></p>
    <?php endif; ?>

    <?php if ( get_the_content() ) : ?>
      <div class="about-text-wrap"><?php the_content(); ?></div>
    <?php endif; ?>

    <div class="contacts-grid">
      <?php if ( get_theme_mod( 'db_contact_address' ) ) : ?>
        <div class="contact-block">
          <div class="contact-block__icon">📍</div>
          <div class="contact-block__label"><?php esc_html_e( 'Юридический и фактический адрес', 'deti-baikala' ); ?></div>
          <div class="contact-block__value"><?php echo esc_html( get_theme_mod( 'db_contact_address' ) ); ?></div>
        </div>
      <?php endif; ?>

      <?php if ( get_theme_mod( 'db_contact_phone' ) ) : ?>
        <div class="contact-block">
          <div class="contact-block__icon">📞</div>
          <div class="contact-block__label"><?php esc_html_e( 'Телефон', 'deti-baikala' ); ?></div>
          <div class="contact-block__value"><?php echo esc_html( get_theme_mod( 'db_contact_phone' ) ); ?></div>
          <?php if ( get_theme_mod( 'db_contact_hours' ) ) : ?>
            <div class="contact-block__sub"><?php echo esc_html( get_theme_mod( 'db_contact_hours' ) ); ?></div>
          <?php endif; ?>
        </div>
      <?php endif; ?>

      <?php if ( get_theme_mod( 'db_contact_email' ) ) : ?>
        <div class="contact-block">
          <div class="contact-block__icon">✉️</div>
          <div class="contact-block__label"><?php esc_html_e( 'Электронная почта', 'deti-baikala' ); ?></div>
          <div class="contact-block__value"><?php echo esc_html( get_theme_mod( 'db_contact_email' ) ); ?></div>
          <div class="contact-block__sub"><?php esc_html_e( 'Для общих вопросов', 'deti-baikala' ); ?></div>
        </div>
      <?php endif; ?>

      <?php if ( get_theme_mod( 'db_contact_email_partners' ) ) : ?>
        <div class="contact-block">
          <div class="contact-block__icon">🤝</div>
          <div class="contact-block__label"><?php esc_html_e( 'Партнёрство и спонсорство', 'deti-baikala' ); ?></div>
          <div class="contact-block__value"><?php echo esc_html( get_theme_mod( 'db_contact_email_partners' ) ); ?></div>
          <div class="contact-block__sub"><?php esc_html_e( 'Для корпоративных партнёров', 'deti-baikala' ); ?></div>
        </div>
      <?php endif; ?>

      <?php if ( get_theme_mod( 'db_contact_email_press' ) ) : ?>
        <div class="contact-block">
          <div class="contact-block__icon">📰</div>
          <div class="contact-block__label"><?php esc_html_e( 'Пресс-служба', 'deti-baikala' ); ?></div>
          <div class="contact-block__value"><?php echo esc_html( get_theme_mod( 'db_contact_email_press' ) ); ?></div>
          <div class="contact-block__sub"><?php esc_html_e( 'Для представителей СМИ', 'deti-baikala' ); ?></div>
        </div>
      <?php endif; ?>

      <?php if ( get_theme_mod( 'db_contact_email_volunteer' ) ) : ?>
        <div class="contact-block">
          <div class="contact-block__icon">🙋</div>
          <div class="contact-block__label"><?php esc_html_e( 'Волонтёрство', 'deti-baikala' ); ?></div>
          <div class="contact-block__value"><?php echo esc_html( get_theme_mod( 'db_contact_email_volunteer' ) ); ?></div>
          <div class="contact-block__sub"><?php esc_html_e( 'Хотите помочь? Напишите нам!', 'deti-baikala' ); ?></div>
        </div>
      <?php endif; ?>
    </div>

    <!-- Contact form -->
    <div class="donate-form" id="contact-form" style="margin-top:2rem">
      <h3><?php esc_html_e( 'Написать нам', 'deti-baikala' ); ?></h3>

      <?php if ( isset( $_GET['db_contact'] ) && 'success' === $_GET['db_contact'] ) : ?>
        <div class="form-success"><?php esc_html_e( 'Спасибо! Ваше сообщение отправлено.', 'deti-baikala' ); ?></div>
      <?php elseif ( isset( $_GET['db_contact'] ) && 'error' === $_GET['db_contact'] ) : ?>
        <div class="form-success" style="border-color:#c0392b;color:#c0392b"><?php esc_html_e( 'Пожалуйста, заполните обязательные поля корректно.', 'deti-baikala' ); ?></div>
      <?php endif; ?>

      <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
        <input type="hidden" name="action" value="db_contact_form">
        <?php wp_nonce_field( 'db_contact_form', 'db_contact_nonce' ); ?>

        <div class="form-group">
          <label class="form-label"><?php esc_html_e( 'Имя', 'deti-baikala' ); ?></label>
          <input type="text" name="db_name" class="form-input" placeholder="<?php esc_attr_e( 'Ваше имя', 'deti-baikala' ); ?>" required>
        </div>
        <div class="form-group">
          <label class="form-label">Email</label>
          <input type="email" name="db_email" class="form-input" placeholder="email@example.com" required>
        </div>
        <div class="form-group">
          <label class="form-label"><?php esc_html_e( 'Тема', 'deti-baikala' ); ?></label>
          <select name="db_subject" class="form-input">
            <option><?php esc_html_e( 'Общий вопрос', 'deti-baikala' ); ?></option>
            <option><?php esc_html_e( 'Волонтёрство', 'deti-baikala' ); ?></option>
            <option><?php esc_html_e( 'Партнёрство', 'deti-baikala' ); ?></option>
            <option><?php esc_html_e( 'Пожертвование', 'deti-baikala' ); ?></option>
            <option><?php esc_html_e( 'СМИ', 'deti-baikala' ); ?></option>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label"><?php esc_html_e( 'Сообщение', 'deti-baikala' ); ?></label>
          <textarea name="db_message" class="form-input" rows="5" placeholder="<?php esc_attr_e( 'Ваше сообщение...', 'deti-baikala' ); ?>" required></textarea>
        </div>
        <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center"><?php esc_html_e( 'Отправить сообщение', 'deti-baikala' ); ?></button>
      </form>
    </div>

    <?php db_render_requisites(); ?>
  </main>

  <?php get_template_part( 'template-parts/sidebar' ); ?>
</div>

<?php
endwhile;

get_footer();
