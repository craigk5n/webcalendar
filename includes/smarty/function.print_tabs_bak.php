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
  $class= 'tabfor';
	$tabs = $params['tabs'];
  $ret ='
	<div id="tabs">';
  foreach ( $tabs as $name=>$label ) {
    $ret .= '
		<span class="' .$class .'" id="tab_' . $name . '">
		<em class="bullctl"><b>&bull;</b></em>
		<em class="bullctr"><b>&bull;</b></em>
		<a href="" onclick="return setTab(\''. $name. '\')">'
		  . $label . '</a></span>';
	  $class = 'tabbak';

  }
  $ret .='</div>'; 

  return $ret;
}

/* vim: set expandtab: */

?>
