<?php // $Id$
include_once 'includes/init.php';
include_once 'includes/help_list.php';
print_header ( '', '', '', true );
echo $helpListStr . '
    <h2>' . translate( 'Help Layers' ) . '</h2>
    <p>' . translate ( 'Layers are useful to display' ) . '</p>';
list_help( array(
  translate ( 'Add/Edit/Delete' ) => translate ( 'Edit Layers link in admin' ),
  translate ( 'Colors_' ) => translate ( 'text color of new layer' ),
  translate ( 'Disabling' ) => translate ( 'Disable Layers link in admin' ),
  translate ( 'Duplicates_' ) => translate ( 'show duplicate events' ),
  translate ( 'Enabling_' ) => translate ( 'Enable Layers link in admin' ),
  translate ( 'Source' ) => translate ( 'user to display on your cal' ),
  )
);
if ( $ALLOW_COLOR_CUSTOMIZATION )
  echo '
    <h3>' . translate ( 'Colors' ) . '</h3>
    <p>' . translate ( 'colors-help' ) . '</p>';

echo print_trailer ( false, true, true );

?>
