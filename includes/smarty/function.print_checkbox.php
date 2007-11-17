<?php
/**
 * Smarty plugin
 * @package Smarty
 * @subpackage plugins
 */


/**
 * WebCalendar print_checkbox function plugin
 *
 * Type:     function<br>
 * Name:     print_checkbox<br>
 * Purpose:  Return the html to display a radio button set
 * @author Ray Jones
 * @param array  $params  (name, value, display, setting, id, onchange)
 *
 * @return string  HTML for checkbox 
 */
function smarty_function_print_checkbox ( $params, &$smarty )
{
 
  $name = $params['name'];
  $value = ( ! empty ( $params['value'] ) ? $params['value'] : 'Y' );
  $onchange = ( ! empty ( $params['onchange'] ) 
    ? ' onchange="' . $params['onchange'] . '"' : '' );
  

  if ( _WC_SCRIPT == 'admin.php' ) {
    $setting = getPref ( $name, 2 );
    $name = 'admin_' . $name ;
  }
  if ( _WC_SCRIPT == 'pref.php' ) {
    $setting = getPref ( $name );
    $name = 'pref_' . $name ;
  }
  //getPref returns boolean values for Y/N by default
  $checked = ( ($setting === $value) 
    || ($setting && $value =='Y' ) ? CHECKED : '' );
    
  $ret = '<input type="checkbox" name="' . $name . '" value="' . $value
   . '" id="' . $name . '" ' . $checked 
   . $onchange . ' />';
   
  if ( ! empty ( $params['label'] ) ) 
    $ret =  '<label>' . $ret . '&nbsp;' . $params['label'] . '</label>';

  return $ret;
}

/* vim: set expandtab: */

?>
