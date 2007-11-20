<?php
/* $Id$
 *
 * Description:
 * Purge events page and handler.
 * When an event is deleted from a user's calendar, it is marked
 * as deleted (webcal_entry_user.cal_status = 'D').  This page
 * will actually clean out the database rather than just mark an
 * event as deleted.
 *
 * Security:
 * Events will only be deleted if they were created by the selected
 * user. Events where the user was a participant (but not did not
 * create) will remain unchanged.
 *
 */
include_once 'includes/init.php';

// Set this to true do show the SQL at the bottom of the page
$smarty->assign ( 'purgeDebug', false );

$sqlLog = '';

if ( ! $WC->isAdmin() ) {
  // must be admin...
  do_redirect ( 'index.php' );
  exit;
}

$ALL = 0;
$sql_params = array();


$delete = $WC->getPOST ( 'delete' );
$do_purge = false;
if ( ! empty ( $delete ) ) {
 $do_purge = true;
}

$purge_all = $WC->getPOST ( 'purge_all' );
$purge_deleted = $WC->getPOST ( 'purge_deleted' );
$end_year = $WC->getPOST ( 'end_year' );
$end_month = $WC->getPOST ( 'end_month' );
$end_day = $WC->getPOST ( 'end_day' );
$user = $WC->getPOST ( 'user' );
$preview = $WC->getPOST ( 'preview' );
$preview = ( empty ( $preview ) ? false : true );

build_header ();


if ( $do_purge ) {
  $eids = '';
  $end_date = mktime ( 0, 0, 0, $end_month, $end_day, $end_year );
  $tail = '';
  if ( $purge_deleted == 'Y' ) {
    $tail = " AND weu.cal_status = 'D' "; 
  }
  if ( $purge_all == 'Y' ) {
    if ( $user == 'ALL' ) {
      $eids = array ( 'ALL' );
    } else {
      $eids = get_event_ids ( $user, false, 'SELECT cal_id FROM webcal_entry 
			  WHERE cal_create_by = ? ' . $tail );
    }
  } elseif ( $end_date ) {
	  $sql_params[] = $end_date;
    if ( $user != 'ALL' ) {
		  $sql_params[] = $user;
      $tail = ' AND we.cal_create_by = ? ' . $tail;
    } else {
      $tail = '';
      $ALL = 1;  // Need this to tell get_ids to ignore participant check
    }
    $E_ids = get_event_ids ( $sql_params, $ALL, 'SELECT we.cal_id 
		  FROM webcal_entry we, webcal_entry_user weu
      WHERE cal_type = \'E\' AND cal_date < ? ' . $tail );
			
    $M_ids = get_event_ids (  $sql_params, $ALL, 'SELECT DISTINCT(we.cal_id) 
		  FROM webcal_entry we, webcal_entry_user weu, webcal_entry_repeats wer
      WHERE we.cal_type = \'M\' AND we.cal_id = wer.cal_id 
			AND we.cal_id = wer.cal_id AND cal_end IS NOT NULL 
			AND cal_end < ? ' . $tail );
    $eids = array_merge ( $E_ids, $M_ids );
  }
  if ( ! empty ( $eids ) ) 
	  purge_events ( $eids );
}

  $userlist = get_my_users ();
  if ( getPref ( '_ENABLE_NONUSERS' ) ) {
    $nonusers = get_nonuser_cals ();
    $userlist = ( getPref ( '_NONUSER_AT_TOP' ) ) ? array_merge($nonusers, $userlist) : array_merge($userlist, $nonusers);
  }
  for ( $i = 0, $cnt = count ( $userlist ); $i < $cnt; $i++ ) {
    $users[$userlist[$i]['cal_login_id']]['fullname'] = 
		  $userlist[$i]['cal_fullname'];
    if ( $WC->isLogin( $userlist[$i]['cal_login_id'] ) )
      $users[$userlist[$i]['cal_login_id']]['selected'] = SELECTED;
  }

$smarty->assign ( 'user', $WC->getFullName ( $user ) );
$smarty->assign ( 'do_purge', $do_purge );
$smarty->assign ( 'userlist', $users );
$smarty->assign ( 'preview', ( $preview ? translate ( 'Preview' ) : '' ) );
$smarty->display ( 'purge.tpl' );

function purge_events ( $eids ) {
  global $preview, $previewStr, $c; // db connection
  global $sqlLog, $allStr;

  $tables = array (
    array ( 'webcal_entry_user', 'cal_id' ),
    array ( 'webcal_entry_repeats', 'cal_id' ),
    array ( 'webcal_entry_exceptions', 'cal_id' ),
    array ( 'webcal_entry_log', 'cal_entry_id' ),
    array ( 'webcal_entry_categories', 'cal_id' ),
    array ( 'webcal_import_data', 'cal_id' ),
    array ( 'webcal_site_extras', 'cal_id' ),
    array ( 'webcal_reminders', 'cal_id' ),
    array ( 'webcal_entry_ext_user', 'cal_id' ),
    array ( 'webcal_blob', 'cal_id' ),
    array ( 'webcal_entry', 'cal_id' )
  );

  //var_dump($tables);exit;
  $num = array();
  $cnt = count ( $tables );
  for ( $i = 0; $i < $cnt; $i++ ) {
    $num[$i] = 0;
  }
  foreach ( $eids as $cal_id ) {
    for ( $i = 0; $i < $cnt; $i++ ) {
      $clause = ( $cal_id == 'ALL' ? '' :
        " WHERE {$tables[$i][1]} = $cal_id" );
      if ( $preview ) {
        $sql = 'SELECT COUNT(' . $tables[$i][1] .
          ") FROM {$tables[$i][0]}" . $clause;
        //echo "cal_id = '$cal_id'<br />clause = '$clause'<br />";
        //echo "$sql <br />\n";
        $res = dbi_execute ( $sql );
        $sqlLog .= $sql . "<br />\n";
        if ( $res ) {
          if ( $row = dbi_fetch_row ( $res ) )
            $num[$i] += $row[0];
          dbi_free_result ( $res );
        }
      } else {
        $sql = "DELETE FROM {$tables[$i][0]}" . $clause;
        $sqlLog .= $sql . "<br />\n";
        $res = dbi_execute ( $sql );
        if ( $cal_id == 'ALL' ) {
          $num[$i] = $allStr;
        } else {
          $num[$i] += dbi_affected_rows ( $c, $res );
        }
      }
    }
  }
  for ( $i = 0; $i < $cnt; $i++ ) {
	  $table[$i]['name'] = $tables[$i][0];
		$table[$i]['num'] = $num[$i];
  }
	$smarty->assign ( 'tables', $table );
	$smarty->assign ( 'none', $cnt == 0 );
}
?>
