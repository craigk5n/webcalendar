<?php
/* $Id: security_audit.php,v 1.1.2.7 2008/04/11 18:16:34 umcesrjones Exp $
 *
 * Description:
 *	This page will take look for possible security issues with
 *	this installation of WebCalendar.
 *
 * Input Parameters:
 *	None
 *
 * Security:
 *  User must be an admin user
 *  AND, if user access control is enabled, they must have access to
 *  'Security Audit'.
 */
include_once 'includes/init.php';

if ( ! $is_admin || ( access_is_enabled () && !
      access_can_access_function ( ACCESS_SECURITY_AUDIT ) ) )
  die_miserable_death ( print_not_auth () );

$phpinfo = getGetValue ( 'phpinfo' );
if ( $phpinfo == '1' ) {
  print_header ( '', '', '', true );
  phpinfo ();
  print_trailer ( false, true, true );
  exit;
}
clearstatcache();
print_header ();

?>
<h2><?php etranslate('Security Audit');?></h2>

<ul id="securityAuditNotes">
<li><?php etranslate('The information below lists potential issues with your WebCalendar installation that could be modified to make your installation more secure.');?></li>
<li><?php etranslate ( 'For questions about any WebCalendar security issue, please use the WebCalendar forums hosted on SourceForge.net.' );?>
  <a href="https://sourceforge.net/forum/?group_id=3870" target="_blank"><img src="docs/newwin.gif" alt="SourceForge.net" border="0"></a></li>
<li><a href="#" onclick="window.open( 'security_audit.php?phpinfo=1', 'phpinfo', 'dependent,menubar,scrollbars,height=500,width=600,innerHeight=520,outerWidth=620' );" /><?php etranslate("View your current PHP settings");?></a>
  </li>
</ul>

<table id="securityAudit" border="0" cellpadding="4">
<tr><th><?php etranslate('Security Issue');?></th>
  <th><?php etranslate('Status');?></th>
  <th><?php etranslate('Details');?></th></tr>
<?php

// Make sure they aren't still using the default admin username/password
$isOk = ( user_valid_login ( 'admin', 'admin' ) == false );
$help =
  translate ( 'You should change the password of the default admin user.' );
print_issue ( 
  translate('Default admin user password'), $isOk, $help );

// Is the main directory still writable?
// just see if we get an error trying to append to it.
$wcDir = '.';
$wcName = 'WebCalendar toplevel director';
if ( preg_match ( '/(.*).security_audit.php/', __FILE__, $matches ) ) {
  $wcDir = $matches[1] . '\\';
  $wcName = basename ( $wcDir );
} 

$isOk = ! is__writable ( $wcDir );
$help = translate ( 'The following item should not be writable' ) .
  ':<br/><tt>' . htmlentities ( $wcDir ) . '</tt>';
print_issue ( 
  translate('File permissions') . ': ' . $wcName, $isOk, $help );

// Is the includes directory still writable?
// just see if we get an error trying to append to it.
$isOk = ! is__writable ( 'includes' );
$help = translate ( 'The following item should not be writable' ) .
  ':<br/><tt>' . get_wc_path ( 'includes' ) . '</tt>';
print_issue ( 
  translate('File permissions') . ': includes', $isOk, $help );

// Is the includes/settings.php file still writable?
// Unfortunately, some of the PHP file permissions calls have bugss, so
// just see if we get an error trying to append to it.
$fd = @fopen ( 'includes/settings.php', 'a+b' );
$isOk = true;
$help = translate ( 'The following item should not be writable' ) .
  ': <br/><tt>' . get_wc_path ( 'includes/settings.php' ) . '</tt>';
if ( $fd > 0 ) {
  // Error: should not be allowed to write!
  fclose ( $fd );
  $isOk = false;
}
print_issue ( 
  translate('File permissions') . ': includes/settings.php', $isOk, $help );

// If email or reminders are not enabled, tell them to remove the file
if ( $SEND_EMAIL != 'Y' ) {
  // Reminders are disabled!
  $isOk = ( ! file_exists ( 'tools/send_reminders.php' ) );
$help = translate ( 'Because you have email disabled, you should remove this file.' );
print_issue ( 
  translate('File exists') . ': tools/send_reminders.php', $isOk, $help );
} else {
  // Is tools/send_reminders.php in the 'standard' location
  $isOk = ! ( file_exists ( 'tools/send_reminders.php' ) );
  $help = translate ( 'If you are not using this file, remove it.  Otherwise, it should be moved to a different location.' ) .
    '<br/><tt>' . get_wc_path ( 'tools/send_reminders.php' ) . '</tt>';
  print_issue ( 
    translate('File location') . ': tools/send_reminders.php', $isOk, $help );
}

// Is UAC enabled
$isOk = access_is_enabled ();
$help = translate ( 'You may want to consider enabling User Access Control to set user privileges.' );
print_issue ( 
  translate('System Settings') . ': ' .
  translate('User Access Control'), $isOk, $help );

// If Public Access enabled, make sure approvals are on
if ( $PUBLIC_ACCESS == 'Y' ) {
  $isOk = ( $PUBLIC_ACCESS_CAN_ADD != 'Y' ||
    $PUBLIC_ACCESS_ADD_NEEDS_APPROVAL == 'Y' );
  $help = translate ( 'It is recommended that public event submissions be approved' );
  print_issue ( 
    translate('System Settings') . ': ' .
    translate('Public access new events require approval'), $isOk, $help );

  $isOk = $ENABLE_CAPTCHA == 'Y';
  $help = translate ( 'CAPTCHA is recommended to guard againt automated event submissions.' );
  print_issue ( 
    translate('System Settings') . ': ' .
    translate('Require CAPTCHA validation for public access new events'), $isOk, $help );

}

// See if db cache directory is subdirectory of WebCalendar
$isOk = true;
$help = translate ( 'The database cache directory should be in a directory that cannot be accessed with a URL.' );
if ( ! empty ( $settings['db_cachedir'] ) && $wcDir != '.' ) {
  $cache = str_replace ( '\\', '/', $settings['db_cachedir'] );
  $wcDir = str_replace ( "\\", '/', $wcDir );
  if ( strncmp ( $cache, $wcDir, strlen ( $wcDir ) ) == 0 &&
    strlen ( $wcDir ) < strlen ( $cache ) ) {
    // Using a webcalendar subdirectory for db cache
    $isOk = false;
  }
}
print_issue ( 
  translate('Database cache directory location'), $isOk, $help );

// Check for magic quotes.
// Recommended setting is off.
// See: http://us.php.net/manual/en/security.magicquotes.php
$help = translate ( 'The recommended setting for magic quotes is Off.' );
$isOk = ( get_magic_quotes_gpc () == 0 );
print_issue ( 
  translate('PHP setting') . ': magic_quotes_gpc', $isOk, $help );

// Check for register globals
// Recommended setting is off.
$help = translate ( 'The recommended setting for register_globals is Off.' );
$isOk = ( ini_get ( 'register_globals' ) == 0 );
print_issue ( 
  translate('PHP setting') . ': register_globals', $isOk, $help );

// Check for allow_url_fopen
// Recommended setting is off when remote calendars are not enabled
$help = translate ( 'The recommended setting for allow_url_fopen is Off when remote calendars are not enabled.' );
$isOk = ( ini_get ( 'allow_url_fopen' ) == 0 || $REMOTES_ENABLED == 'Y' );
print_issue ( 
  translate('PHP setting') . ': allow_url_fopen', $isOk, $help );

// Check for allow_url_include
// Recommended setting is Off
$help = translate ( 'The recommended setting for allow_url_include is Off.' );
$isOk = ( ini_get ( 'allow_url_include' ) == 0 );
print_issue ( 
  translate('PHP setting') . ': allow_url_include', $isOk, $help );


echo "</table>\n";


echo print_trailer ();

echo "<!-- done -->\n";
exit;



/* functions ... */

function print_issue ( $description, $isOk, $help )
{
  global $count;

  if ( empty ( $count ) )
    $count = 0;

  if ( $isOk ) {
    $img = '<img src="images/ok.gif" alt="Ok" width="16" height="16"/>';
    $help = '';
  } else {
    $img = '<img src="images/error.gif" alt="Warning" width="16" height="16"/>';
  }
  if ( $count++ % 2 == 0 )
    $class = 'odd';
  else
    $class = 'even';
  echo '<tr><td class="' . $class .  '">' . $description . 
    '</td><td class="' . $class . '">' . $img . '</td>' .
    '<td class="' . $class . '">' . $help . "</td></tr>\n";
}

/* Get the full path to a file located in the webcalendar directory.
 */
function get_wc_path ( $filename ) {
  if ( preg_match ( '/(.*)security_audit.php/', __FILE__, $matches ) ) {
    $fileLoc = $matches[1] . $filename;
    return $fileLoc;
  } else
    // Oops. This file is not named security_audit.php
    die_miserable_death ( 'Crap! Someone renamed security_audit.php' );
}

function is__writable($path) {
//will work in despite of Windows ACLs bug
//NOTE: use a trailing slash for folders!!!
//see http://bugs.php.net/bug.php?id=27609
//see http://bugs.php.net/bug.php?id=30931

    if ($path{strlen($path)-1}=='/') // recursively return a temporary file path
        return is__writable($path.uniqid(mt_rand()).'.tmp');
    else if (@is_dir($path))
        return is__writable($path.'/'.uniqid(mt_rand()).'.tmp');
    // check tmp file for read/write capabilities
    $rm = @file_exists($path);
    $f = @fopen($path, 'a');
    if ($f===false)
        return false;
    @fclose($f);
    if (!$rm)
        @unlink($path);
    return true;
}
?>
