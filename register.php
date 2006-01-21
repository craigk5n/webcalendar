<?php
require_once 'includes/classes/WebCalendar.class';

$WebCalendar =& new WebCalendar ( __FILE__ );

include 'includes/assert.php';
include 'includes/config.php';
include 'includes/php-dbi.php';
include 'includes/functions.php';

$WebCalendar->initializeFirstPhase();

include "includes/$user_inc";
include 'includes/translate.php';

$WebCalendar->initializeSecondPhase();
$WebCalendar->setLanguage();

load_global_settings ();

require ( 'includes/classes/WebCalMailer.class' );
$mail = new WebCalMailer;
//TODO make this an option for external users
$htmlmail = false;

load_user_preferences ( );


if ( empty ( $ALLOW_SELF_REGISTRATION ) || $ALLOW_SELF_REGISTRATION != "Y" ) { 
  $error = "You are not authorized";
}

if ( empty ( $SELF_REGISTRATION_FULL ) || $SELF_REGISTRATION_FULL == "N" ) { 
  $SELF_REGISTRATION_FULL = "N";
 $form_control = "email";
} else if ( $SELF_REGISTRATION_FULL = "Y" ) {
 $form_control = "full";
}

//See if new username is unique
//return true if all is ok
function check_username ( $user ) {
  global $control, $error;
  if ( ! strlen ( $user ) ) {
   $errror = translate ( "Username can not be blank" );
  return false;
 } 
  $sql="SELECT cal_login FROM webcal_user WHERE cal_login = ?";
  $res = dbi_execute ( $sql , array ( $user ) );
  if ( $res ) {
    $row = dbi_fetch_row ( $res );
    if ( $row[0] == $user ) {
      $error = translate ( "Username already exists" );
      $control = "";
   return false;
    }
  }
 return true;
}

//See if  email is unique
//return true if all is ok
function check_email ( $uemail ) {
  global $control, $error;
  if ( ! strlen ( $uemail ) ) {
   $errror = translate ( "Email address can not be blank" );
  return false;
 } 
  $sql="SELECT cal_email FROM webcal_user WHERE cal_email = ?";
  $res = dbi_execute ( $sql , array ( $uemail ) );
  if ( $res ) {
    $row = dbi_fetch_row ( $res );
    if ( $row[0] == $uemail ) {
      $error = translate ( "Email address already exists" );
      $control = "";
   return false;
    }
  }
 return true;
}

//Generate unique password
function generate_password() {
  $pass_length = 7;
  $pass= '';
  $salt = "abchefghjkmnpqrstuvwxyz0123456789";
  srand((double)microtime()*1000000); 
   $i = 0;
   while ($i <= $pass_length) {
      $num = rand() % 33;
      $tmp = substr($salt, $num, 1);
      $pass = $pass . $tmp;
      $i++;
   }
   return $pass;
}

$user = "";
$upassword1 = "";
$upassword2 = "";
$ufirstname = "";
$ulastname = "";
$uemail = "";

// We can limit what domain is allowed to self register
// $self_registration_domain should have this format  "192.168.220.0:255.255.240.0";
if ( ! empty ( $SELF_REGISTRATION_BLACKLIST ) && $SELF_REGISTRATION_BLACKLIST == "Y"  ) {
  $valid_ip = validate_domain ();
  if ( empty ( $valid_ip ) ) 
    $error = "You are not authorized";
}
//We could make $control a unique value if necessary
$control = getPostValue ( "control" );
//Process full account addition
if ( empty ( $error ) && ! empty ( $control ) && $control == "full" ) {
  $user = getPostValue ( "user" );
  $upassword1 = getPostValue ( "upassword1" );
  $upassword2 = getPostValue ( "upassword2" );
  $ufirstname = getPostValue ( "ufirstname" );
  $ulastname = getPostValue ( "ulastname" );
  $uemail = getPostValue ( "uemail" );
  $uis_admin = "N";
  // Do some checking of user info
 if ( ! empty ( $user ) && ! empty ( $upassword1 ) ) {
    if ( get_magic_quotes_gpc() ) {
      $upassword1 = stripslashes ( $upassword1 );
      $user = stripslashes ( $user );
    }
    $user = trim ( $user );
    if ( $user != addslashes ( $user ) ) {
      $error = translate ( "Illegal characters in login" ) .
        "<tt>" . htmlentities ( $user ) . "</tt>";
    }
  } else if ( $upassword1 != $upassword2 ) { 
    $error = translate( "The passwords were not identical" ) . ".";
   $control = ""; 
  } else {
   //Check to make sure user doesn't already exist
   check_username ( $user );
 }

 if ( empty ( $error ) ) {
   user_add_user ( $user, $upassword1, $ufirstname, $ulastname,
     $uemail, $uis_admin );
  activity_log ( 0, 'admin', $user, LOG_NEWUSER_FULL, "New user via self-registration" );
 }
//Process account info for email submission
} else if ( empty ( $error ) && ! empty ( $control ) && $control == "email" ) { 
  $user = getPostValue ( "user" );
  $ufirstname = getPostValue ( "ufirstname" );
  $ulastname = getPostValue ( "ulastname" );
  $uemail = getPostValue ( "uemail" );
  $user = trim ( $user );
  $uis_admin = "N";
  if ( $user != addslashes ( $user ) ) {
    $error = translate ( "Illegal characters in login" ).
     "<tt>" . htmlentities ( $user ) . "</tt>";
  }
    
  //Check to make sure user doesn't already exist
  check_username ( $user );
  //Check to make sure email address doesn't already exist
  check_email ( $uemail );
  
  // need to generate unique passwords and email them to the new user 
  if ( empty ( $error ) ) {
    $new_pass = generate_password ();
    //TODO allow admin to approve account aand emails prior to processing
    user_add_user ( $user, $new_pass, $ufirstname, $ulastname,
      $uemail, $uis_admin );
   
   $msg = translate("Hello") . ", " . $ufirstname . " " . $ulastname . "\n\n";
   $msg .= translate("A new WebCalendar account has been set up for you"). ".\n\n";
   $msg .= translate("Your username is") . " \"" . $user . "\"\n\n";
   $msg .= translate("Your password is") . " \"" . $new_pass . "\"\n\n";
   $msg .= translate("Please visit") . " " . translate($APPLICATION_NAME) . " " .
     translate("to log in and start using your account") . "!\n";
   // add URL to event, if we can figure it out
   if ( ! empty ( $SERVER_URL ) ) {
     $url = $SERVER_URL .  "login.php";
     if ( $htmlmail == 'Y' ) {
       $url =  activate_urls ( $url ); 
     }
     $msg .= "\n\n" . $url;
   }
  $msg .= "\n\n" . translate("You may change your password after logging in the first time") . ".\n\n";
  $msg .= translate("If you received this email in error" ) . ".\n\n"; 
  
  if ( ! empty ( $EMAIL_FALLBACK_FROM ) ) {
    $mail->From = $EMAIL_FALLBACK_FROM;
    $mail->FromName = translate("Administrator");
  } else {
    $mail->From = translate("Administrator");
  }
  $mail->IsHTML( $htmlmail == 'Y' ? true : false );
  $mail->AddAddress( $uemail, $ufirstname .  " " . $ulastname );
  $mail->Subject = translate($APPLICATION_NAME) . " " .
    translate("Welcome") . ": " . $ufirstname;
  $mail->Body  = $htmlmail == 'Y' ? nl2br ( $msg ) : $msg;
  $mail->Send();
  $mail->ClearAll();

  activity_log ( 0, 'admin', $user, LOG_NEWUSER_EMAIL, "New user via email" ); 
 }
}

$charset = ( ! empty ( $LANGUAGE )?translate("charset"): "iso-8859-1" );
echo "<?xml version=\"1.0\" encoding=\"$charset\"?>" . "\n";
?>
<!DOCTYPE html
    PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?php echo $lang; ?>" lang="<?php echo $lang; ?>">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $charset; ?>" />
<title><?php etranslate($APPLICATION_NAME)?></title>

<script type="text/javascript">
// error check login/password
function valid_form () {
  if ( document.selfreg.user.value.length == 0 || document.selfreg.upassword1.value.length == 0 || document.selfreg.upassword2.value.length == 0) {
    alert ( "<?php etranslate("You must enter a login and password", true)?>." );
    return false;
  }
  if ( document.selfreg.upassword1.value != document.selfreg.upassword2.value ) {
    alert ( "<?php etranslate("Your passwords do not match", true)?>." );
    return false;
  }
 
  return true;
}
</script>
<?php 
 include "includes/styles.php";

 // Print custom header (since we do not call print_header function)
 if ( ! empty ( $CUSTOM_SCRIPT ) && $CUSTOM_SCRIPT == 'Y' ) {
   echo load_template ( $login, 'S' );
 }
?>
</head>
<body id="register">
<h2><?php 
// If Application Name is set to Title then get translation
// If not, use the Admin defined Application Name
if ( ! empty ( $APPLICATION_NAME ) &&  $APPLICATION_NAME =="Title") {
  etranslate($APPLICATION_NAME);
} else {
  echo htmlspecialchars ( $APPLICATION_NAME );
} 
echo " " . translate ( "Registration" ); 
?></h2>

<?php
if ( ! empty ( $error ) ) {
  print "<span style=\"color:#FF0000; font-weight:bold;\">" . 
    translate("Error") . ": $error</span><br />\n";
} else {
  print "<br /><br />\n";
}
?>
<?php if ( ! empty ($control ) && empty ( $error ) ) { ?>
<form action="login.php" method="post" >
<input  type="hidden" name="login" value="<?php echo $user ?>" />
<table align="center"  cellpadding="0" cellspacing="10">
<tr><td rowspan="3"><img src="register.gif"></td>

<td><?php etranslate("Welcome to WebCalendar")?></td></tr>
<?php if ( $SELF_REGISTRATION_FULL == "N" ) { ?>
  <tr><td colspan="3" align="center"><label><?php etranslate("Your email should arrive shortly")?></label><td></tr> 
<?php } ?>
<tr><td colspan="3" align="center">
  <input type="submit" value="<?php etranslate("Return to Login screen")?>" />
</td></tr>
</table>
</form>
<?php } else if ( empty ( $error ) ) { ?>
<form action="register.php" method="post" onsubmit="return valid_form()" name="selfreg">
<input  type="hidden" name="control" value="<?php echo $form_control ?>" />
<table align="center"  cellpadding="0" cellspacing="10">
<tr><td rowspan="3"><img src="register.gif" alt="" /></td>
<td  align="right">
  <label><?php etranslate("Username")?>:</label></td>
  <td align="left"><input  type="text" name="user"  value="<?php echo $user ?>" size="20" maxlength="20" /></td></tr>
<tr><td  align="right">
  <label><?php etranslate("First Name")?>:</label></td>
  <td align="left"><input type="text" name="ufirstname" value="<?php echo $ufirstname ?>" size="25" maxlength="25" /></td></tr>
<tr><td  align="right">
  <label><?php etranslate("Last Name")?>:</label></td>
  <td align="left"><input type="text" name="ulastname" value="<?php echo $ulastname ?>" size="25"  maxlength="25" /></td></tr>
<tr><td  align="right" colspan="2">
  <label><?php etranslate("E-mail address")?>:</label></td>
  <td align="left"><input type="text" name="uemail" value="<?php echo $uemail ?>" size="40"  maxlength="75" /></td></tr>
<?php if ( $SELF_REGISTRATION_FULL == "Y" ) { ?>
  <tr><td  align="right" colspan="2">
    <label><?php etranslate("Password")?>:</label></td>
    <td align="left"><input name="upassword1" value="<?php echo $upassword1 ?>" size="15"  type="password" /></td></tr>
  <tr><td  align="right" colspan="2">
    <label><?php etranslate("Password")?> (<?php etranslate("again")?>):</label></td>
    <td align="left"><input name="upassword2" value="<?php echo $upassword2 ?>" size="15"  type="password" /></td></tr>
<?php } else { ?>  
  <tr><td colspan="3" align="center"><label><?php etranslate ( "Your account information will be emailed to you" ); ?></label></td></tr>
<?php } ?>
<tr><td colspan="3" align="center">
  <input type="submit" value="<?php etranslate("Submit")?>" />
</td></tr>
</table>

</form>

<?php } ?>
<br /><br /><br /><br /><br /><br /><br /><br />
<span class="cookies"><?php etranslate("cookies-note")?></span><br />
<hr />
<br /><br />
<a href="<?php echo $PROGRAM_URL ?>" id="programname"><?php echo $PROGRAM_NAME?></a>
<?php // Print custom trailer (since we do not call print_trailer function)
if ( ! empty ( $CUSTOM_TRAILER ) && $CUSTOM_TRAILER == 'Y' ) {
  $res = dbi_execute (
    "SELECT cal_template_text FROM webcal_report_template " .
    "WHERE cal_template_type = 'T' and cal_report_id = 0" );
  if ( $res ) {
    if ( $row = dbi_fetch_row ( $res ) ) {
      echo $row[0];
    }
    dbi_free_result ( $res );
  }
} ?>
</body>
</html>
