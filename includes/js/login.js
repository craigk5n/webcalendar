// $Id$

addLoadListener(function () {
  var dll = document.login_form.login;
  var error = wc_getCookie('err');
  var login = wc_getCookie('login');

  wc_setCookie('err', '', 0);
  wc_setCookie('login', '', 0);
  dll.focus();

  if (login != '')
    dll.select();

  if (error != '')
    alert(error);

  attachEventListener(document.getElementById('login'), 'submit', function () {
    return valid_form(this);
  });
});
// Error check login/password.
function valid_form(form) {
  if (form.login.value.length == 0 || form.password.value.length == 0) {
    alert(xlate['enterLoginPwd']); // translate( 'must enter login/password' )
    return false;
  }
  return true;
}
