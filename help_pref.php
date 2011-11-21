<?php // $Id$
include_once 'includes/init.php';
include_once 'includes/help_list.php';

ob_start();
print_header( '', '', '', true );

echo $helpListStr . '
    <h2>' . translate( 'Help Preferences' ) . '</h2>
    <h3>' . $setsStr . '</h3>
    <div class="helpbody">
      <div>';

list_help( array(
  translate ( 'Auto-refresh calendars' ) => translate ( 'auto-refresh-help' ),
  translate ( 'Auto-refresh time' ) => translate ( 'auto-refresh-time-help' ),
  translate ( 'Date format' ) => translate ( 'date_format_help' ),
  translate ( 'Default Category' ) => translate ( 'default-category-help' ),
  translate ( 'desc in printer day view' ) =>
  translate ( 'display_desc_print_day_help' ),
  translate ( 'Display unapproved' ) =>
  translate ( 'display_unapproved_help' ),
  translate ( 'Display week number' ) =>
  translate ( 'display_weeknumber_help' ),
  translate ( 'Display weekends in week view' ) =>
  translate ( 'display_weekends_help' ),
  translate ( 'Fonts' ) => translate ( 'fonts_help' ),
  translate ( 'Language' ) => translate ( 'language-help' ),
  translate ( 'Preferred view' ) => translate ( 'preferred_view_help' ),
  translate ( 'Specify timed event length by' ) =>
  translate ( 'timed_evt_len_help' ),
  translate ( 'Time format' ) => translate ( 'time_format_help' ),
  translate ( 'Time interval' ) => translate ( 'time-interval-help' ),
  translate ( 'TZ Offset' ) => translate ( 'tz_help' ),
  translate ( 'Week starts on' ) => translate ( 'display_week_starts_on' ),
  translate ( 'Work hours' ) => translate ( 'work_hours_help' ),
  )
);

echo '
      </div>
      <h3>' . translate ( 'Email' ) . '</h3>
      <div>';

list_help( array(
  translate ( 'Event rejected by participant' ) =>
  translate ( 'email_event_rejected' ),
  translate ( 'Event reminders' ) =>
  translate ( 'email_reminder_help' ),
  translate ( 'Events added to my calendar' ) =>
  translate ( 'email_event_added' ),
  translate ( 'Events removed from my calendar' ) =>
  translate ( 'email_event_deleted' ),
  translate ( 'Events updated on my calendar' ) =>
  translate ( 'email_event_added' ),
  )
);

echo '
      </div>
      <h3>' . translate ( 'When I am the boss' ) . '</h3>
      <div>';

list_help( array(
  translate ( 'Email me event notification' ) =>
  translate ( 'email-boss-notifications-help' ),
  translate ( 'I want to approve events' ) =>
  translate ( 'boss-approve-event-help' ),
  )
);

echo '
      </div>';

if ( $PUBLISH_ENABLED == 'Y' ) {
  echo '
      <h3>' . translate ( 'Subscribe/Publish' ) . '</h3>
      <div>';

  list_help( array(
    translate ( 'Allow remote publishing' ) =>
    translate ( 'allow-remote-publishing-help' ),
    $urlStr => translate( 'remote-publishing-url-help' ),
    translate ( 'Allow remote subscriptions' ) =>
    translate ( 'allow-remote-subscriptions-help' ),
    $urlStr => translate( 'remote-subscriptions-url-help' ),
    translate ( 'Enable FreeBusy publishing' ) =>
    translate ( 'freebusy-enabled-help' ),
    $urlStr => translate( 'freebusy-url-help' ),
    translate ( 'Enable RSS feed' ) => translate ( 'rss-enabled-help' ),
    $urlStr => translate( 'rss-feed-url-help' ),
    )
  );

  echo '
      </div';
}

if ( $ALLOW_COLOR_CUSTOMIZATION == 'Y' )
  echo '
      <h3>' . translate ( 'Colors' ) . '</h3>
      <p>' . translate ( 'colors-help' ) . '</p>';

echo '
    </div>' . print_trailer( false, true, true );
ob_end_flush();

?>
