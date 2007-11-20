<?php
/* $Id$
 *
 * Description
 * This is the handler for event Ajax httpXmlRequests.
 */
include_once 'includes/init.php';

$CONTEXTMENU = true;

$ajaxout =  $out = '';
$tipinfo = array();

$page = $WC->getPOST ( 'page' );

$cmval = array();

/* Pre-load tasks for quicker access */
if ( getPref ( 'DISPLAY_TASKS_IN_GRID' ) )
  $tasks = ( getPref ( 'DISPLAY_TASKS_IN_GRID' ) ? read_tasks () : '' );
$startTS = $WC->getStartDate();
$endTS = $WC->getEndDate();

$user= $WC->userId();
$type = $WC->getPOST ( 'type' );
$force_reload = false;
if ( $page == 'update' ) {
  $eid = $WC->getPOST ( 'eid' );
  $date = $WC->getPOST ( 'date' );
	if ( $type == 'DO' )  //Delete single day of repeating event
    $type .= $date;
  update_status ( $type, $WC->loginId(), $eid );  
	$force_reload = true;
	if ( $type != 'DA' )
	  $startTS = $endTS = date_to_epoch ( $date);
}

/* Pre-Load the repeated events for quicker access */
$repeated_events = read_repeated_events ();

/* Pre-load the non-repeating events for quicker access */
$events = read_events ();
//print_r ( $repeated_events);
//print_r ( $events);
for ( $i=$startTS; $i<=$endTS; ) {
	$idate = gmdate ( 'Ymd', $i );
  $out = print_ajax_entries ( $idate, $user, $events, $tasks, $WC, $force_reload );
	$i += ONE_DAY;
  if ( ! empty ( $out ) ) {
	 $out = substr ( $out, 1 );
    $ajaxout .= '{"dv":"' . $idate . '","ev":[' . $out 
		  . ']},';
    $out = '';
  } else if ( $force_reload ) {
	  $ajaxout .= '{"dv":"' . $idate . '","ev":[{"eid":"blank"}]},';
	}
}
//Strip trailing comma
$ajaxout = substr ( $ajaxout, 0, strlen ( $ajaxout ) -1 );
	 
//Wrap data with JSON label
$ajaxout = '{"caldata":[' . $ajaxout . ']';

//Add popup info
if ( ! empty ( $tipinfo ) ) {
  $ajaxout .= ',"tip":[';
  foreach ( $tipinfo as $tip) {
    $ajaxout .= $tip . ',';
  }
	//Strip trailing comma
  $ajaxout = substr ( $ajaxout, 0, strlen ( $ajaxout ) -1 );
	$ajaxout .=  ']';
}

echo $ajaxout .'}';
/*
TEST CASE
echo '{"caldata":[{"dv":"20071010","ev":[{"eid":"2-0","cl":"entry","user":"1","sum":"Test #2","time":"10am&raquo;&nbsp;","cm":"20071010"}]}],"tip":[{"tid":"2","ptime":"10:00am","ploc":"Anytown USA","pdesc":"This is the Description <font color=\"red\">\"Hello World\"</font>"}]}';
*/ 

 
/* Prints all the calendar entries for the specified user for the specified date.
 *
 * If we are displaying data from someone other than
 * the logged in user, then check the access permission of the entry.
 *
 * @param string $date  Date in YYYYMMDD format
 * @param string $user  Username
 * @param bool   $ssi   Is this being called from week_ssi.php?
 */
function print_ajax_entries ( $date, $user, &$events, &$tasks, &$WC ) {
 global $CONTEXTMENU, $categories, $cmval,
  $layers, $WC, $user, $type, $tipinfo;

//echo $date;
  static $key = 0;
  $dvinfo = ''; 
	 
  $get_unapproved = ( getPref ( 'DISPLAY_UNAPPROVED'  ));
  $ev_ret = $context_ret = '';
  
  // Get, combime and sort the events for this date.
  $ev = combine_and_sort_events (
    // Get all the non-repeating events.
    get_entries ( $date, $get_unapproved ),
    // Get all the repeating events.
    get_repeating_entries ( $user, $date, $get_unapproved ) );

  // If wanted, get all due tasks for this date.
  if ( ( getPref ( 'DISPLAY_TASKS_IN_GRID'  )) &&
      ( $date >= date ( 'Ymd' ) ) )
    $ev = combine_and_sort_events ( $ev, get_tasks ( $date, $get_unapproved ) );
  
	$evCnt = count ( $ev );

  for ( $i = 0; $i < $evCnt; $i++ ) {  
	  $tip = '';   
    if ( $get_unapproved || $ev[$i]->getStatus () == 'A' ) {
      $catIcon = $in_span = $padding = $popup_timestr = $name = $out = $timestr = $time ='';
      $cal_type = $ev[$i]->getCalTypeName ();
      $loginStr = $ev[$i]->getLoginId ();
    
      $can_view = access_user_calendar ( 'view', $loginStr, '',
        $ev[$i]->getCalType (), $ev[$i]->getAccess () );
      $can_edit = access_user_calendar ( 'edit', $loginStr, '',
        $ev[$i]->getCalType (), $ev[$i]->getAccess () );
      $time_only = access_user_calendar ( 'time', $loginStr );
      if ( $cal_type == 'task' && $can_view == 0 )
        return false;
    
      // No need to display if show time only and not a timed event.
      if ( $time_only == 'Y' && ! $ev[$i]->Istimed () )
       continue;
    
      $class = ( ! $WC->isLogin( $loginStr)
        ? 'layerentry' : ( $ev[$i]->getStatus () == 'W' ? 'unapproved' : '' ) . 'entry' );
    
      // If we are looking at a view, then always use "entry".
      if ( defined ( '_WC_CUSTOM_VIEW' ) )
        $class = 'entry';
    
      $cloneStr = $ev[$i]->getClone ();
      $eid = $ev[$i]->getId ();
      $divid = "$eid-$key";
  
      $key++;
    
      // Build entry link if UAC permits viewing.
      //if ( $can_view != 0 && $time_only != 'Y' ) {
        // Make sure clones have parents URL date.
        $realdate = ( $cloneStr ? $cloneStr : $date );
    
    
      $catNum = abs ( $ev[$i]->getCategory () );
      $icon = $cal_type . '.gif';
      if ( ! empty ( $categories[$catNum]['cat_icon'] ) )
        $icon =  $categories[$catNum]['cat_icon'];

    
      if ( $WC->loginId() != $loginStr && strlen ( $loginStr ) ) {
        if ( $layers ) {
          foreach ( $layers as $layer ) {
            if ( $layer['cal_layeruse_id'] == $loginStr ) {
              $color = $layer['cal_color'];
            }
          }
        }
        // Check to see if Category Colors are set.
      } else
      if ( ! empty ( $categories[$catNum]['cat_color'] ) ) {
        $cat_color = $categories[$catNum]['cat_color'];
        if ( $cat_color != '#000000' ) {
          $color = $cat_color;
        }
      }
    
      if ( $ev[$i]->isAllDay () )
        $timestr  = translate ( 'All day event' );
      else
      if ( ! $ev[$i]->isUntimed () ) {
        $time = $timestr = display_time( $ev[$i]->getDate() );
        if ( $ev[$i]->getDuration () > 0 )
          $timestr .= ' - ' . display_time ( $ev[$i]->getEndDate () );
    
        if ( getPref ( 'DISPLAY_END_TIMES' ) )
          $time = $timestr;
    
        if ( $cal_type == 'event' )
         $time = getShortTime ( $time )
           . ( $time_only == 'Y' ? '' : getPref ( 'TIME_SPACER' ) );
      }
      
      
      $reminder = getReminders ( $ev[$i]->getId (), true );
      $not_my_entry = ( ( $WC->loginId() != $user && strlen ( $user ) ) ||
        ( $WC->loginId() != $ev[$i]->getLoginId () && strlen ( $ev[$i]->getLoginId () ) ) );
    
      $sum_length = getPref ( 'SUMMARY_LENGTH' );
      if ( $ev[$i]->isAllDay () || $ev[$i]->isUntimed () )
        $sum_length += 6;
    
      $tmpAccess = $ev[$i]->getAccess ();
      $tmpId = $ev[$i]->getId ();
      $tmpLogin = $ev[$i]->getLoginId ();
      $tmpName = $ev[$i]->getName ();
      $tmp_name = htmlspecialchars ( substr ( $tmpName, 0, $sum_length )
         . ( strlen ( $tmpName ) > $sum_length ? '...' : '' ) );
    
      if ( $not_my_entry && $tmpAccess == 'R' && !
        ( $can_view & PRIVATE_WT ) ) {
        if ( $time_only != 'Y' )
          $name = '(' . translate ( 'Private' ) . ')';
    
        $tip = build_prototip ( $tmpLogin,
          str_replace ( 'XXX', translate ( 'private', true ),
            translate ( 'This event is XXX.', true ) ), '' );
      } else
      if ( $not_my_entry && $tmpAccess == 'C' && !
        ( $can_view & CONF_WT ) ) {
        if ( $time_only != 'Y' )
          $name = '(' . translate ( 'Conf.' ) . ')';
    
        $tip = build_prototip ( $tmpLogin,
          str_replace ( 'XXX', translate ( 'confidential', true ),
            translate ( 'This event is XXX.', true ) ), '' );
      } else
      if ( $can_view == 0 ) {
        if ( $time_only != 'Y' )
          $name = $tmp_name;
    
        $tip = build_prototip ( $tmpLogin, '',
          $timestr, '', '', $tmpName, '' );
      } else {
        if ( $time_only != 'Y' )
          $name = $tmp_name;
    
        $tip = build_prototip ( $tmpLogin,
          $ev[$i]->getDescription (), $timestr, site_extras_for_popup ( $tmpId ),
          $ev[$i]->getLocation (), $tmpName, $tmpId, $reminder );
      }    
          
        
      // Added to allow a small location to be displayed if wanted.
      $loc = ( ! empty ( $location ) && getPref ( 'DISPLAY_LOCATION' )
        ? htmlspecialchars ( $location ) : '' );

    if ( $CONTEXTMENU ) {  
      //example  
      //var cmenu = \$H({ '4-0': 'VED', '8-1': 'VED' });";     
      if ( $can_view ) {
         $cmval = ( $cal_type == 'event'? 'V' : 'W');//view
      }
             
      if (  $ev[$i]->getStatus () == 'W' ) {
         $cmval .= 'AR';//approve/repeat
      }
 
       if ( $can_edit ) {
        if ( $ev[$i]->isRepeat () )
          $cmval = $ev[$i]->getDate ( 'Ymd' );//edit all/edit only/del all/del only      
        else
          $cmval .= ( $cal_type == 'event'? 'ED' : 'TD' );//edit/delete
      }       
      
    } //$CONTEXTMENU
		if ( empty ( $tipinfo[$eid] ) )
		  $tipinfo[$eid] = '{"tid":"' . $eid . '"' . $tip . '}';
   }
	 //example {"eid":"10-0","cl":"entry","user":"1","sum":"Test10","cm":"VED"}
	 $dvinfo .= ',{"eid":"' . $divid . '"' 
	   . ($cal_type != 'event'? ',"type":"' . $ev[$i]->getCalType() . '"' : '' ) 
	   .',"cl":"' . $class . '","user":"' . $tmpLogin 
	   .'","sum":"' . $name . '","time":"' . $time . '","cm":"' . $cmval . '"}';
   
  }

  return $dvinfo;
}


/* Builds the HTML for the entry popup.
 *
 * @param string $popupid      CSS id to use for event popup
 * @param string $user         Username of user the event pertains to
 * @param string $description  Event description
 * @param string $time         Time of the event
 *                             (already formatted in a display format)
 * @param string $site_extras  HTML for any site_extras for this event
 *
 * @return string  The HTML for the event popup.
 */
function build_prototip ( $user, $description = '', $time,
  $site_extras = '', $location = '', $name = '', $eid = '', $reminder = '' ) {
  global $WC, $popup_fullnames, $popuptemp_fullname,
  $tempfullname;

  if ( ! getPref ( 'ENABLE_POPUPS' ) )
    return;

  // Restrict info if time only set.
  $details = true;
  if ( ! $WC->isLogin( $user ) ) {
    $time_only = access_user_calendar ( 'time', $user );
    $details = ( $time_only == 'N' ? 1 : 0 );
  }

  $ret = '';
	
  if ( empty ( $popup_fullnames ) )
    $popup_fullnames = array ();

  $partList = array ();
  if ( $details && $eid != '' && getPref ( 'PARTICIPANTS_IN_POPUP' ) ) {
    $rows = dbi_get_cached_rows ( 'SELECT cal_login_id, cal_status
      FROM webcal_entry_user WHERE cal_id = ? AND cal_status IN ( \'A\',\'W\' )',
      array ( $eid ) );
    if ( $rows ) {
      for ( $i = 0, $cnt = count ( $rows ); $i < $cnt; $i++ ) {
        $row = $rows[$i];
        $participants[] = $row;
      }
    }
    for ( $i = 0, $cnt = count ( $participants ); $i < $cnt; $i++ ) {
      $WC->User->loadVariables ( $participants[$i][0], 'temp' );
      $partList[] = $tempfullname . ' '
       . ( $participants[$i][1] == 'W' ? '(?)' : '' );
    }
    $rows = dbi_get_cached_rows ( 'SELECT cal_fullname FROM webcal_entry_ext_user
      WHERE cal_id = ? ORDER by cal_fullname', array ( $eid ) );
    if ( $rows ) {
      $extStr = translate ( 'External User' );
      for ( $i = 0, $cnt = count ( $rows ); $i < $cnt; $i++ ) {
        $row = $rows[$i];
        $partList[] = $row[0] . ' (' . $extStr . ')';
      }
    }
  }

  if ( ! $WC->isLogin( $user ) ) {
    if ( empty ( $popup_fullnames[$user] ) ) {
      $WC->User->loadVariables ( $user, 'popuptemp_' );
      $popup_fullnames[$user] = $popuptemp_fullname;
    }
    $ret .= ',"puser":"' . $popup_fullnames[$user] . '"';
  }
  $ret .= ( getPref ( 'SUMMARY_LENGTH' ) < 80 && strlen ( $name ) && $details
    ?  ',"psum":"' 
		. htmlspecialchars ( substr ( $name, 0, 40 ) ).'"' : '' );
		
   $ret .= ( strlen ( $time )
    ?  ',"ptime":"' . $time . '"' : '' );
		
    $ret .= ( ! empty ( $location ) && $details
    ? ',"ploc":"' . addslashes ( $location ) . '"' : '' );
		
   $ret .= ( ! empty ( $reminder ) && $details
    ? ',"prem":"' . $reminder . '"' : '' );

  if ( ! empty ( $partList ) && $details ) {
    $ret .= ',"ppart":"';
    foreach ( $partList as $parts ) {
      $ret .= $parts . '<br />';
    }
		$ret .= '"';
  }

  if ( ! empty ( $description ) && $details ) {
	  $description = addslashes ( $description );
    $ret .= ',"pdesc":"';
    if ( getPref ( '_ALLOW_HTML_DESCRIPTION' ) ) {
      // Replace &s and decode special characters.
      $str = unhtmlentities (
        str_replace ( '&amp;amp;', '&amp;',
          str_replace ( '&', '&amp;', $description ) ) );
      // If there is no HTML found, then go ahead and replace
      // the line breaks ("\n") with the HTML break ("<br />").
      $ret .= ( strstr ( $str, '<' ) && strstr ( $str, '>' )
        ? $str : nl2br ( $str ) );
    } else
      // HTML not allowed in description, escape everything.
      $ret .= nl2br ( htmlspecialchars ( $description ) );
		$ret .= '"';
  } //if $description
	
	if ( ! empty ( $site_extras ) )
	  $ret .= ',"pse":"' . $site_extras . '"';;
		
  return $ret;
}


?>
