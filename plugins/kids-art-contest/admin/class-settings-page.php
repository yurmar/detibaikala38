<?php
defined( 'ABSPATH' ) || exit;

/**
 * Настройки плагина (макет, экраны 16–17): одна опция kac_settings, вкладки на одной форме.
 */
class KAC_Settings_Page {

	public static function init(): void {
		add_action( 'admin_init', array( __CLASS__, 'register' ) );
	}

	public static function register(): void {
		register_setting( 'kac_settings_group', 'kac_settings', array( 'type' => 'array', 'sanitize_callback' => array( __CLASS__, 'sanitize' ) ) );
	}

	/* ---------- Сохранение ---------- */

	public static function sanitize( $in ) {
		$old = get_option( 'kac_settings', array() );
		$new = array_merge( kac_default_settings(), is_array( $old ) ? $old : array() );
		if ( ! current_user_can( 'kac_manage_settings' ) || ! is_array( $in ) || empty( $in['_form'] ) ) {
			return $new;
		}
		$bool = static fn( string $k ) => empty( $in[ $k ] ) ? 0 : 1;
		$int  = static fn( string $k, int $min, int $max, int $def ) => isset( $in[ $k ] ) ? max( $min, min( $max, (int) $in[ $k ] ) ) : $def;
		$txt  = static fn( string $k ) => isset( $in[ $k ] ) ? sanitize_text_field( $in[ $k ] ) : '';
		$area = static fn( string $k ) => isset( $in[ $k ] ) ? sanitize_textarea_field( $in[ $k ] ) : '';
		$enum = static fn( string $k, array $allowed, string $def ) => isset( $in[ $k ] ) && in_array( $in[ $k ], $allowed, true ) ? $in[ $k ] : $def;

		// Голосование и защита.
		foreach ( array( 'use_fingerprint', 'ip_limit_enabled', 'captcha_suspicious', 'captcha_always', 'captcha_submit', 'trust_proxy' ) as $k ) {
			$new[ $k ] = $bool( $k );
		}
		$new['vote_ip_daily_limit'] = $int( 'vote_ip_daily_limit', 1, 100, 3 );
		$new['risk_threshold']      = $int( 'risk_threshold', 10, 100, 60 );
		$new['spike_threshold']     = $int( 'spike_threshold', 5, 1000, 30 );
		$new['captcha_provider']    = $enum( 'captcha_provider', array_keys( KAC_Captcha::providers() ), 'builtin' );
		$new['captcha_site_key']    = $txt( 'captcha_site_key' );
		$new['alert_emails']        = $txt( 'alert_emails' );
		$new['tie_mode']            = $enum( 'tie_mode', array( 'earlier_reached', 'earlier_sent', 'share' ), 'earlier_reached' );
		if ( isset( $in['captcha_secret'] ) && '' !== trim( $in['captcha_secret'] ) ) {
			KAC_Captcha::save_secret( sanitize_text_field( $in['captcha_secret'] ) );
		}

		// Файлы.
		$new['upload_max_bytes'] = max( 102400, (int) round( (float) str_replace( ',', '.', (string) ( $in['upload_max_mb'] ?? 2 ) ) * 1048576 ) );
		foreach ( array( 'upload_min_w' => 1080, 'upload_min_h' => 1080, 'upload_max_w' => 1920, 'upload_max_h' => 1920 ) as $k => $def ) {
			$new[ $k ] = $int( $k, 100, 10000, $def );
		}
		if ( $new['upload_min_w'] > $new['upload_max_w'] || $new['upload_min_h'] > $new['upload_max_h'] ) {
			add_settings_error( 'kac_settings', 'px', __( 'Минимальный размер изображения больше максимального — значения не сохранены.', 'kids-art-contest' ) );
			foreach ( array( 'upload_min_w', 'upload_min_h', 'upload_max_w', 'upload_max_h' ) as $k ) {
				$new[ $k ] = $old[ $k ] ?? kac_default_settings()[ $k ];
			}
		}
		$formats = isset( $in['upload_formats'] ) ? array_values( array_intersect( array( 'jpg', 'png', 'webp' ), array_map( 'sanitize_key', (array) $in['upload_formats'] ) ) ) : array();
		$new['upload_formats'] = $formats ? $formats : array( 'jpg', 'png', 'webp' );
		foreach ( array( 'client_shrink', 'strip_exif', 'check_duplicates' ) as $k ) {
			$new[ $k ] = $bool( $k );
		}

		// Форма.
		foreach ( array( 'child_middle', 'city', 'school', 'teacher', 'technique', 'description' ) as $f ) {
			$new[ 'f_' . $f ] = $enum( 'f_' . $f, array( 'hidden', 'optional', 'required' ), 'optional' );
		}
		$new['public_name_format'] = $enum( 'public_name_format', array( '{first} {last_initial}.', '{last_initial}. {first}', '{first}' ), '{first} {last_initial}.' );
		$new['relations']          = $area( 'relations' );

		// Письма.
		$new['moderator_notify'] = $enum( 'moderator_notify', array( 'instant', 'digest', 'off' ), 'instant' );
		foreach ( array( 'mail_received_enabled', 'mail_approved_enabled', 'mail_rejected_enabled', 'mail_winner_enabled', 'mail_results_all', 'mail_spike_enabled' ) as $k ) {
			$new[ $k ] = $bool( $k );
		}
		foreach ( array_keys( KAC_Mailer::default_templates() ) as $k ) {
			$new[ "mail_{$k}_subject" ] = $txt( "mail_{$k}_subject" );
			$new[ "mail_{$k}_body" ]    = $area( "mail_{$k}_body" );
		}
		$new['reject_reasons'] = $area( 'reject_reasons' );

		// Персональные данные.
		$old_texts = array( $old['consent_personal_text'] ?? '', $old['consent_publish_text'] ?? '', $old['consent_rules_text'] ?? '' );
		$new['consent_personal_text'] = $area( 'consent_personal_text' );
		$new['consent_publish_text']  = $area( 'consent_publish_text' );
		$new['consent_rules_text']    = $area( 'consent_rules_text' );
		if ( array( $new['consent_personal_text'], $new['consent_publish_text'], $new['consent_rules_text'] ) !== $old_texts ) {
			$new['consent_version'] = (int) ( $old['consent_version'] ?? 1 ) + 1; // Текст изменился — версия растёт сама.
		}
		$new['privacy_page_id']           = $int( 'privacy_page_id', 0, PHP_INT_MAX, 0 );
		$new['retention_pd_days']         = $int( 'retention_pd_days', 0, 3650, 180 );
		$new['retention_ip_days']         = $int( 'retention_ip_days', 0, 3650, 90 );
		$new['delete_data_on_uninstall']  = $bool( 'delete_data_on_uninstall' );

		// Внешний вид.
		$new['accent_color']     = sanitize_hex_color( (string) ( $in['accent_color'] ?? '' ) ) ?: '#ff6b5b';
		$new['ok_color']         = sanitize_hex_color( (string) ( $in['ok_color'] ?? '' ) ) ?: '#22b88f';
		$new['columns']          = $int( 'columns', 1, 5, 3 );
		$new['per_page']         = $int( 'per_page', 4, 60, 12 );
		$new['font']             = $enum( 'font', array( 'theme', 'nunito' ), 'theme' );
		$new['vote_button_text'] = $txt( 'vote_button_text' ) ?: 'Проголосовать за работу';

		// Права модератора.
		$role = get_role( 'kac_moderator' );
		if ( $role ) {
			$granted = isset( $in['mod_caps'] ) ? array_map( 'sanitize_key', (array) $in['mod_caps'] ) : array();
			foreach ( kac_all_caps() as $cap ) {
				if ( 'kac_manage_settings' === $cap ) {
					$role->remove_cap( $cap ); // Нельзя выдать.
				} else {
					in_array( $cap, $granted, true ) ? $role->add_cap( $cap ) : $role->remove_cap( $cap );
				}
			}
		}
		return $new;
	}

	/* ---------- Вывод ---------- */

	private static function n( string $k ): string {
		return 'kac_settings[' . $k . ']';
	}

	private static function cb( string $k, string $label, string $small = '', bool $disabled = false, ?bool $checked = null ): string {
		$on = null === $checked ? (bool) kac_setting( $k ) : $checked;
		return '<label class="ck"><input type="checkbox" name="' . esc_attr( self::n( $k ) ) . '" value="1" ' . checked( $on, true, false ) . ( $disabled ? ' disabled' : '' ) . '> <span>' . esc_html( $label ) . ( $small ? '<small>' . esc_html( $small ) . '</small>' : '' ) . '</span></label>';
	}

	private static function mode_row( string $f, string $label, bool $fixed = false ): void {
		$cur = (string) kac_setting( 'f_' . $f, 'optional' );
		echo '<tr><td>' . esc_html( $label ) . '</td>';
		if ( $fixed ) {
			echo '<td></td><td></td><td><input type="radio" checked disabled></td></tr>';
			return;
		}
		foreach ( array( 'hidden', 'optional', 'required' ) as $m ) {
			echo '<td><input type="radio" name="' . esc_attr( self::n( 'f_' . $f ) ) . '" value="' . esc_attr( $m ) . '" ' . checked( $cur, $m, false ) . '></td>';
		}
		echo '</tr>';
	}

	public static function render(): void {
		if ( ! current_user_can( 'kac_manage_settings' ) ) {
			wp_die( esc_html__( 'Нет доступа.', 'kids-art-contest' ) );
		}
		$tabs = array(
			't1' => __( 'Голосование и защита', 'kids-art-contest' ),
			't2' => __( 'Загрузка файлов', 'kids-art-contest' ),
			't3' => __( 'Форма', 'kids-art-contest' ),
			't4' => __( 'Уведомления', 'kids-art-contest' ),
			't5' => __( 'Персональные данные', 'kids-art-contest' ),
			't6' => __( 'Внешний вид', 'kids-art-contest' ),
			't7' => __( 'Права доступа', 'kids-art-contest' ),
		);
		$tab = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 't1'; // phpcs:ignore
		if ( 'roles' === $tab ) {
			$tab = 't7';
		}
		$tab = isset( $tabs[ $tab ] ) ? $tab : 't1';
		$s   = static fn( string $k ) => kac_setting( $k );
		?>
		<div class="wrap kac-a">
			<h1 class="kh"><?php esc_html_e( 'Настройки конкурса рисунков', 'kids-art-contest' ); ?></h1>
			<?php settings_errors( 'kac_settings' ); ?>
			<form method="post" action="options.php">
				<?php settings_fields( 'kac_settings_group' ); ?>
				<input type="hidden" name="kac_settings[_form]" value="1">
				<input type="hidden" name="_wp_http_referer" value="<?php echo esc_attr( admin_url( 'admin.php?page=kac-settings' ) ); ?>">
				<div class="wptabs" id="kac-settabs"><?php foreach ( $tabs as $k => $label ) : ?><a href="#" class="<?php echo $k === $tab ? 'on' : ''; ?>" data-t="<?php echo esc_attr( $k ); ?>"><?php echo esc_html( $label ); ?></a><?php endforeach; ?></div>

				<div class="stab <?php echo 't1' === $tab ? 'on' : ''; ?>" id="t1" <?php echo 't1' === $tab ? '' : 'hidden'; ?>>
					<div class="wpf wpf-top"><label><?php esc_html_e( 'Как узнаём голосующего', 'kids-art-contest' ); ?></label><div>
						<label class="ck"><input type="checkbox" checked disabled> <span><?php esc_html_e( 'Токен в cookie + localStorage', 'kids-art-contest' ); ?><small><?php esc_html_e( 'Основной способ, отключить нельзя', 'kids-art-contest' ); ?></small></span></label>
						<?php echo self::cb( 'use_fingerprint', __( 'Отпечаток браузера', 'kids-art-contest' ), __( 'Ловит голосование из режима инкогнито и после очистки cookie', 'kids-art-contest' ) ); // phpcs:ignore ?>
						<label class="ck"><input type="checkbox" name="<?php echo esc_attr( self::n( 'ip_limit_enabled' ) ); ?>" value="1" <?php checked( (bool) $s( 'ip_limit_enabled' ) ); ?>> <span><?php esc_html_e( 'Ограничение по IP', 'kids-art-contest' ); ?><small><?php esc_html_e( 'не больше', 'kids-art-contest' ); ?> <input class="wpi w80" type="number" min="1" max="100" name="<?php echo esc_attr( self::n( 'vote_ip_daily_limit' ) ); ?>" value="<?php echo (int) $s( 'vote_ip_daily_limit' ); ?>" style="min-height:22px"> <?php esc_html_e( 'голосов за одну работу с одного IP в сутки', 'kids-art-contest' ); ?></small></span></label>
						<?php echo self::cb( 'captcha_suspicious', __( 'Капча при подозрительной активности', 'kids-art-contest' ) ); // phpcs:ignore ?>
						<?php echo self::cb( 'captcha_always', __( 'Капча при каждом голосе', 'kids-art-contest' ), __( 'Максимальная защита, но меньше голосов', 'kids-art-contest' ) ); // phpcs:ignore ?>
						<?php echo self::cb( 'captcha_submit', __( 'Проверка «я не робот» в форме подачи работы', 'kids-art-contest' ) ); // phpcs:ignore ?>
						<?php echo self::cb( 'trust_proxy', __( 'Доверять заголовку X-Forwarded-For', 'kids-art-contest' ), __( 'Включайте, только если сайт стоит за прокси/CDN — иначе IP можно подделать', 'kids-art-contest' ) ); // phpcs:ignore ?>
					</div></div>
					<div class="wpf"><label><?php esc_html_e( 'Сервис капчи', 'kids-art-contest' ); ?></label><div><select class="wpi" name="<?php echo esc_attr( self::n( 'captcha_provider' ) ); ?>"><?php foreach ( KAC_Captcha::providers() as $k => $l ) : ?><option value="<?php echo esc_attr( $k ); ?>" <?php selected( $s( 'captcha_provider' ), $k ); ?>><?php echo esc_html( $l ); ?></option><?php endforeach; ?></select></div></div>
					<div class="wpf"><label><?php esc_html_e( 'Ключи капчи', 'kids-art-contest' ); ?></label><div><input class="wpi w200" name="<?php echo esc_attr( self::n( 'captcha_site_key' ) ); ?>" value="<?php echo esc_attr( (string) $s( 'captcha_site_key' ) ); ?>" placeholder="<?php esc_attr_e( 'Ключ клиента', 'kids-art-contest' ); ?>"> <input class="wpi w200" type="password" autocomplete="new-password" name="<?php echo esc_attr( self::n( 'captcha_secret' ) ); ?>" placeholder="<?php echo KAC_Captcha::secret() ? '••••••••' : esc_attr__( 'Секретный ключ', 'kids-art-contest' ); ?>"></div><div class="d"><?php esc_html_e( 'Без ключей работает встроенная проверка. Секретный ключ не показывается; чтобы заменить — введите новый.', 'kids-art-contest' ); ?></div></div>
					<div class="wpf"><label><?php esc_html_e( 'Порог подозрительности', 'kids-art-contest' ); ?></label><div><input type="range" min="10" max="100" name="<?php echo esc_attr( self::n( 'risk_threshold' ) ); ?>" value="<?php echo (int) $s( 'risk_threshold' ); ?>" style="width:200px;vertical-align:middle" oninput="this.nextElementSibling.textContent=this.value+' / 100'"> <span><?php echo (int) $s( 'risk_threshold' ); ?> / 100</span></div><div class="d"><?php esc_html_e( 'Выше порога — голос требует капчу и помечается для проверки.', 'kids-art-contest' ); ?></div></div>
					<div class="wpf"><label><?php esc_html_e( 'Порог всплеска', 'kids-art-contest' ); ?></label><div><input class="wpi w80" type="number" min="5" max="1000" name="<?php echo esc_attr( self::n( 'spike_threshold' ) ); ?>" value="<?php echo (int) $s( 'spike_threshold' ); ?>"> <?php esc_html_e( 'голосов за работу за 10 минут', 'kids-art-contest' ); ?></div></div>
					<div class="wpf"><label><?php esc_html_e( 'Уведомлять о всплесках', 'kids-art-contest' ); ?></label><div><input class="wpi w200" name="<?php echo esc_attr( self::n( 'alert_emails' ) ); ?>" value="<?php echo esc_attr( (string) $s( 'alert_emails' ) ); ?>" placeholder="<?php echo esc_attr( (string) get_option( 'admin_email' ) ); ?>"></div><div class="d"><?php esc_html_e( 'Несколько адресов — через запятую.', 'kids-art-contest' ); ?></div></div>
					<div class="wpf"><label><?php esc_html_e( 'Равенство голосов', 'kids-art-contest' ); ?></label><div><select class="wpi" name="<?php echo esc_attr( self::n( 'tie_mode' ) ); ?>"><option value="earlier_reached" <?php selected( $s( 'tie_mode' ), 'earlier_reached' ); ?>><?php esc_html_e( 'Выше та, что раньше набрала', 'kids-art-contest' ); ?></option><option value="earlier_sent" <?php selected( $s( 'tie_mode' ), 'earlier_sent' ); ?>><?php esc_html_e( 'Выше та, что раньше отправлена', 'kids-art-contest' ); ?></option><option value="share" <?php selected( $s( 'tie_mode' ), 'share' ); ?>><?php esc_html_e( 'Делят место', 'kids-art-contest' ); ?></option></select></div></div>
				</div>

				<div class="stab <?php echo 't2' === $tab ? 'on' : ''; ?>" id="t2" <?php echo 't2' === $tab ? '' : 'hidden'; ?>>
					<div class="wpf"><label><?php esc_html_e( 'Максимальный размер файла', 'kids-art-contest' ); ?></label><div><input class="wpi w80" type="number" step="0.1" min="0.1" name="<?php echo esc_attr( self::n( 'upload_max_mb' ) ); ?>" value="<?php echo esc_attr( (string) round( (int) $s( 'upload_max_bytes' ) / 1048576, 2 ) ); ?>"> <?php esc_html_e( 'МБ', 'kids-art-contest' ); ?></div></div>
					<div class="wpf"><label><?php esc_html_e( 'Мин. размер изображения', 'kids-art-contest' ); ?></label><div><input class="wpi w80" type="number" name="<?php echo esc_attr( self::n( 'upload_min_w' ) ); ?>" value="<?php echo (int) $s( 'upload_min_w' ); ?>"> × <input class="wpi w80" type="number" name="<?php echo esc_attr( self::n( 'upload_min_h' ) ); ?>" value="<?php echo (int) $s( 'upload_min_h' ); ?>"> px</div></div>
					<div class="wpf"><label><?php esc_html_e( 'Макс. размер изображения', 'kids-art-contest' ); ?></label><div><input class="wpi w80" type="number" name="<?php echo esc_attr( self::n( 'upload_max_w' ) ); ?>" value="<?php echo (int) $s( 'upload_max_w' ); ?>"> × <input class="wpi w80" type="number" name="<?php echo esc_attr( self::n( 'upload_max_h' ) ); ?>" value="<?php echo (int) $s( 'upload_max_h' ); ?>"> px</div></div>
					<div class="wpf"><label><?php esc_html_e( 'Форматы', 'kids-art-contest' ); ?></label><div><?php foreach ( array( 'jpg' => 'JPG', 'png' => 'PNG', 'webp' => 'WEBP' ) as $k => $l ) : ?><label class="ck" style="display:inline-flex;margin-right:12px"><input type="checkbox" name="<?php echo esc_attr( self::n( 'upload_formats' ) ); ?>[]" value="<?php echo esc_attr( $k ); ?>" <?php checked( in_array( $k, (array) $s( 'upload_formats' ), true ) ); ?>> <?php echo esc_html( $l ); ?></label><?php endforeach; ?></div></div>
					<div class="wpf"><label><?php esc_html_e( 'Сжимать в браузере', 'kids-art-contest' ); ?></label><div><?php echo self::cb( 'client_shrink', __( 'Если фото больше допустимого — предложить автоматически уменьшить перед отправкой', 'kids-art-contest' ) ); // phpcs:ignore ?></div></div>
					<div class="wpf"><label><?php esc_html_e( 'Обработка', 'kids-art-contest' ); ?></label><div><?php echo self::cb( 'strip_exif', __( 'Удалять EXIF (геолокация)', 'kids-art-contest' ) ); // phpcs:ignore ?><?php echo self::cb( 'check_duplicates', __( 'Искать дубликаты', 'kids-art-contest' ) ); // phpcs:ignore ?></div></div>
				</div>

				<div class="stab <?php echo 't3' === $tab ? 'on' : ''; ?>" id="t3" <?php echo 't3' === $tab ? '' : 'hidden'; ?>>
					<table class="wpt" style="max-width:640px"><thead><tr><th><?php esc_html_e( 'Поле', 'kids-art-contest' ); ?></th><th><?php esc_html_e( 'Скрыто', 'kids-art-contest' ); ?></th><th><?php esc_html_e( 'Необязательно', 'kids-art-contest' ); ?></th><th><?php esc_html_e( 'Обязательно', 'kids-art-contest' ); ?></th></tr></thead><tbody>
						<?php self::mode_row( 'x', __( 'ФИО участника', 'kids-art-contest' ), true ); ?>
						<?php self::mode_row( 'child_middle', __( 'Отчество участника', 'kids-art-contest' ) ); ?>
						<?php self::mode_row( 'x', __( 'Возраст', 'kids-art-contest' ), true ); ?>
						<?php self::mode_row( 'city', __( 'Город', 'kids-art-contest' ) ); ?>
						<?php self::mode_row( 'school', __( 'Школа / студия', 'kids-art-contest' ) ); ?>
						<?php self::mode_row( 'teacher', __( 'Педагог', 'kids-art-contest' ) ); ?>
						<?php self::mode_row( 'technique', __( 'Техника', 'kids-art-contest' ) ); ?>
						<?php self::mode_row( 'description', __( 'Описание работы', 'kids-art-contest' ) ); ?>
					</tbody></table>
					<div class="wpf" style="margin-top:14px"><label><?php esc_html_e( 'Формат публичного имени', 'kids-art-contest' ); ?></label><div><select class="wpi" name="<?php echo esc_attr( self::n( 'public_name_format' ) ); ?>"><?php foreach ( array( '{first} {last_initial}.' => 'Анна И.', '{last_initial}. {first}' => 'И. Анна', '{first}' => 'Анна' ) as $v => $l ) : ?><option value="<?php echo esc_attr( $v ); ?>" <?php selected( $s( 'public_name_format' ), $v ); ?>><?php echo esc_html( $l ); ?></option><?php endforeach; ?></select></div></div>
					<div class="wpf wpf-top"><label><?php esc_html_e( 'Варианты «Кем приходится»', 'kids-art-contest' ); ?></label><div><textarea class="wpi w100" rows="4" style="padding:6px" name="<?php echo esc_attr( self::n( 'relations' ) ); ?>"><?php echo esc_textarea( (string) $s( 'relations' ) ); ?></textarea></div><div class="d"><?php esc_html_e( 'По одному в строке.', 'kids-art-contest' ); ?></div></div>
				</div>

				<div class="stab <?php echo 't4' === $tab ? 'on' : ''; ?>" id="t4" <?php echo 't4' === $tab ? '' : 'hidden'; ?>>
					<table class="wpt"><thead><tr><th><?php esc_html_e( 'Письмо', 'kids-art-contest' ); ?></th><th><?php esc_html_e( 'Кому', 'kids-art-contest' ); ?></th><th><?php esc_html_e( 'Включено', 'kids-art-contest' ); ?></th><th></th></tr></thead><tbody>
					<?php
					$tpls = KAC_Mailer::default_templates();
					foreach ( KAC_Mailer::labels() as $k => $l ) :
						$cur = KAC_Mailer::template( $k );
						?>
						<tr><td><?php echo esc_html( $l[0] ); ?></td><td><?php echo esc_html( $l[1] ); ?></td>
						<td><?php
						if ( 'moderator' === $k ) {
							echo '<select class="wpi" name="' . esc_attr( self::n( 'moderator_notify' ) ) . '"><option value="instant"' . selected( $s( 'moderator_notify' ), 'instant', false ) . '>Сразу</option><option value="digest"' . selected( $s( 'moderator_notify' ), 'digest', false ) . '>Дайджест раз в час</option><option value="off"' . selected( $s( 'moderator_notify' ), 'off', false ) . '>Выключено</option></select>';
						} else {
							$key = 'results' === $k ? 'mail_results_all' : "mail_{$k}_enabled";
							echo '<label class="ck" style="margin:0"><input type="checkbox" name="' . esc_attr( self::n( $key ) ) . '" value="1" ' . checked( (bool) $s( $key ), true, false ) . '> ' . esc_html__( 'Включено', 'kids-art-contest' ) . '</label>';
						}
						?></td>
						<td><a href="#" class="kac-tpl-toggle" data-k="<?php echo esc_attr( $k ); ?>"><?php esc_html_e( 'Редактировать шаблон', 'kids-art-contest' ); ?></a></td></tr>
						<tr class="kac-tpl" data-k="<?php echo esc_attr( $k ); ?>" hidden><td colspan="4">
							<div class="wpf"><label><?php esc_html_e( 'Тема', 'kids-art-contest' ); ?></label><input class="wpi w100" name="<?php echo esc_attr( self::n( "mail_{$k}_subject" ) ); ?>" value="<?php echo esc_attr( (string) $s( "mail_{$k}_subject" ) ); ?>" placeholder="<?php echo esc_attr( $tpls[ $k ]['subject'] ); ?>"></div>
							<div class="wpf wpf-top"><label><?php esc_html_e( 'Текст', 'kids-art-contest' ); ?></label><textarea class="wpi w100" rows="6" style="padding:6px" name="<?php echo esc_attr( self::n( "mail_{$k}_body" ) ); ?>" placeholder="<?php echo esc_attr( $tpls[ $k ]['body'] ); ?>"><?php echo esc_textarea( (string) $s( "mail_{$k}_body" ) ); ?></textarea></div>
							<div class="d" style="color:#646970;font-size:12px"><?php esc_html_e( 'Пусто — шаблон по умолчанию. Подстановки: {contest_title} {ticket} {work_title} {work_url} {child_first_name} {age} {age_group} {parent_first_name} {reject_reason} {submit_end} {vote_end} {place} {votes} {pending_count}', 'kids-art-contest' ); ?></div>
						</td></tr>
					<?php endforeach; ?>
					</tbody></table>
					<div class="wpf wpf-top" style="margin-top:14px"><label><?php esc_html_e( 'Шаблоны причин отказа', 'kids-art-contest' ); ?></label><div><textarea class="wpi w100" rows="5" style="padding:6px" name="<?php echo esc_attr( self::n( 'reject_reasons' ) ); ?>"><?php echo esc_textarea( (string) $s( 'reject_reasons' ) ); ?></textarea></div><div class="d"><?php esc_html_e( 'По одной в строке — выбираются в окне отклонения.', 'kids-art-contest' ); ?></div></div>
				</div>

				<div class="stab <?php echo 't5' === $tab ? 'on' : ''; ?>" id="t5" <?php echo 't5' === $tab ? '' : 'hidden'; ?>>
					<div class="wpf"><label><?php esc_html_e( 'Политика конфиденциальности', 'kids-art-contest' ); ?></label><div><?php wp_dropdown_pages( array( 'name' => self::n( 'privacy_page_id' ), 'selected' => (int) $s( 'privacy_page_id' ), 'show_option_none' => __( '— страница политики WordPress —', 'kids-art-contest' ), 'option_none_value' => 0, 'class' => 'wpi' ) ); ?></div></div>
					<?php foreach ( array( 'consent_personal_text' => 'Текст согласия на обработку ПД', 'consent_publish_text' => 'Текст согласия на публикацию рисунка', 'consent_rules_text' => 'Текст согласия с правилами' ) as $k => $l ) : ?>
					<div class="wpf wpf-top"><label><?php echo esc_html( $l ); ?></label><div><textarea class="wpi w100" rows="3" style="padding:6px" name="<?php echo esc_attr( self::n( $k ) ); ?>"><?php echo esc_textarea( (string) $s( $k ) ); ?></textarea></div></div>
					<?php endforeach; ?>
					<div class="wpf"><label><?php esc_html_e( 'Версия текстов', 'kids-art-contest' ); ?></label><div><?php echo (int) $s( 'consent_version' ); ?> <span style="color:#646970"><?php esc_html_e( '· при изменении текста версия увеличивается, старые согласия сохраняются', 'kids-art-contest' ); ?></span></div></div>
					<div class="wpf"><label><?php esc_html_e( 'Удалять ПД участников', 'kids-art-contest' ); ?></label><div><?php esc_html_e( 'через', 'kids-art-contest' ); ?> <input class="wpi w80" type="number" min="0" max="3650" name="<?php echo esc_attr( self::n( 'retention_pd_days' ) ); ?>" value="<?php echo (int) $s( 'retention_pd_days' ); ?>"> <?php esc_html_e( 'дней после окончания конкурса', 'kids-art-contest' ); ?> <span style="color:#646970"><?php esc_html_e( '(работы и обезличенные имена остаются; 0 — не удалять)', 'kids-art-contest' ); ?></span></div></div>
					<div class="wpf"><label><?php esc_html_e( 'Удалять IP голосующих', 'kids-art-contest' ); ?></label><div><?php esc_html_e( 'через', 'kids-art-contest' ); ?> <input class="wpi w80" type="number" min="0" max="3650" name="<?php echo esc_attr( self::n( 'retention_ip_days' ) ); ?>" value="<?php echo (int) $s( 'retention_ip_days' ); ?>"> <?php esc_html_e( 'дней после окончания конкурса', 'kids-art-contest' ); ?></div></div>
					<div class="wpf"><label><?php esc_html_e( 'Инструменты WordPress', 'kids-art-contest' ); ?></label><div><?php esc_html_e( 'Экспорт и удаление по email подключены к «Инструменты → Экспорт / Удаление персональных данных»', 'kids-art-contest' ); ?> ✓</div></div>
					<div class="wpf"><label><?php esc_html_e( 'При удалении плагина', 'kids-art-contest' ); ?></label><div><?php echo self::cb( 'delete_data_on_uninstall', __( 'Удалять все данные', 'kids-art-contest' ), __( 'Таблицы, работы, настройки, роль и закрытые оригиналы будут удалены безвозвратно', 'kids-art-contest' ) ); // phpcs:ignore ?></div></div>
				</div>

				<div class="stab <?php echo 't6' === $tab ? 'on' : ''; ?>" id="t6" <?php echo 't6' === $tab ? '' : 'hidden'; ?>>
					<div class="wpf"><label><?php esc_html_e( 'Основной цвет', 'kids-art-contest' ); ?></label><div><input type="color" name="<?php echo esc_attr( self::n( 'accent_color' ) ); ?>" value="<?php echo esc_attr( (string) $s( 'accent_color' ) ); ?>"> <input type="color" name="<?php echo esc_attr( self::n( 'ok_color' ) ); ?>" value="<?php echo esc_attr( (string) $s( 'ok_color' ) ); ?>"> <span style="color:#646970"><?php esc_html_e( 'кнопки / «голос учтён»', 'kids-art-contest' ); ?></span></div></div>
					<div class="wpf"><label><?php esc_html_e( 'Колонок в галерее', 'kids-art-contest' ); ?></label><div><select class="wpi" name="<?php echo esc_attr( self::n( 'columns' ) ); ?>"><?php foreach ( array( 3, 4, 2 ) as $c ) : ?><option <?php selected( (int) $s( 'columns' ), $c ); ?>><?php echo (int) $c; ?></option><?php endforeach; ?></select></div></div>
					<div class="wpf"><label><?php esc_html_e( 'Работ на странице', 'kids-art-contest' ); ?></label><div><input class="wpi w80" type="number" min="4" max="60" name="<?php echo esc_attr( self::n( 'per_page' ) ); ?>" value="<?php echo (int) $s( 'per_page' ); ?>"></div></div>
					<div class="wpf"><label><?php esc_html_e( 'Шрифт', 'kids-art-contest' ); ?></label><div><select class="wpi" name="<?php echo esc_attr( self::n( 'font' ) ); ?>"><option value="theme" <?php selected( $s( 'font' ), 'theme' ); ?>><?php esc_html_e( 'Как в теме', 'kids-art-contest' ); ?></option><option value="nunito" <?php selected( $s( 'font' ), 'nunito' ); ?>><?php esc_html_e( 'Nunito (дружелюбный)', 'kids-art-contest' ); ?></option></select></div><div class="d"><?php esc_html_e( 'Nunito загружается с Google Fonts — это внешний запрос с компьютера посетителя.', 'kids-art-contest' ); ?></div></div>
					<div class="wpf"><label><?php esc_html_e( 'Текст кнопки', 'kids-art-contest' ); ?></label><div><input class="wpi w200" name="<?php echo esc_attr( self::n( 'vote_button_text' ) ); ?>" value="<?php echo esc_attr( (string) $s( 'vote_button_text' ) ); ?>"></div></div>
				</div>

				<div class="stab <?php echo 't7' === $tab ? 'on' : ''; ?>" id="t7" <?php echo 't7' === $tab ? '' : 'hidden'; ?>>
					<?php
					$role = get_role( 'kac_moderator' );
					$caps = array(
						'kac_manage_contests'    => 'Создавать, изменять, удалять конкурсы',
						'kac_moderate_works'     => 'Одобрять, отклонять, дисквалифицировать работы',
						'kac_edit_works'         => 'Править название/описание, поворачивать фото',
						'kac_view_personal_data' => 'Видеть ФИО, email, телефон',
						'kac_view_votes'         => 'Смотреть журнал голосов',
						'kac_annul_votes'        => 'Аннулировать голоса',
						'kac_publish_results'    => 'Публиковать итоги, рассылать письма',
						'kac_export'             => 'Экспорт работ и участников',
						'kac_manage_settings'    => 'Настройки плагина',
					);
					?>
					<table class="wpt"><thead><tr><th><?php esc_html_e( 'Право (capability)', 'kids-art-contest' ); ?></th><th><?php esc_html_e( 'Что даёт', 'kids-art-contest' ); ?></th><th><?php esc_html_e( 'Администратор', 'kids-art-contest' ); ?></th><th><?php esc_html_e( 'Модератор конкурса', 'kids-art-contest' ); ?></th></tr></thead><tbody>
					<?php foreach ( $caps as $cap => $desc ) : ?>
						<tr><td><code><?php echo esc_html( $cap ); ?></code></td><td><?php echo esc_html( $desc ); ?></td><td class="yes">✓</td><td><input type="checkbox" name="<?php echo esc_attr( self::n( 'mod_caps' ) ); ?>[]" value="<?php echo esc_attr( $cap ); ?>" <?php checked( $role && $role->has_cap( $cap ) ); ?> <?php disabled( 'kac_manage_settings' === $cap ); ?>></td></tr>
					<?php endforeach; ?>
					</tbody></table>
					<p style="color:#646970;margin-top:10px"><?php esc_html_e( 'Роль назначается в «Пользователи → Добавить». Все проверки в коде идут через current_user_can(), поэтому эти права можно выдать и любой другой роли.', 'kids-art-contest' ); ?></p>
				</div>

				<div style="margin-top:16px"><?php submit_button( __( 'Сохранить изменения', 'kids-art-contest' ), 'primary wpbtn-main', 'submit', false ); ?></div>
			</form>
		</div>
		<?php
	}
}
