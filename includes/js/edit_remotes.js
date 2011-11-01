// $Id$

var validform = true;

linkFile('includes/js/visible.js');

addLoadListener(function () {
  toggleVisible('nlayercolor', 'visible', 'none');

  attachEventListener(document.getElementById('deleEntry'), 'click',
    function () {
    // translate( 'really delete entry' )
    return confirm(xlate['reallyDeleteEntry']);
  });
  attachEventListener(document.getElementById('nid'), 'change', check_name);
  attachEventListener(document.getElementById('nlayer'), 'change',
    toggle_layercolor);
  attachEventListener(document.getElementById('prefform'), 'submit', function () {
    return valid_form(this);
  });
});
function valid_form(form) {
  var err = '';

  if (form.layercolor && !valid_color(form.layercolor.value))
    err += xlate['invalidColor']; // translate ( 'Invalid color' )

  if (err.length > 0) {
    alert(xlate['noBlankURL'].replace(/XXX/, err) + "\n\n"
       + xlate['formatColorRGB']); // translate ( 'Color format should be RGB' )
    return false;
  }
  if (!form.nurl.value) {
    alert(xlate['noBlankURL']); // translate( 'no blank URLs' )
    return false;
  }
  check_name();

  return validform;
}
function check_name() {
  var ajax = new Ajax.Request('ajax.php', {
      method: 'post',
      parameters: 'page=edit_remotes&name=' + $F('nid'),
      onComplete: showResponse
    });
}
function showResponse(originalRequest) {
  if (originalRequest.responseText) {
    text = originalRequest.responseText;
    // This causes javascript errors in Firefox, but these can be ignored.
    alert(text);
    document.prefform.nid.focus();
    validform = false;
  } else {
    validform = true;
  }
}
function toggle_layercolor() {
  toggleVisible('nlayercolor', 'visible',
    (document.prefform.nlayer.checked ? 'block' : 'none'));
}
