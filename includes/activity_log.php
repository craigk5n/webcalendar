<?php
/* $Id$
 *
 * Description:
 *  The data generator functions to display the activity log
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
defined ( '_ISVALID' ) or die ( 'You cannot access this file directly!' );

$startid = $WC->getValue ( 'startid', '-?[0-9]+', true );
$sys = ( $WC->isAdmin() && $WC->getGET ( 'system', false ) != '' );
$smarty->assign ( 'system', ( $sys ? '&amp;system=1' : '' ) );

$smarty ->assign ( 'PAGE_SIZE', $PAGE_SIZE );
$eid = $WC->getId();

$nextpage = '';

//Get activity log data  
$sql_params = $log_data = array ();
if ( ! empty ( $eid ) )
  $sql_params[] = $eid;

$sql_params[] = $startid;

$sql = 'SELECT wel.cal_login_id, wel.cal_owner_id, wel.cal_type, wel.cal_date,
  wel.cal_text, '
  . ( $sys
  ? 'wel.cal_log_id FROM webcal_entry_log wel WHERE wel.cal_entry_id = 0'
  : 'we.cal_id, we.cal_name, wel.cal_log_id, we.cal_type
  FROM webcal_entry_log wel, webcal_entry we
  WHERE wel.cal_entry_id = we.cal_id' )
  . ( ! empty ( $eid ) ? ' AND we.cal_id = ?' : '' )
  . ( ! empty ( $startid ) ? ' AND wel.cal_log_id <= ?' : '' )
  . ' ORDER BY wel.cal_log_id DESC';

$res = dbi_execute ( $sql, $sql_params );

if ( $res ) {
  $num = 0;
  while ( $row = dbi_fetch_row ( $res ) ) {
    $log_data[$num]['l_login'] = $row[0];
    $log_data[$num]['l_owner'] = $row[1];
    $log_data[$num]['l_type'] = $row[2];
    $log_data[$num]['l_date'] = $row[3];
    //$log_data[$num]['l_text'] = $row[4];

    if ( $sys )
      $log_data[$num]['l_id'] = $l_id = $row[5];
    else {
      $log_data[$num]['l_eid'] = $row[5];
      $log_data[$num]['l_ename'] = $row[6];
      $log_data[$num]['l_id'] = $l_id = $row[7];
      $log_data[$num]['l_etype'] =  $row[8];
    }
			
	  $log_data[$num]['actionFullname'] = $WC->User->getFullName ( $row[0]);
	  $log_data[$num]['ownerFullname'] = $WC->User->getFullName ( $row[1] );	
		
		$log_data[$num]['log_data'] = log_data ( $row[2], $row[4] );		
    $num++;
    if ( $num > $PAGE_SIZE ) {
      $nextpage = $l_id;
      break;
    }
  }
  dbi_free_result ( $res );
	$smarty->assign ( 'log_data', $log_data );
}


/* Display a text for a single activity log entry.
 *
 * @param string $cal_type  the log entry type
 * @param string $cal_text  addiitonal text to display
 *
 * @return string  HTML for one log entry.
 */
function log_data ( $cal_type, $cal_text = '' ) {
  if ( $cal_type == LOG_APPROVE )
    $ret = translate ( 'Event approved' );
  elseif ( $cal_type == LOG_APPROVE_J )
    $ret = translate ( 'Journal approved' );
  elseif ( $cal_type == LOG_APPROVE_T )
    $ret = translate ( 'Task approved' );
  elseif ( $cal_type == LOG_ATTACHMENT )
    $ret = translate ( 'Attachment' );
  elseif ( $cal_type == LOG_COMMENT )
    $ret = translate ( 'Comment' );
  elseif ( $cal_type == LOG_CREATE )
    $ret = translate ( 'Event created' );
  elseif ( $cal_type == LOG_CREATE_J )
    $ret = translate ( 'Journal created' );
  elseif ( $cal_type == LOG_CREATE_T )
    $ret = translate ( 'Task created' );
  elseif ( $cal_type == LOG_DELETE )
    $ret = translate ( 'Event deleted' );
  elseif ( $cal_type == LOG_DELETE_J )
    $ret = translate ( 'Journal deleted' );
  elseif ( $cal_type == LOG_DELETE_T )
    $ret = translate ( 'Task deleted' );
  elseif ( $cal_type == LOG_LOGIN_FAILURE )
    $ret = translate ( 'Invalid login' );
  elseif ( $cal_type == LOG_NEWUSER_EMAIL )
    $ret = translate ( 'New user via email (self registration)' );
  elseif ( $cal_type == LOG_NEWUSER_FULL )
    $ret = translate ( 'New user (self registration)' );
  elseif ( $cal_type == LOG_NOTIFICATION )
    $ret = translate ( 'Notification sent' );
  elseif ( $cal_type == LOG_REJECT )
    $ret = translate ( 'Event rejected' );
  elseif ( $cal_type == LOG_REJECT_J )
    $ret = translate ( 'Journal rejected' );
  elseif ( $cal_type == LOG_REJECT_T )
    $ret = translate ( 'Task rejected' );
  elseif ( $cal_type == LOG_REMINDER )
    $ret = translate ( 'Reminder sent' );
  elseif ( $cal_type == LOG_UPDATE )
    $ret = translate ( 'Event updated' );
  elseif ( $cal_type == LOG_UPDATE_J )
    $ret = translate ( 'Journal updated' );
  elseif ( $cal_type == LOG_UPDATE_T )
    $ret = translate ( 'Task updated' );
  elseif ( $cal_type == LOG_USER_ADD )
    $ret = translate ( 'Add User' );
  elseif ( $cal_type == LOG_USER_DELETE )
    $ret = translate ( 'Delete User' );
  elseif ( $cal_type == LOG_USER_UPDATE )
    $ret = translate ( 'Edit User' );
  else
    $ret = '???';

  return $ret
   . ( ! empty ( $cal_text ) ? '<br/>&nbsp;' 
	 . htmlentities ( $cal_text ) : '' );
}
?>
