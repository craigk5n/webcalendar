<?php
/*
 * $Id$
 *
 * Description:
 *  Display either the "Activity Log" (for events/tasks) or the
 *  "System Log" (entries not associated with an event)
 *
 * Input Parameters:
 *  startid - specified the id of the first log entry to display
 *  system - if specified, then view the system log (entries with no
 *    event id associated with them) rather than the event log.
 *
 * Security:
 *  User must be an admin user
 *  AND, if user access control is enabled, they must have access to
 *  activity logs.  (This is because users may see event details
 *  for other groups that they are not supposed to have access to.)
 */
include_once 'includes/init.php';

$PAGE_SIZE = 25; // number of entries to show at once

if ( ! $is_admin || ( access_is_enabled () &&
  ! access_can_access_function ( ACCESS_ACTIVITY_LOG ) ) ) {
  die_miserable_death ( print_not_auth () );
}

$sys = ( $is_admin && getGetValue ( 'system' ) != '' );

print_header();

$startid = getIntValue ( 'startid' );

if ( $sys ) {
  echo '<h2>' . translate( 'System Log' ) . '</h2>';
} else {
  echo '<h2>' . translate( 'Activity Log' ) . '</h2>';
}

echo display_admin_link();

echo "<table class=\"embactlog\">\n";
echo "<tr><th class=\"usr\">\n" .
  translate( 'User' ) . "</th><th class=\"cal\">\n" .
  translate( 'Calendar' ) . "</th><th class=\"scheduled\">\n" .
  translate( 'Date' ) . "/" . translate( 'Time' ) . "</th>" .
  ( $sys ? '' : "<th class=\"dsc\">\n" .  translate( 'Event' ) . "</th>" ) .
  "<th class=\"action\">\n" .
  translate( 'Action' ) . "\n</th></tr>\n";

$startid = getIntValue ( 'startid', true );

if ( $sys ) {
  $sql = 'SELECT cal_login, cal_user_cal, cal_type, cal_date, ' .
    'cal_time, cal_text, cal_log_id ' .
    'FROM webcal_entry_log ' .
    'WHERE webcal_entry_log.cal_entry_id = 0';
  if ( ! empty ( $startid ) )
    $sql .= " AND cal_log_id <= $startid";
  $sql .= ' ORDER BY cal_log_id DESC';
} else {
  $sql = 'SELECT webcal_entry_log.cal_login, webcal_entry_log.cal_user_cal, ' .
    'webcal_entry_log.cal_type, webcal_entry_log.cal_date, ' .
    'webcal_entry_log.cal_time, webcal_entry_log.cal_text, webcal_entry.cal_id, ' .
    'webcal_entry.cal_name, webcal_entry_log.cal_log_id, webcal_entry.cal_type ' .
    'FROM webcal_entry_log, webcal_entry ' .
    'WHERE webcal_entry_log.cal_entry_id = webcal_entry.cal_id';
  if ( ! empty ( $startid ) )
    $sql .= " AND webcal_entry_log.cal_log_id <= $startid ";
  $sql .= ' ORDER BY webcal_entry_log.cal_log_id DESC';
}

$res = dbi_execute ( $sql );

$nextpage = '';

if ( $res ) {
  $num = 0;
  while ( $row = dbi_fetch_row ( $res ) ) {
    if ( $sys ) {
      $l_login = $row[0];
      $l_user = $row[1];
      $l_type = $row[2];
      $l_date = $row[3];
      $l_time = $row[4];
      $l_text = $row[5];
      $l_id = $row[6];
    } else {
      $l_login = $row[0];
      $l_user = $row[1];
      $l_type = $row[2];
      $l_date = $row[3];
      $l_time = $row[4];
      $l_text = $row[5];
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
      echo '<tr';
      if ( $num % 2 ) {
        echo ' class="odd"';
      }
      $view_link = 'view_entry';      
      echo "><td>\n" .
      $l_login . "</td><td>\n" .
      $l_user . "</td><td>\n" . 
      date_to_str ( $l_date ) . '&nbsp;' ;
      // Added TZ conversion
      $use_gmt = ( ! empty ( $GENERAL_USE_GMT ) && $GENERAL_USE_GMT == 'Y' ? 3 : 2 );
      echo display_time ( $l_date . $l_time, $use_gmt ) ;
      echo "</td><td>\n";
      if ( ! $sys ) {
        echo '<a title="' .
        htmlspecialchars($l_ename) . "\" href=\"$view_link.php?id=$l_eid\">" .
        htmlspecialchars($l_ename) . "</a></td><td>\n";
      }
      echo display_activity_log ( $l_type, $l_text );
      echo "\n</td></tr>\n";
    }
  }
  dbi_free_result ( $res );
} else {
  echo db_error ();
}
?>
</table>
<div class="navigation">
<?php
//go BACK in time
if ( ! empty ( $nextpage ) ) {
  $sysL = ( $sys ? "&amp;system=1" : '' );
  echo '<a title="' . 
    translate( 'Previous' ) . "&nbsp;$PAGE_SIZE&nbsp;" . 
    translate ( 'Events' ) .
    "\" class=\"prev\" href=\"activity_log.php?startid=$nextpage$sysL\">" . 
    translate( 'Previous' ) . "&nbsp;$PAGE_SIZE&nbsp;" . 
    translate ( 'Events' ) . "</a>\n";
}

if ( ! empty ( $startid ) ) {
  $previd = $startid + $PAGE_SIZE;
  $res = dbi_execute ( 'SELECT MAX(cal_log_id) FROM webcal_entry_log' );
  if ( $res ) {
    if ( $row = dbi_fetch_row ( $res ) ) {
      if ( $row[0] <= $previd ) {
        $prevarg = ( $sys ? "?system=1" : '' );
      } else {
        $prevarg = "?startid=$previd" . ( $sys ? "&amp;system=1" : '' );
      }
      //go FORWARD in time
      echo '<a title="' .  translate( 'Next' ) . "&nbsp;$PAGE_SIZE&nbsp;" . 
        translate ( 'Events' ) .
        "\" class=\"next\" href=\"activity_log.php$prevarg\">" . 
        translate( 'Next' ) . "&nbsp;$PAGE_SIZE&nbsp;" . 
        translate ( 'Events' ) . "</a><br />\n";
    }
    dbi_free_result ( $res );
  }
}
?>
</div>
<?php echo print_trailer(); ?>

