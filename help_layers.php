<?php
/* $Id: help_layers.php,v 1.23.2.2 2007/08/06 02:28:30 cknudsen Exp $ */
include_once 'includes/init.php';
include_once 'includes/help_list.php';
print_header ( '', '', '', true );
echo $helpListStr . '
    <h2>' . translate ( 'Help' ) . ': ' . translate ( 'Layers' ) . '</h2>
    <p>' .
translate ( 'Layers are useful for displaying...' )
 . '</p>';
$tmp_arr = array (
  translate ( 'Add/Edit/Delete' ) =>
  translate ( 'Clicking the Edit Layers link in the admin section at the bottom of the page will allow you to add/edit/delete layers.' ),
  translate ( 'Colors' ) =>
  translate ( 'The text color of the new layer that will be displayed in your calendar.' ),
  translate ( 'Disabling' ) =>
  translate ( 'Press the Disable Layers link in the admin section at the bottom of the page to turn off layers.' ),
  translate ( 'Duplicates' ) =>
  translate ( 'If checked, events that are duplicates of your events will be shown.' ),
  translate ( 'Enabling' ) =>
  translate ( 'Press the Enable Layers link in the admin section at the bottom of the page to turn on layers.' ),
  translate ( 'Source' ) =>
  translate ( 'Specifies the user that you would like to see displayed in your calendar.' ),
  );
list_help ( $tmp_arr );
if ( $ALLOW_COLOR_CUSTOMIZATION )
  echo '
    <h3>' . translate ( 'Colors' ) . '</h3>
    <p>' . translate ( 'colors-help' ) . '</p>';

echo print_trailer ( false, true, true );

?>
