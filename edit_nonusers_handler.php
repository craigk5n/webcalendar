<?php
include_once 'includes/init.php';
require_valid_referring_url ();
load_user_layers();

if ( ! $is_admin ) {
  echo print_not_auth( true ) . '</body></html>';
  exit;
}
$error = '';

$delete = getPostValue ( 'delete' );
$save = getPostValue ( 'Save' );
$add = getPostValue ( 'Add' );
$nid = getPostValue ( 'nid' );
$nfirstname = getPostValue ( 'nfirstname' );
$nlastname = getPostValue ( 'nlastname' );
$nadmin = getPostValue ( 'nadmin' );
$old_admin = getPostValue ( 'old_admin' );
$ispublic = getPostValue ( 'ispublic' );
if ( empty ( $ispublic ) ) $ispublic = 'N';

if ( ! empty ( $delete ) ) {
  // delete this nonuser calendar

  // Get event ids for all events this user is a participant
  $events = get_users_event_ids ( $nid );

  // Now count number of participants in each event...
  // If just 1, then save id to be deleted
  $delete_em = [];
  for ( $i = 0, $cnt = count ( $events ); $i < $cnt; $i++ ) {
    $res = dbi_execute ( 'SELECT COUNT( * )
      FROM webcal_entry_user WHERE cal_id = ?', [$events[$i]] );
    if ( $res ) {
      if ( $row = dbi_fetch_row ( $res ) ) {
        if ( $row[0] == 1 )
   $delete_em[] = $events[$i];
      }
      dbi_free_result ( $res );
    }
  }
  // Now delete events that were just for this user
  for ( $i = 0, $cnt = count ( $delete_em ); $i < $cnt; $i++ ) {
    dbi_execute ( 'DELETE FROM webcal_entry_repeats WHERE cal_id = ?',
     [$delete_em[$i]] );
    dbi_execute ( 'DELETE FROM webcal_entry_repeats_not WHERE cal_id = ?',
     [$delete_em[$i]] );
    dbi_execute ( 'DELETE FROM webcal_entry_log WHERE cal_entry_id = ?',
     [$delete_em[$i]] );
    dbi_execute ( 'DELETE FROM webcal_import_data WHERE cal_id = ?',
     [$delete_em[$i]] );
    dbi_execute ( 'DELETE FROM webcal_site_extras WHERE cal_id = ?',
     [$delete_em[$i]] );
    dbi_execute ( 'DELETE FROM webcal_entry_ext_user WHERE cal_id = ?',
     [$delete_em[$i]] );
    dbi_execute ( 'DELETE FROM webcal_reminders WHERE cal_id =? ',
     [$delete_em[$i]] );
    dbi_execute ( 'DELETE FROM webcal_blob WHERE cal_id = ?',
    [$delete_em[$i]] );
    dbi_execute ( 'DELETE FROM webcal_entry WHERE cal_id = ?',
     [$delete_em[$i]] );
  }

  // Delete user participation from events
  dbi_execute ( 'DELETE FROM webcal_entry_user WHERE cal_login = ?',
    [$nid] );

  // Delete any layers other users may have that point to this user.
  dbi_execute ( 'DELETE FROM webcal_user_layers WHERE cal_layeruser = ?',
    [$nid] );

  // Delete any UAC calendar access entries for this  user.
  dbi_execute ( 'DELETE FROM webcal_access_user WHERE cal_login = ?
    OR cal_other_user = ?', [$nid, $nid] );

  // Delete any UAC function access entries for this  user.
  dbi_execute ( 'DELETE FROM webcal_access_function WHERE cal_login = ?',
    [$nid] );

  // Delete user
  if ( ! dbi_execute ( 'DELETE FROM webcal_nonuser_cals WHERE cal_login = ?',
    [$nid] ) )
    $error = db_error();

} else {
  if ( ! empty ( $save ) ) {
    // Updating
    $query_params = [];
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
        [$nid, $nfirstname, $nlastname, $nadmin, $ispublic] ) ) {
        $error = db_error();
      }
    } else {
      $error = translate ( 'Calendar ID' ).' '.translate ( 'word characters only' ).'.';
    }
  }
  //Add entry in UAC access table for new admin and remove for of admin
  //first delete any record for this user/nuc combo
  dbi_execute ( 'DELETE FROM webcal_access_user WHERE cal_login = ?
    AND cal_other_user = ?', [$nadmin, $nid] );
  if ( ! dbi_execute ( 'INSERT INTO webcal_access_user ( cal_login,
    cal_other_user, cal_can_view, cal_can_edit, cal_can_approve, cal_can_invite,
    cal_can_email, cal_see_time_only ) VALUES ( ?, ?, ?, ?, ?, ?, ?, ? )',
    [$nadmin, $nid, 511, 511, 511, 'Y', 'Y', 'N'] ) ) {
    die_miserable_death ( translate ( 'Database error' ) . ': ' . dbi_error() );
  }
  // Delete old admin...
  //TODO Make this an optional step
  if ( ! empty ( $old_admin ) )
    dbi_execute ( 'DELETE FROM webcal_access_user WHERE cal_login = ?
    AND cal_other_user = ?', [$old_admin, $nid] );
}

echo error_check('users.php?tab=nonusers', false);
?>
