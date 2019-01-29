<?php
@session_start();
foreach ( $_SESSION as $key => $value ) {
  $dummy[$key] = $value; // Copy to a dummy array.
}
if ( ! empty ( $dummy ) ) {
  foreach ( $dummy as $key => $value ) {
    if ( substr ( $key, 0, 6 ) == 'webcal' )
      unset ( $_SESSION[$key] );
  }
}
// PHP 4.1.0 may have issues with the above code.
unset ( $_SESSION['webcal_login'] );
unset ( $_SESSION['webcalendar_session'] );

include_once 'includes/translate.php';
require_once 'includes/classes/WebCalendar.class';

$WebCalendar = new WebCalendar( __FILE__ );

include 'includes/config.php';
include 'includes/dbi4php.php';
include 'includes/formvars.php';
include 'includes/functions.php';

$WebCalendar->initializeFirstPhase();

include 'includes/' . $user_inc;
include_once 'includes/access.php';
include 'includes/gradient.php';

$WebCalendar->initializeSecondPhase();

load_global_settings();

// Set this true to show "no such user" or "invalid password" on login failures.
$showLoginFailureReason = false;

if ( ! empty ( $last_login ) )
  $login = '';

if ( empty ( $webcalendar_login ) )
  $webcalendar_login = '';

if ( $REMEMBER_LAST_LOGIN == 'Y' && empty ( $login ) )
  $last_login = $login = $webcalendar_login;

load_user_preferences ( 'guest' );

$WebCalendar->setLanguage();

// Look for action=logout.
$logout = false;
$action = getGetValue ( 'action' );
if ( ! empty ( $action ) && $action == 'logout' ) {
  $logout = true;
  $return_path = '';
  SetCookie ( 'webcalendar_login', '', 0 );
  SetCookie ( 'webcalendar_last_view', '', 0 );
} else
if ( empty ( $return_path ) ) {
  // See if a return path was set.
  $return_path = get_last_view ( false );
}

if ( ! empty ( $return_path ) )
  $url = $return_path = clean_whitespace ( $return_path );
else
  $url = 'index.php';

// If Application Name is set to "Title" then get translation.
// If not, use the Admin defined Application Name.
$appStr = generate_application_name();

$login = getPostValue ( 'login' );
$password = getPostValue ( 'password' );
$remember = getPostValue ( 'remember' );

// Calculate path for cookie.
if ( empty ( $PHP_SELF ) )
  $PHP_SELF = $_SERVER['PHP_SELF'];

$cookie_path = str_replace ( 'login.php', '', $PHP_SELF );

if ( $single_user == 'Y' || $use_http_auth )
  // No login for single-user mode or when using HTTP authorization.
  do_redirect ( 'index.php' );
else {
  if ( ! empty ( $login ) && ! empty ( $password ) && ! $logout ) {
    if ( get_magic_quotes_gpc() ) {
      $login = stripslashes ( $login );
      $password = stripslashes ( $password );
    }
    $login = trim ( $login );
    $badLoginStr = translate ( 'Illegal characters in login XXX.' );

    if ( $login != addslashes ( $login ) )
      die_miserable_death (
        str_replace ( 'XXX', htmlentities ( $login ), $badLoginStr ) );

    if ( user_valid_login ( $login, $password ) ) {
      user_load_variables ( $login, '' );

      $encoded_login = encode_string ( $login . '|' . crypt( $password ) );
      // If $remember, set login to expire in 365 days.
      $timeStr = ( ! empty ( $remember ) && $remember == 'yes'
        ? time() + 31536000 : 0 );
      SetCookie ( 'webcalendar_session', $encoded_login, $timeStr, $cookie_path );

      // The cookie "webcalendar_login" is provided as a convenience to other
      // apps that may wish to know what was the last calendar login,
      // so they can use week_ssi.php as a server-side include.
      // As such, it's not a security risk to have it un-encoded since it is not
      // used to allow logins within this app. It is used to load user
      // preferences on the login page (before anyone has logged in)
      // if $REMEMBER_LAST_LOGIN is set to "Y" (in admin.php).
      SetCookie ( 'webcalendar_login', $login, $timeStr, $cookie_path );

      if ( ! empty ( $GLOBALS['newUserUrl'] ) )
        $url = $GLOBALS['newUserUrl'];

      do_redirect ( $url );
    } else {
      // Invalid login.
      if ( empty ( $error ) || ! $showLoginFailureReason )
        $error = translate ( 'Invalid login', true );

      activity_log ( 0, 'system', '', LOG_LOGIN_FAILURE,
        str_replace (  ['XXX', 'YYY'],
           [$login, $_SERVER['REMOTE_ADDR']],
          translate ( 'Activity login failure' ) ) );
    }
  } else {
    // No login info... just present empty login page.
    // $error = "Start";
  }
  // Delete current user.
  SetCookie ( 'webcalendar_session', '', 0, $cookie_path );
  // In older versions, the cookie path had no trailing slash and NS 4.78
  // thinks "path/" and "path" are different, so the line above does not
  // delete the "old" cookie. This prohibits the login. So we also delete the
  // cookie with the trailing slash removed.
  if ( substr ( $cookie_path, -1 ) == '/' )
    SetCookie ( 'webcalendar_session', '', 0, substr ( $cookie_path, 0, -1 ) );
}
echo send_doctype ( $appStr ) . ( $logout ? '' : '
    <script>
    // Error check login/password.
      function valid_form ( form ) {
        if ( form.login.value.length == 0 || form.password.value.length == 0 ) {
          alert ( \''
   . translate ( 'You must enter a login and password.', true ) . '\' );
          return false;
        }
        return true;
      }
      function myOnLoad() {
        document.login_form.login.focus();' . ( empty ( $login ) ? '' : '
        document.login_form.login.select();' ) . ( empty ( $error ) ? '' : '
        alert ( \'' . $error . '\' );' ) . '
      }
    </script>' ) . '
    <link href="css_cacher.php?login=__public__" rel="stylesheet" />
    <link href="includes/css/styles.css" rel="stylesheet" />'

// Print custom header (since we do not call print_header function).
 . ( ! empty ( $CUSTOM_SCRIPT ) && $CUSTOM_SCRIPT == 'Y'
  ? load_template ( $login, 'S' ) : '' ) . '
  </head>
  <body id="login"' . ( $logout ? '' : ' onload="myOnLoad();"' ) . '>'

// Print custom header (since we do not call print_header function).
 . ( ! empty ( $CUSTOM_HEADER ) && $CUSTOM_HEADER == 'Y'
  ? load_template ( $login, 'H' ) : '' ) . '
    <h2>' . $appStr . '</h2>' . ( empty ( $error ) ? '' : '
    <span style="color:#f00; font-weight:bold;">'
   . str_replace ( 'XXX', $error, translate ( 'Error XXX' ) ) . '</span>' )
 . '<br />' . ( $logout ? '
    <p>' . translate ( 'You have been logged out.' ) . '</p><br /><br />
    <a class="nav" href="login.php' . ( empty ( $return_path )
    ? '' : '?return_path=' . htmlentities ( $return_path ) ) . '">'
   . translate ( 'Login' ) . '</a><br /><br /><br />' : '
    <form name="login_form" id="login" action="login.php" method="post" '
   . ' onsubmit="return valid_form( this )">' . ( empty ( $return_path ) ? '' : '
      <input type="hidden" name="return_path" value="'
     . htmlentities ( $return_path ) . '" />' ) . '
      <table class="aligncenter" id="logintable" cellspacing="10" cellpadding="10">
        <tr>
          <td rowspan="2"><img src="images/login.gif" alt="Login" /></td>
          <td class="alignright"><label for="user">' . translate ( 'Username' )
   . ':</label></td>
          <td><input name="login" id="user" size="15" maxlength="25" value="'
   . ( empty ( $last_login ) ? '' : $last_login ) . '" tabindex="1" /></td>
        </tr>
        <tr>
          <td class="alignright"><label for="password">'
   . translate ( 'Password' ) . ':</label></td>
          <td><input name="password" id="password" type="password" size="15" '
   . 'maxlength="30" tabindex="2" /></td>
        </tr>
        <tr>
          <td colspan="3" style="font-size:10px;">
            <input type="checkbox" name="remember" id="remember" tabindex="3" '
   . 'value="yes"' . ( ! empty ( $remember ) && $remember == 'yes'
    ? 'checked="checked"' : '' ) . ' />
            <label id="save-cookies" for="remember">&nbsp;'
   . translate ( 'Save login via cookies so I dont have to login next time.' )
   . '&nbsp;&nbsp;</label>
          </td>
        </tr>
        <tr>
          <td colspan="4" class="aligncenter"><input type="submit" value="'
   . translate ( 'Login' ) . '" tabindex="4" /></td>
        </tr>
      </table>
    </form>' ) . ( ! empty ( $PUBLIC_ACCESS ) && $PUBLIC_ACCESS == 'Y'
  ? '<br /><br />
    <a class="nav" href="index.php">' . translate ( 'Access public calendar' )
   . '</a><br />' : '' );

$nulist = get_nonuser_cals();
$accessStr = translate ( 'Access XXX calendar' );

for ( $i = 0, $cnt = count ( $nulist ); $i < $cnt; $i++ ) {
  if ( $nulist[$i]['cal_is_public'] == 'Y' )
    echo '
    <a class="nav" href="nulogin.php?login=' . $nulist[$i]['cal_login'] . '">'
     . str_replace ( 'XXX', $nulist[$i]['cal_fullname'], $accessStr )
     . '</a><br />';
}
echo ( $DEMO_MODE == 'Y'
  // This is used on the sourceforge demo page.
  ? '
    Demo login: user = "demo", password = "demo"<br />' : '' ) . '<br /><br />';

if ( ! empty ( $ALLOW_SELF_REGISTRATION ) && $ALLOW_SELF_REGISTRATION == 'Y' ) {
  // We can limit what domain is allowed to self register.
  // $self_registration_domain should have this format  "192.168.220.0:255.255.240.0";
  $valid_ip = validate_domain();

  if ( ! empty ( $valid_ip ) )
    echo '
    <b><a href="register.php">'
     . translate ( 'Not yet registered? Register here!' ) . '</a></b><br />';
}
echo '
     <span class="cookies">' . translate ( 'cookies-note' ) . '</span><br />
     <hr />
     <br />
     <a href="' . $PROGRAM_URL . '" target="_blank" id="programname">' . $PROGRAM_NAME . '</a> <br /> <br />'
// Print custom trailer (since we do not call print_trailer function).
 . ( ! empty ( $CUSTOM_TRAILER ) && $CUSTOM_TRAILER == 'Y'
  ? load_template ( $login, 'T' ) : '' ) . '
  </body>
</html>';

?>
