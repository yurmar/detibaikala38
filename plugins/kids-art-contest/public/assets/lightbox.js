/* Лайтбокс (разметка и поведение из макета, экран 2): зум кликом, колесом, щипком; ← →, Esc; #work-ID. */
(function () {
	'use strict';
	var I = (window.KAC && KAC.i18n) || {};
	var lb, stage, img, list = [], idx = -1, scale = 1, tx = 0, ty = 0, pointers = {}, startDist = 0, startScale = 1, drag = null, moved = false, lastFocus = null;

	function esc(s) { var d = document.createElement('div'); d.textContent = s == null ? '' : s; return d.innerHTML; }

	function build() {
		lb = document.getElementById('kac-lb');
		if (!lb) { lb = document.createElement('div'); lb.id = 'kac-lb'; lb.className = 'lb'; var w = document.createElement('div'); w.className = 'kac'; w.appendChild(lb); document.body.appendChild(w); }
		lb.removeAttribute('hidden');
		lb.addEventListener('click', function (e) {
			if (e.target === lb || e.target.closest('[data-lbclose]')) { close(); return; }
			var n = e.target.closest('[data-lbnav]'); if (n) { e.stopPropagation(); go(+n.dataset.lbnav); return; }
			var z = e.target.closest('[data-z]');
			if (z) { var s = z.dataset.z === 'in' ? scale + .5 : z.dataset.z === 'out' ? scale - .5 : 1; var r = stage.getBoundingClientRect(); zoomAt(r.left + r.width / 2, r.top + r.height / 2, s); }
		});
		document.addEventListener('keydown', function (e) {
			if (!lb.classList.contains('on')) return;
			if (e.key === 'Escape') close(); else if (e.key === 'ArrowLeft') go(-1); else if (e.key === 'ArrowRight') go(1); else if (e.key === 'Tab') trap(e);
		});
	}
	function html(c) {
		var d = c.dataset, meta = [];
		if (d.group) meta.push(d.group); if (d.tech) meta.push(d.tech);
		var voteBtn = '';
		if (d.vstate === 'open') voteBtn = '<button type="button" class="vbtn big" data-vote="' + d.id + '">' + esc(I.vote) + '</button><div style="font-size:12.5px;color:#999;text-align:center">' + esc(I.hint) + '</div>';
		else if (d.vstate === 'soon' || d.vstate === 'closed') { var b = c.querySelector('.vbtn'); voteBtn = '<button type="button" class="vbtn big closed" disabled>' + esc(b ? b.textContent : '') + '</button>'; }
		return '<div class="lb-box" role="dialog" aria-modal="true" aria-label="' + esc(d.title) + '">' +
			'<button type="button" class="lb-close" data-lbclose aria-label="' + esc(I.close) + '">✕</button>' +
			'<div class="lb-stage"><img src="' + esc(d.large) + '" alt="' + esc(d.title) + '" draggable="false">' +
			'<button type="button" class="lb-nav prev" data-lbnav="-1" aria-label="←">‹</button><button type="button" class="lb-nav next" data-lbnav="1" aria-label="→">›</button>' +
			'<div class="lb-tools"><button type="button" data-z="in">＋</button><button type="button" data-z="out">−</button><button type="button" data-z="fit">⤢</button></div></div>' +
			'<div class="lb-info"><span style="font-size:12.5px;color:#999;font-weight:800">' + esc(I.workno) + esc(d.number) + '</span>' +
			'<h3>' + esc(d.title) + '</h3><div class="who">' + esc(I.author) + ' <b>' + esc(d.author) + '</b></div>' +
			(meta.length ? '<div class="lb-meta">' + meta.map(function (m) { return '<span>' + esc(m) + '</span>'; }).join('') + '</div>' : '') +
			(d.desc ? '<p>' + esc(d.desc) + '</p>' : '') +
			(d.votes !== '' && d.votes != null ? '<div class="lb-votes"><b data-lbv="' + d.id + '">' + Number(d.votes).toLocaleString('ru-RU') + '</b><span>' + esc(I.votesWord) + '</span></div>' : '') +
			voteBtn +
			'<div class="share" data-url="' + esc(d.url) + '" data-title="' + esc(d.title) + '"><button type="button" data-share="link">🔗 ' + esc(I.link) + '</button><button type="button" data-share="vk">VK</button><button type="button" data-share="tg">Telegram</button></div>' +
			'<div style="font-size:12px;color:#aaa;margin-top:auto">' + esc(I.keys) + '</div></div></div>';
	}
	function bindStage() {
		stage = lb.querySelector('.lb-stage'); img = stage.querySelector('img');
		stage.addEventListener('wheel', function (e) { e.preventDefault(); zoomAt(e.clientX, e.clientY, scale * (e.deltaY < 0 ? 1.2 : 1 / 1.2)); }, { passive: false });
		stage.addEventListener('pointerdown', function (e) {
			if (e.target.closest('button')) return;
			try { stage.setPointerCapture(e.pointerId); } catch (x) {}
			pointers[e.pointerId] = { x: e.clientX, y: e.clientY }; moved = false;
			if (Object.keys(pointers).length === 2) { startDist = dist(); startScale = scale; }
			drag = { x: e.clientX, y: e.clientY, tx: tx, ty: ty };
			img.style.transition = 'none';
		});
		stage.addEventListener('pointermove', function (e) {
			if (!pointers[e.pointerId]) return;
			pointers[e.pointerId] = { x: e.clientX, y: e.clientY };
			if (Object.keys(pointers).length === 2) { moved = true; var c = center(); zoomAt(c.x, c.y, startScale * dist() / startDist); return; }
			if (scale > 1 && drag) {
				var dx = e.clientX - drag.x, dy = e.clientY - drag.y;
				if (Math.abs(dx) + Math.abs(dy) > 4) moved = true;
				tx = drag.tx + dx; ty = drag.ty + dy; apply();
			}
		});
		function up(e) {
			delete pointers[e.pointerId];
			if (!Object.keys(pointers).length) {
				img.style.transition = '';
				if (!moved && e.type === 'pointerup' && !e.target.closest('button')) { if (scale > 1) reset(); else zoomAt(e.clientX, e.clientY, 2.2); }
				drag = null;
			}
		}
		stage.addEventListener('pointerup', up); stage.addEventListener('pointercancel', up);
	}
	function dist() { var p = Object.keys(pointers).map(function (k) { return pointers[k]; }); return Math.hypot(p[0].x - p[1].x, p[0].y - p[1].y) || 1; }
	function center() { var p = Object.keys(pointers).map(function (k) { return pointers[k]; }); return { x: (p[0].x + p[1].x) / 2, y: (p[0].y + p[1].y) / 2 }; }
	function apply() { img.style.transform = 'translate(' + tx + 'px,' + ty + 'px) scale(' + scale + ')'; stage.classList.toggle('zoomed', scale > 1); }
	function reset() { scale = 1; tx = 0; ty = 0; apply(); }
	function zoomAt(cx, cy, s2) {
		s2 = Math.max(1, Math.min(5, s2));
		var r = stage.getBoundingClientRect(), px = cx - (r.left + r.width / 2), py = cy - (r.top + r.height / 2);
		tx = px - (px - tx) * (s2 / scale); ty = py - (py - ty) * (s2 / scale); scale = s2;
		if (scale === 1) { tx = 0; ty = 0; }
		apply();
	}
	function trap(e) {
		var f = lb.querySelectorAll('button:not([disabled])'); if (!f.length) return;
		var first = f[0], last = f[f.length - 1];
		if (e.shiftKey && document.activeElement === first) { e.preventDefault(); last.focus(); }
		else if (!e.shiftKey && document.activeElement === last) { e.preventDefault(); first.focus(); }
	}
	function show(i) {
		idx = (i + list.length) % list.length;
		var c = list[idx];
		lb.innerHTML = html(c); scale = 1; tx = 0; ty = 0; pointers = {};
		bindStage();
		if (list.length < 2) lb.querySelectorAll('.lb-nav').forEach(function (b) { b.hidden = true; });
		if (window.KACVote) KACVote.apply();
		if (location.hash !== '#work-' + c.dataset.id) history.replaceState(null, '', '#work-' + c.dataset.id);
		var cl = lb.querySelector('.lb-close'); if (cl) cl.focus();
	}
	function open(card) {
		if (!lb) build();
		var scope = card.closest('.grid') || card.parentElement;
		list = scope ? Array.prototype.slice.call(scope.querySelectorAll('.wcard[data-id]')) : [card];
		if (!list.length) list = [card];
		lastFocus = document.activeElement;
		show(list.indexOf(card));
		lb.classList.add('on'); document.documentElement.style.overflow = 'hidden';
	}
	function close() {
		if (!lb) return;
		lb.classList.remove('on'); document.documentElement.style.overflow = '';
		if (/^#work-/.test(location.hash)) history.replaceState(null, '', location.pathname + location.search);
		if (lastFocus && lastFocus.focus) lastFocus.focus();
	}
	function go(d) { if (list.length > 1) show(idx + d); }
	function openById(id) {
		var c = document.querySelector('.wcard[data-id="' + id + '"]');
		if (c) { open(c); return true; }
		return false;
	}
	document.addEventListener('click', function (e) {
		var o = e.target.closest('[data-open]');
		if (o && !o.closest('.lb')) { e.preventDefault(); openById(o.dataset.open); }
	});
	document.addEventListener('keydown', function (e) {
		if ((e.key === 'Enter' || e.key === ' ') && e.target.matches && e.target.matches('.wcard .img[data-open]')) { e.preventDefault(); openById(e.target.dataset.open); }
	});
	window.addEventListener('hashchange', function () {
		var m = location.hash.match(/^#work-(\d+)$/);
		if (m && !(lb && lb.classList.contains('on'))) openById(m[1]);
	});
	window.KACLightbox = { openById: openById };
})();
