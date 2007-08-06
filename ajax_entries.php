<?php
/* $Id$
 *
 * Description
 * This is the handler for Ajax httpXmlRequests.
 */
include_once 'includes/init.php';

$CONTEXTMENU = false;

/* Pre-Load the repeated events for quicker access */
$repeated_events = read_repeated_events ();

/* Pre-load the non-repeating events for quicker access */
$events = read_events ();

/* Pre-load tasks for quicker access */
if ( getPref ( 'DISPLAY_TASKS_IN_GRID' ) )
  $tasks = ( getPref ( 'DISPLAY_TASKS_IN_GRID' ) ? read_tasks () : '' );
$start = date ('Ymd', $WC->getStartDate() );
$end = date ('Ymd', $WC->getEndDate() );
$user= $WC->userId();

		
$ajaxout =  ( $CONTEXTMENU ? 'DomContextMenu.create();' : '' );

for ( $i=$start; $i<=$end;$i++) {
  $out = print_ajax_entries ( $i, $user, $events, $tasks, $WC );
  if ( ! empty ( $out ) ) {
	  //$out = str_replace ( '"', '\\"', $out );
    //$out = str_replace ( "'", "\\'", $out );
//	 $tdid = 'td' . $i;
//   $haseventStr = 'parclass = document.getElementById(\'' 
//	   . $tdid  . '\').parentNode.className;
//	   document.getElementById(\'' 
//	   . $tdid . '\').parentNode.className = parclass + " hasevents";';
    $haseventStr = '';
    $ajaxout .= $haseventStr . $out;
		$out = '';
  }
}
	
echo $ajaxout;

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
 global $CONTEXTMENU, $categories,
  $layers, $WC, $user;

 
	$context_ret = '';
  static $key = 0;
  static $viewEventStr, $viewTaskStr, 
	$editStr, $approveStr, $deleteStr;

  if ( empty ( $viewEventStr ) ) {
    $viewEventStr = translate ( 'View this event' );
    $viewTaskStr = translate ( 'View this task' );
		
    $editStr = translate ( 'Edit this event' );
    $approveStr = translate ( 'Approve' );
    $deleteStr = translate ( 'Delete' );
  }
	
  $get_unapproved = ( getPref ( 'DISPLAY_UNAPPROVED'  ));
  $ret = $ev_ret = $context_ret = '';
  
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
  
  for ( $i = 0, $evCnt = count ( $ev ); $i < $evCnt; $i++ ) {     
    if ( $get_unapproved || $ev[$i]->getStatus () == 'A' ) {
			$catIcon = $in_span = $padding = $popup_timestr = $ret = $out =$timestr = '';
			$cal_type = $ev[$i]->getCalTypeName ();
			$loginStr = $ev[$i]->getLogin ();
		
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
		
			if ( $ev[$i]->getPriority () == 3 )
				$ev_ret .= '<strong>';
		
			$cloneStr = $ev[$i]->getClone ();
			$eid = $ev[$i]->getId ();
			$linkid = 'pop' . "$eid-$key";
			$name = $ev[$i]->getName ();
			$view_text = ( $cal_type == 'task' ? $viewTaskStr : $viewEventStr );
		
			$key++;
		
			// Build entry link if UAC permits viewing.
			if ( $can_view != 0 && $time_only != 'Y' ) {
				// Make sure clones have parents URL date.
				$href = 'href="view_entry.php?eid=' . $eid . '&amp;date='
				 . ( $cloneStr ? $cloneStr : $date )
				 . ( strlen ( $user ) > 0
					? '&amp;user=' . $user
					: ( $class == 'layerentry' ? '&amp;user=' . $loginStr : '' ) ) . '"';
				$title = ' title="' . $view_text . '" ';
			} else
				$href = $title = '';
		
			$ev_ret .= '<div id="ev' . $eid . '"><a ' . $title 
				. ' class="' . $class . '" id="' . "$linkid\" $href" . '><img src="';
		
			$catNum = abs ( $ev[$i]->getCategory () );
			$icon = $cal_type . '.gif';
			if ( $catNum > 0 ) {
				$catIcon = 'icons/cat-' . $catNum . '.gif';
		
				if ( ! file_exists ( $catIcon ) )
					$catIcon = '';
			}
		
			if ( empty ( $catIcon ) )
				$ev_ret .= 'images/' . $icon . '" class="bullet" alt="' . $view_text
				 . '" width="5" height="7" />';
			else {
				// Use category icon.
				$catAlt = ( empty ( $categories[$catNum] )
					? '' : translate ( 'Category' ) . ': '
					 . $categories[$catNum]['cat_name'] );
		
				$ev_ret .= $catIcon . '" alt="' . $catAlt . '" title="' . "$catAlt\" />";
			}
		
			if ( $WC->loginId() != $loginStr && strlen ( $loginStr ) ) {
				if ( $layers ) {
					foreach ( $layers as $layer ) {
						if ( $layer['cal_layeruser'] == $loginStr ) {
							$in_span = true;
							$ev_ret .= ( '<span style="color:' . $layer['cal_color'] . ';">' );
						}
					}
				}
				// Check to see if Category Colors are set.
			} else
			if ( ! empty ( $categories[$catNum]['cat_color'] ) ) {
				$cat_color = $categories[$catNum]['cat_color'];
				if ( $cat_color != '#000000' ) {
					$in_span = true;
					$ev_ret .= ( '<span style="color:' . $cat_color . ';">' );
				}
			}
		
			if ( $ev[$i]->isAllDay () )
				$timestr = $popup_timestr = translate ( 'All day event' );
			else
			if ( ! $ev[$i]->isUntimed () ) {
				$timestr = $popup_timestr = 
				smarty_modifier_display_time( $ev[$i]->getDate() );
				if ( $ev[$i]->getDuration () > 0 )
					$popup_timestr .= ' - ' 
					. smarty_modifier_display_time ( $ev[$i]->getEndDate () );
		
				if ( getPref ( 'DISPLAY_END_TIMES' ) )
					$timestr = $popup_timestr;
		
				if ( $cal_type == 'event' )
				 $ev_ret .= getShortTime ( $timestr )
					 . ( $time_only == 'Y' ? '' : getPref ( 'TIME_SPACER' ) );
			}
			
			$ev_ret .= build_entry_label ( $ev[$i], 'eventinfo-' . $linkid, $can_view,
				$popup_timestr, $time_only );	
				
			// Added to allow a small location to be displayed if wanted.
			$ev_ret .= ( ! empty ( $location ) && getPref ( 'DISPLAY_LOCATION' )
				? '<br /><span class="location">('
				 . htmlspecialchars ( $location ) . ')</span>' : '' )
			 . ( $in_span == true ? '</span>' : '' ) . '</a>'
			 . ( $ev[$i]->getPriority () == 3 ? '</strong>' : '' ) // end font-weight span
			. '</div>';
		if ( $CONTEXTMENU ) {	
		  $context_ret .= "contextKiller(\"ev{$eid}\");";
			
			if (  $ev[$i]->getStatus () == 'W' ) {
				$context_ret .= 'contextNew (\''
				  .$eid.'\', \'approve\', \''.$approveStr.'\');';
				
				$context_ret .= 'contextNew (\''
				  .$eid.'\', \'delete\', \''.$deleteStr.'\');'; 
			}
			 
			if ( $can_view ) {
				 $context_ret .= 'contextNew(\''
				   .$eid.'\', \'view\', \''.$viewEventStr.'\');';
			}
				 
			if ( $can_edit ) {
				 $context_ret .= 'contextNew(\''
				   .$eid.'\',\'edit\', \''.$editStr.'\');';
			}
		} //$CONTEXTMENU
	  }
  }
	if ( ! empty ( $ev_ret ) ) {
	  $ret .= "eDiv = document.getElementById('td{$date}');";
    $ret .= "eDiv.innerHTML = '{$ev_ret}';";
		$ret .= $context_ret;
  }
  return $ret;
}


?>
