<?php
/*
 * $Id$
 *
 * Page Description:
 *	This page will present the user with forms for submitting
 *	a data file to import.
 *
 * Input Parameters:
 *	None
 *
 * Comments:
 *	Might be nice to allow user to set the category for all imported
 *	events.  So, a user could easily export events from the work
 *	calendar and import them into WebCalendar with a category
 *	"work".
 */
include_once 'includes/init.php';

$INC = array('js/export.php','js/visible.php');
print_header($INC);

// Generate the selection list for calendar user selection.
// Only ask for calendar user if user is an administrator.
// We may enhance this in the future to allow
// - selection of more than one user
// - non-admin users this functionality
function print_user_list () {
  global $single_user, $is_admin, $nonuser_enabled, $login,
    $is_nonuser_admin, $is_assistant;

  if ( $single_user == "N" && $is_admin ) {
    $userlist = get_my_users ();
    if ($nonuser_enabled == "Y" ) {
      $nonusers = get_nonuser_cals ();
      $userlist = ( ! empty ( $nonuser_at_top ) && $nonuser_at_top == "Y") ?
        array_merge($nonusers, $userlist) : array_merge($userlist, $nonusers);
    }
    $num_users = 0;
    $size = 0;
    $users = "";
    for ( $i = 0; $i < count ( $userlist ); $i++ ) {
      $l = $userlist[$i]['cal_login'];
      $size++;
      $users .= "<option value=\"" . $l . "\"";
      if ( ! empty ( $id ) && $id > 0 ) {
        if ( ! empty ( $participants[$l] ) )
          $users .= " selected=\"selected\"";
      } else {
        if ( $l == $login && ! $is_assistant  && ! $is_nonuser_admin )
          $users .= " selected=\"selected\"";
      }
      $users .= ">" . $userlist[$i]['cal_fullname'] . "</option>\n";
    }
  
    if ( $size > 50 )
      $size = 15;
    else if ( $size > 5 )
      $size = 5;
    print "<tr><td style=\"vertical-align:top;\"><label for=\"caluser\">" . translate("Calendar") . "</label></td>\n";
    print "<td><select name=\"calUser\" id=\"caluser\" size=\"$size\">$users\n";
    print "</select>\n";
    print "</td></tr>\n";
  }
}
?>

<h2>Import&nbsp;<img src="help.gif" alt="<?php etranslate("Help")?>" class="help" onclick="window.open ( 'help_import.php', 'cal_help', 'dependent,menubar,scrollbars,height=400,width=400');" /> / <?php etranslate("Export")?></h2>

<!-- TABS -->
<div id="tabs">
	<span class="tabfor" id="tab_import"><a href="#tabimport" onclick="return showTab('import')">Import</a></span>
	<span class="tabbak" id="tab_export"><a href="#tabexport" onclick="return showTab('export')">Export</a></span>
</div>

<!-- TABS BODY -->
<div id="tabscontent">
	<!-- DETAILS -->
	<a name="tabimport"></a>
	<div id="tabscontent_import">
	<form action="import_handler.php" method="post" name="importform" enctype="multipart/form-data">
<form action="export_handler.php" method="post" name="exportform">
<table style="border-width:0px;">
<tr><td>
	<label for="importtype"><?php etranslate("Import format")?>:</label></td><td>
		<select name="ImportType">
			<option value="PALMDESKTOP">Palm Desktop</option>
			<option value="vcal">vCal</option>
			<option value="ICAL">iCal</option>
		</select>
</td></tr>
<tr id="palm"><td><label>
		<?php etranslate("Exclude private records")?>:</label></td><td>
		<label><input type="radio" name="exc_private" value="1" checked="checked" />&nbsp;<?php etranslate("Yes")?></label>
		&nbsp;&nbsp;<label><input type="radio" name="exc_private" value="0" />&nbsp;<?php etranslate("No")?></label>
</td></tr>
<!-- /PALM -->

<tr id="ivcal"><td><label>
	<?php etranslate("Overwrite Prior Import")?>:</label></td<td>
	<label><input type="radio" name="overwrite" value="Y" checked="checked" />&nbsp;<?php etranslate("Yes");?></label>
	&nbsp;&nbsp;<label><input type="radio" name="overwrite" value="N" />&nbsp;<?php etranslate("No");?></label>
</td></tr>
<!-- /IVCAL -->

<tr class="browse"><td><label>
	Upload file:</label></td><td>
	<input type="file" name="FileName" size="45" maxlength="50" />
</td></tr>

<?php print_user_list(); ?>

</table>
<br /><input type="submit" value="<?php etranslate("Import")?>" />
</form>
</div> <!-- /IMPORT -->

	<!-- EXPORT -->
	<a name="tabexport"></a>
	<div id="tabscontent_export">
<form action="export_handler.php" method="post" name="exportform">
<table style="border-width:0px;">
<tr><td>
	<label for="exformat"><?php etranslate("Export format")?>:</label></td><td>
	<select name="format" id="exformat">
		<option value="ical">iCalendar</option>
		<option value="vcal">vCalendar</option>
		<option value="pilot-csv">pilot-datebook CSV (<?php etranslate("Palm Pilot")?>)</option>
		<option value="pilot-text">install-datebook (<?php etranslate("Palm Pilot")?>)</option>
	</select>
</td></tr>

<?php  // Only include layers if they are enabled.
	if ( ! empty ( $LAYERS_STATUS ) && $LAYERS_STATUS == 'Y' ) {
?>
<tr><td>
	&nbsp;</td><td>
	<input type="checkbox" name="include_layers" id="include_layers" value="y" />
	<label for="include_layers" style="font-weight:bold;"><?php etranslate("Include all layers")?></label>
</td></tr>
<?php } ?>

<tr><td>
	&nbsp;</td><td>
	<input type="checkbox" name="use_all_dates" id="exportall" value="y" />
	<label for="exportall"><?php etranslate("Export all dates")?></label>
</td></tr>
<tr><td style="font-weight:bold;"><label>
	<?php etranslate("Start date")?>:</label></td><td>
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
	<input type="button" onclick="selectDate('fromday','frommonth','fromyear')" value="<?php etranslate("Select")?>..." />
</td></tr>

<tr><td style="font-weight:bold;"><label>
	<?php etranslate("End date")?>:</label></td><td>
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
	<input type="button" onclick="selectDate('endday','endmonth','endyear')" value="<?php etranslate("Select")?>..." />
</td></tr>

<tr><td style="font-weight:bold;"><label>
	<?php etranslate("Modified since")?>:</label></td><td>
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
	<input type="button" onclick="selectDate('modday','modmonth','modyear')" value="<?php etranslate("Select")?>..." />
</td></tr>

<tr><td colspan="2">
	<input type="submit" value="<?php etranslate("Export");?>" />
</td></tr>
</table>
</form>
</div> <!-- /EXPORT -->
</div>
<?php print_trailer (); ?>
</body>
</html>
