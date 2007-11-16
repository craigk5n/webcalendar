<?php
/* $Id$
 * Page Description:
 * This page will present the user with forms for exporting calendar events.
 *
 * Input Parameters:
 * None
 */
include_once 'includes/init.php';
include_once 'includes/xcal.php';
 
//Handle form from export.php or view_entry.php
if ( ! empty ( $_POST ) ) {

  // Handle user
  $calUser = $WC->getValue ( 'calUser' );
  if ( empty ( $calUser ) || ( ! _WC_SINGLE_USER && ! $WC->isAdmin() ) )
    $calUser = $WC->loginId();
		
  if ( ! $WC->isLogin() )
    $layers = loadLayers ();
  
  /* ********************************** */
  /*              Let's go              */
  /* ********************************** */
  
  $format = $WC->getValue ( 'format' );
  if ( $format != 'ical' && $format != 'vcal' && $format != 'pilot-csv' &&
    $format != 'pilot-text' )
    die_miserable_death ( 'Invalid format "' . $format . '"' );
  $eid = $WC->getId ( );
  
  $use_all_dates = $WC->getPOST ( 'use_all_dates' );
  if ( strtolower ( $use_all_dates ) != 'y' )
    $use_all_dates = '';
  
  $include_layers = $WC->getPOST ( 'include_layers' );
  if ( strtolower ( $include_layers ) != 'y' )
    $include_layers = '';
  
  $include_deleted = $WC->getPOST ( 'include_deleted' );
  if ( strtolower ( $include_deleted ) != 'y' )
    $include_deleted = '';
  
  $cat_filter = $WC->getPOST ( 'cat_filter' );
  if ( $cat_filter === 0 )
    $cat_filter = '';
  
  $endday = $WC->getPOST ( 'endday', 0, '-?[0-9]+');
  $endmonth = $WC->getPOST ( 'endmonth', 0, '-?[0-9]+' );
  $endyear = $WC->getPOST ( 'endyear', 0, '-?[0-9]+');
  $fromday = $WC->getPOST ( 'fromday', 0, '-?[0-9]+' );
  $frommonth = $WC->getPOST ( 'frommonth', 0, '-?[0-9]+' );
  $fromyear = $WC->getPOST ( 'fromyear', 0, '-?[0-9]+' );
  $modday = $WC->getPOST ( 'modday', 0, '-?[0-9]+' );
  $modmonth = $WC->getPOST ( 'modmonth', 0, '-?[0-9]+' );
  $modyear = $WC->getPOST ( 'modyear', 0, '-?[0-9]+' );
  $startdate = gmmktime ( 0, 0, 0, $frommonth, $fromday, $fromyear );
  $enddate = gmmktime ( 23, 59, 59, $endmonth, $endday, $endyear );
  $moddate = gmmktime ( 0, 0, 0, $modmonth, $modday, $modyear );

  mt_srand ( ( float ) microtime () * 1000000 );
  
  if ( empty ( $eid ) )
    $eid = 'all';
  
  $outputName = 'webcalendar-' . $WC->loginId() . '-' . $eid;
  if ( substr ( $format, 0, 4 ) == 'ical' ) {
		if ( $ical_out =  export_ical ( $eid ) )
      transmit_header ( 'text/calendar', $outputName . '.ics' );
		echo $ical_out;
  } elseif ( $format == 'vcal' ) {
	  if ( $vcs_out = export_vcal ( $eid ) )
      transmit_header ( 'text/x-vCalendar', $outputName . '.vcs' );
		echo $vcs_out;
  } elseif ( $format == 'pilot-csv' ) {
	  if ( $cvs_out = export_pilot_csv ( $eid ) )
      transmit_header ( 'text/csv', $outputName . '.csv' );
    echo  $cvs_out;
  } elseif ( $format == 'pilot-text' ) {
	  if ( $datebook_out = export_install_datebook ( $eid ) )
      transmit_header ( 'text/plain', $outputName . '.txt' );
    echo $datebook_out;
  } else {
    echo '<script language="javascript" type="text/javascript">
         <!-- <![CDATA[
         alert ( \'' .  translate ( 'Error', true ) . ' ' 
        . translate ( 'export format not defined or incorrect', true ) .'\' );
        //]]> -->
        </script>';
  } //end if ($format == "ical")
	if ( ! empty ( $errorStr ) ) {
		echo '<script language="javascript" type="text/javascript">
      <!-- <![CDATA[
	   alert ( \'' . $errorStr . '\' );
		 history.back(2);
	   //]]> -->
     </script>';
		 
  }
   exit; //we don't wsant to display anthing else

} //end process POST
$datem = date ( 'm' );
$dateY = date ( 'Y' );

// Generate the selection list for calendar user selection.
// Only ask for calendar user if user is an administrator.
// We may enhance this in the future to allow
// - selection of more than one user
// - non-admin users this functionality
if ( ! _WC_SINGLE_USER && $WC->isAdmin() ) {
  $userlist = $WC->User->getUsers ();
  if ( getPref ( 'NONUSER_ENABLED' ) ) {
    $nonusers = get_nonuser_cals ();
    $userlist = ( getPref ( 'NONUSER_AT_TOP' ) ) ?
      array_merge($nonusers, $userlist) : array_merge($userlist, $nonusers);
  }
  $size = 1;
  $users = array();
	//make this user first in list
	$users[$WC->loginId()]['fullname'] = $WC->getFullName();
	$users[$WC->loginId()]['selected'] = SELECTED;
  for ( $i = 0, $cnt = count ( $userlist ); $i < $cnt; $i++ ) {
    $l = $userlist[$i]['cal_login_id'];
		if ( $l == $WC->loginId() )
		  continue;
    $size++;
    $users[$l]['fullname'] = $userlist[$i]['cal_fullname'];
  }

  if ( $size > 50 )
    $size = 15;
  else if ( $size > 5 )
    $size = 5;
	
	$smarty->assign ( 'users', $users );
  $smarty->assign ( 'size', $size );
}
	
build_header ( array ( 'export_import.js' ) );

$smarty->assign ( 'moddate', date ( 'Ymd', time() - ONE_WEEK ) );
if ( ! empty ( $categories ) )
  $smarty->assign ( 'categories', $categories );
$smarty->display ( 'export.tpl' );


//Functions below here

// Convert calendar date to a format suitable for the
// install-datebook utility (part of pilot-link)
function pilot_date_time ( $date, $csv = false ) {
  $mday = date ( 'd', $date);
  $month = date ( 'm', $date);
  $year = date ( 'Y', $date);

  $hour = date ( 'H', $date);
  $min = date ( 'i', $date);

  // All times are now stored as GMT.
  // TODO Palm uses local time, so convert to users' time.
  $tz_offset = date ( 'Z' ); // in seconds
  $tzh = intval ( $tz_offset / ONE_HOUR );
  $tzm = intval ( ( $tz_offset / 60 ) % 60 );
  $tzsign = '+';

  if ( $tzh < 0 ) {
    $tzsign = '-';
    $tzh = abs ( $tzh );
  }

 return ( $csv
  ? sprintf ( "%04d-%02d-%02d%s%02d:%02d:00",
      $year, $month, $mday, $csv, $hour, $min )
  : sprintf ( "%04d/%02d/%02d %02d%02d  GMT%s%d%02d",
      $year, $month, $mday, $hour, $min, $tzsign, $tzh, $tzm ) );
}

function export_install_datebook ( $eid ) {
  
	$ret = '';
  $res = export_get_event_entry ( $eid );

  while ( $row = dbi_fetch_row ( $res ) ) {
    $start_time = pilot_date_time ( $row[3]);
    $end_time = pilot_date_time ( $row[3] + ( $row[7] * 60 ) );
    $ret .= sprintf ( "%s\t%s\t\t%s\n", $start_time, $end_time, $row[1] );
    $ret .= 'Start time: ' . $start_time . '
End time: ' . $end_time . '
Duration: ' . $row[7] . '
Name: ' . $row[1] . "\n";
  }
	return $ret;
}

function get_cal_ent_extras ( $eid, $from, $where = false ) {
  $res = dbi_execute ( 'SELECT * FROM ' . $from . ' WHERE cal_id = ?'
     . ( $where ? ' AND ( ' . $where . ' )' : '' ), array ( $eid ) );
  return ( $res ? ( dbi_fetch_row ( $res ) ) : ( false ) );
}

function export_pilot_csv ( $eid ) {
  /* To be imported to a Palm with:
   * pilot-datebook -r csv -f webcalendar-export.txt -w hotsync
   */
  $ret = '';
	
  $res = export_get_event_entry ( $eid );

  $ret .= 'uid,attributes,category,untimed,beginDate,beginTime,endDate,endTime,'
   . 'description,note,alarm,advance,advanceUnit,repeatType,repeatForever,'
   . 'repeatEnd,repeatFrequency,repeatDay,repeatWeekdays,repeatWeekstart' . "\n";
  while ( $row = dbi_fetch_row ( $res ) ) {
    // uid (long)
    $ret .= $row[0] . ','
    /*
     attributes (int)
      128 = 0x80 : Deleted
       64 = 0x40 : Dirty
       32 = 0x20 : Busy
       16 = 0x10 : Secret/Private
     */
     . ( $row[6] == 'C' || $row[7] == 'R' ? '16,' : '0,' )
    // category (int: 0=Unfiled)
    . '0,';
    // untimed (int: 0=Appointment, 1=Untimed)
    // Note: Palm "Untimed" is WebCalendar "AllDay".
    if ( $row[7] < 0 ) {
      $ret .=
      '1,'. // untimed
      date ( 'Y', $row[3] ). '-'. // beginDate (str: YYYY-MM-DD) + beginTime
      date ( 'm', $row[3] ). '-'.
      date ( 'd', $row[3] ). ',00:00:00,'.
      date ( 'Y', $row[3] + ( $row[7] * 60 ) ). '-'. // endDate + endTime
      date ( 'm', $row[3] + ( $row[7] * 60 ) ). '-'.
      date ( 'd', $row[3] + ( $row[7] * 60 ) ). ',00:00:00,';
    } else {
      $ret .= '0,'. // untimed
      pilot_date_time ( $row[3], $row[7], 0, ',' ). ','. // beginDate,beginTime
      pilot_date_time ( $row[3], $row[7], 0, ',' ). ','; //endDate,endTime
    } //end if ( $row[4] < 0 )
    // description (str)
    $ret .= '"'. preg_replace ( '/\x0D?\n/', "\\n", $row[1] ). '",'
    // note (str)
    . '"'. preg_replace ( '/\x0D?\n/', "\\n", $row[9] ). '",';
    // alarm, advance, advanceUnit
    // alarm (int: 0=no alarm, 1=alarm)
    // FIXME: verify if WebCal. DB interpreted correctly
    // advance (int), advanceUnit (int: 0=minutes, 1=hours, 2=days)
    // FIXME: better adjust unit
    $ext = get_cal_ent_extras ( $row[0], 'webcal_reminders' );
    if ( $ext )
      $ret .= '1,'. $ext[2]. ',0,';
    else
      $ret .= '0,0,0,';
    // repeat:
    // repeatType (int: 0=none, 1=daily, 2=weekly, 3=monthly, 4=monthly/weekday,
    // repeatForever (int: 0=not forever, 1=forever)                   5=yearly)
    // repeatEnd (time)
    // repeatFrequency (int)
    // repeatDay (int: day# or 0..6=Sun..Sat 1st, 7..13 2nd, 14..20 3rd,
    // 21..27 4th,  28-34 last week)
    // repeatWeekdays (int: add - 1=Sun,2=Mon,4=Tue,8=Wed,16=Thu,32=Fri,64=Sat)
    // repeatWeekstart (int)
    $ext = get_cal_ent_extras ( $row[0], 'webcal_entry_repeats' );
    if ( $ext ) {
      switch ( $ext[1] ) {
        case 'daily':
          $repType = 1;
          break;
        case 'weekly':
          $repType = 2;
          break;
        case 'monthlyByDate':
          $repType = 3;
          break;
        case 'monthlyByDay':
          $repType = 4;
          break;
        case 'yearly':
          $repType = 5;
          break;
        default:
          $repType = 0;
      }
    } else
      $repType = 0;

    if ( $repType ) {
      $ret .= $repType . ','; // repeatType
      if ( $ext[2] ) {
        $ret .= '0,'. // repeatForever
        substr ( $ext[2], 0, 4 ). '-'. // repeatEnd
        substr ( $ext[2], 4, 2 ). '-'.
        substr ( $ext[2], 6, 2 ). ' 00:00:00,';
      } else
        $ret .= '1,,'; // repeatForever,repeatEnd

      $ret .= $ext[3]. ','; // repeatFrequency
      switch ( $repType ) {
        case 2: // weekly
          $ret .= '0,'. bindec ( strtr ( strrev ( $ext[4] ), 'yn', '10' ) ). ",1\n";
          break;
        case 3: // monthly/weekday
          // repeatDay (0..6=Sun..Sat 1st, 7..13 2nd, 14..20 3rd,
          // 21..27 4th,  28-34 last week)
          $ret .= floor ( substr ( $row[3], 6, 2 ) / 7 ) * 7 + date ( 'w',
            date_to_epoch ( $row[3] ) ). ",0,0\n";
          break;
        case 1: // daily
        case 4: // monthly
        case 5: // yearly
          $ret .= "0,0,0\n";
      } //end switch
    } else
      $ret .= "0,0,,0,0,0,0\n";
  } //end if ( $repType )
	return $ret;
}

function transmit_header ( $mime, $file ) {
  // header ( 'Content-Type: application/octet-stream' );
  header ( 'Content-Type: ' . $mime );
  header ( 'Content-Disposition: attachment; filename="' . $file . '"' );
  header ( 'Pragma: no-cache' );
  header ( 'Cache-Control: no-cache' );
}
?>
