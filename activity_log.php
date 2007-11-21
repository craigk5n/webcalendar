<?php
/* $Id$
 *
 * Description:
 *  Display either the "Activity Log" (for events/tasks) or the
 *  "System Log" (entries not associated with an event).
 *
 * Input Parameters:
 *  startid - specified the id of the first log entry to display
 *  system  - if specified, then view the system log (entries with no
 *            event id associated with them) rather than the event log.
 *
 * Security:
 *  User must be an admin user
 *  AND, if user access control is enabled, they must have access to
 *  activity logs. (This is because users may see event details
 *  for other groups that they are not supposed to have access to.)
 */
include_once 'includes/init.php';

if ( ! access_can_access_function ( ACCESS_ACTIVITY_LOG ) )
  die_miserable_death ( print_not_auth() );
else {
  $PAGE_SIZE = 25; // Number of entries to show at once.
  include_once 'includes/activity_log.php';
}

if ( ! empty ( $nextpage ) )
  $smarty->assign ( 'prev_URL', 'activity_log.php?startid='
     . $nextpage . ( $sys ? '&amp;system=1' : '' ) );

if ( ! empty ( $startid ) ) {
  $previd = $startid + $PAGE_SIZE;
  $res = dbi_execute ( 'SELECT MAX( cal_log_id ) FROM webcal_entry_log' );
  if ( $res ) {
    if ( $row = dbi_fetch_row ( $res ) )
      $smarty->assign ( 'next_URL', ( $row[0] <= $previd
          ? ( $sys ? '?system=1' : '' )
          : '?startid=' . $previd . ( $sys ? '&amp;system=1' : '' ) ) );

    dbi_free_result ( $res );
  }
}

build_header();
$smarty->assign ( 'activity_log', true );

$smarty->display ( 'activity_log.tpl' );

?>
