<?php
/* $Id: help_admin.php,v 1.32.2.2 2007/08/06 02:28:29 cknudsen Exp $ */
include_once 'includes/init.php';
include_once 'includes/help_list.php';

print_header ( '', '', '', true );
ob_start ();
echo $helpListStr . '
    <h2>' . translate ( 'Help' ) . ': ' . translate ( 'System Settings' )
 . '</h2>
    <h3>' . translate ( 'Settings' ) . '</h3>
    <div class="helpbody">
      <div>';
$tmp_arr = array (
  translate ( 'Allow HTML in Description' ) =>
  translate ( 'allow-html-description-help' ),
  translate ( 'Allow users to override conflicts' ) =>
  translate ( 'conflict-check-override-help' ),
  translate ( 'Allow viewing other users calendars' ) =>
  translate ( 'allow-view-other-help' ),
  translate ( 'Application Name' ) =>
  translate ( 'app-name-help' ),
  translate ( 'Auto-refresh calendars' ) => translate ( 'auto-refresh-help' ),
  translate ( 'Auto-refresh time' ) => translate ( 'auto-refresh-time-help' ),
  translate ( 'Check for event conflicts' ) =>
  translate ( 'conflict-check-help' ),
  translate ( 'Conflict checking months' ) =>
  translate ( 'conflict-months-help' ),
  translate ( 'Custom header' ) => translate ( 'custom-header-help' ),
  translate ( 'Custom script/stylesheet' ) =>
  translate ( 'custom-script-help' ),
  translate ( 'Custom trailer' ) => translate ( 'custom-trailer-help' ),
  translate ( 'Date format' ) => translate ( 'date-format-help' ),
  translate ( 'Disable Access field' ) =>
  translate ( 'disable-access-field-help' ),
  translate ( 'Disable Participants field' ) =>
  translate ( 'disable-participants-field-help' ),
  translate ( 'Disable Priority field' ) =>
  translate ( 'disable-priority-field-help' ),
  translate ( 'Disable Repeating field' ) =>
  translate ( 'disable-repeating-field-help' ),
  translate ( 'Display days with events in bold in year view' ) =>
  translate ( 'yearly-shows-events-help' ),
  translate ( 'Display description in printer day view' ) =>
  translate ( 'display-desc-print-day-help' ),
  translate ( 'Display Site Extras in popup' ) =>
  translate ( 'popup-includes-siteextras-help' ),
  translate ( 'Display unapproved' ) => translate ( 'display-unapproved-help' ),
  translate ( 'Display week number' ) =>
  translate ( 'display-week-number-help' ),
  translate ( 'Display weekends in week view' ) =>
  translate ( 'display-weekends-help' ),
  translate ( 'Fonts' ) => translate ( 'fonts-help' ),
  translate ( 'Home URL' ) => translate ( 'home-url-help' ),
  translate ( 'Include add event link in views' ) =>
  translate ( 'allow-view-add-help' ),
  translate ( 'Language' ) => translate ( 'language-help' ),
  translate ( 'Limit number of timed events per day' ) =>
  translate ( 'limit-appts-help' ),
  translate ( 'Maximum timed events per day' ) =>
  translate ( 'limit-appts-number-help' ),
  translate ( 'Preferred view' ) => translate ( 'preferred-view-help' ),
  translate ( 'Remember last login' ) =>
  translate ( 'remember-last-login-help' ),
  translate ( 'Require event approvals' ) =>
  translate ( 'require-approvals-help' ),
  translate ( 'Server URL' ) => translate ( 'server-url-help' ),
  translate ( 'Specify timed event length by' ) =>
  translate ( 'timed-evt-len-help' ),
  translate ( 'Time format' ) => translate ( 'time-format-help' ),
  translate ( 'Time interval' ) => translate ( 'time-interval-help' ),
  translate ( 'Week starts on' ) => translate ( 'display-week-starts-on' ),
  translate ( 'Work hours' ) => translate ( 'work-hours-help' ),
  );
list_help ( $tmp_arr );
echo '
      </div>
      <h3>' . translate ( 'Public Access' ) . '</h3>
      <div>';
$tmp_arr = array (
  translate ( 'Allow public access' ) =>
  translate ( 'allow-public-access-help' ),
  translate ( 'Public access can add events' ) =>
  translate ( 'public-access-can-add-help' ),
  translate ( 'Public access can view other users' ) =>
  translate ( 'public-access-view-others-help' ),
  translate ( 'Public access can view participants' ) =>
  translate ( 'public-access-sees-participants-help' ),
  translate ( 'Public access is default participant' ) =>
  translate ( 'public-access-default-selected' ),
  translate ( 'Public access new events require approval' ) =>
  translate ( 'public-access-add-requires-approval-help' ),
  translate ( 'Public access visible by default' ) =>
  translate ( 'public-access-default-visible' ),
  );
list_help ( $tmp_arr );
echo '
      </div>
      <h3>' . translate ( 'Groups' ) . '</h3>
      <div>';
$tmp_arr = array (
  translate ( 'Groups enabled' ) => translate ( 'groups-enabled-help' ),
  translate ( 'User sees only his groups' ) =>
  translate ( 'user-sees-his-group-help' ),
  );
list_help ( $tmp_arr );
echo '
      </div>
      <h3>' . translate ( 'Nonuser' ) . '</h3>
      <div>';
$tmp_arr = array (
  translate ( 'Nonuser enabled' ) => translate ( 'nonuser-enabled-help' ),
  translate ( 'Nonuser list' ) => translate ( 'nonuser-list-help' ),
  );
list_help ( $tmp_arr );
echo '
      </div>
      <h3>' . translate ( 'Other' ) . '</h3>
      <div>';
$tmp_arr = array (
  translate ( 'Allow external users' ) =>
  translate ( 'allow-external-users-help' ),
  translate ( 'Allow remote subscriptions' ) =>
  translate ( 'subscriptions-enabled-help' ),
  translate ( 'Categories enabled' ) => translate ( 'categories-enabled-help' ),
  translate ( 'External users can receive email notifications' ) =>
  translate ( 'external-can-receive-notification-help' ),
  translate ( 'External users can receive email reminders' ) =>
  translate ( 'external-can-receive-reminder-help' ),
  translate ( 'Reports enabled' ) => translate ( 'reports-enabled-help' ),
  );
list_help ( $tmp_arr );
echo '
      </div>
      <h3>' . translate ( 'Email' ) . '</h3>
      <div>';
$tmp_arr = array (
  translate ( 'Default sender address' ) =>
  translate ( 'email-default-sender' ),
  translate ( 'Email enabled' ) => translate ( 'email-enabled-help' ),
  translate ( 'Event rejected by participant' ) =>
  translate ( 'email-event-rejected' ),
  translate ( 'Event reminders' ) =>
  translate ( 'email-event-reminders-help' ),
  translate ( 'Events added to my calendar' ) =>
  translate ( 'email-event-added' ),
  translate ( 'Events removed from my calendar' ) =>
  translate ( 'email-event-deleted' ),
  translate ( 'Events updated on my calendar' ) =>
  translate ( 'email-event-updated' ),
  );
list_help ( $tmp_arr );
echo '
      </div>
      <h3>' . translate ( 'Colors' ) . '</h3>
      <div>';
$tmp_arr = array (
  translate ( 'Allow user to customize colors' ) =>
  translate ( 'user-customize-color' ),
  translate ( 'Enable gradient images for background colors' ) =>
  translate ( 'enable-gradient-help' ),
  translate ( 'Manually entering color values' ) => translate ( 'colors-help' ),
  );
list_help ( $tmp_arr );
ob_end_flush ();
echo '
      </div>
    </div>
    ' . print_trailer ( false, true, true );

?>
