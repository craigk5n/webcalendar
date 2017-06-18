<?php
/* $Id: help_pref.php,v 1.35.2.2 2007/08/06 02:28:30 cknudsen Exp $ */
include_once 'includes/init.php';
include_once 'includes/help_list.php';

print_header ( '', '', '', true );

ob_start ();

echo $helpListStr . '
    <h2>' . translate ( 'Help' ) . ': ' . translate ( 'Preferences' ) . '</h2>
    <h3>' . translate ( 'Settings' ) . '</h3>
    <div class="helpbody">
      <div>';

$tmp_arr = array (
  translate ( 'Auto-refresh calendars' ) => translate ( 'auto-refresh-help' ),
  translate ( 'Auto-refresh time' ) => translate ( 'auto-refresh-time-help' ),
  translate ( 'Date format' ) => translate ( 'date-format-help' ),
  translate ( 'Default Category' ) => translate ( 'default-category-help' ),
  translate ( 'Display description in printer day view' ) =>
  translate ( 'display-desc-print-day-help' ),
  translate ( 'Display unapproved' ) =>
  translate ( 'display-unapproved-help' ),
  translate ( 'Display week number' ) =>
  translate ( 'display-week-number-help' ),
  translate ( 'Display weekends in week view' ) =>
  translate ( 'display-weekends-help' ),
  translate ( 'Fonts' ) => translate ( 'fonts-help' ),
  translate ( 'Language' ) => translate ( 'language-help' ),
  translate ( 'Preferred view' ) => translate ( 'preferred-view-help' ),
  translate ( 'Specify timed event length by' ) =>
  translate ( 'timed-evt-len-help' ),
  translate ( 'Time format' ) => translate ( 'time-format-help' ),
  translate ( 'Time interval' ) => translate ( 'time-interval-help' ),
  translate ( 'Timezone Offset' ) => translate ( 'tz-help' ),
  translate ( 'Week starts on' ) => translate ( 'display-week-starts-on' ),
  translate ( 'Work hours' ) => translate ( 'work-hours-help' ),
  );

list_help ( $tmp_arr );

echo '
      </div>
      <h3>' . translate ( 'Email' ) . '</h3>
      <div>';

$tmp_arr = array (
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
      <h3>' . translate ( 'When I am the boss' ) . '</h3>
      <div>';

$tmp_arr = array (
  translate ( 'Email me event notification' ) =>
  translate ( 'email-boss-notifications-help' ),
  translate ( 'I want to approve events' ) =>
  translate ( 'boss-approve-event-help' ),
  );

list_help ( $tmp_arr );

echo '
      </div>';

if ( $PUBLISH_ENABLED == 'Y' ) {
  echo '
      <h3>' . translate ( 'Subscribe/Publish' ) . '</h3>
      <div>';

  $tmp_arr = array (
    translate ( 'Allow remote publishing' ) =>
    translate ( 'allow-remote-publishing-help' ),
    translate ( 'URL' ) => translate ( 'remote-publishing-url-help' ),
    translate ( 'Allow remote subscriptions' ) =>
    translate ( 'allow-remote-subscriptions-help' ),
    translate ( 'URL' ) => translate ( 'remote-subscriptions-url-help' ),
    translate ( 'Enable FreeBusy publishing' ) =>
    translate ( 'freebusy-enabled-help' ),
    translate ( 'URL' ) => translate ( 'freebusy-url-help' ),
    translate ( 'Enable RSS feed' ) => translate ( 'rss-enabled-help' ),
    translate ( 'URL' ) => translate ( 'rss-feed-url-help' ),
    );

  list_help ( $tmp_arr );

  echo '
      </div';
}

if ( $ALLOW_COLOR_CUSTOMIZATION == 'Y' )
  echo '
      <h3>' . translate ( 'Colors' ) . '</h3>
      <p>' . translate ( 'colors-help' ) . '</p>';

echo '
    </div>';

ob_end_flush ();

echo print_trailer ( false, true, true );

?>
