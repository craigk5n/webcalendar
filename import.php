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
 *	Need to either move some of the instructions into translate()
 *	functions or remove them.
 *
 *	Might be nice to allow user to set the category for all imported
 *	events.  So, a user could easily export events from the work
 *	calendar and import them into WebCalendar with a category
 *	"work".
 *
 */
include_once 'includes/init.php';
print_header('','<link href="includes/tabs.css" rel="stylesheet" type="text/css">');

$tabs = array( "Palm Desktop", "vCalendar", "iCalendar" );
if ( empty ( $tab ) ) $tab = 0;


// $items - array of titles for tab
// $sel - which item is currently selected (0 = first)
function print_tabs ( $items, $sel=0 ) {
  // Start tab block
  echo "<div class=\"tabbox\" style=\"clear:both;\">\n";
  echo "  <div class=\"tabarea\">\n";

  // Print each tab
  for ( $i = 0; $i < count ( $items ); $i++ ) {
    echo "    <a class=\"tab";
    if ( $i == $sel ) echo " active";
    echo "\" href=\"import.php?tab=$i\" style=\"font-weight:bold;font-size:18;\">$items[$i]</a>\n";
  }
  echo "  </div>\n";

  // Start content area
  echo "  <div class=\"tabmain\">\n";
  echo "    <div class=\"tabcontent\"><br>\n";
}

// Close our tab area
function end_tabs() {
  echo "    <br /></div>\n";
  echo "  </div>\n";
  echo "</div>\n";
}
?>

<h2><font color="<?= $H2COLOR;?>">Import</font></h2>

<form action="import_handler.php" method="post" name="importform" enctype="multipart/form-data">
<?php
print_tabs ( $tabs, $tab );

if ( $tab == 0 ) {
?>

<br />
<?php etranslate("This form will allow you to import entries from the Palm Desktop Datebook."); ?>
<br /><br />
<input type="hidden" name="ImportType" value="PALMDESKTOP" />
<b><?php etranslate("Exclude private records")?>:</b>
<input type="radio" name="exc_private" value="1" CHECKED="CHECKED"><?php etranslate("Yes")?>
<input type="radio" name="exc_private" value="0"><?php etranslate("No")?>
<br /><br />
<table border="0">
<tr><td><b><?php etranslate("Datebook File")?>:</b></td>
  <td><input type="file" name="FileName" size="45" maxlength="50" /></td></tr>
<tr><td colspan="2"><input type="submit" value="<?php etranslate("Import")?>" />
<input type="button" value="<?php etranslate("Help")?>..." onclick="window.open ( 'help_import.php', 'cal_help', 'dependent,menubar,scrollbars,height=400,width=400');" />
</td></tr>
</table>

<?php
} else if ( $tab == 1 ) {
?>


<br />
<?php etranslate("This form will import vCalendar (.vcs) 1.0 events");?>.
<br /><br />
<input type="hidden" name="ImportType" value="VCAL" />
<table border="0">
<tr><td><b><?php etranslate("vCal File")?>:</b></td>
  <td><input type="file" name="FileName" size="45" maxlength=50" /></td></tr>
<tr><td colspan="2"><input type="submit" value="<?php etranslate("Import")?>" />
<input type="button" value="<?php etranslate("Help")?>..." onclick="window.open ( 'help_import.php', 'cal_help', 'dependent,menubar,scrollbars,height=400,width=400');" />
</td></tr>
</table>

<?php
} else if ( $tab == 2 ) {
?>

<br />
<?php etranslate("This form will import iCalendar (.ics) events");?>.
<br /><br />
<input type="hidden" name="ImportType" value="ICAL" />
<table border="0">
<tr><td><b><?php etranslate("iCal File")?>:</b></td>
  <td><input type="file" name="FileName" size="45" maxlength=50" /></td></tr>
<tr><td><b><?php etranslate("Overwrite Prior Import")?>:</b></td>
  <td><input type="radio" name="overwrite" value="Y" CHECKED="CHECKED" /> <?php etranslate("Yes");?>
  &nbsp;&nbsp;
  <input type="radio" name="overwrite" value="N"/> <?php etranslate("No");?>
   </td></tr>
<tr><td colspan="2"><input type="submit" value="<?php etranslate("Import")?>" />
<input type="button" value="<?php etranslate("Help")?>..." onclick="window.open ( 'help_import.php', 'cal_help', 'dependent,menubar,scrollbars,height=400,width=400');" />
</td></tr>
</table>

<?php
} else {
  echo "No such tab!";
}
end_tabs();
echo "</form>";

print_trailer ();
?>
</body>
</html>
