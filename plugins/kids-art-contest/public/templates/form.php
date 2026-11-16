<?php
/**
 * Форма подачи работы (макет, экраны 3–4). Переменные: $contest_id, $standalone.
 */
defined( 'ABSPATH' ) || exit;

if ( ! KAC_Phase::can_submit( $contest_id ) ) {
	echo '<div class="kac"><p class="empty">' . esc_html__( 'Приём работ сейчас закрыт.', 'kids-art-contest' ) . '</p></div>';
	return;
}

$rules = KAC_Contest::upload_rules( $contest_id );
$min   = KAC_Contest::min_age( $contest_id );
$max   = KAC_Contest::max_age( $contest_id );
$d     = KAC_Phase::dates( $contest_id );
$mode  = static fn( string $f ) => KAC_Submission::field_mode( $f );
$req   = static fn( string $f ) => 'required' === KAC_Submission::field_mode( $f );
$star  = '<em>*</em>';

// Повторная отправка отклонённой работы.
$pre = array();
$resubmit = 0;
$rkey = '';
if ( ! empty( $_GET['kac_resubmit'] ) && ! empty( $_GET['k'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
	$rid  = absint( $_GET['kac_resubmit'] ); // phpcs:ignore
	$rkey = sanitize_text_field( wp_unslash( $_GET['k'] ) ); // phpcs:ignore
	if ( KAC_Submission::resubmit_ok( $rid, $contest_id, $rkey ) ) {
		$resubmit = $rid;
		$p        = KAC_Participants::get( $rid );
		if ( $p ) {
			$pre        = (array) $p;
			$pre['age'] = $p->child_age;
		}
	}
}
$v = static fn( string $k ) => isset( $pre[ $k ] ) ? esc_attr( (string) $pre[ $k ] ) : '';

$relations = array_filter( array_map( 'trim', explode( "\n", (string) kac_setting( 'relations' ) ) ) );
$techs     = array( 'Карандаши', 'Фломастеры', 'Акварель', 'Гуашь', 'Пастель', 'Смешанная техника', 'Другое' );
$consent_pd    = (string) kac_setting( 'consent_personal_text' );
$consent_pub   = (string) kac_setting( 'consent_publish_text' );
$consent_rules = (string) kac_setting( 'consent_rules_text' );
$privacy_url   = kac_privacy_url();
$rules_id  = (int) get_post_meta( $contest_id, '_kac_rules_file', true );
$rules_url = $rules_id ? wp_get_attachment_url( $rules_id ) : '';
$back      = get_permalink( $contest_id );
$result_date = max( $d['vote_end'], $d['submit_end'] );
$captcha_on  = (bool) kac_setting( 'captcha_submit' );
$formats = strtoupper( implode( ', ', $rules['formats'] ) );
?>
<section class="kac kac-formwrap" id="kac-form">
	<div class="wrap">
		<?php if ( empty( $standalone ) ) : ?>
		<div style="display:flex;align-items:center;gap:10px;margin-bottom:14px;flex-wrap:wrap"><a class="btn ghost sm" href="<?php echo esc_url( $back ); ?>">← <?php esc_html_e( 'К конкурсу', 'kids-art-contest' ); ?></a><span style="font-weight:900;font-size:20px"><?php echo esc_html( sprintf( __( 'Участие в конкурсе «%s»', 'kids-art-contest' ), get_the_title( $contest_id ) ) ); ?></span></div>
		<?php endif; ?>

		<div class="stepper" id="kac-stepper"><div class="on"><i>1</i><span><?php esc_html_e( 'Рисунок', 'kids-art-contest' ); ?></span></div><div><i>2</i><span><?php esc_html_e( 'Об участнике', 'kids-art-contest' ); ?></span></div><div><i>3</i><span><?php esc_html_e( 'Контакты и согласие', 'kids-art-contest' ); ?></span></div></div>

		<div class="form-layout">
			<form class="fcard kac-form" novalidate data-contest="<?php echo (int) $contest_id; ?>" data-min-w="<?php echo (int) $rules['min_w']; ?>" data-min-h="<?php echo (int) $rules['min_h']; ?>" data-max-w="<?php echo (int) $rules['max_w']; ?>" data-max-h="<?php echo (int) $rules['max_h']; ?>" data-max-bytes="<?php echo (int) $rules['max_bytes']; ?>" data-formats="<?php echo esc_attr( implode( ',', $rules['formats'] ) ); ?>" data-shrink="<?php echo kac_setting( 'client_shrink' ) ? '1' : '0'; ?>" data-captcha="<?php echo $captcha_on ? '1' : '0'; ?>" data-back="<?php echo esc_url( $back ); ?>" data-form-url="<?php echo esc_url( add_query_arg( 'kac', 'submit', $back ) ); ?>">
				<input type="hidden" name="contest_id" value="<?php echo (int) $contest_id; ?>">
				<input type="hidden" name="resubmit_of" value="<?php echo (int) $resubmit; ?>">
				<input type="hidden" name="resubmit_key" value="<?php echo esc_attr( $rkey ); ?>">
				<div class="hp" aria-hidden="true"><label>Website<input type="text" name="website" tabindex="-1" autocomplete="off"></label></div>

				<!-- шаг 1 -->
				<div class="fstep on" data-step="1">
					<h3><?php esc_html_e( 'Загрузите рисунок', 'kids-art-contest' ); ?></h3>
					<p class="hint"><?php esc_html_e( 'Фото или скан рисунка. Требования к файлу — ниже, мы проверим их сразу.', 'kids-art-contest' ); ?></p>
					<label class="drop" id="kac-drop">
						<div class="ic">🖼️</div>
						<b><?php esc_html_e( 'Перетащите файл сюда или нажмите, чтобы выбрать', 'kids-art-contest' ); ?></b>
						<span style="color:#777;font-size:13.5px"><?php esc_html_e( 'Можно сфотографировать рисунок прямо с телефона', 'kids-art-contest' ); ?></span>
						<div class="req"><span><?php echo esc_html( $formats ); ?></span><span><?php echo esc_html( sprintf( __( 'до %s', 'kids-art-contest' ), size_format( $rules['max_bytes'], 0 ) ) ); ?></span><span><?php echo esc_html( sprintf( __( 'от %1$d×%2$d px', 'kids-art-contest' ), $rules['min_w'], $rules['min_h'] ) ); ?></span><span><?php echo esc_html( sprintf( __( 'до %1$d×%2$d px', 'kids-art-contest' ), $rules['max_w'], $rules['max_h'] ) ); ?></span></div>
						<input type="file" id="kac-file" name="image" accept="image/jpeg,image/png,image/webp">
					</label>
					<div class="upres" id="kac-upres">
						<img id="kac-upimg" alt="">
						<div>
							<b id="kac-upname" style="font-size:15px"></b>
							<ul class="checks" id="kac-upchecks"></ul>
							<button type="button" class="btn ghost sm" id="kac-reupload"><?php esc_html_e( 'Выбрать другой файл', 'kids-art-contest' ); ?></button>
						</div>
					</div>
					<div class="fld" id="kac-fileerr" style="display:none"><span class="msg err"><?php esc_html_e( 'Загрузите рисунок, который подходит по требованиям', 'kids-art-contest' ); ?></span></div>
					<div class="tips">💡 <b><?php esc_html_e( 'Как сделать хорошее фото:', 'kids-art-contest' ); ?></b> <?php esc_html_e( 'положите рисунок на ровную поверхность у окна, снимайте сверху без вспышки, чтобы рисунок попал в кадр целиком.', 'kids-art-contest' ); ?></div>
					<div class="fld"><label><?php esc_html_e( 'Название работы', 'kids-art-contest' ); ?> <?php echo $star; // phpcs:ignore ?></label><input class="inp" name="title" maxlength="60" placeholder="<?php esc_attr_e( 'Например: «Снеговик и его друзья»', 'kids-art-contest' ); ?>" data-req data-counter="60"><div class="counter">0 / 60</div></div>
					<?php if ( 'hidden' !== $mode( 'description' ) ) : ?>
					<div class="fld"><label><?php esc_html_e( 'Пара слов о рисунке', 'kids-art-contest' ); ?> <?php echo $req( 'description' ) ? $star : '<small>— ' . esc_html__( 'необязательно', 'kids-art-contest' ) . '</small>'; // phpcs:ignore ?></label><textarea class="inp" name="description" rows="3" maxlength="500" placeholder="<?php esc_attr_e( 'Что изображено, почему выбрана эта тема…', 'kids-art-contest' ); ?>" data-counter="500" <?php echo $req( 'description' ) ? 'data-req' : ''; ?>></textarea><div class="counter">0 / 500</div></div>
					<?php endif; ?>
					<?php if ( 'hidden' !== $mode( 'technique' ) ) : ?>
					<div class="fld"><label><?php esc_html_e( 'Техника', 'kids-art-contest' ); ?> <?php echo $req( 'technique' ) ? $star : '<small>— ' . esc_html__( 'необязательно', 'kids-art-contest' ) . '</small>'; // phpcs:ignore ?></label>
						<select class="inp" name="technique" <?php echo $req( 'technique' ) ? 'data-req' : ''; ?>><option value=""><?php esc_html_e( 'Выберите…', 'kids-art-contest' ); ?></option><?php foreach ( $techs as $t ) : ?><option><?php echo esc_html( $t ); ?></option><?php endforeach; ?></select></div>
					<?php endif; ?>
				</div>

				<!-- шаг 2 -->
				<div class="fstep" data-step="2">
					<h3><?php esc_html_e( 'Об участнике', 'kids-art-contest' ); ?></h3>
					<p class="hint"><?php esc_html_e( 'Данные ребёнка — автора рисунка. В галерее будет показано только имя и первая буква фамилии:', 'kids-art-contest' ); ?> <b id="kac-nameprev">Анна И.</b></p>
					<div class="row r3">
						<div class="fld"><label><?php esc_html_e( 'Фамилия', 'kids-art-contest' ); ?> <?php echo $star; // phpcs:ignore ?></label><input class="inp" name="child_last" placeholder="Иванова" value="<?php echo $v( 'child_last' ); // phpcs:ignore ?>" autocomplete="off" data-req></div>
						<div class="fld"><label><?php esc_html_e( 'Имя', 'kids-art-contest' ); ?> <?php echo $star; // phpcs:ignore ?></label><input class="inp" name="child_first" placeholder="Анна" value="<?php echo $v( 'child_first' ); // phpcs:ignore ?>" autocomplete="off" data-req></div>
						<?php if ( 'hidden' !== $mode( 'child_middle' ) ) : ?><div class="fld"><label><?php esc_html_e( 'Отчество', 'kids-art-contest' ); ?><?php echo $req( 'child_middle' ) ? ' ' . $star : ''; // phpcs:ignore ?></label><input class="inp" name="child_middle" placeholder="Сергеевна" value="<?php echo $v( 'child_middle' ); // phpcs:ignore ?>" autocomplete="off" <?php echo $req( 'child_middle' ) ? 'data-req' : ''; ?>></div><?php else : ?><div></div><?php endif; ?>
					</div>
					<div class="row">
						<div class="fld"><label><?php esc_html_e( 'Возраст', 'kids-art-contest' ); ?> <?php echo $star; // phpcs:ignore ?></label>
							<select class="inp" name="age" id="kac-age" data-req data-groups="<?php echo esc_attr( wp_json_encode( KAC_Contest::groups( $contest_id ) ) ); ?>"><option value=""><?php esc_html_e( 'Выберите возраст', 'kids-art-contest' ); ?></option><?php for ( $a = $min; $a <= $max; $a++ ) : ?><option value="<?php echo (int) $a; ?>" <?php selected( (int) ( $pre['age'] ?? 0 ), $a ); ?>><?php echo esc_html( Kac_Format::age( $a ) ); ?></option><?php endfor; ?></select></div>
						<?php if ( 'hidden' !== $mode( 'city' ) ) : ?><div class="fld"><label><?php esc_html_e( 'Город / населённый пункт', 'kids-art-contest' ); ?><?php echo $req( 'city' ) ? ' ' . $star : ''; // phpcs:ignore ?></label><input class="inp" name="city" placeholder="<?php esc_attr_e( 'Например: Казань', 'kids-art-contest' ); ?>" value="<?php echo $v( 'city' ); // phpcs:ignore ?>" <?php echo $req( 'city' ) ? 'data-req' : ''; ?>></div><?php else : ?><div></div><?php endif; ?>
					</div>
					<div class="agegroup" id="kac-agegroup">✓ <?php esc_html_e( 'Работа будет участвовать в группе', 'kids-art-contest' ); ?> <span id="kac-agegroupname"></span></div>
					<?php if ( 'hidden' !== $mode( 'school' ) || 'hidden' !== $mode( 'teacher' ) ) : ?>
					<div class="row">
						<?php if ( 'hidden' !== $mode( 'school' ) ) : ?><div class="fld"><label><?php esc_html_e( 'Школа, студия или кружок', 'kids-art-contest' ); ?> <?php echo $req( 'school' ) ? $star : '<small>— ' . esc_html__( 'необязательно', 'kids-art-contest' ) . '</small>'; // phpcs:ignore ?></label><input class="inp" name="school" value="<?php echo $v( 'school' ); // phpcs:ignore ?>" <?php echo $req( 'school' ) ? 'data-req' : ''; ?>></div><?php else : ?><div></div><?php endif; ?>
						<?php if ( 'hidden' !== $mode( 'teacher' ) ) : ?><div class="fld"><label><?php esc_html_e( 'Педагог', 'kids-art-contest' ); ?> <?php echo $req( 'teacher' ) ? $star : '<small>— ' . esc_html__( 'необязательно', 'kids-art-contest' ) . '</small>'; // phpcs:ignore ?></label><input class="inp" name="teacher" placeholder="<?php esc_attr_e( 'ФИО педагога', 'kids-art-contest' ); ?>" value="<?php echo $v( 'teacher' ); // phpcs:ignore ?>" <?php echo $req( 'teacher' ) ? 'data-req' : ''; ?>></div><?php else : ?><div></div><?php endif; ?>
					</div>
					<?php endif; ?>
				</div>

				<!-- шаг 3 -->
				<div class="fstep" data-step="3">
					<h3><?php esc_html_e( 'Законный представитель', 'kids-art-contest' ); ?></h3>
					<p class="hint"><?php esc_html_e( 'Родитель или опекун. Контакты увидит только организатор — для связи и отправки результатов.', 'kids-art-contest' ); ?></p>
					<div class="row r3">
						<div class="fld"><label><?php esc_html_e( 'Фамилия', 'kids-art-contest' ); ?> <?php echo $star; // phpcs:ignore ?></label><input class="inp" name="parent_last" value="<?php echo $v( 'parent_last' ); // phpcs:ignore ?>" data-req></div>
						<div class="fld"><label><?php esc_html_e( 'Имя', 'kids-art-contest' ); ?> <?php echo $star; // phpcs:ignore ?></label><input class="inp" name="parent_first" value="<?php echo $v( 'parent_first' ); // phpcs:ignore ?>" data-req></div>
						<div class="fld"><label><?php esc_html_e( 'Отчество', 'kids-art-contest' ); ?></label><input class="inp" name="parent_middle" value="<?php echo $v( 'parent_middle' ); // phpcs:ignore ?>"></div>
					</div>
					<div class="row">
						<div class="fld"><label><?php esc_html_e( 'Кем приходится', 'kids-art-contest' ); ?> <?php echo $star; // phpcs:ignore ?></label><select class="inp" name="parent_relation" data-req><option value=""><?php esc_html_e( 'Выберите…', 'kids-art-contest' ); ?></option><?php foreach ( $relations as $rel ) : ?><option <?php selected( $pre['parent_relation'] ?? '', $rel ); ?>><?php echo esc_html( $rel ); ?></option><?php endforeach; ?></select></div>
						<div></div>
					</div>
					<div class="row">
						<div class="fld"><label>Email <?php echo $star; // phpcs:ignore ?></label><input class="inp" name="email" id="kac-email" type="email" inputmode="email" autocomplete="email" placeholder="name@example.ru" value="<?php echo $v( 'email' ); // phpcs:ignore ?>" data-req><span class="msg" id="kac-emailmsg"><?php esc_html_e( 'На него придёт подтверждение и итоги', 'kids-art-contest' ); ?></span></div>
						<div class="fld"><label><?php esc_html_e( 'Телефон', 'kids-art-contest' ); ?> <?php echo $star; // phpcs:ignore ?></label><input class="inp" name="phone" id="kac-phone" type="tel" inputmode="tel" autocomplete="tel" placeholder="+7 (___) ___-__-__" data-req><span class="msg" id="kac-phonemsg"><?php esc_html_e( 'Только для связи с организатором', 'kids-art-contest' ); ?></span></div>
					</div>
					<label class="check"><input type="checkbox" name="consent_personal" value="1" data-req><span><?php echo esc_html( $consent_pd ); ?><?php if ( $privacy_url ) : ?> <a href="<?php echo esc_url( $privacy_url ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'Политика конфиденциальности', 'kids-art-contest' ); ?></a><?php endif; ?></span></label>
					<label class="check"><input type="checkbox" name="consent_publish" value="1" data-req><span><?php echo esc_html( $consent_pub ); ?></span></label>
					<label class="check"><input type="checkbox" name="consent_rules" value="1" data-req><span><?php echo esc_html( $consent_rules ); ?><?php if ( $rules_url ) : ?> <a href="<?php echo esc_url( $rules_url ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'Правила конкурса', 'kids-art-contest' ); ?></a><?php endif; ?></span></label>
					<label class="check"><input type="checkbox" name="newsletter" value="1"><span><?php esc_html_e( 'Хочу получать новости о следующих конкурсах', 'kids-art-contest' ); ?> <small style="color:#999">— <?php esc_html_e( 'необязательно', 'kids-art-contest' ); ?></small></span></label>
					<?php if ( $captcha_on ) : ?><div class="captcha" id="kac-captcha" hidden></div><?php endif; ?>
					<div class="formerr" id="kac-formerr" role="alert"></div>
				</div>

				<div class="fnav">
					<button type="button" class="btn ghost" id="kac-prev" style="visibility:hidden">← <?php esc_html_e( 'Назад', 'kids-art-contest' ); ?></button>
					<span style="color:#999;font-size:13px" id="kac-stepinfo"><?php esc_html_e( 'Шаг 1 из 3', 'kids-art-contest' ); ?></span>
					<button type="button" class="btn primary" id="kac-next"><?php esc_html_e( 'Далее →', 'kids-art-contest' ); ?></button>
				</div>
			</form>

			<aside>
				<div class="aside-box pcard"><h4><?php esc_html_e( 'Так работа будет выглядеть в галерее', 'kids-art-contest' ); ?></h4>
					<article class="wcard" id="kac-preview">
						<div class="img"><img id="kac-previmg" alt="" hidden><span class="num">№—</span></div>
						<div class="body"><h3 id="kac-prevtitle"><?php esc_html_e( 'Название работы', 'kids-art-contest' ); ?></h3><div class="who" id="kac-prevwho"></div><div class="foot"><span class="votes">❤ 0</span><button type="button" class="vbtn" tabindex="-1"><?php echo esc_html( (string) kac_setting( 'vote_button_text', 'Проголосовать за работу' ) ); ?></button></div></div>
					</article>
				</div>
				<div class="aside-box"><h4><?php esc_html_e( 'Важно знать', 'kids-art-contest' ); ?></h4><ul>
					<li><?php echo esc_html( sprintf( __( 'Работ от одного участника: %d', 'kids-art-contest' ), KAC_Contest::max_works( $contest_id ) ) ); ?></li>
					<li><?php esc_html_e( 'Модерация — до 2 рабочих дней', 'kids-art-contest' ); ?></li>
					<li><?php esc_html_e( 'Голосовать можно сразу после одобрения', 'kids-art-contest' ); ?></li>
					<?php if ( $result_date ) : ?><li><?php echo esc_html( sprintf( __( 'Итоги — %s', 'kids-art-contest' ), kac_date( $result_date, 'j F' ) ) ); ?></li><?php endif; ?>
				</ul></div>
			</aside>
		</div>

		<div class="success" id="kac-success" hidden>
			<div class="big">🎉</div>
			<h1><?php esc_html_e( 'Спасибо! Рисунок отправлен', 'kids-art-contest' ); ?></h1>
			<p><?php esc_html_e( 'Работа', 'kids-art-contest' ); ?> «<span id="kac-sworktitle"></span>» <?php esc_html_e( 'отправлена на проверку модератору.', 'kids-art-contest' ); ?><br><?php esc_html_e( 'Подтверждение мы отправили на', 'kids-art-contest' ); ?> <b id="kac-semail"></b></p>
			<div class="ticket"><?php esc_html_e( 'Номер заявки:', 'kids-art-contest' ); ?> <span id="kac-sticket"></span></div>
			<div class="timeline">
				<div><span class="st">✅</span><b><?php esc_html_e( 'Отправлено', 'kids-art-contest' ); ?></b><span id="kac-sdate"></span></div>
				<div class="cur"><span class="st">🔍</span><b><?php esc_html_e( 'Проверка модератором', 'kids-art-contest' ); ?></b><?php esc_html_e( 'Обычно до 2 рабочих дней', 'kids-art-contest' ); ?></div>
				<div><span class="st">🗳</span><b><?php esc_html_e( 'Публикация и голосование', 'kids-art-contest' ); ?></b><?php esc_html_e( 'Пришлём письмо со ссылкой на работу', 'kids-art-contest' ); ?></div>
			</div>
			<div style="display:flex;gap:10px;justify-content:center;flex-wrap:wrap"><a class="btn primary" href="<?php echo esc_url( $back ); ?>"><?php esc_html_e( 'Вернуться к конкурсу', 'kids-art-contest' ); ?></a><a class="btn ghost" id="kac-another" href="<?php echo esc_url( add_query_arg( 'kac', 'submit', $back ) ); ?>"><?php esc_html_e( 'Отправить работу другого ребёнка', 'kids-art-contest' ); ?></a></div>
		</div>
	</div>
</section>
