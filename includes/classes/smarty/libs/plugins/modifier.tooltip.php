<?php
/**
 * Smarty plugin
 * @package Smarty
 * @subpackage plugins
 */


/**
 * WebCalendar tooltip function plugin
 *
 * Type:     function<br>
 * Name:     tooltip<br>
 * Purpose:  translate a text string into the required language
 * @author Ray Jones
 * @param string $str 
 * @param Smarty
 * @return string
 */
function smarty_modifier_tooltip( $str )
{
  $retval = tooltip( $str );
  return $retval;
    
}

/* vim: set expandtab: */

?>
