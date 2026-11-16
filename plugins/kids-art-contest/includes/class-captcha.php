<?php
defined( 'ABSPATH' ) || exit;

/**
 * Капча: встроенная, Cloudflare Turnstile, Яндекс SmartCaptcha, Google reCAPTCHA v3.
 * Секретный ключ хранится в отдельной опции kac_secrets (autoload=no).
 */
class KAC_Captcha {

	public static function providers(): array {
		return array(
			'builtin'      => __( 'Простая встроенная (вопрос-пример)', 'kids-art-contest' ),
			'smartcaptcha' => __( 'Яндекс SmartCaptcha', 'kids-art-contest' ),
			'turnstile'    => __( 'Cloudflare Turnstile', 'kids-art-contest' ),
			'recaptcha3'   => __( 'Google reCAPTCHA v3', 'kids-art-contest' ),
		);
	}

	public static function provider(): string {
		$p = (string) kac_setting( 'captcha_provider', 'builtin' );
		if ( 'builtin' !== $p && ( '' === (string) kac_setting( 'captcha_site_key' ) || '' === self::secret() ) ) {
			return 'builtin'; // Ключи не заданы — безопасный запасной вариант.
		}
		return isset( self::providers()[ $p ] ) ? $p : 'builtin';
	}

	public static function secret(): string {
		$s = get_option( 'kac_secrets', array() );
		return is_array( $s ) && isset( $s['captcha_secret'] ) ? (string) $s['captcha_secret'] : '';
	}

	public static function save_secret( string $secret ): void {
		$s = get_option( 'kac_secrets', array() );
		$s = is_array( $s ) ? $s : array();
		$s['captcha_secret'] = $secret;
		update_option( 'kac_secrets', $s, false );
	}

	/** Что нужно JS, чтобы показать капчу. */
	public static function client_config(): array {
		$p = self::provider();
		return array(
			'provider' => $p,
			'siteKey'  => 'builtin' === $p ? '' : (string) kac_setting( 'captcha_site_key' ),
		);
	}

	/**
	 * Проверка ответа капчи из параметров запроса.
	 *
	 * @param array $params captcha_token, captcha_answer (встроенная) или captcha_response (внешние).
	 */
	public static function verify( array $params ): bool {
		$p = self::provider();
		if ( 'builtin' === $p ) {
			return KAC_Security::captcha_check( (string) ( $params['captcha_token'] ?? '' ), (string) ( $params['captcha_answer'] ?? '' ) );
		}
		$resp = (string) ( $params['captcha_response'] ?? '' );
		if ( '' === $resp ) {
			return false;
		}
		$ip     = KAC_Security::ip();
		$secret = self::secret();
		switch ( $p ) {
			case 'turnstile':
				$r = wp_remote_post( 'https://challenges.cloudflare.com/turnstile/v0/siteverify', array( 'timeout' => 8, 'body' => array( 'secret' => $secret, 'response' => $resp, 'remoteip' => $ip ) ) );
				return self::json_flag( $r, 'success' );
			case 'recaptcha3':
				$r = wp_remote_post( 'https://www.google.com/recaptcha/api/siteverify', array( 'timeout' => 8, 'body' => array( 'secret' => $secret, 'response' => $resp, 'remoteip' => $ip ) ) );
				$d = self::json( $r );
				return ! empty( $d['success'] ) && (float) ( $d['score'] ?? 0 ) >= 0.5;
			case 'smartcaptcha':
				$r = wp_remote_post( 'https://smartcaptcha.yandexcloud.net/validate', array( 'timeout' => 8, 'body' => array( 'secret' => $secret, 'token' => $resp, 'ip' => $ip ) ) );
				$d = self::json( $r );
				return isset( $d['status'] ) && 'ok' === $d['status'];
		}
		return false;
	}

	private static function json( $r ): array {
		if ( is_wp_error( $r ) ) {
			return array();
		}
		$d = json_decode( (string) wp_remote_retrieve_body( $r ), true );
		return is_array( $d ) ? $d : array();
	}

	private static function json_flag( $r, string $key ): bool {
		$d = self::json( $r );
		return ! empty( $d[ $key ] );
	}
}
