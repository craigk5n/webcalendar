<?php
include_once 'includes/init.php';
include_once 'includes/xcal.php';

$error = '';

$delete = getPostValue ( 'delete' );
$reload = getPostValue ( 'reload' );
$nid = getPostValue ( 'nid' );
$nadmin = getPostValue ( 'nadmin' );
$nurl = getPostValue ( 'nurl' );
$nlayer = getPostValue ( 'nlayer' );
$nlayercolor = getPostValue ( 'layercolor' );

if ( ! empty ( $reload ) ) {
    $calUser = $nid;
    $type = 'remoteics';
    $overwrite = true;
    $data = parse_ical( $nurl, $type );
    print_header( '','','',true,false,true);
    if (! empty ($data) && empty ($errormsg) ) {
      //delete existing events
      delete_events ( $nid );
      //import new events
      import_data ( $data, $overwrite, $type );
      echo '<p>' . translate( 'Import Results' ) . "</p>\n<br /><br />\n" .
      translate( 'Events successfully imported' ) . ": $count_suc<br />\n";
      load_user_layers();
      $layer_found = false;
      foreach ($layers as $layer) {
        if($layer['cal_layeruser'] == $nid )
          $layer_found = true;
      }
      if ( $layer_found == false )     
        echo '<p>' . translate( 'Create a new layer to view this calendar' ) . ".</p>\n";
    } elseif (! empty ( $errormsg ) ) {
      echo translate ( 'Errors' ) . ": $error_num<br /><br />\n";
      echo "<br /><br />\n<b>" . translate( 'Error' ) . ":</b> $errormsg<br />\n";
    } else {
      echo "<br /><br />\n<b>" . translate( 'Error' ) . ":</b> " .
        translate( 'There was an error parsing the import file or no events were returned' ) .
        ".<br />\n";
    }
    print_trailer ( false, true, true );
    exit;    
} else if ( ! empty ( $delete ) ) {
  // delete events from this remote calendar
  delete_events ( $nid );

  // Delete any layers other users may have that point to this user.
  dbi_execute ( "DELETE FROM webcal_user_layers WHERE cal_layeruser = ?", 
    array( $nid ) );

  // Delete any UAC calendar access entries for this  user.
  dbi_execute ( "DELETE FROM webcal_access_user WHERE cal_login = ? " .
    "OR cal_other_user = ?", array( $nid, $nid ) );

  // Delete any UAC function access entries for this  user.
  dbi_execute ( "DELETE FROM webcal_access_function WHERE cal_login = ?", 
    array( $nid ) );
    
  // Delete user
  if ( ! dbi_execute ( "DELETE FROM webcal_nonuser_cals WHERE cal_login = ?", 
    array( $nid ) ) )
    $error = translate( 'Database error' ) . ': ' . dbi_error();

} else {
  if ( $action == "Save" || $action == translate( 'Save' ) ) {
    // Updating
    $query_params = array();
    $sql = "UPDATE webcal_nonuser_cals SET ";
    if ($nlastname) { 
      $sql .= " cal_lastname = ?, ";
      $query_params[] = $nlastname;
    }
    if ($nfirstname) {
      $sql .= " cal_firstname = ?, ";
      $query_params[] = $nfirstname;
    }
    $sql .= " cal_url = ?, ";
    $query_params[] = $nurl;
    $sql .= " cal_is_public = ?, ";
    $query_params[] = 'N';
        
    $sql .= "cal_admin = ? WHERE cal_login = ?";
    $query_params[] = $nadmin;
    $query_params[] = $nid;

    if ( ! dbi_execute ( $sql, $query_params ) ) {
      $error = translate( 'Database error' ) . ': ' . dbi_error();
    }
  } else {
    // Adding    
    if (preg_match( "/^[\w]+$/", $nid )) {
      $nid = $NONUSER_PREFIX.$nid;
      $sql = "INSERT INTO webcal_nonuser_cals " .
      "( cal_login, cal_firstname, cal_lastname, cal_admin, cal_is_public, cal_url ) " .
      "VALUES ( ?, ?, ?, ?, ?, ? )";
      if ( ! dbi_execute ( $sql, 
        array( $nid, $nfirstname, $nlastname, $nadmin, 'N', $nurl ) ) ) {
        $error = translate( 'Database error' ) . ': ' . dbi_error();
      }
    } else {
      $error = translate ( 'Calendar ID' ).' '.translate ( 'word characters only' ).'.';
    }
    //add new layer if requested
    if ( ! empty ( $nlayer ) && $nlayer == 'Y' ) {
      $res = dbi_execute ( "SELECT MAX(cal_layerid) FROM webcal_user_layers" );
      if ( $res ) {
        $row = dbi_fetch_row ( $res );
        $layerid = $row[0] + 1;
      } else {
        $layerid = 1;
      }
      dbi_execute ( "INSERT INTO webcal_user_layers ( cal_layerid, cal_login, cal_layeruser, cal_color, cal_dups ) VALUES ( ?, ?, ?, ?, ? )", 
        array( $layerid, $login, $nid, $layercolor, 'N' ) );    
    
    }
  }
  //Add entry in UAC access table for new admin and remove for old admin
  //first delete any record for this user/nuc combo
  dbi_execute ( "DELETE FROM webcal_access_user WHERE cal_login = ? " .
    "AND cal_other_user = ?", array( $nadmin, $nid ) );  
  $sql = "INSERT INTO webcal_access_user " .
    "( cal_login, cal_other_user, cal_can_view, cal_can_edit, " .
    "cal_can_approve, cal_can_invite, cal_can_email, cal_see_time_only ) VALUES " .
    "( ?, ?, ?, ?, ?, ?, ?, ? )";
  if ( ! dbi_execute ( $sql, array( $nadmin, $nid, 511, 511, 511, 'Y', 'Y', 'N' ) ) ) {
    die_miserable_death ( translate ( 'Database error' ) . ': ' .
      dbi_error () );
  }  
}

function delete_events ( $nid ){

  // Get event ids for all events this user is a participant
  $events = array ();
  $res = dbi_execute ( "SELECT webcal_entry.cal_id " .
    "FROM webcal_entry, webcal_entry_user " .
    "WHERE webcal_entry.cal_id = webcal_entry_user.cal_id " .
    "AND webcal_entry_user.cal_login = ?", array( $nid ) );
  if ( $res ) {
    while ( $row = dbi_fetch_row ( $res ) ) {
      $events[] = $row[0];
    }
  }

  // Now count number of participants in each event...
  // If just 1, then save id to be deleted
  $delete_em = array ();
  $cnt = count ( $events );
  for ( $i = 0; $i < $cnt; $i++ ) {
    $res = dbi_execute ( "SELECT COUNT(*) FROM webcal_entry_user " .
      "WHERE cal_id = ?", array( $events[$i] ) );
    if ( $res ) {
      if ( $row = dbi_fetch_row ( $res ) ) {
        if ( $row[0] == 1 )
   $delete_em[] = $events[$i];
      }
      dbi_free_result ( $res );
    }
  }
  // Now delete events that were just for this user
  $cnt = count ( $delete_em );
  for ( $i = 0; $i < $cnt; $i++ ) {
    dbi_execute ( "DELETE FROM webcal_entry_repeats WHERE cal_id = ?", 
      array( $delete_em[$i] ) );
    dbi_execute ( "DELETE FROM webcal_entry_repeats_not WHERE cal_id = ?", 
      array( $delete_em[$i] ) );
    dbi_execute ( "DELETE FROM webcal_entry_log WHERE cal_entry_id = ?", 
      array( $delete_em[$i] ) );
    dbi_execute ( "DELETE FROM webcal_import_data WHERE cal_id = ?", 
      array( $delete_em[$i] ) );
    dbi_execute ( "DELETE FROM webcal_site_extras WHERE cal_id = ?", 
      array( $delete_em[$i] ) );
    dbi_execute ( "DELETE FROM webcal_entry_ext_user WHERE cal_id = ?", 
      array( $delete_em[$i] ) );
    dbi_execute ( "DELETE FROM webcal_reminders WHERE cal_id =? ", 
      array( $delete_em[$i] ) );
    dbi_execute ( "DELETE FROM webcal_blob WHERE cal_id = ?", 
     array( $delete_em[$i] ) );
    dbi_execute ( "DELETE FROM webcal_entry WHERE cal_id = ?", 
      array( $delete_em[$i] ) );
  }
  // Delete user participation from events
  dbi_execute ( "DELETE FROM webcal_entry_user WHERE cal_login = ?", 
    array( $nid ) );

}


if ( ! empty ( $error ) ) {
  print_header( '', '', '', true );
?>

<h2><?php etranslate(  'Error' )?></h2>

<blockquote>
<?php
echo $error;
//if ( $sql != "" )
//  echo "<br /><br /><b>SQL:</b> $sql";
//?>
</blockquote>
</body>
</html>
<?php } else if ( empty ( $error ) ) {
?><html><head></head>
<body onLoad="alert('<?php etranslate( 'Changes successfully saved', true);?>'); window.parent.location.href='users.php?tab=remotes';">
</body></html>
<?php } ?>
