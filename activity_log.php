<?php
/* $Id$
 *
 * Description:
 *  Display either the "Activity Log" (for events/tasks) or the
 *  "System Log" (entries not associated with an event).
 *
 * Input Parameters:
 *  startid - specified the id of the first log entry to display
 *  system - if specified, then view the system log (entries with no
 *           event id associated with them) rather than the event log.
 *
 * Security:
 *  User must be an admin user
 *  AND, if user access control is enabled, they must have access to
 *  activity logs. (This is because users may see event details
 *  for other groups that they are not supposed to have access to.)
 */
include_once 'includes/init.php';

if ( ! $is_admin || ( access_is_enabled () && !
      access_can_access_function ( ACCESS_ACTIVITY_LOG ) ) )
  die_miserable_death ( print_not_auth () );

$PAGE_SIZE = 25; // number of entries to show at once
$startid = getIntValue ( 'startid', true );
$sys = ( $is_admin && getGetValue ( 'system' ) != '' );

$evntStr = translate ( 'Events' );
$nextStr = translate ( 'Next' );
$prevStr = translate ( 'Previous' );

print_header ();
echo '
    <h2>' . ( $sys ? translate ( 'System Log' ) : translate ( 'Activity Log' ) )
 . '</h2>
    ' . display_admin_link () . '
    <table class="embactlog">
      <tr>
        <th class="usr">' . translate ( 'User' ) . '</th>
        <th class="cal">' . translate ( 'Calendar' ) . '</th>
        <th class="scheduled">' . translate ( 'Date' ) . '/'
 . translate ( 'Time' ) . '</th>' . ( $sys ? '' : '
        <th class="dsc">' . translate ( 'Event' ) . '</th>' ) . '
        <th class="action">' . translate ( 'Action' ) . '</th>
      </tr>';

$res = dbi_execute ( 'SELECT wel.cal_login, wel.cal_user_cal, wel.cal_type,
  wel.cal_date, wel.cal_time, wel.cal_text, '
   . ( $sys
    ? 'wel.cal_log_id FROM webcal_entry_log AS wel WHERE wel.cal_entry_id = 0'
    : 'we.cal_id, we.cal_name, wel.cal_log_id, we.cal_type
     FROM webcal_entry_log AS wel, webcal_entry AS we
     WHERE wel.cal_entry_id = we.cal_id' )
   . ( ! empty ( $startid ) ? ' AND wel.cal_log_id <= ' . $startid : '' )
   . ' ORDER BY wel.cal_log_id DESC' );

$nextpage = '';

if ( $res ) {
  ob_start ();
  $num = 0;
  while ( $row = dbi_fetch_row ( $res ) ) {
    $l_login = $row[0];
    $l_user = $row[1];
    $l_type = $row[2];
    $l_date = $row[3];
    $l_time = $row[4];
    $l_text = $row[5];

    if ( $sys )
      $l_id = $row[6];
    else {
      $l_eid = $row[6];
      $l_ename = $row[7];
      $l_id = $row[8];
      $l_etype = $row[9];
    }
    $num++;
    if ( $num > $PAGE_SIZE ) {
      $nextpage = $l_id;
      break;
    } else {
      echo '
      <tr' . ( $num % 2 ? ' class="odd"' : '' ) . '>
        <td>' . $l_login . '</td>
        <td>' . $l_user . '</td>
        <td>' . date_to_str ( $l_date ) . '&nbsp;'
       . display_time ( $l_date . $l_time,
        // Added TZ conversion
        ( ! empty ( $GENERAL_USE_GMT ) && $GENERAL_USE_GMT == 'Y' ? 3 : 2 ) )
       . '</td>
        <td>' . ( ! $sys ? '<a title="' . htmlspecialchars ( $l_ename )
         . '" href="view_entry.php?id=' . $l_eid . '">'
         . htmlspecialchars ( $l_ename ) . '</a></td>
        <td>' : '' ) . display_activity_log ( $l_type, $l_text ) . '</td>
      </tr>';
    }
  }
  ob_end_flush ();
  dbi_free_result ( $res );
} else
  echo db_error ();

echo '
    </table>
    <div class="navigation">'
// go BACK in time
 . ( ! empty ( $nextpage ) ? '
      <a title="' . $prevStr . '&nbsp;' . $PAGE_SIZE . '&nbsp;' . $evntStr
   . '" class="prev" href="activity_log.php?startid=' . $nextpage
   . ( $sys ? '&amp;system=1' : '' ) . '">' . $prevStr . '&nbsp;' . $PAGE_SIZE
   . '&nbsp;' . $evntStr . '</a>' : '' );

if ( ! empty ( $startid ) ) {
  $previd = $startid + $PAGE_SIZE;
  $res = dbi_execute ( 'SELECT MAX (cal_log_id) FROM webcal_entry_log' );
  if ( $res ) {
    if ( $row = dbi_fetch_row ( $res ) )
      // go FORWARD in time
      echo '<a title="' . $nextStr . '&nbsp;' . $PAGE_SIZE . '&nbsp;' . $evntStr
       . '" class="next" href="activity_log.php'
       . ( $row[0] <= $previd
        ? ( $sys ? '?system=1' : '' )
        : '?startid=' . $previd . ( $sys ? '&amp;system=1' : '' ) )
       . '">' . $nextStr . '&nbsp;' . $PAGE_SIZE . '&nbsp;' . $evntStr
       . '</a><br />';

    dbi_free_result ( $res );
  }
}

echo '
    </div>
    ' . print_trailer ();

?>
