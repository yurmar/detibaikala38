/* Форма подачи работы (логика из макета, экран 3): проверка файла, превью, маски, шаги, экран «Спасибо». */
(function () {
	'use strict';
	var I = (window.KAC && KAC.i18n) || {};
	var FIX = { 'gmail.con': 'gmail.com', 'gmail.co': 'gmail.com', 'gmial.com': 'gmail.com', 'mail.r': 'mail.ru', 'mail.ry': 'mail.ru', 'yandex.r': 'yandex.ru', 'yandex.ry': 'yandex.ru', 'yandex.com.ru': 'yandex.ru', 'bk.r': 'bk.ru', 'inbox.r': 'inbox.ru' };
	var EMAIL_RE = /^[^@\s]+@[^@\s]+\.[a-zа-я]{2,}$/i;

	function init(form) {
		var $ = function (id) { return document.getElementById(id); };
		var wrap = form.closest('.kac-formwrap') || document;
		var steps = Array.prototype.slice.call(wrap.querySelectorAll('#kac-stepper > div'));
		var step = 1, file = null, fileURL = null, fileOK = false;
		var rules = { max: +form.dataset.maxBytes, minW: +form.dataset.minW, minH: +form.dataset.minH, maxW: +form.dataset.maxW, maxH: +form.dataset.maxH, types: form.dataset.formats.split(',').map(function (x) { return x === 'jpg' ? 'image/jpeg' : 'image/' + x; }) };
		var key = 'kac_draft_' + form.dataset.contest;

		/* Черновик (без файла). */
		var skip = { image: 1, website: 1, captcha_answer: 1, captcha_token: 1, resubmit_of: 1, resubmit_key: 1, contest_id: 1 };
		try {
			var d = JSON.parse(localStorage.getItem(key) || '{}');
			Object.keys(d).forEach(function (n) { var el = form.elements[n]; if (!el || skip[n] || el.value) return; if (el.type === 'checkbox') el.checked = !!d[n]; else el.value = d[n]; });
		} catch (e) {}
		form.addEventListener('input', function (e) {
			try { var o = {}; Array.prototype.forEach.call(form.elements, function (el) { if (!el.name || skip[el.name] || el.type === 'file') return; o[el.name] = el.type === 'checkbox' ? el.checked : el.value; }); localStorage.setItem(key, JSON.stringify(o)); } catch (x) {}
			if (e.target.classList.contains('err') && e.target.value) e.target.classList.remove('err');
		});
		form.addEventListener('change', function (e) { if (e.target.type === 'checkbox') { var c = e.target.closest('.check'); if (c) c.classList.remove('err'); } });

		/* Счётчики символов и превью карточки. */
		form.querySelectorAll('[data-counter]').forEach(function (i) {
			var c = i.parentElement.querySelector('.counter');
			i.addEventListener('input', function () { c.textContent = i.value.length + ' / ' + i.dataset.counter; preview(); });
		});
		function pubName() {
			var f = (form.elements.child_first.value || 'Анна').trim(), l = (form.elements.child_last.value || 'Иванова').trim();
			return f + ' ' + l.charAt(0).toUpperCase() + '.';
		}
		function yrs(n) { var a = n % 10, b = n % 100; return (a === 1 && b !== 11) ? 'год' : (a >= 2 && a <= 4 && (b < 12 || b > 14)) ? 'года' : 'лет'; }
		var ageSel = $('kac-age'), groups = [];
		try { groups = JSON.parse(ageSel.dataset.groups || '[]'); } catch (e) {}
		function groupFor(a) { return groups.filter(function (g) { return a >= +g.from && a <= +g.to; })[0]; }
		function preview() {
			var age = +ageSel.value || 7, g = groupFor(age);
			$('kac-nameprev').textContent = pubName();
			$('kac-prevtitle').textContent = form.elements.title.value || I.photo;
			$('kac-prevwho').textContent = pubName() + ', ' + age + ' ' + yrs(age) + (g ? ' · ' + g.label : '');
			var im = $('kac-previmg'); if (fileURL) { im.src = fileURL; im.hidden = false; }
		}
		ageSel.addEventListener('change', function () {
			var g = groupFor(+ageSel.value); $('kac-agegroup').classList.toggle('on', !!g);
			if (g) $('kac-agegroupname').textContent = '«' + g.label + '»';
			preview();
		});
		['child_first', 'child_last'].forEach(function (n) { form.elements[n].addEventListener('input', preview); });
		ageSel.dispatchEvent(new Event('change'));

		/* Файл. */
		var drop = $('kac-drop'), fi = $('kac-file'), upres = $('kac-upres');
		['dragenter', 'dragover'].forEach(function (ev) { drop.addEventListener(ev, function (e) { e.preventDefault(); drop.classList.add('hover'); }); });
		['dragleave', 'drop'].forEach(function (ev) { drop.addEventListener(ev, function (e) { e.preventDefault(); drop.classList.remove('hover'); }); });
		drop.addEventListener('drop', function (e) { if (e.dataTransfer.files[0]) check(e.dataTransfer.files[0]); });
		fi.addEventListener('change', function () { if (fi.files[0]) check(fi.files[0]); });
		$('kac-reupload').addEventListener('click', function () { fi.click(); });
		function mb(b) { return (b / 1048576).toFixed(2).replace('.', ',') + ' МБ'; }
		function li(ok, t) { return '<li class="' + (ok ? 'ok' : 'bad') + '">' + t + '</li>'; }
		function show(name, url, size, type, w, h, f) {
			var okType = rules.types.indexOf(type) !== -1, okSize = size <= rules.max, okMin = w >= rules.minW && h >= rules.minH, okMax = w <= rules.maxW && h <= rules.maxH;
			var html = li(okType, okType ? 'Формат подходит' : 'Формат не подходит — нужен ' + form.dataset.formats.toUpperCase().replace(/,/g, ', ')) +
				li(okSize, okSize ? 'Размер файла ' + mb(size) + ' (до ' + mb(rules.max) + ')' : 'Файл весит ' + mb(size) + ' — нужно не больше ' + mb(rules.max)) +
				li(okMin, okMin ? 'Изображение ' + w + '×' + h + ' px — не меньше ' + rules.minW + '×' + rules.minH : 'Изображение ' + w + '×' + h + ' px — слишком маленькое, нужно от ' + rules.minW + '×' + rules.minH) +
				li(okMax, okMax ? 'Не больше ' + rules.maxW + '×' + rules.maxH + ' px' : 'Изображение ' + w + '×' + h + ' px — слишком большое, нужно до ' + rules.maxW + '×' + rules.maxH);
			var canShrink = form.dataset.shrink === '1' && okType && okMin && (!okSize || !okMax);
			if (canShrink) html += '<li style="color:#1d5fa8;font-weight:700">↘ Уменьшить автоматически? <u style="cursor:pointer" id="kac-shrink">Да, уменьшить</u></li>';
			$('kac-upchecks').innerHTML = html;
			fileOK = okType && okSize && okMin && okMax;
			file = fileOK ? f : null; fileURL = fileOK ? url : null;
			$('kac-upname').textContent = name; $('kac-upimg').src = url;
			upres.classList.add('on'); drop.style.display = 'none';
			upres.style.borderColor = fileOK ? '#7fd8bd' : '#f3b4b6';
			$('kac-fileerr').style.display = 'none';
			var s = $('kac-shrink'); if (s) s.onclick = function () { shrink(url, w, h, f); };
			preview();
		}
		function check(f) {
			var url = URL.createObjectURL(f), im = new Image();
			im.onload = function () { show(f.name, url, f.size, f.type, im.naturalWidth, im.naturalHeight, f); };
			im.onerror = function () { show(f.name, url, f.size, f.type, 0, 0, f); };
			im.src = url;
		}
		function shrink(url, w, h, f) {
			var im = new Image();
			im.onload = function () {
				var k = Math.min(1, rules.maxW / w, rules.maxH / h), nw = Math.round(w * k), nh = Math.round(h * k);
				var c = document.createElement('canvas'); c.width = nw; c.height = nh; c.getContext('2d').drawImage(im, 0, 0, nw, nh);
				var q = 0.85;
				(function attempt() {
					c.toBlob(function (blob) {
						if (!blob) return;
						if (blob.size > rules.max && q > 0.4) { q -= 0.1; attempt(); return; }
						var nf = new File([blob], (f.name.replace(/\.[^.]+$/, '') || 'drawing') + '.jpg', { type: 'image/jpeg' });
						show(nf.name + ' (уменьшено)', URL.createObjectURL(nf), nf.size, 'image/jpeg', nw, nh, nf);
					}, 'image/jpeg', q);
				})();
			};
			im.src = url;
		}

		/* Email и телефон. */
		var email = $('kac-email'), emsg = $('kac-emailmsg'), emsg0 = emsg.textContent;
		email.addEventListener('input', function () { email.value = email.value.replace(/\s/g, '').toLowerCase(); email.classList.remove('err', 'okk'); emsg.className = 'msg'; emsg.textContent = emsg0; });
		email.addEventListener('blur', function () {
			var v = email.value; if (!v) return;
			var ok = EMAIL_RE.test(v), dom = v.split('@')[1];
			if (dom && FIX[dom]) { emsg.className = 'msg sug'; emsg.textContent = 'Возможно, вы имели в виду ' + v.split('@')[0] + '@' + FIX[dom] + '?'; emsg.onclick = function () { email.value = v.split('@')[0] + '@' + FIX[dom]; email.dispatchEvent(new Event('blur')); }; return; }
			emsg.onclick = null; email.classList.add(ok ? 'okk' : 'err'); emsg.className = ok ? 'msg' : 'msg err'; emsg.textContent = ok ? '✓ Отлично' : 'Проверьте email: должен быть вида name@example.ru';
		});
		var phone = $('kac-phone'), pmsg = $('kac-phonemsg'), pmsg0 = pmsg.textContent;
		function mask(v) {
			var d = v.replace(/\D/g, ''); if (!d) return ''; if (d[0] === '8') d = '7' + d.slice(1); if (d[0] !== '7') d = '7' + d; d = d.slice(0, 11);
			var a = d.slice(1, 4), b = d.slice(4, 7), c = d.slice(7, 9), e = d.slice(9, 11), o = '+7';
			if (a) o += ' (' + a; if (b) o += ') ' + b; if (c) o += '-' + c; if (e) o += '-' + e; return o;
		}
		phone.addEventListener('focus', function () { if (!phone.value) phone.value = '+7 ('; });
		phone.addEventListener('input', function () { phone.value = mask(phone.value); phone.classList.remove('err', 'okk'); pmsg.className = 'msg'; pmsg.textContent = pmsg0; });
		phone.addEventListener('blur', function () {
			var d = phone.value.replace(/\D/g, ''); if (d.length <= 1) { phone.value = ''; return; }
			var ok = d.length === 11; phone.classList.add(ok ? 'okk' : 'err'); pmsg.className = ok ? 'msg' : 'msg err'; pmsg.textContent = ok ? '✓ Отлично' : 'Введите номер полностью: 10 цифр после +7';
		});

		/* Шаги. */
		function setStep(n) {
			step = n;
			form.querySelectorAll('.fstep').forEach(function (s) { s.classList.toggle('on', +s.dataset.step === n); });
			steps.forEach(function (s, i) { s.className = i + 1 < n ? 'ok' : i + 1 === n ? 'on' : ''; s.querySelector('i').textContent = i + 1 < n ? '✓' : i + 1; });
			$('kac-prev').style.visibility = n > 1 ? 'visible' : 'hidden';
			$('kac-next').textContent = n < 3 ? I.next : I.submit;
			$('kac-stepinfo').textContent = I.step.replace('%1$d', n).replace('%2$d', 3);
			var top = wrap.getBoundingClientRect().top + window.scrollY - 80; window.scrollTo({ top: top, behavior: 'smooth' });
			if (n === 3 && form.dataset.captcha === '1') showCaptcha();
		}
		function validStep(n) {
			var ok = true, cur = form.querySelector('.fstep[data-step="' + n + '"]');
			cur.querySelectorAll('[data-req]').forEach(function (i) {
				var bad = i.type === 'checkbox' ? !i.checked : !i.value.trim();
				if (i === phone) bad = i.value.replace(/\D/g, '').length !== 11;
				if (i === email) bad = !EMAIL_RE.test(i.value);
				if (i.type === 'checkbox') i.closest('.check').classList.toggle('err', bad); else i.classList.toggle('err', bad);
				if (bad) ok = false;
			});
			if (n === 1 && !fileOK) { $('kac-fileerr').style.display = 'block'; ok = false; }
			if (!ok) KACApi.toast(I.required, 'warn');
			return ok;
		}
		$('kac-next').addEventListener('click', function () {
			if (!validStep(step)) return;
			if (step < 3) { setStep(step + 1); return; }
			send();
		});
		$('kac-prev').addEventListener('click', function () { setStep(step - 1); });

		/* Капча формы (если включена). */
		var capParams = null;
		function showCaptcha() {
			var box = $('kac-captcha'); if (!box) return;
			box.hidden = false; box.innerHTML = '<b>Проверка</b> ';
			var b = document.createElement('button'); b.type = 'button'; b.className = 'btn ghost sm'; b.textContent = capParams ? '✓' : 'Я не робот';
			b.onclick = function () { KACCaptcha.get().then(function (p) { capParams = p; b.textContent = '✓ Готово'; }).catch(function () {}); };
			box.appendChild(b);
		}

		/* Отправка. */
		function send() {
			var btn = $('kac-next'), err = $('kac-formerr'); err.textContent = '';
			if (form.dataset.captcha === '1' && !capParams) { err.textContent = 'Подтвердите, что вы не робот'; return; }
			btn.disabled = true; btn.textContent = I.sending;
			KACApi.nonce().then(function (nonce) {
				var fd = new FormData(form);
				fd.delete('image'); fd.append('image', file, file.name); fd.append('kac_nonce', nonce);
				if (capParams) Object.keys(capParams).forEach(function (k) { fd.append(k, capParams[k]); });
				return fetch(KAC.rest + '/works', { method: 'POST', body: fd, cache: 'no-store' });
			}).then(function (r) { return r.json().then(function (d) { return { ok: r.ok, d: d }; }); }).then(function (res) {
				if (res.ok) {
					try { localStorage.removeItem(key); } catch (e) {}
					$('kac-sworktitle').textContent = res.d.title || form.elements.title.value; $('kac-semail').textContent = res.d.email || '';
					$('kac-sticket').textContent = res.d.ticket || ''; $('kac-sdate').textContent = res.d.date || '';
					wrap.querySelector('.form-layout').hidden = true; $('kac-stepper').hidden = true; $('kac-success').hidden = false;
					window.scrollTo({ top: wrap.getBoundingClientRect().top + window.scrollY - 80, behavior: 'smooth' });
					return;
				}
				var fields = (res.d.data && res.d.data.fields) || {}, first = 0;
				Object.keys(fields).forEach(function (k) {
					var el = form.elements[k]; if (!el) return;
					if (el.type === 'checkbox') el.closest('.check').classList.add('err'); else el.classList.add('err');
					var st = el.closest('.fstep'); if (st && (!first || +st.dataset.step < first)) first = +st.dataset.step;
				});
				if (fields.image) { $('kac-fileerr').style.display = 'block'; $('kac-fileerr').firstChild.textContent = fields.image; first = 1; }
				err.textContent = (Object.keys(fields).map(function (k) { return fields[k]; })[0]) || res.d.message || I.error;
				if (first) setStep(first);
				btn.disabled = false; btn.textContent = step < 3 ? I.next : I.submit; capParams = null; if (form.dataset.captcha === '1') showCaptcha();
			}).catch(function () { err.textContent = I.error; btn.disabled = false; btn.textContent = I.submit; });
		}
		if (window.KACMask) { /* маска телефона реализована выше (как в макете) */ }
	}
	document.addEventListener('DOMContentLoaded', function () { document.querySelectorAll('.kac-form').forEach(init); });
})();
