<?php
/* $Id: edit_report_handler.php,v 1.32.2.8 2012/02/28 02:07:45 cknudsen Exp $
 *
 * Page Description:
 * This page will handle the form submission from edit_report.php
 * and either add, update or delete a report.
 *
 * Input Parameters:
 * report_id (optional) - the report id of the report to edit.
 *                        If blank, user is adding a new report.
 * public (optional) -    If set to '1' and user is an admin user,
 *                        then we are creating a report for the public user.
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
 * If system setting $REPORTS_ENABLED is set to anything other than 'Y',
 * then don't allow access to this page.
 * If $ALLOW_VIEW_OTHER is 'N', then do not allow selection of participants.
 * Can only delete/edit an event if you are the creator of the event
 * or you are an admin user.
 */
include_once 'includes/init.php';
require_valide_referring_url ();
load_user_categories ();

$error = ( empty ( $REPORTS_ENABLED ) || $REPORTS_ENABLED != 'Y'
  ? print_not_auth (12) : '' );
$report_id = getValue ( 'report_id', '-?[0-9]+', true );
$public = getPostValue ( 'public' );
$report_name = getPostValue ( 'report_name' );
$report_user = getPostValue ( 'report_user' );
$time_range = getPostValue ( 'time_range' );
$cat_id = getValue ( 'cat_id', '-?[0-9,\-]*', true );
$page_template = getPostValue ( 'page_template' );
$day_template = getPostValue ( 'day_template' );
$event_template = getPostValue ( 'event_template' );
$delete = getPostValue ( 'delete' );
$updating_public = ( $is_admin && ! empty ( $public ) && $PUBLIC_ACCESS == 'Y' );

if ( $single_user == 'Y' || $DISABLE_PARTICIPANTS_FIELD == 'Y' )
  $report_user = '';

if ( ! $is_admin )
  $is_global = 'N';

$adding_report = ( empty ( $report_id ) || $report_id <= 0 );

// Check permissions.
// Can only edit/delete if you created the event or you are an admin.
if ( empty ( $error ) && $single_user != 'N' && !
    empty ( $report_id ) && $report_id > 0 && ! $is_admin ) {
  $res = dbi_execute ( 'SELECT cal_login FROM webcal_report WHERE report_id = ?',
    array ( $report_id ) );
  if ( $res ) {
    if ( $row = dbi_fetch_row ( $res ) ) {
      if ( $row[0] != $login )
        $error = print_not_auth (5);
    } else
      $error = str_replace ( 'XXX', $report_id,
        translate ( 'No such report id XXX.' ) );

    dbi_free_result ( $res );
  } else
    $error = db_error ();
}

// Validate templates to make sure the required variables are found.
// Page template must include ${days}.
if ( empty ( $error ) ) {
  $errStr = '
    <p>' . translate ( 'Error' ) . ' [';
  // translate ( 'Variable N not found' )
  $noVarXXX = ']: ' . translate ( 'Variable XXX not found.' ) . '</p>';
  if ( ! strstr ( $page_template, '${days}' ) )
    $error .= $errStr . translate ( 'Page template' )
     . str_replace ( 'XXX', '${days}', $noVarXXX );

  // Day template must include ${events}.
  if ( ! strstr ( $day_template, '${events}' ) )
    $error .= $errStr . translate ( 'Day template' )
     . str_replace ( 'XXX', '${events}', $noVarXXX );

  // Event template must include ${name}.
  if ( ! strstr ( $event_template, '${name}' ) )
    $error .= $errStr . translate ( 'Event template' )
     . str_replace ( 'XXX', '${name}', $noVarXXX );
}
$delete = getPostValue ( 'delete' );
if ( empty ( $error ) && ! empty ( $report_id ) && ! empty ( $delete ) ) {
  if ( ! dbi_execute ( 'DELETE FROM webcal_report_template WHERE cal_report_id = ?',
      array ( $report_id ) ) )
    $error = db_error ();

  if ( empty ( $error ) && ! dbi_execute ( 'DELETE FROM webcal_report
    WHERE cal_report_id = ?', array ( $report_id ) ) )
    $error = db_error ();
  // Send back to main report listing page.
  if ( empty ( $error ) )
    do_redirect ( 'report.php' );
}

if ( empty ( $error ) ) {
  if ( empty ( $report_name ) || trim ( $report_name ) == '' )
    $report_name = translate ( 'Unnamed Report' );

  $names = array ( 'cal_login', 'cal_update_date', 'cal_report_type',
    'cal_report_name', 'cal_user', 'cal_include_header', 'cal_time_range',
    'cal_cat_id', 'cal_allow_nav', 'cal_include_empty', 'cal_is_global',
    'cal_show_in_trailer' );

  $values = array (
    ( $updating_public ? '__public__' : $login ),
    date ( 'Ymd' ),
    'html',
    $report_name,
    ( ! $is_admin || empty ( $report_user ) ? null : $report_user ),
    ( empty ( $include_header ) || $include_header != 'Y' ? 'N' : 'Y' ),
    ( isset ( $time_range ) ? $time_range : 11 ),
    ( empty ( $cat_id ) ? null : $cat_id ),
    ( empty ( $allow_nav ) || $allow_nav != 'Y' ? 'N' : 'Y' ),
    ( empty ( $include_empty ) || $include_empty != 'Y' ? 'N' : 'Y' ),
    ( empty ( $is_global ) || $is_global != 'Y' ? 'N' : 'Y' ),
    ( empty ( $show_in_trailer ) || $show_in_trailer != 'Y' ? 'N' : 'Y' ) );

  if ( $adding_report ) {
    $newid = 1;
    $res = dbi_execute ( 'SELECT MAX( cal_report_id ) FROM webcal_report' );
    if ( $res ) {
      if ( $row = dbi_fetch_row ( $res ) )
        $newid = $row[0] + 1;

      dbi_free_result ( $res );
    }
    $names[] = 'cal_report_id';
    $values[] = $newid;

    $sql = 'INSERT INTO webcal_report ( ';
    $sql_v = '';

    $namecnt = count ( $names );
    for ( $i = 0; $i < $namecnt; $i++ ) {
      $sql .= ( $i > 0 ? ', ' : '' ) . $names[$i];
      $sql_v .= ( $i > 0 ? ', ' : '' ) . '?';
    }
    $sql .= ' ) VALUES ( ' . $sql_v . ' )';
    $report_id = $newid;
  } else {
    $sql = 'UPDATE webcal_report SET ';
    $namecnt = count ( $names );
    for ( $i = 0; $i < $namecnt; $i++ ) {
      $sql .= ( $i > 0 ? ', ' : '' ) . "$names[$i] = ?";
    }
    $sql .= ' WHERE cal_report_id = ?';
    $values[] = $report_id; // Push the $report_id to $values.
  }
}

if ( empty ( $error ) && ! dbi_execute ( $sql, $values ) )
  $error = db_error ();

if ( empty ( $error ) ) {
  if ( ! $adding_report ) {
    if ( ! dbi_execute ( 'DELETE FROM webcal_report_template
      WHERE cal_report_id = ?', array ( $report_id ) ) )
      $error = db_error ();
  }
  $ins_sql = 'INSERT INTO webcal_report_template
    ( cal_report_id, cal_template_type, cal_template_text ) VALUES ( ?, ?, ? )';

  if ( empty ( $error ) && ! dbi_execute ( $ins_sql,
        array ( $report_id, 'D', $day_template ) ) )
    $error = db_error ();

  if ( empty ( $error ) && ! dbi_execute ( $ins_sql,
        array ( $report_id, 'E', $event_template ) ) )
    $error = db_error ();

  if ( empty ( $error ) && ! dbi_execute ( $ins_sql,
        array ( $report_id, 'P', $page_template ) ) )
    $error = db_error ();
}

if ( empty ( $error ) ) {
  do_redirect ( 'report.php' . ( $updating_public ? '?public=1' : '' ) );
  exit;
}

print_header ();
echo print_error ( $error ) . print_trailer ();

?>
