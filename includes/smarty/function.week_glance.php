<?php
/* Generates the HTML for an event to be viewed in the week-at-glance (week.php).
 *
 * The HTML will be stored in an array (global variable $hour_arr)
 * indexed on the event's starting hour.
 *
 * @param Event  $event           The event
 * @param string $date            Date for which we're printing (in YYYYMMDD format)
 * @param string $override_class  If set, then this is the class to use
 * @param bool   $show_time       If enabled, then event time is displayed
 */
function smarty_function_week_glance ( $params, &$smarty ) {
  global $categories, $DISPLAY_TZ, $first_slot,
  $hour_arr, $last_slot, $layers, $WC,
  $rowspan, $rowspan_arr, $user;
  static $key = 0;

  $event = $params['event'];
	$date  = $params['date'];
  $override_class = ( ! empty ( $params['override_class'] ) ?
	  $params['override_class'] : '' );
	$show_time = ( ! empty ( $params['show_time'] ) ?
	  $params['show_time'] : '' );

  $can_access = CAN_DOALL;
  $catAlt = $href = $timestr = '';
  $getCalTypeName = $event->getCalTypeName ();
  $getCat = abs ( $event->getCategory () );
  $getClone = $event->getClone ();
  $getDatetime = $event->getDatetime ();
  $getLoginStr = $event->getLoginId ();
  $getPri = $event->getPriority ();
  $eid = $event->getId ();
  $ind = 9999;
  $isAllDay = $event->isAllDay ();
  $isUntime = $event->isUntimed ();
  $linkid = "pop$eid-$key";
  $name = $event->getName ();
  $time_only = 'N';
  $title = '<a title="';

  $catIcon = 'icons/cat-' . $getCat . '.gif';
  $key++;

  $can_access = access_user_calendar ( 'view', $getLoginStr, '',
    $event->getCalType (), $event->getAccess () );
  $time_only = access_user_calendar ( 'time', $getLoginStr );
  if ( $getCalTypeName == 'task' && $can_access == 0 )
    return false;

  // Figure out which time slot it goes in.  Put tasks in with AllDay and Untimed.
  if ( ! $isUntime && ! $isAllDay && $getCalTypeName != 'task' ) {
    $tz_time = date ( 'His', $event->getDate () );
    $ind = calc_time_slot ( $tz_time );
    if ( $ind < $first_slot )
      $first_slot = $ind;

    if ( $ind > $last_slot )
      $last_slot = $ind;
  }

  $class = ( $WC->loginId() != $getLoginStr && strlen ( $getLoginStr )
    ? 'layer' : ( $event->getStatus () == 'W' ? 'unapproved' : '' ) . 'entry' );
  // If we are looking at a view, then always use "entry".
  if ( defined ( '_WC_CUSTOM_VIEW' ) )
    $class = 'entry';

  if ( ! empty ( $override_class ) )
    $class .= ' ' . $override_class;

  // Avoid PHP warning for undefined array index.
  if ( empty ( $hour_arr[$ind] ) )
    $hour_arr[$ind] = '';

  if ( $getCat > 0 && file_exists ( $catIcon ) ) {
    $catAlt = translate ( 'Category' ) . ': ' . $categories[$getCat]['cat_name'];
    $hour_arr[$ind] .= '<img src="' . $catIcon . '" alt="' . $catAlt
     . '" title="' . $catAlt . '" />';
  }

  // Build entry link if UAC permits viewing.
  if ( $can_access != 0 && $time_only != 'Y' ) {
    // Make sure clones have parents URL date.
    $href = 'href="view_entry.php?eid=' . $eid . '&amp;date='
     . ( $getClone ? $getClone : $date ) . '"';
    if ( $getCalTypeName == 'task' ) {
      $hour_arr[$ind] .= '<img src="images/task.gif" class="bullet" alt="*" /> ';
      $title .= translate ( 'View this task' );
    } else { // Must be event.
      if ( $isAllDay || $isUntime && $catAlt == '' )
        $hour_arr[$ind] .= '<img src="images/circle.gif" class="bullet" alt="*" /> ';

      $title .= translate ( 'View this event' );
    }
  }

  $hour_arr[$ind] .= $title . '" class="' . $class . '" id="' . $linkid . '" '
   . $href . ( strlen ( $GLOBALS['user'] ) > 0
    ? '&amp;user=' . $GLOBALS['user']
    : ( $class == 'layerentry' ? '&amp;user=' . $getLoginStr : '' ) ) . '">'
   . ( $getPri == 3 ? '<strong>' : '' );

  if ( $WC->loginId() != $getLoginStr && strlen ( $getLoginStr ) ) {
    if ( $layers ) {
      foreach ( $layers as $layer ) {
        if ( $layer['cal_layeruse_id'] == $getLoginStr ) {
          $hour_arr[$ind] .= '<span style="color:' . $layer['cal_color'] . ';">';
          $in_span = true;
        }
      }
    }
    // Check to see if Category Colors are set.
  } else
  if ( ! empty ( $categories[$getCat]['cat_color'] ) ) {
    $cat_color = $categories[$getCat]['cat_color'];
    if ( $cat_color != '#000000' ) {
      $hour_arr[$ind] .= '<span style="color:' . $cat_color . ';">';
      $in_span = true;
    }
  }
  if ( $isAllDay ) {
    $timestr = translate ( 'All day event' );
    // Set start cell of all-day event to beginning of work hours.
    if ( empty ( $rowspan_arr[$first_slot] ) )
      $rowspan_arr[$first_slot] = 0; // Avoid warning below.
    // We'll skip tasks here as well.
  } else
  if ( ! $event->isUntimed() && $getCalTypeName != 'task' ) {
    if ( $show_time )
      $hour_arr[$ind] .= display_time ( $getDatetime )
       . ( $time_only == 'Y' ? '' : getPref ( 'TIME_SPACER' ) );

    $timestr = display_time ( $getDatetime );
    if ( $event->getDuration () > 0 ) {
      $end_time = date ( 'His', $event->getEndDate () );
      $timestr .= '-' . display_time ( $event->getEndDate (), $DISPLAY_TZ );
      // This fixes the improper display if an event ends at or after midnight.
      if ( $end_time < $tz_time )
        $end_time += 240000;
    } else
      $end_time = 0;

    if ( empty ( $rowspan_arr[$ind] ) )
      $rowspan_arr[$ind] = 0; // Avoid warning below.

    // Which slot is end time in? take one off so we don't
    // show 11:00-12:00 as taking up both 11 and 12 slots.
    $endind = calc_time_slot ( $end_time, true );
    $rowspan = ( $endind == $ind ? 0 : $endind - $ind + 1 );

    if ( $rowspan > $rowspan_arr[$ind] && $rowspan > 1 )
      $rowspan_arr[$ind] = $rowspan;
  }

  $hour_arr[$ind] .= build_entry_label ( $event, 'eventinfo-' . $linkid,
    $can_access, $timestr, $time_only )
   . ( empty ( $in_span ) ? '' : '</span>' )// End color span.
   . ( $getPri == 3 ? '</strong>' : '' ) . '</a>'
  // . ( getPref ( 'DISPLAY_ICONS' ) ? icon_text ( $eid, true, true ) : '' )
  . "<br />\n";
}

?>
