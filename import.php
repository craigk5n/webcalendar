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
print_header();

// This is really a poor man's windows-style tab.
// If anyone can put together something that looks nicer without
// having to resort to images, please do!
// $items - array of titles for tab
// $sel - which item is currently selected (0 = first)
function print_tab ( $items, $sel=0 ) {
  $width = sprintf ( "%2d", 100 / count ( $items ) );
  print '<tr>';
  for ( $i = 0; $i < count ( $items ); $i++ ) {
    if ( $i > 0 ) {
      print "<td width=\"1\" bgcolor=\"" . $GLOBALS['TABLEBG'] . "\">" .
        "<img src=\"spacer.gif\" width=\"1\" height=\"50\"></td>";
    }
    if ( $i == $sel ) {
      $color = $GLOBALS['CELLBG'];
      $title = $items[$i];
    } else {
      $color = $GLOBALS['BGCOLOR'];
      $title = "<a href=\"import.php?tab=$i\">$items[$i]</a>";
    }
    print '<td width="' . $width . '%" bgcolor="' . $color . '">' .
      "<h2><center>$title</center></h2></td>";
    
  }
  print '</td></tr><tr>';

  for ( $i = 0; $i < count ( $items ); $i++ ) {
    if ( $i > 0 )
      print "<td></td>";
    if ( $i == $sel ) {
      $color = $GLOBALS['CELLBG'];
    } else {
      $color = $GLOBALS['TABLEBG'];
    }
    print '<td width="1" bgcolor="' . $color . '">' .
      "<img src=\"spacer.gif\" width=\"" . $width . "%\" height=\"1\"></td>";
    
  }
  print "</tr>";
}


?>

<h2><font color="<?= $H2COLOR;?>">Import</font></h2>

<form action="import_handler.php" method="POST" name="importform" enctype="multipart/form-data">
<table border="0" cellspacing="0" cellpadding="0" width="75%"><tr><td bgcolor="<?= $TEXTCOLOR ?>"><table border="0" width="100%" cellspacing="1" cellpadding="2"><tr><td width="100%" bgcolor="<?php echo $CELLBG ?>"><table border="0" width="100%">
<?php
$tabs = array ( "Palm Desktop", "vCal" );
if ( empty ( $tab ) )
  $tab = 0;
print_tab ( $tabs, $tab );
$colspan = 2 * count ( $tabs ) - 1;
?>
<tr><td colspan="<?php echo $colspan; ?>">
<?php if ( $tab == 0 ) { ?>
<h3><font color="<?= $H2COLOR;?>">Palm Desktop</font></h3>
<p>
<?php etranslate("This form will allow you to import entries from the Palm Desktop Datebook."); ?>
</p>
<p>
<input type="hidden" name="ImportType" value="PALMDESKTOP">
<b><?php etranslate("Exclude private records")?>:</b>
<input type=radio name=exc_private value="1" checked><?php etranslate("Yes")?>
<input type=radio name=exc_private value="0"><?php etranslate("No")?>
<p>

<table border=0>
<tr><td><b><?php etranslate("Datebook File")?>:</b></td>
  <td><input type="file" name="FileName" size=45 maxlength=50"> 
<tr><td colspan="2"><input type="submit" value="<?php etranslate("Import")?>">
<input type="button" value="<?php etranslate("Help")?>..."
  onclick="window.open ( 'help_import.php', 'cal_help', 'dependent,menubar,screollbars,height=400,width=400,innerHeight=420,outerWidth=420');">
</td>
</tr>
</table></p>
</td></tr></table>
</td></tr></table></td></tr></table>
</form>

<?php 
} else if ( $tab == 1 ) {
?>


<form action="import_handler.php" method="POST" name="importform" enctype="multipart/form-data">
<h3><font color="<?= $H2COLOR;?>">vCalendar</font></h3>
<p>
<?php etranslate("This form will import vCalendar (.vcs) 1.0 events");?>.
</p>
<p>
<input type="hidden" name="ImportType" value="VCAL">

<table border=0>
<tr><td><b><?php etranslate("vCal File")?>:</b></td>
  <td><input type="file" name="FileName" size="45" maxlength=50"> &nbsp; </td></tr>
<tr><td colspan="2"><input type="submit" value="<?php etranslate("Import")?>">
<input type="button" value="<?php etranslate("Help")?>..."
  onclick="window.open ( 'help_import.php', 'cal_help', 'dependent,menubar,screollbars,height=400,width=400,innerHeight=420,outerWidth=420');">
</td></tr>
</table></p>

<?php 
} else {
  echo "No such tab!";
}
?>
</td></tr></table></td></tr></table></td></tr></table>
</form>


<?php include "includes/trailer.php"; ?>
</BODY>
</HTML>
