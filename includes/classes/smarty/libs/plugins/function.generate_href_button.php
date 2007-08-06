<?php
/**
 * Smarty plugin
 * @package Smarty
 * @subpackage plugins
 */


/**
 * WebCalendar generate HREF button function plugin
 *
 * Type:     function<br>
 * Name:     generate_href_button<br>
 * Purpose:  Return the html to display a href button
 * @author Ray Jones
 * @return string
 */
function smarty_function_generate_href_button ( $params, &$smarty )
{

  $ret = '
	<input type="button" value="' 
	  . $params[label] . '" ' . $params[attrib] . '/>
		';

  return $ret;
}

/* vim: set expandtab: */

?>
