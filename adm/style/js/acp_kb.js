(function($) { // Avoid conflicts with other libraries

  'use strict';

  $('#kb_font_icon').on('keyup blur', function () {
    var input = $(this).val();
    var $icon = $(this).next('i');
    if (input.length > 0) {
      $icon.attr('class', 'icon acp-icon fa-2x fa-' + input);
    } else {
      $icon.attr('class', '');
    }
  });
})(jQuery);


function show_extensions(elem)
{
  var selected = elem.querySelectorAll('#' + elem.id + ' option:checked');
  var values = Array.from(selected).map(el => el.value);
  var str = values.join(', ');
  var elementname = 'ext_' + (elem.name).replace(/extensions|\[|\]/ig, "");

  if (!str) { str = ' '; }
  if (document.getElementById(elementname).textContent)
  {
    document.getElementById(elementname).textContent = str;
  }
  else if (document.getElementById(elementname).firstChild.nodeValue)
  {
    document.getElementById(elementname).firstChild.nodeValue = str;
  }
}
