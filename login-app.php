<?php
require_once 'includes/classes/WebCalendar.class';

$WebCalendar =& new WebCalendar ( __FILE__ );

include 'includes/config.php';
include 'includes/dbi4php.php';
include 'includes/functions.php';

$WebCalendar->initializeFirstPhase();

include "includes/$user_inc";
include 'includes/translate.php';

$WebCalendar->initializeSecondPhase();

load_global_settings ();

$WebCalendar->setLanguage();

load_global_settings ();
load_user_preferences ( "guest" );


// Set default language
$lang = '';
if ( ! empty ( $LANGUAGE ) &&  $LANGUAGE != "Browser-defined" && $LANGUAGE != "none" ) {
  $lang = languageToAbbrev ( $LANGUAGE );
} else {
  $lang_long = get_browser_language ();
  $lang = languageToAbbrev ( $lang_long );
}

if ( empty ( $lang ) ) {
  $lang = 'en';
}

$charset = ( ! empty ( $LANGUAGE )?translate("charset"): "iso-8859-1" );
echo "<?xml version=\"1.0\" encoding=\"$charset\"?>" . "\n";

// Set return page
if ( $return_path != '') {
  $login_return_path = $SERVER_URL.$return_path;
} else {
  $login_return_path = $SERVER_URL;
}
?>
<!DOCTYPE html
    PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?php echo $lang; ?>" lang="<?php echo $lang; ?>">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $charset; ?>" />
<title><?php etranslate($APPLICATION_NAME)?></title>
<?php if ( ! $logout ) { ?>
<script type="text/javascript">
// error check login/password
function valid_form ( form ) {
  if ( form.login.value.length == 0 || form.password.value.length == 0 ) {
    alert ( '<?php etranslate("You must enter a login and password", true)?>.' );
    return false;
  }
  return true;
}
function myOnLoad() {
  <?php if ( ! empty ( $PLUGINS_ENABLED ) && ( $PLUGINS_ENABLED ) ){ ?>
   if (self != top)  {
     window.open("login.php","_top","");
     return;
   }
  <?php } ?>
  document.login_form.login.focus();
  <?php
    if ( ! empty ( $login ) ) echo "document.login_form.login.select();";
    if ( ! empty ( $error ) ) {
      echo "  alert ( \"$error\" );\n";
    }
  ?>
}
</script>
<?php 
}
 include "includes/styles.php";

 // Print custom header (since we do not call print_header function)
 if ( ! empty ( $CUSTOM_SCRIPT ) && $CUSTOM_SCRIPT == 'Y' ) {
   echo load_template ( $login, 'S' );
 }
?>
</head>
<body onload="myOnLoad();">
<?php
// Print custom header (since we do not call print_header function)
if ( ! empty ( $CUSTOM_HEADER ) && $CUSTOM_HEADER == 'Y' ) {
  echo load_template ( $login, 'H' );
}
?>

<h2><?php 
// If Application Name is set to Title then get translation
// If not, use the Admin defined Application Name
if ( ! empty ( $APPLICATION_NAME ) &&  $APPLICATION_NAME =="Title") {
  etranslate($APPLICATION_NAME);
} else {
  echo htmlspecialchars ( $APPLICATION_NAME );
}  
?></h2>

<?php
if ( ! empty ( $error ) ) {
  print "<span style=\"color:#FF0000; font-weight:bold;\">" . 
    translate("Error") . ": $error</span><br />\n";
} else {
  print "<br />\n";
}
?>
<form name="login_form" id="login" action="<?php echo $app_login_page['action'] ?>" method="post" 
  onsubmit="return valid_form(this)">
<input type="hidden" name="<?php echo $app_login_page['return'] ?>" 
  value="<?php echo $login_return_path ?>" />

<table cellpadding="10" align="center">
<tr><td rowspan="2">
 <img src="login.gif" alt="Login" /></td><td align="right">
 <label for="user"><?php etranslate("Username")?>:</label></td><td>
 <input name="<?php echo $app_login_page['username'] ?>" id="user" size="15" maxlength="25" 
   tabindex="1" />
</td></tr>
<tr><td style="text-align:right;">
 <label for="password"><?php etranslate("Password")?>:</label></td><td>
 <input name="<?php echo $app_login_page['password'] ?>" id="password" type="password" size="15" 
   maxlength="30" tabindex="2" />
</td></tr>
<?php if (! empty (  $app_login_page['remember'] ) ) { ?>
<tr><td colspan="3" style="font-size: 10px;">
 <input type="checkbox" name="<?php echo $app_login_page['remember'] ?>" id="remember" tabindex="3" 
   value="yes" <?php if ( ! empty ( $remember ) && $remember == "yes" ) {
     echo "checked=\"checked\""; }?> /><label for="remember">&nbsp;
   <?php etranslate("Save login via cookies so I don't have to login next time")?></label>
</td></tr>
<?php } ?>
<tr><td colspan="4" style="text-align:center;">
<?php 
  if (! empty (  $app_login_page['hidden'] ) ) { 
    foreach ( $app_login_page['hidden'] as $key => $val ) {
      echo "<input type=\"hidden\" name=\"$key\" value=\"$val\" />\n";
    }
  }
?>
 <input type="submit" name="<?php echo $app_login_page['submit'] ?>" value="<?php etranslate("Login")?>" tabindex="4" />
</td></tr>
</table>
</form>


<?php if ( ! empty ( $PUBLIC_ACCESS ) && $PUBLIC_ACCESS == "Y" ) { ?>
 <br /><br />
 <a class="nav" href="index.php">
   <?php etranslate("Access public calendar")?></a><br />
<?php } ?>

<?php
  $nulist = get_nonuser_cals ();
  for ( $i = 0; $i < count ( $nulist ); $i++ ) {
    if ( $nulist[$i]['cal_is_public'] == 'Y' ) {
      ?><a class="nav" href="nulogin.php?login=<?php
        echo $nulist[$i]['cal_login'] . "\">" .
          translate("Access") . ' ' . $nulist[$i]['cal_fullname'] . ' ' .
          translate("calendar");
      ?></a><br /><?php
    }
  }
?>

<?php if ( $DEMO_MODE == "Y" ) {
 // This is used on the sourceforge demo page
 echo "Demo login: user = \"demo\", password = \"demo\"<br />";
} ?>
<br /><br />

<span class="cookies"><?php etranslate("cookies-note")?></span><br />
<hr />
<br /><br />
<a href="<?php echo $PROGRAM_URL ?>" id="programname"><?php echo $PROGRAM_NAME?></a>

<?php // Print custom trailer (since we do not call print_trailer function)
if ( ! empty ( $CUSTOM_TRAILER ) && $CUSTOM_TRAILER == 'Y' ) {
  echo load_template ( $login, 'T' );
}
?>
</body>
</html>
