<?php // $Id$

foreach( array(
    'access',
    'config',
    'dbi4php',
    'formvars',
    'functions',
    'translate',
  ) as $i ) {
  include_once 'includes/' . $i . '.php';
}
require_once 'includes/classes/WebCalendar.class';

$WebCalendar = new WebCalendar( __FILE__ );
$WebCalendar->initializeFirstPhase();

include 'includes/' . $user_inc;
include 'includes/gradient.php';

$WebCalendar->initializeSecondPhase();

load_global_settings();

// TODO make this an option for external users.
$htmlmail = false;

load_user_preferences( 'guest' );

$WebCalendar->setLanguage();

require 'includes/classes/WebCalMailer.class';
$mail = new WebCalMailer;

$appStr  = generate_application_name();
$notauth = print_not_auth();

$error = ( empty( $ALLOW_SELF_REGISTRATION ) || $ALLOW_SELF_REGISTRATION != 'Y'
  ? $notauth : '' );

if( empty( $SELF_REGISTRATION_FULL ) || $SELF_REGISTRATION_FULL != 'Y' )
  $SELF_REGISTRATION_FULL = 'N';

$form_control = ( $SELF_REGISTRATION_FULL == 'Y' ? 'email' : 'full' );

/**
 * See if username and email are unique.
 *
 * param: $isWhat  string  What are we looking for; user login or user email?
 * param: $isWher  string  Where are we looking; "login" or "email"?
 *
 * Return true if all is OK.
 */
function checks( $isWhat, $isWher ) {
  global $control, $error;

  if( ! strlen( $isWhat ) ) {
    $error = ( $isWher == 'login'
      ? translate( 'no blank username' )
      : translate( 'no blank email' ) );
    return false;
  }
  $res = dbi_execute( 'SELECT cal_' . $isWher . ' FROM webcal_user
    WHERE cal_' . $isWher . ' = ?', array( $isWhat ) );

  if( $res ) {
    $row = dbi_fetch_row( $res );

    if( $row[0] == $isWhat ) {
      $control = '';
      $error = ( $isWher == 'login'
        ? translate( 'Username already exists.' )
        : translate( 'Email address already exists.' ) );
      return false;
    }
  }
  return true;
}

/**
 *  Generate unique password.
 */
function generate_password() {
  $pass = '';
  $pass_length = 8;
  $salt = 'abchefghjkmnpqrstuvwxyz0123456789';
  srand( ( double ) microtime() * 1000000 );
  $i = 0;
  while( $i < $pass_length ) {
    $pass .= substr( $salt, rand() % 33, 1 );
    $i++;
  }
  return $pass;
}

$uemail = $ufirstname = $ulastname = $upassword1 = $upassword2 = $user = '';

// We can limit what domain is allowed to self register.
// $self_registration_domain should have this format "192.168.220.0:255.255.240.0";
$valid_ip = validate_domain();

if( empty( $valid_ip ) )
  $error = $notauth;

// We could make $control a unique value if necessary.
$control = getPostValue( 'control' );
$illegalCharStr = translate( 'Illegal chars in login XXX' );

if( empty( $error ) && ! empty( $control ) ) {
  $uemail     = getPostValue( 'uemail' );
  $ufirstname = getPostValue( 'ufirstname' );
  $uis_admin  = 'N';
  $ulastname  = getPostValue( 'ulastname' );
  $user       = trim( getPostValue( 'user' ) );

  if( $user != addslashes( $user ) )
    $error = str_replace( 'XXX', htmlentities( $user ), $illegalCharStr );

  // Check to make sure user doesn't already exist.
  checks( $user, 'login' );

  // Check to make sure email address doesn't already exist.
  checks( $uemail, 'email' );
}

if( empty( $error ) && ! empty( $control ) ) {
  if( $control == 'full' ) {
    // Process full account addition.
    $upassword1 = getPostValue( 'upassword1' );
    $upassword2 = getPostValue( 'upassword2' );

    // Do some checking of user info.
    if( ! empty( $user ) && ! empty( $upassword1 ) ) {
      if( get_magic_quotes_gpc() ) {
        $upassword1 = stripslashes( $upassword1 );
        $user       = stripslashes( $user );
      }
      $user = trim( $user );

      if( $user != addslashes( $user ) )
        $error = str_replace( 'XXX', htmlentities( $user ), $illegalCharStr );
    } elseif( $upassword1 != $upassword2 ) {
      $control = '';
      $error = translate( 'passwords not identical' );
    }

    if( empty( $error ) ) {
      user_add_user( $user, $upassword1, $ufirstname, $ulastname,
        $uemail, $uis_admin );
      activity_log( 0, 'system', $user, LOG_NEWUSER_FULL,
        translate( 'New user via self-reg' ) );
    }
  } elseif( $control == 'email' ) {
    // Process account info for email submission.
    // Need to generate unique passwords and email them to the new user.
    $new_pass = generate_password();
    // TODO allow admin to approve account and emails prior to processing.
    user_add_user( $user, $new_pass, $ufirstname, $ulastname,
      $uemail, $uis_admin );

    $tempName = trim( $ufirstname . ' ' . $ulastname );
    $msg = str_replace( ', XXX.',
      ( strlen( $tempName ) ? ', ' . $tempName . '.' : '.' ),
      translate( 'Hello, XXX.' ) ) . "\n\n"
     . translate( 'you have a WebCal account' ) . "\n\n"
    . str_replace( 'XXX', $user, translate( 'Your username is XXX.' ) )
     . "\n\n"
    . str_replace( 'XXX', $new_pass, translate( 'Your password is XXX.' ) )
     . "\n\n"
    . str_replace( 'XXX', $appStr,
      translate( 'login to your account at XXX' ) ) . "\n";

    // Add URL to event, if we can figure it out.
    if( ! empty( $SERVER_URL ) ) {
      $url = $SERVER_URL . 'login.php';

      if( $htmlmail == 'Y' )
        $url = activate_urls( $url );

      $msg .= "\n\n" . $url;
    }
    $msg .= "\n\n"
     . translate( 'change pwd after 1st login' )
     . "\n\n" . translate( 'If email received in error' ) . "\n\n";
    $adminStr = translate( 'Administrator', true );
    $name = $appStr . ' ' . translate( 'Welcome' ) . ' ' . $ufirstname;
    // Send via WebCalMailer class.
    $mail->WC_Send( $adminStr, $uemail, $ufirstname . ' '
       . $ulastname, $name, $msg, $htmlmail, $EMAIL_FALLBACK_FROM );
    activity_log( 0, 'system', $user, LOG_NEWUSER_EMAIL,
      translate( 'New user via email.' ) );
  }
}

echo send_doctype( $appStr ) . '
    <link href="css_cacher.php?login=__public__" rel="stylesheet">
    <link href="includes/css/styles.css" rel="stylesheet">
    <!--[if IE 5]><script src="includes/js/ie5.js"></script><![endif]-->
    <script src="includes/js/base.js"></script>'

// Print custom header (since we do not call print_header function).
 . ( ! empty( $CUSTOM_SCRIPT ) && $CUSTOM_SCRIPT == 'Y'
  ? load_template( $login, 'S' ) : '' ) . '
  </head>
  <body id="register">
    <h2>' . $appStr . ' ' . translate( 'Registration' ) . '</h2>'
 . ( ! empty( $error )
  ? '
    <span class="error">' . $err_Str . $error . '</span><br>'
  : '<br><br>' . ( empty( $control ) ? '' : '
    <form action="login.php" method="post">
      <input type="hidden" name="login" value="' . $user . '">
      <table cellspacing="10" summary="">
        <tr>
          <td rowspan="3"><img src="images/register.gif"></td>
          <td>' . translate( 'Welcome to WebCal' ) . '</td>
        </tr>' . ( $SELF_REGISTRATION_FULL == 'Y' ? '
        <tr>
          <td colspan="3" align="center"><label>'
       . translate( 'should get email soon' ) . '</label></td>
        </tr>' : '' ) . '
        <tr>
          <td colspan="3" align="center"><input type="submit" value="'
     . translate( 'Return to Login screen' ) . '"></td>
        </tr>
      </table>
    </form>' ) . '
    <form action="register.php" method="post" id="selfreg" name="selfreg">
      <input type="hidden" name="control" value="' . $form_control . '">
      <table cellspacing="10" summary="">
        <tr>
          <td rowspan="3"><img src="images/register.gif" alt=""></td>
          <td align="right"><label>' . translate( 'Username' ) . '</label></td>
          <td><input type="text" id="user" name="user" size="20" maxlength="20" '
   . 'value="' . $user . '"></td>
        </tr>
        <tr>
          <td align="right"><label>' . translate( 'First Name' ) . '</label></td>
          <td><input type="text" name="ufirstname" size="25" maxlength="25" value="'
   . $ufirstname . '"></td>
        </tr>
        <tr>
          <td align="right"><label>' . translate( 'Last Name' ) . '</label></td>
          <td><input type="text" name="ulastname" size="25" maxlength="25" value="'
   . $ulastname . '"></td>
        </tr>
        <tr>
          <td align="right" colspan="2"><label>' . translate( 'E-mail address' )
   . '</label></td>
          <td><input type="text" id="uemail" name="uemail" size="40" maxlength="75" '
   . 'value="' . $uemail . '"></td>
        </tr>
        <tr>
          <td colspan="' . ( $SELF_REGISTRATION_FULL != 'Y'
    ? '2" align="right"><label>' . translate( 'Password' ) . '</label></td>
          <td><input type="password" name="upassword1" size="15" value="'
     . $upassword1 . '"></td>
        </tr>
        <tr>
          <td colspan="2" align="right"><label>'
           . translate( 'Password (again)' ) . '</label></td>
          <td><input type="password" name="upassword2" size="15" value="'
     . $upassword2 . '">'
    : '3" align="center"><label>'
     . translate( 'you get info by email' ) . '</label>' ) . '</td>
        </tr>
        <tr>
          <td colspan="3" align="center"><input type="submit" value="'
   . translate( 'Submit' ) . '"></td>
        </tr>
      </table>
    </form>' ) . '
    <span class="cookies">' . translate( 'cookies-note' ) . '</span><br>
    <hr>
    <br><br>
    <a href="' . $PROGRAM_URL . '" id="programname">' . $PROGRAM_NAME . '</a>';
// Print custom trailer (since we do not call print_trailer function).
if( ! empty( $CUSTOM_TRAILER ) && $CUSTOM_TRAILER == 'Y' ) {
  $res = dbi_execute( 'SELECT cal_template_text FROM webcal_report_template
    WHERE cal_template_type = \'T\' and cal_report_id = 0' );
  if( $res ) {
    if( $row = dbi_fetch_row( $res ) )
      echo $row[0];

    dbi_free_result( $res );
  }
}

?>
  </body>
</html>
