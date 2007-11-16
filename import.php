<?php
/*
 * $Id$
 *
 * Page Description:
 * This page will present the user with forms for submitting
 * a data file to import.
 *
 * Input Parameters:
 * None
 *
 * Comments:
 * Might be nice to allow user to set the category for all imported
 * events.  So, a user could easily export events from the work
 * calendar and import them into WebCalendar with a category
 * "work".
 */
include_once 'includes/init.php';

if ( ! empty ( $_POST ) ) {

  include_once 'includes/xcal.php';
	
  $errormsg = $sqlLog = $BodyX = $INC = '';
  $overwrite = $WC->getPost ( 'overwrite' );
  $ImportType = $WC->getPost ( 'ImportType' );
  $doOverwrite = ( empty ( $overwrite ) || $overwrite != 'Y' ) ? false : true;
  $numDeleted = 0;

  if ( ! empty ( $_FILES['FileName'] ) )
    $file = $_FILES['FileName'];

  if ( empty ( $file ) )
    $errormsg = translate ( 'No file' );

  // Handle user
  $calUser = $WC->getValue ( 'calUser' );
  if ( ! _WC_SINGLE_USER && ! $WC->isAdmin() )
    $calUser = $WC->loginId();
  
  if ( $file['size'] > 0 ) {
    switch ( $ImportType ) {

    // ADD New modules here:
    /*
    case 'MODULE':
      include "import_module.php";
      $data = parse_module ( $_FILES['FileName']['tmp_name'] );
      break;
    */

    case 'PALMDESKTOP':
      include 'import_palmdesktop.php';
      if ( delete_palm_events ( $WC->loginId() ) != 1 )
        $errormsg = translate ( 'Error deleting palm events from webcalendar.' );
      $data = parse_palmdesktop ( $file['tmp_name'], $exc_private );
      $type = 'palm';
      break;

    case 'VCAL':
      $data = parse_vcal ( $file['tmp_name'] );
      $type = 'vcal';
      break;

    case 'ICAL':
      $data = parse_ical ( $file['tmp_name'] );
      $type = 'ical';
      break;

    case 'OUTLOOKCSV':
      include 'import_outlookcsv.php';
      $data = parse_outlookcsv ( $file['tmp_name'] );
      $type = 'outlookcsv';
      break;
    }
    $count_con = $count_suc = $error_num = 0;
    if ( ! empty ( $data ) && empty ( $errormsg ) ) {
      $importmsg = import_data ( $data, $doOverwrite, $type );
      $smarty->assign ( 'importmsg', $importmsg );
      
			$smarty->assign ( 'handler', true );
      $smarty->assign ( 'count_con', $count_con );
      $smarty->assign ( 'count_suc', $count_suc );
      $smarty->assign ( 'error_num', $error_num );
      $smarty->assign ( 'handler', true );
	    $smarty->assign ( 'data', true );
	  } else if ( empty ( $data ) ) {
		  $errormsg = translate ( 'There was an error parsing the import file or no events were returned' );
		}
	
	} else { //Filesize = 0
	  $errormsg = translate ( 'The import file contained no data' );
	}	
	$smarty->assign ( 'errormsg', $errormsg );
} else { //Present Import Form

  $BodyX = 'onload="toggle_import();"';
  $INC = array('export_import.js');
	

  // Generate the selection list for calendar user selection.
  // Only ask for calendar user if user is an administrator.
  // We may enhance this in the future to allow
  // - selection of more than one user
  // - non-admin users this functionality
  if ( ! _WC_SINGLE_USER && $WC->isAdmin() ) {
	  $userlist = $WC->User->getUsers ();
	  if ( getPref ( 'NONUSER_ENABLED' ) ) {
	  	$nonusers = get_nonuser_cals ();
		  $userlist = ( getPref ( 'NONUSER_AT_TOP' ) ) ?
		  	array_merge($nonusers, $userlist) : array_merge($userlist, $nonusers);
	  }
	  $size = 0;
	  $users = array();
	  for ( $i = 0, $cnt = count ( $userlist ); $i < $cnt; $i++ ) {
		  $l = $userlist[$i]['cal_login_id'];
		  $size++;
		  $users[$l]['fullname'] = $userlist[$i]['cal_fullname'];
		  if ( $WC->isLogin( $l ) && ! $WC->isNonuserAdmin() )
		  	$users[$l]['selected'] = SELECTED;
	  }

	  if ( $size > 50 )
		  $size = 15;
	  else if ( $size > 5 )
		  $size = 5;
	}
	
  $smarty->assign ( 'users', $users );
  $smarty->assign ( 'size', $size );
	
}

$upload = ini_get ( 'file_uploads' );
$upload_enabled = ! empty ( $upload ) &&
  preg_match ( "/(On|1|true|yes)/i", $upload );

$smarty->assign ( 'upload_enabled', $upload_enabled );

build_header ($INC, '', $BodyX);

$smarty->display ( 'import.tpl' );
 
?>
