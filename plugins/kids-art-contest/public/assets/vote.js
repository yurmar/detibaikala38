/* Голосование: состояния кнопки как в макете (обычная → loading → voted). */
(function () {
	'use strict';
	var I = (window.KAC && KAC.i18n) || {};
	var voted = {};

	function btns(id) { return document.querySelectorAll('[data-vote="' + id + '"]'); }
	function markVoted(id, already) {
		btns(id).forEach(function (b) {
			b.classList.remove('loading'); b.classList.add('voted'); b.disabled = false;
			b.textContent = '✓ ' + (already ? I.voted2 : I.voted);
		});
	}
	function setVotes(id, n) {
		if (n === null || n === undefined) return;
		document.querySelectorAll('[data-vc="' + id + '"]').forEach(function (v) {
			v.textContent = '❤ ' + Number(n).toLocaleString('ru-RU');
			if (v.animate) v.animate([{ transform: 'scale(1.4)' }, { transform: 'scale(1)' }], { duration: 350 });
		});
		document.querySelectorAll('[data-lbv="' + id + '"]').forEach(function (v) { v.textContent = Number(n).toLocaleString('ru-RU'); });
		var card = document.querySelector('.wcard[data-id="' + id + '"]'); if (card) card.dataset.votes = n;
	}
	function cast(workId, extra) {
		var body = { fp: window.KACFp ? KACFp.value() : '', ix: window.KACFp ? KACFp.interactions() : 0 };
		if (extra) Object.keys(extra).forEach(function (k) { body[k] = extra[k]; });
		return window.KACApi.api('/works/' + workId + '/vote', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(body) })
			.catch(function (e) {
				if (e.code === 'captcha_required') return KACCaptcha.get().then(function (cap) { return cast(workId, cap); });
				throw e;
			});
	}
	function vote(id) {
		if (voted[id]) { KACApi.toast(I.already, 'warn'); return; }
		btns(id).forEach(function (b) { b.classList.add('loading'); });
		cast(id).then(function (r) {
			voted[id] = true; markVoted(id); setVotes(id, r.votes); KACApi.toast(I.thanks, 'ok');
		}).catch(function (e) {
			btns(id).forEach(function (b) { b.classList.remove('loading'); });
			if (e.code === 'already_voted') { voted[id] = true; markVoted(id, true); }
			if (e.message !== 'cancel') KACApi.toast(e.message || I.error, 'warn');
		});
	}
	function apply() { Object.keys(voted).forEach(function (id) { markVoted(id); }); }
	function loadMine(contestId) {
		return KACApi.api('/contests/' + contestId + '/my-votes').then(function (r) {
			(r.works || []).forEach(function (id) { voted[id] = true; markVoted(id); });
		}).catch(function () {});
	}
	document.addEventListener('click', function (e) {
		var b = e.target.closest('[data-vote]');
		if (b && !b.classList.contains('closed') && b.tagName === 'BUTTON') { e.preventDefault(); vote(b.dataset.vote); }
	});
	document.addEventListener('DOMContentLoaded', function () {
		var seen = {};
		document.querySelectorAll('[data-contest]').forEach(function (el) {
			var c = el.dataset.contest;
			if (c && !seen[c] && document.querySelector('[data-vote]')) { seen[c] = 1; loadMine(c); }
		});
	});
	window.KACVote = { vote: vote, apply: apply, isVoted: function (id) { return !!voted[id]; } };
})();
