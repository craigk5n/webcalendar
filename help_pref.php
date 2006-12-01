<?php
/* $Id$ */
include_once 'includes/init.php';
include_once 'includes/help_list.php';

print_header ( '', '', '', true );

ob_start ();

echo $helpListStr . '
    <h2>' . $translations['Help'] . ': ' . $translations['Preferences'] . '</h2>
    <h3>' . $translations['Settings'] . '</h3>
    <div class="helpbody">
      <div>';

$tmp_arr = array (
  $translations['Auto-refresh calendars'] => $translations['auto-refresh-help'],
  $translations['Auto-refresh time'] => $translations['auto-refresh-time-help'],
  $translations['Date format'] => $translations['date-format-help'],
  translate ( 'Default Category' ) => translate ( 'default-category-help' ),
  $translations['Display description in printer day view'] =>
  $translations['display-desc-print-day-help'],
  $translations['Display unapproved'] =>
  $translations['display-unapproved-help'],
  $translations['Display week number'] =>
  $translations['display-week-number-help'],
  $translations['Display weekends in week view'] =>
  $translations['display-weekends-help'],
  $translations['Fonts'] => $translations['fonts-help'],
  $translations['Language'] => $translations['language-help'],
  $translations['Preferred view'] => $translations['preferred-view-help'],
  $translations['Specify timed event length by'] =>
  $translations['timed-evt-len-help'],
  $translations['Time format'] => $translations['time-format-help'],
  $translations['Time interval'] => $translations['time-interval-help'],
  $translations['Timezone Offset'] => $translations['tz-help'],
  $translations['Week starts on'] => $translations['display-week-starts-on'],
  $translations['Work hours'] => $translations['work-hours-help'],
  );

list_help ( $tmp_arr );

echo '
      </div>
      <h3>' . $translations['Email'] . '</h3>
      <div>';

$tmp_arr = array (
  $translations['Event rejected by participant'] =>
  $translations['email-event-rejected'],
  $translations['Event reminders'] =>
  $translations['email-event-reminders-help'],
  $translations['Events added to my calendar'] =>
  $translations['email-event-added'],
  $translations['Events removed from my calendar'] =>
  $translations['email-event-deleted'],
  $translations['Events updated on my calendar'] =>
  $translations['email-event-updated'],
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
    $translations['URL'] => translate ( 'remote-publishing-url-help' ),
    $translations['Allow remote subscriptions'] =>
    translate ( 'allow-remote-subscriptions-help' ),
    $translations['URL'] => translate ( 'remote-subscriptions-url-help' ),
    translate ( 'Enable FreeBusy publishing' ) =>
    translate ( 'freebusy-enabled-help' ),
    $translations['URL'] => translate ( 'freebusy-url-help' ),
    $translations['Enable RSS feed'] => $translations['rss-enabled-help'],
    $translations['URL'] => translate ( 'rss-feed-url-help' ),
    );

  list_help ( $tmp_arr );

  echo '
      </div';
}

if ( $ALLOW_COLOR_CUSTOMIZATION == 'Y' )
  echo '
      <h3>' . $translations['Colors'] . '</h3>
      <p>' . $translations['colors-help'] . '</p>';

echo '
    </div>';

ob_end_flush ();

echo print_trailer ( false, true, true );

?>
