<?php
/**
 * Smarty plugin
 * @package Smarty
 * @subpackage plugins
 */


/**
 * WebCalendar generate color input html function plugin
 *
 * Type:     function<br>
 * Name:     html_color_input<br>
 * Purpose:  Return the html to display a color input control set
 * @author Ray Jones
 * @return string
 */
function smarty_function_html_color_input ( $params, &$smarty )
{
  global $SCRIPT;
  static $select;

  $name = $params['name'];
  $setting = ( ! empty ( $params['val']) ? $params['val'] : '#000000' );
  $title = $params['title'];
  if ( empty ( $select ) )
    $select = translate ( 'Select' ) . '...';

  if ( _WC_SCRIPT == 'admin.php' ) {
	  $s = $smarty->get_template_vars( 's' );
	  $setting = $s[$params['name']];
    $name = 'admin_' . $params['name'];
  }
  if ( _WC_SCRIPT == 'pref.php' ) {
	  $p = $smarty->get_template_vars( 'p' );
	  $setting = $p[$params['name']];
    $name = 'pref_' . $params['name'];
  }

  $ret = <<<EOT
<label for="{$name}">{$title}:</label></td>
<td width="50">
  <input type="text" name="{$name}" id="{$name}" size="7" maxlength="7" value="{$setting}" onchange="updateColor( this, '{$name}_sample' );" /></td>
<td class="sample" id="{$name}_sample" style="background-color:{$setting};"></td>
<td>
  <input type="button" onclick="selectColor( '{$name}', event )" value="{$select}" />
EOT;
	 
  return $ret;
}

/* vim: set expandtab: */

?>
