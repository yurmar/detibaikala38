/* Капча: встроенная, Turnstile, SmartCaptcha, reCAPTCHA v3. Возвращает промис с параметрами для запроса. */
(function () {
	'use strict';
	var I = (window.KAC && KAC.i18n) || {};
	var loaded = {};

	function loadScript(src) {
		if (loaded[src]) return loaded[src];
		loaded[src] = new Promise(function (res, rej) {
			var s = document.createElement('script'); s.src = src; s.async = true; s.onload = res; s.onerror = rej; document.head.appendChild(s);
		});
		return loaded[src];
	}
	function modal(inner) {
		var ov = document.createElement('div');
		ov.style.cssText = 'position:fixed;inset:0;background:rgba(18,20,28,.6);z-index:100001;display:grid;place-items:center;padding:16px';
		ov.innerHTML = '<div class="kac"><div style="background:#fff;border-radius:18px;padding:20px;max-width:340px;width:100%;color:#1f2430;font-size:14.5px"></div></div>';
		ov.firstChild.firstChild.appendChild(inner);
		document.body.appendChild(ov);
		return ov;
	}

	/* Встроенная: вопрос-пример. */
	function builtin(c) {
		return new Promise(function (resolve, reject) {
			var f = document.createElement('form');
			f.innerHTML = '<p style="margin:0 0 10px;font-weight:800"></p><input class="inp" inputmode="numeric" autocomplete="off"><div style="display:flex;gap:8px;justify-content:flex-end;margin-top:12px"><button type="button" class="btn ghost sm">×</button><button class="btn primary sm"></button></div>';
			f.querySelector('p').textContent = I.captcha + ' ' + c.question + ' ?';
			f.querySelectorAll('button')[1].textContent = I.send;
			var ov = modal(f); var inp = f.querySelector('input'); inp.focus();
			f.querySelector('button').onclick = function () { ov.remove(); reject(new Error('cancel')); };
			f.onsubmit = function (e) { e.preventDefault(); var a = inp.value; ov.remove(); resolve({ captcha_token: c.token, captcha_answer: a }); };
		});
	}

	function widget(c, cfg) {
		return new Promise(function (resolve, reject) {
			var box = document.createElement('div');
			var close = document.createElement('button'); close.type = 'button'; close.className = 'btn ghost sm'; close.textContent = '×'; close.style.marginTop = '10px';
			var wrap = document.createElement('div'); wrap.appendChild(box); wrap.appendChild(close);
			var ov = modal(wrap);
			close.onclick = function () { ov.remove(); reject(new Error('cancel')); };
			var done = function (t) { ov.remove(); resolve({ captcha_response: t }); };
			if (cfg.provider === 'turnstile') {
				loadScript('https://challenges.cloudflare.com/turnstile/v0/api.js?render=explicit').then(function () {
					window.turnstile.render(box, { sitekey: cfg.siteKey, callback: done });
				}).catch(reject);
			} else {
				loadScript('https://smartcaptcha.yandexcloud.net/captcha.js').then(function () {
					window.smartCaptcha.render(box, { sitekey: cfg.siteKey, callback: done });
				}).catch(reject);
			}
		});
	}

	/**
	 * get(): подготовить параметры капчи. Для reCAPTCHA v3 — без окна.
	 */
	function get() {
		return window.KACApi.api('/captcha').then(function (c) {
			if (c.provider === 'builtin' || !c.provider) return builtin(c);
			if (c.provider === 'recaptcha3') {
				return loadScript('https://www.google.com/recaptcha/api.js?render=' + encodeURIComponent(c.siteKey)).then(function () {
					return new Promise(function (res) { grecaptcha.ready(function () { grecaptcha.execute(c.siteKey, { action: 'kac' }).then(function (t) { res({ captcha_response: t }); }); }); });
				});
			}
			return widget(c, c);
		});
	}
	window.KACCaptcha = { get: get };
})();
