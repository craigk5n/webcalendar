<?php
/* $Id$ */
require_once 'includes/classes/WebCalendar.class.php';

$WC =& new WebCalendar ( __FILE__ );

include 'includes/translate.php';
include 'includes/config.php';
include 'includes/dbi4php.php';
include 'includes/functions.php';

$WC->initializeFirstPhase();
 
include_once 'includes/access.php';
include 'includes/gradient.php';

$WC->initializeSecondPhase();


require ( 'includes/classes/WebCalMailer.class.php' );
$mail = new WebCalMailer;
//TODO make this an option for external users
$htmlmail = false;

$WC->setLanguage();

$appStr =  generate_application_name ();

$notauth = print_not_auth ();

if ( ! getPref ( '_ALLOW_SELF_REGISTRATION' ) ) { 
  $error = $notauth;
}
$self_registration_full = getPref ( '_SELF_REGISTRATION_FULL', 2 );
$form_control = ( $self_registration_full ? 'email' : 'full');

//See if new username is unique
//return true if all is ok
function check_username ( $user ) {
  global $control, $error;

  if ( strlen ( $user ) == 0 ) {
   $error = translate ( 'Username can not be blank' );
  return false;
 } 
  $sql='SELECT cal_login FROM webcal_user WHERE cal_login = ?';
  $res = dbi_execute ( $sql, array ( $user ) );
  if ( $res ) {
    $row = dbi_fetch_row ( $res );
    if ( $row[0] == $user ) {
      $error = translate ( 'Username already exists' );
      $control = '';
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
   $error = translate ( 'Email address can not be blank' );
  return false;
 } 
  $sql='SELECT cal_email FROM webcal_user WHERE cal_email = ?';
  $res = dbi_execute ( $sql, array ( $uemail ) );
  if ( $res ) {
    $row = dbi_fetch_row ( $res );
    if ( $row[0] == $uemail ) {
      $error = translate ( 'Email address already exists' );
      $control = '';
   return false;
    }
  }
 return true;
}

//Generate unique password
function generate_password() {
  $pass_length = 7;
  $pass= '';
  $salt = 'abchefghjkmnpqrstuvwxyz0123456789';
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

$user = '';
$upassword1 = '';
$upassword2 = '';
$ufirstname = '';
$ulastname = '';
$uemail = '';

// We can limit what domain is allowed to self register
// $self_registration_domain should have this format  "192.168.220.0:255.255.240.0";
$valid_ip = validate_domain ();
if ( empty ( $valid_ip ) ) 
  $error = $notauth;

//We could make $control a unique value if necessary
$control = $WC->getPOST ( 'control' );
if ( empty ( $error ) && ! empty ( $control ) ) {
  $user = $WC->getPOST ( 'user' );
  $ufirstname = $WC->getPOST ( 'ufirstname' );
  $ulastname = $WC->getPOST ( 'ulastname' );
  $uemail = $WC->getPOST ( 'uemail' );
  $user = trim ( $user );
  $uis_admin = 'N';
  if ( $user != addslashes ( $user ) ) {
    $error = translate ( 'Illegal characters in login' ).
     '<tt>' . htmlentities ( $user ) . '</tt>';
  }
    
  //Check to make sure user doesn't already exist
  check_username ( $user );
  //Check to make sure email address doesn't already exist
  check_email ( $uemail );
}
//Process full account addition
if ( empty ( $error ) && ! empty ( $control ) && $control == 'full' ) {
  $upassword1 = $WC->getPOST ( 'upassword1' );
  $upassword2 = $WC->getPOST ( 'upassword2' );
  // Do some checking of user info
 if ( ! empty ( $user ) && ! empty ( $upassword1 ) ) {
    if ( get_magic_quotes_gpc() ) {
      $upassword1 = stripslashes ( $upassword1 );
      $user = stripslashes ( $user );
    }
    $user = trim ( $user );
    if ( $user != addslashes ( $user ) ) {
      $error = translate ( 'Illegal characters in login' ) .
        '<tt>' . htmlentities ( $user ) . '</tt>';
    }
  } else if ( $upassword1 != $upassword2 ) { 
    $error = translate( 'The passwords were not identical' ) . '.';
   $control = ''; 
 }

 if ( empty ( $error ) ) {
	 $params = array ( 'cal_login'=>$user,
		 'cal_passwd'=>$upassword1,
		 'cal_lastname'=>$ulastname,
		 'cal_firstname'=>$ufirstname,
		 'cal_email'=>$uemail,
		 'cal_is_admin'=>$uis_admin );
	 $newID = $WC->User->addUser ( $params );
  activity_log ( 0, 'system', $user, LOG_NEWUSER_FULL, $newID .' New user via self-registration' );
 }
//Process account info for email submission
} else if ( empty ( $error ) && ! empty ( $control ) && $control == 'email' ) {  
  // need to generate unique passwords and email them to the new user 
  if ( empty ( $error ) ) {
    $new_pass = generate_password ();
    //TODO allow admin to approve account aand emails prior to processing
	  $params = array ( 'cal_login'=>$user,
		  'cal_passwd'=>$new_pass,
	 	  'cal_lastname'=>$ulastname,
		  'cal_firstname'=>$ufirstname,
		  'cal_email'=>$uemail,
		  'cal_is_admin'=>$uis_admin );
	  $newID = $WC->User->addUser ( $params );
   
   $msg = translate( 'Hello' ) . ', ' . $ufirstname . ' ' . $ulastname . "\n\n";
   $msg .= translate( 'A new WebCalendar account has been set up for you' ). ".\n\n";
   $msg .= translate( 'Your username is' ) . ' "' . $user . "\"\n\n";
   $msg .= translate( 'Your password is' ) . ' "' . $new_pass . "\"\n\n";
   $msg .= translate( 'Please visit' ) . ' ' . $appStr . ' ' .
     translate( 'to log in and start using your account' ) . "!\n";
   // add URL to event, if we can figure it out
   if ( $server_url = getPref ( 'SERVER_URL', 2 ) ) {
     $url = $server_url .  'login.php';
     if ( $htmlmail == 'Y' ) {
       $url =  activate_urls ( $url ); 
     }
     $msg .= "\n\n" . $url;
   }
  $msg .= "\n\n" . translate( 'You may change your password after logging in the first time' ) . ".\n\n";
  $msg .= translate( 'If you received this email in error' ) . ".\n\n"; 
  $adminStr = translate( 'Administrator', true );
  $name = $appStr . ' ' . translate( 'Welcome' ) . ': ' . $ufirstname;
  //send  via WebCalMailer class
  $mail->WC_Send ( $adminStr, $uemail, $ufirstname .  ' ' 
    . $ulastname, $name, $msg, $htmlmail, getPref ( '_EMAIL_FALLBACK_FROM', 2 ) );
  activity_log ( 0, 'system', $user, LOG_NEWUSER_EMAIL, $newID .' New user via email' ); 
 }
}

echo send_doctype ( $appStr );
?>
<script type="text/javascript" src="includes/js/prototype.js"></script>
<script type="text/javascript">
var validform = false;
var formfield = 'user';
function valid_form () {
  if ( document.selfreg.upassword1.value.length == 0 ) {
    alert ( "<?php etranslate( 'You have not entered a password', true)?>." );
    return false;
  }
  if ( document.selfreg.user.value.length == 0 ) {
    alert ( "<?php etranslate( 'Username can not be blank', true)?>." );
    return false;
  }
  if ( document.selfreg.upassword1.value != document.selfreg.upassword2.value ) {
    alert ( "<?php etranslate( 'The passwords were not identical', true)?>." );
    return false;
  } 
  check_name();
  check_uemail();

  return validform;
}

function check_name() {
  formfield = 'user';
  var url = 'ajax.php';
  var params = 'page=register&name=' + $F('user');
  var ajax = new Ajax.Request(url,
    {method: 'post', 
    parameters: params, 
    onComplete: showResponse});
}

function check_uemail() {
  formfield = 'uemail';
  var url = 'ajax.php';
  var params = 'page=email&name=' + $F('uemail');
  var ajax = new Ajax.Request(url,
    {method: 'post', 
    parameters: params, 
    onComplete: showResponse});
}

function showResponse(originalRequest) {
  if (originalRequest.responseText) {
    text = originalRequest.responseText;
    //this causes javascript errors in Firefox, but these can be ignored
    alert (text);
    if (   formfield == 'user' )
      document.selfreg.user.focus();
    if (   formfield == 'uemail' )
      document.selfreg.uemail.focus();
    validform =  false;
  } else {
    validform =  true;
  }
}

</script>
<?php 
  echo '<link rel="stylesheet" type="text/css" href="includes/styles.css" />';

 // Print custom header (since we do not call print_header function)
 if ( getPref ( 'CUSTOM_SCRIPT' ) ) {
   echo load_template ( $WC->loginId(), 'S' );
 }
  //load dynamic CSS
 ob_start ();
 include 'includes/styles.php';
 $ret = ob_get_contents ();
 ob_end_clean ();
 echo $ret;
?>
</head>
<body id="register">
<h2><?php  echo $appStr . " " . translate ( 'Registration' ); ?></h2>

<?php
if ( ! empty ( $error ) ) {
  echo '<span style="color:#FF0000; font-weight:bold;">' . 
    translate( 'Error' ) . ": $error</span><br />\n";
} else {
  echo "<br /><br />\n";
}
if ( ! empty ($control ) && empty ( $error ) ) { ?>
<form action="login.php" method="post"  >
<input  type="hidden" name="login" value="<?php echo $user ?>" />
<table align="center" cellspacing="10" cellpadding="10">
<tr><td rowspan="3"><img src="images/register.gif"></td>

<td><?php etranslate( 'Welcome to WebCalendar' )?></td></tr>
<?php if ( $self_registration_full ) { ?>
  <tr><td colspan="3" align="center"><label><?php 
  etranslate( 'Your email should arrive shortly' )?></label><td></tr> 
<?php } ?>
<tr><td colspan="3" align="center">
  <input type="submit" value="<?php etranslate( 'Return to Login screen' )?>" />
</td></tr>
</table>
</form>
<?php } else if ( empty ( $error ) ) { ?>
<form action="register.php" method="post" onSubmit="return valid_form()" name="selfreg">
<input  type="hidden" name="control" value="<?php echo $form_control ?>" />
<table align="center"  cellpadding="10" cellspacing="10">
<tr><td rowspan="3"><img src="images/register.gif" alt="" /></td>
<td  align="right">
  <label><?php etranslate( 'Username' )?>:</label></td>
  <td align="left"><input  type="text" name="user"  id="user" value="<?php echo $user ?>" size="20" maxlength="20" onChange="check_name();" /></td></tr>
<tr><td  align="right">
  <label><?php etranslate( 'First Name' )?>:</label></td>
  <td align="left"><input type="text" name="ufirstname" value="<?php echo $ufirstname ?>" size="25" maxlength="25" /></td></tr>
<tr><td  align="right">
  <label><?php etranslate( 'Last Name' )?>:</label></td>
  <td align="left"><input type="text" name="ulastname" value="<?php echo $ulastname ?>" size="25"  maxlength="25" /></td></tr>
<tr><td  align="right" colspan="2">
  <label><?php etranslate( 'E-mail address' )?>:</label></td>
  <td align="left"><input type="text" name="uemail" id="uemail" value="<?php echo $uemail ?>" size="40"  maxlength="75" onChange="check_uemail();" /></td></tr>
<?php if ( ! $self_registration_full ) { ?>
  <tr><td  align="right" colspan="2">
    <label><?php etranslate( 'Password' )?>:</label></td>
    <td align="left"><input name="upassword1" value="<?php echo $upassword1 ?>" size="15"  type="password" /></td></tr>
  <tr><td  align="right" colspan="2">
    <label><?php etranslate( 'Password' )?> (<?php etranslate( 'again' )?>):</label></td>
    <td align="left"><input name="upassword2" value="<?php echo $upassword2 ?>" size="15"  type="password" /></td></tr>
<?php } else { ?>  
  <tr><td colspan="3" align="center"><label><?php 
 etranslate ( 'Your account information will be emailed to you' ); ?></label></td></tr>
<?php } ?>
<tr><td colspan="3" align="center">
  <input type="submit" value="<?php etranslate( 'Submit' )?>" />
</td></tr>
</table>

</form>

<?php } ?>
<br /><br /><br /><br /><br /><br /><br /><br />
<span class="cookies"><?php etranslate( 'cookies-note' )?></span><br />
<hr />
<br /><br />
<a href="<?php echo PROGRAM_URL ?>" id="programname"><?php echo PROGRAM_NAME?></a>
<?php // Print custom trailer (since we do not call print_trailer function)
if ( getPref ( 'CUSTOM_TRAILER' ) ) {
  $res = dbi_execute (
    'SELECT cal_template_text FROM webcal_report_template ' .
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
