// $Id$

linkFile('../includes/js/visible.js');

addLoadListener(function () {
  attachEventListener(document.getElementById('testPHPbtn'), 'click', testPHPInfo);
  attachEventListener(document.getElementById('odbc_db'), 'change', document.set_odbc_db.submit);
  attachEventListener(document.getElementById('logoutBtn'), 'click', function () {
    document.location.href = 'index.php?action=logout'
  });
  attachEventListener(document.getElementById('form_user_inc'), 'change', auth_handler);
  attachEventListener(document.getElementById('saveBtn'), 'click', function () {
    return validate();
  });
  attachEventListener(document.getElementById('launchBtn'), 'click', function () {
    window.open('../index.php', 'webcalendar');
  });

});
function validate(form) {
  // Only check to make sure single-user login is specified
  // if in single-user mode.
  var err = '',
  form = document.form_app_settings,
  listid = 0; // Find id of single user object.

  for (var i = form.form_user_inc.length - 1; i >= 0; i--) {
    if (form.form_user_inc.options[i].value == 'none') {
      listid = i;
      break;
    }
  }
  if (form.form_user_inc.options[listid].selected) {
    if (form.form_single_user_login.value.length == 0) {
      alert(xlate['input1UserLogin']); // translate( 'specify Single-User login' )
      form.form_single_user_login.focus();
      return false;
    }
  }
  if (form.form_server_url.value == '') {
    err += xlate['reqServerURL']; // translate( 'Server URL is required.' )
    form.form_server_url.select();
    form.form_server_url.focus();
  } else if (form.form_server_url.value.charAt(
      form.form_server_url.value.length - 1) != '/') {
    err += xlate['endServerURL']; // translate( 'Server URL must end with /.' )
    form.form_server_url.select();
    form.form_server_url.focus();
  }
  if (err != '') {
    alert(xlate['errorXXX'].replace(/XXX/, err)); // translate( 'Error XXX' )
    return false;
  }
  // Submit form...
  form.submit();
}
function auth_handler() {
  var form = document.form_app_settings,
  listid = 0; // Find id of single user object.

  for (var i = form.form_user_inc.length - 1; i >= 0; i--) {
    if (form.form_user_inc.options[i].value == 'none') {
      listid = i;
      break;
    }
  }
  toggleVisible('singleuser'
    (form.form_user_inc.options[listid].selected ? 'visible' : 'hidden'));
}
function db_type_handler() {
  var form = document.dbform,
  listid = 0,
  selectvalue = form.form_db_type.value;

  if (selectvalue == 'sqlite' || $db_type == 'sqlite3' || selectvalue == 'ibase') {
    form.form_db_database.size = 65;
    document.getElementById('db_name').innerHTML = xlate['dbNameStr'] + ' ' + xlate['fullPath'];
    // translate( 'Database Name' ) translate( 'Full Path (no backslashes)' )
  } else {
    form.form_db_database.size = 20;
    document.getElementById('db_name').innerHTML = xlate['dbNameStr'];
  }
}
function chkPassword() {
  var form = document.dbform,
  db_pass = form.form_db_password.value,
  illegalChars = /\#/;
  // Do not allow #.../\#/ would stop all non-alphanumeric.

  if (illegalChars.test(db_pass)) {
    alert(xlate['illegalPwdChr']); // translate( 'illegal chars in password' )
    form.form_db_password.select();
    form.form_db_password.focus();
    return false;
  }
}
