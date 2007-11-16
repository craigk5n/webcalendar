<?php
/* $Id$
 *
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
include 'includes/classes/Doc.class.php';
include 'includes/classes/DocList.class.php';
include 'includes/classes/AttachmentList.class.php';
include 'includes/classes/CommentList.class.php';

//Load Activity Log functions if allowed
if ( access_can_access_function ( ACCESS_ACTIVITY_LOG ) ) {
	$PAGE_SIZE = 100; //We should never have to generate Prev/Next links
  include_once 'includes/activity_log.php';
}
	
// Make sure this user is allowed to look at this calendar.
$can_approve = $can_edit = $can_view = false;
$is_my_event = $hide_details = false; // Is this user owner or participant?
$rss_view = $unapproved = false;
$error = $event_status = '';
$can_email = 'Y';
$eType = 'event';

$pri[1] = translate ( 'High' );
$pri[2] = translate ( 'Medium' );
$pri[3] = translate ( 'Low' );

if ( ! $eid = $WC->getId() )
  $error = translate ( 'Invalid entry id.' );

$date = $WC->getGET ( 'date' );
$smarty->assign ( 'eid', $eid );

// If sent here from an email and not logged in, save URI and redirect to login.
if ( empty ( $error ) ) {
  $em = $WC->getGET ( 'em' );
  if ( ! empty ( $em ) && ! $WC->loginId() ) {
    remember_this_view ();
    do_redirect ( 'login.php' );
  }
}

// Check if we can display basic info for RSS FEED
$rssuser = $WC->getGET ( 'rssuser' );
if ( ! empty ( $rssuser ) ) {
  $user_rss_enabled = getPref ( 'USER_RSS_ENABLED', 1, $rssuser );
  $user_rss_timezone = getPref ( 'TIMEZONE', 1, $rssuser );
  $rss_view = ( getPref ( 'RSS_ENABLED', 2 ) && $user_rss_enabled == 'Y' &&
    $friendly == 1 && ! empty ( $rssuser ) );
  if ( $rss_view == true ) {
    $hide_details = false;
    // Make sure the displayed time is accurate.
    set_env ( 'TZ', $user_rss_timezone );
  }
}


// Load event info now.
$item = loadEvent ( $eid  );
if ( ! $item )
  $error = str_replace ('XXX', $eid, translate ( 'Invalid entry id XXX' ) ) ;
else {
  $eType = $item->getCalTypeName ();
  if ( $eType == 'task' ) 
    $cal_completed = $item->getCompleted ();
  if ( $hide_details ) {
    $overrideStr = translate ( $OVERRIDE_PUBLIC_TEXT );
    $name = $overrideStr;
    $description = $overrideStr;
    if ( ! empty ( $row[11] ) )
      $location = $overrideStr;
    if ( ! empty ( $row[12] ) )
      $url = $overrideStr;
  } else {
	  //???
  }
}

if ( empty ( $error ) ) {
  // don't shift date if All Day or Untimed
  $display_date = $item->getDate ();

  if ( ! empty ( $year ) )
    $thisyear = $year;

  if ( ! empty ( $month ) )
    $thismonth = $month;

  // Is this user a participant or the creator of the event?
  $is_my_event = $item->isMine ( $WC->userLoginId() );

  // check UAC
  $euser = ( empty ( $user ) ? ( $is_my_event ? 
    $WC->loginId() : $item->getOwner () ) : $user );
  $time_only = 'N';

  $is_assistant = access_user_calendar ( 'assistant', $euser, 
    $WC->loginId(), $item->getCalType (), $item->getCalType () );
  $can_approve = access_user_calendar ( 'approve', $euser, 
    $WC->loginId(), $item->getCalType (), $item->getCalType () );
  $can_edit = access_user_calendar ( 'edit', $item->getOwner (), 
    $WC->loginId(), $item->getCalType (), $item->getCalType () );
  $can_view = access_user_calendar ( 'view', $euser, 
    $WC->loginId(), $item->getCalType (), $item->getCalType () );
  $time_only = access_user_calendar ( 'time', $euser, 
    $WC->loginId(), $item->getCalType (), $item->getCalType () ); 
	
	
  if ( ! $can_view ) {
    // if not a participant in the event, must be allowed to look at
    // other user's calendar.
    $check_group =  'Y';
    // If $check_group is true, it means this user can look at the event only if
    // they are in the same group as some of the people in the event. This gets
    // kind of tricky. If there is a participant from a different group, do we
    // still show it? For now, the answer is no. This could be configurable
    // somehow, but how many lines of text would it need in the admin page to
    // describe this scenario? Would confuse 99.9% of users.
    // In summary, make sure at least one event participant is in one of
    // this user's groups.
    $my_users = get_my_users ();
    $my_usercnt = count ( $my_users );
    if ( is_array ( $my_users ) && $my_usercnt ) {
      $sql_params = array ();
      $sql = 'SELECT we.cal_id FROM webcal_entry we, webcal_entry_user weu
        WHERE we.cal_id = weu.cal_id AND we.cal_id = ? AND weu.cal_login_id IN ( ';
      $sql_params[] = $eid;
      for ( $i = 0; $i < $my_usercnt; $i++ ) {
        $sql .= ( $i > 0 ? ', ' : '' ) . '?';
        $sql_params[] = $my_users[$i]['cal_login_id'];
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
    if ( ! $check_group  )
      $can_view = false;
  }
} //end $error test

// Update the task percentage for this user.
$canUpdatePercentage = ( empty ( $user ) && $readonly != 'Y' && $is_my_event &&
      ( ! $WC->isNonUser ( ) && $event_status != 'D' );
if ( ! empty ( $_POST ) && $canUpdatePercentage ) {
  $upercent = $WC->getPOST ( 'upercent' );
  if ( $upercent >= 0 && $upercent <= 100 ) {
    dbi_execute ( 'UPDATE webcal_entry_user SET cal_percent = ?
      WHERE cal_login_id = ? AND cal_id = ?',
      array ( $upercent, $WC->loginId(), $eid ) );
    activity_log ( $eid, $WC->loginId(), $creator, LOG_UPDATE_T, 
      translate ( 'Update Task Percentage' ) . ' ' . $upercent . '%' );
   }
  // Check if all other user percent is 100%, if so, set cal_complete date.
  $others_complete = $WC->getPOST ( 'others_complete' );
  if ( $upercent == 100 && $others_complete == 'yes' ) {
    dbi_execute ( 'UPDATE webcal_entry SET cal_completed = ?
      WHERE cal_id = ?', array ( gmdate ( 'Ymd', time () ), $eid ) );
    activity_log ( $eid, $WC->loginId(), $creator, LOG_UPDATE_T, 
      translate ( 'Completed' ) );
  }
}

build_header ();

if ( ! empty ( $error ) ) {
  $smarty->assign ( 'errorStr', $error );
  $smarty->display ( 'error.tpl' );
  exit;
}

if ( $WC->isUser() ) {
  // If viewing another user's calendar, check the status of the
  // event on their calendar (to see if it's deleted).
  $res = dbi_execute ( 'SELECT cal_status FROM webcal_entry_user
    WHERE cal_login_id = ? AND cal_id = ?', array ( $user, $eid ) );
  if ( $res ) {
    if ( $row = dbi_fetch_row ( $res ) )
      $event_status = $row[0];

    dbi_free_result ( $res );
  }
} else {
  // We are viewing event on user's own calendar, so check the
  // status on their own calendar.
  $res = dbi_execute ( 'SELECT cal_id, cal_status FROM webcal_entry_user
    WHERE cal_login_id = ? AND cal_id = ?', array ( $WC->loginId(), $eid ) );
  if ( $res ) {
    $row = dbi_fetch_row ( $res );
    $event_status = $row[1];
    dbi_free_result ( $res );
  }
}


// If we have no event status yet, it must have been deleted.
if ( ( empty ( $event_status ) && ! $WC->isAdmin() ) ||
  ( ! $can_view && empty ( $rss_view ) ) ) {
	$smarty->assign ( 'not_auth', true );
  $smarty->display ( 'error.tpl' );
  exit;
}

// $subject is used for mailto URLs.
$subject = generate_application_name () . ': ' . $item->getname ();
// Remove the '"' character since it causes some mailers to barf
$subject = str_replace ( ' "', '', $subject );
$subject = htmlspecialchars ( $subject );
$smarty->assign ( 'subject', $subject );

$event_repeats = $item->isRepeat ();

/* calculate end time */
$end_str = ( $item->getDuration () > 0
  ? '-' . display_time ( $item->getDate () + ( $item->getDuration () * 60 ) )
  : '' );

// get the email adress of the creator of the entry
$createby = $WC->User->loadVariables ( $item->getOwner () );
$email_addr = empty ( $createby['email'] ) ? '' : $createby['email'];


$event_date = ( $event_repeats && ! empty ( $date ) ? $date : $item->getDate ( 'Ymd' ) );

// Get category Info
if ( getPref ( 'CATEGORIES_ENABLED' ) ) {
  $categories = get_categories_by_eid ( $eid,
    ( ( ! empty ( $user ) && strlen ( $user ) ) && $WC->isAdmin() 
      ? $user : $WC->loginId() ), true );
  $smarty->assign ( 'itemCategory', implode ( ', ', $categories ) );
}

// get reminders
$smarty->assign ('reminder', getReminders ( $eid, true ) );


if ( getPref ( 'ALLOW_HTML_DESCRIPTION' ) ) {
  $str = $item->getDescription ();
  // $str = str_replace ( '&', '&amp;', $item->getDescription () );
  $str = str_replace ( '&amp;amp;', '&amp;', $str );
  // If there is no HTML found, then go ahead and replace
  // the line breaks ("\n") with the HTML break.
  $description = ( strstr ( $str, '<' ) && strstr ( $str, '>' )
    ? $str // found some html...
    : nl2br ( activate_urls ( $str ) ) );
} else
  $description = nl2br ( activate_urls ( htmlspecialchars ( $item->getDescription () ) ) );
$smarty->assign ( 'description', $description );
	
$smarty->assign ( 'location', $item->getLocation () );

$smarty->assign ( 'url', activate_urls (  $item->getUrl () ) );


if ( $event_status != 'A' && ! empty ( $event_status ) ) {
  if ( $event_status == 'D' )
    $status = ( $eType == 'task'
      ? translate ( 'Declined' ) : translate ( 'Deleted' ) );
  elseif ( $event_status == 'R' )
    $status = translate ( 'Rejected' );
  elseif ( $event_status == 'W' )
    $status = ( $eType == 'task'
      ? translate ( 'Needs-Action' ) : translate ( 'Waiting for approval' ) );

  $smarty->assign ( 'status', $status );
}

$smarty->assign ( 'eType', $eType );
$smarty->assign ( 'eType_label', '(' . $eType .')' );

$smarty->assign ( 'display_date', $event_date );

$smarty->assign ( 'itemDate', $item->getDate () );

$smarty->assign ( 'itemDueDate', $item->getDueDate () );

$smarty->assign ( 'itemCompleted', $item->getCompleted () );

if ( $item->isRepeat () ) {
  $smarty->assign ('event_repeats', true );
}

$smarty->assign ('recurrenceStr', export_recurrence_ical ( $eid, true ) );

$smarty->assign ('timeStr', ( $item->isAllDay() ? translate ( 'All day event' )
    : display_time ( $item->getDate (), ( empty ( $end_str ) ? 2 : 0 ) ) . $end_str ) );
 
if ( $item->getDuration () > 0 && $item->getDuration () != 1440 ) {
  $dur_h = intval ( $item->getDuration () / 60 );
  $dur_m = $item->getDuration () - ( $dur_h * 60 );
  $smarty->assign ('durationStr', ( $dur_h > 0 ? $dur_h . ' ' . translate ( 'hour'
       . ( $dur_h == 1 ? '' : 's' ) ) . ' ' : '' )
   . ( $dur_m > 0 ? $dur_m . ' ' . translate ( 'minutes' ) : '' ) );
}

if ( ! getPref ( 'DISABLE_PRIORITY_FIELD' ) ){
 $smarty->assign ( 'itemPriority', $item->getPriority () . '-' . $pri[ceil($item->getPriority ()/3)] );
}			
			
if ( ! getPref ( 'DISABLE_ACCESS_FIELD' ) ) {
 $smarty->assign ( 'itemAccess', $item->getAccessName() );
}

// Display who originally created event
// useful if assistant or Admin
if ( getPref ( 'DISPLAY_CREATED_BYPROXY' ) ) {
  $res = dbi_execute ( 'SELECT cal_login_id FROM webcal_entry_log
    WHERE webcal_entry_log.cal_entry_id = ? AND webcal_entry_log.cal_type = \'C\'',
    array ( $eid ) );
  if ( $res ) {
    $row3 = dbi_fetch_row ( $res );
    if ( $row3 ) {
      $proxy = $WC->User->loadVariables ( $row3[0] );
      $smarty->assign ( 'proxy_fullname', ( $createby['fullname'] == $proxy['fullname']
        ? '' : ' ( ' . translate ( 'by' ) . ' ' . $proxy['fullname'] . ' )' ) );
    }
    dbi_free_result ( $res );
  }
}

if ( ! _WC_SINGLE_USER && ! empty ( $createby['fullname'] ) ) {
    $smarty->assign ( 'can_email', 
		  access_user_calendar ( 'email', $item->getOwner () ) );

    $smarty->assign ( 'pubAccStr', ( $row[0] == '__public__'
      ? translate ( 'Public Access' ) : $createby['fullname'] ) );
}

$smarty->assign ( 'itemModDate', display_time ( $item->getModDate (), getPref ( 'GENERAL_USE_GMT' ) ?3 : 2 ) );
 


// load any site-specific fields and display them
//print_r ( get_site_extra_fields ( $eid ) );
$smarty->assign ( 'extras', get_site_extra_fields ( $eid )) ;
$smarty->assign ( 'site_extras', $site_extras );

// participants
// Only ask for participants if we are multi-user.
$allmails = $approved = $rejected = $waiting = array ();
$show_participants = ( ! getPref ( 'DISABLE_PARTICIPANTS_FIELD' ) );
if ( $WC->isAdmin() )
  $show_participants = true;
$smarty->assign ( 'show_participants', $show_participants );

if ( ! _WC_SINGLE_USER && $show_participants ) {


  $num_app = $num_rej = $num_wait = 0;

  $res = dbi_execute ( 'SELECT cal_login_id, cal_status, cal_percent
        FROM webcal_entry_user WHERE cal_id = ?'
       . ( $eType == 'task' ? ' AND cal_status IN ( \'A\', \'W\' )' : '' ),
      array ( $eid ) );
    $first = 1;
    if ( $res ) {
      while ( $row = dbi_fetch_row ( $res ) ) {
        $participants[] = $row;
        if ( ( $WC->isLogin( $row[0] ) ||
            access_user_calendar ( 'approve', $row[0] ) ||
              ( $WC->isNonuserAdmin() && !
                empty ( $user ) && $user == $row[0] ) ) && $row[1] == 'W' )
          $unapproved = true;

				$temp = $WC->User->loadVariables ( $row[0] );
        $can_email = access_user_calendar ( 'email', $temp['login_id'] );
        if ( $row[1] == 'A' ) {
					$approved[$row[0]]['fullname'] = $temp['fullname'];
			    if ( $can_email )
			      $approved[$row[0]]['email'] = $temp['email'];
        } elseif ( $row[1] == 'R' ) {
					$rejected[$row[0]]['fullname'] = $temp['fullname'];
			    if ( $can_email )
			      $rejected[$row[0]]['email'] = $temp['email'];
        } elseif ( $row[1] == 'W' ) {
					$waiting[$row[0]]['fullname'] = $temp['fullname'];
			    if ( $can_email )
			      $waiting[$row[0]]['email'] = $temp['email'];
				}
      }
      dbi_free_result ( $res );
    } else
      db_error () . '<br />';
  }
  if ( $eType == 'task' ) {
    $others_complete = 'yes';
    for ( $i = 0, $cnt = count ( $participants ); $i < $cnt; $i++ ) {
      $temp = $WC->User->loadVariables ( $participants[$i][0] );
      $can_email = access_user_calendar ( 'email', $temp['login_id'] );
      if ( $can_email && ! empty ( $temp['email'] ) )
			  $percentage[$i]['email'] = $temp['email'];
				$percentage[$i]['fullname'] = $temp['fullname'];
        $percentage[$i]['percentage'] = $participants[$i][2];
				$percentage[$i]['spacer'] = 100 - $participants[$i][2];
      if ( $WC->isLogin( $participants[$i][0] ) )
        $login_percentage = $participants[$i][2];
      else
      if ( $participants[$i][2] < 100 )
        $others_complete = 'no';
    }
		$smarty->assign ( 'percentage', $percentage );
    $smarty->assign ( 'others_complete', $others_complete );
		$smarty->assign ( 'canUpdatePercentage', $canUpdatePercentage );
  } else {
		$smarty->assign ( 'approved', $approved );
	  $smarty->assign ( 'rejected', $rejected );
	  $smarty->assign ( 'waiting', $waiting );
    // show external users here...
    if ( getPref ( 'ALLOW_EXTERNAL_USERS' ) ) {
      $external_users = event_get_external_users ( $eid, 1 );
			if ( ! empty ( $external_users ) ) 
        $smarty->assign ( 'ext_users', explode ( "\n", $external_users ) );
    }

} // end participants

if ( empty ( $event_status ) ) {
  // this only happens when an admin views a deleted event that he is
  // not a participant for.  Set to $event_status to "D" just to get
  // rid of all the edit/delete links below.
  $event_status = 'D';
}

if ( Doc::attachmentsEnabled () && $rss_view == false ) {
  $attList =& new AttachmentList ( $eid );
  for ( $i = 0; $i < $attList->getSize (); $i++ ) {
    $a = $attList->getDoc ( $i );
     $a->getSummary ();
     $a->getId ();
  }
  $num_attach = $attList->getSize ();

}

if ( Doc::commentsEnabled () ) {

  $comList =& new CommentList ( $eid );
  $num_comment = $comList->getSize ();
  $comment_text = '';
  for ( $i = 0; $i < $num_comment; $i++ ) {
    $cmt = $comList->getDoc ( $i );
    $comment_text .= '
          <strong>' . htmlspecialchars ( $cmt->getDescription () )
     . '</strong> - ' . $cmt->getLogin () . ' @ '
     . date_to_str ( $cmt->getModDate (), '', false, true )
    // show delete link if user can delete
    . ( $can_edit ? 
	   ' [<a href="docdel.php?blid='
       . $cmt->getId () . '" onclick="return confirm(\'' . $areYouSureStr
       . '\');">' . translate ( 'Delete' ) . '</a>]' : '' )// end show delete link
     . '<br />
          <blockquote id="eventcomment">' . nl2br ( activate_urls (
        htmlspecialchars ( $cmt->getData () ) ) ) . '</blockquote>';
  }
	$smarty->assign ( 'comment_text', $comment_text );
}


$smarty->assign ( 'rdate', ( $event_repeats ? '&amp;date=' . $event_date : '' ) );

$smarty->assign ( 'u_url', $WC->getUserUrl() );


if ( ( $is_my_event || $WC->isNonuserAdmin() || $can_approve ) &&
    ( $unapproved ) && ! _WC_READONLY ) {
  $smarty->assign ( 'can_approve', true );
}

// TODO add these permissions to the UAC list
$smarty->assign ( 'can_add_attach', ( Doc::attachmentsEnabled () &&
  $can_edit && ( $is_my_event && getPref ( 'ALLOW_ATTACH_PART' ) ) ||
  ( getPref ( 'ALLOW_ATTACH_ANY' ) && $event_status != 'D' ) ) );

$smarty->assign ( 'can_add_comment', ( Doc::commentsEnabled () &&
  $can_edit && ( $is_my_event && getPref ( 'ALLOW_COMMENTS_PART' ) ) ||
  ( getPref ( 'ALLOW_COMMENTS_ANY' ) && $event_status != 'D' ) ) );





// If approved, but event category not set (and user does not have permission
// to edit where they could also set the category), then allow them to
// set it through set_cat.php.
if ( empty ( $user ) && getPref ( 'CATEGORIES_ENABLED' ) && !_WC_READONLY &&
    $is_my_event && ! $WC->isLogin( '__public__' ) && !
    $is_nonuser && $event_status != 'D' && ! $can_edit ) {
  $smarty->assign ( 'setCategory', true );
}


//TODO Don't show if $user != $login and not assistant
// This will be easier with UAC always on
if ( $can_edit && $event_status != 'D' && ! $is_nonuser && ! _WC_READONLY ) {
  $smarty->assign ( 'can_edit', true );

} elseif ( ! _WC_READONLY &&
  ( $is_my_event || $WC->isNonuserAdmin() || $can_edit ) &&
    ! $is_nonuser && $event_status != 'D' ) {
   $smarty->assign ( 'can_edit', true );
}

if ( ! _WC_READONLY && ! $is_my_event && $event_status != 'D' &&  ! $is_nonuser )
  $smarty->assign ( 'addToMyCal', true );

if ( ! $WC->login( '__public__' ) && count ( $allmails ) > 0 )
  $smarty->assign ( 'emailAll', true );

if ( access_can_access_function ( ACCESS_ACTIVITY_LOG ) ) {
  $smarty->assign ( 'can_show_log', true );
  $smarty->assign ( 'show_log', $WC->getGET ( 'log' ) );
}

if ( access_can_access_function ( ACCESS_EXPORT ) && ! $hide_details ) {
  $smarty->assign ( 'allowExport', true );
}

$smarty->display ( 'view_entry.tpl' );

?>
