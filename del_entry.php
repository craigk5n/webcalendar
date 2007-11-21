<?php
/* $Id$ */
include_once 'includes/init.php';
require ( 'includes/classes/WebCalMailer.class.php' );
$mail = new WebCalMailer;


$my_event = false;
$can_edit = false;
$other_user = '';
$eid = $WC->getGET ( 'eid' );
$date = $WC->getGET ( 'date' );
$override = $WC->getGET ( 'override' );
// First, check to see if this user should be able to delete this event.
if ( $eid > 0 ) {
  // first see who has access to edit this entry
  if ( $WC->isAdmin() ) {
    $can_edit = true;
  } else if ( _WC_READONLY ) {
    $can_edit = false;
  } else {
    $can_edit = false;
  }
    // TODO if assistant is doing this, then we need to switch login
    // to user in the sql
    $query_params = array();
    $query_params[] = $eid;
    $sqlparm = $WC->userLoginId();
    $sql = 'SELECT we.cal_id, we.cal_type FROM webcal_entry we, webcal_entry_user weu 
      WHERE we.cal_id = weu.cal_id AND we.cal_id = ? ';
    if ( ! $WC->isAdmin() ) {
      $sql .= ' AND (we.cal_create_by = ? OR weu.cal_login_id = ? )';
      $query_params[] = $sqlparm;
      $query_params[] = $sqlparm;
     }
    $res = dbi_execute ( $sql, $query_params );
    if ( $res ) {
      $row = dbi_fetch_row ( $res );
      if ( $row && $row[0] > 0 )
        $can_edit = true;
      $activity_type = $row[1];
      dbi_free_result ( $res );
    }
}
if ( $activity_type =='E' || $activity_type == 'M' ) {
  $log_delete = LOG_DELETE;
  $log_reject = LOG_REJECT;
} else {
  $log_delete = LOG_DELETE_T;
  $log_reject = LOG_REJECT_T;
}
// See who owns the event. Owner should be able to delete.
$res = dbi_execute (
  'SELECT cal_create_by FROM webcal_entry WHERE cal_id = ?', array( $eid ) );
if ( $res ) {
  $row = dbi_fetch_row ( $res );
  $owner = $row[0];
  dbi_free_result ( $res );

  if ( $WC->isLogin( $owner ) || $WC->isNonuserAdmin() ) {
    $my_event = true;
    $can_edit = true;
  }
  //check UAC
  $can_edit = access_user_calendar ( 'edit', $owner );
}

// If the user is the creator of the event or their assistant, 
// allow them to delete the event from another user's calendar.
// It's essentially the same thing as editing the event and removing the
// user from the participants list.
//TODO Test is_assistant
if ( $my_event && ! $WC->isLogin() ) {
  $other_user = $user;
}

if ( _WC_READONLY )
  $can_edit = false;

// If User Access Control is enabled, check to see if the current
// user is allowed to delete events from the other user's calendar.
if ( $WC->userId() ) {
  $can_edit = access_user_calendar ( 'edit', $WC->userId() );

}

if ( ! $can_edit ) {
  $error = print_not_auth ();
}

// Is this a repeating event?
$event_repeats = false;
$res = dbi_execute ( 'SELECT COUNT(cal_id) FROM webcal_entry_repeats
  WHERE cal_id = ?', array( $eid ) );
if ( $res ) {
  $row = dbi_fetch_row ( $res );
  if ( $row[0] > 0 )
    $event_repeats = true;
  dbi_free_result ( $res );
}
$override_repeat = false;
if ( ! empty ( $date ) && $event_repeats && ! empty ( $override ) ) {
  $override_repeat = true;
}

if ( $eid > 0 && empty ( $error ) ) {
  if ( ! empty ( $date ) ) {
    $thisdate = $date;
  } else {
    $res = dbi_execute ( 'SELECT cal_date FROM webcal_entry WHERE cal_id = ?', array( $eid ) );
    if ( $res ) {
      // date format is 19991231
      $row = dbi_fetch_row ( $res );
      $thisdate = $row[0];
    }
  }

  // Only allow delete of webcal_entry & webcal_entry_repeats
  // if owner or admin, not participant.
  // If a user was specified, then only delete that user (not here) even if we
  // are the owner or an admin.
  if ( ( $WC->isAdmin() || $my_event ) && ! $other_user ) { 
    // Email participants that the event was deleted
    // First, get list of participants (with status Approved or
    // Waiting on approval).
    $sql = 'SELECT cal_login_id FROM webcal_entry_user WHERE cal_id = ? ' .
      "AND cal_status IN ('A','W')";
    $res = dbi_execute ( $sql, array( $eid ) );
    $partlogin = array ();
    if ( $res ) {
      while ( $row = dbi_fetch_row ( $res ) ) {
        $partlogin[] = $row[0];
      }
      dbi_free_result($res);
    }
    // Get event name
    $sql = 'SELECT cal_name, cal_date
      FROM webcal_entry WHERE cal_id = ?';
    $res = dbi_execute( $sql, array( $eid ) );
    if ( $res ) {
      $row = dbi_fetch_row ( $res );
      $name = $row[0];
      $eventstart = $row[1];
      dbi_free_result ( $res );
    }
    
    for ( $i = 0, $cnt = count ( $partlogin ); $i < $cnt; $i++ ) {
      // Log the deletion 
     activity_log ( $eid, $WC->loginId(), $partlogin[$i], $log_delete, '' );
      //check UAC
      $can_email = access_user_calendar ( 'email', 
	    $partlogin[$i], $WC->loginId());
   
      //don't email the logged in user  
      if ( $can_email == 'Y' && ! $WC->isLogin( $partlogin[$i] ) ) {  
        $do_send = getPref ( 'EMAIL_EVENT_DELTED', 1, $partlogin[$i] );
        $htmlmail = getPref ( 'EMAIL_HTML', 1, $partlogin[$i] );
        $t_format = getPref ( 'TIME_FORMAT', 1, $partlogin[$i] );
        $user_TIMEZONE = getPref ( 'TIMEZONE', 1, $partlogin[$i] );
        set_env ( 'TZ', $user_TIMEZONE );
        $default_language = getPref ( 'LANGUAGE', 2 );
        $user_language = getPref ( 'LANGUAGE', 1, $partlogin[$i] );
        $temp = $WC->User->loadVariables ( $partlogin[$i] );         
        if ( ! $WC->isNonuserAdmin() && ! $WC->isLogin( $partlogin[$i] ) 
		  && $do_send == 'Y' &&
          boss_must_be_notified ( $WC->loginId(), $partlogin[$i] ) && 
          ! empty ( $temp['email'] ) && getPref ( '_SEND_EMAIL', 2 ) ) {
            if ( empty ( $user_language ) || ( $user_language == 'none' )) {
               reset_language ( $default_language );
            } else {
               reset_language ( $user_language );
            }
          $msg = translate( 'Hello' ) . ', ' . $temp['fullname'] . ".\n\n" .
            translate( 'An appointment has been canceled for you by' ) .
            ' ' . $WC->getFullName () .  ".\n" .
            translate( 'The subject was' ) . ' "' . $name . "\"\n" .
            translate( 'Date' ) . ': ' . date_to_str ($thisdate) . "\n";
            if ( ! empty ( $eventtime ) && $eventtime != '-1' ) 
              $msg .= translate( 'Time' ) . ': ' . 
             // Apply user's GMT offset and display their TZID
             display_time ( $eventstart, 2, $t_format );
            $msg .= "\n\n";
            //use WebCalMailer class
            $mail->WC_Send ( $WC->getFullName (), $temp['email'], 
              $temp['fullname'], $name, $msg, $htmlmail, $login_email );
        }
      }
    }

    // Instead of deleting from the database... mark it as deleted
    // by setting the status for each participant to "D" (instead
    // of "A"/Accepted, "W"/Waiting-on-approval or "R"/Rejected)
    if ( $override_repeat ) {
      dbi_execute ( 'INSERT INTO webcal_entry_exceptions ( cal_id, cal_date, cal_exdate )
        VALUES ( ?, ?, ? )', array( $eid, $date, 1 ) );
      // Should we log this to the activity log???
    } else {
      // If it's a repeating event, delete any event exceptions
      // that were entered.
      if ( $event_repeats ) {
        $res = dbi_execute ( 'SELECT cal_id FROM webcal_entry
          WHERE cal_parent_id = ?', array( $eid ) );
        if ( $res ) {
          $ex_events = array ();
          while ( $row = dbi_fetch_row ( $res ) ) {
            $ex_events[] = $row[0];
          }
          dbi_free_result ( $res );
          for ( $i = 0, $cnt = count ( $ex_events );  $i < $cnt; $i++ ) {
            $res = dbi_execute ( 'SELECT cal_login_id FROM
              webcal_entry_user WHERE cal_id = ?', array( $ex_events[$i] ) );
            if ( $res ) {
              $delusers = array ();
              while ( $row = dbi_fetch_row ( $res ) ) {
                $delusers[] = $row[0];
              }
              dbi_free_result ( $res );
              for ( $j = 0, $cnt = count ( $delusers ); $j < $cnt; $j++ ) {
                // Log the deletion
                activity_log ( $ex_events[$i], $WC->loginId(), $delusers[$j],
                  $log_delete, '' );
                dbi_execute ( 'UPDATE webcal_entry_user SET cal_status = ?
                  WHERE cal_id = ? AND cal_login_id = ?', 
                  array( 'D', $ex_events[$i], $delusers[$j] ) );
              }
            }
          }
        }
      }

      // Now, mark event as deleted for all users.
      dbi_execute ( "UPDATE webcal_entry_user SET cal_status = 'D' " .
        "WHERE cal_id = ?", array( $eid ) );
        
      // Delete External users for this event
      dbi_execute ( 'DELETE FROM webcal_entry_ext_user
        WHERE cal_id = ?', array( $eid ) );
    }
  } else {
    // Not the owner of the event, but participant or noncal_admin
    // Just  set the status to 'D' instead of deleting.
    $del_user = ( ! empty ( $other_user ) ?  $other_user : $WC->loginId() );
    if ( ! $WC->isLogin() ) {
      if ( access_user_calendar ( 'edit', $WC->userId() ) ) {
        $del_user = $user;
      } else {
        // Error: user cannot delete from other user's calendar
        $error = print_not_auth ();
      }
    }
    if ( empty ( $error ) ) {
      dbi_execute ( 'UPDATE webcal_entry_user SET cal_status = ?
        WHERE cal_id = ? AND cal_login_id = ?', array( 'D', $eid, $del_user ) );
      activity_log ( $eid, $WC->loginId(), $WC->loginId(), 
	    $log_reject, '' );
    }
  }
}

$ret = $WC->getValue ( 'ret' );
$return_view = get_last_view ();
if ( ! empty ( $ret ) && $ret == 'listall' ) {
  $url = 'list_unapproved.php';
} else if ( ! empty ( $ret ) && $ret == 'list' ) {
  $url = 'list_unapproved.php';
  if ( $WC->userId() )
    $url .= "?user=$user";
}else if ( ! empty ( $return_view ) ) {
    do_redirect ( $return_view );
} else {
  $url = get_preferred_view ( '', $WC->userId() ? '' : "user=$user" );
}

//return to login TIMEZONE
set_env ( 'TZ', getPref ( 'TIMEZONE' ) );
if ( empty ( $error ) && empty ( $mailerError ) ) {
  do_redirect ( $url );
  exit;
}
//process errors
$mail->MailError ( $mailerError, $error ); ?>

