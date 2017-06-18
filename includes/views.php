<?php
/**
 * This file includes functions needed by the custom views.
 *
 * @author Craig Knudsen <cknudsen@cknudsen.com>
 * @copyright Craig Knudsen, <cknudsen@cknudsen.com>, http://www.k5n.us/cknudsen
 * @license http://www.gnu.org/licenses/gpl.html GNU GPL
 * @version $Id: views.php,v 1.23.2.3 2007/11/12 15:40:33 umcesrjones Exp $
 * @package WebCalendar
 */

/**
  * Initialize view variables and check permissions.
  * @param int $view_id id for the view
  */
function view_init ( $view_id )
{
  global $views, $error, $login;
  global $ALLOW_VIEW_OTHER, $is_admin;
  global $view_name, $view_type, $custom_view;

  //set this to prove we in are inside a custom view page
  $custom_view = true;

  if ( ( empty ( $ALLOW_VIEW_OTHER ) || $ALLOW_VIEW_OTHER == 'N' )
    && ! $is_admin ) {
    // not allowed...
    send_to_preferred_view ();
  }
  if ( empty ( $view_id ) ) {
    do_redirect ( 'views.php' );
  }

  // Find view name in $views[]
  $view_name = '';
  $view_type = '';
  $viewcnt = count ( $views );
  for ( $i = 0; $i < $viewcnt; $i++ ) {
    if ( $views[$i]['cal_view_id'] == $view_id ) {
      $view_name = htmlspecialchars ( $views[$i]['cal_name'] );
      $view_type = $views[$i]['cal_view_type'];
    }
  }

  // If view_name not found, then the specified view id does not
  // belong to current user.
  if ( empty ( $view_name ) ) {
    $error = print_not_auth (34);
  }
}

/**
  * Remove any users from the view list who this user is not
  * allowed to view.
  * @param int $view_id id of the view
  * @return the array of valid users
  */
function view_get_user_list ( $view_id ) {
  global $error, $login, $is_admin, $NONUSER_ENABLED, $USER_SEES_ONLY_HIS_GROUPS;

  // get users in this view
  $res = dbi_execute (
    'SELECT cal_login FROM webcal_view_user WHERE cal_view_id = ?', array ( $view_id ) );
  $ret = array ();
  $all_users = false;
  if ( $res ) {
      while ( $row = dbi_fetch_row ( $res ) ) {
      $ret[] = $row[0];
      if ( $row[0] == '__all__' )
        $all_users = true;
    }
    dbi_free_result ( $res );
  } else {
    $error = db_error ();
  }
  if ( $all_users ) {
    $users = get_my_users ( '', 'view' );
    $ret = array ();
    $usercnt = count ( $users );
    for ( $i = 0; $i < $usercnt; $i++ ) {
      $ret[] = $users[$i]['cal_login'];
    }
  } else {
    $myusers = get_my_users ( '', 'view' );

    if ( ! empty ( $NONUSER_ENABLED ) && $NONUSER_ENABLED == 'Y' ) {
      $myusers = array_merge ( $myusers, get_my_nonusers ( $login, true, 'view' ) );
    }
    // Make sure this user is allowed to see all users in this view
    // If this is a global view, it may include users that this user
    // is not allowed to see.
    if ( ! empty ( $USER_SEES_ONLY_HIS_GROUPS ) &&
      $USER_SEES_ONLY_HIS_GROUPS == 'Y' ) {
      $userlookup = array ();
      $myusercnt = count ( $myusers );
      for ( $i = 0; $i < $myusercnt; $i++ ) {
        $userlookup[$myusers[$i]['cal_login']] = 1;
      }
      $newlist = array ();
      $retcnt = count ( $ret );
      for ( $i = 0; $i < $retcnt; $i++ ) {
        if ( ! empty ( $userlookup[$ret[$i]] ) )
          $newlist[] = $ret[$i];
      }
      $ret = $newlist;
    }

    //Sort user list...
    $sortlist = array ();
    $myusercnt = count ( $myusers );
    $retcnt = count ( $ret );
    for ( $i = 0; $i < $myusercnt; $i++ ) {
      for ( $j = 0; $j < $retcnt; $j++ ) {
        if ( $myusers[$i]['cal_login'] == $ret[$j] ) {
          $sortlist[] = $ret[$j];
          break;
        }
      }
    }
    $ret = $sortlist;
  }

  // If user access control enabled, check against that as well.
  if ( access_is_enabled () && ! $is_admin ) {
    $newlist = array ();
    $retcnt = count ( $ret );
    for ( $i = 0; $i < $retcnt; $i++ ) {
      if ( access_user_calendar ( 'view', $ret[$i] ) )
        $newlist[] = $ret[$i];
    }
    $ret = $newlist;
  }

  //echo "<pre>"; print_r ( $ret ); echo "</pre>\n";
  return $ret;
}
?>
