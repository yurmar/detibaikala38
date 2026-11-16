/* Маска телефона +7 (___) ___-__-__ */
(function () {
	'use strict';
	function format(digits) {
		if (digits[0] === '8') digits = '7' + digits.slice(1);
		if (digits[0] !== '7') digits = '7' + digits;
		digits = digits.slice(0, 11);
		var d = digits.slice(1), out = '+7';
		if (d.length) out += ' (' + d.slice(0, 3);
		if (d.length >= 3) out += ')';
		if (d.length > 3) out += ' ' + d.slice(3, 6);
		if (d.length > 6) out += '-' + d.slice(6, 8);
		if (d.length > 8) out += '-' + d.slice(8, 10);
		return out;
	}
	window.KACMask = {
		phone: function (input) {
			input.addEventListener('input', function () {
				var digits = input.value.replace(/\D+/g, '');
				input.value = digits ? format(digits) : '';
			});
			input.addEventListener('focus', function () { if (!input.value) input.value = '+7 ('; });
			input.addEventListener('blur', function () { if (input.value.replace(/\D+/g, '').length <= 1) input.value = ''; });
		}
	};
})();
