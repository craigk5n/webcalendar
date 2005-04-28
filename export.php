<?php
/*
 * Page Description:
 *	This page will present the user with forms for exporting calendar
 *  events.
 *
 * Input Parameters:
 *	None
 *
 */
include_once 'includes/init.php';

if ( empty ( $login) || $login == "__public__" ) {
  // do not allow public access
  do_redirect ( empty ( $STARTVIEW ) ? "month.php" : "$STARTVIEW" );
  exit;
}

$INC = array('js/export.php');
print_header($INC);
?>

<h2><?php etranslate("Export")?></h2>

<form action="export_handler.php" method="post" name="exportform">
<table style="border-width:0px;">
<tr><td>
	<label for="exformat"><?php etranslate("Export format")?>:</label></td><td>
	<select name="format" id="exformat">
		<option value="ical">iCalendar</option>
		<option value="vcal">vCalendar</option>
		<option value="pilot-csv">Pilot-datebook CSV (<?php etranslate("Palm Pilot")?>)</option>
		<option value="pilot-text">Install-datebook (<?php etranslate("Palm Pilot")?>)</option>
	</select>
</td></tr>
<?php  // Only include layers if they are enabled.
	if ( ! empty ( $LAYERS_STATUS ) && $LAYERS_STATUS == 'Y' ) {
?>
<tr><td>&nbsp;
	</td><td>
	<input type="checkbox" name="include_layers" id="include_layers" value="y" />
	<label for="include_layers"><?php etranslate("Include all layers")?></label>
</td></tr>
<?php } ?>

<tr><td>&nbsp;
	</td><td>
	<input type="checkbox" name="use_all_dates" id="exportall" value="y" />
	<label for="exportall"><?php etranslate("Export all dates")?></label>
</td></tr>
<tr><td>
	<label><?php etranslate("Start date")?>:</label></td><td>
	<select name="fromday">
		<?php
			$day = date ( "d" );
			for ( $i = 1; $i <= 31; $i++ ) echo "<option" . ( $i == $day ? " selected=\"selected\"" : "" ) . ">$i</option>\n";
		?>
	</select>
	<select name="frommonth">
		<?php
			$month = date ( "m" );
			$year = date ( "Y" );
			for ( $i = 1; $i <= 12; $i++ ) {
				$m = month_short_name ( $i - 1 );
				print "<option value=\"$i\"" . ( $i == $month ? " selected=\"selected\"" : "" ) . ">$m</option>\n";
			}
		?>
	</select>
	<select name="fromyear">
		<?php
			$year = date ( "Y" ) - 1;
			for ( $i = -1; $i < 5; $i++ ) {
				$y = date ( "Y" ) + $i;
				print "<option value=\"$y\"" . ( $y == $year ? " selected=\"selected\"" : "" ) . ">$y</option>\n";
			}
		?>
	</select>
	<input type="button" onclick="selectDate('fromday','frommonth','fromyear', '', event)" value="<?php etranslate("Select")?>..." />
</td></tr>

<tr><td>
	<label><?php etranslate("End date")?>:</label></td><td>
	<select name="endday">
		<?php
			$day = date ( "d" );
			for ( $i = 1; $i <= 31; $i++ ) echo "<option" . ( $i == $day ? " selected=\"selected\"" : "" ) . ">$i</option>\n";
		?>
	</select>
	<select name="endmonth">
		<?php
			$month = date ( "m" );
			$year = date ( "Y" );
			for ( $i = 1; $i <= 12; $i++ ) {
				$m = month_short_name ( $i - 1 );
				print "<option value=\"$i\"" . ( $i == $month ? " selected=\"selected\"" : "" ) . ">$m</option>\n";
			}
		?>
	</select>
	<select name="endyear">
		<?php
			$year = date ( "Y" ) + 1;
			for ( $i = -1; $i < 5; $i++ ) {
				$y = date ( "Y" ) + $i;
				print "<option value=\"$y\"" . ( $y == $year ? " selected=\"selected\"" : "" ) . ">$y</option>\n";
			}
		?>
	</select>
	<input type="button" onclick="selectDate('endday','endmonth','endyear', '', event)" value="<?php etranslate("Select")?>..." />
</td></tr>

<tr><td>
	<label><?php etranslate("Modified since")?>:</label></td><td>
	<select name="modday">
		<?php
			$week_ago = mktime ( 0, 0, 0, date ( "m" ), date ( "d" ) - 7, date ( "Y" ) );
			$day = date ( "d", $week_ago );
			for ( $i = 1; $i <= 31; $i++ ) echo "<option" . ( $i == $day ? " selected=\"selected\"" : "" ) . ">$i</option>\n";
		?>
	</select>
	<select name="modmonth">
		<?php
			$month = date ( "m", $week_ago );
			$year = date ( "Y", $week_ago );
			for ( $i = 1; $i <= 12; $i++ ) {
				$m = month_short_name ( $i - 1 );
				print "<option value=\"$i\"" . ( $i == $month ? " selected=\"selected\"" : "" ) . ">$m</option>\n";
			}
		?>
	</select>
	<select name="modyear">
		<?php
			$year = date ( "Y", $week_ago );
			for ( $i = -1; $i < 5; $i++ ) {
				$y = date ( "Y" ) + $i;
				print "<option value=\"$y\"" . ( $y == $year ? " selected=\"selected\"" : "" ) . ">$y</option>\n";
			}
		?>
	</select>
	<input type="button" onclick="selectDate('modday','modmonth','modyear', '', event)" value="<?php etranslate("Select")?>..." />
</td></tr>

<tr><td colspan="2">
	<input type="submit" value="<?php etranslate("Export");?>" />
</td></tr>
</table>
</form>
<?php print_trailer (); ?>
</body>
</html>
