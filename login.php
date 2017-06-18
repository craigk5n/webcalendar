<?php
/* $Id: login.php,v 1.111.2.10 2013/01/24 21:10:19 cknudsen Exp $ */
@session_start ();
foreach ( $_SESSION as $key=>$value ) {
  $dummy[$key]=$value;  // copy to a dummy array
}
if ( ! empty ( $dummy ) )
  foreach ($dummy as $key=>$value) {
   if ( substr ( $key, 0, 6 ) == 'webcal' )
     unset ( $_SESSION[$key] );
  }
//php 4.1.0 may have issues with the above code
unset ( $_SESSION['webcal_login'] );
unset ( $_SESSION['webcalendar_session'] );

require_once 'includes/classes/WebCalendar.class';

$WebCalendar = new WebCalendar ( __FILE__ );

include 'includes/translate.php';
include 'includes/config.php';
include 'includes/dbi4php.php';
include 'includes/formvars.php';
include 'includes/functions.php';

$WebCalendar->initializeFirstPhase ();

include 'includes/' . $user_inc;
include_once 'includes/access.php';
include 'includes/gradient.php';

$WebCalendar->initializeSecondPhase ();

load_global_settings ();

// Change this to true to show "no such user" or "invalid password" on
// login failures.
$showLoginFailureReason = false;

if ( ! empty ( $last_login ) ) {
  $login = '';
}

if ( empty ( $webcalendar_login ) ) {
  $webcalendar_login = '';
}

if ( $REMEMBER_LAST_LOGIN == 'Y' && empty ( $login ) ) {
  $last_login = $login = $webcalendar_login;
}

load_user_preferences ( 'guest' );

$WebCalendar->setLanguage ();

$cookie_path = str_replace ( 'login.php', '', $PHP_SELF );
//echo "Cookie path: $cookie_path\n";

// Look for action=logout
$logout = false;
$action = getGetValue ( 'action' );
if ( ! empty ( $action ) && $action == 'logout' ) {
  $logout = true;
  $return_path = '';
  SetCookie ( 'webcalendar_login', '', 0, $cookie_path );
  SetCookie ( 'webcalendar_last_view', '', 0, $cookie_path );
} else if ( empty ( $return_path ) ) {
  // see if a return path was set
  $return_path = get_last_view ( false );


}

if ( ! empty ( $return_path ) ) {
  $return_path = clean_whitespace ( $return_path );
  $url = $return_path;
} else {
  $url = 'index.php';
}

// If Application Name is set to Title then get translation
// If not, use the Admin defined Application Name
$appStr =  generate_application_name ();

$login = getPostValue ( 'login' );
$password = getPostValue ( 'password' );
$remember = getPostValue ( 'remember' );

// calculate path for cookie
if ( empty ( $PHP_SELF ) ) {
  $PHP_SELF = $_SERVER['PHP_SELF'];
}

if ( $single_user == 'Y' ) {
  // No login for single-user mode
  do_redirect ( 'index.php' );
} else if ( $use_http_auth ) {
  // There is no login page when using HTTP authorization
  do_redirect ( 'index.php' );
} else {
  if ( ! empty ( $login ) && ! empty ( $password ) && ! $logout ) {
    if ( get_magic_quotes_gpc () ) {
      $password = stripslashes ( $password );
      $login = stripslashes ( $login );
    }
    $login = trim ( $login );
    if ( $login != addslashes ( $login ) ) {
      die_miserable_death ( 'Illegal characters in login ' .
        '<tt>' . htmlentities ( $login ) . '</tt>' );
    }
    if ( user_valid_login ( $login, $password ) ) {

      user_load_variables ( $login, '' );

      $encoded_login = encode_string ( $login . '|' . crypt($password) );
      // set login to expire in 365 days
      if ( ! empty ( $remember ) && $remember == 'yes' ) {
        SetCookie ( 'webcalendar_session', $encoded_login,
          time () + ( 24 * 3600 * 365 ), $cookie_path );
      } else {
        SetCookie ( 'webcalendar_session', $encoded_login, 0, $cookie_path );
      }
      // The cookie "webcalendar_login" is provided as a convenience to
      // other apps that may wish to find out what the last calendar
      // login was, so they can use week_ssi.php as a server-side include.
      // As such, it's not a security risk to have it un-encoded since it
      // is not used to allow logins within this app. It is used to
      // load user preferences on the login page (before anyone has
      // logged in) if $REMEMBER_LAST_LOGIN is set to "Y" (in admin.php).
      if ( ! empty ( $remember ) && $remember == 'yes' ) {
        SetCookie ( 'webcalendar_login', $login,
          time () + ( 24 * 3600 * 365 ), $cookie_path );
      } else {
        SetCookie ( 'webcalendar_login', $login, 0, $cookie_path );
      }
      if ( ! empty ( $GLOBALS['newUserUrl'] ) ) $url = $GLOBALS['newUserUrl'];
      do_redirect ( $url );
    } else {
      // Invalid login
      if ( empty ( $error ) || ! $showLoginFailureReason ) {
        $error = translate ('Invalid login', true );
      }
      activity_log ( 0, 'system', '', LOG_LOGIN_FAILURE,
        translate ( 'Username' ) . ": " . $login .
        ", IP: " . $_SERVER['REMOTE_ADDR'] );
    }
  } else {
    // No login info... just present empty login page
    //$error = "Start";
  }
  // delete current user
  SetCookie ( 'webcalendar_session', '', 0, $cookie_path );
  // In older versions the cookie path had no trailing slash and NS 4.78
  // thinks "path/" and "path" are different, so the line above does not
  // delete the "old" cookie. This prohibits the login. So we delete the
  // cookie with the trailing slash removed
  if (substr ($cookie_path, -1) == '/') {
    SetCookie ( 'webcalendar_session', '', 0, substr ($cookie_path, 0, -1)  );
  }
}
echo send_doctype ( $appStr );

if ( ! $logout ) { ?>
<script type="text/javascript">
// error check login/password
function valid_form ( form ) {
  if ( form.login.value.length == 0 || form.password.value.length == 0 ) {
    alert ( '<?php etranslate ( 'You must enter a login and password.', true)?>' );
    return false;
  }
  return true;
}
function myOnLoad () {
  document.login_form.login.focus ();
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
  $csscache =  ( isset ( $_COOKIE['webcalendar_csscache'] ) ?
    $_COOKIE['webcalendar_csscache'] : 1 );

  echo '<link rel="stylesheet" type="text/css" href="css_cacher.php?login=__public__'
	 . $csscache . '" />';

 // Print custom header (since we do not call print_header function)
 if ( ! empty ( $CUSTOM_SCRIPT ) && $CUSTOM_SCRIPT == 'Y' ) {
   echo load_template ( $login, 'S' );
 }
?>
</head>
<body id="login" <?php if ( ! $logout ) { ?>onload="myOnLoad();"<?php } ?>>
<?php
// Print custom header (since we do not call print_header function)
if ( ! empty ( $CUSTOM_HEADER ) && $CUSTOM_HEADER == 'Y' ) {
  echo load_template ( $login, 'H' );
}
?>

<h2><?php echo $appStr?></h2>

<?php
if ( ! empty ( $error ) ) {
  echo '<span style="color:#FF0000; font-weight:bold;">' .
    translate ( 'Error' ) . ": $error</span><br />\n";
} else {
  echo "<br />\n";
}

if ( $logout ) {
  echo '<p>' . translate ( 'You have been logged out.' ) . ".</p>\n";
  echo "<br /><br />\n";
  echo '<a href="login.php' .
    ( ! empty ( $return_path ) ?
      '?return_path=' . htmlentities ( $return_path ) : '' ) .
    '" class="nav">' . translate ( 'Login' ) .
    "</a><br /><br /><br />\n";
}

if ( ! $logout ) {
?>
<form name="login_form" id="login" action="login.php" method="post"
  onsubmit="return valid_form( this )">
<?php
if ( ! empty ( $return_path ) ) {
  echo '<input type="hidden" name="return_path" value="' .
    htmlentities ( $return_path ) . '" />' . "\n";
}
?>

<table align="center" cellspacing="10" cellpadding="10">
<tr><td rowspan="2">
 <img src="images/login.gif" alt="Login" /></td><td align="right">
 <label for="user"><?php etranslate ( 'Username' )?>:</label></td><td>
 <input name="login" id="user" size="15" maxlength="25"
   value="<?php if ( ! empty ( $last_login ) ) echo $last_login;?>"
   tabindex="1" />
</td></tr>
<tr><td class="alignright">
 <label for="password"><?php etranslate ( 'Password' )?>:</label></td><td>
 <input name="password" id="password" type="password" size="15"
   maxlength="30" tabindex="2" />
</td></tr>
<tr><td colspan="3" style="font-size: 10px;">
 <input type="checkbox" name="remember" id="remember" tabindex="3"
   value="yes" <?php if ( ! empty ( $remember ) && $remember == 'yes' ) {
     echo 'checked="checked"'; }?> /><label for="remember">&nbsp;
   <?php etranslate ( 'Save login via cookies so I dont have to login next time.' )?></label>
</td></tr>
<tr><td colspan="4" class="aligncenter">
 <input type="submit" value="<?php etranslate ( 'Login' )?>" tabindex="4" />
</td></tr>
</table>
</form>

<?php }

if ( ! empty ( $PUBLIC_ACCESS ) && $PUBLIC_ACCESS == 'Y' ) { ?>
 <br /><br />
 <a class="nav" href="index.php">
   <?php etranslate ( 'Access public calendar' )?></a><br />
<?php }

  $nulist = get_nonuser_cals ();
  for ( $i = 0, $cnt = count ( $nulist ); $i < $cnt; $i++ ) {
    if ( $nulist[$i]['cal_is_public'] == 'Y' ) {
      ?><a class="nav" href="nulogin.php?login=<?php
        echo $nulist[$i]['cal_login'] . '">' .
          translate ( 'Access' ) . ' ' . $nulist[$i]['cal_fullname'] . ' ' .
          translate ( 'calendar' );
      ?></a><br /><?php
    }
  }
if ( $DEMO_MODE == 'Y' ) {
 // This is used on the sourceforge demo page
 echo 'Demo login: user = "demo", password = "demo"<br />';
} ?>
<br /><br />
<?php if ( ! empty ( $ALLOW_SELF_REGISTRATION ) &&
  $ALLOW_SELF_REGISTRATION == 'Y' ) {
  // We can limit what domain is allowed to self register
  // $self_registration_domain should have this format  "192.168.220.0:255.255.240.0";
  $valid_ip = validate_domain ();

  if ( ! empty ( $valid_ip ) ) {
    echo '<b><a href="register.php">' . translate ( 'Not yet registered? Register here!' ) .
     '</a></b><br />';
  }
}
?>
<span class="cookies"><?php etranslate ( 'cookies-note' )?></span><br />
<hr />
<br />
<a href="<?php echo $PROGRAM_URL ?>" id="programname"><?php echo $PROGRAM_NAME?></a>
<?php // Print custom trailer (since we do not call print_trailer function)
if ( ! empty ( $CUSTOM_TRAILER ) && $CUSTOM_TRAILER == 'Y' ) {
  echo load_template ( $login, 'T' );
}
?>
</body>
</html>
