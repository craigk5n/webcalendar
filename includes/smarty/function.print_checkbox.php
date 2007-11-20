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
  static $sysConfig, $userPref;

  //load webcal_config values if not already loaded
  if ( empty ( $sysConfig ) ) 
    $sysConfig = loadConfig ();
 
  //load webcal_user_pref values if not already loaded
  if (  _WC_SCRIPT == 'pref.php' && empty ( $userPref ) ) {
    $userPref = loadPreferences ();
  }
  
  $disabled = '';
  $name = $params['name'];
  $value = ( ! empty ( $params['value'] ) ? $params['value'] : 'Y' );
  $onchange = ( ! empty ( $params['onchange'] ) 
    ? ' onchange="' . $params['onchange'] . '"' : '' );
  

  if ( _WC_SCRIPT == 'admin.php' ) {
    $setting = $sysConfig[$name];
    $name = 'admin_' . $name ;
  }
  if ( _WC_SCRIPT == 'pref.php' ) {
    $setting = $userPref[$name];
    //Check if control should be disabled
    $admin_setting = $sysConfig[$name];
    if ( substr ( $name, 0, 1 ) == '_' &&  $admin_setting == 'N' ) {
      $disabled = DISABLED;
      $setting = $admin_setting;
    }
    $name = 'pref_' . $name ;
  }
  //getPref returns boolean values for Y/N by default
  $checked = ( ( $setting == $value ) ? CHECKED : '' );
      
  $ret = '<input type="checkbox" name="' . $name . '" value="' . $value
   . '" id="' . $name . '" ' . $checked 
   . $onchange . $disabled . ' />';
   
  if ( ! empty ( $params['label'] ) ) 
    $ret =  '<label>' . $ret . '&nbsp;' . $params['label'] . '</label>';

  return $ret;
}

/* vim: set expandtab: */

?>
