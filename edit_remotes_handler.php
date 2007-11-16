<?php
/* $Id$ */
include_once 'includes/init.php';
include_once 'includes/xcal.php';

// Only available in php 5.x Used for hCalendar parsing.
if ( function_exists ( 'simplexml_load_string' ) )
  require_once 'includes/classes/hKit/hkit.class.php';

$error = '';
$layer_found = false;

$action = $WC->getPOST ( 'action' );
$delete = $WC->getPOST ( 'delete' );
$reload = $WC->getPOST ( 'reload' );
$nid = $WC->getPOST ( 'nid' );
$nadmin = $WC->getPOST ( 'nadmin' );
$nurl = $WC->getPOST ( 'nurl' );
$nlayer = $WC->getPOST ( 'nlayer' );
$nlayercolor = $WC->getPOST ( 'layercolor' );

if ( ! empty ( $delete ) ) {
  // Delete all records this remote calendar.
  $WC->User->deleteUser ( $nid );

} else {
  if ( ! empty ( $nid ) && $action == 'Save' ||
    $action == translate ( 'Save' ) ) {
    // Updating
    $query_params = array ();
    $sql = 'UPDATE webcal_user SET ';
    if ( $rmt_name ) {
      $sql .= ' cal_fullname = ?, ';
      $query_params[] = $rmt_name;
    }
    $sql .= ' cal_url = ?, cal_is_public = ?, cal_admin = ?
      WHERE cal_login_id = ?';
    $query_params[] = $nurl;
    $query_params[] = 'N';
    $query_params[] = $nadmin;
    $query_params[] = $nid;

    if ( ! dbi_execute ( $sql, $query_params ) )
      $error = db_error ();
  } else
  if ( $action == 'Add' || $action == translate ( 'Add' ) ) {
    // Adding
    if ( preg_match ( '/^[\w]+$/', $nid ) ) {
      $nid = _WC_NONUSER_PREFIX . $nid;
			$params = array ( 'cal_login'=>$nid,
			  'cal_lastname'=>$WC->getPOST ( '$nlastname' ),
				'cal_firstname'=>$WC->getPOST ( '$nfirstname' ),
				'cal_is_nuc'=>'Y',
				'cal_is_public'=>'N',
				'cal_admin'=>$nadmin,
				'cal_url'=>$nurl );
      if ( ! $newID = $WC->User->addUser ( $params  ) )
        $error = db_error ();
    } else
      $error = translate ( 'Calendar ID' ) . ' '
       . translate ( 'word characters only' ) . '.';

    // Add new layer if requested.
    if ( ! empty ( $nlayer ) && $nlayer == 'Y' ) {
      $res = dbi_execute ( 'SELECT MAX( cal_layerid ) FROM webcal_user_layers' );
      $layerid = 1;
      if ( $res ) {
        $row = dbi_fetch_row ( $res );
        $layerid += $row[0];
      }

      dbi_execute ( 'INSERT INTO webcal_user_layers ( cal_layerid, cal_login_id,
        cal_layeruser_id, cal_color, cal_dups ) VALUES ( ?, ?, ?, ?, ? )',
        array ( $layerid, $WC->loginId(), $newID, $layercolor, 'N' ) );
      $layer_found = true;
    }
  }
  // Add entry in UAC access table for new admin and remove for old admin.
  // First delete any record for this user/nuc combo.
  dbi_execute ( 'DELETE FROM webcal_access_user WHERE cal_login_id = ?
    AND cal_other_user_id = ?', array ( $nadmin, $nid ) );
  if ( ! dbi_execute ( 'INSERT INTO webcal_access_user ( cal_login_id,
    cal_other_user_id, cal_can_view, cal_can_edit, cal_can_approve, cal_can_invite,
    cal_can_email, cal_see_time_only ) VALUES ( ?, ?, ?, ?, ?, ?, ?, ? )',
      array ( $nadmin, $nid, 511, 511, 511, 'Y', 'Y', 'N' ) ) )
    die_miserable_death ( translate ( 'Database error' ) . ': '
       . dbi_error () );
}

if ( ! empty ( $reload ) ) {
  $data = array ();
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

  build_header ( '', '', '', 5 );
  if ( count ( $data ) && empty ( $errormsg ) ) {
    // Delete all events for this user.
    $WC->User->deleteUserEvents ( $nid );
    // Import new events.
    import_data ( $data, $overwrite, $type );
    echo '
    <p>' . translate ( 'Import Results' ) . '</p><br /><br />
    ' . translate ( 'Events successfully imported' ) . ': ' . $count_suc
     . '<br />';
    if ( $layer_found == false ) { // We may have just added layer.
      $layers = loadLayers ();
      foreach ( $layers as $layer ) {
        if ( $layer['cal_layeruse_id'] == $nid )
          $layer_found = true;
      }
    }
    if ( $layer_found == false )
      echo '
    <p>' . translate ( 'Create a new layer to view this calendar' ) . '.</p>';
  } elseif ( ! empty ( $errormsg ) ) {
    echo '
    ' . translate ( 'Errors' ) . ': ' . $error_num . '<br /><br />
    ' . $errorStr . $errormsg . '<br />';
  } else {
    echo $errorStr .
    translate ( 'There was an error parsing the import file or no events were returned' )
     . '.<br />';
  }
  echo print_trailer ( false, true, true );
}

echo error_check ( 'users.php?tab=remotes', false );

?>
