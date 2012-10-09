<?php /* $Id$ */
include_once 'includes/init.php';
require_valide_referring_url();
load_user_layers();

if ( ! $is_admin ) {
  echo print_not_auth( true ) . '</body></html>';
  exit;
}
$error = '';

$add       = getPostValue ( 'Add' );
$delete    = getPostValue ( 'delete' );
$ispublic  = getPostValue ( 'ispublic' );
$nadmin    = getPostValue ( 'nadmin' );
$nfirstname= getPostValue ( 'nfirstname' );
$nid       = getPostValue ( 'nid' );
$nlastname = getPostValue ( 'nlastname' );
$old_admin = getPostValue ( 'old_admin' );
$save      = getPostValue ( 'Save' );

if ( empty ( $ispublic ) )
  $ispublic = 'N';

if ( ! empty ( $delete ) ) {
  // delete this nonuser calendar

  // Get event ids for all events this user is a participant
  $events = get_users_event_ids ( $nid );

  // TODO: Move a lot of this into a function. We could eliminate 8 or 8 copies.
  // Now count number of participants in each event...
  // If just 1, then save id to be deleted
  $delete_em = array();
  foreach ( $events as $i ) {
    $res = dbi_execute ( 'SELECT COUNT( * )
      FROM webcal_entry_user WHERE cal_id = ?', array ( $i ) );
    if ( $res ) {
      if ( $row = dbi_fetch_row ( $res ) ) {
        if ( $row[0] == 1 )
          $delete_em[] = $i;
      }
      dbi_free_result ( $res );
    }
  }
  // Now delete events that were just for this user
  foreach ( $delete_em as $i ) {
    foreach ( array (
        'webcal_blob',
        'webcal_entry',
        'webcal_entry_ext_user',
        'webcal_entry_repeats',
        'webcal_entry_repeats_not',
        'webcal_import_data',
        'webcal_reminders',
        'webcal_site_extras' ) as $db ) {
      dbi_execute ( 'DELETE FROM ' . $db . ' WHERE cal_id = ?', array ( $i ) );
    }
    dbi_execute ( 'DELETE FROM webcal_entry_log WHERE cal_entry_id = ?', array ( $i ) );
  }

  // Delete user participation from events
  dbi_execute ( 'DELETE FROM webcal_entry_user WHERE cal_login = ?',
    array ( $nid ) );

  // Delete any layers other users may have that point to this user.
  dbi_execute ( 'DELETE FROM webcal_user_layers WHERE cal_layeruser = ?',
    array ( $nid ) );

  // Delete any UAC calendar access entries for this  user.
  dbi_execute ( 'DELETE FROM webcal_access_user WHERE cal_login = ?
    OR cal_other_user = ?', array ( $nid, $nid ) );

  // Delete any UAC function access entries for this  user.
  dbi_execute ( 'DELETE FROM webcal_access_function WHERE cal_login = ?',
    array ( $nid ) );

  // Delete user
  if ( ! dbi_execute ( 'DELETE FROM webcal_nonuser_cals WHERE cal_login = ?',
    array ( $nid ) ) )
    $error = db_error();

} else {
  if ( ! empty ( $save ) ) {
    // Updating
    $query_params = array();
    $sql = 'UPDATE webcal_nonuser_cals SET ';
    if ($nlastname) {
      $sql .= ' cal_lastname = ?, ';
      $query_params[] = $nlastname;
    }
    if ($nfirstname) {
      $sql .= ' cal_firstname = ?, ';
      $query_params[] = $nfirstname;
    }
    if ( $ispublic ) {
      $sql .= ' cal_is_public = ?, ';
      $query_params[] = $ispublic;
    }

    $query_params[] = $nadmin;
    $query_params[] = $nid;

    if ( ! dbi_execute ( $sql . 'cal_admin = ? WHERE cal_login = ?',
      $query_params ) )
      $error = db_error();
  } else {
    // Adding
    if ( preg_match ( '/^[\w]+$/', $nid ) ) {
      $nid = $NONUSER_PREFIX.$nid;
      if ( ! dbi_execute ( 'INSERT INTO webcal_nonuser_cals ( cal_login,
        cal_firstname, cal_lastname, cal_admin, cal_is_public )
        VALUES ( ?, ?, ?, ?, ? )',
        array ( $nid, $nfirstname, $nlastname, $nadmin, $ispublic ) ) ) {
        $error = db_error();
      }
    } else {
      $error = translate ( 'Cal ID word chars only' );
    }
  }
  //Add entry in UAC access table for new admin and remove old admin
  //first delete any record for this user/nuc combo
  dbi_execute ( 'DELETE FROM webcal_access_user WHERE cal_login = ?
    AND cal_other_user = ?', array ( $nadmin, $nid ) );
  if ( ! dbi_execute ( 'INSERT INTO webcal_access_user ( cal_login,
    cal_other_user, cal_can_view, cal_can_edit, cal_can_approve, cal_can_invite,
    cal_can_email, cal_see_time_only ) VALUES ( ?, ?, ?, ?, ?, ?, ?, ? )',
    array ( $nadmin, $nid, 511, 511, 511, 'Y', 'Y', 'N' ) ) ) {
    die_miserable_death ( str_replace ( 'XXX', dbi_error(),
      translate ( 'DB error XXX' ) ) );
  }
  // Delete old admin...
  //TODO Make this an optional step
  if ( ! empty ( $old_admin ) )
    dbi_execute ( 'DELETE FROM webcal_access_user WHERE cal_login = ?
      AND cal_other_user = ?', array ( $old_admin, $nid ) );
}

echo error_check ( 'users.php?tab=nonusers', false );

?>
