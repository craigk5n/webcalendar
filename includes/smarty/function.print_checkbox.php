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
  static $No, $Yes;

  $name = $params['name'];
	$value = ( ! empty ( $params['value'] ) ? $params['value'] : 'Y' );
  $onchange = ( ! empty ( $params['onchange'] ) 
	  ? ' onchange="' . $params['onchange'] . '"' : '' );
	
  if ( empty ( $No ) ) {
    $No = translate ( 'No' );
    $Yes = translate ( 'Yes' );
  }


  if ( _WC_SCRIPT == 'admin.php' ) {
    $setting = getPref ( $name, 2 );
    $name = 'admin_' . $name ;
  }
  if ( _WC_SCRIPT == 'pref.php' ) {
    $setting = getPref ( $name );
    $name = 'pref_' . $name ;
  }
	$checked = ( $setting == $params['value'] ? CHECKED : '' );
  return '
      <label><input type="checkbox" name="' . $name . '" value="' . $value
   . '" id="' . $name . '" ' . $checked 
   . $onchange . ' />&nbsp;' . $params['label'] . '</label>';

}

/* vim: set expandtab: */

?>
