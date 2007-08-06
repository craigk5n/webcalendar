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

$name = $WC->getPOST ( 'name' );
$page = $WC->getValue ( 'page' );

$initPHP = $WC->getPOST ( 'initPHP', false );
$filename = $WC->getPOST ( 'filename', false );
// We're processing edit_remotes Calendar ID field.
if ( $page == 'edit_remotes' || $page == 'edit_nonuser' ) {
  $res = dbi_execute ( 'SELECT cal_login FROM webcal_user
    WHERE cal_login = ?', array ( _WC_NONUSER_PREFIX . $name ) );
  if ( $res ) {
    $row = dbi_fetch_row ( $res );
    // Presuming we are using '_NUC_' as _WC_NONUSER_PREFIX.
    if ( $name == substr ( $row[0], strlen ( _WC_NONUSER_PREFIX ) ) )
      // translate ( 'Duplicate Name' )
      echo str_replace ( 'XXX', $name, translate ( 'Duplicate Name XXX', true ) );
  }
} elseif ( $page == 'register' || $page == 'edit_user' ) {
  // We're processing username field.
  $res = dbi_execute ( 'SELECT cal_login FROM webcal_user WHERE cal_login = ?',
    array ( $name ) );
  if ( $res ) {
    $row = dbi_fetch_row ( $res );
    // translate ( 'Username already exists' )
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
    // translate ( 'Email address already exists' )
    if ( $row[0] == $name )
      echo str_replace ( 'XXX', $name,
        translate ( 'Email address XXX already exists', true ) );
  }
} elseif ( $page == 'minitask' ) {
  $name = ( ! empty ( $name ) ? $name : 0 );
  require_once 'includes/classes/Event.class.php';
  require_once 'includes/classes/RptEvent.class.php';
  $column_array = array ( 'we.cal_priority', 'we.cal_name', 
    'we.cal_due_date', 'weu.cal_percent' );
  $task_filter = ' ORDER BY ' . $column_array[$name % 4] . 
    ( $name > 3 ? ' ASC' : ' DESC' );
  echo display_small_tasks ( $WC->catId() );
} elseif ( $page == 'initPHP' ) {
  //do some generic translation first
  $ret = 'invalidColor = "' . translate ( 'Invalid Color', true ) . '";
    Error = "' . translate ( 'Error', true ) . '";
    colorFormat = "' . 
    translate ( 'Color format should be &#39;#RRGGBB&#39;', true ) . '";
	';
  if ( $filename == 'export_import' ) {
     $ret .= 'fileType = "' . 
	   translate ( 'File type does not match Import Format', true ) . '";';
  } elseif ( $filename == 'edit_layer' ) {
     $ret .= 'ruSure = "' . str_replace ( 'XXX', translate ( 'layer', true ),
      translate ( 'Are you sure you want to delete this XXX?', true ) ) . '";';
  } elseif ( $filename == 'edit_user' ) {
     $ret .= 'noName = "' . translate ( 'Username can not be blank', true ) . '";
	 noPassword = "' . 
	   translate ( 'You have not entered a password', true ) . '";
	 diffPassword = "' . 
	   translate ( 'The passwords were not identical', true ) . '";';
  } elseif ( $filename == 'edit_remotes' ) {
     $ret .= 'blankUrl = "' . translate ( 'URL can not be blank', true ) . '";';
  } elseif ( $filename == 'edit_nonuser' ) {
     $ret .= 'blankID = "' . translate ( 'Calendar ID can not be blank', true ) . '";
	 blankNames = "' . 
	   translate ( 'Both first and last names can not be blank', true ) . '";';
	} elseif ( $filename == 'list_unapproved' ) {
		$ret .= 'appEntry = "' . translate( 'Approve this entry?', true) . '";
		rejEntry = "' . translate( 'Reject this entry?', true) . '";
		confirmDel = "' . translate( 'Delete this entry?', true) . '";
		appSelected = "' . translate( 'Approve Selected entries?', true) . '";
		rejSelected = "' . translate( 'Reject Selected entries?', true) . '";';
  } elseif ( $filename == 'admin' || $filename == 'pref' ) {
    $invStr = translate ( 'Invalid color for' , true ) . ' ';
		//forces styles.php to use values from webcal_config table
		if ( $filename == 'admin' )
      $ret .= 'CSS_COLOR_FROM_CONFIG = "true";'; 
    if ( $filename == 'pref' )
      $ret .= 'ALLOW_COLOR_CUSTOMIZATION = "' . 
	  getPref ( 'ALLOW_COLOR_CUSTOMIZATION' ) . '";';   
    $ret .= 'SERVER_URL = "' . 
	   translate ( 'Server URL is required', true ) . '";
	 SERVER_URL_END = "' . 
	   translate ( 'Server URL must end with &quot;/&quot;.', true ) . '";
	 WORK_DAY_END_HOUR = "' . 
	   translate ( 'Invalid work hours', true ) . '";
	 BGCOLOR = "' . $invStr .
	   translate ( 'Document background', true ) . '";
	 H2COLOR = "' . $invStr .
	   translate ( 'Document title', true ) . '";
	 TEXTCOLOR = "' . $invStr .
	   translate ( 'Document text', true ) . '";
	 MYEVENTS = "' . $invStr .
	   translate ( 'My event text', true ) . '";
     TABLEBG = "' . $invStr .
	   translate ( 'Table grid', true ) . '";
     THBG = "' . $invStr .
	   translate ( 'Table header background', true ) . '";
     THFG = "' . $invStr .
	   translate ( 'Table header text', true ) . '";
     CELLBG = "' . $invStr .
	   translate ( 'Table cell background', true ) . '";
     TODAYCELLBG = "' . $invStr .
	   translate ( 'Table cell background for current day', true ) . '";
     HASEVENTSBG = "' . $invStr .
	   translate ( 'Table cell background for days with events', true ) . '";
     WEEKENDBG = "' . $invStr .
	   translate ( 'Table cell background for weekends', true ) . '";
     OTHERMONTHBG = "' . $invStr .
	   translate ( 'Table cell background for other month', true ) . '";
     WEEKNUMBER = "' . $invStr .
	   translate ( 'Week number', true ) . '";
     POPUP_BG = "' . $invStr .
	   translate ( 'Event popup background', true ) . '";
     POPUP_FG = "' . $invStr .
	   translate ( 'Event popup text', true ) . '";';
  } elseif ( $filename == 'edit_entry' ) {
    $ret .= 'GROUPS_ENABLED = "' .  getPref ( 'GROUPS_ENABLED' ) . '";
	  WORK_DAY_START_HOUR = "' . getPref ( 'WORK_DAY_START_HOUR' ) . '";
	  WORK_DAY_END_HOUR = "' . getPref ( 'WORK_DAY_END_HOUR' ) . '";
	  TIME_FORMAT = "' . getPref ( 'TIME_FORMAT' ) . '";
	  EVENT_EDIT_TABS = "' . getPref ( 'EVENT_EDIT_TABS' ) . '";
	  SU = "' . translate ( 'SU' ) . '";
	  MO = "' . translate ( 'MO' ) . '";
	  TU = "' . translate ( 'TU' ) . '";	
	  WE = "' . translate ( 'WE' ) . '";	
	  TH = "' . translate ( 'TH' ) . '";	
	  FR = "' . translate ( 'FR' ) . '";	
	  SA = "' . translate ( 'SA' ) . '";
	  blankSummary = "' . 
	    translate( 'You have not entered a Brief Description', true) . '";
	  invalidEventDate = "' . 
	    translate ( 'Invalid Event Date', true) . '";
	  invalidExceptDate = "' . 
	    translate ( 'Invalid Exception Date', true) . '";
	  noPart = "' .
	    translate( 'Please add a participant', true) . '";
	  invalidTime = "' .
	    translate ( 'You have not entered a valid time of day', true) . '";
	  startTime = "' . 
	    translate ( 'The time you have entered begins before your preferred work hours.  Is this correct?', true) . '";';
  }
  echo $ret;
} elseif ( $page == 'initMENU' ) {

} elseif ( $page == 'initJS2PHP' ) {
  //we can now get browser attributes and store them in the db YeeHaw!!
  $screenwidth = $WC->getPOST ( 'sw' );
  $screenheight = $WC->getPOST ( 'sh' );
  dbi_execute ( 'DELETE FROM webcal_user_pref WHERE cal_login_id = ?
    AND cal_setting = \'SCREEN_WIDTH\'', array ( $WC->loginId() ) );
  dbi_execute ( 'DELETE FROM webcal_user_pref WHERE cal_login_id = ?
    AND cal_setting = \'SCREEN_HEIGHT\'', array ( $WC->loginId() ) );  
  dbi_execute ( 'INSERT INTO webcal_user_pref ( cal_login_id, cal_setting,
     cal_value ) VALUES ( ?, ?, ? )', 
	 array ( $WC->loginId(), 'SCREEN_WIDTH', $screenwidth ) );
  dbi_execute ( 'INSERT INTO webcal_user_pref ( cal_login_id, cal_setting,
     cal_value ) VALUES ( ?, ?, ? )', 
	 array ( $WC->loginId(), 'SCREEN_HEIGHT', $screenheight ) );
  echo true;
}
?>
