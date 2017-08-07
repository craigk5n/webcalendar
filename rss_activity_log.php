<?php // $Id: rss_activity_log.php,v 1.9 2010/10/05 17:16:59 cknudsen Exp $
/**
 * Description:
 *  Generates RSS 2.0 output of the activity log.
 *
 *  Like icalclient.php, this file does not use the standard web-based
 *  user authentication. It always uses HTTP-based user authentication
 *  since that is what RSS readers will expect.
 *
 *  For details on the RSS 2.0 specification:
 *    http://cyber.law.harvard.edu/rss/rss.html
 *
 * Input parameters:
 *  None
 *
 * Security:
 *  If User Access Control is on, the user must have access to
 *  ACCESS_ACTIVITY_LOG or be an admin user.
 *  If User Access Control is off, the user must be an admin user.
 *
 * Notes:
 *  Changes in functionality should be coordinated with activity_log.php
 *  since there is common code in the two files.
 *
 *  If running as CGI, the following instructions should set the
 *  PHP_AUTH_xxxx variables. This has only been tested with apache2,
 *  so far. If using php as CGI, you'll need to include this in your
 *  httpd.conf file or possibly in an .htaccess file.
 *
 *  <IfModule mod_rewrite.c>
 *    RewriteEngine on
 *    RewriteRule .* - [E=REMOTE_USER:%{HTTP:Authorization},L]
 *  </IfModule>
 */

include_once 'includes/translate.php';
require_once 'includes/classes/WebCalendar.class';

$WebCalendar = new WebCalendar( __FILE__ );

include 'includes/config.php';
include 'includes/dbi4php.php';
include 'includes/formvars.php';
include 'includes/functions.php';
include 'includes/access.php';

$WebCalendar->initializeFirstPhase();

include 'includes/' . $user_inc;

include_once 'includes/validate.php';
include 'includes/site_extras.php';

// This next step will send a redirect to login.php, which we don't want.
$WebCalendar->initializeSecondPhase();

$appStr = generate_application_name();

if ( empty ( $_SERVER['PHP_AUTH_USER'] ) && ! empty ( $_ENV['REMOTE_USER'] ) ) {
  list ( $_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW'] ) =
  explode ( ':', base64_decode ( substr ( $_ENV['REMOTE_USER'], 6 ) ) );

  $_SERVER['PHP_AUTH_USER'] = trim ( $_SERVER['PHP_AUTH_USER'] );
  $_SERVER['PHP_AUTH_PW'] = trim ( $_SERVER['PHP_AUTH_PW'] );
}

unset ( $_ENV['REMOTE_USER'] );
if ( empty ( $login ) ) {
  if ( isset ( $_SERVER['PHP_AUTH_USER'] ) &&
      user_valid_login ( $_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW'], true ) )
    $login = $_SERVER['PHP_AUTH_USER'];

  if ( empty ( $login ) || $login != $_SERVER['PHP_AUTH_USER'] ) {
    $_SERVER['PHP_AUTH_PW'] = $_SERVER['PHP_AUTH_USER'] = '';
    unset ( $_SERVER['PHP_AUTH_USER'] );
    unset ( $_SERVER['PHP_AUTH_PW'] );
    header ( 'WWW-Authenticate: Basic realm="' . $appStr . '"' );
    header ( 'HTTP/1.0 401 Unauthorized' );
    exit;
  }
}
load_global_settings();
load_user_preferences();

$WebCalendar->setLanguage();

// Load user name, etc.
user_load_variables ( $login, '' );

// Make sure the have privileges to access the activity log
if ( ! $is_admin || ( access_is_enabled() && !
  access_can_access_function ( ACCESS_ACTIVITY_LOG ) ) )
  die_miserable_death ( print_not_auth (2) );


$charset = ( empty ( $LANGUAGE ) ? 'iso-8859-1' : translate ( 'charset' ) );
// This should work ok with RSS, may need to hardcode fallback value.
$lang = languageToAbbrev ( $LANGUAGE == 'Browser-defined' || $LANGUAGE == 'none'
  ? $lang : $LANGUAGE );
if ( $lang == 'en' )
  $lang = 'en-us'; //the RSS 2.0 default.

$appStr = generate_application_name();
$descr = $appStr . ' - ' . translate ( 'Activity Log' );

//header ( 'Content-type: application/rss+xml');
header ( 'Content-type: text/xml' );
echo '<?xml version="1.0" encoding="' . $charset . '"?>
<?xml-stylesheet href="rss-style.css" ?>
<rss version="2.0" xml:lang="' . $lang . '">
  <channel>
    <title><![CDATA[' . $appStr . ']]></title>
    <link>' . $SERVER_URL . '</link>
    <description><![CDATA[' . $descr . ']]></description>
    <language>' . $lang . '</language>
    <generator>WebCalendar ' . $PROGRAM_VERSION
 . '</generator>
    <image>
      <title><![CDATA[' . $appStr . ']]></title>
      <link>' . $SERVER_URL . '</link>
      <url>http://www.k5n.us/k5n_small.gif</url>
    </image>' . "\n";


$num = getIntValue ( false, 'num' );
if ( empty ( $num ) || $num <= 0 || $num > 100 )
  $num = 100;
echo rss_activity_log ( false, $num );

echo "  </channel>\n</rss>\n";

exit;

/**
 * Generate the activity log.
*/
function rss_activity_log ( $sys, $entries ) {
  global $SERVER_URL, $ALLOW_HTML_DESCRIPTION, $login;

  $sql_params = array();

  $limit = $where = '';
  switch ( $GLOBALS['db_type'] ) {
    case 'mysqli':
    case 'mysql':
    case 'postgresql':
      $limit .= ' LIMIT ' . $entries;
      break;
    case 'oracle':
      $where .= ' AND ROWNUM <= ' . $entries;
      break;
  }

  $sql = 'SELECT wel.cal_login, wel.cal_user_cal, wel.cal_type, wel.cal_date,
    wel.cal_time, wel.cal_text, '
   . ( $sys
    ? 'wel.cal_log_id FROM webcal_entry_log wel WHERE wel.cal_entry_id = 0'
    : 'we.cal_id, we.cal_name, wel.cal_log_id, we.cal_type, we.cal_description
      FROM webcal_entry_log wel, webcal_entry we
      WHERE wel.cal_entry_id = we.cal_id' . $where )
   . ' ORDER BY wel.cal_log_id DESC' . $limit;

  $rows = dbi_get_cached_rows ( $sql, $sql_params );

  $ret = '';

  for ( $i = 0; $i < count ( $rows ) && $i < $entries; $i++ ) {
    $row = $rows[$i];
    $num = 0;
    $l_login = $row[0];
    $l_user = $row[1];
    $l_type = $row[2];
    $l_date = $row[3];
    $l_time = $row[4];
    $l_text = $row[5];

    if ( $sys ) {
      $l_id = $row[6];
      $l_description = '';
    } else {
      $l_eid = $row[6];
      $l_ename = $row[7];
      $l_id = $row[8];
      $l_etype = $row[9];
      $l_description = $row[10];
      // convert lines to <br /> if no HTML formatting found
      if ( strpos ( $l_description, "</" ) == false ) {
        $l_description = nl2br ( $l_description );
      }
    }
    $num++;
    $unixtime = date_to_epoch ( $l_date . $l_time );
    $subject = display_activity_log ( $l_type, $l_text, "\n" );
    $ret .=
      "<item>\n" . '  <title><![CDATA[' . $subject . ': '
      . htmlspecialchars( $l_ename ) . ']]></title>' . "\n  <link>"
      . $SERVER_URL . 'view_entry.php?id=' . $l_eid . "</link>\n"
      . '  <description>';
    if ( $ALLOW_HTML_DESCRIPTION == 'Y' ) {
      $x = str_replace ( '&', '&amp;', $l_description );
      $x = str_replace ( '&amp;amp;', '&amp;', $x );
      $ret .= $x;
    } else
      $ret .= '<![CDATA[' . $l_description  . ']]>';
    $ret .= '</description>';
    $ret .= "\n"
    // . '  <category><![CDATA[' . $category . ']]></category>' . "\n"
    /* RSS 2.0 date format Wed, 02 Oct 2002 13:00:00 GMT */
      . '<pubDate>' . gmdate( 'D, d M Y H:i:s', $unixtime ) . ' GMT</pubDate>'
      . "\n" . '  <guid>' . $SERVER_URL . 'view_entry.php?id=' . $l_eid
      . '&amp;friendly=1&amp;rssuser=' . $login . '&amp;date=' . $l_date
      . "</guid>\n" . "</item>\n\n";
  }

  return $ret;
}

?>
