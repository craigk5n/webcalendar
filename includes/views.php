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


/**
  * Initialize view variables and check permissions.
  * @param int $view_id id for the view
  */
function view_init ( $view_id )
{
  global $views, $error, $login;
  global $ALLOW_VIEW_OTHER, $is_admin;
  global $view_name, $view_type;

  if ( ( empty ( $ALLOW_VIEW_OTHER ) || $ALLOW_VIEW_OTHER == "N" )
    && ! $is_admin ) {
    // not allowed...
    send_to_preferred_view ();
  }
  if ( empty ( $view_id ) ) {
    do_redirect ( "views.php" );
  }

  // Find view name in $views[]
  $view_name = "";
  $view_type = "";
  for ( $i = 0; $i < count ( $views ); $i++ ) {
    if ( $views[$i]['cal_view_id'] == $view_id ) {
      $view_name = $views[$i]['cal_name'];
      $view_type = $views[$i]['cal_view_type'];
    }
  }

  // If view_name not found, then the specified view id does not
  // belong to current user.
  if ( empty ( $view_name ) ) {
    $error = translate ( "You are not authorized" );
  }
}


/**
  * Remove any users from the view list who this user is not
  * allowed to view.
  * @param int $view_id id of the view
  * @return the array of valid users
  */
function view_get_user_list ( $view_id )
{
  global $error, $login, $is_admin;

  // get users in this view
  $res = dbi_execute (
    "SELECT cal_login FROM webcal_view_user WHERE cal_view_id = ?" , array ( $view_id ) );
  $ret = array ();
  $all_users = false;
  if ( $res ) {
      while ( $row = dbi_fetch_row ( $res ) ) {
      $ret[] = $row[0];
      if ( $row[0] == "__all__" )
        $all_users = true;
    }
    dbi_free_result ( $res );
  } else {
    $error = translate ( "Database error" ) . ": " . dbi_error ();
  }
  if ( $all_users ) {
    $users = get_my_users ();
    $ret = array ( );
    for ( $i = 0; $i < count ( $users ); $i++ ) {
      $ret[] = $users[$i]['cal_login'];
    }
  } else {
    // Make sure this user is allowed to see all users in this view
    // If this is a global view, it may include users that this user
    // is not allowed to see.
    if ( ! empty ( $USER_SEES_ONLY_HIS_GROUPS ) &&
      $USER_SEES_ONLY_HIS_GROUPS == 'Y' ) {
      $myusers = get_my_users ();
      if ( ! empty ( $NONUSER_ENABLED ) && $NONUSER_ENABLED == "Y" ) {
        $myusers = array_merge ( $myusers, get_nonuser_cals () );
      }
      $userlookup = array();
      for ( $i = 0; $i < count ( $myusers ); $i++ ) {
        $userlookup[$myusers[$i]['cal_login']] = 1;
      }
      $newlist = array ();
      for ( $i = 0; $i < count ( $ret ); $i++ ) {
        if ( ! empty ( $userlookup[$ret[$i]] ) )
          $newlist[] = $ret[$i];
      }
      $ret = $newlist;
    }
  }

  // If user access control enabled, check against that as well.
  if ( access_is_enabled () && ! $is_admin ) {
    $newlist = array ( );
    for ( $i = 0; $i < count ( $ret ); $i++ ) {
      if ( access_can_view_user_calendar ( $ret[$i] ) )
        $newlist[] = $ret[$i];
    }
    $ret = $newlist;
  }

  //echo "<pre>"; print_r ( $ret ); echo "</pre>\n";
  return $ret;
}



?>

