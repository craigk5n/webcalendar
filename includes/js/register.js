// $Id$

var ds = document.selfreg,
validform = false;

linkFile('includes/js/prototype.js');

addLoadListener(function () {
  attachEventListener(document.getElementById('selfreg'), 'submit', function () {
    return valid_form();
  });
  attachEventListener(document.getElementById('user'), 'change', check_name);
  attachEventListener(document.getElementById('uemail'), 'change', check_uemail);
});
function valid_form() {
  if (ds.upassword1.value.length == 0) {
    alert(xlate['inputPassword']); // translate( 'must enter a password' )
    return false;
  }
  if (ds.user.value.length == 0) {
    alert(xlate['noBlankUsername']); // translate( 'no blank username' )
    return false;
  }
  if (ds.upassword1.value != ds.upassword2.value) {
    alert(xlate['passwordsNoMatch']); // translate( 'passwords not identical' )
    return false;
  }

  checkers('user', 'register');
  checkers('uemail', 'email');

  return validform;
}

function checkers(formfield, params) {
  var ajax = new Ajax.Request('ajax.php', {
      method: 'post',
      parameters: 'page=' + params + '&name=' + $F(formfield),
      onComplete: showResponse
    });
}

function showResponse(originalRequest) {
  if (originalRequest.responseText) {
    // This causes javascript errors in Firefox, but these can be ignored.
    alert(originalRequest.responseText);

    if (formfield == 'user')
      ds.user.focus();

    if (formfield == 'uemail')
      ds.uemail.focus();

    validform = false;
  } else {
    validform = true;
  }
}
