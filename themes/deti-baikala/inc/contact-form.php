<?php
/**
 * Дети Байкала — обработчик формы обратной связи (страница «Контакты»).
 */

if ( ! defined( 'ABSPATH' ) ) exit;

function db_handle_contact_form() {
	if ( ! isset( $_POST['db_contact_nonce'] ) || ! wp_verify_nonce( $_POST['db_contact_nonce'], 'db_contact_form' ) ) {
		wp_die( esc_html__( 'Ошибка проверки формы. Обновите страницу и попробуйте снова.', 'deti-baikala' ) );
	}

	$name    = isset( $_POST['db_name'] ) ? sanitize_text_field( wp_unslash( $_POST['db_name'] ) ) : '';
	$email   = isset( $_POST['db_email'] ) ? sanitize_email( wp_unslash( $_POST['db_email'] ) ) : '';
	$subject = isset( $_POST['db_subject'] ) ? sanitize_text_field( wp_unslash( $_POST['db_subject'] ) ) : '';
	$message = isset( $_POST['db_message'] ) ? sanitize_textarea_field( wp_unslash( $_POST['db_message'] ) ) : '';

	$redirect = wp_get_referer() ? wp_get_referer() : home_url( '/' );

	if ( empty( $name ) || empty( $email ) || empty( $message ) || ! is_email( $email ) ) {
		wp_safe_redirect( add_query_arg( 'db_contact', 'error', $redirect ) . '#contact-form' );
		exit;
	}

	$to = get_theme_mod( 'db_contact_email' );
	if ( empty( $to ) ) {
		$to = get_option( 'admin_email' );
	}

	$mail_subject = sprintf( '[%s] %s', wp_strip_all_tags( get_bloginfo( 'name' ) ), $subject ? $subject : __( 'Сообщение с сайта', 'deti-baikala' ) );

	$body  = __( 'Имя:', 'deti-baikala' ) . ' ' . $name . "\n";
	$body .= __( 'Email:', 'deti-baikala' ) . ' ' . $email . "\n";
	if ( $subject ) {
		$body .= __( 'Тема:', 'deti-baikala' ) . ' ' . $subject . "\n";
	}
	$body .= "\n" . $message;

	$headers = array( 'Reply-To: ' . $email );

	wp_mail( $to, $mail_subject, $body, $headers );

	wp_safe_redirect( add_query_arg( 'db_contact', 'success', $redirect ) . '#contact-form' );
	exit;
}
add_action( 'admin_post_db_contact_form', 'db_handle_contact_form' );
add_action( 'admin_post_nopriv_db_contact_form', 'db_handle_contact_form' );
