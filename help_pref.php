<?php
include_once 'includes/init.php';
print_header('','','',true);
?>

<h2 style="color:<?php echo $H2COLOR;?>;"><?php etranslate("Help")?>: <?php etranslate("Preferences")?></h2>

<h3><?php etranslate("Settings")?></h3>
<table style="border-width:0px;">

<tr><td valign="top" style="font-weight:bold;"><?php etranslate("Language")?>:</td>
  <td><?php etranslate("language-help")?></td></tr>
<tr><td valign="top" style="font-weight:bold;"><?php etranslate("Fonts")?>:</td>
  <td><?php etranslate("fonts-help")?></td></tr>
<tr><td valign="top" style="font-weight:bold;"><?php etranslate("Preferred view")?>:</td>
  <td><?php etranslate("preferred-view-help")?></td></tr>
<tr><td valign="top" style="font-weight:bold;"><?php etranslate("Display weekends in week view")?>:</td>
  <td><?php etranslate("display-weekends-help")?></td></tr>
<tr><td valign="top" style="font-weight:bold;"><?php etranslate("Display description in printer day view")?>:</td>
  <td><?php etranslate("display-desc-print-day-help")?></td></tr>
<tr><td valign="top" style="font-weight:bold;"><?php etranslate("Date format")?>:</td>
  <td><?php etranslate("date-format-help")?></td></tr>
<tr><td valign="top" style="font-weight:bold;"><?php etranslate("Time format")?>:</td>
  <td><?php etranslate("time-format-help")?></td></tr>
<tr><td valign="top" style="font-weight:bold;"><?php etranslate("Time interval")?>:</td>
  <td><?php etranslate("time-interval-help")?></td></tr>
<tr><td valign="top" style="font-weight:bold;"><?php etranslate("Display unapproved")?>:</td>
  <td><?php etranslate("display-unapproved-help")?></td></tr>
<tr><td valign="top" style="font-weight:bold;"><?php etranslate("Display week number")?>:</td>
  <td><?php etranslate("display-week-number-help")?></td></tr>
<tr><td valign="top" style="font-weight:bold;"><?php etranslate("Week starts on")?>:</td>
  <td><?php etranslate("display-week-starts-on")?></td></tr>
<tr><td valign="top" style="font-weight:bold;"><?php etranslate("Work hours")?>:</td>
  <td><?php etranslate("work-hours-help")?></td></tr>
<tr><td valign="top" style="font-weight:bold;"><?php etranslate("Default Category")?>:</td>
  <td><?php etranslate("default-category-help")?></td></tr>
</table>

<br /><br />

<h3><?php etranslate("Email")?></h3>
<table style="border-width:0px;">
<tr><td valign="top" style="font-weight:bold;"><?php etranslate("Event reminders")?>:</td>
  <td><?php etranslate("email-event-reminders-help")?></td></tr>
<tr><td valign="top" style="font-weight:bold;"><?php etranslate("Events added to my calendar")?>:</td>
  <td><?php etranslate("email-event-added")?></td></tr>
<tr><td valign="top" style="font-weight:bold;"><?php etranslate("Events updated on my calendar")?>:</td>
  <td><?php etranslate("email-event-updated")?></td></tr>
<tr><td valign="top" style="font-weight:bold;"><?php etranslate("Events removed from my calendar")?>:</td>
  <td><?php etranslate("email-event-deleted")?></td></tr>
<tr><td valign="top" style="font-weight:bold;"><?php etranslate("Event rejected by participant")?>:</td>
  <td><?php etranslate("email-event-rejected")?></td></tr>
</table>

<?php if ( $PUBLISH_ENABLED == 'Y' ) { ?>
<h3><?php etranslate("Subscribe/Publish")?></h3>
<table style="border-width:0px;">
<tr><td valign="top" style="font-weight:bold;"><?php etranslate("Allow remote subscriptions")?>:</td>
  <td><?php etranslate("allow-remote-subscriptions-help")?></td></tr>
<tr><td valign="top" style="font-weight:bold;"><?php etranslate("URL")?>:</td>
  <td><?php etranslate("remote-subscriptions-url-help")?></td></tr>
</table>
<?php } ?>

<?php if ( $allow_color_customization == 'Y' ) { ?>
<h3><?php etranslate("Colors")?></h3>
<?php etranslate("colors-help")?>
<br /><br />
<?php } // if $allow_color_customization ?>

<?php include_once "includes/help_trailer.php"; ?>

</body>
</html>