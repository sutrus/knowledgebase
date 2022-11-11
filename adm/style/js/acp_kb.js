(function($) { // Avoid conflicts with other libraries

	'use strict';

	$('#kb_font_icon').on('keyup blur', function() {
		let input = $(this).val();
		let $icon = $(this).next('i');
		if (input.length > 0) {
			$icon.attr('class', 'icon acp-icon fa-2x fa-' + input);
		} else {
			$icon.attr('class', '');
		}
	});
})(jQuery);


function show_extensions(elem) {
	const selected = elem.querySelectorAll('#' + elem.id + ' option:checked');
	let values = Array.from(selected).map(el => el.value);
	let str = values.join(', ');
	let elementname = 'ext_' + (elem.name).replace(/extensions|\[|\]/ig, "");

	if (!str) {
		str = ' ';
	}
	if (document.getElementById(elementname).textContent) {
		document.getElementById(elementname).textContent = str;
	} else if (document.getElementById(elementname).firstChild.nodeValue) {
		document.getElementById(elementname).firstChild.nodeValue = str;
	}
}
