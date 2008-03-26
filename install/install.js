/* $Id$  */
initPhpVars( 'install' );

function testPHPInfo () {
  var url = "index.php?action=phpinfo";
  window.open ( url, "wcTestPHPInfo", "width=800,height=600,resizable=yes,scrollbars=yes" );
}

function validate(form)
{
  var form = document.form_app_settings;
  var err = "";
  // only check is to make sure single-user login is specified if
  // in single-user mode
  var inc = document.form_app_settings.form_user_inc;
  var incval = inc.options[inc.selectedIndex].value;
  if ( incval == 'none' ) {
    if ( form.form_single_user_login.value.length == 0 ) {
      // No single user login specified
      alert ( errSingleUser );
      form.form_single_user_login.focus ();
      return false;
    }
  }
  if ( incval == 'UserImap' ) {
    if ( form.form_imap_server.value.length == 0 ) {
      // No single user login specified
      alert ( errIMAP );
      form.form_imap_server.focus ();
      return false;
    }
  }
  if ( incval.substr(0,7) == 'UserApp' ) {
    if ( form.form_user_app_path.value.length == 0 ) {
      // No App Path specifies
      alert ( errAppPath );
      form.form_user_app_path.focus ();
      return false;
    }
  }
  if ( form.form_server_url.value == "" ) {
    err += errServerURL + "\n";
    form.form_server_url.select ();
    form.form_server_url.focus ();
  }
  else if ( form.form_server_url.value.charAt (
    form.form_server_url.value.length - 1 ) != '/' ) {
    err += errServerSlash + "\n";
    form.form_server_url.select ();
    form.form_server_url.focus ();
  }
 if ( err != "" ) {
    alert ( errERROR + ":\n\n" + err );
    return false;
  }
  // Submit form...
  form.submit ();
}
function auth_handler () {
  var inc = document.form_app_settings.form_user_inc;
	var i = inc.selectedIndex;
  var val = inc.options[i].value;
	$('singleuser').showIf(val == 'none');
	$('imapserver').showIf(val == 'UserImap');
	$('userapppath').showIf(val.substr(0,7) == 'UserApp');
}

function db_type_handler () {
  var form = document.dbform;
  // find id of db_type object
  var listid = 0;
  var selectvalue = form.form_db_type.value;
  if ( selectvalue == "sqlite" || selectvalue == "ibase" ) {
      form.form_db_database.size = 65;
    document.getElementById("db_name").innerHTML = dbName + ": " + fullPath;
  } else {
      form.form_db_database.size = 20;
    document.getElementById("db_name").innerHTML = dbName + ": ";
  }
}
function chkPassword () {
  var form = document.dbform;
  var db_pass = form.form_db_password.value;
  var illegalChars = /\#/;
  // do not allow #.../\#/ would stop all non-alphanumeric
  if (illegalChars.test(db_pass)) {
    alert( errIlegal );
    form.form_db_password.select ();
    form.form_db_password.focus ();
    return false;
  } 
}


function initPhpVars(filename) {
  var url = 'install_ajax.php';
  var params = 'page=initPHP&filename=' + filename;
  var ajax = new Ajax.Request(url,
    {method: 'post', 
    parameters: params, 
    onComplete: setPhpVars});
}
function setPhpVars(originalRequest) {
  if (originalRequest.responseText) {
    text = originalRequest.responseText;
    eval ( text );
  }
}