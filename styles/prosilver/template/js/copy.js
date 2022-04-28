(function() {
	'use strict';
	document.body.addEventListener('click', copy, true);
	function copy(e) {
		var
			navClipboard = navigator.clipboard,
			t = e.target,
			c = t.dataset.copytarget,
			inp = (c ? document.querySelector(c) : null);
		if (inp && inp.select)
		{
			try
			{
				inp.select();
				if (!navClipboard)
				{
					// old method deprecated
					document.execCommand('copy');
				} else
				{
					// New method works only https
					navClipboard.writeText(inp.defaultValue);
				 }
				inp.blur();
				t.classList.add('copied');
				setTimeout(function() {
					t.classList.remove('copied'); }, 1500);
			 }
			 catch (err)
			 {
				alert('please press Ctrl/Cmd+C to copy');
			 }
		}
	}
})();
