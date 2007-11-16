<?php
/**
 * Smarty plugin
 * @package Smarty
 * @subpackage plugins
 */


/**
 * WebCalendar htmlspecialchars function plugin
 *
 * Type:     function<br>
 * Name:     htmlspecialchars<br>
 * Purpose:  apply php htmlspecialchars to string
 * @author Ray Jones
 * @param string $str 
 * @param Smarty
 * @return string
 */
function smarty_modifier_htmlspecialchars( $str )
{
  $retval = htmlspecialchars( $str );
  return $retval;
    
}

/* vim: set expandtab: */

?>
