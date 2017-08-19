<?php
include_once 'includes/init.php';
require_valid_referring_url ();
include_once 'includes/xcal.php';

// Only available in php 5.x Used for hCalendar parsing.
if ( function_exists ( 'simplexml_load_string' ) )
  require_once 'includes/classes/hKit/hkit.class.php';

$error = '';
$layer_found = false;

$save = getPostValue ( 'Save' );
$add = getPostValue ( 'Add' );
$delete = getPostValue ( 'delete' );
$reload = getPostValue ( 'reload' );
$nid = getPostValue ( 'nid' );
$nfirstname = getPostValue ( 'nfirstname' );
$nlastname = getPostValue ( 'nlastname' );
$nadmin = getPostValue ( 'nadmin' );
$nurl = getPostValue ( 'nurl' );
$reload = getPostValue ( 'reload' );
$nlayer = getPostValue ( 'nlayer' );
$nlayercolor = getPostValue ( 'layercolor' );

if ( ! empty ( $delete ) ) {
  // Delete events from this remote calendar.
  delete_events ( $nid );

  // Delete any layers other users may have that point to this user.
  dbi_execute ( 'DELETE FROM webcal_user_layers WHERE cal_layeruser = ?',
    [$nid] );

  // Delete any UAC calendar access entries for this  user.
  dbi_execute ( 'DELETE FROM webcal_access_user WHERE cal_login = ?
    OR cal_other_user = ?', [$nid, $nid] );

  // Delete any UAC function access entries for this  user.
  dbi_execute ( 'DELETE FROM webcal_access_function WHERE cal_login = ?',
   [$nid] );

  // Delete user.
  if ( ! dbi_execute ( 'DELETE FROM webcal_nonuser_cals WHERE cal_login = ?',
     [$nid] ) )
    $error = db_error();
} else {
  if ( ! empty ( $nid ) && ! empty ( $save ) ) {
    // Updating
    $query_params = [];
    $sql = 'UPDATE webcal_nonuser_cals SET ';
    if ( $nlastname ) {
      $sql .= ' cal_lastname = ?, ';
      $query_params[] = $nlastname;
    }
    if ( $nfirstname ) {
      $sql .= ' cal_firstname = ?, ';
      $query_params[] = $nfirstname;
    }
    $sql .= ' cal_url = ?, cal_is_public = ?, cal_admin = ?
      WHERE cal_login = ?';
    $query_params[] = $nurl;
    $query_params[] = 'N';
    $query_params[] = $nadmin;
    $query_params[] = $nid;

    if ( ! dbi_execute ( $sql, $query_params ) )
      $error = db_error();
  } else
  if ( ! empty ( $add ) ) {
    // Adding
    if ( preg_match ( '/^[\w]+$/', $nid ) ) {
      $nid = $NONUSER_PREFIX . $nid;
      if ( ! dbi_execute ( 'INSERT INTO webcal_nonuser_cals ( cal_login,
        cal_firstname, cal_lastname, cal_admin, cal_is_public, cal_url )
        VALUES ( ?, ?, ?, ?, ?, ? )',
        [$nid, $nfirstname, $nlastname, $nadmin, 'N', $nurl] ) )
        $error = db_error();
    } else
      $error = translate( 'Calendar ID' )
        . translate( 'word characters only' );

    // Add new layer if requested.
    if ( ! empty ( $nlayer ) && $nlayer == 'Y' ) {
      $res = dbi_execute ( 'SELECT MAX( cal_layerid ) FROM webcal_user_layers' );
      $layerid = 1;
      if ( $res ) {
        $row = dbi_fetch_row ( $res );
        $layerid += $row[0];
      }

      dbi_execute ( 'INSERT INTO webcal_user_layers ( cal_layerid, cal_login,
        cal_layeruser, cal_color, cal_dups ) VALUES ( ?, ?, ?, ?, ? )',
        [$layerid, $login, $nid, $layercolor, 'N'] );
      $layer_found = true;
    }
  }
  // Add entry in UAC access table for new admin and remove for old admin.
  // First delete any record for this user/nuc combo.
  dbi_execute ( 'DELETE FROM webcal_access_user WHERE cal_login = ?
    AND cal_other_user = ?', [$nadmin, $nid] );
  if ( ! dbi_execute ( 'INSERT INTO webcal_access_user ( cal_login,
    cal_other_user, cal_can_view, cal_can_edit, cal_can_approve, cal_can_invite,
    cal_can_email, cal_see_time_only ) VALUES ( ?, ?, ?, ?, ?, ?, ?, ? )',
      [$nadmin, $nid, 511, 511, 511, 'Y', 'Y', 'N'] ) )
    die_miserable_death ( translate ( 'Database error' ) . ': '
       . dbi_error() );
}

if ( ! empty ( $reload ) ) {
  $data = [];
  $calUser = $nid;
  $overwrite = true;
  $type = 'remoteics';
  // We will check ics first.
  $data = parse_ical ( $nurl, $type );
  // TODO it may be a vcs file.
  // if ( count ( $data ) == 0 ) {
  // $data = parse_vcal ( $nurl );
  // }
  // We may be processing an hCalendar.
  // $data sometimes has a count of 1 but is not a valid array.
  if ( ( count ( $data ) == 0 || ! isset ( $data[0] ) ) &&
      function_exists ( 'simplexml_load_string' ) ) {
    $h = new hKit;
    $h->tidy_mode = 'proxy';
    $result = $h->getByURL ( 'hcal', $nurl );
    $type = 'hcal';
    $data = parse_hcal ( $result, $type );
  }

  $errorStr = '<br /><br />
    <b>' . translate ( 'Error' ) . ':</b> ';

  print_header ( '', '', '', true, false, true );
  if ( count ( $data ) && empty ( $errormsg ) ) {
    // Delete existing events.
    delete_events ( $nid );
    // Import new events.
    import_data ( $data, $overwrite, $type );
    echo '
    <p>' . translate ( 'Import Results' ) . '</p><br /><br />
    ' . translate ( 'Events successfully imported' ) . ': ' . $count_suc
     . '<br />';
    if ( $layer_found == false ) { // We may have just added layer.
      load_user_layers();
      foreach ( $layers as $layer ) {
        if ( $layer['cal_layeruser'] == $nid )
          $layer_found = true;
      }
    }
    if ( $layer_found == false )
      echo '
    <p>' . translate( 'Create a new layer to view this calendar.' ) . '</p>';
  } elseif ( ! empty ( $errormsg ) ) {
    echo '
    ' . translate ( 'Errors' ) . ': ' . $error_num . '<br /><br />
    ' . $errorStr . $errormsg . '<br />';
  } else {
    echo $errorStr .
    translate( 'There was an error parsing the import file or no events were returned.' )
     . '<br />';
  }
  echo print_trailer ( false, true, true );
}

function delete_events ( $nid ) {
  // Get event ids for all events this user is a participant.
  $events = get_users_event_ids ( $nid );

  // Now count number of participants in each event...
  // If just 1, then save id to be deleted.
  $delete_em = [];
  for ( $i = 0, $cnt = count ( $events ); $i < $cnt; $i++ ) {
    $res = dbi_execute ( 'SELECT COUNT( * ) FROM webcal_entry_user
  WHERE cal_id = ?', [$events[$i]] );
    if ( $res ) {
      $row = dbi_fetch_row ( $res );
      if ( ! empty ( $row ) && $row[0] == 1 )
        $delete_em[] = $events[$i];

      dbi_free_result ( $res );
    }
  }
  // Now delete events that were just for this user.
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
  // Delete user participation from events.
  dbi_execute ( 'DELETE FROM webcal_entry_user WHERE cal_login = ?',
   [$nid] );
}

echo error_check ( 'users.php?tab=remotes', false );

?>
