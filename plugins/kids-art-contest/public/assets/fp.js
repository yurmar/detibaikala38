/* Отпечаток браузера (не персональный: набор грубых признаков → строка). */
(function () {
	'use strict';
	var parts = [];
	try {
		parts.push(screen.width + 'x' + screen.height + 'x' + screen.colorDepth);
		parts.push(Intl.DateTimeFormat().resolvedOptions().timeZone || '');
		parts.push(navigator.language || '');
		parts.push(navigator.hardwareConcurrency || '');
		parts.push(navigator.platform || '');
		var c = document.createElement('canvas'); c.width = 120; c.height = 30;
		var x = c.getContext('2d');
		x.textBaseline = 'top'; x.font = '14px Arial'; x.fillStyle = '#f60'; x.fillRect(10, 2, 60, 20);
		x.fillStyle = '#069'; x.fillText('kac-fp 😀', 2, 4);
		parts.push(c.toDataURL().slice(-64));
		var fonts = ['Arial', 'Georgia', 'Courier New', 'Verdana', 'Times New Roman', 'Comic Sans MS'], span = document.createElement('span');
		span.style.cssText = 'position:absolute;left:-9999px;font-size:72px'; span.textContent = 'mmmmmmmmmmlli'; document.body && document.body.appendChild(span);
		span.style.fontFamily = 'monospace'; var base = span.offsetWidth;
		fonts.forEach(function (f) { span.style.fontFamily = '"' + f + '",monospace'; parts.push(span.offsetWidth !== base ? 1 : 0); });
		span.remove();
	} catch (e) {}
	var interactions = 0;
	['pointerdown', 'keydown', 'touchstart', 'scroll', 'mousemove'].forEach(function (ev) {
		window.addEventListener(ev, function () { interactions++; }, { passive: true, once: false });
	});
	window.KACFp = { value: function () { return parts.join('|'); }, interactions: function () { return interactions; } };
})();
