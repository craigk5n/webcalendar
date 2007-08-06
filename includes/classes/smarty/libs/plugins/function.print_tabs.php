<?php
/**
 * Smarty plugin
 * @package Smarty
 * @subpackage plugins
 */


/**
 * WebCalendar print_tabs function plugin
 *
 * Type:     function<br>
 * Name:     print_tabs<br>
 * Purpose:  Return the html to display a set of tabs
 * @author Ray Jones
 * @param array  $params  (tabs array)
 *
 * @return string  HTML for tabs
 */
function smarty_function_print_tabs ( $params, &$smarty )
{

  create_menu_edges ();
  $class= 'tabfor';
	$tabs = $params['tabs'];
  $ret ='
	<div id="tabs">';
  foreach ( $tabs as $name=>$label ) {
    $ret .= '
		<span class="' .$class .'" id="tab_' . $name . '">
		<a href="" onclick="return setTab(\''. $name. '\')">'
		  . $label . '</a></span>';
	  $class = 'tabbak';

  }
  $ret .='</div>'; 

  return $ret;
}

function create_menu_edges () {

  $dir_name = 'cache/images/';

  $FGCOLOR = substr ( getPref ( 'CELLBG', 
	   _WC_SCRIPT == 'admin.php' ? 2 : 1 ), 1);	
  
	if ( file_exists ( $dir_name . $FGCOLOR . '_lt.png' ) ) 
	  return;

  $FGred = hexdec ( substr ( $FGCOLOR, 0, 2 ) );
  $FGgrn = hexdec ( substr ( $FGCOLOR, 2, 2 ) );
  $FGblu = hexdec ( substr ( $FGCOLOR, 4, 2 ) );
				

  if ( function_exists ( 'imagepng' ) ) {
    $image = imagecreatefrompng ( 'images/left_menu.png' );
		
		$colors = imagecolorallocate ( $image, $FGred, $FGgrn, $FGblu );
		imagefill ( $image, 0, 0, $colors );
    imagepng ( $image, $dir_name . $FGCOLOR . '_lt.png' );
		
		$colors = imagecolorallocate ( $image, $FGred, $FGgrn, $FGblu );
		imagefill ( $image, 0, 9, $colors );
    imagepng ( $image, $dir_name . $FGCOLOR . '_rt.png' );
				
  } elseif ( function_exists ( 'imagegif' ) ) {
    $image = imagecreatefromgif ( 'images/lft_menu.gif' );
		    
    imagegif ( $image, $file_name );
  } 
	
	
  imagedestroy ( $image );
  return;
} 

/* vim: set expandtab: */

?>
