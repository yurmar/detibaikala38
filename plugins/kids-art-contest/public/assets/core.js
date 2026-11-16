/* Общие утилиты: REST, токен голосующего, уведомления, таймер, «поделиться». */
(function () {
	'use strict';
	var KACX = window.KAC || {};
	var I = KACX.i18n || {};
	var nonce = null, nonceP = null;

	function uuid() {
		if (window.crypto && crypto.randomUUID) return crypto.randomUUID();
		return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function (c) {
			var r = Math.random() * 16 | 0; return (c === 'x' ? r : (r & 3 | 8)).toString(16);
		});
	}
	function getCookie(n) {
		var m = document.cookie.match(new RegExp('(?:^|; )' + n + '=([^;]*)'));
		return m ? decodeURIComponent(m[1]) : '';
	}
	function voterId() {
		var id = '';
		try { id = localStorage.getItem('kac_vid') || ''; } catch (e) {}
		if (!id) id = getCookie('kac_vid');
		if (!/^[a-f0-9-]{36}$/i.test(id)) id = uuid();
		try { localStorage.setItem('kac_vid', id); } catch (e) {}
		document.cookie = 'kac_vid=' + id + ';path=/;max-age=' + (86400 * 400) + ';SameSite=Lax';
		return id;
	}
	function getNonce() {
		if (nonce) return Promise.resolve(nonce);
		if (!nonceP) nonceP = fetch(url('/token'), { cache: 'no-store' }).then(function (r) { return r.json(); }).then(function (d) { nonce = d.nonce; return nonce; });
		return nonceP;
	}
	// При «простых» постоянных ссылках rest уже содержит ?rest_route=, query добавляем через &.
	function url(path) {
		if (KACX.rest.indexOf('?') !== -1) path = path.replace('?', '&');
		return KACX.rest + path;
	}
	function api(path, opts) {
		opts = opts || {};
		var headers = opts.headers || {};
		headers['X-KAC-Voter'] = voterId();
		return getNonce().then(function (n) {
			headers['X-KAC-Nonce'] = n;
			var o = { method: opts.method || 'GET', headers: headers, cache: 'no-store' };
			if (opts.body) o.body = opts.body;
			return fetch(url(path), o).then(function (r) {
				return r.json().catch(function () { return {}; }).then(function (d) {
					if (!r.ok) { var e = new Error(d.message || I.error); e.code = d.code; e.data = d; e.status = r.status; throw e; }
					return d;
				});
			});
		});
	}
	var tt;
	function toast(msg, cls) {
		var el = document.getElementById('kac-toast');
		if (!el) return;
		el.textContent = msg; el.className = 'toast on ' + (cls || '');
		clearTimeout(tt); tt = setTimeout(function () { el.className = 'toast'; }, 2400);
	}
	function pad(n) { return n < 10 ? '0' + n : '' + n; }
	function initTimers() {
		var els = document.querySelectorAll('.cd[data-until]');
		if (!els.length) return;
		function tick() {
			var now = Math.floor(Date.now() / 1000);
			els.forEach(function (el) {
				var left = Math.max(0, parseInt(el.dataset.until, 10) - now);
				var D = Math.floor(left / 86400), H = Math.floor(left % 86400 / 3600), M = Math.floor(left % 3600 / 60);
				el.innerHTML = '<div><b>' + D + '</b><span>' + I.days + '</span></div><div><b>' + pad(H) + '</b><span>' + I.hours + '</span></div><div><b>' + pad(M) + '</b><span>' + I.minutes + '</span></div>';
				if (left === 0 && !el.dataset.done) { el.dataset.done = 1; setTimeout(function () { location.reload(); }, 6000); }
			});
		}
		tick(); setInterval(tick, 15000);
	}
	function share(kind, link, title) {
		if (kind === 'link') {
			if (navigator.share && /Mobi|Android/i.test(navigator.userAgent)) { navigator.share({ title: title, url: link }).catch(function () {}); return; }
			if (navigator.clipboard) navigator.clipboard.writeText(link).then(function () { toast(I.copied, 'ok'); }); else window.prompt('', link);
		} else if (kind === 'vk') {
			window.open('https://vk.com/share.php?url=' + encodeURIComponent(link) + '&title=' + encodeURIComponent(title), '_blank', 'noopener');
		} else if (kind === 'tg') {
			window.open('https://t.me/share/url?url=' + encodeURIComponent(link) + '&text=' + encodeURIComponent(title), '_blank', 'noopener');
		}
	}
	document.addEventListener('click', function (e) {
		var b = e.target.closest('.share [data-share]');
		if (b) { var box = b.closest('.share'); share(b.dataset.share, box.dataset.url || location.href, box.dataset.title || document.title); }
	});
	// Выводим блок из узкой колонки темы: ширина до 1240px и центр по окну (как в макете).
	function fit() {
		document.querySelectorAll('.kac.kac-contest,.kac.kac-results,.kac.kac-formwrap,.kac.kac-contests,.kac.work-page').forEach(function (el) {
			el.style.marginLeft = '0'; el.style.width = '';
			var w = Math.min(1240, document.documentElement.clientWidth - 32);
			el.style.width = w + 'px';
			var r = el.getBoundingClientRect();
			el.style.marginLeft = ((document.documentElement.clientWidth - w) / 2 - r.left) + 'px';
		});
	}
	window.addEventListener('resize', fit);
	document.addEventListener('DOMContentLoaded', fit);
	window.addEventListener('load', fit);
	window.KACApi = { api: api, voterId: voterId, toast: toast, nonce: getNonce, share: share };
	document.addEventListener('DOMContentLoaded', initTimers);
})();
