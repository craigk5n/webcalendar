<?php
include_once 'includes/init.php';

$INC = array('js/export.php');
print_header($INC);
?>

<h2><font color="<?php echo $H2COLOR;?>"><?php etranslate("Export")?></font></h2>

<form action="export_handler.php" method="post" name="exportform">

<table border="0">
<tr><td><b><?php etranslate("Export format")?>:</b></td><td><select name="format">
  <option value="ical">iCalendar</option>
  <option value="vcal">vCalendar</option>
  <option value="pilot-csv">pilot-datebook CSV (<?php etranslate("Palm Pilot")?>)</option>
  <option value="pilot-text">install-datebook (<?php etranslate("Palm Pilot")?>)</option>
</select></td></tr>
<tr><td></td><td><input type="checkbox" name="use_all_dates" value="y" />
  <b><?php etranslate("Export all dates")?></b></td></tr>
<tr><td><b><?php etranslate("Start date")?>:</b></td>
  <td><select name="fromday">
<?php
  $day = date ( "d" );
  for ( $i = 1; $i <= 31; $i++ ) echo "<option" . ( $i == $day ? " SELECTED=\"SELECTED\"" : "" ) . ">$i</option>";
?>
  </select>
  <select name="frommonth">
<?php
  $month = date ( "m" );
  $year = date ( "Y" );
  for ( $i = 1; $i <= 12; $i++ ) {
    $m = month_short_name ( $i - 1 );
    print "<option value=\"$i\"" . ( $i == $month ? " SELECTED=\"SELECTED\"" : "" ) . ">$m</option>";
  }
?>
  </select>
  <select name="fromyear">
<?php
  $year = date ( "Y" ) - 1;
  for ( $i = -1; $i < 5; $i++ ) {
    $y = date ( "Y" ) + $i;
    print "<option value=\"$y\"" . ( $y == $year ? " SELECTED=\"SELECTED\"" : "" ) . ">$y</option>";
  }
?>
  </select>
  <input type="button" onclick="selectDate('fromday','frommonth','fromyear')" value="<?php etranslate("Select")?>..." />
</td></tr>

<tr><td><b><?php etranslate("End date")?>:</b></td>
  <td><select name="endday">
<?php
  $day = date ( "d" );
  for ( $i = 1; $i <= 31; $i++ ) echo "<option" . ( $i == $day ? " SELECTED=\"SELECTED\"" : "" ) . ">$i</option>";
?>
  </select>
  <select name="endmonth">
<?php
  $month = date ( "m" );
  $year = date ( "Y" );
  for ( $i = 1; $i <= 12; $i++ ) {
    $m = month_short_name ( $i - 1 );
    print "<option value=\"$i\"" . ( $i == $month ? " SELECTED=\"SELECTED\"" : "" ) . ">$m</option>";
  }
?>
  </select>
  <select name="endyear">
<?php
  $year = date ( "Y" ) + 1;
  for ( $i = -1; $i < 5; $i++ ) {
    $y = date ( "Y" ) + $i;
    print "<option value=\"$y\"" . ( $y == $year ? " SELECTED=\"SELECTED\"" : "" ) . ">$y</option>";
  }i
?>
  </select>
  <input type="button" onclick="selectDate('endday','endmonth','endyear')" value="<?php etranslate("Select")?>..." />
</td></tr>

<tr><td><b><?php etranslate("Modified since")?>:</b></td>
  <td><select name="modday">
<?php
  $week_ago = mktime ( 0, 0, 0, date ( "m" ), date ( "d" ) - 7, date ( "Y" ) );
  $day = date ( "d", $week_ago );
  for ( $i = 1; $i <= 31; $i++ ) echo "<option " . ( $i == $day ? " SELECTED=\"SELECTED\"" : "" ) . ">$i</option>";
?>
  </select>
  <select name="modmonth">
<?php
  $month = date ( "m", $week_ago );
  $year = date ( "Y", $week_ago );
  for ( $i = 1; $i <= 12; $i++ ) {
    $m = month_short_name ( $i - 1 );
    print "<option value=\"$i\"" . ( $i == $month ? " SELECTED=\"SELECTED\"" : "" ) . ">$m</option>";
  }
?>
  </select>
  <select name="modyear">
<?php
  $year = date ( "Y", $week_ago );
  for ( $i = -1; $i < 5; $i++ ) {
    $y = date ( "Y" ) + $i;
    print "<option value=\"$y\"" . ( $y == $year ? " SELECTED=\"SELECTED\"" : "" ) . ">$y</option>";
  }
?>
  </select>
  <input type="button" onclick="selectDate('modday','modmonth','modyear')" value="<?php etranslate("Select")?>..." />
</td></tr>

<tr><td colspan="2"><input type="submit" value="<?php etranslate("Export");?>" /></td></tr>
</table>
</form>

<?php print_trailer(); ?>
</body>
</html>
