#!/usr/local/bin/php -q
<?php
/**
 * Description:
 * This is a command-line script that will reload all user's remote calendars.
 *
 * Usage:
 * php reload_remotes.php
 *
 * Setup:
 * This script should be setup to run periodically on your system.
 * You should not run this more a once per hour for performance reasons
 *
 * To set this up in cron, add a line like the following in your crontab
 * to run it every hour:
 *   1 * * * * php /some/path/here/reload_remotes.php
 * Of course, change the path to where this script lives. If the PHP binary is
 * not in your $PATH, you may also need to provide the full path to "php".
 * On Linux, just type crontab -e to edit your crontab.
 *
 * If you're a Windows user, you'll either need to find a cron clone
 * for Windows (they're out there) or use the Windows Task Scheduler.
 * (See docs/WebCalendar-SysAdmin.html for instructions.)
 *
 * Comments:
 * You will need access to the PHP binary (command-line) rather than the
 * module-based version that is typically installed for use with a web server.
 *
 * If running this script from the command line generates PHP warnings,
 * you can disable error_reporting by adding
 * "-d error_reporting=0" to the command line:
 *   php -d error_reporting=0 /some/path/here/tools/reload_remotes.php
 *
 ******************************************************************** */
// Load include files.
// If you have moved this script out of the WebCalendar directory,
// which you probably should do since it would be better for security reasons,
// you would need to change __WC_INCLUDEDIR to point to the
// webcalendar include directory.

define('__WC_BASEDIR', '../'); // Points to the base WebCalendar directory
// relative to current working directory.
define('__WC_INCLUDEDIR', __WC_BASEDIR . 'includes/');
define('__WC_CLASSDIR', __WC_INCLUDEDIR . 'classes/');
$old_path = ini_get('include_path');
$delim = (strstr($old_path, ';') ? ';' : ':');
ini_set('include_path', $old_path . $delim . __WC_INCLUDEDIR . $delim);

include_once __WC_INCLUDEDIR . 'translate.php';
require_once __WC_CLASSDIR . 'WebCalendar.php';

$WebCalendar = new WebCalendar(__FILE__);

include __WC_INCLUDEDIR . 'config.php';
include __WC_INCLUDEDIR . 'dbi4php.php';
include __WC_INCLUDEDIR . 'formvars.php';
include __WC_INCLUDEDIR . 'functions.php';

$WebCalendar->initializeFirstPhase();

include __WC_INCLUDEDIR . $user_inc;
include __WC_INCLUDEDIR . 'xcal.php';

$WebCalendar->initializeSecondPhase();
// Used for hCal parsing.
require_once __WC_CLASSDIR . 'hKit/hkit.class.php';

$debug = false; // Set to true to print debug info...

// Establish a database connection.
$c = dbi_connect($db_host, $db_login, $db_password, $db_database, true);
if (!$c) {
  echo translate('Error connecting to database') . ': ' . dbi_error();
  exit;
}

load_global_settings();
$WebCalendar->setLanguage();

if ($debug)
  echo "<br />\n" . translate('Include Path')
    . ' =' . ini_get('include_path') . "<br />\n";

if ($REMOTES_ENABLED == 'Y') {
  $res = dbi_execute('SELECT cal_login, cal_url, cal_admin ' .
    'FROM webcal_nonuser_cals WHERE cal_url IS NOT NULL');
  $cnt = 0;
  if ($res) {
    while ($row = dbi_fetch_row($res)) {
      $data = [];
      $cnt++;
      $calUser = $row[0];
      $cal_url = $row[1];
      $login = $row[2];
      $type = 'remoteics';
      if ($debug) echo "Checking calendar: $cal_url\n";
      // TODO: Handle hcal data.  Is that still used by anyeone???
/*
      $data = parse_ical($cal_url, $type);
      // we may be processing an hCalendar
      if (empty($data) == 0 && function_exists('simplexml_load_string')) {
        if ($debug) echo "  No data found.  Trying hcal...\n";
        $h = new hKit;
        $h->tidy_mode = 'proxy';
        $result = $h->getByURL('hcal', $cal_url);
        $type = 'hcal';
        $data = parse_hcal($result, $type);
      }
*/
      if (empty($errormsg) && !empty($cal_url)) {
        if ($debug) {
          echo "Loading calendar \"$calUser\" from URL: $cal_url\n";
        }
        $arr = load_remote_calendar($calUser, $cal_url);
        if (empty($arr[0])) {
          // Success (or not updated)
          if (!empty($arr[3])) {
            $message = $arr[3];
          } else {
            $message = $arr[1] . ' ' . translate('events added') . ', ' . $arr[2] . ' ' . translate('events deleted');
          }
        } else {
          // Error
          $error = $arr[3];
        }
      }
    }
    dbi_free_result($res);
  }
  if ($cnt == 0)
    echo "<br />\n" . translate('No Remote Calendars found');
} else {
  echo "<br />\n" . translate('Remote Calendars not enabled');
}

?>
