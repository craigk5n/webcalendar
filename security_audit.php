<?php // $Id: security_audit.php,v 1.13 2010/01/24 10:07:07 bbannon Exp $
/**
 * Description:
 *  This page will take look for possible security issues with
 *  this installation of WebCalendar.
 *
 * Input Parameters:
 *  None
 *
 * Security:
 *  User must be an admin user
 *  AND, if user access control is enabled, they must have access to
 *  'Security Audit'.
 */
include_once 'includes/init.php';

if ( ! $is_admin || ( access_is_enabled()
     && ! access_can_access_function( ACCESS_SECURITY_AUDIT ) ) )
  die_miserable_death( print_not_auth() );

$phpinfo = getGetValue( 'phpinfo' );
if ( $phpinfo == '1' ) {
  print_header( '', '', '', true );
  phpinfo();
  print_trailer( false, true, true );
  exit;
}
clearstatcache();
print_header();
echo '
    <h2>' . translate( 'Security Audit' ) . '</h2>
    <ul id="securityAuditNotes">
      <li>' . translate( 'list potential security issues') . '</li>
      <li>' . translate( 'For questions about WebCalendar security see the forums' )
  . '<a href="https://sourceforge.net/forum/?group_id=3870" target="_blank">'
  . '<img src="docs/newwin.gif" alt="SourceForge.net"></a></li>
      <li><a href="#" onclick="window.open( \'security_audit.php?phpinfo=1\', '
  . '\'phpinfo\', \'dependent,menubar,scrollbars,height=500,width=600,'
  . 'innerHeight=520,outerWidth=620\' );" />'
  . translate( 'View your current PHP settings' ) . '</a></li>
    </ul>
    <table id="securityAudit" cellpadding="4">
      <tr>
        <th>' . translate( 'Security Issue' ) . '</th>
        <th>' . translate( 'Status' ) . '</th>
        <th>' . translate( 'Details' ) . '</th>
      </tr>';

// Make sure they aren't still using the default admin username/password.
print_issue( translate( 'Default admin user password' ),
  ( user_valid_login( 'admin', 'admin' ) == false ),
  translate( 'You should change the password of the default admin user.' ) );

// Is the main directory still writable?
// Just see if we get an error trying to append to it.
$wcDir = '.';
$wcName = 'WebCalendar toplevel director';
if ( preg_match( '/(.*).security_audit.php/', __FILE__, $matches ) ) {
  $wcDir  = $matches[1] . '\\';
  $wcName = basename( $wcDir );
}

$filePerms   = translate( 'File permissions XXX' );
$noWriteItem = translate( 'item XXX should not be writable' );

print_issue( str_replace( 'XXX', $wcName, $filePerms ),
  ( ! is__writable( $wcDir ) ),
  str_replace( 'XXX', htmlentities( $wcDir ), $noWriteItem ) );

// Is the includes directory still writable?
// Just see if we get an error trying to append to it.
print_issue( str_replace( 'XXX', 'includes', $filePerms ),
  ( ! is__writable( 'includes' ) ),
  str_replace( 'XXX', get_wc_path( 'includes' ), $noWriteItem ) );

// Is the includes/settings.php file still writable?
// Unfortunately, some of the PHP file permissions calls have bugs,
// so just see if we get an error trying to append to it.
$fd   = @fopen( 'includes/settings.php', 'a+b' );
$isOk = true;
if ( $fd > 0 ) {
  // Error: should not be allowed to write!
  fclose( $fd );
  $isOk = false;
}
print_issue( str_replace( 'XXX', 'includes/settings.php', $filePerms ), $isOk,
  str_replace( 'XXX', get_wc_path( 'includes/settings.php' ), $noWriteItem ) );

// If email or reminders are not enabled, tell them to remove the file.
$isOk = ( ! file_exists( 'tools/send_reminders.php' ) );
if ( $SEND_EMAIL != 'Y' ) {
  // Reminders are disabled!
  print_issue( str_replace( 'XXX', 'tools/send_reminders.php',
      translate( 'File exists XXX' ) ), $isOk,
    translate( 'Because you have email disabled, you should remove this file.' ) );
} else {
  // Is tools/send_reminders.php in the 'standard' location?
  print_issue( str_replace( 'XXX', 'tools/send_reminders.php',
      translate( 'File location XXX' ) ), $isOk,
    str_replace( 'XXX', get_wc_path( 'tools/send_reminders.php' ),
      translate( 'remove XXX if not using' ) ) );
}

$sysSettingsXXX = translate( 'System Settings XXX' );

// Is UAC enabled?
print_issue(
  str_replace( 'XXX', translate( 'User Access Control' ), $sysSettingsXXX ),
  access_is_enabled(), translate( 'consider enabling UAC' ) );

// If Public Access enabled, make sure approvals are on
if ( $PUBLIC_ACCESS == 'Y' ) {
  print_issue( str_replace( 'XXX',
      translate( 'Public access new events require approval' ), $sysSettingsXXX ),
    ( $PUBLIC_ACCESS_CAN_ADD != 'Y' || $PUBLIC_ACCESS_ADD_NEEDS_APPROVAL == 'Y' ),
    translate( 'recommend approving new public events' ) );

  print_issue( str_replace( 'XXX',
      translate( 'Require CAPTCHA validation for public access new events' ),
      $sysSettingsXXX ),
    ( $ENABLE_CAPTCHA == 'Y' ), translate( 'recommend using CAPTCHA' ) );
}

// Is db cache directory a subdirectory of WebCalendar?
$isOk = true;
if ( ! empty( $settings['db_cachedir'] ) && $wcDir != '.' ) {
  $cache = str_replace( '\\', '/', $settings['db_cachedir'] );
  $wcDir = str_replace( '\\', '/', $wcDir );
  if ( strncmp( $cache, $wcDir, strlen( $wcDir ) ) == 0
      && strlen( $wcDir ) < strlen( $cache ) ) {
    // Using a webcalendar subdirectory for db cache.
    $isOk = false;
  }
}
print_issue( translate( 'Database cache directory location' ), $isOk,
  translate( 'db cache should be inaccessable' ) );

$phpSettingsXXX  = translate( 'PHP Settings XXX' );
$recommendXXXOff = translate( 'recommend setting XXX Off' );

// Check for magic quotes.
// See: http://us.php.net/manual/en/security.magicquotes.php
print_issue( str_replace( 'XXX', 'magic_quotes_gpc', $phpSettingsXXX ),
  ( get_magic_quotes_gpc() == 0 ),
  str_replace( 'XXX', 'magic quotes', $recommendXXXOff ) );

// Check for register globals.
print_issue( str_replace( 'XXX', 'register_globals', $phpSettingsXXX ),
  ( ini_get( 'register_globals' ) == 0 ),
  str_replace( 'XXX', 'register_globals', $recommendXXXOff ) );

// Check for allow_url_fopen.
// Recommended setting is off when remote calendars are not enabled.
print_issue( str_replace( 'XXX', 'allow_url_fopen', $phpSettingsXXX ),
  ( ini_get( 'allow_url_fopen' ) == 0 || $REMOTES_ENABLED == 'Y' ),
  translate( 'recommend setting allow_url_fopen Off' ) );

// Check for allow_url_include.
print_issue( str_replace( 'XXX', 'allow_url_include', $phpSettingsXXX ),
  ( ini_get( 'allow_url_include' ) == 0 ),
  str_replace( 'XXX', 'allow_url_include', $recommendXXXOff ) );

echo '
    </table>
' . print_trailer() . '
<!-- done -->';
exit;

/* functions ... */
/**
 * print_issue (needs description)
 */
function print_issue( $description, $isOk, $help ) {
  global $count;

  if ( empty( $count ) )
    $count = 0;

  if ( $isOk ) {
    $img = 'ok.gif" alt="OK"';
    $help = '&nbsp;';
  } else
    $img = 'error.gif" alt="Warning"';

  echo '
      <tr' . ( $count++ % 2 > 0 ? ' class="odd"' : '' ) . '>
        <td>' . $description . '</td>
        <td><img src="images/' . $img . ' width="16" height="16" /></td>
        <td>' . $help . '</td>
      </tr>';
}

/**
 * Get the full path to a file located in the webcalendar directory.
 */
function get_wc_path( $filename ) {
  if ( preg_match( '/(.*)security_audit.php/', __FILE__, $matches ) )
    return $matches[1] . $filename;
  else
    // Oops. This file is not named security_audit.php
    die_miserable_death( 'Crap! Someone renamed security_audit.php' );
}
/**
 * Determine if a directory or file is writable
 */
function is__writable( $path ) {
//Will work despite Windows ACLs bug.
//NOTE: use a trailing slash for folders!!!
//see http://bugs.php.net/bug.php?id=27609
//see http://bugs.php.net/bug.php?id=30931

  if ( $path{ strlen( $path ) - 1 } == '/' ) // recursively return a temporary file path
    return is__writable( $path . uniqid( mt_rand() ) . '.tmp' );
  else if ( @is_dir( $path ) )
    return is__writable( $path . '/' . uniqid( mt_rand() ) . '.tmp' );

  // Check tmp file for read/write capabilities.
  $rm = @file_exists( $path );
  $f = @fopen( $path, 'a' );
  if ( $f === false )
    return false;

  @fclose( $f );
  if ( ! $rm )
    @unlink( $path );

  return true;
}

?>
