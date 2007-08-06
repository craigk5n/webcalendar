<?php
/**
 * Smarty plugin
 * @package Smarty
 * @subpackage plugins
 */


/**
 * WebCalendar load_template function plugin
 *
 * Type:     function<br>
 * Name:     load_template<br>
 * Purpose:  Return the value of user or server preferences
 * @author Ray Jones
 * @return string
 */
function smarty_function_load_template( $type )
{
   echo load_template( $type );
}

/* vim: set expandtab: */

?>
