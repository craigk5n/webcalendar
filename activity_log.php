<?php
include_once 'includes/init.php';
include_once 'includes/site_extras.php';
$PAGE_SIZE = 25;
print_header();

echo "<h3>" . translate("Activity Log") . "</h3>\n";
echo "<table style=\"border-width:0px; width:100%;\">\n";
echo "<tr>";
echo "<th style=\"background-color:$THBG; width:10%; color:$THFG;\">" .
  translate("User") . "</th>";
echo "<th style=\"background-color:$THBG; width:10%; color:$THFG;\">" .
  translate("Calendar") . "</th>";
echo "<th style=\"background-color:$THBG; width:25%; color:$THFG;\">" .
  translate("Date") . "/" . translate("Time") . "</th>";
echo "<th style=\"background-color:$THBG; width:30%; color:$THFG;\">" .
  translate("Event") . "</th>";
echo "<th style=\"background-color:$THBG; width:15%; color:$THFG;\">" .
  translate("Action") . "</th></tr>\n";
$sql = "SELECT webcal_entry_log.cal_login, webcal_entry_log.cal_user_cal, " .
  "webcal_entry_log.cal_type, webcal_entry_log.cal_date, " .
  "webcal_entry_log.cal_time, webcal_entry.cal_id, " .
  "webcal_entry.cal_name, webcal_entry_log.cal_log_id " .
  "FROM webcal_entry_log, webcal_entry " .
  "WHERE webcal_entry_log.cal_entry_id = webcal_entry.cal_id ";
if ( ! empty ( $startid ) )
  $sql .= "AND webcal_entry_log.cal_log_id <= $startid ";
$sql .= "ORDER BY webcal_entry_log.cal_log_id DESC";
$res = dbi_query ( $sql );

$nextpage = "";

if ( $res ) {
  $font = "<span style=\"font-size:13px;\">";
  $num = 0;
  while ( $row = dbi_fetch_row ( $res ) ) {
    $num++;
    if ( $num > $PAGE_SIZE ) {
      $nextpage = $row[7];
      break;
    } else {
      echo "<tr>";
      echo "<td style=\"vertical-align:top; background-color:$CELLBG;\">" .
        $font . $row[0] . "</span></td>";
      echo "<td style=\"vertical-align:top; background-color:$CELLBG;\">" .
        $font . $row[1] . "</span></td>";
      echo "<td style=\"vertical-align:top; background-color:$CELLBG;\">" . $font .
        date_to_str ( $row[3] ) . " " .
        display_time ( $row[4] ) . "</span></td>";
      echo "<td style=\"vertical-align:top; background-color:$CELLBG;\">" . $font .
        "<a href=\"view_entry.php?id=$row[5]\" class=\"navlinks\">" .
        htmlspecialchars($row[6]) . "</a></span></td>";
      echo "<td style=\"vertical-align:top; background-color:$CELLBG;\">" . $font;
      if ( $row[2] == $LOG_CREATE )
        etranslate("Event created");
      else if ( $row[2] == $LOG_APPROVE )
        etranslate("Event approved");
      else if ( $row[2] == $LOG_REJECT )
        etranslate("Event rejected");
      else if ( $row[2] == $LOG_UPDATE )
        etranslate("Event updated");
      else if ( $row[2] == $LOG_DELETE )
        etranslate("Event deleted");
      else if ( $row[2] == $LOG_NOTIFICATION )
        etranslate("Notification sent");
      else if ( $row[2] == $LOG_REMINDER )
        etranslate("Reminder sent");
      else
        echo "???";
      echo "</span></td></tr>\n";
    }
  }
  dbi_free_result ( $res );
} else {
  echo translate("Database error") . ": " . dbi_error ();
}
?>

</table><br />

<?php
if ( ! empty ( $startid ) ) {
  $previd = $startid + $PAGE_SIZE;
  $res = dbi_query ( "SELECT MAX(cal_log_id) FROM " .
    "webcal_entry_log" );
  if ( $res ) {
    if ( $row = dbi_fetch_row ( $res ) ) {
      if ( $row[0] <= $previd ) {
        $prevarg = '';
      } else {
        $prevarg = "?startid=$previd";
      }
      echo "<a href=\"activity_log.php$prevarg\" class=\"navlinks\">" .
        translate("Previous") . " $PAGE_SIZE</a><br />\n";
    }
    dbi_free_result ( $res );
  }
}
if ( ! empty ( $nextpage ) ) {
  echo "<a href=\"activity_log.php?startid=$nextpage\" class=\"navlinks\">" .
    translate("Next") . " $PAGE_SIZE</a><br />\n";
}
?>

<?php print_trailer(); ?>
</body>
</html>