<?php 
/*
 * $Id$
 *
 * Page Description:
 * Installation Header information
 */
defined ( '_ISVALID' ) or die ( 'You cannot access this file directly!' );

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head><title>WebCalendar Setup Wizard</title>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<script language="JavaScript" type="text/javascript">
<!-- <![CDATA[
// detect browser
NS4 = (document.layers) ? 1 : 0;
IE4 = (document.all) ? 1 : 0;
// W3C stands for the W3C standard, implemented in Mozilla (and Netscape 6) and IE5
W3C = (document.getElementById) ? 1 : 0; 

<?php   if ( ! empty ( $_SESSION['validuser'] ) ) { ?>
function testPHPInfo () {
  var url;
  url = "index.php?action=phpinfo";
  //alert ( "URL:\n" + url );
  window.open ( url, "wcTestPHPInfo", "width=800,height=600,resizable=yes,scrollbars=yes" );
}
<?php } ?>
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
      alert ("<?php etranslate ( 'Error you must specify a\\nSingle-User Login', true ) ?> ");
      form.form_single_user_login.focus ();
      return false;
    }
  }
  if ( incval == 'UserImap' ) {
    if ( form.form_imap_server.value.length == 0 ) {
      // No single user login specified
      alert ("<?php etranslate ( 'Error you must specify an\\nIMAP Server', true ) ?> ");
      form.form_imap_server.focus ();
      return false;
    }
  }
  if ( incval.substr(0,7) == 'UserApp' ) {
    if ( form.form_user_app_path.value.length == 0 ) {
      // No App Path specifies
      alert ("<?php etranslate ( 'Error you must specify an\\nApplication Path', true ) ?> ");
      form.form_user_app_path.focus ();
      return false;
    }
  }
  if ( form.form_server_url.value == "" ) {
    err += "<?php etranslate ( 'Server URL is required', true ) ?>" + "\n";
    form.form_server_url.select ();
    form.form_server_url.focus ();
  }
  else if ( form.form_server_url.value.charAt (
    form.form_server_url.value.length - 1 ) != '/' ) {
    err += "<?php etranslate ( 'Server URL must end with &quot;/&quot;', true )?>" + "\n";
    form.form_server_url.select ();
    form.form_server_url.focus ();
  }
 if ( err != "" ) {
    alert ( "<?php etranslate ( 'Error', true ) ?>" + ":\n\n" + err );
    return false;
  }
  // Submit form...
  form.submit ();
}
function auth_handler () {
  var inc = document.form_app_settings.form_user_inc;
	var i = inc.selectedIndex;
  var val = inc.options[i].value;
  if ( val == 'none' ) {
    makeVisible ( "singleuser", true );
  } else {
    makeInvisible ( "singleuser", true );
  }
  if ( val == 'UserImap' ) {
    makeVisible ( "imapserver", true );
  } else {
    makeInvisible ( "imapserver", true );
  }
  if ( val.substr(0,7) == 'UserApp' ) {
    makeVisible ( "userapppath", true );
  } else {
    makeInvisible ( "userapppath", true );
  }
}

function db_type_handler () {
  var form = document.dbform;
  // find id of db_type object
  var listid = 0;
  var selectvalue = form.form_db_type.value;
  if ( selectvalue == "sqlite" || selectvalue == "ibase" ) {
      form.form_db_database.size = 65;
    document.getElementById("db_name").innerHTML = 
    "<?php echo $datebaseNameStr ?>" + ": " +  
   "<?php etranslate ( 'Full Path (no backslashes)') ?>";
  } else {
      form.form_db_database.size = 20;
    document.getElementById("db_name").innerHTML = "<?php echo $datebaseNameStr ?>" + ": ";
  }
}
function chkPassword () {
  var form = document.dbform;
  var db_pass = form.form_db_password.value;
  var illegalChars = /\#/;
  // do not allow #.../\#/ would stop all non-alphanumeric
  if (illegalChars.test(db_pass)) {
    alert( "<?php etranslate ( 'The password contains illegal characters.', true ) ?>");
    form.form_db_password.select ();
    form.form_db_password.focus ();
    return false;
  } 
}

function makeVisible( name, hide ) {
 //alert (name);
 var ele;
  if ( W3C ) {
    ele = document.getElementById(name);
  } else if ( NS4 ) {
    ele = document.layers[name];
  } else { // IE4
    ele = document.all[name];
  }

  if ( NS4 ) {
    ele.visibility = "show";
  } else {  // IE4 & W3C & Mozilla
    ele.style.visibility = "visible";
    if ( hide )
     ele.style.display = "";
  }
}

function makeInvisible( name, hide ) {
  //alert (name);
 if (W3C) {
    document.getElementById(name).style.visibility = "hidden";
    if ( hide )
      document.getElementById(name).style.display = "none";
  } else if (NS4) {
    document.layers[name].visibility = "hide";
  } else {
    document.all[name].style.visibility = "hidden";
    if ( hide )
      document.all[name].style.display = "none";
  }
}
 //]]> -->
</script>
<style type="text/css">
body {
  background-color: #ffffff;
  font-family: Arial, Helvetica, sans-serif;
  margin: 0;
}
table {
  border: 0px solid #ccc;
}
th.pageheader {
  font-size: 18px;
 padding:10px;
  background-color: #eee;
}
th.header {
  font-size: 14px;
  background-color: #eee;
}
th.redheader {
  font-size: 14px;
  color: red; 
  background-color: #eee;
}
td {
  padding: 5px;
}
td.prompt {
  font-weight: bold;
  padding-right: 20px;
}
td.subprompt {
  font-weight: bold;
  padding-right: 20px;
 font-size: 12px;
}
div.nav {
  margin: 0;
  border-bottom: 1px solid #000;
}
div.main {
  margin: 10px;
}
li {
  margin-top: 10px;
}
doc.li {
  margin-top: 5px;
}
.recommended {
  color: green;
}
.notrecommended {
  color: red;
}
</style>
</head>
<body <?php if ( ! empty ($onload) ) echo "onload=\"$onload\""; ?> >

<?php //end of header ?>

