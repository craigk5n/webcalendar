<?php
/**
 * Smarty plugin
 * @package Smarty
 * @subpackage plugins
 */


/**
 * Smarty add_icon WebCalendar function plugin
 *
 * Type:     function<br />
 * Name:     add_icon<br />
 * Generates the HTML for an icon to add a new event.
 * Input:<br />
 *
 * @param string $date    Date for new event in YYYYMMDD format
 * @param int    $hour    Hour of day (0-23)
 * @param int    $minute  Minute of the hour (0-59)
 * @param string $user    Participant to initially select for new event
 *
 * @return string  The HTML for the add event icon.
 */
function smarty_function_add_icon($params, &$smarty)
{
  global $WC;
  static $newEntryStr;

  if ( _WC_READONLY )
    return '';

  $date = $params['date']; 
	$hour = ( ! empty ( $params['hour'] ) ? $params['hour'] : '' ); 
	$minute = ( ! empty ( $params['minute'] ) ? $params['minute'] : '' ); 
	$user = $params['user'];

  if ( empty ( $newEntryStr ) )
    $newEntryStr = translate ( 'New Entry' );

  if ( $minute < 0 ) {
    $hour = $hour -1;
    $minute = abs ( $minute );
  }
  return '
        <a title="' . $newEntryStr . '" href="edit_entry.php?'
   . ( ! empty ( $user ) && ! $WC->isLogin( $user ) ? 'user=' . $user . '&amp;' : '' )
   . 'date=' . $date . ( strlen ( $hour ) > 0 ? '&amp;hour=' . $hour : '' )
   . ( $minute > 0 ? '&amp;minute=' . $minute : '' )
   . ( empty ( $user ) ? '' : '&amp;defusers=' . $user )
   . ( ! $WC->catId() ? '' : '&amp;cat_id=' . $WC->catId() )
   . '"><img src="images/new.gif" class="new" alt="' . $newEntryStr . '" /></a>';
}

/* vim: set expandtab: */

?>
