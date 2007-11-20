<?php
/* $Id$
 *
 * Description:
 * Presents page to edit/add an event/task/journal
 *
 */
include_once 'includes/init.php';


// Default for using tabs is enabled
$useTabs = ( getpref ( '_EVENT_EDIT_TABS' ) );
// make sure this is not a read-only calendar
$can_edit = true;

$smarty->assign ( 'copy', $WC->getGET ( 'copy', false ) );


$defusers = $WC->getPOST ( 'defusers' );
$override = $WC->getGET ( 'override', false );
$smarty->assign ( 'override', $override );

$eType = $WC->getGET ( 'eType', 'event' );
$date = $WC->getValue ( 'date', '-?[0-9]+' );
$day = $WC->getValue ( 'day', '-?[0-9]+' );
$month = $WC->getValue ( 'month', '-?[0-9]+' );
$year = $WC->getValue ( 'year', '-?[0-9]+' );
if ( empty ( $date ) && empty ( $month ) ) {
  if ( empty ( $year ) ) $year = date ( 'Y' );
  if ( empty ( $month ) ) $month = date ( 'm' );
  if ( empty ( $day ) ) $day = date ( 'd' );
  $date = sprintf ( "%04d%02d%02d", $year, $month, $day );
}

$eid = $WC->getId();

// Do we use FCKEditor?
if ( getPref ( '_ALLOW_HTML_DESCRIPTION' ) ){
  if ( file_exists ( 'includes/FCKeditor-2.0/fckeditor.js' ) &&
    file_exists ( 'includes/FCKeditor-2.0/fckconfig.js' ) ) {
    $smarty->assign ( 'use_fckeditor',  true );
  }
}

$external_users = $catNames = $catList='';
$participants = $bymonth = $byday = $bymonthday = $bysetpos = array();
$wkst = 'MO';
$create_by = $WC->loginId();

$real_user =  $WC->userLoginId();

if ( _WC_READONLY ) {
  $can_edit = false;
} else if ( ! empty ( $eid ) && $eid > 0 ) {
  $item = loadEvent ( $eid  );
 //  print_r ( $item);
  if ( ! $item ) {
    $error = str_replace ('XXX', $eid, translate ( 'Invalid entry id XXX' ) );
  } else {
    // first see who has access to edit this entry
    if ( $WC->isAdmin() ) {
      $can_edit = true;
    }

    // If current user is creator of event, then they can edit
    if ( $WC->isLogin( $item->getOwner() ) )
      $can_edit = true;
 
    if ( ! empty ( $override ) && ! empty ( $date ) ) {
      // Leave $cal_date to what was set in URL with date=YYYYMMDD
      $calTS = $date;
    } else {
      $calTS = $item->getDate();
    }
    $create_by = $item->getOwner();
    $duration = $item->getDuration();  
      $dur_h = (int)( $duration / 60 );
      $dur_m = $duration - ( $dur_h * 60 );   
    $priority = $item->getPriority();
    $eType = $item->getCalTypeName();
    $smarty->assign ( 'allday', $item->isAllDay() );
    $smarty->assign ( 'isTimed', $item->isTimed() );
    $smarty->assign ( 'isUntimed', $item->isUntimed() );
    $access = $item->getAccess();
    $smarty->assign ( 'name', $item->getname() );
    $smarty->assign ( 'description', $item->getDescription() );
    $smarty->assign ( 'parent',  $item->getParent() );
    $smarty->assign ( 'location', $item->getLocation() );
    $smarty->assign ( 'completed', $item->getCompleted() );
    $smarty->assign ( 'cal_url', $item->getUrl() );    
    $cal_creatorIp = $item->getRmtAddr();

    //Don't adjust for All Day entries
    $cal_date = $item->getDate( 'Ymd' );
    $cal_time = $item->getDate( 'His' );
    
    $end_time = $item->getEndDate ( 'His' );
  
  
    $due_date = $item->getDueDate( 'Ymd' );
    $due_time = $item->getDueDate( 'His' );

    
    $can_edit_uac = access_user_calendar ( 'edit', 
      $create_by, $WC->loginId(), $item->getCalType(), $access );
    if ( ! $can_edit_uac ) {
       $can_edit = false;
       $error = str_replace ('XXX', 'UAC', translate ( 'Restricted by XXX' ) );
    }
      
    //if we allow NUCs to edit their own events, we can compare their IP
    // to the one saved with the event.
    if ( getPref ( '_RESTRICT_NUC_EDITS_BY_IP', 2 ) && $WC->isNonUser() && 
    ( $cal_creatorIp != ip2long ( $_SERVER['REMOTE_ADDR'] ) ) ) {
      $can_edit = false;
      $error = str_replace ('XXX', 'IP', translate ( 'Restricted by XXX' ) );
    }
    //what kind of entry are we dealing with?
    if ( $eType == 'task' ) {      
      $others_complete = 'Y';
      $pctnum = 0;
      $sql = 'SELECT cal_login_id,  cal_percent, cal_status 
       FROM webcal_entry_user WHERE cal_id = ?';
      $res = dbi_execute ( $sql, array( $eid ) );
      if ( $res ) {
        while ( $row = dbi_fetch_row ( $res ) ) {
          $overall_percent[$pctnum]['percent'] = $row[1];
          $overall_percent[$pctnum]['fullname'] = $WC->User->getFullName( $row[0] ); 
          if ( $row[1] < 100 ) $others_complete = 'N';
          if ( $WC->isLogin( $row[0] ) || ( $WC->isAdmin() && 
            $WC->userId() == $row[0] ) ) {
            $task_percent = $row[1];
            $task_status = $row[2];
          } 
        $pctnum++;
        }
        if ( $pctnum == 1 ) $others_complete = 'Y';  
        dbi_free_result ( $res ); 
      } 
    }

    
    $year = (int) ( $cal_date / 10000 );
    $month = ( $cal_date / 100 ) % 100;
    $day = $cal_date % 100;


    // check for repeating event info...
    // but not if we are overriding a single entry of an already repeating
    // event... confusing, eh?
    if ( ! empty ( $override ) ) {
      $rpt_type = 'none';
      $rpt_end = 0;
      $rpt_end_date = $cal_date;
      $rpt_freq = 1;
    } elseif ( $item->isRepeat() ) {
      $rpt_type = $item->getRepeatType();
      $rpt_end = $item->getRepeatType();

      if ( $item->getRepeatEnd() ) {
         $rpt_end_date = date( 'Ymd', $item->getRepeatEnd() );
         $rpt_end_time = date( 'His', $item->getRepeatEnd() );
      }  else {
        $rpt_end_date = $cal_date;
        $rpt_end_time = $cal_time;
      }        
      $rpt_freq = $item->getRepeatFrequency();
      
      $byday = explode( ',', $item->getRepeatByDay() );
      $smarty->assign ( 'bydayStr', $item->getRepeatByDay() );
      
      $bymonth = explode( ',', $item->getRepeatByMonth() );
      $smarty->assign ( 'bymonth', $item->getRepeatByMonth() );
      
      $bymonthday = explode( ',', $item->getRepeatByMonthDay() );
      $smarty->assign ( 'bymonthdayStr', $item->getRepeatByMonthDay() );
      
      $bysetpos = explode( ',', $item->getRepeatBySetPos() );      
      $smarty->assign ( 'bysetposStr', $item->getRepeatBySetPos() );
      
      $smarty->assign ( 'byweekno', $item->getRepeatByWeekNo() );
      $smarty->assign ( 'byyearday', $item->getRepeatByYearDay() );
      $smarty->assign ( 'wkst', $item->getRepeatWkst() );
      $smarty->assign ( 'rpt_count', $item->getRepeatCount() );
           
      //Check to see if Weekends Only is applicable
      $smarty->assign ( 'weekdays_only', $item->isWeekdaysOnly() );


    //Get Repeat Exceptions/Inclusions
      $smarty->assign ( 'exceptions', $item->getRepeatExceptions() );
      $smarty->assign ( 'inclusions', $item->getRepeatInclusions() ); 

      //determine if Expert mode needs to be set
      $smarty->assign ( 'expert_mode', $item->isExpertMode() );
    }  

  if ( getPref ( '_ENABLE_CATEGORIES' ) ) {
    $catById = get_categories_by_eid ( $eid, $real_user, true );
    if ( ! empty ( $catById ) ) {
      $catNames = implode(', ', $catById );
      $catList = implode(',', array_keys ( $catById ) );
    }
  } //end _ENABLE_CATEGORIES test

  //get reminders 
  $reminder = getReminders ( $eid ); 
  $reminder_offset = ( ! empty (  $reminder ) ? $reminder['offset'] : 0 );
  
  //get participants
  $sql = 'SELECT cal_login_id , cal_status 
    FROM webcal_entry_user 
    WHERE cal_id = ? AND cal_status IN (\'A\', \'W\' )';
  $res = dbi_execute ( $sql, array( $eid ) );
  if ( $res ) {
    $partnum=0;
    while ( $row = dbi_fetch_row ( $res ) ) {
      $participants[$partnum]['cal_login_id'] = $row[0];
      $participants[$partnum]['cal_fullname'] = $WC->getFullName ( $row[0] );
      $participants[$partnum]['status'] = ( $row[1] == 'W' ? '(?)' : '' );
      $partnum++;
    }
    dbi_free_result ( $res );    
  }
//Not allowed for tasks or journals 
  if (  $eType == 'event'  && getPref ( '_ALLOW_EXTERNAL_USERS' ) ) {
    $external_users = event_get_external_users ( $eid );
  }
  }
} else {
 // ##########   New entry.   ################
 // to avoid warnings below about use of undefined var
 //We'll use $WORK_DAY_START_HOUR,$WORK_DAY_END_HOUR
 // As our starting and due times
 $cal_time = getPref ( 'WORK_DAY_START_HOUR' ) . '0000';
 $due_hour = getPref ( 'WORK_DAY_END_HOUR' );
 $end_time = $due_time = $due_hour . '0000';
 $dur_h = $dur_m = '';
 $due_minute = 0;
 $task_percent = 0;
 $priority = getPref ( 'DEFAULT_PRIORITY', 1, '', '5' );
 $access = getPref ( 'DEFAULT_ACCESS', 1, '', 'P' );

 //reminder settings
 $reminder_offset = ( getPref ( 'REMINDER_WITH_DATE' ) ? 
   getPref ( 'REMINDER_OFFSET' ) : 0 );
 
 //Did we pass in a time?
 $cal_time = $WC->getValue ( 'cal_time' );
 $smarty->assign ( 'isTimed', ! empty ( $cal_time ) );  
 
  if ( ! empty ( $defusers ) ) {
    $smarty->assign ( 'isTimed', true );    
    $tmp_ar = explode ( ',', $defusers );
    for ( $i = 0, $cnt = count ( $tmp_ar ); $i < $cnt; $i++ ) {
      $participants[$tmp_ar[$i]] = 1;
    }
  }
  if ( empty ( $participants ) ) {
    $participants[0]['cal_login_id'] = $WC->loginId();
    $participants[0]['cal_fullname'] = $WC->getFullName ( $WC->loginId() );
  }
  
  $smarty->assign ( 'allDay', $WC->getGet ( 'allday' ) );
  
  $cat_id = $WC->getGet ( 'cat_id' );
  //if ( $cat_id && $cat_id > 0 ) {
    $categories = $WC->categories ();
    if ( isset ( $categories[$cat_id] ) ) {
      $catNames = $categories[$cat_id]['cat_name'];
      $catList = $WC->getGet ( 'cat_id' );
    }
  //}
  $overall_percent = 0;
  $others_complete = 'Y';
  
  if ( $eType == 'task' ) {
    $overall_percent = array();
    $overall_percent[0]['percent'] = 0;
    $overall_percent[0]['fullname'] = $WC->User->getFullName( $WC->loginId() );
  }
} //end new entry

if ( ! empty ( $error ) || ! $can_edit ) {
  build_header ();
  $smarty->assign ( 'errorStr', $error );
  $smarty->display ( 'error.tpl' );
  exit;
}


$dateYmd = date ( 'Ymd' );
$thisyear = $year;
$thismonth = $month;
$thisday = $day;
if ( empty ( $rpt_type ) || ! $rpt_type )
  $rpt_type = 'none';

if ( empty ( $duration ) )
  $duration = 0;


$smarty->assign ( 'priority', $priority );
$smarty->assign ( 'access', $access );

if ( empty ( $rpt_freq ) )
  $rpt_freq = 0;
if ( empty ( $rpt_end_date ) )
  $rpt_end_date = 0;
if ( empty ( $rpt_end_time ) )
  $rpt_end_time = 0;

if ( empty ( $cal_date ) ) {
  if ( ! empty ( $date ) && $eType != 'task' )
    $cal_date = $date;
  else
    $cal_date = $dateYmd;
  if ( empty ( $due_date ) )
    $due_date = $dateYmd;
}
if ( empty ( $thisyear ) )
  $thisdate = $dateYmd;
else {
  $thisdate = sprintf ( "%04d%02d%02d",
    empty ( $thisyear ) ? date ( 'Y' ) : $thisyear,
    empty ( $thismonth ) ? date ( 'm' ) : $thismonth,
    empty ( $thisday ) ? date ( 'd' ) : $thisday );
}
if ( empty ( $cal_date ) || ! $cal_date ) {
  $cal_date = $thisdate;
}
if ( empty ( $due_date ) || ! $due_date )
  $due_date = $thisdate;

$smarty->assign ( 'cal_date', $cal_date );
$smarty->assign ( 'cal_time', $cal_time );

$smarty->assign ( 'end_time', $end_time );

$smarty->assign ( 'dur_h', $dur_h );
$smarty->assign ( 'dur_m', $dur_m );

$smarty->assign ( 'due_date', $due_date );
$smarty->assign ( 'due_time', $due_time );

$smarty->assign ( 'catNames', $catNames );
$smarty->assign ( 'catList', $catList );

$smarty->assign ( 'overall_percent', $overall_percent );
$smarty->assign ( 'others_complete', $others_complete );
//Setup to display user's timezone difference if Admin or Assistane
//Even though event is stored in GMT, an Assistant may need to know that
//the boss is in a different Timezone
if ( $WC->userId() ) {
  //don't change this to date
  $tz_offset = date ( 'Z', date_to_epoch ( $cal_date . $cal_time ) );   
  $user_TIMEZONE = getPref ( 'TIMEZONE', 1, $WC->userId() );
  set_env ( 'TZ', $user_TIMEZONE );
  $user_tz_offset = date ( 'Z', date_to_epoch ( $cal_date . $cal_time ) );
  if ( $tz_offset != $user_tz_offset ) {  //Different TZ_Offset
    $WC->User->loadVariables ( $user, 'temp' );
    $tz_diff = ( $user_tz_offset - $tz_offset ) / ONE_HOUR;
    $tz_value = ( $tz_diff > 0? translate ( 'hours ahead of you' ) :
      translate ( 'hours behind you' ) );
    $TZ_notice = '(' . $tempfullname . ' ' . 
      translate ( 'is in a different timezone than you are. Currently' ) . ' ';
      //TODO show hh:mm instead of abs 
    $TZ_notice .= abs ( $tz_diff ) . ' ' . $tz_value . '.<br />&nbsp;'; 
    $TZ_notice .= translate ( 'Time entered here is based on your Timezone' ) . '.)'; 
  }
  //return to $login TIMEZONE
  set_env ( 'TZ', getPref ( 'TIMEZONE' ) );
}

//Priority
$pri[1] = translate ( 'High' );
$pri[2] = translate ( 'Medium' );
$pri[3] = translate ( 'Low' );
for ( $i=1;$i<=9;$i++){ 
  $smartypriority[$i]['selected'] = ( $priority == $i ? SELECTED : '' );
  $smartypriority[$i]['display'] = $i . '-' .$pri[ceil($i/3)];
}
$smarty->assign ( 'priority', $smartypriority );

//Site Extras
$smarty->assign ( 'site_extras', $site_extras );
if ( $eid > 0 )
  $smarty->assign ( 'extras', get_site_extra_fields ( $eid ) );

//Participants
if ( ( getPref ( '_ENABLE_PARTICIPANTS_FIELD' ) 
  || $WC->isAdmin() ) && ! _WC_SINGLE_USER ) {
  $myuserlist = array ();
  $smarty->assign ( 'show_participants', true );
  
  $userlist = get_my_users ( $real_user, 'invite', false );
  $size = count ( $userlist );
   
  $nonuserlist = get_nonuser_cals ();
  $size = ( count ( $nonuserlist ) > $size ? count ( $nonuserlist ) : $size );
    
  $grouplist = get_groups ( $real_user );  
  $size = ( count ( $grouplist ) > $size ? count ( $grouplist ) : $size );
   
  for ( $i = 0, $usercnt = count ( $userlist ); $i < $usercnt; $i++ ) {
    $l = $myuserlist[$i]['cal_login_id'] = $userlist[$i]['cal_login_id'];
    $n = $myuserlist[$i]['cal_fullname'] = $userlist[$i]['cal_fullname'];
    if ( ! empty ( $eid ) ) {
      if ( ! empty ($participants[$l]) )
        $participants[$i]['selected'] = SELECTED;
    } else {
      if ( ! empty ($defusers) && ! empty ( $participants[$l] ) ) {
        // default selection of participants was in the URL
        $participants[$i]['selected'] = SELECTED;
      }
      if ( ( $WC->isLogin( $l ) &&  ! $WC->isNonuserAdmin() ) 
        || ( $WC->userId() == $l ) ) {
        $participants[$i]['selected'] = SELECTED;
      }
    }
  }
  if ( $size > 50 )
    $size = 15;
  else if ( $size > 5 )
    $size = 5;  

  $smarty->assign ( 'size', $size );
  $smarty->assign ( 'myuserlist', $myuserlist );
  $smarty->assign ( 'nonuserlist', $nonuserlist );
  $smarty->assign ( 'grouplist', $grouplist );
  $smarty->assign ( 'participants', $participants );
}

//Setup Repeat data
$smarty->assign ( 'rpt_type', $rpt_type );
$smarty->assign ( 'rpt_freq', $rpt_freq );
//set up bymonth
for ( $by_month =1;$by_month <=12; $by_month++){
  $smartybymon[$by_month]['checked']= in_array($by_month,$bymonth)? CHECKED :'';
  $smartybymon[$by_month]['date']= translate ( date ( 'M', mktime ( 0,0,0, $by_month,1 ) ) );
}
$smarty->assign ( 'bymonth', $smartybymon );
//set up bydayAll
for ( $byday_single =0;$byday_single <=6; $byday_single++){
    $bydayAlllbl = translate ( $WC->byday_names[$byday_single] );
    $bydayAll[$bydayAlllbl] = in_array($WC->byday_names[$byday_single],$byday)? CHECKED :'';
}
$smarty->assign ( 'bydayAll', $bydayAll );

//set up byday
for ( $loop_ctr=1; $loop_ctr <= 6; $loop_ctr++) {
  for ( $rpt_byday =0;$rpt_byday <=6; $rpt_byday++){
    $rptByday[$loop_ctr . $rpt_byday]['id'] = "b$loop_ctr$rpt_byday";
    $rptByday[$loop_ctr . $rpt_byday]['value'] = 
      (in_array($loop_ctr . $WC->byday_names[$rpt_byday],$byday) 
    ?$loop_ctr . translate ( $WC->byday_names[$rpt_byday] )
   : (in_array(($loop_ctr -6) . $WC->byday_names[$rpt_byday],$byday)
   ?($loop_ctr -6) . translate ( $WC->byday_names[$rpt_byday] ):'        ')); 
  }
}
$smarty->assign ( 'byday', $rptByday );

//set up bymonthday
for ( $loop_ctr=1; $loop_ctr <32; $loop_ctr++) {
  $rptByMonthDay[$loop_ctr]['id'] = 'bymonthday' . $loop_ctr;
  $rptByMonthDay[$loop_ctr]['value'] =  (in_array($loop_ctr,$bymonthday) 
    ?($loop_ctr):(in_array(($loop_ctr -32),$bymonthday)
   ?($loop_ctr -32):'')); 
}
$smarty->assign ( 'bymonthday', $rptByMonthDay );

//set up bysetpos
for ( $loop_ctr=1; $loop_ctr <32; $loop_ctr++) {
  $rptBySetPos[$loop_ctr]['id'] = 'p' . $loop_ctr;
  $rptBySetPos[$loop_ctr]['value'] = (in_array($loop_ctr,$bysetpos) 
    ?($loop_ctr):(in_array(($loop_ctr -32),$bysetpos)
   ?($loop_ctr -32):'')); 
}
$smarty->assign ( 'bysetpos', $rptBySetPos );

$smarty->assign ( 'textareasize', 'rows="10" cols="70"' );

$BodyX = 'onload="onLoad();"';

$tabs_ar = array();
$tabs_ar['details'] =translate( 'Details' );
if ( getPref ( '_ENABLE_PARTICIPANTS_FIELD' ) )
 $tabs_ar['participants'] = translate( 'Participants' );

if ( getPref ( '_ENABLE_REPEATING_FIELD' ) )
   $tabs_ar['pete'] = translate( 'Repeat' );

if ( getPref ( '_ENABLE_REMINDER_FIELD' ) )
   $tabs_ar['reminder'] = translate( 'Reminders' );

$smarty->assign ( 'tabs_ar', $tabs_ar );

build_header ( array ( 'edit_entry.js', 'datepicker.js' ), '', $BodyX );
$smarty->assign ( 'eType', $eType );

$smarty->assign ( 'eid', $eid );
$smarty->display ( 'edit_entry.tpl' );
?>
