<?php
/**
 * Smarty plugin
 * @package Smarty
 * @subpackage plugins
 */


/**
 * WebCalendar daily_matrix function plugin
 *
 * Type:     function<br />
 * Name:     daily_matrix<br />
 * Purpose:  Draws a daily outlook style availability 
 * grid showing events that are approved and awaiting approval.
 * @author Ray Jones
 *
 * @param string $date          Date to show the grid for
 * @param array  $participants  Which users should be included in the grid
 * @param string $popup         Not used
 *
 * @return string  HTML to display matrix.
 */
function smarty_function_daily_matrix ( $params, &$smarty ) {
  global $WC, $events, $repeated_events;
@set_time_limit(10);
  $date = $params['date'];
  $participants = $params['participants'];
  $popup = ( ! empty ( $params['popup']) ? $params['popup'] : false );
  $friendly = $WC->friendly ();

  $allAttendeesStr = translate ( 'All Attendees' );
  $busy = translate ( 'Busy' );
  $cnt = count ( $participants );
  $dateTS = date_to_epoch ( $date );
  $work_day_start_hour = getPref ( 'WORK_DAY_START_HOUR' );
  $entry_slots = getPref ( 'ENTRY_SLOTS' );
  $increment = intval ( 1440 /
    ( $entry_slots > 288 ? 288 : ( $entry_slots < 72 ? 72 : $entry_slots ) ) );
  $work_day_end_hour = getPref ( 'WORK_DAY_END_HOUR' );
  $master = array ();
  $participant_pct = '20%'; //Use percentage.
  $tentative = translate ( 'Tentative' );

  $hours = $work_day_end_hour - $work_day_start_hour +1;
  $interval = intval ( 60 / $increment );
  $cell_pct = intval ( 80 / ( $hours * $interval ) );
  $style_width = ( $cell_pct > 0 ? 'style="width:' . $cell_pct . '%;"' : '' );
	$cols = ( ( $hours * $interval ) + 1 );
  $ret = '
    <br />
    <table class="matrixd" cellspacing="0"
      cellpadding="0">
      <tr>
        <td class="matrix" colspan="'.$cols.'"></td>
      </tr>
      <tr>
        <th style="width:'.$participant_pct.';">'
     .translate ( 'Participants' ) . '</th>';

  // Build a master array containing all events for $participants.
  for ( $i = 0; $i < $cnt; $i++ ) {
    /* Pre-Load the repeated events for quckier access. */
    $repeated_events = read_repeated_events ( $participants[$i], $dateTS,
      $dateTS, '' );
    /* Pre-load the non-repeating events for quicker access. */
    $events = read_events ( $participants[$i], $dateTS, $dateTS );

    // Combine events for this date into a single array for easy processing.
    $ALL = array_merge (
      get_repeating_entries ( $participants[$i], $date ),
      get_entries ( $date )
      );
    foreach ( $ALL as $E ) {
      if ( $E->getDate ( 'His' ) == 0 ) {
        $duration = 60 * $hours;
        $time = $work_day_start_hour . '0000';
      } else {
        $duration = $E->getDuration ();
        $time = $E->getDate ( 'His' );
      }
      $hour = substr ( $time, 0, 2 );
      $mins = substr ( $time, 2, 2 );

      // Convert cal_time to slot.
      $slot = $hour + substr ( $mins, 0, 1 );

      // Convert cal_duration to bars.
      $bars = $duration / $increment;

      // Never replace 'A' with 'W'.
      for ( $q = 0; $bars > $q; $q++ ) {
        $slot = sprintf ( "%02.2f", $slot );
        if ( strlen ( $slot ) == 4 )
          $slot = '0' . $slot; // Add leading zeros.

        $slot = $slot . ''; // Convert to a string.
        if ( empty ( $master[-1][$slot] ) ||
            ( $master[-1][$slot]['stat'] != 'A' ) )
          $master[-1][$slot]['stat'] = $E->getStatus ();

        if ( empty ( $master[$participants[$i]][$slot] ) ||
            ( $master[$participants[$i]][$slot]['stat'] != 'A' ) ) {
          $master[$participants[$i]][$slot]['stat'] = $E->getStatus ();
          $master[$participants[$i]][$slot]['ID'] = $E->getId ();
        }
        $slot = $slot + ( $increment * .01 );
        if ( $slot - ( int )$slot >= .59 )
          $slot = ( int )$slot + 1;
      }
    }
  }
  $time_format = getPref ( 'TIME_FORMAT' );
  for( $i = $work_day_start_hour; $i <= $work_day_end_hour; $i++ ) {
    $hour = $i;
    if ( $time_format == '12' ) {
      $hour %= 12;
      if ( $hour == 0 )
        $hour = 12;
    }
    $halfway = intval ( ( $interval / 2 ) -1 );
    for( $j = 0; $j < $interval; $j++ ) {
      $inc_x_j = $increment * $j;
			$class = 'dailymatrixr';
      $str .= '
        <td id="d' . sprintf ( '%02d' , $i ) . sprintf ( '%02d', $inc_x_j ) . '" ';
      switch ( $j ) {
        case $halfway:
          $val = substr ( sprintf ( '%02d', $hour ), 0, 1 );
          break;
        case $halfway + 1:
          $val  = substr ( sprintf ( '%02d', $hour ), 1, 2 );
					$class = 'dailymatrixl';
          break;
        default:
          $val = '&nbsp;&nbsp';
      }
			$str .= 'class="' . $class . '" ' . $style_width . '>' . $val . '</td>';

    }
  }
  $ret .= $str . '
      </tr>
      <tr>
        <td class="matrix" colspan="' . $cols . '"></td>
      </tr>';

  // Add user _all_ to beginning of $participants array.
  array_unshift ( $participants, -1 );
  // Javascript for cells.
  // Display each participant.
  for ( $i = 0; $i <= $cnt; $i++ ) {
    if ( $participants[$i] != -1 ) {
      // Load full name of user.
      $user_ = $WC->User->loadVariables ( $participants[$i] );
      // Exchange space for &nbsp; to keep from breaking.
      $user_nospace = preg_replace ( '/\s/', '&nbsp;', $user_['fullname'] );
    } else
      $user_nospace = preg_replace ( '/\s/', '&nbsp;', $allAttendeesStr );

    $ret .= '
      <tr>
        <th class="row" style="width:' . $participant_pct . ';">'
     . $user_nospace . '</th>';
    $col = 1;

    // Check each timebar.
    for ( $j = $work_day_start_hour; $j <= $work_day_end_hour; $j++ ) {
      for ( $k = 0; $k < $interval; $k++ ) {
			  $inc_j_k =  $increment * $k;
        $r = sprintf ( '%02d', $j ) . '.'
         . sprintf ( '%02d', $inc_j_k ) . '';
        $space = '&nbsp;';
        if ( empty ( $master[$participants[$i]][$r] ) ) {
          // Ignore this..
        } else
        if ( empty ( $master[$participants[$i]][$r]['ID'] ) )
          // This is the first line for 'all' users.  No event here.
          $space = '
          <span class="matrix"><img src="images/pix.gif" alt="" /></span>';
        else {
          $tmpMast = $master[$participants[$i]][$r]['stat'];
          if ( strpos ( ' AW', $tmpMast ) )
            $space = '
          <a class="matrix" '. ( ! $friendly ? 'href="view_entry.php?eid='
             . $master[$participants[$i]][$r]['ID']  . '"': '' )
             . '><img src="images/pix' . ( $tmpMast = 'A' ? '' : 'b' )
             . '.gif" /></a>';
        }
        //add Participant ID to td id to keep it unique
        $ret .= '
           <td id="a' . sprintf ( '%02d', $j )
				   . sprintf ( '%02d', $inc_j_k ) . $participants[$i] .'"'
				   . ' class="matrixappts' . ( $k == '0' ? ' matrixledge' : '' ) . '" '
           . $style_width . '>' . $space . '</td>';
        $col++;
      }
    }

    $ret .= '
      </tr>
      <tr>
        <td class="matrix" colspan="' . $cols
     . '"><img src="images/pix.gif" alt="-" /></td>
      </tr>';
  } // End foreach participant.
   $ret .= '
    </table><br />
    <table align="center">
      <tr>
        <td class="matrixlegend" ><img src="images/pix.gif" title="'.$busy.'"
          alt="'.$busy.'" />&nbsp;'.$busy.'&nbsp;&nbsp;&nbsp;<img src="images/pixb.gif"
          title="'.$tentative.'" alt="'.$tentative.'" />&nbsp;'.$tentative.'</td>
      </tr>
    </table>';
  return $ret;
}

/* vim: set expandtab: */

?>
