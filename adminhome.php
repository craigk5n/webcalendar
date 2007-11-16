<?php
/* $Id$

 Page Description:
  Serves as the home page for administrative functions.
 Input Parameters:
  None
 Security:
  Users will see different options available on this page.
 */
include_once 'includes/init.php';


build_header ();
$i=0;
$names = array ();
if ( $WC->isNonuserAdmin() ) {
  if ( access_can_access_function ( ACCESS_PREFERENCES ) ) {
    $names[$i]['name'] = translate ( 'Preferences', P40 );
    $names[$i++]['link'] = 'pref.php?user=' . $user;
  }

} else {
  if ( access_can_access_function ( ACCESS_SYSTEM_SETTINGS ) ) {
    $names[$i]['name'] = translate ( 'System Settings', P40 );
    $names[$i++]['link'] = 'admin.php';
  }

  if ( access_can_access_function ( ACCESS_PREFERENCES ) ) {
    $names[$i]['name'] = translate ( 'Preferences', P40 );
    $names[$i++]['link'] = 'pref.php';
  }

  $names[$i]['name'] = ( $WC->isAdmin() 
	  ? translate ( 'Users', P40 ) : translate ( 'Account', P40 ) );
  $names[$i++]['link'] = 'users.php';

  if ( access_can_access_function ( ACCESS_ACCESS_MANAGEMENT ) ) {
    $names[$i]['name'] = translate ( 'User Access Control', P40 );
    $names[$i++]['link'] = 'access.php';
  }


  if ( getPref ( 'CATEGORIES_ENABLED' ) ) {
    if ( access_can_access_function ( ACCESS_CATEGORY_MANAGEMENT ) ) {
      $names[$i]['name'] = translate ( 'Categories', P40 );
      $links[$i++]['link'] = 'category.php';
    }
  }

  if ( access_can_access_function ( ACCESS_VIEW_MANAGEMENT ) ) {
    $names[$i]['name'] = translate ( 'Views', P40 );
    $links[$i++]['link'] = 'views.php';
  }

  if ( access_can_access_function ( ACCESS_LAYERS ) ) {
    $names[$i]['name'] = translate ( 'Layers', P40 );
    $links[$i++]['link'] = 'layers.php';
  }

  if ( getPref ( 'REPORTS_ENABLED', 2 ) &&
    ( access_can_access_function ( ACCESS_REPORT ) ) ) {
    $names[$i]['name'] = translate ( 'Reports', P40 );
    $links[$i++]['link'] = 'report.php';
  }

  if ( $WC->isAdmin() ) {
    $names[$i]['name'] = translate ( 'Delete Events', P40 );
    $links[$i++]['link'] = 'purge.php';
  }
  /*
 This Activity Log link shows ALL activity for ALL events, so you really need
 to be an admin user for this.  Enabling "Activity Log" in UAC just gives you
 access to the log for your _own_ events or other events you have access to.
 */
  if ( $WC->isAdmin() && access_can_access_function ( ACCESS_ACTIVITY_LOG ) ) {
    $names[$i]['name'] = translate ( 'Activity Log', P40 );
    $links[$i++]['link'] = 'activity_log.php';

    $names[$i]['name'] = translate ( 'System Log', P40 );
    $links[$i++]['link'] = 'activity_log.php?system=1';
  }
}
$smarty->assign ( 'columns', 3 );
$smarty->assign ( 'names', $names );
$smarty->display ( 'adminhome.tpl' );

?>
