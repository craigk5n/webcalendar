<?php // $Id: view_entry.php,v 1.185.2.2 2013/01/07 16:52:13 cknudsen Exp $
/**
 * Description:
 * Presents page to view an event with links to edit, delete
 * confirm, copy, add event
 *
 * Input Parameters:
 * id (*) - cal_id of requested event
 * date   - yyyymmdd format of requested event
 * user   - user to display
 * log    - show activity log (any non-empty value)
 * (*) required field
 */
include_once 'includes/init.php';
include 'includes/xcal.php'; // only to display recurrance info
// Load Doc classes for attachments and comments
include 'includes/classes/Doc.class';
include 'includes/classes/DocList.class';
include 'includes/classes/AttachmentList.class';
include 'includes/classes/CommentList.class';

// Make sure this user is allowed to look at this calendar.
$can_approve = $can_edit = $can_view = false;
$is_my_event = false; // Is this user owner or participant?
$is_confidential = $is_private = $rss_view  = false;
$error = $eType = $event_status = '';
$log = getGetValue ( 'log' );
$show_log = ! empty ( $log );
$can_email = 'Y';

$areYouSureStr = translate( 'Are you sure you want to delete this entry?' );
$pri[1] = translate ( 'High' );
$pri[2] = translate ( 'Medium' );
$pri[3] = translate ( 'Low' );

if ( empty ( $id ) || $id <= 0 || ! is_numeric ( $id ) )
  $error = translate ( 'Invalid entry id.' );

$hide_details = ( $login == '__public__' && !
  empty ( $OVERRIDE_PUBLIC ) && $OVERRIDE_PUBLIC == 'Y' );


// Check if we can display basic info for RSS FEED
$rssuser = getGetValue ( 'rssuser' );
if ( ! empty ( $rssuser ) ) {
  $user_rss_enabled = get_pref_setting ( $rssuser, 'USER_RSS_ENABLED' );
  $user_remote_access = get_pref_setting ( $rssuser, 'USER_REMOTE_ACCESS' );
  $user_rss_timezone = get_pref_setting ( $rssuser, 'TIMEZONE' );
  $rss_view = ( $RSS_ENABLED == 'Y' && $user_rss_enabled == 'Y' &&
    $friendly == 1 && ! empty ( $rssuser ) && isset ( $user_remote_access ) );
  if ( $rss_view == true ) {
    if ( $login == '__public__')
      $user = $rssuser;
    $hide_details = false;
    // Make sure the displayed time is accurate.
    set_env ( 'TZ', $user_rss_timezone );
  }
}

// Is this user a participant or the creator of the event?
// If assistant is doing this, then we need to switch login to user in the sql.
$sqlparm = ( $is_assistant ? $user : $login );
$res = dbi_execute ( 'SELECT we.cal_id, we.cal_create_by
  FROM webcal_entry we, webcal_entry_user weu
  WHERE we.cal_id = weu.cal_id AND we.cal_id = ?
  AND ( we.cal_create_by = ? OR weu.cal_login = ? )',
  [$id, $sqlparm, $sqlparm] );
if ( $res ) {
  $row = dbi_fetch_row ( $res );
  if ( $row && $row[0] > 0 ) {
    $can_view = $is_my_event = true;
    $creator = $row[1];
  }
  dbi_free_result ( $res );
}

// Update the task percentage for this user.
if ( ! empty ( $_POST ) && $is_my_event ) {
  require_valid_referring_url ();
  $upercent = getPostValue ( 'upercent' );
  if ( $upercent >= 0 && $upercent <= 100 ) {
    dbi_execute ( 'UPDATE webcal_entry_user SET cal_percent = ?
      WHERE cal_login = ? AND cal_id = ?',
     [$upercent, $login, $id] );
    activity_log ( $id, $login, $creator, LOG_UPDATE_T,
      translate ( 'Update Task Percentage' ) . ' ' . $upercent . '%' );
   }
  // Check if all other user percent is 100%, if so, set cal_complete date.
  $others_complete = getPostValue ( 'others_complete' );
  if ( $upercent == 100 && $others_complete == 'yes' ) {
    dbi_execute ( 'UPDATE webcal_entry SET cal_completed = ?
  WHERE cal_id = ?', [gmdate ( 'Ymd', time() ), $id] );
    activity_log ( $id, $login, $creator, LOG_UPDATE_T,
      translate ( 'Completed' ) );
  }
}

// Load event info now.
$res = dbi_execute ( 'SELECT cal_create_by, cal_date, cal_time, cal_mod_date,
  cal_mod_time, cal_duration, cal_priority, cal_type, cal_access,
  cal_name, cal_description, cal_location, cal_url, cal_due_date,
  cal_due_time, cal_completed FROM webcal_entry WHERE cal_id = ?',
  [$id] );
if ( ! $res )
  $error = str_replace ('XXX', $id, translate ( 'Invalid entry id XXX.' ) );
else {
  $row = dbi_fetch_row ( $res );
  if ( $row ) {
    $create_by = $row[0];
    $orig_date = $row[1];
    $event_time = $row[2];
    $mod_date = $row[3];
    $mod_time = sprintf ( "%06d", $row[4] );
    $duration = $row[5];
    $cal_priority = $row[6];
    $cal_type = $row[7];
    $cal_access = $row[8];
    if ( strpos ( 'NT', $cal_type ) !== false )
      $eType = 'task';
    if ( $hide_details ) {
      $description = $name = $overrideStr = translate ( $OVERRIDE_PUBLIC_TEXT );
      if ( ! empty ( $row[11] ) )
        $location = $overrideStr;
      if ( ! empty ( $row[12] ) )
        $url = $overrideStr;
    } else {
      $name = $row[9];
      $description = $row[10];
      $location = $row[11];
      $url = $row[12];
    }
    $due_date = $row[13];
    $due_time = $row[14];
    $cal_completed = $row[15];
  } else
    $error = str_replace ('XXX', $id, translate ( 'Invalid entry id XXX.' ) );

  dbi_free_result ( $res );
}

if ( empty ( $error ) ) {
  // don't shift date if All Day or Untimed
  $display_date = ( $event_time > 0 || ( $event_time == 0 && $duration != 1440 )
    ? date ( 'Ymd', date_to_epoch ( $orig_date
         . sprintf ( "%06d", $event_time ) ) )
    : $orig_date );

  if ( ! empty ( $year ) )
    $thisyear = $year;

  if ( ! empty ( $month ) )
    $thismonth = $month;

  // Check UAC.
  $euser = ( empty ( $user ) ? ( $is_my_event ? $login : $create_by ) : $user );
  $time_only = 'N';

  if ( access_is_enabled() ) {
    $can_approve =
     access_user_calendar ( 'approve', $euser, $login, $cal_type, $cal_access );
    $can_edit =
     access_user_calendar ( 'edit', $create_by, $login, $cal_type, $cal_access );
    $can_view =
     access_user_calendar ( 'view', $euser, $login, $cal_type, $cal_access );
    $time_only =
     access_user_calendar ( 'time', $euser, $login, $cal_type, $cal_access );
  }

  if ( $is_admin || $is_nonuser_admin || $is_assistant )
    $can_view = true;

 // Commented out by RJ. Not sure of the reason for this code
 //   if ( ($login != '__public__') && ($PUBLIC_ACCESS_OTHERS == 'Y') ) {
 //     $can_view = true;
 //   }

  $can_edit = ( $can_edit || $is_admin || $is_nonuser_admin &&
    $user == $create_by ||
    ( $is_assistant && ! $is_private && $user == $create_by ) ||
    ( $readonly != 'Y' && ( $login == $create_by || $single_user == 'Y' ) ) );

  if ( $readonly == 'Y' || $is_nonuser ||
    ( $PUBLIC_ACCESS == 'Y' && $login == '__public__' ) )
    $can_edit = false;

  if ( ! $can_view ) {
    // if not a participant in the event, must be allowed to look at
    // other user's calendar.
    $check_group = ( $login == '__public__' && $PUBLIC_ACCESS_OTHERS == 'Y' ) ||
        $ALLOW_VIEW_OTHER == 'Y';
    // If $check_group is true, it means this user can look at the event only if
    // they are in the same group as some of the people in the event. This gets
    // kind of tricky. If there is a participant from a different group, do we
    // still show it? For now, the answer is no. This could be configurable
    // somehow, but how many lines of text would it need in the admin page to
    // describe this scenario? Would confuse 99.9% of users.
    // In summary, make sure at least one event participant is in one of
    // this user's groups.
    $my_users = get_my_users();
    $my_usercnt = count ( $my_users );
    if ( is_array ( $my_users ) && $my_usercnt ) {
      $sql_params = [];
      $sql = 'SELECT we.cal_id FROM webcal_entry we, webcal_entry_user weu
        WHERE we.cal_id = weu.cal_id AND we.cal_id = ? AND weu.cal_login IN ( ';
      $sql_params[] = $id;
      for ( $i = 0; $i < $my_usercnt; $i++ ) {
        $sql .= ( $i > 0 ? ', ' : '' ) . '?';
        $sql_params[] = $my_users[$i]['cal_login'];
      }
      $res = dbi_execute ( $sql . ' )', $sql_params );
      if ( $res ) {
        $row = dbi_fetch_row ( $res );
        if ( $row && $row[0] > 0 )
          $can_view = true;

        dbi_free_result ( $res );
      }
    }
    // If we didn't indicate we need to check groups,
    // then this user can't view this event.
    if ( ! $check_group || access_is_enabled() )
      $can_view = false;
  }
} //end $error test

// If they still cannot view, make sure they are not looking at a nonuser
// calendar event where the nonuser is the _only_ participant.
if ( empty ( $error ) && ! $can_view && !
    empty ( $NONUSER_ENABLED ) && $NONUSER_ENABLED == 'Y' ) {
  $nonusers = get_nonuser_cals();
  $nonuser_lookup = [];
  for ( $i = 0, $cnt = count ( $nonusers ); $i < $cnt; $i++ ) {
    $nonuser_lookup[$nonusers[$i]['cal_login']] = 1;
  }
  $res = dbi_execute ( 'SELECT cal_login FROM webcal_entry_user
  WHERE cal_id = ?
    AND cal_status IN ("A","W")', [$id] );
  $found_nonuser_cal = $found_reg_user = false;
  if ( $res ) {
    while ( $row = dbi_fetch_row ( $res ) ) {
      if ( ! empty ( $nonuser_lookup[$row[0]] ) )
        $found_nonuser_cal = true;
      else
        $found_reg_user = true;
    }
    dbi_free_result ( $res );
  }
  // Does this event contain only nonuser calendars as participants?
  // If so, then grant access.
  if ( $found_nonuser_cal && ! $found_reg_user && ! access_is_enabled() )
    $can_view = true;
}

// Final case. If 'public visible by default' is on and 'public' is
// a participant to this event, then anyone can view the event.
if ( ! $can_view && ! empty ( $PUBLIC_ACCESS_DEFAULT_VISIBLE ) &&
  $PUBLIC_ACCESS_DEFAULT_VISIBLE == 'Y' ) {
  // check to see if 'public' was a participant
  $res = dbi_execute ( 'SELECT cal_login
  FROM webcal_entry_user
  WHERE cal_id = ?
    AND cal_login = "__public__"
    AND cal_status IN ("A","W")', [$id] );
  if ( $res ) {
    while ( $row = dbi_fetch_row ( $res ) ) {
      if ( ! empty ( $row[0] ) && $row[0] == '__public__' ) {
        // public is participant
        $can_view = true;
      }
    }
    dbi_free_result ( $res );
  }
}

$printerStr = generate_printer_friendly ( 'view_entry.php' );

print_header();

if ( ! empty ( $error ) ) {
  echo print_error ( $error ) . print_trailer();
  exit;
}

if ( ! empty ( $user ) && $login != $user ) {
  // If viewing another user's calendar, check the status of the
  // event on their calendar (to see if it's deleted).
  $res = dbi_execute ( 'SELECT cal_status FROM webcal_entry_user
  WHERE cal_login = ?
    AND cal_id = ?', [$user, $id] );
  if ( $res ) {
    if ( $row = dbi_fetch_row ( $res ) )
      $event_status = $row[0];

    dbi_free_result ( $res );
  }
} else {
  // We are viewing event on user's own calendar, so check the
  // status on their own calendar.
  $res = dbi_execute ( 'SELECT cal_id, cal_status FROM webcal_entry_user
  WHERE cal_login = ?
    AND cal_id = ?', [$login, $id] );
  if ( $res ) {
    $row = dbi_fetch_row ( $res );
    $event_status = $row[1];
    dbi_free_result ( $res );
  }
}
// This section commented out by RJ
// This code allows viewing events not otherwise authorized

// At this point, if we don't have the event status, then this user is not
// viewing an event from his own calendar and not viewing an event from someone
// else's calendar. They probably got here from the search results page
// (or possibly by hand typing in the URL.)
// Check to make sure that it hasn't been deleted from everyone's calendar.
//if ( empty ( $event_status ) ) {
//  $res = dbi_execute ( 'SELECT cal_status FROM webcal_entry_user
//  WHERE cal_status <> "D"
//  ORDER BY cal_status', [] );
 // if ( $res ) {
//    if ( $row = dbi_fetch_row ( $res ) )
//      $event_status = $row[0];

//    dbi_free_result ( $res );
//  }
//}

// If we have no event status yet, it must have been deleted.
if ( ( empty ( $event_status ) && ! $is_admin ) ||
    ( ! $can_view && empty ( $rss_view ) ) ) {
  echo print_not_auth ( true ) . print_trailer();
  exit;
}

// We can bypass $can_view if coming from RSS
if ( ( ! $can_view && empty ( $rss_view ) ) ) {
  echo print_not_auth ( true ) . print_trailer();
  exit;
}
// save date so the trailer links are for the same time period
$thisyear = intval ( $orig_date / 10000 );
$thismonth = ( $orig_date / 100 ) % 100;
$thisday = $orig_date % 100;
// $subject is used for mailto URLs.
$subject = generate_application_name() . ': ' . $name;
// Remove the '"' character since it causes some mailers to barf
$subject = str_replace ( ' "', '', $subject );
$subject = htmlspecialchars ( $subject );

$event_repeats = false;
// Build info string for repeating events and end date.
$res = dbi_execute ( 'SELECT cal_type FROM webcal_entry_repeats
  WHERE cal_id = ?', [$id] );
$rep_str = '';
if ( $res ) {
  if ( $tmprow = dbi_fetch_row ( $res ) )
    $event_repeats = true;

  dbi_free_result ( $res );
}
/* calculate end time */
$end_str = ( $event_time >= 0 && $duration > 0
  ? '-' . display_time ( $display_date
     . add_duration ( $event_time, $duration % 1440 ), 2 )
  : '' );

// get the email adress of the creator of the entry
user_load_variables ( $create_by, 'createby_' );
$email_addr = empty ( $createby_email ) ? '' : $createby_email;

// If Private and not this user's event or
// Confidential and not user's and not assistant,
// then they cannot see name or description.
// if ( $row[8] == "R" && ! $is_my_event && ! $is_admin ) {
if ( $cal_access == 'R' && ! $is_my_event && ! access_is_enabled() ) {
  $is_private = true;
  $description = $name = '[' . translate ( 'Private' ) . ']';
} else if ( $cal_access == 'C' && ! $is_my_event && ! $is_assistant && !
  access_is_enabled() ) {
  $is_confidential = true;
  $description = $name = '[' . translate ( 'Confidential' ) . ']';
}
$event_date = ( $event_repeats && ! empty ( $date ) ? $date : $orig_date );

// Get category Info
if ( $CATEGORIES_ENABLED == 'Y' ) {
  $categories = get_categories_by_id ( $id,
    ( ( ! empty ( $user ) && strlen ( $user ) ) && ( $is_assistant || $is_admin )
      ? $user : $login ), true );
  $category = implode ( ', ', $categories );
}

// get reminders
$reminder = getReminders ( $id, true );
echo '
    <h2>' . $name . ( $is_nonuser_admin ||
  ( $is_admin && ! empty ( $user ) && $user == '__public__' )
  ? '  ( ' . translate ( 'Admin mode' ) . ' )' : '' )
 . ( $is_assistant ? ' ( ' . translate ( 'Assistant mode' ) . ' )' : '' )
 . '</h2>
    <table>
      <tr>
        <td class="aligntop bold colon" width="10%">' . translate ( 'Description' )
 . '</td>
        <td>';

if ( ! empty ( $ALLOW_HTML_DESCRIPTION ) && $ALLOW_HTML_DESCRIPTION == 'Y' ) {
  $str = $description;
  // $str = str_replace ( '&', '&amp;', $description );
  $str = str_replace ( '&amp;amp;', '&amp;', $str );
  // If there is no HTML found, then go ahead and replace
  // the line breaks ("\n") with the HTML break.
  echo ( strstr ( $str, '<' ) && strstr ( $str, '>' )
    ? $str // found some html...
    : nl2br ( activate_urls ( $str ) ) );
} else
  echo nl2br ( activate_urls ( htmlspecialchars ( $description ) ) );

echo '</td>
      </tr>' . ( $DISABLE_LOCATION_FIELD != 'Y' && ! empty ( $location ) ? '
      <tr>
        <td class="aligntop bold colon">' . translate ( 'Location' ) . '</td>
        <td>' . $location . '</td>
      </tr>' : '' ) . ( $DISABLE_URL_FIELD != 'Y' && ! empty ( $url ) ? '
      <tr>
        <td class="aligntop bold colon">' . translate ( 'URL' ) . '</td>
        <td>' . activate_urls ( $url ) . '</td>
      </tr>' : '' );

if ( $event_status != 'A' && ! empty ( $event_status ) ) {
  echo '
      <tr>
        <td class="aligntop bold colon">' . translate ( 'Status' ) . '</td>
        <td>';

  if ( $event_status == 'D' )
    echo ( $eType == 'task'
      ? translate ( 'Declined' ) : translate ( 'Deleted' ) );
  elseif ( $event_status == 'R' )
    echo translate ( 'Rejected' );
  elseif ( $event_status == 'W' )
    echo ( $eType == 'task'
      ? translate ( 'Needs-Action' ) : translate ( 'Waiting for approval' ) );

  echo '</td>
      </tr>';
}

echo '
      <tr>
        <td class="aligntop bold colon">'
 . ( $eType == 'task' ? translate ( 'Start Date' ) : translate ( 'Date' ) )
 . '</td>
        <td>' . date_to_str ( $display_date ) . ( $eType == 'task' ? '</td>
      </tr>' . ( $event_time >= 0 ? '
      <tr>
        <td class="aligntop bold colon">' . translate ( 'Start Time' ) . '</td>
        <td>'
     . display_time ( $display_date . sprintf ( "%06d", $event_time ), 2 )
     . '</td>
      </tr>' : '' ) . '
      <tr>
        <td class="aligntop bold colon">' . translate ( 'Due Date' ) . '</td>
        <td>' . date_to_str ( $due_date ) . '</td>
      </tr>
      <tr>
        <td class="aligntop bold colon">' . translate ( 'Due Time' ) . '</td>
        <td>' . display_time ( $due_date . sprintf ( "%06d", $due_time ), 2 )
   . '</td>
      </tr>' . ( ! empty ( $cal_completed ) ? '
      <tr>
        <td class="aligntop bold colon">' . translate ( 'Completed' ) . '</td>
        <td>' . date_to_str ( $cal_completed ) : '' ) : '' ) . '</td>
      </tr>' . ( $event_repeats ? '
      <tr>
        <td class="aligntop bold colon">' . translate ( 'Repeat Type' ) . '</td>
        <td>' . export_recurrence_ical ( $id, true ) . '</td>
      </tr>' : '' ) . ( $eType != 'task' && $event_time >= 0 ? '
      <tr>
        <td class="aligntop bold colon">' . translate ( 'Time' ) . '</td>
        <td>' . ( $duration == 1440 && $event_time == 0
    ? translate ( 'All day event' )
    : display_time ( $display_date . sprintf ( "%06d", $event_time ),
      // Display TZID if no end time
      ( empty ( $end_str ) ? 2 : 0 ) )
     . $end_str ) . '</td>
      </tr>' : '' );

if ( $duration > 0 && $duration != 1440 ) {
  $dur_h = intval ( $duration / 60 );
  $dur_m = $duration - ( $dur_h * 60 );
  echo '
      <tr>
        <td class="aligntop bold colon">' . translate ( 'Duration' ) . '</td>
        <td>' . ( $dur_h > 0 ? $dur_h . ' ' . translate ( 'hour'
       . ( $dur_h == 1 ? '' : 's' ) ) . ' ' : '' )
   . ( $dur_m > 0 ? $dur_m . ' ' . translate ( 'minutes' ) : '' ) . '</td>
      </tr>';
}

echo ( $DISABLE_PRIORITY_FIELD != 'Y' ? '
      <tr>
        <td class="aligntop bold colon">' . translate ( 'Priority' ) . '</td>
        <td>' . $cal_priority . '-' . $pri[ceil($cal_priority/3)] .'</td>
      </tr>' : '' ) . ( $DISABLE_ACCESS_FIELD != 'Y' ? '
      <tr>
        <td class="aligntop bold colon">' . translate ( 'Access' ) . '</td>
        <td>' . ( $cal_access == "P"
    ? translate ( 'Public' )
    : ( $cal_access == 'C'
      ? translate ( 'Confidential' )
      : translate ( 'Private' ) ) ) . '</td>
      </tr>' : '' ) . ( $CATEGORIES_ENABLED == 'Y' && ! empty ( $category ) ? '
      <tr>
        <td class="aligntop bold colon">' . translate ( 'Category' ) . '</td>
        <td>' . $category . '</td>
      </tr>' : '' );

// Display who originally created event
// useful if assistant or Admin
$proxy_fullname = '';
if ( ! empty ( $DISPLAY_CREATED_BYPROXY ) && $DISPLAY_CREATED_BYPROXY == 'Y' ) {
  $res = dbi_execute ( 'SELECT cal_login FROM webcal_entry_log
    WHERE webcal_entry_log.cal_entry_id = ? AND webcal_entry_log.cal_type = \'C\'',
    [$id] );
  if ( $res ) {
    $row3 = dbi_fetch_row ( $res );
    if ( $row3 ) {
      user_load_variables ( $row3[0], 'proxy_' );
      $proxy_fullname = ( $createby_fullname == $proxy_fullname
        ? '' : ' ( ' . translate ( 'by' ) . ' ' . $proxy_fullname . ' )' );
    }
    dbi_free_result ( $res );
  }
}

if ( $single_user == 'N' && ! empty ( $createby_fullname ) ) {
  echo '
      <tr>
        <td class="aligntop bold colon">' . translate ( 'Created by' ) . '</td>
        <td>';
  if ( $is_private && ! access_is_enabled() )
    echo '[' . translate ( 'Private' ) . ']</td>
      </tr>';
  else
  if ( $is_confidential && ! access_is_enabled() )
    echo '[' . translate ( 'Confidential' ) . ']</td>
      </tr>';
  else {
    if ( access_is_enabled() )
      $can_email = access_user_calendar ( 'email', $create_by );

    $pubAccStr = ( $row[0] == '__public__'
      ? translate ( 'Public Access' ) : $createby_fullname );

    echo ( strlen ( $email_addr ) && $can_email != 'N'
      ? '<a href="mailto:' . $email_addr . '?subject=' . $subject . '">'
       . $pubAccStr . '</a>'
      : $pubAccStr )
     . $proxy_fullname . '</td>
      </tr>';
  }
}

echo '
      <tr>
        <td class="aligntop bold colon">' . translate ( 'Updated' ) . '</td>
        <td>'
 . ( ! empty ( $GENERAL_USE_GMT ) && $GENERAL_USE_GMT == 'Y'
  ? date_to_str ( $mod_date ) . ' ' . display_time ( $mod_date . $mod_time, 3 )
  : date_to_str ( date ( 'Ymd', date_to_epoch ( $mod_date . $mod_time ) ) )
   . ' ' . display_time ( $mod_date . $mod_time, 2 ) ) . '</td>
      </tr>'
// Display the reminder info if found.
 . ( ! empty ( $reminder ) ? '
      <tr>
        <td class="aligntop bold colon">' . translate ( 'Send Reminder' ) . '</td>
        <td>' . $reminder . '</td>
      </tr>' : '' );

// load any site-specific fields and display them
$extras = get_site_extra_fields ( $id );
$site_extracnt = count ( $site_extras );
for ( $i = 0; $i < $site_extracnt; $i++ ) {
  if ( $site_extras[$i] == 'FIELDSET' ) continue;
  $extra_name = $site_extras[$i][0];
  $extra_type = $site_extras[$i][2];
  $extra_arg1 = $site_extras[$i][3];
  $extra_arg2 = $site_extras[$i][4];
  if ( ! empty ( $site_extras[$i][5] ) )
    $extra_view = $site_extras[$i][5] & EXTRA_DISPLAY_VIEW;
  if ( ! empty ( $extras[$extra_name]['cal_name'] )  && ! empty ( $extra_view ) ) {
    echo '
      <tr>
        <td class="aligntop bold colon">' . translate ( $site_extras[$i][1] ) . '</td>
        <td>';

    if ( $extra_type == EXTRA_URL ) {
      $target = ( ! empty ( $extra_arg1 ) ? ' target="' . $extra_arg1 . '" ' : '' );
      echo ( strlen ( $extras[$extra_name]['cal_data'] ) ? '<a href="'
         . $extras[$extra_name]['cal_data'] . '"' . $target . '>'
         . $extras[$extra_name]['cal_data'] . '</a>' : '' );
     } elseif ( $extra_type == EXTRA_EMAIL )
      echo ( strlen ( $extras[$extra_name]['cal_data'] ) ? '<a href="mailto:'
         . $extras[$extra_name]['cal_data'] . '?subject=' . $subject . '">'
         . $extras[$extra_name]['cal_data'] . '</a>' : '' );
    elseif ( $extra_type == EXTRA_DATE )
      echo ( $extras[$extra_name]['cal_date'] > 0
        ? date_to_str ( $extras[$extra_name]['cal_date'] ) : '' );
    elseif ( $extra_type == EXTRA_TEXT || $extra_type == EXTRA_MULTILINETEXT )
      echo nl2br ( $extras[$extra_name]['cal_data'] );
    elseif ( $extra_type == EXTRA_USER || $extra_type == EXTRA_SELECTLIST
      || $extra_type == EXTRA_CHECKBOX )
      echo $extras[$extra_name]['cal_data'];
    elseif ( $extra_type == EXTRA_RADIO )
      echo $extra_arg1[$extras[$extra_name]['cal_data']];

    echo '</td>
      </tr>';
  }
}
// participants
// Only ask for participants if we are multi-user.
$allmails = [];
$show_participants = ( $DISABLE_PARTICIPANTS_FIELD != 'Y' );
if ( $is_admin )
  $show_participants = true;

if ( $PUBLIC_ACCESS == 'Y' && $login == '__public__' &&
  ( $PUBLIC_ACCESS_OTHERS != 'Y' || $PUBLIC_ACCESS_VIEW_PART == 'N' ) )
  $show_participants = false;

if ( $single_user == 'N' && $show_participants ) {
  echo '
      <tr>
        <td class="aligntop bold colon">' . translate ( 'Participants' ) . '</td>
        <td>';

  $num_app = $num_rej = $num_wait = 0;
  if ( $is_private && ! access_is_enabled() )
    echo '[' . translate ( 'Private' ) . ']';
  else
  if ( $is_confidential && ! access_is_enabled() )
    echo '[' . translate ( 'Confidential' ) . ']';
  else {
    $res = dbi_execute ( 'SELECT cal_login, cal_status, cal_percent
        FROM webcal_entry_user WHERE cal_id = ?'
       . ( $eType == 'task' ? ' AND cal_status IN ( \'A\', \'W\' )' : '' ),
      [$id] );
    $first = 1;
    if ( $res ) {
      while ( $row = dbi_fetch_row ( $res ) ) {
        $participants[] = $row;
        $pname = $row[0];

        if ( $row[1] == 'A' )
          $approved[$num_app++] = $pname;
        elseif ( $row[1] == 'R' )
          $rejected[$num_rej++] = $pname;
        elseif ( $row[1] == 'W' )
          $waiting[$num_wait++] = $pname;
      }
      dbi_free_result ( $res );
    } else
      db_error() . '<br />';
  }
  if ( $eType == 'task' ) {
    echo '
          <table border="1" width="80%" cellpadding="1">
            <th class="aligncenter">' . translate ( 'Participants' ) . '</th>
            <th class="aligncenter" colspan="2">'
     . translate ( 'Percentage Complete' ) . '</th>';
    $others_complete = 'yes';
    for ( $i = 0, $cnt = count ( $participants ); $i < $cnt; $i++ ) {
      user_load_variables ( $participants[$i][0], 'temp' );
      if ( access_is_enabled() )
        $can_email = access_user_calendar ( 'email', $templogin );
      $spacer = 100 - $participants[$i][2];
      $percentage = $participants[$i][2];
      if ( $participants[$i][0] == $login )
        $login_percentage = $participants[$i][2];
      else
      if ( $participants[$i][2] < 100 )
        $others_complete = 'no';

      echo '
            <tr>
              <td width="30%">';
      if ( strlen ( $tempemail ) && $can_email != 'N' ) {
        echo '<a href="mailto:' . $tempemail . '?subject=' . $subject
         . '">&nbsp;' . $tempfullname . '</a>';
        $allmails[] = $tempemail;
      } else
        echo '&nbsp;' . $tempfullname;

      echo '</td>
              <td width="5%" class="aligncenter">' . $percentage . '%</td>
              <td width="65%">
                <img src="images/pix.gif" width="' . $percentage
       . '%" height="10">
                <img src="images/spacer.gif" width="' . $spacer
       . '" height="10">
              </td>
            </tr>';
    }
    echo '
          </table>';
  } else {
    for ( $i = 0; $i < $num_app; $i++ ) {
      user_load_variables ( $approved[$i], 'temp' );
      if ( access_is_enabled() )
        $can_email = access_user_calendar ( 'email', $templogin );
      echo '
          ';
      if ( strlen ( $tempemail ) > 0 && $can_email != 'N' ) {
        echo '<a href="mailto:' . $tempemail . '?subject=' . $subject . '">'
         . $tempfullname . '</a>';
        $allmails[] = $tempemail;
      } else
        echo $tempfullname;

      echo '<br />';
    }
    // show external users here...
    if ( ! empty ( $ALLOW_EXTERNAL_USERS ) && $ALLOW_EXTERNAL_USERS == 'Y' ) {
      $external_users = event_get_external_users ( $id, 1 );
      $ext_users = explode ( "\n", $external_users );
      if ( is_array ( $ext_users ) ) {
        $externUserStr = translate ( 'External User' );
        for ( $i = 0, $cnt = count ( $ext_users ); $i < $cnt; $i++ ) {
          if ( ! empty ( $ext_users[$i] ) ) {
            echo '
          ' . $ext_users[$i] . ' (' . $externUserStr . ')<br />';
            if ( preg_match ( '/mailto: (\S+)"/', $ext_users[$i], $match ) )
              $allmails[] = $match[1];
          }
        }
      }
    }
    for ( $i = 0; $i < $num_wait; $i++ ) {
      user_load_variables ( $waiting[$i], 'temp' );
      if ( access_is_enabled() )
        $can_email = access_user_calendar ( 'email', $templogin );
      echo '
          ';
      if ( strlen ( $tempemail ) > 0 && $can_email != 'N' ) {
        echo '<a href="mailto:' . $tempemail . '?subject=' . $subject . '">'
         . $tempfullname . '</a>';
        $allmails[] = $tempemail;
      } else
        echo $tempfullname;

      echo ' (?)<br />';
    }
    for ( $i = 0; $i < $num_rej; $i++ ) {
      user_load_variables ( $rejected[$i], 'temp' );
      if ( access_is_enabled() )
        $can_email = access_user_calendar ( 'email', $templogin );

      echo '
          <strike>' . ( strlen ( $tempemail ) > 0 && $can_email != 'N'
        ? '<a href="mailto:' . $tempemail . '?subject=' . $subject . '">'
         . $tempfullname . '</a>'
        : $tempfullname ) . '</strike> (' . translate ( 'Rejected' ) . ')<br />';
    }
  }

  echo '
        </td>
      </tr>';
} // end participants

$can_edit = ( $can_edit || $is_admin || $is_nonuser_admin &&
  ( $user == $create_by ) ||
  ( $is_assistant && ! $is_private && ( $user == $create_by ) ) ||
  ( $readonly != 'Y' && ( $login != '__public__' && $login == $create_by ||
  $single_user == 'Y' ) ) );

if ( empty ( $event_status ) ) {
  // this only happens when an admin views a deleted event that he is
  // not a participant for. Set to $event_status to "D" just to get
  // rid of all the edit/delete links below.
  $event_status = 'D';
}

if ( $eType == 'task' ) {
  // allow user to update their task completion percentage
  if ( empty ( $user ) && $readonly != 'Y' && $is_my_event &&
      ( $login != '__public__' ) && ! $is_nonuser && $event_status != 'D' ) {
    echo '
      <tr>
        <td class="aligntop bold">
          <form action="view_entry.php?id=' . $id
     . '" method="post" name="setpercentage">
            <input type="hidden" name="others_complete" value="'
     . $others_complete . '" />' . translate ( 'Update Task Percentage' ) . '
        </td>
        <td>
            <select name="upercent" id="task_percent">';
    for ( $i = 0; $i <= 100; $i += 10 ) {
      echo '
              <option value="' . "$i\" " . ( $login_percentage == $i
        ? ' selected="selected"':'' ) . ' >' . $i . '</option>';
    }
    echo '
            </select>&nbsp;
            <input type="submit" value="' . translate ( 'Update' ) . '" />
          </form>
        </td>
      <tr>';
  }
}

if ( Doc::attachmentsEnabled() && $rss_view == false ) {
  echo '
      <tr>
        <td class="aligntop bold colon">' . translate ( 'Attachments' ) . '</td>
        <td>';

  $attList = new AttachmentList( $id );
  for ( $i = 0; $i < $attList->getSize(); $i++ ) {
    $a = $attList->getDoc ( $i );
    echo '
          ' . $a->getSummary()
    // show delete link if user can delete
    . ( $is_admin || $login == $a->getLogin()
        || user_is_assistant( $login, $a->getLogin() ) || $login == $create_by
        || user_is_assistant( $login, $create_by )
      ? ' <a href="docdel.php?blid=' . $a->getId()
       . '" onclick="return confirm( \'' . $areYouSureStr . '\' );">'
       . '<img src="images/delete.png"/></a>' : '' ) . '<br />';
  }
  $num_app = $num_rej = $num_wait = 0;
  $num_attach = $attList->getSize();

  echo ( $num_attach == 0 ? '
          ' . translate ( 'None' ) . '<br />' :'' ) . '
        </td>
      </tr>';
}

if ( Doc::commentsEnabled() ) {
  echo '
      <tr>
        <td class="aligntop bold colon">' . translate ( 'Comments' ) . '</td>
        <td>';

  $comList = new CommentList( $id );
  $num_comment = $comList->getSize();
  $comment_text = '';
  for ( $i = 0; $i < $num_comment; $i++ ) {
    $cmt = $comList->getDoc ( $i );
    user_load_variables ( $cmt->getLogin(), 'cmt_' );
    $comment_text .= '
          <strong>' . htmlspecialchars ( $cmt->getDescription() )
     . '</strong> - ' . $cmt_fullname . ' ' . translate ( 'at' ) . ' '
     . date_to_str ( $cmt->getModDate(), '', false, true ) . ' '
     . display_time ( $cmt->getModTime(), 2 )
    // show delete link if user can delete
    . ( $is_admin || $login == $cmt->getLogin()
        || user_is_assistant( $login, $cmt->getLogin() ) || $login == $create_by
        || user_is_assistant( $login, $create_by )
      ? ' <a href="docdel.php?blid=' . $cmt->getId()
       . '" onclick="return confirm( \'' . $areYouSureStr
       . '\' );"><img src="images/delete.png"/></a>' : '' )// end show delete link
     . '<br />
          <blockquote id="eventcomment">';
     if ( ! empty ( $ALLOW_HTML_DESCRIPTION ) && $ALLOW_HTML_DESCRIPTION == 'Y' ) {
       $str = $cmt->getData();
       $str = str_replace ( '&amp;amp;', '&amp;', $str );
       // If there is no HTML found, then go ahead and replace
       // the line breaks ("\n") with the HTML break.
       $comment_text .= ( strstr ( $str, '<' ) && strstr ( $str, '>' )
         ? $str // found some html...
         : nl2br ( activate_urls ( $str ) ) );
     } else {
       $comment_text .= nl2br ( activate_urls (
        htmlspecialchars( $cmt->getData() ) ) );
     }
     $comment_text .= '</blockquote><div style="clear:both"></div>';
  }

  if ( $num_comment == 0 )
    echo translate ( 'None' ) . '<br />';
  else {
    echo '
          ' . $num_comment . ' ' . translate ( 'comments' ) . '
          <input id="showbutton" type="button" value="' . translate ( 'Show' )
     . '" onclick="showComments();" />
          <input id="hidebutton" type="button" value="' . translate ( 'Hide' )
     . '" onclick="hideComments();" /><br />
          <div id="comtext">' . $comment_text . '</div>';
    // We could put the following JS in includes/js/view_entry.php,
    // but we won't need it in many cases and we don't know whether
    // we need it until after would need to include it.
    // So, we will include it here instead.
    ?>
<script>
<!-- <![CDATA[
function showComments() {
  var x = document.getElementById ( "comtext" )
  if ( x ) {
    x.style.display = "block";
  }
  x = document.getElementById ( "showbutton" )
  if ( x ) {
    x.style.display = "none";
  }
  x = document.getElementById ( "hidebutton" )
  if ( x ) {
    x.style.display = "block";
  }
}
function hideComments() {
  var x = document.getElementById ( "comtext" )
  if ( x ) {
    x.style.display = "none";
  }
  x = document.getElementById ( "showbutton" )
  if ( x ) {
    x.style.display = "block";
  }
  x = document.getElementById ( "hidebutton" )
  if ( x ) {
    x.style.display = "none";
  }
}
hideComments();
//]]> -->
</script>
    <?php
  }

  $num_app = $num_rej = $num_wait = 0;

  echo '</td>
      </tr>';
}

$rdate = ( $event_repeats ? '&amp;date=' . $event_date : '' );

$u_url = ( ! empty ( $user ) && $login != $user ? "&amp;user=$user" : '' );

echo '
    </table>
    <ul class="nav">';

// Show a printer-friendly link
if ( empty ( $friendly ) )
  echo $printerStr;

if ( ( $is_my_event || $is_nonuser_admin || $is_assistant || $can_approve )
    && $readonly == 'N' && $login != '__public__') {
  if ( $event_status != 'A' ) {
    $approveStr = translate( 'Approve/Confirm entry' );
    echo '
        <li><a title="' . $approveStr
     . '" class="nav" href="approve_entry.php?id=' . $id . $u_url
     . '&amp;type=E" onclick="return confirm( \''
     . translate( 'Approve this entry?', true ) . '\' );">'
     . $approveStr . '</a></li>';
  }
  if ( $event_status != 'R' ) {
    $rejectStr = translate( 'Reject entry' );
    echo '
        <li><a title="' . $rejectStr
     . '" class="nav" href="reject_entry.php?id=' . $id . $u_url
     . '&amp;type=E" onclick="return confirm( \''
     . translate( 'Reject this entry?', true ) . '\' );">'
     . $rejectStr . '</a></li>';
  }
}

// TODO add these permissions to the UAC list
$can_add_attach = ( Doc::attachmentsEnabled() && $login != '__public__'
  && ( ( $login == $create_by ) || ( $is_my_event && $ALLOW_ATTACH_PART == 'Y' ) ||
  ( $ALLOW_ATTACH_ANY == 'Y' ) || $is_admin ) );

$can_add_comment = ( Doc::commentsEnabled() && $login != '__public__'
  && ( ( $login == $create_by ) ||  ( $is_my_event && $ALLOW_COMMENTS_PART == 'Y' ) ||
  ( $ALLOW_COMMENTS_ANY == 'Y' ) || $is_admin ) );

if ( $can_add_attach && $event_status != 'D' ) {
  $addAttchStr = translate ( 'Add Attachment' );
  echo '
      <li><a title="' . $addAttchStr
   . '" class="nav" href="docadd.php?type=A&amp;id=' . $id
   . $u_url . '">' . $addAttchStr
   . '</a></li>';
}

if ( $can_add_comment && $event_status != 'D' ) {
  $addCommentStr = translate ( 'Add Comment' );
  echo '
      <li><a title="' . $addCommentStr
   . '" class="nav" href="docadd.php?type=C&amp;id=' . $id
   . $u_url . '">' . $addCommentStr
   . '</a></li>';
}

// If approved, but event category not set (and user does not have permission
// to edit where they could also set the category), then allow them to
// set it through set_cat.php.
if ( empty ( $user ) && $CATEGORIES_ENABLED == 'Y' && $readonly != 'Y' &&
    $is_my_event && $login != '__public__' && !
    $is_nonuser && $event_status != 'D' && ! $can_edit ) {
  $setCatStr = translate ( 'Set category' );
  echo '
      <li><a title="' . $setCatStr . '" class="nav" href="set_entry_cat.php?id='
   . $id . $rdate . '">' . $setCatStr . '</a></li>';
}

$addToMineStr = translate ( 'Add to My Calendar' );
$copyStr = translate ( 'Copy entry' );
$deleteAllStr = translate ( 'This will delete this entry for all users.', true );
$deleteEntryStr = translate ( 'Delete entry' );
$editEntryStr = translate ( 'Edit entry' );

//TODO Don't show if $user != $login and not assistant
// This will be easier with UAC always on
if ( $can_edit && $event_status != 'D' && ! $is_nonuser && $readonly != 'Y' ) {
  if ( $event_repeats ) {
    $editAllDatesStr = translate ( 'Edit repeating entry for all dates' );
    $deleteAllDatesStr = translate ( 'Delete repeating event for all dates' );
    echo '
      <li><a title="' . $editAllDatesStr
     . '" class="nav" href="edit_entry.php?id=' . $id . $u_url . '">'
     . $editAllDatesStr . '</a></li>';
    // Don't allow override of first event
    if ( ! empty ( $date ) && $date != $orig_date ) {
      $editThisDateStr = translate ( 'Edit entry for this date' );
      echo '
      <li><a title="' . $editThisDateStr . '" class="nav" '
       . 'href="edit_entry.php?id=' . $id . $u_url . $rdate . '&amp;override=1">'
       . $editThisDateStr . '</a></li>';
    }
    echo '
      <li><a title="' . $deleteAllDatesStr
     . '" class="nav" href="del_entry.php?id=' . $id . $u_url
     . '&amp;override=1" onclick="return confirm( \'' . $areYouSureStr . "\\n\\n"
     . $deleteAllStr . '\' );">' . $deleteAllDatesStr . '</a></li>';
    // Don't allow deletion of first event
    if ( ! empty ( $date ) && $date != $orig_date ) {
      $deleteOnlyStr = translate ( 'Delete entry only for this date' );
      echo '
      <li><a title="' . $deleteOnlyStr . '" class="nav" href="del_entry.php?id='
       . $id . $u_url . $rdate . '&amp;override=1" onclick="return confirm( \''
       . $areYouSureStr . "\\n\\n" . $deleteAllStr . '\' );">' . $deleteOnlyStr
       . '</a></li>';
    }
  } else {
    if ( ! empty( $user ) && $user != $login && ! $is_assistant ) {
      user_load_variables( $user, 'temp_' );
      $delete_str = str_replace( 'XXX', $temp_fullname,
                                translate( 'Delete entry from calendar of XXX' ) );
    } else {
      $delete_str = $deleteEntryStr;
    }
    echo '
      <li><a title="' . $editEntryStr . '" class="nav" href="edit_entry.php?id='
     . $id . $u_url . '">' . $editEntryStr . '</a></li>
      <li><a title="' . $delete_str . '" class="nav" href="del_entry.php?id='
     . $id . $u_url . $rdate . '" onclick="return confirm( \'' . $areYouSureStr
     . "\\n\\n"
     . ( empty ( $user ) || $user == $login || $is_assistant
      ? $deleteAllStr : '' )
     . '\' );">' . $delete_str;
    echo '</a></li>';
  }
  echo '
      <li><a title="' . $copyStr . '" class="nav" href="edit_entry.php?id='
   . $id . $u_url . '&amp;copy=1">' . $copyStr . '</a></li>';
} elseif ( $readonly != 'Y' &&
  ( $is_my_event || $is_nonuser_admin || $can_edit ) &&
    ( $login != '__public__' ) && ! $is_nonuser && $event_status != 'D' ) {
  $delFromCalStr =
  translate ( 'This will delete the entry from your XXX calendar.', true );
  echo '
      <li><a title="' . $deleteEntryStr . '" class="nav" href="del_entry.php?id='
   . $id . $u_url . $rdate . '" onclick="return confirm( \'' . $areYouSureStr
   . "\\n\\n"
   . str_replace ( 'XXX ',
    ( $is_assistant ? translate ( 'boss' ) . ' ' : '' ), $delFromCalStr )
  // ( $is_assistant
  // ? translate ( 'This will delete the entry from your boss calendar.', true )
  // : translate ( 'This will delete the entry from your calendar.', true ) )
  . '\' );">'
   . $deleteEntryStr
   . ( $is_assistant ? ' ' . translate ( 'from your boss calendar' ) : '' )
   . '</a></li>
      <li><a title="' . $copyStr . '" class="nav" href="edit_entry.php?id='
   . $id . '&amp;copy=1">' . $copyStr . '</a></li>';
}

if ( $readonly != 'Y' && ! $is_my_event && ! $is_private && !
  $is_confidential && $event_status != 'D' && $login != '__public__' && !
  $is_nonuser )
  echo '
      <li><a title="' . $addToMineStr . '" class="nav" href="add_entry.php?id='
   . $id . '" onclick="return confirm( \''
   . translate ( 'Do you want to add this entry to your calendar?', true )
   . "\\n\\n" . translate ( 'This will add the entry to your calendar.', true )
   . '\' );">' . $addToMineStr . '</a></li>';

if ( $login != '__public__' && count ( $allmails ) > 0 ) {
  $emailAllStr = translate ( 'Email all participants' );
  echo '
      <li><a title="' . $emailAllStr . '" class="nav" href="mailto:'
   . implode ( ',', $allmails ) . '?subject=' . rawurlencode ( $subject ) . '">'
   . $emailAllStr . '</a></li>';
}

$can_show_log = $is_admin; // default if access control is not enabled
if ( access_is_enabled() )
  $can_show_log = access_can_access_function ( ACCESS_ACTIVITY_LOG );

if ( $can_show_log ) {
  $hideActivityStr = translate ( 'Hide activity log' );
  $showActivityStr = translate ( 'Show activity log' );
  echo '
      <li><a title="'
   . ( ! $show_log
    ? $showActivityStr . '" class="nav" href="view_entry.php?id=' . $id
     . '&amp;log=1">' . $showActivityStr
    : $hideActivityStr . '" class="nav" href="view_entry.php?id=' . $id . '">'
     . $hideActivityStr )
   . '</a></li>';
}

echo '
    </ul>';
if ( $can_show_log && $show_log ) {
  $PAGE_SIZE = 25; // number of entries to show at once
  echo generate_activity_log ( $id );
}

if ( access_can_access_function ( ACCESS_EXPORT ) &&
    ( ( ! $is_private && ! $is_confidential ) || ! access_is_enabled() ) && !
    $hide_details ) {
  $exportStr = translate ( 'Export' );
  $exportThisStr = translate ( 'Export this entry to' );
  $palmStr = translate ( 'Palm Pilot' );
  $selectStr = generate_export_select();
  $userStr = ( ! empty ( $user ) ? '<input type="hidden" name="user" value="' .
    $user . '" />' : '' );
  echo <<<EOT
    <br />
    <form method="post" name="exportform" action="export_handler.php">
      <label for="exformat">{$exportThisStr}:&nbsp;</label>
      {$selectStr}
      <input type="hidden" name="id" value="{$id}" />
          {$userStr}
      <input type="submit" value="{$exportStr}" />
    </form>
EOT;
}

echo print_trailer ( empty ( $friendly ) );

?>
