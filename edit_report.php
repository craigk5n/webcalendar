<?php
/* Presents a HTML form to add or edit a report.
 * 
 * Input Parameters:
 * - <var>report_id</var> (optional) - the report id of the report to edit.  If
 *   blank, user is adding a new report.
 * - <var>public</var> (optional) - If set to '1' and user is an admin user,
 *   then we are creating a report for the public user.
 *
 * @author Craig Knudsen <cknudsen@cknudsen.com>
 * @copyright Craig Knudsen, <cknudsen@cknudsen.com>, http://www.k5n.us/cknudsen
 * @license http://www.gnu.org/licenses/gpl.html GNU GPL
 * @version $Id$
 * @package WebCalendar
 * @subpackage Reports
 *
 */

/*
 * Security:
 * If system setting $REPORTS_ENABLED is set to anything other than
 *   'Y', then don't allow access to this page.
 * If $ALLOW_VIEW_OTHER is 'N', then do not allow selection of
 *   participants.
 * If not an admin user, only report creator (cal_login in webcal_report)
 *   can edit/delete report.
 */

include_once 'includes/init.php';

$error = '';

if ( ! getPref ( 'REPORTS_ENABLED', 2 ) ) {
  $error = print_not_auth () . '.';
}

$report_user = '';


$report_id = $WC->getValue ( 'report_id', '-?[0-9]+', true );

$adding_report = false;
if ( empty ( $report_id ) ) {
  $adding_report = true;
  $report_id = -1;
  $include_header = 'Y';
  $report_is_global = 'N';
  $report_allow_nav = 'Y';
}

$show_participants = true;
if ( _WC_SINGLE_USER || getPref ( 'DISABLE_PARTICIPANTS_FIELD' ) ) {
  $show_participants = false;
}

$smarty->assign ( 'charset', ( getPref ( 'LANGUAGE' ) ? translate( 'charset' ): 'iso-8859-1' ) );

$nextXXXStr = translate ( 'Next XXX days' );

// Set date range options
$ranges = array (
  '1' => translate ( 'Today' ),
  '2' => translate ( 'Tomorrow' ),
  '3' => translate ( 'Yesterday' ),
  '4' => translate ( 'Day before yesterday' ),
  '10' => translate ( 'Next week' ),
  '11' => translate ( 'This week' ),
  '12' => translate ( 'Last week' ),
  '13' => translate ( 'Week before last' ),
  '20' => translate ( 'Next week and week after' ),
  '21' => translate ( 'This week and next week' ),
  '22' => translate ( 'Last week and this week' ),
  '23' => translate ( 'Last two weeks' ),
  '30' => translate ( 'Next month' ),
  '31' => translate ( 'This month' ),
  '32' => translate ( 'Last month' ),
  '33' => translate ( 'Month before last' ),
  '40' => translate ( 'Next year' ),
  '41' => translate ( 'This year' ),
  '42' => translate ( 'Last year' ),
  '43' => translate ( 'Year before last' ),
  '50' => str_replace ('XXX', '14', $nextXXXStr ),
  '51' => str_replace ('XXX', '30', $nextXXXStr ),
  '52' => str_replace ('XXX', '60', $nextXXXStr ),
  '53' => str_replace ('XXX', '90', $nextXXXStr ),
  '54' => str_replace ('XXX', '180', $nextXXXStr ),
  '55' => str_replace ('XXX', '365', $nextXXXStr ),
);

// Get list of users that the current user can see
if ( empty ( $error ) && $show_participants ) {
  $userlist = get_my_users ( '', 'view' );
  if ( getpref ( 'NONUSER_ENABLED' ) ) {
    //restrict NUC list if groups are enabled
    $nonusers = get_my_nonusers ( $WC->loginId(), true, 'view' );
    $userlist = getpref ( 'NONUSER_AT_TOP') ? array_merge($nonusers, $userlist) : 
      array_merge($userlist, $nonusers);
  }
  $userlistcnt = count ( $userlist );
}

//NOTE***  the ${} are meant to be passed as part of the array elements
//DO NOT change single quotes to double below or they will be evaluted
//by php
// Default values
$page_template = '<dl>${days}</dl>';
$day_template = '<dt><b>${date}</b></dt>
<dd><dl>${events}</dl></dd>';
$event_template = '<dt>${name}</dt>
<dd><b>' . translate ( 'Date' ) . ':</b> ${date}<br />
<b>' . translate ( 'Time' ) . ':</b> ${time}<br />
${description}</dd>';

//Setup option arrays
$smarty->assign ( 'page_options', array ( 
  '${days}', '${report_id}' ) );
$smarty->assign ( 'day_options', array ( 
  '${events}', '${date}', '${fulldate}', '${report_id}') );
$smarty->assign ( 'event_options', array ( 
  '${name}',
  '${description}',
  '${date}',
  '${fulldate}',
  '${time}',
  '${starttime}',
  '${endtime}',
  '${duration}',
  '${location}',
  '${url}',
  '${priority}',
  '${href}',
  '${user}',
  '${fullname}',
  '${report_id}'
) );

if ( empty ( $error ) && $report_id >= 0 ) {
  $sql = 'SELECT cal_login_id, cal_report_id, cal_is_global,
    cal_report_type, cal_include_header, cal_report_name,
    cal_time_range, cal_user_id, cal_allow_nav, cal_cat_id,
    cal_include_empty, cal_show_in_trailer, cal_update_date
    FROM webcal_report
    WHERE cal_report_id = ?';
  //echo "SQL: $sql<br /><br />";
  $res = dbi_execute ( $sql, array( $report_id ) );
  if ( $res ) {
    if ( $row = dbi_fetch_row ( $res ) ) {
      $i = 0;
      $report_login = $row[$i++];
      $report_id = $row[$i++];
      $report_is_global = $row[$i++];
      $report_type = $row[$i++];
      $report_include_header = $row[$i++];
      $report_name = $row[$i++];
      $report_time_range = $row[$i++];
      $report_user = $row[$i++];
      $report_allow_nav = $row[$i++];
      $report_cat_id = $row[$i++];
      $report_include_empty = $row[$i++];
      $report_show_in_menu = $row[$i++];
      $report_update_date = $row[$i++];

      // Check permissions.
      if ( $show_participants && ! empty ( $report_user ) ) {
        $user_is_in_list = false;
        for ( $i = 0; $i < $userlistcnt; $i++ ) {
          if ( $report_user == $userlist[$i]['cal_login_id'] ) {
            $user_is_in_list = true;
          }
        }
        if ( ! $user_is_in_list && ! $WC->isLogin( $report_login ) && 
		  ! $WC->isAdmin() ) {
          $error = print_not_auth ();
        }
      }
      if ( ! $WC->isAdmin() && ! $WC->isLogin( $report_login ) ) {
        // If not admin, only creator can edit/delete the event
        $error = print_not_auth ();
      }
      
        
    } else {
      $error = translate ( 'Invalid report id' ) . ": $report_id";
    }
    dbi_free_result ( $res );
  } else {
    $error = db_error ();
  }
  $res = dbi_execute ( 'SELECT cal_template_type, cal_template_text ' .
    'FROM webcal_report_template ' .
    'WHERE cal_report_id = ?', array( $report_id ) );
  if ( $res ) {
    while ( $row = dbi_fetch_row ( $res ) ) {
      if ( $row[0] == 'P' ) {
        $page_template = $row[1];
      } else if ( $row[0] == 'D' ) {
        $day_template = $row[1];
      } else if ( $row[0] == 'E' ) {
        $event_template = $row[1];
      }
    }
    dbi_free_result ( $res );
  }
} else {
  // default values for new report
  $report_login = $WC->loginId();
  $report_id = -1;
  $report_is_global = 'N';
  $report_type = 'html';
  $report_include_header = 'Y';
  $report_name = translate( 'Unnamed Report' );
  $report_time_range = 11; // current week
  //$report_user already set
  $report_allow_nav = 'Y';
  $report_cat_id = '';
  $report_include_empty = 'N';
  $report_show_in_menu = 'N';
  $report_update_date = '';
}

while ( list ( $num, $desc ) = each ( $ranges ) ) {
  $rpt_ranges[$num]['desc'] = $desc;
  if ( $report_time_range == $num )
    $rpt_ranges[$num]['selected'] = SELECTED;
}
$smarty->assign ('rpt_ranges', $rpt_ranges );

if ( ! empty ( $report_user ) ) 
  $userlist[$report_user]['selected'] = SELECTED;
$smarty->assign ('users', $userlist );
	
$INC = array ( 'edit_report.js' );
build_header ( $INC );

$smarty->assign ('extra_names', get_site_extras_names( EXTRA_DISPLAY_REPORT ) );
$smarty->assign ('report_user', $report_user );
$smarty->assign ('report_login', $report_login );
$smarty->assign ('report_id', $report_id );
$smarty->assign ('report_is_global', $report_is_global );
$smarty->assign ('report_type', $report_type );
$smarty->assign ('report_include_header', $report_include_header );
$smarty->assign ('report_name', $report_name );
$smarty->assign ('report_time_range', $report_time_range ); 
$smarty->assign ('report_allow_nav', $report_allow_nav );
$smarty->assign ('report_cat_id', $report_cat_id );
$smarty->assign ('report_include_empty', $report_include_empty );
$smarty->assign ('report_show_in_menu', $report_show_in_menu );
$smarty->assign ('report_update_date', $report_update_date );

$smarty->assign ('page_template', $page_template );
$smarty->assign ('day_template', $day_template );
$smarty->assign ('event_template', $event_template );
	
$smarty->assign ('adding_report', $adding_report );
$smarty->display ( 'edit_report.tpl' );
?>

