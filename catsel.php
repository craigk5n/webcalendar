<?php
/* $Id$ */
include_once 'includes/init.php';


$catList = $catNames = $error = '';

if ( ! getPref ( 'CATEGORIES_ENABLED' ) )
  exit;

//$WC->loadCategories();

$cats = $WC->getGET ( 'cats' );
$form = $WC->getGET ( 'form' );

build_header ( array ( 'catsel.js' ),
  '', '', 5 );

  foreach ( $WC->categories() as $K => $V ) {
    // None is index -1 and needs to be ignored
    if ( $K > 0 && ( $WC->isLogin( $V['cat_owner'] ) || $WC->isAdmin() ||
        substr ( $form, 0, 4 ) == 'edit' ) ) {
        $catList[$K]['name'] = $V['cat_name'] 
          . ( empty ( $V['cat_owner'] ) ? '<sup>*</sup>' : '' );
    } 
  } 

if ( strlen ( $cats ) ) {
$eventcats = explode ( ',', $cats );
  foreach ( $eventcats as $K ) {
    // disable if not creator and category is Global
    $neg_num = $show_ast = '';
    $disabled = ( empty ( $categories[abs ( $K )]['cat_owner'] ) &&
      substr ( $form, 0, 4 ) != 'edit' ? 'disabled' : '' );
  //  if ( empty ( $categories[abs ( $K )]['cat_owner'] ) ) {
  //    $neg_num = '-';
  //    $show_ast = '*';
  // } 
	 $eventList[$K]['name'] = $categories[abs ( $K )]['cat_name'] 
       . ( empty ( $categories[abs ( $K )]['cat_owner'] ) ? '<sup>*</sup>' : '' );

  } 
}
print_r ($eventcats );
print_r ($eventList );
$smarty->assign ( 'catList', $catList );
$smarty->assign ( 'eventList', $eventList );
$smarty->display ( 'catsel.tpl' );
?>
