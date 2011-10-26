<?php // $Id$
/**
 * The file contains a listing of all the current help files in an array.
 * This should make it easier to add new help screens without having to
 * touch each file every time.
*/
defined ( '_ISVALID' ) or ( 'You cannot access this file directly!' );

$help_list = array( translate( 'Index' ) => 'help_index.php' );

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
  $help_list[translate( 'Adding/Editing Calendar Entries' )] = 'help_edit_entry.php';

if( ! access_is_enabled() && $login != '__public__'
    || access_can_access_function( ACCESS_LAYERS ) )
  $help_list[translate( 'Layers' )] = 'help_layers.php';
if( ( ! access_is_enabled() && $login != '__public__' )
    || access_can_access_function( ACCESS_IMPORT ) )
  $help_list[translate( 'Import' )] = 'help_import.php';

if( ( ! access_is_enabled() && $login != '__public__' )
    || access_can_access_function( ACCESS_PREFERENCES ) )
  $help_list[translate( 'Preferences' )] = 'help_pref.php';

if( access_is_enabled() && $login != '__public__' )
  $help_list[translate( 'UAC' )] = 'help_uac.php';

if( ( $is_admin && ! access_is_enabled() )
    || access_can_access_function( ACCESS_IMPORT ) )
  $help_list[translate( 'System Settings' )] = 'help_admin.php';

$help_list[translate( 'Documentation' )] = 'help_docs.php';
$help_list[translate( 'Report Bug' )] = 'help_bug.php';

$helpListStr = '
    <div class="helplist">
      ' . translate( 'Page_' ) . ' ';
$page = 0;
if ( empty ( $thispage ) )
  $thispage = 0;
foreach ( $help_list as $k => $v ) {
  $page++;
  $helpListStr .= '
      <a href="' . "$v?thispage=$page\"" . ( $page == $thispage
    ? ' class="current"' : '' ) . ' title="' . "$k\">$page" . '</a>';
}
$helpListStr .= '
    </div>';

/**
 * Just to print out the help pages.
 *
 * @params $help_array   The array of things to print.
 */
function list_help ( $help_array ) {
  foreach ( $help_array as $l => $v ) {
    echo '
        <p><label>' . $l . '</label> '
     . ( $v == '0' ? '0' : empty( $v ) ? '&nbsp;' : $v ) . '</p>';
  }
}

?>
