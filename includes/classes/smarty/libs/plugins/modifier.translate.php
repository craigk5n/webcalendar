<?php
/**
 * Smarty plugin
 * @package Smarty
 * @subpackage plugins
 */


/**
 * WebCalendar translate function plugin
 *
 * Type:     function<br>
 * Name:     translate<br>
 * Purpose:  translate a text string into the required language
 * @author Ray Jones
 * @param string $str 
 * @param bool decode  Whether to use unhtmlentities
 * @param Smarty
 * @return string
 */
function smarty_modifier_translate( $str, $decode=false )
{
  $retval = translate( $str, $decode );
  return $retval;
    
}

/* vim: set expandtab: */

?>
