<?php
include_once 'includes/init.php';
print_header('','','',true);
?>

<H2><FONT COLOR="<?php echo $H2COLOR;?>"><?php etranslate("Help")?>: <?php etranslate("Preferences")?></FONT></H2>

<H3><?php etranslate("Settings")?></H3>
<TABLE BORDER=0>

<TR><TD VALIGN="top"><B><?php etranslate("Language")?>:</B></TD>
  <TD><?php etranslate("language-help")?></TD></TR>
<TR><TD VALIGN="top"><B><?php etranslate("Fonts")?>:</B></TD>
  <TD><?php etranslate("fonts-help")?></TD></TR>
<TR><TD VALIGN="top"><B><?php etranslate("Preferred view")?>:</B></TD>
  <TD><?php etranslate("preferred-view-help")?></TD></TR>
<TR><TD VALIGN="top"><B><?php etranslate("Display weekends in week view")?>:</B></TD>
  <TD><?php etranslate("display-weekends-help")?></TD></TR>
<TR><TD VALIGN="top"><B><?php etranslate("Display description in printer day view")?>:</B></TD>
  <TD><?php etranslate("display-desc-print-day-help")?></TD></TR>
<TR><TD VALIGN="top"><B><?php etranslate("Date format")?>:</B></TD>
  <TD><?php etranslate("date-format-help")?></TD></TR>
<TR><TD VALIGN="top"><B><?php etranslate("Time format")?>:</B></TD>
  <TD><?php etranslate("time-format-help")?></TD></TR>
<TR><TD VALIGN="top"><B><?php etranslate("Time interval")?>:</B></TD>
  <TD><?php etranslate("time-interval-help")?></TD></TR>
<TR><TD VALIGN="top"><B><?php etranslate("Display unapproved")?>:</B></TD>
  <TD><?php etranslate("display-unapproved-help")?></TD></TR>
<TR><TD VALIGN="top"><B><?php etranslate("Display week number")?>:</B></TD>
  <TD><?php etranslate("display-week-number-help")?></TD></TR>
<TR><TD VALIGN="top"><B><?php etranslate("Week starts on")?>:</B></TD>
  <TD><?php etranslate("display-week-starts-on")?></TD></TR>
<TR><TD VALIGN="top"><B><?php etranslate("Work hours")?>:</B></TD>
  <TD><?php etranslate("work-hours-help")?>
<TR><TD VALIGN="top"><B><?php etranslate("Default Category")?>:</B></TD>
  <TD><?php etranslate("default-category-help")?>
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

<?php if ( $PUBLISH_ENABLED == 'Y' ) { ?>
<H3><?php etranslate("Subscribe/Publish")?></H3>
<TABLE BORDER=0>
<TR><TD VALIGN="top"><B><?php etranslate("Allow remote subscriptions")?>:</B></TD>
  <TD><?php etranslate("allow-remote-subscriptions-help")?></TD></TR>
<TR><TD VALIGN="top"><B><?php etranslate("URL")?>:</B></TD>
  <TD><?php etranslate("remote-subscriptions-url-help")?></TD></TR>
</TABLE>
<?php } ?>

<?php if ( $allow_color_customization == 'Y' ) { ?>
<H3><?php etranslate("Colors")?></H3>
<?php etranslate("colors-help")?>
<P>
<?php } // if $allow_color_customization ?>

<?php include_once "includes/help_trailer.php"; ?>

</BODY>
</HTML>
