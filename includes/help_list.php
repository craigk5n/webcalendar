<?php
/* $Id$ 
 * 
 * The file contains a listing of all the current help files in an array.
 * This should make it easier to add new help screens without having to
 * touch each file every time
 *
 *
*/
defined( '_ISVALID' ) or die( "You can't access this file directly!" );

$help_list = array (
'Index'=>'help_index.php',
'Adding/Editing Calendar Entries'=>'help_edit_entry.php',
'Layers'=>'help_layers.php',
'Import'=>'help_import.php',
'Preferences'=>'help_pref.php',
'User Access Control'=>'help_uac.php',
'System Settings'=>'help_admin.php',
'Report Bug'=>'help_bug.php'
);
$helpListStr = '<div style="text-align:center; font-size:1.2em">' .
 '<span style="font-weight:bold">' . translate( 'Page' ) . ':</span>';;
$page = 0;
if ( empty ( $thispage ) ) $thispage = 0;
$cnt = count ( $help_list );
foreach ( $help_list as $key => $val ) {
  $page++;
	$transStr = translate( $key );
  $val .= '?thispage=' . $page;
  $bold = ( $page == $thispage ? ' style="font-weight:bold" ' :
   ' style="text-decoration: underline" ' );
	$helpListStr .= ' <a ' . $bold . ' title="' . $transStr . '" href="' . $val . '">' . 
    $page .'</a> ';
}
$helpListStr .= '</div>';
?>
