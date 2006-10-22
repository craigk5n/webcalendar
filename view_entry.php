<?php
/*
 * $Id$
 *
 * Description:
 * Presents page to view an event with links to edit, delete
 * confirm, copy, add event
 *
 * Input Parameters:
 * id (*) - cal_id of requested event
 * date  - yyyymmdd format of requested event
 * user  - user to display
 * log - show activity log (any non-empty value)
 * (*) required field
 */
include_once 'includes/init.php';
include 'includes/xcal.php'; //only to display recurrance info
// Load Doc classes for attachments and comments
include 'includes/classes/Doc.class';
include 'includes/classes/DocList.class';
include 'includes/classes/AttachmentList.class';
include 'includes/classes/CommentList.class';

// make sure this user is allowed to look at this calendar.
$can_view = $can_edit = $can_approve = false;
$is_my_event = false; // is this user owner or participant?
$is_private = $is_confidential = $unapproved = false;
$event_status = '';
$log = getGetValue ( 'log' );
$show_log = ! empty ( $log );
$can_email = 'Y';
$rss_view = false;

$error = '';
$eType = '';
$pri[1] = translate( 'Low' );
$pri[2] = translate( 'Medium' );
$pri[3] = translate( 'High' );

if ( empty ( $id ) || $id <= 0 || ! is_numeric ( $id ) ) {
  $error = translate( 'Invalid entry id' ) . '.'; 
}

if ( $login == '__public__' &&
  ! empty ( $OVERRIDE_PUBLIC ) && $OVERRIDE_PUBLIC == 'Y' ) {
  $hide_details = true;
} else {
  $hide_details = false;
}

//if sent here from an email and not logged in,
//save URI and redirect to login
if ( empty ( $error ) ) {
  $em = getGetValue ( 'em' );
  if ( ! empty ( $em ) && empty ( $login ) ) {
    remember_this_view ();
    do_redirect ( 'login.php' );  
  } 
}

//Check if we can display basic info for RSS FEED
$rssuser = getGetValue ( 'rssuser' );
if ( ! empty ( $rssuser ) ) {
  $user_rss_enabled = get_pref_setting ( $rssuser, 'USER_RSS_ENABLED' );
  $user_rss_timezone = get_pref_setting ( $rssuser, 'TIMEZONE' );
  $rss_view = ( $RSS_ENABLED == 'Y' && $user_rss_enabled == 'Y' &&
    $friendly ==1 && ! empty ( $rssuser ) ?  true : false );
  if ( $rss_view == true ) {
    $hide_details = false;
    //make sure the displayed time is accurate
    set_env ( 'TZ', $user_rss_timezone );
  }
}



  // is this user a participant or the creator of the event?
  // if assistant is doing this, then we need to switch login
  // to user in the sql
  $sqlparm = ( $is_assistant ? $user : $login );
  $sql = 'SELECT webcal_entry.cal_id FROM webcal_entry, ' .
    'webcal_entry_user WHERE webcal_entry.cal_id = ' .
    'webcal_entry_user.cal_id AND webcal_entry.cal_id = ? ' .
    'AND (webcal_entry.cal_create_by = ? ' .
    'OR webcal_entry_user.cal_login = ?)';
  $res = dbi_execute ( $sql , array ( $id , $sqlparm, $sqlparm ) );
  if ( $res ) {
    $row = dbi_fetch_row ( $res );
    if ( $row && $row[0] > 0 ) {
      $can_view = true;
      $is_my_event = true;
    }
    dbi_free_result ( $res );
  }
//update the task percentage for this user
if ( ! empty ( $_POST ) && $is_my_event ) {
  $upercent = getPostValue ( 'upercent' );
 if ( $upercent >= 0 && $upercent <= 100 )
    dbi_execute ( 'UPDATE webcal_entry_user SET cal_percent = ? WHERE cal_login = ? ' .
    ' AND cal_id = ?' , array ( $upercent , $login, $id ) );
//check if all other user percent is 100%, if so, set cal_complete date
$others_complete = getPostValue ( 'others_complete' );
if ( $upercent == 100 && $others_complete == 'yes' )
  dbi_execute ( 'UPDATE webcal_entry SET cal_completed = ? WHERE ' .
    'cal_id = ?' , array ( gmdate ( 'Ymd', time() ), $id ) );

}

// Load event info now.
$sql = 'SELECT cal_create_by, cal_date, cal_time, cal_mod_date, ' .
  'cal_mod_time, cal_duration, cal_priority, cal_type, cal_access, ' .
  'cal_name, cal_description, cal_location, cal_url, cal_due_date, ' .
  'cal_due_time, cal_completed FROM webcal_entry  WHERE cal_id = ?';
$res = dbi_execute ( $sql , array ( $id ) );
if ( ! $res  ) {
  $error = translate( 'Invalid entry id' ) . ": $id";
} else {
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
    if ( $cal_type == 'T' || $cal_type == 'N' )
      $eType = 'task';
    $due_date = $row[13];
    $due_time = $row[14];
    $cal_completed = $row[15];
    if ( $hide_details ) {
      $overrideStr = translate ( $OVERRIDE_PUBLIC_TEXT );
      $name = $overrideStr;
      $description = $overrideStr;
      if ( ! empty ( $row[11] ) ) $location = $overrideStr;
      if ( ! empty ( $row[12] ) ) $url = $overrideStr;
    } else {
      $name = $row[9];
      $description = $row[10];
      $location = $row[11]; 
      $url = $row[12];    
    }
  } else {
     $error = translate( 'Invalid entry id' ) . ": $id";
  }
  dbi_free_result ( $res );
}

if ( empty ( $error ) ) {
  //don't shift date if All Day or Untimed
  $display_date = ( $event_time > 0 || ($event_time == 0 && $duration != 1440 )  ? date ('Ymd', 
    date_to_epoch ( $orig_date . sprintf( "%06d", $event_time ) ) ) :$orig_date );
  
  if ( ! empty ( $year ) ) {
    $thisyear = $year;
  }
  if ( ! empty ( $month ) ) {
    $thismonth = $month;
  }
  
  //check UAC
  if ( empty ( $user ) ) {
    $euser =  ( $is_my_event == true ? $login : $create_by );
  } else {
    $euser =  ( $login != $user ? $user : $login );
  }
  if ( access_is_enabled () && ! empty ( $user ) ) {
    $can_view =  access_user_calendar ( 'view', $euser, $login, $cal_type, $cal_access );
    $can_edit = access_user_calendar ( 'edit', $euser, $login, $cal_type, $cal_access );
    $can_approve = access_user_calendar ( 'approve', $euser, $login, $cal_type, $cal_access );
    $time_only = access_user_calendar ( 'time', $euser, $login, $cal_type, $cal_access );
  } else {
    $time_only = 'N';
  }
  if ( $is_admin || $is_nonuser_admin || $is_assistant ) {
    $can_view = true;
  }
    if ( ($login != '__public__') && ($PUBLIC_ACCESS_OTHERS == 'Y') ) {
      $can_view = true;
    }
   $can_edit = ( $can_edit || $is_admin || $is_nonuser_admin && ($user == $create_by) || 
    ( $is_assistant && ! $is_private && ($user == $create_by) ) ||
    ( $readonly != 'Y' && ( $login == $create_by || $single_user == 'Y' ) ) );
    
  if ( $readonly == 'Y' || $is_nonuser || 
    ( $PUBLIC_ACCESS == 'Y' && $login == '__public__' ) ) {
    $can_edit = false;
  }  
    if ( ! $can_view ) {
      $check_group = false;
      // if not a participant in the event, must be allowed to look at
      // other user's calendar.
      if ( $login == '__public__' ) {
        if ( $PUBLIC_ACCESS_OTHERS == 'Y' ) {
          $check_group = true;
        }
      } else {
        if ( $ALLOW_VIEW_OTHER == 'Y' ) {
          $check_group = true;
        }
      }
      // If $check_group is true now, it means this user can look at the
      // event only if they are in the same group as some of the people in
      // the event.
      // This gets kind of tricky.  If there is a participant from a different
      // group, do we still show it?  For now, the answer is no.
      // This could be configurable somehow, but how many lines of text would
      // it need in the admin page to describe this scenario?  Would confuse
      // 99.9% of users.
      // In summary, make sure at least one event participant is in one of
      // this user's groups.
      $my_users = get_my_users ();
      $my_usercnt = count ( $my_users );
      if ( is_array ( $my_users ) && $my_usercnt ) {
        $sql_params = array ();
        $sql = 'SELECT webcal_entry.cal_id FROM webcal_entry, ' .
          'webcal_entry_user WHERE webcal_entry.cal_id = ' .
          'webcal_entry_user.cal_id AND webcal_entry.cal_id = ? ' .
          'AND webcal_entry_user.cal_login IN ( ';
        $sql_params[] = $id;
        for ( $i = 0; $i < $my_usercnt; $i++ ) {
          if ( $i > 0 ) {
            $sql .= ', ';
          }
          $sql .= '?';
          $sql_params[] = $my_users[$i]['cal_login'];
        }
        $sql .= ' )';
        $res = dbi_execute ( $sql , $sql_params );
        if ( $res ) {
          $row = dbi_fetch_row ( $res );
          if ( $row && $row[0] > 0 ) {
            $can_view = true;
          }
          dbi_free_result ( $res );
        }
      }
      // If we didn't indicate we need to check groups, then this user
      // can't view this event.
      if ( ! $check_group || access_is_enabled ()  ) {
        $can_view = false;
      }
  }
} //end $error test

// If they still cannot view, make sure they are not looking at a nonuser
// calendar event where the nonuser is the _only_ participant.
if ( empty ( $error ) && ! $can_view && ! empty ( $NONUSER_ENABLED ) &&
  $NONUSER_ENABLED == 'Y' ) {
  $nonusers = get_nonuser_cals ();
  $nonuser_lookup = array ();
  for ( $i = 0, $cnt = count ( $nonusers ); $i < $cnt; $i++ ) {
    $nonuser_lookup[$nonusers[$i]['cal_login']] = 1;
  }
  $sql = "SELECT cal_login FROM webcal_entry_user WHERE cal_id = ? AND cal_status in ('A','W')";
  $res = dbi_execute ( $sql , array ( $id ) );
  $found_nonuser_cal = false;
  $found_reg_user = false;
  if ( $res ) {
    while ( $row = dbi_fetch_row ( $res ) ) {
      if ( ! empty ( $nonuser_lookup[$row[0]] ) ) {
        $found_nonuser_cal = true;
      } else {
        $found_reg_user = true;
      }
    }
    dbi_free_result ( $res );
  }
  // Does this event contain only nonuser calendars as participants?
  // If so, then grant access.
  if ( $found_nonuser_cal && ! $found_reg_user && ! access_is_enabled()) {
    $can_view = true;
  }
}

$printerStr = generate_printer_friendly ( 'view_entry.php' );

print_header ();

if ( ! empty ( $error ) ) {
  echo print_error ( $error );
  echo print_trailer ();
  exit;
}
// Try to determine the event status.
$event_status = '';

if ( ! empty ( $user ) && $login != $user ) {
  // If viewing another user's calendar, check the status of the
  // event on their calendar (to see if it's deleted).
  $sql = 'SELECT cal_status FROM webcal_entry_user ' .
    'WHERE cal_login = ? AND cal_id = ?';
  $res = dbi_execute ( $sql , array ( $user , $id ) );
  if ( $res ) {
    if ( $row = dbi_fetch_row ( $res ) ) {
      $event_status = $row[0];
    }
    dbi_free_result ( $res );
  }
} else {
  // We are viewing event on user's own calendar, so check the
  // status on their own calendar.
  $sql = 'SELECT cal_id, cal_status FROM webcal_entry_user ' .
    'WHERE cal_login = ? AND cal_id = ?';
  $res = dbi_execute ( $sql , array ( $user , $id ) );
  if ( $res ) {
    $row = dbi_fetch_row ( $res );
    $event_status = $row[1];
    dbi_free_result ( $res );
  }
}

// At this point, if we don't have the event status, then either
// this user is not viewing an event from his own calendar and not
// viewing an event from someone else's calendar.
// They probably got here from the search results page (or possibly
// by hand typing in the URL.)
// Check to make sure that it hasn't been deleted from everyone's
// calendar.
if ( empty ( $event_status ) ) {
  $sql = 'SELECT cal_status FROM webcal_entry_user ' .
    "WHERE cal_status <> 'D' ORDER BY cal_status";
  $res = dbi_execute ( $sql , array () );
  if ( $res ) {
    if ( $row = dbi_fetch_row ( $res ) ) {
      $event_status = $row[0];
    }
    dbi_free_result ( $res );
  }
}


// If we have no event status yet, it must have been deleted.
if ( ( empty ( $event_status ) && ! $is_admin ) || 
  ( ! $can_view  && empty ( $rss_view ) ) ) {
  echo print_not_auth ( true );
  echo print_trailer ();
  exit;
}


// save date so the trailer links are for the same time period
$thisyear = (int) ( $orig_date / 10000 );
$thismonth = ( $orig_date / 100 ) % 100;
$thisday = $orig_date % 100;

// $subject is used for mailto URLs
$subject = translate($APPLICATION_NAME) . ': ' . $name;
// Remove the '"' character since it causes some mailers to barf
$subject = str_replace ( ' "', '', $subject );
$subject = htmlspecialchars ( $subject );

$event_repeats = false;
// build info string for repeating events and end date
$sql = 'SELECT cal_type FROM webcal_entry_repeats WHERE cal_id = ?';
$res = dbi_execute ( $sql , array ( $id ) );
$rep_str = '';
if ( $res ) {
  if ( $tmprow = dbi_fetch_row ( $res ) ) {
    $event_repeats = true;
  }
  dbi_free_result ( $res );
}
/* calculate end time */
if ( $event_time >= 0 && $duration > 0 )
  $end_str = '-' . display_time ( $display_date . 
    add_duration ( $event_time, $duration % 1440 ), 2 );
else
  $end_str = '';

// get the email adress of the creator of the entry
user_load_variables ( $create_by, 'createby_' );
$email_addr = empty ( $createby_email ) ? '' : $createby_email;

// If private and not this user's event or
// Confidential and not user's or not assistant, then
// They cannot seem name or description.
//if ( $row[8] == "R" && ! $is_my_event && ! $is_admin ) {
if ( $cal_access == 'R' && ! $is_my_event && ! access_is_enabled() ) {
  $is_private = true;
    $description = $name = '[' . ucfirst ( translate( 'private' ) ) . ']';

} else if ( $cal_access == 'C' &&  ! $is_my_event && 
  ! $is_assistant  && ! access_is_enabled() ) {
  $is_confidential = true;
  $description = $name = '[' . ucfirst ( translate( 'confidential' ) ) . ']';

}
if ( $event_repeats && ! empty ( $date ) )
  $event_date = $date;
else
  $event_date = $orig_date;


// Get category Info
if ( $CATEGORIES_ENABLED == 'Y' ) {
  $cat_owner =  ( ( ! empty ( $user ) && strlen ( $user ) ) &&  ( $is_assistant  ||
    $is_admin ) ) ? $user : $login;  
  $categories = get_categories_by_id ( $id, $cat_owner, true );
  $category = implode ( ', ', $categories);
}

  //get reminders 
  $reminder = getReminders ( $id, true );
  
?>
<h2><?php echo  $name ; 
  if ( $is_nonuser_admin || ( $is_admin && ! empty( $user ) && $user == '__public__' ) )
    echo '  ( ' . translate('Admin mode') . ' )';
  if ( $is_assistant )
    echo '  ( ' . translate('Assistant mode') . ' )';
?></h2>
<table width="100%">
<tr><td class="aligntop bold" width="10%">
 <?php etranslate( 'Description' )?>:</td><td>
 <?php
  if ( ! empty ( $ALLOW_HTML_DESCRIPTION ) &&
    $ALLOW_HTML_DESCRIPTION == 'Y' ) {
     $str = $description ;
 //   $str = str_replace ( '&', '&amp;', $description );
    $str = str_replace ( '&amp;amp;', '&amp;', $str );
    // If there is no html found, then go ahead and replace
    // the line breaks ("\n") with the html break.
    if ( strstr ( $str, '<' ) && strstr ( $str, '>' ) ) {
      // found some html...
      echo $str;
    } else {
      echo nl2br ( activate_urls ( $str ) );
    }
  } else {
    echo nl2br ( activate_urls ( htmlspecialchars ( $description ) ) );
  }
?></td></tr>
 <?php
  if ( $DISABLE_LOCATION_FIELD != 'Y' && ! empty ( $location ) ) { 
    echo '<tr><td class="aligntop bold">';
    echo translate( 'Location' ) . ':</td><td>';
    echo $location . "</td><tr>\n";
  }
  if ( $DISABLE_URL_FIELD != 'Y' && ! empty ( $url ) ) { 
    echo '<tr><td class="aligntop bold">';
    echo translate( 'URL' ) . ':</td><td>';
    echo activate_urls ( $url ) . "</td><tr>\n";
  }
    
 if ( $event_status != 'A' && ! empty ( $event_status ) ) { ?>
<tr><td class="aligntop bold">
 <?php etranslate( 'Status' )?>:</td><td>
 <?php
     if ( $event_status == 'A' )
       etranslate( 'Accepted' );
     if ( $event_status == 'W' )
       echo ( $eType == 'task' ?translate( 'Needs-Action' ) : 
        translate( 'Waiting for approval' ) );
   
     if ( $event_status == 'D' )
      echo ( $eType == 'task' ? translate( 'Declined' ) : translate( 'Deleted' ) );
     else if ( $event_status == 'R' )
       etranslate( 'Rejected' );
      ?>
</td></tr>
<?php } ?>

<tr><td class="aligntop bold">
 <?php echo ( $eType == 'task' ? translate('Start Date' ) : translate( 'Date' ) )?>:</td><td>
 <?php
 if ( $eType == 'task' ) {
   echo date_to_str ( $display_date );
  ?>
</td></tr>
<?php if ( $event_time >= 0 ) { ?>
<tr><td class="aligntop bold">
 <?php etranslate( 'Start Time' )?>:</td><td>
 <?php
   echo display_time ( $display_date . sprintf( "%06d", $event_time ), 2 );
  ?>
</td></tr>
<?php } ?>
<tr><td class="aligntop bold">
 <?php etranslate( 'Due Date' )?>:</td><td>
 <?php
   echo date_to_str ( $due_date );
  ?>
</td></tr>
<tr><td class="aligntop bold">
 <?php etranslate( 'Due Time' )?>:</td><td>
 <?php
   echo display_time (  $due_date . sprintf( "%06d", $due_time ), 2 );
  ?>
  </td></tr>
  <?php if (! empty ( $cal_completed ) ) { ?>
    <tr><td class="aligntop bold">
    <?php echo translate( 'Completed' ) . ":</td><td>\n";
    echo date_to_str (  $cal_completed ); 
   }
 } else {
   echo date_to_str ( $display_date );
 }
  ?>
</td></tr>
<?php if ( $event_repeats ) { ?>
<tr><td class="aligntop bold">
 <?php etranslate( 'Repeat Type' )?>:</td><td>
 <?php echo export_recurrence_ical( $id , true); ?>
</td></tr>
<?php }
if ( $eType != 'task' && $event_time >= 0 ) { ?>
<tr><td class="aligntop bold">
 <?php etranslate( 'Time' )?>:</td><td>
 <?php
    if ( $duration == 1440  && $event_time == 0 ) {
      etranslate( 'All day event' );
    } else {
      // Display TZID if no end time
      $display_tzid = empty ( $end_str ) ? 2 : 0;
      echo display_time ( $display_date . sprintf( "%06d", $event_time ), 
        $display_tzid ) . $end_str;
    }
  ?>
</td></tr>
<?php }
if ( $duration > 0 && $duration != 1440 ) { ?>
<tr><td class="aligntop bold">
 <?php etranslate( 'Duration' )?>:</td><td>
 <?php 
   $dur_h = (int)( $duration / 60 );
   $dur_m = $duration - ( $dur_h * 60 );
   if ( $dur_h ==1 ) echo $dur_h . ' ' . translate( 'hour' ) . ' ';
   if ( $dur_h > 1 ) echo $dur_h . ' ' . translate( 'hours' ) . ' ';
   if ( $dur_m > 0 )echo $dur_m . ' ' . translate( 'minutes' )?>
</td></tr>
<?php }
if ( $DISABLE_PRIORITY_FIELD != 'Y' ) { ?>
<tr><td class="aligntop bold">
 <?php etranslate( 'Priority' )?>:</td><td>
 <?php echo $pri[$cal_priority]; ?>
</td></tr>
<?php }
if ( $DISABLE_ACCESS_FIELD != 'Y' ) { ?>
<tr><td class="aligntop bold">
 <?php etranslate( 'Access' )?>:</td><td>
 <?php echo ( $cal_access == "P"
   ? translate ( 'Public' )
   : ( $cal_access == 'C'
     ? ucfirst ( translate( 'confidential' ) )
     : ucfirst ( translate( 'private' ) ) ) ); ?>
</td></tr>
<?php }
if ( $CATEGORIES_ENABLED == 'Y' && ! empty ( $category ) ) { ?>
<tr><td class="aligntop bold">
 <?php etranslate( 'Category' )?>:</td><td>
 <?php echo $category; ?>
</td></tr>
<?php }

// Display who originally created event
// useful if assistant or Admin
$proxy_fullname = '';  
if ( !empty ( $DISPLAY_CREATED_BYPROXY ) && $DISPLAY_CREATED_BYPROXY == 'Y' ) {
  $res = dbi_execute ( 'SELECT cal_login ' .
    'FROM webcal_entry_log ' .
    'WHERE webcal_entry_log.cal_entry_id = ? ' .
    "AND webcal_entry_log.cal_type = 'C'" , array ( $id ) );
  if ( $res ) {
    $row3 = dbi_fetch_row ( $res ) ;
    if ( $row3 ) {
      user_load_variables ( $row3[0], 'proxy_' );
      $proxy_fullname = ($createby_fullname == $proxy_fullname ? ''  :
        ' ( ' . translate( 'by' ) . ' ' . $proxy_fullname . ' )');
    }
    dbi_free_result ( $res );
  }
}

if ( $single_user == 'N' && ! empty ( $createby_fullname )  ) {
  echo '<tr><td class="aligntop bold">' . 
 translate( 'Created by' ) . ":</td><td>\n";
  if ( $is_private  && ! access_is_enabled() ) {
    echo '[' . ucfirst ( translate( 'private' ) ) . "]\n</td></tr>";
  } else   if ( $is_confidential  && ! access_is_enabled() ) {
    echo '[' . ucfirst ( translate( 'confidential' ) ) . "]\n</td></tr>";
  } else {
    if ( access_is_enabled() ) 
      $can_email = access_user_calendar ( 'email', $create_by );
    if ( strlen ( $email_addr ) && $can_email != 'N' ) {
      echo "<a href=\"mailto:$email_addr?subject=$subject\">" .
        ( $row[0] == '__public__' ? 
        translate( 'Public Access' ): $createby_fullname ) .
        "</a>$proxy_fullname\n</td></tr>";
    } else {
      echo ( $row[0] == '__public__' ? 
        translate( 'Public Access' ) : $createby_fullname ) .
        "$proxy_fullname\n</td></tr>";
    }
  }
}
?>
<tr><td class="aligntop bold">
 <?php etranslate( 'Updated' )?>:</td><td>
 <?php
    if ( ! empty ( $GENERAL_USE_GMT ) && $GENERAL_USE_GMT == 'Y' ) {    
      echo date_to_str ( $mod_date ) . ' ' . 
        display_time ( $mod_date . $mod_time, 3 );
    } else {
      echo date_to_str ( date ('Ymd', date_to_epoch ( $mod_date . $mod_time ) ) ) . ' ' .
        display_time ( $mod_date . $mod_time, 2 );    
    }
   ?>
</td></tr>
<?php
//display the reminder info if found
if ( ! empty ( $reminder ) ) {
  echo '<tr><td class="aligntop bold">' .
      translate ( 'Send Reminder' ) . ":</td>\n";
  echo '<td>' . $reminder . "</td></tr>\n";
}

// load any site-specific fields and display them
$extras = get_site_extra_fields ( $id );
$site_extracnt = count ( $site_extras );
for ( $i = 0; $i < $site_extracnt; $i++ ) {
  $extra_name = $site_extras[$i][0];
  $extra_type = $site_extras[$i][2];
  $extra_arg1 = $site_extras[$i][3];
  $extra_arg2 = $site_extras[$i][4];
  if ( ! empty ( $extras[$extra_name]['cal_name'] ) ) {
    echo '<tr><td class="aligntop bold">' .
      translate ( $site_extras[$i][1] ) .
      ":</td><td>\n";
    if ( $extra_type == EXTRA_URL ) {
      if ( strlen ( $extras[$extra_name]['cal_data'] ) ) {
        echo '<a href="' . $extras[$extra_name]['cal_data'] . '">' .
          $extras[$extra_name]['cal_data'] . "</a>\n";
      }
    } else if ( $extra_type == EXTRA_EMAIL ) {
      if ( strlen ( $extras[$extra_name]['cal_data'] ) ) {
        echo '<a href="mailto:' . $extras[$extra_name]['cal_data'] .
          "?subject=$subject\">" .
          $extras[$extra_name]['cal_data'] . "</a>\n";
      }
    } else if ( $extra_type == EXTRA_DATE ) {
      if ( $extras[$extra_name]['cal_date'] > 0 ) {
        echo date_to_str ( $extras[$extra_name]['cal_date'] );
      }
    } else if ( $extra_type == EXTRA_TEXT ||
      $extra_type == EXTRA_MULTILINETEXT ) {
      echo nl2br ( $extras[$extra_name]['cal_data'] );
    } else if ( $extra_type == EXTRA_USER ) {
      echo $extras[$extra_name]['cal_data'];
    } else if ( $extra_type == EXTRA_SELECTLIST ) {
      echo $extras[$extra_name]['cal_data'];
    }
    echo "\n</td></tr>\n";
  }
}
 // participants
// Only ask for participants if we are multi-user.
$allmails = array ();
$show_participants = ( $DISABLE_PARTICIPANTS_FIELD != 'Y' );
if ( $is_admin ) {
  $show_participants = true;
}
if ( $PUBLIC_ACCESS == 'Y' && $login == '__public__' &&
  ( $PUBLIC_ACCESS_OTHERS != 'Y' || $PUBLIC_ACCESS_VIEW_PART == 'N' ) ) {
  $show_participants = false;
}
if ( $single_user == 'N' && $show_participants ) { ?>
  <tr><td class="aligntop bold">
  <?php etranslate( 'Participants' )?>:</td><td>
  <?php
  $num_app = $num_wait = $num_rej = 0;
  if ( $is_private && ! access_is_enabled() ) {
    echo '[' . ucfirst ( translate( 'private' ) ) . ']';
  } else   if ( $is_confidential  && ! access_is_enabled() ) {
    echo '[' . ucfirst ( translate( 'confidential' ) ) . ']';
  } else {
    $sql = 'SELECT cal_login, cal_status, cal_percent FROM webcal_entry_user ' .
      'WHERE cal_id = ?';
    if ( $eType == 'task' ) {
        $sql .= " AND cal_status IN ( 'A', 'W' )";
    }
    //echo "$sql\n";
    $res = dbi_execute ( $sql , array ( $id ) );
    $first = 1;
    if ( $res ) {
      while ( $row = dbi_fetch_row ( $res ) ) {
        $participants[] = $row;
        $pname = $row[0];
        if ( ( $login == $row[0] || access_user_calendar ( 'approve', $row[0] ) ||
          ( $is_nonuser_admin || $is_assistant && 
          ! empty ( $user ) && $user == $row[0] ) ) && 
          $row[1] == 'W' ) {
          $unapproved = TRUE;
        }
        if ( $row[1] == 'A' ) {
          $approved[$num_app++] = $pname;
        } else if ( $row[1] == 'W' ) {
          $waiting[$num_wait++] = $pname;
        } else if ( $row[1] == 'R' )  {
          $rejected[$num_rej++] = $pname;
        }
      }
      dbi_free_result ( $res );
    } else {
      db_error() . "<br />\n";
    }
  }
  if ( $eType == 'task' ) {
    echo '<table  border="1"  width="80%" cellspacing="0" cellpadding="\">' . "\n";
    echo '<th align="center">' .translate( 'Participants' ) . '</th>';
    echo '<th align="center" colspan="2">' . translate( 'Percentage Complete' ) . '</th>';
    $others_complete = 'yes';
    for ( $i = 0, $cnt = count ( $participants ); $i < $cnt; $i++ ) {
      user_load_variables ( $participants[$i][0], 'temp' );
      if ( access_is_enabled() ) 
        $can_email = access_user_calendar ( 'email', $templogin );
      $spacer = 100 - $participants[$i][2];
      $percentage = $participants[$i][2];
      if ( $participants[$i][0] == $login ) {
        $login_percentage = $participants[$i][2];
      } else {
        if ( $participants[$i][2] < 100 ) $others_complete = 'no';
      }
      echo '<tr><td width="30%">';
      if ( strlen ( $tempemail ) && $can_email != 'N') {
        echo '<a href="mailto:' . $tempemail . "?subject=$subject\">" .
          '&nbsp;' . $tempfullname . '</a>'; 
        $allmails[] = $tempemail;
      } else { 
        echo '&nbsp;' . $tempfullname; 
      }   
      echo "</td>\n";
      echo "<td width=\"5%\" align=\"center\">$percentage%</td>\n<td width=\"65%\">";
      echo "<img src=\"images/pix.gif\" width=\"$percentage%\" height=\"10\">";
      echo "<img src=\"images/spacer.gif\" width=\"$spacer\" height=\"10\">";
      echo "</td></tr>\n";
    }
    echo '</table>';
  
  } else {
  for ( $i = 0; $i < $num_app; $i++ ) {
    user_load_variables ( $approved[$i], 'temp' );
    if ( access_is_enabled() ) 
      $can_email = access_user_calendar ( 'email', $templogin );
    if ( strlen ( $tempemail ) > 0 && $can_email != 'N' ) {
      echo '<a href="mailto:' . $tempemail . "?subject=$subject\">" . 
        $tempfullname . "</a><br />\n";
      $allmails[] = $tempemail;
    } else {
      echo $tempfullname . "<br />\n";
    }
  }
  // show external users here...
  if ( ! empty ( $ALLOW_EXTERNAL_USERS ) && $ALLOW_EXTERNAL_USERS == 'Y' ) {
    $external_users = event_get_external_users ( $id, 1 );
    $ext_users = explode ( "\n", $external_users );
    if ( is_array ( $ext_users ) ) {
      for ( $i = 0, $cnt = count( $ext_users ); $i < $cnt; $i++ ) {
        if ( ! empty ( $ext_users[$i] ) ) {
          echo $ext_users[$i] . ' (' . translate( 'External User' ) . 
            ")<br />\n";
          if ( preg_match ( '/mailto:(\S+)"/', $ext_users[$i], $match ) ) {
            $allmails[] = $match[1];
          }
        }
      }
    }
  }
  for ( $i = 0; $i < $num_wait; $i++ ) {
    user_load_variables ( $waiting[$i], 'temp' );
    if ( access_is_enabled() ) 
      $can_email = access_user_calendar ( 'email', $templogin );
    if ( strlen ( $tempemail ) > 0 && $can_email != 'N'  ) {
      echo '<a href="mailto:' . $tempemail . "?subject=$subject\">" . 
        $tempfullname . "</a> (?)<br />\n";
      $allmails[] = $tempemail;
    } else {
      echo $tempfullname . " (?)<br />\n";
    }
  }
  for ( $i = 0; $i < $num_rej; $i++ ) {
    user_load_variables ( $rejected[$i], 'temp' );
    if ( access_is_enabled() ) 
      $can_email = access_user_calendar ( 'email', $templogin );
    if ( strlen ( $tempemail ) > 0 && $can_email != 'N'  ) {
      echo '<strike><a href="mailto:' . $tempemail .
        "?subject=$subject\">" . $tempfullname .
        '</a></strike> (' . translate( 'Rejected' ) . ")<br />\n";
    } else {
      echo "<strike>$tempfullname</strike> (" . 
        translate( 'Rejected' ) . ")<br />\n";
    }
  }
  }
?>
</td></tr>
<?php
 } // end participants
 
 $can_edit = ( $can_edit || $is_admin || $is_nonuser_admin && ($user == $create_by) || 
  ( $is_assistant && ! $is_private && ($user == $create_by) ) ||
  ( $readonly != 'Y' && ( $login == $create_by || $single_user == 'Y' ) ) );
   
 if ( $eType == 'task' ) {
 //allow user to update their task completion percentage
  if ( empty ( $user ) && $readonly != 'Y' && $is_my_event && 
    $login != '__public__' && ! $is_nonuser && 
   $event_status != 'D'  )  {
    echo "<tr><td class=\"aligntop bold\">\n";
    echo "<form action=\"view_entry.php?id=$id\" method=\"post\" name=\"setpercentage\">\n";
    echo "<input type=\"hidden\" name=\"others_complete\" value=\"$others_complete\" />\n";
    echo  translate ( 'Update Task Percentage' ) . 
     '</td><td><select name="upercent" id="task_percent">' . "\n";
    for ( $i=0; $i<=100 ; $i+=10 ){ 
      echo "<option value=\"$i\" " .  
        ($login_percentage == $i? ' selected="selected"':'') . ' >' . 
           $i . "</option>\n";
    }
    echo "</select>\n";
    echo '&nbsp;<input type="submit" value="' . translate( 'Update' ) . "\" />\n";
    echo "</form></td><tr>\n"; 
  }
}


if ( Doc::attachmentsEnabled () && $rss_view == false ) { ?>
  <tr><td class="aligntop bold">
  <?php etranslate( 'Attachments' )?>:</td><td>
  <?php
  $attList =& new AttachmentList ( $id );
  for ( $i = 0; $i < $attList->getSize(); $i++ ) {
    $a = $attList->getDoc ( $i );
    echo $a->getSummary ();
    // show delete link if user can delete
    if ( $is_admin || $login == $a->getLogin() ||
      user_is_assistant ( $login, $a->getLogin() ) ||
      $login == $create_by ||
      user_is_assistant ( $login, $create_by ) ) {
        echo ' [<a href="docdel.php?blid=' . $a->getId() .
          "\" onclick=\"return confirm('" .
          translate ( 'Are you sure you want to delete this entry?', true ) .
          "');\">" . translate ( 'Delete' ) . '</a>]';
    }
    echo "<br/>\n";
  }
  $num_attach = $attList->getSize();
  if ( $num_attach == 0 ) {
    echo translate( 'None' ) . '<br/>';
  }

  $num_app = $num_wait = $num_rej = 0;

  echo "</td></tr>\n";
}

if ( Doc::commentsEnabled () ) { ?>
  <tr><td class="aligntop bold">
  <?php etranslate( 'Comments' )?>:</td><td>
  <?php
  $comList =& new CommentList ( $id );
  $num_comment = $comList->getSize();
  $comment_text = '';
  for ( $i = 0; $i < $num_comment; $i++ ) {
    $cmt = $comList->getDoc ( $i );
    $comment_text .=
      '<strong>' . htmlspecialchars ( $cmt->getDescription() ) . '</strong> - ' .
      $cmt->getLogin() . " @ " .
      date_to_str ( $cmt->getModDate(), '', false, true ) .
      ' ' . display_time ( $cmt->getModTime() ) . "\n";
      // show delete link if user can delete
      if ( $is_admin || $login == $cmt->getLogin() ||
        user_is_assistant ( $login, $cmt->getLogin() ) ||
        $login == $create_by ||
        user_is_assistant ( $login, $create_by ) ) {
          $comment_text .= ' [<a href="docdel.php?blid=' . $cmt->getId() .
            "\" onclick=\"return confirm('" .
            translate ( 'Are you sure you want to delete this entry?', true ) .
            "');\">" . translate ( 'Delete' ) . '</a>]';
      }
      $comment_text .= "<br/>\n" .
        '<blockquote id="eventcomment">' . nl2br ( activate_urls (
        htmlspecialchars ( $cmt->getData () ) ) ) .
        "</blockquote>\n";
  }
  if ( $num_comment == 0 ) {
    echo translate( 'None' ) . '<br/>';
  } else {
    echo $num_comment . ' ' . translate ( 'comments' );
    echo '<input id="showbutton" type="button" value="' .
      translate( 'Show' ) . '" onclick="showComments();" />';
    echo '<input id="hidebutton" type="button" value="' .
      translate( 'Hide' ) . '" onclick="hideComments();" />';
    echo '<br/><div id="comtext">' . $comment_text . "</div>\n";
    // We could put the following JS in includes/js/view_entry.php,
    // but we won't need it in many cases and we don't know whether
    // we need until after would need to include it.  So, we
    // will include it here instead.
    ?>
<script language="JavaScript" type="text/javascript">
<!-- <![CDATA[
function showComments () {
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
function hideComments () {
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
hideComments ();
//]]> -->
</script>
    <?php
  }
    
  $num_app = $num_wait = $num_rej = 0;

  ?>
  </td></tr>
<?php } ?>

</table>

<br />

<?php 
$rdate = '';
if ( $event_repeats ) {
  $rdate = "&amp;date=$event_date";
}

// Show a printer-friendly link
if ( empty ( $friendly ) ) {
  echo $printerStr;
}

if ( empty ( $event_status ) ) {
  // this only happens when an admin views a deleted event that he is
  // not a participant for.  Set to $event_status to "D" just to get
  // rid of all the edit/delete links below.
  $event_status = 'D';
}

if ( ! empty ( $user ) && $login != $user ) {
  $u_url = "&amp;user=$user";
} else {
  $u_url = '';
}

if ( ( $is_my_event || $is_nonuser_admin || $is_assistant || $can_approve ) && 
  $unapproved && $readonly == 'N' ) {
  echo '<a title="' . 
    translate( 'Approve/Confirm entry' ) . 
    "\" class=\"nav\" href=\"approve_entry.php?id=$id$u_url&amp;type=E\" " .
    "onclick=\"return confirm('" . 
    translate( 'Approve this entry?', true) . "');\">" . 
    translate( 'Approve/Confirm entry' ) . "</a><br />\n";
  echo '<a title="' . 
    translate( 'Reject entry' ) .
    "\"  class=\"nav\" href=\"reject_entry.php?id=$id$u_url&amp;type=E\" " .
    "onclick=\"return confirm('" .
    translate( 'Reject this entry?', true) . "');\">" . 
    translate( 'Reject entry') . "</a><br />\n";
}


$can_add_attach = false;
if ( Doc::attachmentsEnabled () ) {
  if ( $can_edit )
    $can_add_attach = true;
  else if ( $is_my_event && $ALLOW_ATTACH_PART == 'Y' )
    $can_add_attach = true;
  else if ( $ALLOW_ATTACH_ANY == 'Y' )
    $can_add_attach = true;
}
  
$can_add_comment = false;
if ( Doc::commentsEnabled () ) {
  if ( $can_edit )
    $can_add_comment = true;
  else if ( $is_my_event && $ALLOW_COMMENTS_PART == 'Y' )
    $can_add_comment = true;
  else if ( $ALLOW_COMMENTS_ANY == 'Y' )
    $can_add_comment = true;
}
  
if ( $can_add_attach ) {
  echo '<a title="' . translate( 'Add Attachment' ) .
    "\" class=\"nav\" href=\"docadd.php?type=A&amp;id=$id" . 
    ( $login != $user? "&amp;user=$user": '')  . '">' .
  translate ( 'Add Attachment' ) . "</a><br/>\n";
}

if ( $can_add_comment ) {
  echo '<a title="' . translate( 'Add Comment' ) .
    "\" class=\"nav\" href=\"docadd.php?type=C&amp;id=$id" . 
    ( $login != $user? "&amp;user=$user":'')  . '">' .
  translate ( 'Add Comment' ) . "</a><br/>\n";
}

// If approved, but event category not set (and user does not have permission
// to edit where they could also set the category), then allow them to
// set it through set_cat.php.
if ( empty ( $user ) && $CATEGORIES_ENABLED == 'Y' &&
  $readonly != 'Y' && $is_my_event && $login != '__public__' &&
  ! $is_nonuser && $event_status != 'D' && ! $can_edit )  {
  echo '<a title="' . 
    translate( 'Set category' ) . "\" class=\"nav\" " .
    "href=\"set_entry_cat.php?id=$id$rdate\">" .
    translate( 'Set category' ) . "</a><br />\n";
}

if ( $can_edit && $event_status != 'D' && ! $is_nonuser
  && $readonly != 'Y' ) {
  if ( $event_repeats ) {
    echo '<a title="' .
      translate( 'Edit repeating entry for all dates' ) . 
      "\" class=\"nav\" href=\"edit_entry.php?id=$id$u_url\">" . 
      translate( 'Edit repeating entry for all dates' ) . "</a><br />\n";
    // Don't allow override of first event
    if ( ! empty ( $date ) && $date != $orig_date ) {
      echo '<a title="' .
        translate( 'Edit entry for this date' ) . '" class="nav" ' . 
        "href=\"edit_entry.php?id=$id$u_url$rdate&amp;override=1\">" .
        translate( 'Edit entry for this date' ) . "</a><br />\n";
    }
    echo '<a title="' . 
      translate( 'Delete repeating event for all dates' ) . 
      "\" class=\"nav\" href=\"del_entry.php?id=$id$u_url&amp;override=1\" " .
      "onclick=\"return confirm('" . 
      translate( 'Are you sure you want to delete this entry?', true) . "\\n\\n" . 
      translate( 'This will delete this entry for all users.', true) . "');\">" . 
      translate( 'Delete repeating event for all dates' ) . "</a><br />\n";
    // Don't allow deletion of first event
    if ( ! empty ( $date ) && $date != $orig_date ) {
      echo '<a title="' . 
        translate( 'Delete entry only for this date' ) . 
        "\" class=\"nav\" href=\"del_entry.php?id=$id$u_url$rdate&amp;override=1\" " .
        "onclick=\"return confirm('" .
        translate( 'Are you sure you want to delete this entry?', true) . "\\n\\n" . 
        translate( 'This will delete this entry for all users.', true) . "');\">" . 
        translate( 'Delete entry only for this date' ) . "</a><br />\n";
    }
  } else {
    echo '<a title="' .
      translate( 'Edit entry' ) . '" class="nav" ' .
      "href=\"edit_entry.php?id=$id$u_url\">" .
      translate( 'Edit entry' ) . "</a><br />\n";
    echo '<a title="' . 
      translate( 'Delete entry' ) . '" class="nav" ' .
      "href=\"del_entry.php?id=$id$u_url$rdate\" onclick=\"return confirm('" . 
       translate( 'Are you sure you want to delete this entry?', true) . "\\n\\n";
    if ( empty ( $user ) || $user == $login || $is_assistant )
      echo translate( 'This will delete this entry for all users.' , true);
    echo "');\">" .  translate( 'Delete entry' );
    if ( ! empty ( $user ) &&  $user != $login  && ! $is_assistant ) {
      user_load_variables ( $user, 'temp_' );
      echo ' ' . translate ( 'from calendar of' ) . ' ' . $temp_fullname;
    }
    echo "</a><br />\n";
  }
  echo '<a title="' . 
    translate( 'Copy entry' ) . '" class="nav" ' .
    "href=\"edit_entry.php?id=$id$u_url&amp;copy=1\">" . 
    translate( 'Copy entry' ) . "</a><br />\n";  
} elseif ( $readonly != 'Y' && ( $is_my_event || $is_nonuser_admin || 
  $can_edit ) && $login != '__public__' &&
  ! $is_nonuser && $event_status != 'D' )  {
  echo '<a title="' . 
    translate( 'Delete entry' ) . '" class="nav" ' .
    "href=\"del_entry.php?id=$id$u_url$rdate\" onclick=\"return confirm('" . 
    translate( 'Are you sure you want to delete this entry?', true) . "\\n\\n";
  if ( $is_assistant ) {
    echo translate( "This will delete the entry from your boss\' calendar.", true) . 
      "');\">";
  } else {
    echo translate( 'This will delete the entry from your calendar.', true) . 
      "');\">";
  }
  echo translate( 'Delete entry' );
  if ( $is_assistant ) {
    echo  ' ' . translate ( "from your boss' calendar" );
  }
  echo "</a><br />\n";
  echo '<a title="' . 
    translate( 'Copy entry' ) . '" class="nav" ' .
    "href=\"edit_entry.php?id=$id&amp;copy=1\">" . 
    translate( 'Copy entry' ) . "</a><br />\n";
}
if ( $readonly != 'Y' && ! $is_my_event && ! $is_private && ! $is_confidential &&
  $event_status != 'D' && $login != '__public__' && ! $is_nonuser )  {
  echo '<a title="' . 
    translate( 'Add to My Calendar' ) . '" class="nav" ' .
    "href=\"add_entry.php?id=$id\" onclick=\"return confirm('" . 
    translate( 'Do you want to add this entry to your calendar?', true) . "\\n\\n" . 
    translate( 'This will add the entry to your calendar.', true) . "');\">" . 
    translate( 'Add to My Calendar' ) . "</a><br />\n";
}

if ( $login != '__public__' && count ( $allmails ) > 0 ) {
  echo '<a title="' . 
    translate( 'Email all participants' ) . '" class="nav" ' .
    'href="mailto:' . implode ( ',', $allmails ) .
    '?subject=' . rawurlencode($subject) . '">' . 
    translate( 'Email all participants' ) . "</a><br />\n";
}

$can_show_log = $is_admin; // default if access control is not enabled
if ( access_is_enabled () ) {
  $can_show_log = access_can_access_function ( ACCESS_ACTIVITY_LOG );
}

if ( $can_show_log ) {
  if ( ! $show_log ) {
    echo '<a title="' . 
      translate( 'Show activity log' ) . '" class="nav" ' .
      "href=\"view_entry.php?id=$id&amp;log=1\">" . 
      translate( 'Show activity log' ) . "</a><br />\n";
  } else {
    echo '<a title="' . 
      translate( 'Hide activity log' ) . '" class="nav" ' .
      "href=\"view_entry.php?id=$id\">" . 
       translate( 'Hide activity log' ) . "</a><br />\n";
  }
}

if ( $can_show_log && $show_log ) {
  echo '<h3>' . translate( 'Activity Log' ) . "</h3>\n";
  echo '<table class="embactlog">' .  "\n";
  echo '<tr><th class="usr">' . "\n";
  echo translate( 'User' ) . '</th><th class="cal">' . "\n";
  echo translate( 'Calendar' ) . '</th><th class="date">' . "\n";
  echo translate( 'Date' ) . '/' . 
   translate( 'Time' ) . '</th><th class="action">' . "\n";
  echo translate( 'Action' ) . "\n</th></tr>\n";

  $res = dbi_execute ( 'SELECT cal_login, cal_user_cal, cal_type, ' .
    'cal_date, cal_time, cal_text ' .
    'FROM webcal_entry_log WHERE cal_entry_id = ? ' .
    'ORDER BY cal_log_id DESC' , array ( $id ) );
  if ( $res ) {
    while ( $row = dbi_fetch_row ( $res ) ) {
      echo "<tr><td valign=\"top\">\n";
      echo $row[0] . "</td><td valign=\"top\">\n";
      echo $row[1] . "</td><td valign=\"top\">\n" . 
        date_to_str ( $row[3] ) . '&nbsp;' ;
      $use_gmt = ( ! empty ( $GENERAL_USE_GMT ) && $GENERAL_USE_GMT == 'Y'? 3 : 2 );    
      echo display_time ( $row[3] . $row[4], $use_gmt ) ;
      echo "</td><td valign=\"top\">\n";
      echo display_activity_log ( $row[2], $row[5] );
      echo "</td></tr>\n";
    }
    dbi_free_result ( $res );
  }
  echo "</table>\n";
}

if ( access_can_access_function ( ACCESS_EXPORT ) && 
   (( ! $is_private  && ! $is_confidential )  || 
   ! access_is_enabled() )  && ! $hide_details ) {
   $exportThisStr = translate( 'Export this entry to' );
   $palmStr = translate ( 'Palm Pilot' );
   $exportStr = translate ( 'Export' );
   $selectStr = generate_export_select ( );
   echo <<<EOT
   <br />
   <form method="post" name="exportform" action="export_handler.php">
     <label for="exformat">{$exportThisStr}:&nbsp;</label>
     {$selectStr}
     <input type="hidden" name="id" value="{$id}" />
     <input type="submit" value="{$exportStr}" />
   </form>
EOT;
}

echo print_trailer ( empty ($friendly) );?>

