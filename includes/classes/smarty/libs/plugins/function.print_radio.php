<?php
/**
 * Smarty plugin
 * @package Smarty
 * @subpackage plugins
 */


/**
 * WebCalendar print_radio function plugin
 *
 * Type:     function<br>
 * Name:     print_radio<br>
 * Purpose:  Return the html to display a radio button set
 * @author Ray Jones
  * @param string  $variable the name of the variable to display
 * @param array   $vars the value and display variables
 *                if empty ( Yes/No options will be displayed )
 * @param string  $onclick  javascript function to call if needed
 * @param string  $defIdx default array index to select
 * @param string  $sep HTML value between radio options (&nbsp;, <br />)
 * @return string  HTML for the radio control.
 */
function smarty_function_print_radio ( $params, &$smarty )
{
  static $No, $Yes;
  $cnt = 0;
  $ret = '';
  if ( empty ( $No ) ) {
    $No = translate ( 'No' );
    $Yes = translate ( 'Yes' );
  }
	$variable = $params[variable];
  $setting = $params[defIdx];
  $vars = ( ! empty ( $params[vars] ) 
	  ? $params[vars] : array ( 'Y' => $Yes, 'N' => $No ) );

  $onclickStr = ( empty ( $params[onclick] ) 
	  ? '' : ' onclick="' . $params[onclick] . '"' );

  $sep = ( ! empty ( $params[sep] ) ? $params[sep] : '&nbsp;' );
			
  if ( _WC_SCRIPT == 'admin.php' ) {
	  $s = $smarty->get_template_vars( 's' );
	  $setting = $s[$variable];
    $variable = 'admin_' . $variable;
  }
  if ( _WC_SCRIPT == 'pref.php' ) {
	  $p = $smarty->get_template_vars( 'p' );
	  $setting = $p[$variable];
    $variable = 'pref_' . $variable;
  }

  $varcnt = count ( $vars );
  foreach ( $vars as $K => $V ) {
    $cnt++;
    $ret .= '
      <label><input type="radio" name="' . $variable . '" value="' . $K . '" '
     . ( $setting == $K ? CHECKED : '' ) . $onclickStr . ' />&nbsp;' . $V
     . '</label>' . ( $cnt < $varcnt ? $sep : '' );
  }
  return $ret;
}

/* vim: set expandtab: */

?>
