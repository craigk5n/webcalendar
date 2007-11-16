<?php
/* $Id$ */
include_once 'includes/init.php';

if ( ! getPref ( 'CATEGORIES_ENABLED' ) ) {
  send_to_preferred_view ();
  exit;
}

if ( ! access_can_access_function ( ACCESS_CATEGORY_MANAGEMENT ) )
  $error = print_not_auth ();

$smarty->assign('tabs_ar', array ( 'categories'=>translate ( 'Categories' ) ) );

$icon_path = 'icons/';
// If editing, make sure they are editing their own (or they are an admin user).
if ( $WC->getId() ) {
  if ( empty ( $categories[$eid] ) )
    $error =
    str_replace ( 'XXX', $eid, translate ( 'Invalid entry id XXX' ) );

$smarty->assign ( 'catcolor', $categories[$eid]['cat_color'] );
$smarty->assign ( 'catname', $categories[$eid]['cat_name'] );
$smarty->assign ( 'catowner', $categories[$eid]['cat_owner'] );
$smarty->assign ( 'catIcon', $icon_path . $categories[$eid]['cat_owner'] );

} else
$smarty->assign ( 'catcolor', '#000000' );

$smarty->assign ( 'showIcon', ( ! empty ( $catIcon ) && file_exists ( $catIcon )
  ? 'visible' : 'hidden' ) );;

$smarty->assign ( 'doUploads',  @is_dir ( $icon_path ) &&
    ( getPref ( 'ENABLE_ICON_UPLOADS' )  || $WC->isAdmin() ) ); 

if ( $WC->getGET('add' ) || $WC->getId() ) {
 $smarty->assign ( 'add_edit', true );
}

build_header ();

$smarty->display ( 'category.tpl' );
?>