<?php
include_once 'includes/init.php';
include_once 'includes/site_extras.php';
$PAGE_SIZE = 25;
print_header();

echo "<H3>" . translate("Activity Log") . "</H3>\n";
echo "<TABLE BORDER=\"0\" WIDTH=\"100%\">\n";
echo "<TR>";
echo "<TH ALIGN=\"left\" BGCOLOR=\"$THBG\" WIDTH=\"10%\"><FONT COLOR=\"$THFG\">" .
  translate("User") . "</FONT></TH>";
echo "<TH ALIGN=\"left\" BGCOLOR=\"$THBG\" WIDTH=\"10%\"><FONT COLOR=\"$THFG\">" .
  translate("Calendar") . "</FONT></TH>";
echo "<TH ALIGN=\"left\" BGCOLOR=\"$THBG\" WIDTH=\"25%\"><FONT COLOR=\"$THFG\">" .
  translate("Date") . "/" . translate("Time") . "</FONT></TH>";
echo "<TH ALIGN=\"left\" BGCOLOR=\"$THBG\" WIDTH=\"30%\"><FONT COLOR=\"$THFG\">" .
  translate("Event") . "</FONT></TH>";
echo "<TH ALIGN=\"left\" BGCOLOR=\"$THBG\" WIDTH=\"15%\"><FONT COLOR=\"$THFG\">" .
  translate("Action") . "</FONT></TH></TR>\n";
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
  $font = "<FONT SIZE=\"-1\">";
  $num = 0;
  while ( $row = dbi_fetch_row ( $res ) ) {
    $num++;
    if ( $num > $PAGE_SIZE ) {
      $nextpage = $row[7];
      break;
    } else {
      echo "<TR>";
      echo "<TD VALIGN=\"top\" BGCOLOR=\"$CELLBG\">" .
        $font . $row[0] . "</FONT></TD>";
      echo "<TD VALIGN=\"top\" BGCOLOR=\"$CELLBG\">" .
        $font . $row[1] . "</FONT></TD>";
      echo "<TD VALIGN=\"top\" BGCOLOR=\"$CELLBG\">" . $font .
        date_to_str ( $row[3] ) . " " .
        display_time ( $row[4] ) . "</FONT></TD>";
      echo "<TD VALIGN=\"top\" BGCOLOR=\"$CELLBG\">" . $font .
        "<A HREF=\"view_entry.php?id=$row[5]\" CLASS=\"navlinks\">" .
        htmlspecialchars($row[6]) . "</A></FONT></TD>";
      echo "<TD VALIGN=\"top\" BGCOLOR=\"$CELLBG\">" . $font;
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
      echo "</FONT></TD></TR>\n";
    }
  }
  dbi_free_result ( $res );
} else {
  echo translate("Database error") . ": " . dbi_error ();
}
?>

</TABLE><BR>

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
        translate("Previous") . " $PAGE_SIZE</a><br>\n";
    }
    dbi_free_result ( $res );
  }
}
if ( ! empty ( $nextpage ) ) {
  echo "<a href=\"activity_log.php?startid=$nextpage\" class=\"navlinks\">" .
    translate("Next") . " $PAGE_SIZE</a><br>\n";
}
?>

<?php print_trailer(); ?>
</BODY>
</HTML>
