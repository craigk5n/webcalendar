<?php
/* $Id$ */
include_once 'includes/init.php';
include_once 'includes/help_list.php';
build_header ( '', '', '', 29 );
echo $helpListStr . '
    <h2>' . translate ( 'Help' ) . ': ' . translate ( 'Layers' ) . '</h2>
    <p>' .
translate ( 'Layers are useful...' )
 . '</p>';
$tmp_arr = array (
  translate ( 'Add/Edit/Delete' ) =>
  translate ( 'Clicking the Edit Layers link...' ),
  translate ( 'Colors' ) =>
  translate ( 'The text color of the new layer that will be displayed in your calendar.' ),
  translate ( 'Disabling' ) =>
  translate ( 'Press the Disable Layers link...' ),
  translate ( 'Duplicates' ) =>
  translate ( 'If checked, events that are duplicates of your events will be shown.' ),
  translate ( 'Enabling' ) =>
  translate ( 'Press the Enable Layers link...' ),
  translate ( 'Source' ) =>
  translate ( 'Specifies the user that you would like to see displayed in your calendar.' ),
  );
list_help ( $tmp_arr );
if ( getPref ( 'ALLOW_COLOR_CUSTOMIZATION' ) )
  echo '
    <h3>' . translate ( 'Colors' ) . '</h3>
    <p>' . translate ( 'colors-help' ) . '</p>';

echo print_trailer ( false, true, true );

?>
