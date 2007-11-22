<?php
/* $Id$
 *
 * Description
 * This is the handler for Ajax httpXmlRequests.
 */
require_once 'includes/classes/WebCalendar.class.php';

$WC =& new WebCalendar ( __FILE__ );

include 'includes/translate.php';
include 'includes/config.php';
include 'includes/dbi4php.php';
include 'includes/functions.php';

$WC->initializeFirstPhase();

include 'includes/access.php';

$WC->initializeSecondPhase();

$WC->setLanguage();

$datepicker = false;
$filename = $WC->getPOST ( 'filename', false );
$initPHP = $WC->getPOST ( 'initPHP', false );
$name = $WC->getPOST ( 'name' );
$page = $WC->getValue ( 'page' );
// ,
// We're processing edit_remotes Calendar ID field.
if ( $page == 'edit_remotes' || $page == 'edit_nonuser' ) {
  $res = dbi_execute ( 'SELECT cal_login FROM webcal_user
    WHERE cal_login = ?', array ( _WC_NONUSER_PREFIX . $name ) );
  if ( $res ) {
    $row = dbi_fetch_row ( $res );
    // Presuming we are using '_NUC_' as _WC_NONUSER_PREFIX.
    if ( $name == substr ( $row[0], strlen ( _WC_NONUSER_PREFIX ) ) )
      echo str_replace ( 'XXX', $name,
        translate ( 'Duplicate Name XXX', true ) );
  }
} elseif ( $page == 'register' || $page == 'edit_user' ) {
  // We're processing username field.
  $res = dbi_execute ( 'SELECT cal_login FROM webcal_user WHERE cal_login = ?',
    array ( $name ) );
  if ( $res ) {
    $row = dbi_fetch_row ( $res );
    if ( $row[0] == $name )
      echo str_replace ( 'XXX', $name,
        translate ( 'Username XXX already exists.', true ) );
  }
} elseif ( $page == 'email' ) {
  // We're processing email field from any page field.
  $res = dbi_execute ( 'SELECT cal_email FROM webcal_user
    WHERE cal_email = ?', array ( $name ) );
  if ( $res ) {
    $row = dbi_fetch_row ( $res );
    if ( $row[0] == $name )
      echo str_replace ( 'XXX', $name,
        translate ( 'Email address XXX already exists.', true ) );
  }
} elseif ( $page == 'minitask' ) {
  $name = ( empty ( $name ) ? 0 : $name );
  require_once 'includes/classes/Event.class.php';
  require_once 'includes/classes/RptEvent.class.php';
  require_once 'includes/gradient.php';
  $column_array = array ( 'we.cal_priority', 'we.cal_name',
    'we.cal_due_date', 'weu.cal_percent' );
  $task_filter = ' ORDER BY ' . $column_array[$name % 4]
   . ( $name > 3 ? ' ASC' : ' DESC' );
  echo display_small_tasks ( $WC->catId() );
} elseif ( $page == 'initPHP' ) {
  // Do some generic translation first.
  $ret = 'invalidColor = "' . translate ( 'Invalid Color', true ) . '";
    Error = "' . translate ( 'Error', true ) . '";
    colorFormat = "' . translate ( 'Color format should be #RRGGBB', true ) . '";
    viewEventStr = "' . translate ( 'View this event' ) . '";
    editEventStr = "' . translate ( 'Edit this event' ) . '";
    viewTaskStr = "' . translate ( 'View this task' ) . '";
    editTaskStr = "' . translate ( 'Edit this task' ) . '";
    approveStr = "' . translate ( 'Approve' ) . '";
    rejectStr = "' . translate ( 'Reject' ) . '";
    deleteStr = "' . translate ( 'Delete' ) . '";
    invalidDate = "' . translate ( 'Invalid Date', true ) . '";
    ';
  if ( $filename == 'export_import' )
    $ret .= 'fileType = "'
     . translate ( 'File type does not match Import Format.', true ) . '";';
  elseif ( $filename == 'calendar' ) {
    $ret .= 'doubleClick = "'
     . translate ( 'Double-click on empty cell to add new entry.', true ) . '";
     User = "' . translate ( 'User', true ) . '";
     Time = "' . translate ( 'Time', true ) . '";
     Summary = "' . translate ( 'Summary', true ) . '";
     Participants = "' . translate ( 'Participants', true ) . '";
     Description = "' . translate ( 'Description', true ) . '";
     SiteExtras = "' . translate ( 'Site Extras', true ) . '";
     Location = "' . translate ( 'Location', true ) . '";
     Reminder = "' . translate ( 'Reminder', true ) . '";
     editAllDates = "' . translate ( 'editAllDates' ) . '";
     deleteAllDates = "' . translate ( 'deleteAllDates' ) . '";
     editThisDate = "' . translate ( 'editThisDate' ) . '";
     deleteOnly = "' . translate ( 'deleteOnly' ) . '";
     cat_id = "' . $WC->catId() . '";
     DISPLAY_TASKS = "' . getPref ( 'DISPLAY_TASKS' ) . '";
     TIME_SPACER = "' . getPref ( 'TIME_SPACER' ) . '";';
  } elseif ( $filename == 'edit_layer' )
    $ret .= 'ruSure = "' . translate ( 'ruSureLayer', true ) . '";';
  elseif ( $filename == 'colors' )
    $ret .= 'CUSTOM_COLORS = "' . getPref ( 'CUSTOM_COLORS' ) . '";';
  elseif ( $filename == 'edit_user' ) {
    $ret .= 'noName = "'
     . translate ( 'Username can not be blank.', true ) . '";
   noPassword = "'
     . translate ( 'You have not entered a password.', true ) . '";
   diffPassword = "'
     . translate ( 'The passwords were not identical.', true ) . '";';
  } elseif ( $filename == 'edit_remotes' )
    $ret .= 'blankUrl = "' . translate ( 'URL can not be blank.', true ) . '";';
  elseif ( $filename == 'edit_nonuser' ) {
    $ret .= 'blankID = "'
     . translate ( 'Calendar ID can not be blank.', true ) . '";
   blankNames = "'
     . translate ( 'First and last names cannot both be blank.', true ) . '";';
  } elseif ( $filename == 'list_unapproved' ) {
    $ret .= 'appEntry = "' . translate ( 'Approve this entry?', true ) . '";
    rejEntry = "' . translate ( 'Reject this entry?', true ) . '";
    confirmDel = "' . translate ( 'Delete this entry?', true ) . '";
    appSelected = "' . translate ( 'Approve Selected entries?', true ) . '";
    rejSelected = "' . translate ( 'Reject Selected entries?', true ) . '";';
  } elseif ( $filename == 'matrix' ) {
    $ret .= 'schedStr = "'
     . translate ( 'Schedule an appointment for', true ) . ' ";
    chgStr = "'
     . translate ( 'Change the time for this appointment to', true ) . ' ";
    chgConfirmStr = "'
     . translate ( 'Change the date and time of this entry?', true ) . ' ";
    viewStr = "' . translate ( 'View this entry', true ) . '";
    timeFormat = ' . getPref ( 'TIME_FORMAT' ) . ';';
  } elseif ( $filename == 'admin' || $filename == 'pref' ) {
    $invStr = translate ( 'Invalid color for', true ) . ' ';
    $tlbCellBG = $invStr . translate ( 'Table cell background', true ) . ' ';
    // Forces styles.php to use values from webcal_config table.
    $ret .= ( $filename == 'admin' ? 'CSS_COLOR_FROM_CONFIG = "true";' : '' )
     . ( $filename == 'pref' ? '_ALLOW_COLOR_CUSTOMIZATION = "'
       . getPref ( '_ALLOW_COLOR_CUSTOMIZATION' ) . '";' : '' ) . '
    SERVER_URL = "' . translate ( 'Server URL is required.', true ) . '";
    SERVER_URL_END = "'
     . translate ( 'Server URL must end with /.', true ) . '.";
    WORK_DAY_END_HOUR = "' . translate ( 'Invalid work hours', true ) . '";
    BGCOLOR = "' . $invStr . translate ( 'Document background', true ) . '";
    H2COLOR = "' . $invStr . translate ( 'Document title', true ) . '";
    TEXTCOLOR = "' . $invStr . translate ( 'Document text', true ) . '";
    MYEVENTS = "' . $invStr . translate ( 'My event text', true ) . '";
    TABLEBG = "' . $invStr . translate ( 'Table grid', true ) . '";
    THBG = "' . $invStr . translate ( 'Table header background', true ) . '";
    THFG = "' . $invStr . translate ( 'Table header text', true ) . '";
    CELLBG = "' . $tlbCellBG . '";
    TODAYCELLBG = "' . $tlbCellBG . translate ( 'for current day', true ) . '";
    HASEVENTSBG = "' . $tlbCellBG
     . translate ( 'for days with events', true ) . '";
    WEEKENDBG = "' . $tlbCellBG . translate ( 'for weekends', true ) . '";
    OTHERMONTHBG = "' . $tlbCellBG . translate ( 'for other month', true ) . '";
    WEEKNUMBER = "' . $invStr . translate ( 'Week number', true ) . '";
    POPUP_BG = "' . $invStr . translate ( 'Event popup background', true ) . '";
    POPUP_FG = "' . $invStr . translate ( 'Event popup text', true ) . '";';
  } elseif ( $filename == 'edit_entry' ) {
    $datepicker = true;
    $ret .= '_ENABLE_GROUPS = "' . getPref ( '_ENABLE_GROUPS', 2 ) . '";
    WORK_DAY_START_HOUR = "' . getPref ( 'WORK_DAY_START_HOUR' ) . '";
    WORK_DAY_END_HOUR = "' . getPref ( 'WORK_DAY_END_HOUR' ) . '";
    TIME_FORMAT = "' . getPref ( 'TIME_FORMAT' ) . '";
    _EVENT_EDIT_TABS = "' . getPref ( '_EVENT_EDIT_TABS' ) . '";
    SU = "' . translate ( 'SU' ) . '";
    MO = "' . translate ( 'MO' ) . '";
    TU = "' . translate ( 'TU' ) . '";
    WE = "' . translate ( 'WE' ) . '";
    TH = "' . translate ( 'TH' ) . '";
    FR = "' . translate ( 'FR' ) . '";
    SA = "' . translate ( 'SA' ) . '";
    blankSummary = "'
     . translate ( 'You have not entered a Brief Description.', true ) . '";
    invalidExceptDate = "' . translate ( 'Invalid Exception Date', true ) . '";
    noPart = "' . translate ( 'Please add a participant.', true ) . '";
    invalidTime = "'
     . translate ( 'You have not entered a valid time of day.', true ) . '";
    startTime = "'
     . translate ( 'time entered before preferred work hours...', true ) . '";';
  }
  // These values needed if datepicker is used.
  if ( $datepicker ) {
    $ret .= 'DATE_FORMAT = "' . translate ( '__mm__/__dd__/__yyyy__' ) . '";
    mn = new Array( ';
    for ( $i = 0; $i < 12; $i++ ) {
      $ret .= "'" . month_name ( $i, 'M' ) . "'" . ( $i < 11 ? ',' : '' );
    }
    $ret .= ' );dn = "';
    for ( $i = 0; $i < 7; $i++ ) {
      $ret .= '<td>'
       . substr ( translate ( $WC->byday_names[$i] ), 0, 1 ) . '</td>';
    }
    $ret .= '";';
  }
  echo $ret;
} elseif ( $page == 'edit_entry_groups' ) {
  if ( getPref ( '_ENABLE_GROUPS', 2 ) ) {
    $groups = get_groups();
    $groupmembers = '{"groups":[';
    for ( $i = 0, $cnt = count ( $groups ); $i < $cnt; $i++ ) {
      $res = dbi_execute ( 'SELECT cal_login_id from webcal_group_user
          WHERE cal_group_id = ?', array ( $groups[$i]['cal_group_id'] ) );
      if ( $res ) {
        while ( $row = dbi_fetch_row ( $res ) ) {
          $groupmembers .= '{"grp":"' . $i . '","id":"' . $row[0] . '"},';
        }
        dbi_free_result ( $res );
      }
      $groupmembers =
      substr ( $groupmembers, 0, strlen ( $groupmembers ) - 1 ) . ']}';
    }
    $JSON = $groupmembers;
  }
  echo $JSON;
} elseif ( $page == 'initMENU' ) {
} elseif ( $page == 'initJS2PHP' ) {
  // We can now get browser attributes and store them in the db YeeHaw!!
  dbi_execute ( 'DELETE FROM webcal_user_pref WHERE cal_login_id = ?
    AND ( cal_setting = \'SCREEN_WIDTH\' OR cal_setting = \'SCREEN_HEIGHT\' )',
    array ( $WC->loginId() ) );
  dbi_execute ( 'INSERT INTO webcal_user_pref ( cal_login_id, cal_setting,
    cal_value ) VALUES ( ?, ?, ? )',
    array ( $WC->loginId(), 'SCREEN_WIDTH', $WC->getPOST ( 'sw' ) ) );
  dbi_execute ( 'INSERT INTO webcal_user_pref ( cal_login_id, cal_setting,
    cal_value ) VALUES ( ?, ?, ? )',
    array ( $WC->loginId(), 'SCREEN_HEIGHT', $WC->getPOST ( 'sh' ) ) );
  echo true;
} elseif ( $page == 'setPref' ) {
  $setting = $WC->getPOST ( 'setting' );
  $value = $WC->getPOST ( 'value' );
  dbi_execute ( 'DELETE FROM webcal_user_pref WHERE cal_login_id = ?
    AND cal_setting = ?', array ( $WC->loginId(), $setting ) );
  dbi_execute ( 'INSERT INTO webcal_user_pref ( cal_login_id, cal_setting,
    cal_value ) VALUES ( ?, ?, ? )',
    array ( $WC->loginId(), $setting, $value ) );
} elseif ( $page == 'getPref' ) {
  $control = $WC->getPOST ( 'control' );
  $setting = $WC->getPOST ( 'setting' );
  $ret = getPref ( $setting, $control );
  echo $ret;
}

?>
