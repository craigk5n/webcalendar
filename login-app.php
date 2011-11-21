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
load_user_preferences ( 'guest' );

$WebCalendar->setLanguage();

// Look for action=logout.
$action = getGetValue ( 'action' );
$logout = false;

if ( ! empty ( $action ) && $action == 'logout' ) {
  $logout = true;
  $return_path = '';
  setcookie( 'webcalendar_last_view', '', 0 );
  setcookie( 'webcalendar_login', '', 0 );
} else
if ( empty ( $return_path ) ) {
  // See if a return path was set.
  $return_path = get_last_view();
  if ( ! empty ( $return_path ) )
    setcookie( 'webcalendar_last_view', '', 0 );
}

$appStr = generate_application_name();

// Set return page.
$login_return_path = $SERVER_URL . $return_path;

ob_start();
setcookie( 'err', $error, 180 );
setcookie( 'login', $login, 180 );
echo send_doctype( $appStr ) . '
    <link href="css_cacher.php?login=__public__" rel="stylesheet">
    <link href="includes/css/styles.css" rel="stylesheet">'. ( $logout ? '' : '
    <script src="includes/js/base.js"></script>
    <script src="includes/js/login.js"></script>' )

// Print custom header (since we do not call print_header function).
 . ( ! empty ( $CUSTOM_SCRIPT ) && $CUSTOM_SCRIPT == 'Y'
  ? load_template ( $login, 'S' ) : '' ) . '
  </head>
  <body id="loginapp">'
// Print custom header (since we do not call print_header function).
 . ( ! empty ( $CUSTOM_HEADER ) && $CUSTOM_HEADER == 'Y'
  ? load_template ( $login, 'H' ) : '' ) . '
    <h2>' . $appStr . '</h2>' . ( empty ( $error ) ? '' : '
    <span class="error">'
   . str_replace( 'XXX', $error, translate( 'Error XXX' ) ) . '</span>' ) . '<br>
    <form action="' . $app_login_page['action']
 . '" method="post" id="login_form" name="login">
      <input type="hidden" name="' . $app_login_page['return'] . '" value="'
 . $login_return_path . '">
      <table cellpadding="10" align="center" summary="">
        <tr>
          <td rowspan="2"><img src="images/login.gif" alt="Login"></td>
          <td align="right"><label for="user">' . translate ( 'Username' )
 . '</label></td>
          <td><input type="text" name="' . $app_login_page['username']
 . '" id="user" size="15" maxlength="25" tabindex="1"></td>
        </tr>
        <tr>
          <td class"alignright"><label for="password">'
 . translate ( 'Password' ) . '</label></td>
          <td><input type="password" name="' . $app_login_page['password']
 . '" id="password" size="15" maxlength="30" tabindex="2"></td>
        </tr>' . ( empty( $app_login_page['remember'] ) ? '' : '
        <tr>
          <td colspan="3" style="font-size: 10px;">
            <input type="checkbox" name="' . $app_login_page['remember']
   . '" id="remember" tabindex="3" value="yes" '
   . ( ! empty ( $remember ) && $remember == 'yes' ? 'checked>' : '>' ) . '
            <label for="remember">&nbsp;'
   . translate ( 'Save login via cookies' ) . '</label>
          </td>
        </tr>' ) . '
        <tr>
          <td colspan="4" class="aligncenter">';

if ( ! empty ( $app_login_page['hidden'] ) ) {
  foreach ( $app_login_page['hidden'] as $k => $v ) {
    echo '
            <input type="hidden" name="' . $k . '" value="' . $v . '">';
  }
}

echo '
            <input type="submit" name="' . $app_login_page['submit']
 . '" value="' . translate( 'Login' ) . '" tabindex="4">
          </td>
        </tr>
      </table>
    </form>' . ( ! empty ( $PUBLIC_ACCESS ) && $PUBLIC_ACCESS == 'Y'
  ? '<br><br>
    <a class="nav" href="index.php">' . str_replace ( 'XXX',
    translate ( 'public' ), translate ( 'Access XXX calendar' ) )
   . '</a><br>' : '' );

$nulist = get_nonuser_cals();
for ( $i = 0, $cnt = count ( $nulist ); $i < $cnt; $i++ ) {
  if ( $nulist[$i]['cal_is_public'] == 'Y' )
    echo '
    <a class="nav" href="nulogin.php?login=' . $nulist[$i]['cal_login'] . '">'
     . str_replace ( 'XXX', $nulist[$i]['cal_fullname'],
      translate( 'Access XXX calendar' ) ) . '</a><br>';
}

echo ( $DEMO_MODE == 'Y'
  // This is used on the SourceForge demo page.
  ? 'Demo login: user = "demo", password = "demo"<br>' : '' ) . '<br><br>
    <span class="cookies">' . translate( 'cookies-note' ) . '</span><br>
    <hr>
    <br><br>
    <a href="' . $PROGRAM_URL . '" id="programname">' . $PROGRAM_NAME . '</a>'

// Print custom trailer (since we do not call print_trailer function).
 . ( ! empty ( $CUSTOM_TRAILER ) && $CUSTOM_TRAILER == 'Y'
  ? load_template( $login, 'T' ) : '' ) . '
  </body>
</html>';

ob_end_flush();

?>
