<?php
/* $Id$
 *
 * The file contains a listing of all the current help files in an array.
 * This should make it easier to add new help screens without having to
 * touch each file every time.
*/
defined ( '_ISVALID' ) or ( 'You cannot access this file directly!' );
// DO NOT DELETE translate ( 'Index' ) translate ( 'Documentation' )
$help_list = array (
  'Index' => 'help_index.php',
  'Adding/Editing Calendar Entries' => 'help_edit_entry.php',
  'Layers' => 'help_layers.php',
  'Import' => 'help_import.php',
  'Preferences' => 'help_pref.php',
  'User Access Control' => 'help_uac.php',
  'System Settings' => 'help_admin.php',
  'Documentation' => 'help_docs.php',
  'Report Bug' => 'help_bug.php'
  );
$helpListStr = '
    <div class="helplist">
      ' . translate ( 'Page' ) . ': ';
$page = 0;
if ( empty ( $thispage ) )
  $thispage = 0;
foreach ( $help_list as $key => $val ) {
  $page++;
  $helpListStr .= '
      <a' . ( $page == $thispage ? ' class="current"' : '' ) . ' title="'
   . translate ( $key ) . '" href="' . $val . '?thispage=' . $page . '">'
   . $page . '</a>';
}
$helpListStr .= '
    </div>';

/* Just to print out the help pages.
*
* @params $help_array   The array of things to print.
*/
function list_help ( $help_array ) {
  foreach ( $help_array as $lab => $val ) {
    echo '
        <p><label>' . $lab . ':</label> '
     . ( $val == '0' ? '0' : empty ( $val ) ? '&nbsp;' : $val ) . '</p>';
  }
}

?>
