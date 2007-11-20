<?php
/**
 * This file includes functions needed by the custom views.
 *
 * @author Craig Knudsen <cknudsen@cknudsen.com>
 * @copyright Craig Knudsen, <cknudsen@cknudsen.com>, http://www.k5n.us/cknudsen
 * @license http://www.gnu.org/licenses/gpl.html GNU GPL
 * @version $Id$
 * @package WebCalendar
 */
defined ( '_ISVALID' ) or die ( 'You cannot access this file directly!' );

//set this to prove we in are inside a custom view page
define ( '_WC_CUSTOM_VIEW', true );

$error = ''; 
$vid = $WC->getValue ( 'vid', '-?[0-9]+', true ); 

view_init ( $vid );

$date = $WC->getDate();
$WC->setToday ( $date );



$participants = view_get_user_list ( $vid );
if ( count ( $participants ) == 0 ) {
  // This could happen if user_sees_only_his_groups  = Y and
  // this user is not a member of any group assigned to this view.
  $smarty->assign ( 'errorStr', translate ( 'No users for this view' ) . '.' );
  $smarty->display ( 'error.tpl' );
  exit;
}

/**
  * Initialize view variables and check permissions.
  * @param int $view_id id for the view
  */
function view_init ( $vid ) {
  global $WC, $smarty;

  
  if ( ! getPref ( '_ALLOW_VIEW_OTHER' ) && ! $WC->isAdmin() ) {
    // not allowed...
    send_to_preferred_view ();
  }
  if ( empty ( $vid ) ) {
    do_redirect ( 'views.php' );
  }

  // Find view name in $views[]
  $views = loadViews ( $vid );
  $smarty->assign ( 'view_name', htmlspecialchars ( $views[0]['cal_name'] ) );
  $smarty->assign ( 'view_type', $views[0]['cal_view_type'] );

  // If view_name not found, then the specified view id does not
  // belong to current user.
  if ( empty ( $views ) ) {
    $smarty->assign ( 'not_auth', true );
    $smarty->display ( 'error.tpl' );
  }
  return $views;
}


/**
  * Remove any users from the view list who this user is not
  * allowed to view.
  * @param int $view_id id of the view
  * @return the array of valid users
  */
function view_get_user_list ( $vid ) {
  global $error, $WC;

  // get users in this view
  $res = dbi_execute (
    'SELECT cal_login_id FROM webcal_view_user WHERE cal_view_id = ?', array ( $vid ) );
  $ret = array ();
  $all_users = false;
  if ( $res ) {
      while ( $row = dbi_fetch_row ( $res ) ) {
      $ret[] = $row[0];
      if ( $row[0] == -1 )
        $all_users = true;
    }
    dbi_free_result ( $res );
  } else {
    $error = db_error ();
  }
  $myusers = get_my_users ( '', 'view' );

  if ( ! $all_users ) {
    for ( $i = 0, $cnt = count ( $myusers ); isset ( $myusers[$i] ) && $i < $cnt; $i++ ) {
        if ( ! array_key_exists ( $myusers[$i]['cal_login_id'], $ret ) )
          array_pop ( $myusers);
    }
  }
    $ret = $myusers;    
//  echo "<pre>"; print_r ( $ret ); echo "</pre>\n";
  return $ret;
}
?>
