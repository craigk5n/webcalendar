<?php php_track_vars?>
<?php
include "includes/config.inc";
include "includes/php-dbi.inc";
include "includes/functions.inc";
include "includes/$user_inc";
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

<H2><FONT COLOR="<?php echo $H2COLOR;?>"><?php etranslate("Help")?>: <?php etranslate("Preferences")?></FONT></H2>

<H3><?php etranslate("Settings")?></H3>
<TABLE BORDER=0>

<TR><TD VALIGN="top"><B><?php etranslate("Language")?>:</B></TD>
  <TD><?php etranslate("language-help")?></TD></TR>
<TR><TD VALIGN="top"><B><?php etranslate("Preferred view")?>:</B></TD>
  <TD><?php etranslate("preferred-view-help")?></TD></TR>
<TR><TD VALIGN="top"><B><?php etranslate("Time format")?>:</B></TD>
  <TD><?php etranslate("time-format-help")?></TD></TR>
<TR><TD VALIGN="top"><B><?php etranslate("Display unapproved")?>:</B></TD>
  <TD><?php etranslate("display-unapproved-help")?></TD></TR>
<!--
<TR><TD VALIGN="top"><B><?php etranslate("Display icons")?>:</B></TD>
  <TD><?php etranslate("display-icons-help")?></TD></TR>
-->
<TR><TD VALIGN="top"><B><?php etranslate("Display week number")?>:</B></TD>
  <TD><?php etranslate("display-week-number-help")?></TD></TR>
<TR><TD VALIGN="top"><B><?php etranslate("Week starts on")?>:</B></TD>
  <TD><?php etranslate("display-week-starts-on")?></TD></TR>
<TR><TD VALIGN="top"><B><?php etranslate("Work hours")?>:</B></TD>
  <TD><?php etranslate("work-hours-help")?>
      </TD></TR>

</TABLE>
<P>

<H3><?php etranslate("Email")?></H3>
<TABLE BORDER=0>
<TR><TD VALIGN="top"><B><?php etranslate("Event reminders")?>:</B></TD>
  <TD><?php etranslate("email-event-reminders-help")?></TD></TR>
<TR><TD VALIGN="top"><B><?php etranslate("Events added to my calendar")?>:</B></TD>
  <TD><?php etranslate("email-event-added")?></TD></TR>
<TR><TD VALIGN="top"><B><?php etranslate("Events updated on my calendar")?>:</B></TD>
  <TD><?php etranslate("email-event-updated")?></TD></TR>
<TR><TD VALIGN="top"><B><?php etranslate("Events removed from my calendar")?>:</B></TD>
  <TD><?php etranslate("email-event-deleted")?></TD></TR>
<TR><TD VALIGN="top"><B><?php etranslate("Event rejected by participant")?>:</B></TD>
  <TD><?php etranslate("email-event-rejected")?></TD></TR>
</TABLE>

<?php if ( $allow_color_customization ) { ?>
<H3><?php etranslate("Colors")?></H3>
<?php etranslate("colors-help")?>
<P>
<?php } // if $allow_color_customization ?>

<?php include "includes/help_trailer.inc"; ?>

</BODY>
</HTML>
