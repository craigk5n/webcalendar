<?php // $Id: help_list.php,v 1.9 2009/11/22 16:47:46 bbannon Exp $
/**
 * The file contains a listing of all the current help files in an array.
 * This should make it easier to add new help screens without having to
 * touch each file every time.
*/
defined ( '_ISVALID' ) or ( 'You cannot access this file directly!' );
// DO NOT DELETE translate ( 'Index' ) translate ( 'Documentation' )
$help_list = array();
$help_list['Index'] = 'help_index.php';

$can_add = true;
if ( $readonly == 'Y' )
  $can_add = false;
elseif( access_is_enabled() )
  $can_add = access_can_access_function ( ACCESS_EVENT_EDIT );
else {
  if ( $login == '__public__' )
    $can_add = ( $GLOBALS['PUBLIC_ACCESS_CAN_ADD'] == 'Y' );

  if ( $is_nonuser )
    $can_add = false;
}
if ( $can_add )
  $help_list['Adding/Editing Calendar Entries'] = 'help_edit_entry.php';

if( ! access_is_enabled() && $login != '__public__'
    || access_can_access_function( ACCESS_LAYERS ) )
  $help_list['Layers'] = 'help_layers.php';
if( ( ! access_is_enabled() && $login != '__public__' )
    || access_can_access_function( ACCESS_IMPORT ) )
  $help_list['Import'] = 'help_import.php';

if( ( ! access_is_enabled() && $login != '__public__' )
    || access_can_access_function( ACCESS_PREFERENCES ) )
  $help_list['Preferences'] = 'help_pref.php';

if( access_is_enabled() && $login != '__public__' )
  $help_list['User Access Control'] = 'help_uac.php';

if( ( $is_admin && ! access_is_enabled() )
    || access_can_access_function( ACCESS_IMPORT ) )
  $help_list['System Settings'] = 'help_admin.php';

$help_list['Documentation'] = 'help_docs.php';
$help_list['Report Bug'] = 'help_bug.php';

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

/**
 * Just to print out the help pages.
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
