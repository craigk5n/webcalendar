<?php php_track_vars?>
<?php
include "includes/config.inc";
include "includes/php-dbi.inc";
include "includes/functions.inc";
include "includes/validate.inc";
include "includes/connect.inc";

load_user_preferences ();
load_user_layers ();

include "includes/translate.inc";

?>
<HTML>
<HEAD>
<TITLE><?php etranslate("Title")?></TITLE>
<?php include "includes/styles.inc"; ?>
</HEAD>
<BODY BGCOLOR="<?php echo $BGCOLOR; ?>">

<H2><FONT COLOR="<?php echo $H2COLOR;?>"><?php etranslate("Help")?>: <?php etranslate("Adding/Editing Calendar Entries")?></FONT></H2>

<TABLE BORDER=0>
<TR>
<TD VALIGN="top"><B><?php etranslate("Brief Description")?>:</B></TD>
  <TD><?php etranslate("brief-description-help")?></TD></TR>
<TD VALIGN="top"><B><?php etranslate("Full Description")?>:</B></TD>
  <TD><?php etranslate("full-description-help")?></TD></TR>
<TD VALIGN="top"><B><?php etranslate("Date")?>:</B></TD>
  <TD><?php etranslate("date-help")?></TD></TR>
<TD VALIGN="top"><B><?php etranslate("Time")?>:</B></TD>
  <TD><?php etranslate("time-help")?></TD></TR>
<TD VALIGN="top"><B><?php etranslate("Duration")?>:</B></TD>
  <TD><?php etranslate("duration-help")?></TD></TR>
<TD VALIGN="top"><B><?php etranslate("Priority")?>:</B></TD>
  <TD><?php etranslate("priority-help")?></TD></TR>
<TD VALIGN="top"><B><?php etranslate("Access")?>:</B></TD>
  <TD><?php etranslate("access-help")?></TD></TR>
<?php if ( ! strlen ( $single_user_login ) ) { ?>
<TD VALIGN="top"><B><?php etranslate("Participants")?>:</B></TD>
  <TD><?php etranslate("participants-help")?></TD></TR>
<?php } ?>
<TD VALIGN="top"><B><?php etranslate("Repeat Type")?>:</B></TD>
  <TD><?php etranslate("repeat-type-help")?></TD></TR>
<TD VALIGN="top"><B><?php etranslate("Repeat End Date")?>:</B></TD>
  <TD><?php etranslate("repeat-end-date-help")?></TD></TR>
<TD VALIGN="top"><B><?php etranslate("Repeat Day")?>:</B></TD>
  <TD><?php etranslate("repeat-day-help")?></TD></TR>
<TD VALIGN="top"><B><?php etranslate("Frequency")?>:</B></TD>
  <TD><?php etranslate("repeat-frequency-help")?></TD></TR>
</TABLE>

<?php include "includes/help_trailer.inc"; ?>

</BODY>
</HTML>
