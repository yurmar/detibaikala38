<?php
defined( 'ABSPATH' ) || exit;

/**
 * IP, хеши, nonce и встроенная капча.
 */
class KAC_Security {

	public static function hash( string $value ): string {
		return hash_hmac( 'sha256', $value, wp_salt( 'auth' ) . 'kac' );
	}

	public static function ip(): string {
		$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
		if ( kac_setting( 'trust_proxy' ) && ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
			$parts = explode( ',', sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) );
			$first = trim( $parts[0] );
			if ( filter_var( $first, FILTER_VALIDATE_IP ) ) {
				$ip = $first;
			}
		}
		$ip = (string) apply_filters( 'kac_client_ip', $ip );
		return filter_var( $ip, FILTER_VALIDATE_IP ) ? $ip : '0.0.0.0';
	}

	public static function mask_ip( string $ip ): string {
		if ( false !== strpos( $ip, ':' ) ) {
			$p = explode( ':', $ip );
			return ( $p[0] ?? '' ) . ':' . ( $p[1] ?? '' ) . ':***';
		}
		$p = explode( '.', $ip );
		return count( $p ) === 4 ? "{$p[0]}.{$p[1]}.***.{$p[3]}" : '***';
	}

	public static function subnet( string $ip ): string {
		if ( false !== strpos( $ip, ':' ) ) {
			return implode( ':', array_slice( explode( ':', $ip ), 0, 3 ) );
		}
		$p = explode( '.', $ip );
		return count( $p ) === 4 ? "{$p[0]}.{$p[1]}.{$p[2]}" : $ip;
	}

	public static function user_agent(): string {
		return isset( $_SERVER['HTTP_USER_AGENT'] ) ? substr( sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ), 0, 400 ) : '';
	}

	/* ---- Публичный nonce (выдаётся через REST /token, не кешируется) ---- */

	public static function make_nonce(): string {
		$tick = (int) ceil( time() / 43200 );
		return substr( hash_hmac( 'sha256', 'kac_public|' . $tick, wp_salt( 'nonce' ) ), 0, 24 ) . '.' . $tick;
	}

	public static function check_nonce( string $nonce ): bool {
		$parts = explode( '.', $nonce );
		if ( 2 !== count( $parts ) ) {
			return false;
		}
		$tick = (int) $parts[1];
		if ( $tick < (int) ceil( time() / 43200 ) - 4 ) { // Живёт ~2 суток.
			return false;
		}
		$expect = substr( hash_hmac( 'sha256', 'kac_public|' . $tick, wp_salt( 'nonce' ) ), 0, 24 );
		return hash_equals( $expect, $parts[0] );
	}

	/* ---- Капча: простой пример, токен подписан HMAC ---- */

	public static function captcha_challenge(): array {
		$a       = wp_rand( 2, 9 );
		$b       = wp_rand( 2, 9 );
		$nonce   = wp_generate_password( 8, false );
		$payload = base64_encode( wp_json_encode( array( 'h' => self::captcha_hash( $a + $b, $nonce ), 'e' => time() + 1800, 'n' => $nonce ) ) );
		$sig     = hash_hmac( 'sha256', $payload, wp_salt( 'secure_auth' ) );
		return array(
			'question' => sprintf( '%d + %d', $a, $b ),
			'token'    => $payload . '.' . $sig,
		);
	}

	private static function captcha_hash( int $answer, string $nonce ): string {
		return hash_hmac( 'sha256', $answer . '|' . $nonce, wp_salt( 'secure_auth' ) . 'cap' );
	}

	public static function captcha_check( string $token, string $answer ): bool {
		$parts = explode( '.', $token );
		if ( 2 !== count( $parts ) ) {
			return false;
		}
		if ( ! hash_equals( hash_hmac( 'sha256', $parts[0], wp_salt( 'secure_auth' ) ), $parts[1] ) ) {
			return false;
		}
		$data = json_decode( base64_decode( $parts[0] ), true );
		if ( ! is_array( $data ) || $data['e'] < time() || (string) (int) $answer !== trim( $answer ) ) {
			return false;
		}
		$key = 'kac_cap_' . md5( $token );
		if ( get_transient( $key ) ) {
			return false; // Одноразовость.
		}
		if ( ! hash_equals( (string) $data['h'], self::captcha_hash( (int) $answer, (string) $data['n'] ) ) ) {
			return false;
		}
		set_transient( $key, 1, 1800 );
		return true;
	}

	/**
	 * Простой лимит: не более $max действий за $window секунд на ключ.
	 */
	/** Проверка лимита без увеличения счётчика. */
	public static function rate_exceeded( string $key, int $max ): bool {
		return (int) get_transient( 'kac_rl_' . md5( $key ) ) >= $max;
	}

	public static function rate_limit( string $key, int $max, int $window ): bool {
		$k = 'kac_rl_' . md5( $key );
		$n = (int) get_transient( $k );
		if ( $n >= $max ) {
			return false;
		}
		set_transient( $k, $n + 1, $window );
		return true;
	}
}
