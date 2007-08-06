<?php
/**
 * Smarty plugin
 * @package Smarty
 * @subpackage plugins
 */


/**
 * WebCalendar generate help icon function plugin
 *
 * Type:     function<br>
 * Name:     generate help icon<br>
 * Purpose:  Return the html to display a href button
 * @author Ray Jones
 * @return string
 */
function smarty_function_generate_help_icon ( $params, &$smarty )
{
  if ( substr ( $params['url'], 0, 5 ) != 'help_' ) 
    return false;
  return '&nbsp;<img src="images/help.gif" alt="' . translate( 'Help' ) 
    . '" class="help" onclick="openHelp( \'' . $params['url']. '\' );" />';
}

/* vim: set expandtab: */

?>
