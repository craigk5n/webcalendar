<?php
/* $Id$ */
include_once 'includes/init.php';


$catList = $catNames = $error = '';

if ( ! getPref ( 'CATEGORIES_ENABLED' ) )
  exit;

$WC->loadCategories();

$cats = $WC->getGET ( 'cats' );
$form = $WC->getGET ( 'form' );

$eventcats = explode ( ',', $cats );

$availCatStr = translate ( 'AVAILABLE CATEGORIES' );
$availCatFiller = str_repeat( '&nbsp;', ( 30 - strlen ( $availCatStr ) ) / 2 );
if ( strlen ( $availCatStr ) < 30 )
  $availCatStr = $availCatFiller . $availCatStr . $availCatFiller ;
  
$entryCatStr = translate ( 'ENTRY CATEGORIES' );
$entryCatFiller = str_repeat( '&nbsp;', ( 30 - strlen ( $entryCatStr ) ) / 2 );
if ( strlen ( $entryCatStr ) < 30 )
  $entryCatStr = $entryCatFiller . $entryCatStr . $entryCatFiller ;
 
 
build_header ( array ( 'catsel.js' ),
  '', '', true, false, true );

  foreach ( $WC->categories() as $K => $V ) {
    // None is index -1 and needs to be ignored
    if ( $K > 0 && ( $WC->isLogin( $V['cat_owner'] ) || $WC->isAdmin() ||
        substr ( $form, 0, 4 ) == 'edit' ) ) {
      $pol = ( empty ( $V['cat_owner'] ) ? '=' : '' );
			$catList[$pol.$K]['name'] = $V['cat_name'] 
			 . ( empty ( $V['cat_owner'] ) ? '<sup>*</sup>' : '' );
    } 
  } 


if ( strlen ( $cats ) ) {
  foreach ( $eventcats as $K ) {
    // disable if not creator and category is Global
    $neg_num = $show_ast = '';
    $disabled = ( empty ( $categories[abs ( $K )]['cat_owner'] ) &&
      substr ( $form, 0, 4 ) != 'edit' ? 'disabled' : '' );
    if ( empty ( $categories[abs ( $K )]['cat_owner'] ) ) {
      $neg_num = '-';
      $show_ast = '*';
    } 

  } 
}

$smarty->assign ( 'catList', $catList );
$smarty->assign ( 'availCatStr', $availCatStr );
$smarty->assign ( 'entryCatStr', $entryCatStr );
$smarty->display ( 'catsel.tpl' );
?>
