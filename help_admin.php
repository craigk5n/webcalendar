<?php
include_once 'includes/init.php';
print_header('', '', '', true);
?>

<h2><?php etranslate("Help")?>: <?php etranslate("System Settings")?></h2>


<h3><?php etranslate("Settings")?></h3>
<table style="border-width:0px;">
<tr><td class="help"><?php etranslate("Language")?>:</td>
  <td><?php etranslate("language-help")?></td></tr>
<tr><td class="help"><?php etranslate("Fonts")?>:</td>
  <td><?php etranslate("fonts-help")?></td></tr>
<tr><td class="help"><?php etranslate("Preferred view")?>:</td>
  <td><?php etranslate("preferred-view-help")?></td></tr>
<tr><td class="help"><?php etranslate("Display weekends in week view")?>:</td>
  <td><?php etranslate("display-weekends-help")?></td></tr>
<tr><td class="help"><?php etranslate("Display days with events in bold in year view")?>:</td>
  <td><?php etranslate("yearly-shows-events-help")?></td></tr>
<tr><td class="help"><?php etranslate("Display description in printer day view")?>:</td>
  <td><?php etranslate("display-desc-print-day-help")?></td></tr>
<tr><td class="help"><?php etranslate("Date format")?>:</td>
  <td><?php etranslate("date-format-help")?></td></tr>
<tr><td class="help"><?php etranslate("Time format")?>:</td>
  <td><?php etranslate("time-format-help")?></td></tr>
<tr><td class="help"><?php etranslate("Time interval")?>:</td>
  <td><?php etranslate("time-interval-help")?></td></tr>
<tr><td class="help"><?php etranslate("Require event approvals")?>:</td>
  <td><?php etranslate("require-approvals-help")?></td></tr>
<tr><td class="help"><?php etranslate("Display unapproved")?>:</td>
  <td><?php etranslate("display-unapproved-help")?></td></tr>
<tr><td class="help"><?php etranslate("Display week number")?>:</td>
  <td><?php etranslate("display-week-number-help")?></td></tr>
<tr><td class="help"><?php etranslate("Week starts on")?>:</td>
  <td><?php etranslate("display-week-starts-on")?></td></tr>
<tr><td class="help"><?php etranslate("Work hours")?>:</td>
  <td><?php etranslate("work-hours-help")?></td></tr>
<tr><td class="help"><?php etranslate("Disable Priority field")?>:</td>
  <td><?php etranslate("disable-priority-field-help")?></td></tr>
<tr><td class="help"><?php etranslate("Disable Access field")?>:</td>
  <td><?php etranslate("disable-access-field-help")?></td></tr>
<tr><td class="help"><?php etranslate("Disable Participants field")?>:</td>
  <td><?php etranslate("disable-participants-field-help")?></td></tr>
<tr><td class="help"><?php etranslate("Disable Repeating field")?>:</td>
  <td><?php etranslate("disable-repeating-field-help")?></td></tr>
<tr><td class="help"><?php etranslate("Display Site Extras in popup")?>:</td>
  <td><?php etranslate("popup-includes-siteextras-help")?></td></tr>
<tr><td class="help"><?php etranslate("Allow HTML in Description")?>:</td>
  <td><?php etranslate("allow-html-description-help")?></td></tr>
<tr><td class="help"><?php etranslate("Allow public access")?>:</td>
  <td><?php etranslate("allow-public-access-help")?></td></tr>
<tr><td class="help"><?php etranslate("Public access can view other users")?>:</td>
  <td><?php etranslate("public-access-view-others-help")?></td></tr>
<tr><td class="help"><?php etranslate("Allow viewing other user's calendars")?>:</td>
  <td><?php etranslate("allow-view-other-help")?></td></tr>
<tr><td class="help"><?php etranslate("Allow external users")?>:</td>
  <td><?php etranslate("allow-external-users-help")?></td></tr>
<tr><td class="help"><?php etranslate("External users can receive email notifications")?>:</td>
  <td><?php etranslate("external-can-receive-notification-help")?></td></tr>
<tr><td class="help"><?php etranslate("External users can receive email reminders")?>:</td>
  <td><?php etranslate("external-can-receive-reminder-help")?></td></tr>
<tr><td class="help"><?php etranslate("Remember last login")?>:</td>
  <td><?php etranslate("remember-last-login-help")?></td></tr>
<tr><td class="help"><?php etranslate("Check for event conflicts")?>:</td>
  <td><?php etranslate("conflict-check-help")?></td></tr>
<tr><td class="help"><?php etranslate("Conflict checking months")?>:</td>
  <td><?php etranslate("conflict-months-help")?></td></tr>
<tr><td style="vertical-align:top; font-weight:bold;"><?php etranslate("Specify timed event length by")?>:</td>
  <td><?php etranslate("timed-evt-len-help")?></td></tr>
      </td></tr>
</table>
<br /><br />

<h3><?php etranslate("Groups")?></h3>
<table style="border-width:0px;">
<tr><td class="help"><?php etranslate("Groups enabled")?>:</td>
  <td><?php etranslate("groups-enabled-help")?></td></tr>
<tr><td class="help"><?php etranslate("User sees only his groups")?>:</td>
  <td><?php etranslate("user-sees-his-group-help")?></td></tr>
</table>

<h3><?php etranslate("Categories")?></h3>
<table style="border-width:0px;">
<tr><td class="help"><?php etranslate("Categories enabled")?>:</td>
  <td><?php etranslate("categories-enabled-help")?></td></tr>
</table>

<h3><?php etranslate("Nonuser")?></h3>
<table style="border-width:0px;">
<tr><td class="help"><?php etranslate("Nonuser enabled")?>:</td>
  <td><?php etranslate("nonuser-enabled-help")?></td></tr>
<tr><td class="help"><?php etranslate("Nonuser list")?>:</td>
  <td><?php etranslate("nonuser-list-help")?></td></tr>
</table>

<h3><?php etranslate("Reports")?></h3>
<table style="border-width:0px;">
<tr><td class="help"><?php etranslate("Reports enabled")?>:</td>
  <td><?php etranslate("reports-enabled-help")?></td></tr>
</table>

<h3><?php etranslate("Subscribe/Publish")?></h3>
<table style="border-width:0px;">
<tr><td class="help"><?php etranslate("Allow remote subscriptions")?>:</td>
  <td><?php etranslate("subscriptions-enabled-help")?></td></tr>
</table>


<h3><?php etranslate("Email")?></h3>
<table style="border-width:0px;">
<tr><td class="help"><?php etranslate("Email enabled")?>:</td>
  <td><?php etranslate("email-enabled-help")?></td></tr>
<tr><td class="help"><?php etranslate("Default sender address")?>:</td>
  <td><?php etranslate("email-default-sender")?></td></tr>
<tr><td class="help"><?php etranslate("Event reminders")?>:</td>
  <td><?php etranslate("email-event-reminders-help")?></td></tr>
<tr><td class="help"><?php etranslate("Events added to my calendar")?>:</td>
  <td><?php etranslate("email-event-added")?></td></tr>
<tr><td class="help"><?php etranslate("Events updated on my calendar")?>:</td>
  <td><?php etranslate("email-event-updated")?></td></tr>
<tr><td class="help"><?php etranslate("Events removed from my calendar")?>:</td>
  <td><?php etranslate("email-event-deleted")?></td></tr>
<tr><td class="help"><?php etranslate("Event rejected by participant")?>:</td>
  <td><?php etranslate("email-event-rejected")?></td></tr>
</table>

<h3><?php etranslate("Colors")?></h3>
<?php etranslate("colors-help")?>
<br /><br />

<?php include_once "includes/help_trailer.php"; ?>

</body>
</html>
