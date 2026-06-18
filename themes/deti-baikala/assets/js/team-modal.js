(function () {
	var members = window.dbTeamMembers || {};
	var modal = document.getElementById('teamModal');
	if (!modal) return;

	var photoEl = document.getElementById('teamModalPhoto');
	var nameEl  = document.getElementById('teamModalName');
	var roleEl  = document.getElementById('teamModalRole');
	var descEl  = document.getElementById('teamModalDesc');

	function openModal(id) {
		var m = members[id];
		if (!m) return;
		photoEl.innerHTML = m.photo
			? '<img src="' + m.photo + '" alt="' + m.name.replace(/"/g, '&quot;') + '">'
			: '';
		nameEl.textContent = m.name;
		roleEl.textContent = m.role;
		descEl.textContent = m.desc;
		modal.classList.add('is-open');
		modal.setAttribute('aria-hidden', 'false');
		document.body.style.overflow = 'hidden';
	}

	function closeModal() {
		modal.classList.remove('is-open');
		modal.setAttribute('aria-hidden', 'true');
		document.body.style.overflow = '';
	}

	document.querySelectorAll('.team-card[data-member-id]').forEach(function (card) {
		card.addEventListener('click', function () { openModal(card.dataset.memberId); });
	});

	document.addEventListener('click', function (e) {
		var link = e.target.closest('.team-link[data-id]');
		if (link) { e.preventDefault(); openModal(link.dataset.id); }
	});

	document.getElementById('teamModalClose').addEventListener('click', closeModal);
	document.getElementById('teamModalBackdrop').addEventListener('click', closeModal);
	document.addEventListener('keydown', function (e) { if (e.key === 'Escape') closeModal(); });
})();
