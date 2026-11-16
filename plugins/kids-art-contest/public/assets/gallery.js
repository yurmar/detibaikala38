/* Галерея: вкладки групп, сортировка, поиск, «Показать ещё»; вкладки итогов. */
(function () {
	'use strict';
	var I = (window.KAC && KAC.i18n) || {};

	function init(g) {
		var cid = g.dataset.contest, grid = g.querySelector('.grid');
		var st = { group: '', sort: 'new', s: '', page: 1, pages: parseInt(g.dataset.pages, 10) || 1, seed: g.dataset.seed };
		var more = g.querySelector('.kac-more'), moreWrap = g.querySelector('.more'), empty = g.querySelector('.empty');
		var sortEl = g.querySelector('.sel'), search = g.querySelector('.search');
		var timer = null, busy = false;
		try { var sd = sessionStorage.getItem('kac_seed'); if (sd) st.seed = sd; else sessionStorage.setItem('kac_seed', st.seed); } catch (e) {}

		function load(reset) {
			if (busy) return Promise.resolve();
			busy = true;
			if (reset) st.page = 1;
			var q = 'group=' + encodeURIComponent(st.group) + '&sort=' + st.sort + '&page=' + st.page + '&s=' + encodeURIComponent(st.s) + '&seed=' + st.seed;
			return KACApi.api('/contests/' + cid + '/works?' + q).then(function (r) {
				if (reset) grid.innerHTML = '';
				grid.insertAdjacentHTML('beforeend', r.html);
				st.pages = r.pages;
				if (moreWrap) moreWrap.hidden = st.page >= st.pages;
				if (empty) { empty.hidden = r.total > 0; empty.textContent = st.s ? I.nothing : I.empty; }
				if (window.KACVote) KACVote.apply();
			}).catch(function () { KACApi.toast(I.error, 'warn'); }).then(function () { busy = false; });
		}
		g.addEventListener('click', function (e) {
			var tab = e.target.closest('.tabs button');
			if (tab) {
				g.querySelectorAll('.tabs button').forEach(function (t) { t.classList.toggle('on', t === tab); });
				st.group = tab.dataset.g; load(true);
			}
		});
		if (more) more.addEventListener('click', function () { st.page++; load(false); });
		if (sortEl) sortEl.addEventListener('change', function () { st.sort = sortEl.value; load(true); });
		if (search) search.addEventListener('input', function () { clearTimeout(timer); timer = setTimeout(function () { st.s = search.value.trim(); load(true); }, 350); });

		// Открытие работы по якорю #work-ID: подгружаем порции, пока не найдём.
		var m = location.hash.match(/^#work-(\d+)$/);
		if (m) {
			(function find(n) {
				if (window.KACLightbox && KACLightbox.openById(m[1])) return;
				if (n > 20 || st.page >= st.pages) return;
				st.page++; load(false).then(function () { find(n + 1); });
			})(0);
		}
	}
	function results() {
		document.querySelectorAll('.kac-results').forEach(function (r) {
			var tabs = r.querySelectorAll('.kac-restabs button');
			tabs.forEach(function (b) {
				b.addEventListener('click', function () {
					tabs.forEach(function (x) { x.classList.toggle('on', x === b); });
					r.querySelectorAll('.res-tab-pane').forEach(function (p) { p.hidden = p.dataset.g !== b.dataset.g; });
				});
			});
			var m = location.hash.match(/^#work-(\d+)$/);
			if (m && window.KACLightbox) {
				var card = r.querySelector('.wcard[data-id="' + m[1] + '"]');
				if (card) { var pane = card.closest('.res-tab-pane'); if (pane) { tabs.forEach(function (x) { x.classList.toggle('on', x.dataset.g === pane.dataset.g); }); r.querySelectorAll('.res-tab-pane').forEach(function (p) { p.hidden = p !== pane; }); } KACLightbox.openById(m[1]); }
			}
		});
	}
	document.addEventListener('DOMContentLoaded', function () { document.querySelectorAll('.kac-gallery').forEach(init); results(); });
})();
