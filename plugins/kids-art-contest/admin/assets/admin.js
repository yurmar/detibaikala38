(function () {
	'use strict';
	var A = window.KACA || {}, I = A.i18n || {};
	function $(s, c) { return (c || document).querySelector(s); }
	function $$(s, c) { return Array.prototype.slice.call((c || document).querySelectorAll(s)); }
	function api(path, method, body) {
		return fetch(A.rest + path, { method: method || 'GET', headers: { 'X-WP-Nonce': A.nonce, 'Content-Type': 'application/json' }, body: body ? JSON.stringify(body) : undefined, credentials: 'same-origin' })
			.then(function (r) { return r.json().catch(function () { return {}; }).then(function (d) { if (!r.ok) throw new Error(d.message || I.error); return d; }); });
	}

	/* ---- Уведомления (toast) ---- */
	var MSG = { approve: 'Работа одобрена', reject: 'Работа отклонена', disqualify: 'Работа дисквалифицирована', pending: 'Работа возвращена в очередь', 'delete': 'Работа удалена' };
	function toast(text, err) {
		var t = document.createElement('div'); t.className = 'kac-toast' + (err ? ' err' : ''); t.textContent = text; t.setAttribute('role', 'status');
		document.body.appendChild(t);
		setTimeout(function () { t.classList.add('on'); }, 10);
		setTimeout(function () { t.classList.remove('on'); setTimeout(function () { t.remove(); }, 300); }, err ? 6000 : 3500);
	}
	try { var pt = sessionStorage.getItem('kacToast'); if (pt) { sessionStorage.removeItem('kacToast'); toast(pt); } } catch (e) {}
	function busy(b, on) {
		if (on) { b.dataset.l = b.innerHTML; b.disabled = true; b.style.opacity = '.6'; b.textContent = 'Подождите…'; }
		else { b.disabled = false; b.style.opacity = ''; if (b.dataset.l) b.innerHTML = b.dataset.l; }
	}
	function confirmDelete(n, title) {
		return window.confirm(n > 1 ? 'Удалить безвозвратно работы (' + n + ')? Будут стёрты файлы, данные участников и голоса.' : 'Удалить работу' + (title ? ' «' + title + '»' : '') + ' безвозвратно? Будут стёрты файл, данные участника и голоса. Это нельзя отменить.');
	}

	/* ---- Маска дат ДД.ММ.ГГГГ ЧЧ:ММ ---- */
	$$('.kac-dt').forEach(function (i) {
		i.addEventListener('input', function () {
			var d = i.value.replace(/\D/g, '').slice(0, 12), o = d.slice(0, 2);
			if (d.length > 2) o += '.' + d.slice(2, 4);
			if (d.length > 4) o += '.' + d.slice(4, 8);
			if (d.length > 8) o += ' ' + d.slice(8, 10);
			if (d.length > 10) o += ':' + d.slice(10, 12);
			i.value = o;
		});
		// Календарь: кнопка открывает системный выбор даты, результат пишется как ДД.ММ.ГГГГ (время остаётся своим, 24 ч).
		var btn = document.createElement('button'); btn.type = 'button'; btn.className = 'wpbtn sm'; btn.textContent = '📅'; btn.title = 'Выбрать дату'; btn.style.marginLeft = '4px';
		var nat = document.createElement('input'); nat.type = 'date'; nat.tabIndex = -1; nat.setAttribute('aria-hidden', 'true');
		nat.style.cssText = 'position:absolute;opacity:0;width:1px;height:1px;pointer-events:none';
		i.insertAdjacentElement('afterend', btn); btn.insertAdjacentElement('afterend', nat);
		btn.addEventListener('click', function () {
			var m = i.value.match(/^(\d{2})\.(\d{2})\.(\d{4})/);
			nat.value = m ? m[3] + '-' + m[2] + '-' + m[1] : '';
			if (nat.showPicker) { try { nat.showPicker(); return; } catch (e) {} }
			nat.style.pointerEvents = 'auto'; nat.focus(); nat.click();
		});
		nat.addEventListener('change', function () {
			if (!nat.value) return;
			var p = nat.value.split('-'), t = (i.value.match(/\s(\d{2}:\d{2})$/) || [])[1] || '00:00';
			i.value = p[2] + '.' + p[1] + '.' + p[0] + ' ' + t;
			i.dispatchEvent(new Event('change', { bubbles: true }));
		});
	});

	/* ---- Редактор конкурса ---- */
	var groups = $('#kac-groups');
	if (groups) {
		var tpl = $('#kac-group-tpl').innerHTML;
		$('.kac-group-add').addEventListener('click', function () { groups.insertAdjacentHTML('beforeend', tpl.replace(/__i__/g, Date.now())); });
		document.addEventListener('click', function (e) { if (e.target.classList.contains('kac-group-del')) e.target.closest('.rep').remove(); });
		// Предупреждение при смене групп / режима голосования, если в конкурсе уже есть работы.
		var form = $('#post');
		if (form && $('.kac-a[data-has-works="1"]')) {
			var snap = function () { return JSON.stringify($$('#kac-groups input').map(function (i) { return i.value; })) + ($('input[name=kac_vote_mode]:checked') || {}).value; };
			var before = snap();
			form.addEventListener('submit', function (e) { if (snap() !== before && !window.confirm(I.warnChange)) e.preventDefault(); });
		}
	}
	var pick = $('#kac_rules_pick');
	if (pick && window.wp && wp.media) {
		var frame;
		pick.addEventListener('click', function (e) {
			e.preventDefault();
			if (!frame) {
				frame = wp.media({ title: I.chooseRules, library: { type: 'application/pdf' }, button: { text: I.use }, multiple: false });
				frame.on('select', function () {
					var a = frame.state().get('selection').first().toJSON();
					$('#kac_rules_file').value = a.id;
					$('#kac_rules_name').innerHTML = '📄 <a target="_blank" href="' + a.url + '"></a>';
					$('#kac_rules_name a').textContent = a.filename || a.title;
				});
			}
			frame.open();
		});
		$('#kac_rules_clear').addEventListener('click', function (e) { e.preventDefault(); $('#kac_rules_file').value = 0; $('#kac_rules_name').textContent = '—'; });
	}
	document.addEventListener('click', function (e) {
		var c = e.target.closest('.kac-copy');
		if (c) { e.preventDefault(); var t = c.dataset.copy; if (navigator.clipboard) navigator.clipboard.writeText(t); c.textContent = '✓'; setTimeout(function () { c.textContent = '📋'; }, 1200); }
		var b = e.target.closest('[data-confirm]');
		if (b && !window.confirm(b.dataset.confirm)) e.preventDefault();
	});

	/* ---- Окно отклонения (модерация и карточка работы) ---- */
	var modal = $('#kac-rejmodal'), pending = null;
	function setupModal() {
		var box = $('#kac-reasons');
		box.innerHTML = '';
		(A.reasons || []).forEach(function (r, i) {
			var l = document.createElement('label'); l.className = 'r';
			var inp = document.createElement('input'); inp.type = 'radio'; inp.name = 'kac_rr'; inp.value = r; if (i === 0) inp.checked = true;
			l.appendChild(inp); l.appendChild(document.createTextNode(' ' + r)); box.appendChild(l);
		});
		$('#kac-rej-cancel').addEventListener('click', function () { pending = null; modal.hidden = true; });
		$('#kac-rej-ok').addEventListener('click', function () {
			if (!pending) return;
			var radio = $('input[name=kac_rr]:checked', modal), reason = radio && radio.value !== '__other' ? radio.value : '', extra = $('#kac-reason').value.trim();
			if (extra) reason = reason ? reason + '. ' + extra : extra;
			var p = pending; pending = null; modal.hidden = true;
			p.done({ reason: reason, allow_resubmit: $('#kac-allow-resubmit').checked && p.action === 'reject', notify: $('#kac-notify').checked });
		});
	}
	function askReason(action, title, done) {
		pending = { action: action, done: done };
		$('#kac-rejtitle').textContent = title;
		$('#kac-resubmit-wrap').hidden = action === 'disqualify';
		$('#kac-reason').value = '';
		var first = $('input[name=kac_rr]', modal); if (first) first.checked = true;
		modal.hidden = false;
	}
	if (modal) setupModal();

	/* ---- Модерация: плитки ---- */
	var grid = $('#kac-mgrid');
	if (grid) {
		var tiles = function () { return $$('.mcard', grid).filter(function (t) { return !t.classList.contains('gone'); }); };
		var pv = $('#kac-pv');
		function setActive(t) { $$('.mcard.is-active', grid).forEach(function (x) { x.classList.remove('is-active'); }); if (t) { t.classList.add('is-active'); t.scrollIntoView({ block: 'nearest' }); } }
		function updPend(delta) {
			var el = $('#kac-pendcnt'); if (!el || grid.dataset.status !== 'kac_pending') return;
			var n = parseInt(el.textContent, 10) + delta; el.textContent = n > 0 ? n + ' ждут проверки' : '0'; el.hidden = n <= 0;
		}
		function act(ids, action, opts) {
			return Promise.all(ids.map(function (id) {
				var req = action === 'delete' ? api('/admin/works/' + id, 'DELETE') : api('/admin/works/' + id + '/moderate', 'POST', Object.assign({ action: action }, opts || {}));
				return req.then(function () {
					var t = $('.mcard[data-id="' + id + '"]', grid);
					if (t) { t.classList.add('gone'); setTimeout(function () { t.remove(); }, 300); }
					updPend(-1);
				});
			})).then(function () { toast(ids.length > 1 ? MSG[action] + ' (' + ids.length + ')' : MSG[action]); }).catch(function (e) { toast(e.message, true); });
		}
		function ask(ids, action) {
			if (action === 'approve' || action === 'pending') return act(ids, action, { notify: true });
			if (action === 'delete') { var dt = ids.length === 1 ? $('.mcard[data-id="' + ids[0] + '"]', grid) : null; if (confirmDelete(ids.length, dt ? dt.dataset.title : '')) return act(ids, action); return; }
			var title = action === 'disqualify' ? 'Дисквалифицировать' : 'Отклонить';
			if (ids.length === 1) { var t = $('.mcard[data-id="' + ids[0] + '"]', grid); title += ' работу «' + (t ? t.dataset.title : '') + '»'; } else title += ' (' + ids.length + ')';
			askReason(action, title, function (o) { act(ids, action, o); });
		}
		grid.addEventListener('click', function (e) {
			var b = e.target.closest('[data-act]');
			if (b) { ask([b.closest('.mcard').dataset.id], b.dataset.act); return; }
			var im = e.target.closest('.im');
			if (im && !e.target.classList.contains('cb')) { var t = im.closest('.mcard'); setActive(t); pv.querySelector('img').src = t.dataset.large; pv.classList.add('on'); }
		});
		pv.addEventListener('click', function () { pv.classList.remove('on'); });
		var all = $('#kac-pickall');
		if (all) all.addEventListener('change', function () { $$('.kac-pick', grid).forEach(function (c) { c.checked = all.checked; }); });
		$('#kac-bulk-apply').addEventListener('click', function () {
			var ids = $$('.kac-pick:checked', grid).map(function (c) { return c.closest('.mcard').dataset.id; }), a = $('#kac-bulk-action').value;
			if (ids.length && a) ask(ids, a);
		});
		$$('#kac-filters select').forEach(function (s) { s.addEventListener('change', function () { if (s.id === 'kac-bulk-action') return; s.form.submit(); }); });
		document.addEventListener('keydown', function (e) {
			if (/^(INPUT|TEXTAREA|SELECT)$/.test(e.target.tagName) || e.metaKey || e.ctrlKey || e.altKey) return;
			if (!modal.hidden) { if (e.key === 'Escape') { modal.hidden = true; pending = null; } return; }
			if (pv.classList.contains('on')) { if (e.key === 'Escape' || e.key === ' ') { pv.classList.remove('on'); e.preventDefault(); } return; }
			var list = tiles(), cur = $('.mcard.is-active', grid), i = list.indexOf(cur), k = e.key.toLowerCase();
			if (e.key === 'ArrowRight' || e.key === 'ArrowDown') { e.preventDefault(); setActive(list[Math.min(list.length - 1, i + 1)]); }
			else if (e.key === 'ArrowLeft' || e.key === 'ArrowUp') { e.preventDefault(); setActive(list[Math.max(0, i - 1)]); }
			else if (!cur) return;
			else if (e.key === ' ') { e.preventDefault(); pv.querySelector('img').src = cur.dataset.large; pv.classList.add('on'); }
			else if (k === 'a' || k === 'ф') { var nxt = list[i + 1] || list[i - 1]; ask([cur.dataset.id], 'approve'); setTimeout(function () { setActive(nxt); }, 350); }
			else if (k === 'r' || k === 'к') ask([cur.dataset.id], 'reject');
		});
		var first = tiles()[0]; if (first) setActive(first);
	}

	/* ---- Карточка работы ---- */
	var card = $('.kac-card-page');
	if (card) {
		var id = card.dataset.id;
		var tg = $('#kac-edit-toggle');
		tg.addEventListener('click', function () { var ed = $('#kac-desc-edit'); ed.hidden = !ed.hidden; $('#kac-desc-view').hidden = !ed.hidden; });
		$('#kac-save-work').addEventListener('click', function () {
			api('/admin/works/' + id, 'PATCH', { title: $('#kac-f-title').value, description: $('#kac-f-desc').value, technique: $('#kac-f-tech').value }).then(function () { location.reload(); }).catch(function (e) { window.alert(e.message); });
		});
		$('#kac-save-note').addEventListener('click', function () { api('/admin/works/' + id, 'PATCH', { note: $('#kac-f-note').value }).then(function () { window.alert('✓'); }).catch(function (e) { window.alert(e.message); }); });
		$$('[data-rotate]').forEach(function (b) {
			b.addEventListener('click', function () { api('/admin/works/' + id + '/rotate', 'POST', { dir: b.dataset.rotate }).then(function (r) { var im = $('#kac-work-img'); if (im && r.thumb) im.src = r.thumb; }).catch(function (e) { window.alert(e.message); }); });
		});
		var img = $('#kac-work-img'), pv2 = $('#kac-pv');
		if (img) img.addEventListener('click', function () { pv2.querySelector('img').src = img.src; pv2.classList.add('on'); });
		pv2.addEventListener('click', function () { pv2.classList.remove('on'); });
		$$('.kac-decision [data-act]').forEach(function (b) {
			b.addEventListener('click', function () {
				var a = b.dataset.act;
				var done = function () { try { sessionStorage.setItem('kacToast', MSG[a]); } catch (e) {} if (a === 'delete') location.href = 'admin.php?page=kac-works'; else location.reload(); };
				var go = function (o) {
					$$('.kac-decision button').forEach(function (x) { x.disabled = true; }); busy(b, true);
					var req = a === 'delete' ? api('/admin/works/' + id, 'DELETE') : api('/admin/works/' + id + '/moderate', 'POST', Object.assign({ action: a }, o || {}));
					req.then(done).catch(function (e) { $$('.kac-decision button').forEach(function (x) { x.disabled = false; }); busy(b, false); toast(e.message, true); });
				};
				if (a === 'delete') { if (confirmDelete(1, $('#kac-f-title') ? $('#kac-f-title').value : '')) go(); return; }
				if (a === 'reject' || a === 'disqualify') askReason(a, a === 'reject' ? 'Отклонить работу' : 'Дисквалифицировать работу', go); else go({ notify: true });
			});
		});
	}

	/* ---- Голоса ---- */
	var vall = $('#kac-vote-all');
	if (vall) {
		vall.addEventListener('change', function () { $$('.kac-vote-pick').forEach(function (c) { c.checked = vall.checked; }); });
		$$('#kac-vfilters select, #kac-vfilters input[name=vq]').forEach(function (el) { if (el.id !== 'kac-vote-action') el.addEventListener('change', function () { el.form.submit(); }); });
		var ap = $('#kac-vote-apply');
		if (ap) ap.addEventListener('click', function () {
			var ids = $$('.kac-vote-pick:checked').map(function (c) { return +c.value; }), a = $('#kac-vote-action').value;
			if (!ids.length || !a) return;
			if (a === 'annul' && !window.confirm(I.confirmAnnul)) return;
			api('/admin/votes/' + a, 'POST', { ids: ids }).then(function () { location.reload(); }).catch(function (e) { window.alert(e.message); });
		});
	}
	document.addEventListener('click', function (e) {
		var d = e.target.closest('[data-alert-dismiss]'), n = e.target.closest('[data-alert-annul]');
		if (d) api('/admin/alerts/' + d.dataset.alertDismiss + '/dismiss', 'POST').then(function () { d.closest('.knotice').remove(); });
		if (n && window.confirm(I.confirmAnnul)) api('/admin/alerts/' + n.dataset.alertAnnul + '/annul', 'POST').then(function () { location.reload(); }).catch(function (x) { window.alert(x.message); });
	});

	/* ---- Итоги: вкладки групп ---- */
	var rt = $('#kac-restabs');
	if (rt) {
		$$('a', rt).forEach(function (a) {
			a.addEventListener('click', function (e) {
				e.preventDefault();
				$$('a', rt).forEach(function (x) { x.classList.toggle('on', x === a); });
				$$('.stab[data-g]').forEach(function (p) { var on = p.dataset.g === a.dataset.g; p.classList.toggle('on', on); p.hidden = !on; });
			});
		});
	}

	/* ---- Настройки: вкладки и шаблоны писем ---- */
	var st = $('#kac-settabs');
	if (st) {
		$$('a', st).forEach(function (a) {
			a.addEventListener('click', function (e) {
				e.preventDefault();
				$$('a', st).forEach(function (x) { x.classList.toggle('on', x === a); });
				$$('.stab').forEach(function (p) { var on = p.id === a.dataset.t; p.classList.toggle('on', on); p.hidden = !on; });
				if (history.replaceState) history.replaceState(null, '', location.pathname + '?page=kac-settings&tab=' + a.dataset.t);
			});
		});
		$$('.kac-tpl-toggle').forEach(function (a) {
			a.addEventListener('click', function (e) { e.preventDefault(); var r = $('.kac-tpl[data-k="' + a.dataset.k + '"]'); r.hidden = !r.hidden; });
		});
	}
})();
