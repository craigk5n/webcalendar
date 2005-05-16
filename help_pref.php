<?php
include_once 'includes/init.php';
print_header('','','',true);
?>

<h2><?php etranslate("Help")?>: <?php etranslate("Preferences")?></h2>

<h3><?php etranslate("Settings")?></h3>
<table style="border-width:0px;">
 <tr><td class="help">
  <?php etranslate("Language")?>:</td><td>
  <?php etranslate("language-help")?>
 </td></tr>
  <tr><td class="help">
  <?php etranslate("Timezone Offset")?>:</td><td>
  <?php etranslate("tz-help")?>
 </td></tr>
 <tr><td class="help">
  <?php etranslate("Fonts")?>:</td><td>
  <?php etranslate("fonts-help")?>
 </td></tr>
 <tr><td class="help">
  <?php etranslate("Preferred view")?>:</td><td>
  <?php etranslate("preferred-view-help")?>
 </td></tr>
 <tr><td class="help">
  <?php etranslate("Display weekends in week view")?>:</td><td>
  <?php etranslate("display-weekends-help")?>
 </td></tr>
 <tr><td class="help">
  <?php etranslate("Display description in printer day view")?>:</td><td>
  <?php etranslate("display-desc-print-day-help")?>
 </td></tr>
 <tr><td class="help">
  <?php etranslate("Date format")?>:</td><td>
  <?php etranslate("date-format-help")?>
 </td></tr>
 <tr><td class="help">
  <?php etranslate("Time format")?>:</td><td>
  <?php etranslate("time-format-help")?>
 </td></tr>
 <tr><td class="help">
  <?php etranslate("Time interval")?>:</td><td>
  <?php etranslate("time-interval-help")?>
 </td></tr>
  <tr><td class="help">
  <?php etranslate("Auto-refresh calendars")?>:</td><td>
  <?php etranslate("auto-refresh-help")?>
 </td></tr>
  <tr><td class="help">
  <?php etranslate("Auto-refresh time")?>:</td><td>
  <?php etranslate("auto-refresh-time-help")?>
 </td></tr>
 <tr><td class="help">
  <?php etranslate("Display unapproved")?>:</td><td>
  <?php etranslate("display-unapproved-help")?>
 </td></tr>
 <tr><td class="help">
  <?php etranslate("Display week number")?>:</td><td>
  <?php etranslate("display-week-number-help")?>
 </td></tr>
 <tr><td class="help">
  <?php etranslate("Week starts on")?>:</td><td>
  <?php etranslate("display-week-starts-on")?>
 </td></tr>
 <tr><td class="help">
  <?php etranslate("Work hours")?>:</td><td>
  <?php etranslate("work-hours-help")?>
 </td></tr>
 <tr><td class="help">
   <?php etranslate("Specify timed event length by")?>:</td><td>
   <?php etranslate("timed-evt-len-help")?>
  </td></tr>
 <tr><td class="help">
  <?php etranslate("Default Category")?>:</td><td>
  <?php etranslate("default-category-help")?>
 </td></tr>
</table>
<br /><br />

<h3><?php etranslate("Email")?></h3>
<table style="border-width:0px;">
 <tr><td class="help">
  <?php etranslate("Event reminders")?>:</td><td>
  <?php etranslate("email-event-reminders-help")?>
 </td></tr>
 <tr><td class="help">
  <?php etranslate("Events added to my calendar")?>:</td><td>
  <?php etranslate("email-event-added")?>
 </td></tr>
 <tr><td class="help">
  <?php etranslate("Events updated on my calendar")?>:</td><td>
  <?php etranslate("email-event-updated")?>
 </td></tr>
 <tr><td class="help">
  <?php etranslate("Events removed from my calendar")?>:</td><td>
  <?php etranslate("email-event-deleted")?>
 </td></tr>
 <tr><td class="help">
  <?php etranslate("Event rejected by participant")?>:</td><td>
  <?php etranslate("email-event-rejected")?>
 </td></tr>
</table>

<h3><?php etranslate("When I am the boss")?></h3>
<table style="border-width:0px;">
 <tr><td class="help">
  <?php etranslate("Email me event notification")?>:</td><td>
  <?php etranslate("email-boss-notifications-help")?>
 </td></tr>
 <tr><td class="help">
  <?php etranslate("I want to approve events")?>:</td><td>
  <?php etranslate("boss-approve-event-help")?>
 </td></tr>
</table>
<?php if ( $PUBLISH_ENABLED == 'Y' ) { ?>
 <h3><?php etranslate("Subscribe/Publish")?></h3>
 <table style="border-width:0px;">
  <tr><td class="help">
   <?php etranslate("Allow remote subscriptions")?>:</td><td>
   <?php etranslate("allow-remote-subscriptions-help")?>
  </td></tr>
  <tr><td class="help">
   <?php etranslate("URL")?>:</td><td>
   <?php etranslate("remote-subscriptions-url-help")?>
  </td></tr>
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
