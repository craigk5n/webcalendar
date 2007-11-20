<?php
/* $Id$
 *
 * Page Description:
 * This page will handle the form submission from edit_report.php
 * and either add, update or delete a report.
 *
 * Input Parameters:
 * report_id (optional) - the report id of the report to edit.
 *   If blank, user is adding a new report.
 * public (optional) - If set to '1' and user is an admin user,
 *   then we are creating a report for the public user.
 * report_name
 * report_user
 * is_global (Y or N)
 * include_header (Y or N)
 * time_range
 * cat_id
 * allow_nav
 * include_empty
 * show_in_trailer
 * delete (if 'delete' button pressed)
 * page_template
 * day_template
 * event_template
 *
 * Security:
 * Same as in edit_report.php...
 * If system setting _ENABLE_REPORTS is set to anything other than
 *   'Y', then don't allow access to this page.
 * If _ALLOW_VIEW_OTHER is 'N', then do not allow selection of
 *   participants.
 * Can only delete/edit an event if you are the creator of the event
 *   or you are an admin user.
 */
include_once 'includes/init.php';

$error = '';
$report_id = $WC->getValue ( 'report_id', '-?[0-9]+', true );

if ( ! getPref ( '_ENABLE_REPORTS', 2 ) ) {
  $error = print_not_auth () . '.';
}

if ( _WC_SINGLE_USER || ! getPref ( '_ENABLE_PARTICIPANTS_FIELD' ) ) {
  $report_user = '';
}

if ( ! $WC->isAdmin() )
  $is_global = 'N';

$page_template = $WC->getPOST ( 'page_template' );
$day_template = $WC->getPOST ( 'day_template' );
$event_template = $WC->getPOST ( 'event_template' );

$report_id = $WC->getPOST ( 'report_id', 0 );
$adding_report = ( $report_id <= 0 );

// Check permissions
// Can only edit/delete if you created the event or your are an admin.
if ( empty ( $error ) && _WC_SINGLE_USER && ! empty ( $report_id ) &&
  $report_id > 0 && ! $WC->isAdmin() ) {
  $res = dbi_execute ( 'SELECT cal_login_id FROM webcal_report ' .
     'WHERE report_id = ?', array( $report_id ) );
  if ( $res ) {
    if ( $row = dbi_fetch_row ( $res ) ) {
      if ( ! $WC->isLogin( $row[0] ) ) {
        $error = print_not_auth ();
      }
    } else {
      $error = 'No such report id';
    }
    dbi_free_result ( $res );
  } else {
    $error = db_error ();
  }
}

// Validate templates to make sure the required variables are found.
// Page template must include ${days}
if ( empty ( $error ) ) {
  if ( ! strstr ( $page_template, '${days}' ) ) {
    $error = '<p>' . translate ( 'Error' ) . ' [' .
      translate ( 'Page template' ) . ']: ' .
      str_replace ( ' N ', ' <tt>${days}</tt> ',
        translate ( 'Variable N not found' ) ) .  '.';
  }
  // Day template must include ${events}
  if ( ! strstr ( $day_template, '${events}' ) ) {
    if ( ! empty ( $error ) )
      $error .= '</p>';
    $error .= '<p>' . translate ( 'Error' ) . ' [' .
      translate ( 'Day template' ) . ']: ' .
      str_replace ( ' N ', ' <tt>${events}</tt> ',
        translate ( 'Variable N not found' ) ) . '.';
  }
  // Event template must include ${name}
  if ( ! strstr ( $event_template, '${name}' ) ) {
    if ( ! empty ( $error ) )
      $error .= '</p>';
    $error .= '<p>' . translate ( 'Error' ) . ' [' .
      translate ( 'Event template' ) . ']: ' .
      str_replace ( ' N ', ' <tt>${name}</tt> ',
        translate ( 'Variable N not found' ) ) . '.';
  }
}
$delete = $WC->getPOST ( 'delete' );
if ( empty ( $error ) && ! empty ( $report_id ) && ! empty ( $delete ) ) {
  if ( ! dbi_execute ( 'DELETE FROM webcal_report_template ' .
    'WHERE cal_report_id = ?', array( $report_id ) ) )
    $error = db_error ();
  if ( empty ( $error ) &
    ! dbi_execute ( 'DELETE FROM webcal_report ' .
    'WHERE cal_report_id = ?', array( $report_id ) ) )
    $error = db_error ();
  // send back to main report listing page
  if ( empty ( $error ) )
    do_redirect ( 'report.php' );
}

if ( empty ( $error ) ) {
  $names = array ();
  $values = array ();

  $names[] = 'cal_login_id';
  $values[] = ( $WC->loginId() );

  $names[] .= 'cal_update_date';
  $values[] = date ( 'Ymd' );

  $names[] = 'cal_report_type';
  $values[] = 'html';

  $names[] = 'cal_report_name';
  if ( empty ( $report_name ) || trim ( $report_name ) == '' )
    $report_name = translate ( 'Unnamed Report' );
  $values[] = $report_name;

  $names[] = 'cal_user_id';
  if ( ! $WC->isAdmin() || empty ( $report_user ) ) {
    $values[] = NULL;
  } else {
    $values[] = $report_user;
  }

  $names[] = 'cal_include_header';
  if ( empty ( $include_header ) || $include_header != 'Y' ) {
    $values[] = 'N';
  } else {
    $values[] = 'Y';
  }

  $names[] = 'cal_time_range';
  $values[] = ( ! isset ( $time_range ) ? 11 : $time_range );

  $names[] = 'cal_cat_id';
  $values[] = $WC->catId();

  $names[] = 'cal_allow_nav';
  $values[] = ( empty ( $allow_nav ) || $allow_nav != 'Y' ) ? 'N' : 'Y';

  $names[] = 'cal_include_empty';
  $values[] = ( empty ( $include_empty ) || $include_empty != 'Y' ) ? 'N' : 'Y';

  $names[] = 'cal_is_global';
  $values[] = ( empty ( $is_global ) || $is_global != 'Y' ) ? 'N' : 'Y';

  $names[] = 'cal_show_in_trailer';
  $values[] = ( empty ( $show_in_trailer ) || $show_in_trailer != 'Y' ) ? 'N' : 'Y';

  if ( $adding_report ) {
    $res = dbi_execute ( 'SELECT MAX(cal_report_id) FROM webcal_report' );
    $newid = 1;
    if ( $res ) {
      if ( $row = dbi_fetch_row ( $res ) ) {
        $newid = $row[0] + 1;
      }
      dbi_free_result ( $res );
    }
    $names[] = 'cal_report_id';
    $values[] = $newid;
    $sql = 'INSERT INTO webcal_report ( ';
    $namecnt = count ( $names );
    for ( $i = 0; $i < $namecnt; $i++ ) {
      if ( $i > 0 )
        $sql .= ', ';
      $sql .= $names[$i];
    }
    $sql .= ' ) VALUES ( ';
    $valuecnt = count ( $values );
    for ( $i = 0; $i < $valuecnt; $i++ ) {
      if ( $i > 0 )
        $sql .= ', ';
      //$sql .= $values[$i];
    $sql .= '?';
    }
    $sql .= ' )';
    $report_id = $newid;
  } else {
    $sql = 'UPDATE webcal_report SET ';
    $namecnt = count ( $names );
    for ( $i = 0; $i < $namecnt; $i++ ) {
      if ( $i > 0 )
        $sql .= ', ';
      //$sql .= "$names[$i] = $values[$i]";
    $sql .= "$names[$i] = ?";
    }
    $sql .= ' WHERE cal_report_id = ?';
  $values[] = $report_id; // push the $report_id to $values

  }
  //echo "SQL: $sql"; exit;
}


if ( empty ( $error ) ) {
  if ( ! dbi_execute ( $sql, $values ) ) {
    $error = db_error ();
  }
}

if ( empty ( $error ) ) {
  if ( ! $adding_report ) {
    if ( ! dbi_execute ( 'DELETE FROM webcal_report_template ' .
      'WHERE cal_report_id = ?', array( $report_id ) ) )
      $error = db_error ();
  }
  if ( empty ( $error ) &&
    ! dbi_execute ( 'INSERT INTO webcal_report_template ' .
    '( cal_report_id, cal_template_type, cal_template_text ) VALUES ( ' .
    '?, ?, ? )', array( $report_id, 'P', $page_template ) ) )
    $error = db_error ();
  if ( empty ( $error ) &&
    ! dbi_execute ( 'INSERT INTO webcal_report_template ' .
    '( cal_report_id, cal_template_type, cal_template_text ) VALUES ( ' .
    '?, ?, ? )', array( $report_id, 'D', $day_template ) ) )
    $error = db_error ();
  if ( empty ( $error ) &&
    ! dbi_execute ( 'INSERT INTO webcal_report_template ' .
    '( cal_report_id, cal_template_type, cal_template_text ) VALUES ( ' .
    '?, ?, ? )', array( $report_id, 'E', $event_template ) ) )
    $error = db_error ();
}

if ( empty ( $error ) ) {
  do_redirect ( 'report.php' );
  exit;
}

build_header ();
echo print_error ( $error);
echo print_trailer(); 
?>

