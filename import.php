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
    echo "\" href=\"import.php?tab=$i\" style=\"font-weight:bold; font-size:18;\">$items[$i]</a>\n";
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
      $userlist = ($nonuser_at_top == "Y") ?
        array_merge($nonusers, $userlist) : array_merge($userlist, $nonusers);
    }
    $num_users = 0;
    $size = 0;
    $users = "";
    for ( $i = 0; $i < count ( $userlist ); $i++ ) {
      $l = $userlist[$i]['cal_login'];
      $size++;
      $users .= "<option value=\"" . $l . "\"";
      if ( $id > 0 ) {
        if ( ! empty ( $participants[$l] ) )
          $users .= " selected=\"selected\"";
      } else {
        if ( $l == $login && ! $is_assistant  && ! $is_nonuser_admin )
          $users .= " selected=\"selected\"";
      }
      $users .= "> " . $userlist[$i]['cal_fullname'] . "</option>\n";
    }
  
    if ( $size > 50 )
      $size = 15;
    else if ( $size > 5 )
      $size = 5;
    print "<tr><td valign=\"top\"><b>" . translate("Calendar") . "</b></td>\n";
    print "<td><select name=\"calUser\" size=\"$size\">$users\n";
    print "</select>";
    print "</td></tr>\n";
  }
}
?>

<h2>Import</h2>

<form action="import_handler.php" method="post" name="importform" enctype="multipart/form-data">
<?php
print_tabs ( $tabs, $tab );

if ( $tab == 0 ) {
?>

<br />
<?php etranslate("This form will allow you to import entries from the Palm Desktop Datebook."); ?>
<br /><br />
<input type="hidden" name="ImportType" value="PALMDESKTOP" />
<table border="0">
<tr><td><b><?php etranslate("Exclude private records")?>:</b></td>
<td><input type="radio" name="exc_private" value="1" CHECKED="CHECKED"><?php etranslate("Yes")?>
<input type="radio" name="exc_private" value="0"><?php etranslate("No")?>
</td></tr>
<?php print_user_list(); ?>
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
<?php print_user_list(); ?>
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
<?php print_user_list(); ?>
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
