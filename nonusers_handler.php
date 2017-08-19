<?php
include_once 'includes/init.php';
load_user_layers();

$nid = getValue ( 'nid' );
$old_admin = getValue ( 'old_admin' );
$nfirstname = getValue ( 'nfirstname' );
$nlastname = getValue ( 'nlastname' );
$nadmin = getValue ( 'nadmin' );
$ispublic = getValue ( 'ispublic' );
$action = getValue ( 'action' );
$delete = getValue ( 'delete' );

if ( ! $is_admin ) {
  echo print_not_auth ( true ) . print_trailer();
  exit;
}
$error = '';

if ( $action == 'Delete' || $action == translate ( 'Delete' ) ) {
  // delete this nonuser calendar
  $user = $nid;

  // Get event ids for all events this user is a participant.
  $events = get_users_event_ids ( $user );

  // Now count number of participants in each event...
  // If just 1, then save id to be deleted.
  $delete_em = [];
  for ( $i = 0, $cnt = count ( $events ); $i < $cnt; $i++ ) {
    $res = dbi_execute ( 'SELECT COUNT( * ) FROM webcal_entry_user
  WHERE cal_id = ?', [$events[$i]] );
    if ( $res ) {
      if ( $row = dbi_fetch_row ( $res ) && $row[0] == 1 )
        $delete_em[] = $events[$i];

      dbi_free_result ( $res );
    }
  }
  // Now delete events that were just for this user
  for ( $i = 0, $cnt = count ( $delete_em ); $i < $cnt; $i++ ) {
    dbi_execute ( 'DELETE FROM webcal_entry WHERE cal_id = ?',
      [$delete_em[$i]] );
  }

  // Delete user participation from events
  dbi_execute ( 'DELETE FROM webcal_entry_user WHERE cal_login = ?',
    [$user] );
  // Delete any layers other users may have that point to this user.
  dbi_execute ( 'DELETE FROM webcal_user_layers WHERE cal_layeruser = ?',
    [$user] );

  // Delete user
  if ( ! dbi_execute ( 'DELETE FROM webcal_nonuser_cals WHERE cal_login = ?',
      [$user] ) )
    $error = db_error();
} else {
  if ( $action == 'Save' || $action == translate ( 'Save' ) ) {
    // Updating
    $sql_params = [];
    $sql = 'UPDATE webcal_nonuser_cals SET';
    if ( $nlastname ) {
      $sql .= ' cal_lastname = ?,';
      $sql_params[] = $nlastname;
    }
    if ( $nfirstname ) {
      $sql .= ' cal_firstname = ?,';
      $sql_params[] = $nfirstname;
    }
    $sql_params[] = $nadmin;
    $sql_params[] = $nid;
    if ( ! dbi_execute ( $sql . ' cal_admin = ? WHERE cal_login = ?',
        $sql_params ) )
      $error = db_error();
  } else {
    // Adding
    if ( preg_match ( '/^[\w]+$/', $nid ) ) {
      $nid = $NONUSER_PREFIX . $nid;
      if ( ! dbi_execute ( 'INSERT INTO webcal_nonuser_cals ( cal_login,
        cal_firstname, cal_lastname, cal_admin ) VALUES ( ?, ?, ?, ? )',
          [$nid, $nfirstname, $nlastname, $nadmin] ) )
        $error = db_error();
    } else
      $error = translate ( 'Calendar ID' ) . ' '
       . translate ( 'word characters only' ) . '.';
  }
}
if ( empty ( $error ) )
  do_redirect ( 'nonusers.php' );

print_header();
echo print_error ( $error ) . print_trailer();

?>
