<?php_track_vars?>
<?php

include "includes/config.inc";
include "includes/php-dbi.inc";
include "includes/functions.inc";
include "includes/$user_inc";
include "includes/connect.inc";

if ( ! empty ( $remember_last_login ) && empty ( $login ) ) {
  $last_login = $login = $webcalendar_login;
}

load_user_preferences ();

if ( ! empty ( $last_login ) )
  $login = "";

include "includes/translate.inc";

if ( $single_user ) {
  // No login for single-user mode
  do_redirect ( "index.php" );
} else if ( $use_http_auth ) {
  // There is no login page when using HTTP authorization
  do_redirect ( "index.php" );
} else {
  if ( ! empty ( $login ) && ! empty ( $password ) ) {
    if ( user_valid_login ( $login, $password ) ) {
      user_load_variables ( $login, "" );
      // set login to expire in 1000 days
      $encoded_login = encode_string ( $login );
      if ( $remember == "yes" )
        SetCookie ( "webcalendar_session", $encoded_login,
          time() + ( 24 * 3600 * 1000 ) );
      else
        SetCookie ( "webcalendar_session", $encoded_login );
      // The cookie "webcalendar_login" is provided as a convenience to
      // other apps that may wish to find out what the last calendar
      // login was, so they can use week_ssi.php as a server-side include.
      // As such, it's not a security risk to have it un-encoded since it
      // is not used to allow logins within this app.  It is used to
      // load user preferences on the login page (before anyone has
      // logged in) if $remember_last_login is set to true (in config.inc).
      if ( $remember == "yes" )
        SetCookie ( "webcalendar_login", $login,
          time() + ( 24 * 3600 * 1000 ), "/" );
      else
        SetCookie ( "webcalendar_login", $login );
      do_redirect ( "index.php" );
    }
  }
  // delete current user
  SetCookie ( "webcalendar_session", "" );
}

?>
<HTML>
<HEAD>
<TITLE><?php etranslate("Title")?></TITLE>
<SCRIPT LANGUAGE="JavaScript">
// error check login/password
function valid_form ( form ) {
  if ( form.login.value.length == 0 || form.password.value.length == 0 ) {
    alert ( "<?php etranslate("You must enter a login and password")?>." );
    return false;
  }
  return true;
}
</SCRIPT>
<?php include "includes/styles.inc"; ?>
</HEAD>
<BODY BGCOLOR="<?php echo $BGCOLOR;?>"
ONLOAD="document.forms[0].login.focus(); <?php if ( ! empty ( $login ) ) echo "document.forms[0].login.select();" ?>">

<H2><FONT COLOR="<?php echo $H2COLOR?>"><?php etranslate("Title")?></FONT></H2>

<?php
if ( ! empty ( $error ) ) {
  print "<FONT COLOR=\"#FF0000\"><B>" . translate("Error") .
    ":</B> $error</FONT><P>\n";
}
?>
<FORM NAME="login_form" ACTION="login.php" METHOD="POST" ONSUBMIT="return valid_form(this)">

<TABLE BORDER=0>
<TR><TD><B><?php etranslate("Username")?>:</B></TD>
  <TD><INPUT NAME="login" SIZE=10 VALUE="<?php if ( isset ( $last_login ) ) echo $last_login;?>" TABINDEX="1"></TD></TR>
<TR><TD><B><?php etranslate("Password")?>:</B></TD>
  <TD><INPUT NAME="password" TYPE="password" SIZE=10 TABINDEX="2"></TD></TR>
<TR><TD COLSPAN=2><INPUT TYPE="checkbox" NAME="remember" VALUE="yes" <?php if ( ! isset ( $remember ) || $remember == "yes" ) echo "CHECKED"; ?>> <?php etranslate("Save login via cookies so I don't have to login next time")?></TD></TR>
<TR><TD COLSPAN=2><INPUT TYPE="submit" VALUE="<?php etranslate("Login")?>" TABINDEX="3"></TD></TR>
</TABLE>

</FORM>

<P>
<?php if ( $demo_mode ) {
// This is used on the sourceforge demo page
  echo "Demo login: user = \"demo\", password = \"demo\"";
} ?>
<BR><BR><BR>
<FONT SIZE="-1">
<?php etranslate("cookies-note")?>
<P>
<HR><P>
<A HREF="<?php echo $PROGRAM_URL ?>" CLASS="aboutinfo"><?php echo $PROGRAM_NAME?></A>
</FONT>
</BODY>
</HTML>
