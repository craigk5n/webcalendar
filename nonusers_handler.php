<?php
include_once 'includes/init.php';
load_user_layers ();

if ( ! $is_admin ) {
  echo "<h2>" . translate("Error") .
    "</h2>" . translate("You are not authorized") . ".\n";
  print_trailer ();
  echo "</body></html>\n";
  exit;
}
$error = "";

if ( $action == "Delete" || $action == translate ("Delete") ) {
  // delete this nonuser calendar
  $user = $nid;

  // Get event ids for all events this user is a participant
  $events = array ();
  $res = dbi_query ( "SELECT webcal_entry.cal_id " .
    "FROM webcal_entry, webcal_entry_user " .
    "WHERE webcal_entry.cal_id = webcal_entry_user.cal_id " .
    "AND webcal_entry_user.cal_login = '$user'" );
  if ( $res ) {
    while ( $row = dbi_fetch_row ( $res ) ) {
      $events[] = $row[0];
    }
  }

  // Now count number of participants in each event...
  // If just 1, then save id to be deleted
  $delete_em = array ();
  for ( $i = 0; $i < count ( $events ); $i++ ) {
    $res = dbi_query ( "SELECT COUNT(*) FROM webcal_entry_user " .
      "WHERE cal_id = " . $events[$i] );
    if ( $res ) {
      if ( $row = dbi_fetch_row ( $res ) ) {
        if ( $row[0] == 1 )
	  $delete_em[] = $events[$i];
      }
      dbi_free_result ( $res );
    }
  }
  // Now delete events that were just for this user
  for ( $i = 0; $i < count ( $delete_em ); $i++ ) {
    dbi_query ( "DELETE FROM webcal_entry WHERE cal_id = " . $delete_em[$i] );
  }

  // Delete user participation from events
  dbi_query ( "DELETE FROM webcal_entry_user WHERE cal_login = '$user'" );

  // Delete any layers other users may have that point to this user.
  dbi_query ( "DELETE FROM webcal_user_layers WHERE cal_layeruser = '$user'" );

  // Delete user
  if ( ! dbi_query ( "DELETE FROM webcal_nonuser_cals WHERE cal_login = '$user'" ) )
     $error = translate ("Database error") . ": " . dbi_error();

} else {
  if ( $action == "Save" || $action == translate ("Save") ) {
  // Updating
    $sql = "UPDATE webcal_nonuser_cals SET ";
    if ($nlastname) $sql .= " cal_lastname = '$nlastname', ";
    if ($nfirstname) $sql .= " cal_firstname = '$nfirstname', ";
    $sql .= "cal_admin = '$nadmin' WHERE cal_login = '$nid'";
    if ( ! dbi_query ( $sql ) ) {
      $error = translate ("Database error") . ": " . dbi_error();
    }
  } else {
  // Adding
    if (preg_match( "/^[\w]+$/", $nid )) {
      $nid = $NONUSER_PREFIX.$nid;
      $sql = "INSERT INTO webcal_nonuser_cals " .
      "( cal_login, cal_firstname, cal_lastname, cal_admin ) " .
      "VALUES ( '$nid', '$nfirstname', '$nlastname', '$nadmin' )";
      if ( ! dbi_query ( $sql ) ) {
        $error = translate ("Database error") . ": " . dbi_error();
      }
    } else {
      $error = translate ("Calendar ID")." ".translate ("word characters only").".";
    }
  }
}
if ( empty ( $error ) ) do_redirect ( "nonusers.php" );

print_header();
?>

<h2><?php etranslate("Error")?></h2>

<blockquote>
<?php

echo $error;
//if ( $sql != "" )
//  echo "<br /><br /><b>SQL:</b> $sql";
//?>
</blockquote>

<?php print_trailer(); ?>
</body>
</html>