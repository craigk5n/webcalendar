<?php
include_once 'includes/init.php';
send_no_cache_header ();

if ( empty ( $user ) )
  $user = $login;

// Only admin user or assistant can specify a username other than his own.
if ( ! $is_admin && $user != $login  && ! $is_assistant)
  $user = $login;

$HeadX = '';
if ( $auto_refresh == "Y" && ! empty ( $auto_refresh_time ) ) {
  $refresh = $auto_refresh_time * 60; // convert to seconds
  $HeadX = "<meta http-equiv=\"refresh\" content=\"$refresh; URL=list_unapproved.php\" />\n";
}
$INC = array('js/popups.php');
print_header($INC,$HeadX);

$key = 0;

// List all unapproved events for the user
// Exclude "extension" events (used when an event goes past midnight)
function list_unapproved ( $user ) {
  global $temp_fullname, $key, $login;
  //echo "Listing events for $user <br>";

  $sql = "SELECT webcal_entry.cal_id, webcal_entry.cal_name, " .
    "webcal_entry.cal_description, " .
    "webcal_entry.cal_priority, webcal_entry.cal_date, " .
    "webcal_entry.cal_time, webcal_entry.cal_duration, " .
    "webcal_entry_user.cal_status " .
    "FROM webcal_entry, webcal_entry_user " .
    "WHERE webcal_entry.cal_id = webcal_entry_user.cal_id " .
    "AND ( webcal_entry.cal_ext_for_id IS NULL " .
    "OR webcal_entry.cal_ext_for_id = 0 ) AND " .
    "webcal_entry_user.cal_login = '$user' AND " .
    "webcal_entry_user.cal_status = 'W' " .
    "ORDER BY webcal_entry.cal_date";
  $res = dbi_query ( $sql );
  $count = 0;
  $eventinfo = "";
  if ( $res ) {
    while ( $row = dbi_fetch_row ( $res ) ) {
      if ($count == 0 ) { echo "<ul>\n"; }
      $key++;
      $id = $row[0];
      $name = $row[1];
      $description = $row[2];
      $pri = $row[3];
      $date = $row[4];
      $time = $row[5];
      $duration = $row[6];
      $status = $row[7];
      $divname = "eventinfo-$id-$key";
      echo "<li><a title=\"" . 
      		translate("View this entry") . "\" class=\"entry\" href=\"view_entry.php?id=$id&amp;user=$user";
      echo "\" onmouseover=\"window.status='" . translate("View this entry") .
        "'; show(event, '$divname'); return true;\" onmouseout=\"hide('$divname'); return true;\">";
      $timestr = "";
      if ( $time > 0 ) {
        $timestr = display_time ( $time );
        if ( $duration > 0 ) {
          // calc end time
          $h = (int) ( $time / 10000 );
          $m = ( $time / 100 ) % 100;
          $m += $duration;
          $d = $duration;
          while ( $m >= 60 ) {
            $h++;
            $m -= 60;
          }
          $end_time = sprintf ( "%02d%02d00", $h, $m );
          $timestr .= " - " . display_time ( $end_time );
        }
      }
      echo htmlspecialchars ( $name );
      echo "</a>";
      echo " (" . date_to_str ($date) . ")\n";
//approve
      echo ": <a title=\"" . 
	translate("Approve/Confirm") . "\"  href=\"approve_entry.php?id=$id&amp;ret=list&amp;user=$user";
      if ( $user == "__public__" )
        echo "&amp;public=1";
      echo "\" class=\"nav\" onclick=\"return confirm('" .
        translate("Approve this entry?") . "');\">" . 
	translate("Approve/Confirm") . "</a>, ";
//reject
      echo "<a title=\"" . 
	translate("Reject") . "\" href=\"reject_entry.php?id=$id&amp;ret=list&amp;user=$user";
      if ( $user == "__public__" )
        echo "&amp;public=1";
      echo "\" class=\"nav\" onclick=\"return confirm('" .
        translate("Reject this entry?") . "');\">" . 
	translate("Reject") . "</a>";
//delete
      echo ", <a title=\"" . 
	translate("Delete") . "\" href=\"del_entry.php?id=$id&amp;ret=list";
      if ( $user != $login )
        echo "&amp;user=$user";
      echo "\" class=\"nav\" onclick=\"return confirm('" .
        translate("Are you sure you want to delete this entry?") . "');\">" . 
	translate("Delete") . "</a>";
      echo "\n</li>\n";
      $eventinfo .= build_event_popup ( $divname, $user, $description,
        $timestr, site_extras_for_popup ( $id ));
      $count++;
    }
    dbi_free_result ( $res );
    if ($count > 0 ) { echo "</ul>\n"; }
  }
  if ( $count == 0 ) {
    user_load_variables ( $user, "temp_" );
    echo "<span class=\"nounapproved\">" . 
	translate("No unapproved events for") . "&nbsp;" . $temp_fullname . ".</span>\n";
  } else {
    if ( ! empty ( $eventinfo ) ) echo $eventinfo;
  }
}
?>

<h2><?php 
	etranslate("Unapproved Events"); 
	if ( $user == '__public__' ) echo " - " . $PUBLIC_ACCESS_FULLNAME; 
?></h2>
<?php
// List unapproved events for this user.
list_unapproved ( ( $is_assistant || $is_nonuser_admin || $is_admin ) ? $user : $login );

// Admin users can also approve Public Access events
if ( $is_admin && $public_access == "Y" &&
  ( empty ( $user ) || $user != '__public__' ) ) {
  echo "\n<h3>" . translate ( "Public Access" ) . "</h3>\n";
  list_unapproved ( "__public__" );
}

// NonUser calendar admins cal approve events on that specific NonUser
// calendar.
if ( $nonuser_enabled == 'Y' ) {
  $admincals = get_nonuser_cals ( $login );
  for ( $i = 0; $i < count ( $admincals ); $i++ ) {
    echo "\n<h3>" . $admincals[$i]['cal_fullname'] . "</h3>\n";
    list_unapproved ( $admincals[$i]['cal_login'] );
  }
}
?>

<?php print_trailer(); ?>
</body>
</html>