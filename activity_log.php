<?php
include_once 'includes/init.php';
include_once 'includes/site_extras.php';
$PAGE_SIZE = 25;
print_header();

echo "<h3>" . translate("Activity Log") . "</h3>\n";

echo "<a title=\"" . translate("Admin") . "\" class=\"nav\" href=\"adminhome.php\">&laquo;&nbsp;" . translate("Admin") . "</a><br /><br />\n";

echo "<table>\n";
echo "<tr><th class=\"usr\">\n" .
  translate("User") . "</th><th class=\"cal\">\n" .
  translate("Calendar") . "</th><th class=\"scheduled\">\n" .
  translate("Date") . "/" . translate("Time") . "</th><th class=\"dsc\">\n" .
  translate("Event") . "</th><th class=\"action\">\n" .
  translate("Action") . "\n</th></tr>\n";
$sql = "SELECT webcal_entry_log.cal_login, webcal_entry_log.cal_user_cal, " .
  "webcal_entry_log.cal_type, webcal_entry_log.cal_date, " .
  "webcal_entry_log.cal_time, webcal_entry.cal_id, " .
  "webcal_entry.cal_name, webcal_entry_log.cal_log_id " .
  "FROM webcal_entry_log, webcal_entry " .
  "WHERE webcal_entry_log.cal_entry_id = webcal_entry.cal_id ";
$startid = getIntValue ( 'startid', true );
if ( ! empty ( $startid ) )
  $sql .= "AND webcal_entry_log.cal_log_id <= $startid ";
$sql .= "ORDER BY webcal_entry_log.cal_log_id DESC";
$res = dbi_query ( $sql );

$nextpage = "";

if ( $res ) {
  $num = 0;
  while ( $row = dbi_fetch_row ( $res ) ) {
    $num++;
    if ( $num > $PAGE_SIZE ) {
      $nextpage = $row[7];
      break;
    } else {
	echo "<tr";
		if ( $num % 2 ) {
			echo " class=\"odd\"";
		}
	echo "><td>\n" .
        $row[0] . "</td><td>\n" .
        $row[1] . "</td><td>\n" . 
        date_to_str ( $row[3] ) . "&nbsp;" .
        display_time ( $row[4] ) . "</td><td>\n" . 
        "<a title=\"" .
        htmlspecialchars($row[6]) . "\" href=\"view_entry.php?id=$row[5]\">" .
        htmlspecialchars($row[6]) . "</a></td><td>\n";
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
      echo "\n</td></tr>\n";
    }
  }
  dbi_free_result ( $res );
} else {
  echo translate("Database error") . ": " . dbi_error ();
}
?>
</table><br />
<div class="navigation">
<?php
//go BACK in time
if ( ! empty ( $nextpage ) ) {
  echo "<a title=\"" . 
  	translate("Previous") . "&nbsp;$PAGE_SIZE&nbsp;" . 
	translate("Events") . "\" class=\"prev\" href=\"activity_log.php?startid=$nextpage\">" . 
  	translate("Previous") . "&nbsp;$PAGE_SIZE&nbsp;" . 
	translate("Events") . "</a>\n";
}

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
      //go FORWARD in time
      echo "<a title=\"" . 
  	translate("Next") . "&nbsp;$PAGE_SIZE&nbsp;" . 
	translate("Events") . "\" class=\"next\" href=\"activity_log.php$prevarg\">" . 
  	translate("Next") . "&nbsp;$PAGE_SIZE&nbsp;" . 
	translate("Events") . "</a><br />\n";
    }
    dbi_free_result ( $res );
  }
}
?>
</div>
<?php print_trailer(); ?>
</body>
</html>
