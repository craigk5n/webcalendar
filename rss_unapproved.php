<?php
/**
 * Description:
 *  Generates RSS 2.0 output of unapproved events for a user.
 *
 *  Like icalclient.php, this file does not use the standard web-based
 *  user authentication. It always uses HTTP-based user authentication
 *  since that is what RSS readers will expect.
 *
 *  For details on the RSS 2.0 specification:
 *    http://cyber.law.harvard.edu/rss/rss.html
 *
 * Input parameters:
 *  user=NNN to display unapproved events for the specified user login
 *
 * Security:
 *  No system settings or user preferences are required to enable this
 *  page to work.
 *
 * Notes:
 *  Changes in functionality should be coordinated with list_unapproved.php
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

include_once 'includes/xcal.php';

$WebCalendar->initializeSecondPhase();

$appStr = generate_application_name();
// If WebCalendar is using http auth, then $login will be set in validate.php.
if ( empty ( $_SERVER['PHP_AUTH_USER'] ) && ! empty ( $_ENV['REMOTE_USER'] ) ) {
  list ( $_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW'] ) =
  explode ( ':', base64_decode ( substr ( $_ENV['REMOTE_USER'], 6 ) ) );

  $_SERVER['PHP_AUTH_USER'] = trim ( $_SERVER['PHP_AUTH_USER'] );
  $_SERVER['PHP_AUTH_PW'] = trim ( $_SERVER['PHP_AUTH_PW'] );
}

unset ( $_ENV['REMOTE_USER'] );
if ( empty ( $login ) || $login == '__public__' ) {
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

// See if a user login was specified in the URL
$user = getGetValue ( 'user' );
// translate 'public' to be '__public__'
if ( $user == 'public' )
  $user = '__public__';
// Make sure the current user has proper permissions to see unapproved
// events for the specified user. We're not checking to see if
if ( $user != '' ) {
  if ( access_is_enabled() ) {
     if ( ! access_user_calendar ( 'approve', $user ) ) {
       // not allowed
       $user = login;
     }
  } else if ( ! $is_admin && $user != $login && ! $is_assistant &&
    ! access_is_enabled() ) {
    $user = $login;
  }
}

// If not, user current user's login
if ( $user == '' )
  $user = $login;


$charset = ( empty ( $LANGUAGE ) ? 'iso-8859-1' : translate ( 'charset' ) );
// This should work ok with RSS, may need to hardcode fallback value.
$lang = languageToAbbrev ( $LANGUAGE == 'Browser-defined' || $LANGUAGE == 'none'
  ? $lang : $LANGUAGE );
if ( $lang == 'en' )
  $lang = 'en-us'; //the RSS 2.0 default.

user_load_variables ( $user, 'temp_' );
$appStr = generate_application_name();
$descr = $appStr . ' - ' . translate ( 'Unapproved Entries' ) . ' - ' .
  $temp_fullname;

// header ( 'Content-type: application/rss+xml');
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

echo list_unapproved ( $user );

echo "  </channel>\n</rss>\n";

exit;

/**
 * List all unapproved events for the specified user.
 * Exclude "extension" events (used when an event goes past midnight).
 * TODO: Only include delete link if they have permission to delete
 *       when user access control is enabled.
 * NOTE: this function is almost identical to the one in list_unapproved.php.
 * Just the format (RSS vs HTML) is different.
*/
function list_unapproved ( $user ) {
  global $login, $SERVER_URL;

  $count = 0;
  $ret = '';

  $sql = 'SELECT we.cal_id, we.cal_name, we.cal_description, weu.cal_login,
    we.cal_priority, we.cal_date, we.cal_time, we.cal_duration,
    weu.cal_status, we.cal_type
    FROM webcal_entry we, webcal_entry_user weu
    WHERE we.cal_id = weu.cal_id AND weu.cal_login = ? AND weu.cal_status = \'W\'
    ORDER BY weu.cal_login, we.cal_date';
  $rows = dbi_get_cached_rows ( $sql, [$user] );
  if ( $rows ) {
    $allDayStr = translate ( 'All day event' );
    $appConStr = translate ( 'Approve/Confirm' );
    $appSelStr = translate ( 'Approve Selected' );
    $checkAllStr = translate ( 'Check All' );
    $deleteStr = translate ( 'Delete' );
    $emailStr = translate ( 'Emails Will Not Be Sent' );
    $rejectSelStr = translate ( 'Reject Selected' );
    $rejectStr = translate ( 'Reject' );
    $uncheckAllStr = translate ( 'Uncheck All' );
    $viewStr = translate ( 'View this entry' );
    for ( $i = 0, $cnt = count ( $rows ); $i < $cnt; $i++ ) {
      $row = $rows[$i];
      $id = $row[0];
      $name = $row[1];
      $description = $row[2];
      $cal_user = $row[3];
      $pri = $row[4];
      $date = $row[5];
      $time = sprintf ( "%06d", $row[6] );
      $duration = $row[7];
      $status = $row[8];
      $type = $row[9];
      $view_link = 'view_entry';
      $entryID = 'entry' . $type . $id;
      $unixtime = date_to_epoch ( $date . $time );

      $timestr = '';
      if ( $time > 0 || ( $time == 0 && $duration != 1440 ) ) {
        $eventstart = date_to_epoch ( $date . $time );
        $eventstop = $eventstart + $duration;
        $eventdate = date_to_str ( date ( 'Ymd', $eventstart ) );
        $timestr = display_time ( '', 0, $eventstart )
         . ( $duration > 0 ? ' - ' . display_time ( '', 0, $eventstop ) : '' );
      } else {
        // Don't shift date if All Day or Untimed.
        $eventdate = date_to_str ( $date );
        // If All Day display in popup.
        if ( $time == 0 && $duration == 1440 )
          $timestr = $allDayStr;
      }

      $ret .=
        "<item>\n" .
        '  <title><![CDATA[' . htmlspecialchars ( $name ) . ']]></title>' .
        "\n  <link>" . $SERVER_URL .
        $view_link . '.php?id=' . $id .
        '&amp;user=' . $cal_user . "</link>\n" .
        '  <description><![CDATA[' . $description  . ']]></description>' . "\n";
      $ret .=
        '  <category><![CDATA[' . $category . ']]></category>' . "\n";
        /* RSS 2.0 date format Wed, 02 Oct 2002 13:00:00 GMT */
      $ret .= '<pubDate>' .
        gmdate ( 'D, d M Y H:i:s', $unixtime ) . ' GMT</pubDate>' . "\n" .
        '  <guid>' . $SERVER_URL . 'view_entry.php?id=' . $id .
        '&amp;friendly=1&amp;rssuser=' . $login .
       '&amp;date=' . $d . "</guid>\n";
      $ret .= "</item>\n\n";
    }
  }
  return $ret;
} //end list_unapproved()

?>
