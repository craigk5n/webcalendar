<?php php_track_vars?>
<?php

include "includes/config.inc";
include "includes/php-dbi.inc";
include "includes/functions.inc";
include "includes/user.inc";
include "includes/validate.inc";
include "includes/connect.inc";

load_user_preferences ();
load_user_layers ();

include "includes/translate.inc";

?>
<HTML>
<HEAD>
<TITLE><?php etranslate("Title")?></TITLE>
<?php include "includes/styles.inc"; ?>
<?php include "includes/js.inc"; ?>
</HEAD>
<BODY BGCOLOR="<?php echo $BGCOLOR;?>">

<H2><FONT COLOR="<?php echo $H2COLOR;?>"><?php etranslate("Unapproved Events")?></FONT></H2>

<UL>
<?php
$sql = "SELECT webcal_entry.cal_id, webcal_entry.cal_name, " .
  "webcal_entry.cal_description, " .
  "webcal_entry.cal_priority, webcal_entry.cal_date, " .
  "webcal_entry.cal_time, webcal_entry.cal_duration, " .
  "webcal_entry_user.cal_status " .
  "FROM webcal_entry, webcal_entry_user " .
  "WHERE webcal_entry.cal_id = webcal_entry_user.cal_id AND " .
  "webcal_entry_user.cal_login = '$login' AND " .
  "webcal_entry_user.cal_status = 'W' " .
  "ORDER BY webcal_entry.cal_date";
$res = dbi_query ( $sql );
$count = 0;
$key = 0;
if ( $res ) {
  while ( $row = dbi_fetch_row ( $res ) ) {
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
    echo "<LI><A CLASS=\"entry\" HREF=\"view_entry.php?id=$id";
     echo "\" onMouseOver=\"window.status='" . translate("View this entry") .
      "'; show(event, '$divname'); return true;\" onMouseOut=\"hide('$divname'); return true;\">";
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
    echo "</A>";
    echo " (" . date_to_str ($date) . ")\n";
    echo ": <A HREF=\"approve_entry.php?id=$id&ret=list\" " .
      "CLASS=\"navlinks\" onClick=\"return confirm('" .
      translate("Approve this entry?") .
      "');\">" . translate("Approve/Confirm") . "</A>, ";
    echo "<A HREF=\"reject_entry.php?id=$id&ret=list\" " .
      "CLASS=\"navlinks\" onClick=\"return confirm('" .
      translate("Reject this entry?") .
      "');\">" . translate("Reject") . "</A>";
    $eventinfo .= build_event_popup ( $divname, $login, $description, $timestr );
    $count++;
  }
  dbi_free_result ( $res );
}

echo "</UL>";

if ( $count == 0 ) {
  echo translate("No unapproved events") . ".";
} else {
  echo $eventinfo;
}
?>

<?php include "includes/trailer.inc"; ?>
</BODY>
</HTML>
