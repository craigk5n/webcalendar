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
?>

<H2><FONT COLOR="<?= $H2COLOR;?>">Import</FONT></H2>

<FORM ACTION="import_handler.php" METHOD="POST" NAME="importform" enctype="multipart/form-data">
<H3><FONT COLOR="<?= $H2COLOR;?>">Palm Desktop Datebook</FONT></H3>
<TABLE BORDER="0" CELLSPACING="0" CELLPADDING="0"><TR><TD BGCOLOR="<?= $TEXTCOLOR ?>"><TABLE BORDER="0" WIDTH="100%" CELLSPACING="1" CELLPADDING="2"><TR><TD WIDTH="100%" BGCOLOR="<?php echo $CELLBG ?>"><TABLE BORDER="0" WIDTH="100%">
<P>
This form will import entries from the Palm Desktop Datebook. It should be located in
your Palm directory under <B>palm_user_name/datebook/datebook.dat</B>.
The following entries <B>will not</b> be imported:<br>
<UL>
<LI>Entries older than the current time (except repeating events that have not expired).
<LI>Entries created in the Palm Desktop that have NOT been HotSync'd.<BR> (because a
record_id isn't created until the hotsync)
</UL>

<B>NOTE:</B> Anything imported from the Palm will be overwritten during the next import
(unless the event date has passed).
Therefore, updates should be made in the Palm/Desktop rather than the web calendar.
</P>
<P>
<INPUT TYPE="hidden" NAME="ImportType" VALUE="PALMDESKTOP">
<B>Exclude Private Records:</B>
<INPUT TYPE=radio NAME=exc_private VALUE="1" CHECKED>Yes
<INPUT TYPE=radio NAME=exc_private VALUE="0">No

<TABLE BORDER=0>
<TR><TD><B><?php etranslate("Select Datebook File")?>:</B></TD>
  <TD><INPUT TYPE="file" NAME="FileName" SIZE=45 MAXLENGTH=50"> &nbsp;
ex: C:\palm\hooverj\datebook\datebook.dat</TD></TR>
<TR><TD COLSPAN="2"><INPUT TYPE="submit" VALUE="<?php etranslate("Import")?>"></TD></TR>
</TABLE></P>
</TD></TR></TABLE></TD></TR></TABLE></TD></TR></TABLE>
</FORM>


<FORM ACTION="import_handler.php" METHOD="POST" NAME="importform" enctype="multipart/form-data">
<H3><FONT COLOR="<?= $H2COLOR;?>">vCalendar</FONT></H3>
<TABLE BORDER="0" CELLSPACING="0" CELLPADDING="0"><TR><TD BGCOLOR="<?= $TEXTCOLOR ?>"><TABLE BORDER="0" WIDTH="100%" CELLSPACING="1" CELLPADDING="2"><TR><TD WIDTH="100%" BGCOLOR="<?php echo $CELLBG ?>"><TABLE BORDER="0" WIDTH="100%">
<P>
This form will import vCalendar (.vcs) 1.0 events.<br>
<BR>
The following formats have been tested:<BR>
<UL>
<LI>Palm Desktop 4</LI>
<LI>Lotus Organizer 6</LI>
</UL>
</P>
<P>
<INPUT TYPE="hidden" NAME="ImportType" VALUE="VCAL">

<TABLE BORDER=0>
<TR><TD><B><?php etranslate("Select vCal File")?>:</B></TD>
  <TD><INPUT TYPE="file" NAME="FileName" SIZE=45 MAXLENGTH=50"> &nbsp; </TD></TR>
<TR><TD COLSPAN="2"><INPUT TYPE="submit" VALUE="<?php etranslate("Import")?>"></TD></TR>
</TABLE></P>
</TD></TR></TABLE></TD></TR></TABLE></TD></TR></TABLE>
</FORM>


<?php include "includes/trailer.php"; ?>
</BODY>
</HTML>
