<?php // $Id$
include_once 'includes/init.php';
include_once 'includes/help_list.php';

ob_start();
print_header( '', '', '', true );
echo $helpListStr . '
    <h2>' . translate ( 'Help System Settings' ) . '</h2>
    <h3>' . $setsStr . '</h3>
    <div class="helpbody">
      <div>';
list_help( array(
  translate ( 'Allow HTML in Description' ) =>
  translate ( 'allow_html_description_help' ),
  translate ( 'may users override conflicts' ) =>
  translate ( 'allow_conflict_override_help' ),
  translate ( 'may view others cals' ) =>
  translate ( 'allow_view_other_help' ),
  translate ( 'Application Name' ) =>
  translate ( 'app-name-help' ),
  translate ( 'Auto-refresh calendars' ) => translate ( 'auto-refresh-help' ),
  translate ( 'Auto-refresh time' ) => translate ( 'auto-refresh-time-help' ),
  translate ( 'Check for conflicts' ) =>
  translate ( 'allow_conflicts_help' ),
  translate ( 'Conflict checking months' ) =>
  translate ( 'conflict_months_help' ),
  translate ( 'Custom header' ) => translate ( 'custom_header_help' ),
  translate ( 'Custom script' ) =>
  translate ( 'custom_script_help' ),
  translate ( 'Custom trailer' ) => translate ( 'custom_trailer_help' ),
  translate ( 'Date format' ) => translate ( 'date_format_help' ),
  translate ( 'Disable Access field' ) =>
  translate ( 'disable_access_field_help' ),
  translate ( 'Disable Participants field' ) =>
  translate ( 'disable_participants_field_help' ),
  translate ( 'Disable Priority field' ) =>
  translate ( 'disable_priority_field_help' ),
  translate ( 'Disable Repeating field' ) =>
  translate ( 'disable_repeating_field_help' ),
  translate ( 'bold events in year view' ) =>
  translate ( 'display_bold_days_in_year_help' ),
  translate ( 'desc in printer day view' ) =>
  translate ( 'display_desc_print_day_help' ),
  translate ( 'Display Site Extras in popup' ) =>
  translate ( 'site_extras_in_popup_help' ),
  translate ( 'Display unapproved' ) => translate ( 'display_unapproved_help' ),
  translate ( 'Display week number' ) =>
  translate ( 'display_weeknumber_help' ),
  translate ( 'Display weekends in week view' ) =>
  translate ( 'display_weekends_help' ),
  translate ( 'Fonts' ) => translate ( 'fonts_help' ),
  translate ( 'Home URL' ) => translate ( 'home-url-help' ),
  translate ( 'Include add event link in views' ) =>
  translate ( 'display_add_link_in_views_help' ),
  translate ( 'Language_' ) => translate ( 'language-help' ),
  translate ( 'Limit timed events per day' ) =>
  translate ( 'limit_appts_help' ),
  translate ( 'Maximum timed events per day' ) =>
  translate ( 'limit_appts_number_help' ),
  translate ( 'Preferred view' ) => translate ( 'preferred_view_help' ),
  translate ( 'Remember last login' ) =>
  translate ( 'remember_last_login_help' ),
  translate ( 'Require event approvals' ) =>
  translate ( 'require_approvals_help' ),
  translate ( 'Server URL' ) => translate( 'server_url_help' ),
  translate ( 'Specify timed event length by' ) =>
  translate ( 'timed_evt_len_help' ),
  translate ( 'Time format' ) => translate ( 'time_format_help' ),
  translate ( 'Time interval' ) => translate ( 'time-interval-help' ),
  translate ( 'Week starts on' ) => translate ( 'display_week_starts_on' ),
  translate ( 'Work hours' ) => translate ( 'work_hours_help' ),
  )
);
echo '
      </div>
      <h3>' . translate ( 'Public Access' ) . '</h3>
      <div>';
list_help( array(
  translate ( 'Allow public access' ) =>
  translate ( 'allow_public_access_help' ),
  translate ( 'may public add events' ) =>
  translate ( 'public-access-can-add-help' ),
  translate ( 'may public view others' ) =>
  translate ( 'public-access-view-others-help' ),
  translate ( 'may public see participants' ) =>
  translate ( 'public-access-sees-participants-help' ),
  translate ( 'is public default party' ) =>
  translate ( 'public-access-default-selected' ),
  translate ( 'must approve public events' ) =>
  translate ( 'public-access-add-requires-approval-help' ),
  translate ( 'Public visible by default' ) =>
  translate ( 'public-access-default-visible' ),
  )
);
echo '
      </div>
      <h3>' . $groupsStr . '</h3>
      <div>';
list_help( array(
  translate ( 'Groups enabled' ) => translate ( 'groups-enabled-help' ),
  translate ( 'User sees only his groups' ) =>
  translate ( 'user-sees-his-group-help' ),
  )
);
echo '
      </div>
      <h3>' . translate ( 'Nonuser' ) . '</h3>
      <div>';
list_help( array(
  translate ( 'NUCs enabled' ) => translate ( 'nonuser-enabled-help' ),
  translate ( 'list NUCs at' ) => translate ( 'nonuser-list-help' ),
  )
);
echo '
      </div>
      <h3>' . translate ( 'Other' ) . '</h3>
      <div>';
list_help( array(
  translate ( 'Allow external users' ) =>
  translate ( 'allow-external-users-help' ),
  translate ( 'Allow remote subscriptions' ) =>
  translate ( 'subscriptions-enabled-help' ),
  translate ( 'Categories enabled' ) => translate ( 'categories-enabled-help' ),
  translate ( 'may notify externals by email' ) =>
  translate ( 'external-can-receive-notification-help' ),
  translate ( 'may remind externals by email' ) =>
  translate ( 'external-can-receive-reminder-help' ),
  translate ( 'Reports enabled' ) => translate ( 'reports-enabled-help' ),
  )
);
echo '
      </div>
      <h3>' . translate ( 'Email' ) . '</h3>
      <div>';
list_help( array(
  translate ( 'Default sender address' ) =>
  translate ( 'email-default-sender' ),
  translate ( 'Email enabled' ) => translate ( 'email-enabled-help' ),
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
      <h3>' . translate ( 'Colors' ) . '</h3>
      <div>';
list_help( array(
  translate ( 'Allow user to customize colors' ) =>
  translate ( 'user-customize-color' ),
  translate ( 'Enable gradient images for BG' ) =>
  translate ( 'enable-gradient-help' ),
  translate ( 'Manually entering color values' ) => translate ( 'colors-help' ),
  )
);
echo '
      </div>
    </div>' . print_trailer( false, true, true );
ob_end_flush();

?>
