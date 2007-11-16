<?php
/**
 * Smarty plugin
 * @package Smarty
 * @subpackage plugins
 */


/**
 * WebCalendar generate Export Select function plugin
 *
 * Type:     function<br>
 * Name:     generate_href_button<br>
 * Purpose:  Return the html to display a href button
 * @author Ray Jones
 * @return string
 */
function smarty_function_generate_export_select ( $params, &$smarty  ) {

  $name = ( ! empty ( $params['name'] ) ? $params['name'] : 'exformat' );
  $jsaction = ( ! empty ( $params['jsaction'] ) ? $params['jsaction'] : '' );	
  $palmStr = translate ( 'Palm Pilot' );
  return '
      <select name="format" id="' . $name . '"'
   . ( ! empty ( $jsaction ) ? 'onchange="' . $jsaction . '();"' : '' ) . '>
        <option value="ical">iCalendar</option>
        <option value="vcal">vCalendar</option>
        <option value="pilot-csv">Pilot-datebook CSV (' . $palmStr . ')</option>
        <option value="pilot-text">Install-datebook (' . $palmStr . ')</option>
      </select>';
}

/* vim: set expandtab: */

?>
