<?php // $Id: week_details.php,v 1.78 2009/11/22 22:26:18 bbannon Exp $
include_once 'includes/init.php';
send_no_cache_header();

load_user_layers ( $user != $login && $is_nonuser_admin ? $user : '' );
load_user_categories();

$next = mktime ( 0, 0, 0, $thismonth, $thisday + 7, $thisyear );
$prev = mktime ( 0, 0, 0, $thismonth, $thisday - 7, $thisyear );

$wkstart = get_weekday_before ( $thisyear, $thismonth, $thisday + 1 );

$start_ind = 0;
$thisdate = date ( 'Ymd', $wkstart );
$wkend = $wkstart + ( 86400 * ( $DISPLAY_WEEKENDS == 'N' ? 5 : 7 ) );

if ( $DISPLAY_WEEKENDS == 'N' ) {
  if ( $WEEK_START == 1 )
    $end_ind = 4;
  else {
    $start_ind = 1;
    $end_ind = 5;
  }
} else
  $end_ind = 6;

$printerStr = generate_printer_friendly ( 'week_details.php' );

/* Pre-Load the repeated events for quckier access. */
$repeated_events = read_repeated_events ( ( strlen ( $user )
  ? $user : $login ), $wkstart, $wkend, $cat_id );

/* Pre-load the non-repeating events for quicker access. */
$events = read_events ( ( strlen ( $user )
  ? $user : $login ), $wkstart, $wkend, $cat_id );

if ( $WEEK_START == 0 && $DISPLAY_WEEKENDS == 'N' )
  $wkstart = $wkstart - 86400;

for ( $i = 0; $i < 7; $i++ ) {
  $days[$i] = ( $wkstart + 86400 * $i ) + 43200;
  $weekdays[$i] = weekday_name ( ( $i + $WEEK_START ) % 7, $DISPLAY_LONG_DAYS );
  $header[$i] = $weekdays[$i] . ' '
   . date_to_str ( date ( 'Ymd', $days[$i] ), $DATE_FORMAT_MD, false );
}

$nextStr = translate ( 'Next' );
$newEntryStr = translate ( 'New Entry' );
$prevStr = translate ( 'Previous' );

print_header( array( 'js/popups.js/true' ), generate_refresh_meta() );
echo '
    <div class="title">
      <a title="' . $prevStr . '" class="prev" href="week_details.php?' . $u_url
 . 'date=' . date ( 'Ymd', $prev ) . $caturl
 . '"><img src="images/leftarrow.gif" alt="' . $prevStr . '" /></a>
      <a title="' . $nextStr . '" class="next" href="week_details.php?' . $u_url . 'date='
 . date ( 'Ymd', $next ) . $caturl
 . '"><img src="images/rightarrow.gif" alt="' . $nextStr . '" /></a>
      <span class="date">' . date_to_str ( date ( 'Ymd', $wkstart ), '', false )
 . '&nbsp;&nbsp;&nbsp; - &nbsp;&nbsp;&nbsp;'
 . date_to_str ( date ( 'Ymd', $wkend ), '', false ) . '</span>'
 . ( $DISPLAY_WEEKNUMBER == 'Y' ? '<br />
      <span class="titleweek">(' . translate ( 'Week' ) . ' '
   . date ( 'W', $wkstart + 86400 ) . ')</span>' : '' ) . '
      <span class="user">' . ( $single_user == 'N' ? '<br />
      ' . $user_fullname : '' ) . ( $is_nonuser_admin ? '<br />-- '
   . translate ( 'Admin mode' ) . ' --' : '' ) . ( $is_assistant ? '<br />-- '
   . translate ( 'Assistant mode' ) . ' --' : '' ) . '</span>'
 . ( $CATEGORIES_ENABLED == 'Y' ? '<br /><br />'
   . print_category_menu( 'week', sprintf ( "%04d%02d%02d", $thisyear,
      $thismonth, $thisday ), $cat_id ) : '' ) . '
    </div><br />
    <center>
      <table class="main">';

$untimed_found = false;
for ( $d = 0; $d < 7; $d++ ) {
  $date = date ( 'Ymd', $days[$d] );
  $thiswday = date ( 'w', $days[$d] );
  $is_weekend = ( $thiswday == 0 || $thiswday == 6 );
  if ( $is_weekend && $DISPLAY_WEEKENDS == 'N' )
    continue;

  $class = ( $date == date ( 'Ymd', $today )
    ? ' class="today">'
    : ( $is_weekend ? ' class="weekend">' : '>' ) );
  echo '
        <tr>
          <th' . $class . ( $can_add ? '
            <a title="' . $newEntryStr . '" href="edit_entry.php?' . $u_url
     . 'date=' . date ( 'Ymd', $days[$d] )
     . '"><img src="images/new.gif" class="new" alt="' . $newEntryStr
     . '" /></a>' : '' ) . '
            <a title="' . $header[$d] . '" href="day.php?' . $u_url . 'date='
   . date ( 'Ymd', $days[$d] ) . $caturl . '">' . $header[$d] . '</a>
          </th>
        </tr>
        <tr>
          <td' . $class;
  print_det_date_entries ( $date, $user, true );
  echo '&nbsp;
          </td>
        </tr>';
}

echo '
      </table>
    </center>
    ' . ( empty ( $eventinfo ) ? '' : $eventinfo ) . '<br />' . $printerStr . print_trailer ();

/**
 * Prints the HTML for one event in detailed view.
 *
 * @param Event  $event The event
 * @param string $date  The date for which we're printing (in YYYYMMDD format)
 */
function print_detailed_entry ( $event, $date ) {
  global $eventinfo, $layers, $login, $user;
  static $key = 0;

  $descStr = $event->getDescription();
  $evAccessStr = $event->getAccess();
  $evPri = ( $event->getPriority() < 4 );
  $getExtStr = $event->getExtForID();
  $loginStr = $event->getLogin();
  $name = $event->getName();

  $class = ( $login != $loginStr && strlen ( $loginStr )
    ? 'layer' : ( $event->getStatus() == 'W' ? 'unapproved' : '' ) ) . 'entry';

  if ( $getExtStr != '' ) {
    $id = $getExtStr;
    $name .= ' (' . translate ( 'cont.' ) . ')';
  } else
    $id = $event->getID();

  $linkid = 'pop' . "$id-$key";
  $key++;

  echo ( $evPri ? '
            <strong>' : '' ) . '
            <a title="' . translate ( 'View this entry' ) . '" class="' . $class
   . '" id="' . $linkid . '" href="view_entry.php?id=' . $id
   . '&amp;date=' . $date;

  if ( strlen ( $user ) > 0 )
    echo '&amp;user=' . $user;
  else
  if ( $class == 'layerentry' )
    echo '&amp;user=' . $loginStr;

  echo '<img src="images/circle.gif" class="bullet" alt="view icon" />';
  if ( $login != $loginStr && strlen ( $loginStr ) ) {
    if ( $layers ) {
      foreach ( $layers as $layer ) {
        if ( $layer['cal_layeruser'] == $loginStr ) {
          $in_span = true;
          echo '
              <span style="color:#' . $layer['cal_color'] . ';">';
        }
      }
    }
  }

  $timestr = '';

  if ( $event->isAllDay() )
    $timestr = translate ( 'All day event' );
  else
  if ( $event->getDuration() > 0 ) {
    $timestr = display_time ( $event->getDateTime() ) . ' - '
     . display_time ( $event->getEndDateTime() );

    echo $timestr . '&raquo;&nbsp;';
  }

  if ( $login != $user && $evAccessStr == 'R' && strlen ( $user ) )
    $PN = $PD = '(' . translate ( 'Private' ) . ')';
  elseif ( $login != $loginStr && $evAccessStr == 'R' &&
    strlen ( $loginStr ) )
    $PN = $PD = '(' . translate ( 'Private' ) . ')';
  elseif ( $login != $loginStr && strlen ( $loginStr ) ) {
    $PN = htmlspecialchars ( $name );
    $PD = activate_urls ( htmlspecialchars ( $descStr ) );
  } else {
    $PN = htmlspecialchars ( $name );
    $PD = activate_urls ( htmlspecialchars ( $descStr ) );
  }
  if ( ! empty ( $in_span ) )
    $PN .= '</span>';

  echo $PN . '</a>' . ( $evPri ? '
            </strong>' : '' )
  # Only display description if it is different than the event name.
  . ( $PN != $PD ? ' - ' . $PD : '' ) . '<br />';

  $eventinfo .= build_entry_popup ( 'eventinfo-' . $linkid, $loginStr,
    $descStr, $timestr, site_extras_for_popup ( $id ) );
}

/**
 * Print all the calendar entries for the specified user for the specified date.
 * If we are displaying data from someone other than the logged in user,
 * then check the access permission of the entry.
 *
 *  @param string $date   - date in YYYYMMDD format
 *  @param string $user   - username
 *  @param bool   $is_ssi - is this being called from week_ssi.php?
 */
function print_det_date_entries ( $date, $user, $ssi ) {
  global $events, $is_admin, $readonly;

  $date = mktime ( 0, 0, 0, substr ( $date, 4, 2 ),
    substr ( $date, 6, 2 ), substr ( $date, 0, 4 ) );

  // Get and sort all the repeating and non-repeating events for this date.
  $ev = combine_and_sort_events ( get_entries ( $date ),
    get_repeating_entries ( $user, $date ) );
  for ( $i = 0, $cnt = count ( $ev ); $i < $cnt; $i++ ) {
    if ( ( ! empty ( $DISPLAY_UNAPPROVED ) && $DISPLAY_UNAPPROVED != 'N' ) ||
      $ev[$i]->getStatus() == 'A' )
      print_detailed_entry ( $ev[$i], $date );
  }
}

?>
