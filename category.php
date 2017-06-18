<?php
/* $Id: category.php,v 1.45.2.4 2013/01/24 21:10:18 cknudsen Exp $ */

include_once 'includes/init.php';
// Load user and global cats.
load_user_categories ();

if ( $CATEGORIES_ENABLED == 'N' ) {
  send_to_preferred_view ();
  exit;
}

$catIcon = $catname = $error = $idStr = '';
$catIconStr = translate ( 'Category Icon' );
$globalStr = translate ( 'Global' );
$icon_path = 'icons/';
// If editing, make sure they are editing their own (or they are an admin user).
if ( ! empty ( $id ) ) {
  if ( empty ( $categories[$id] ) )
    $error =
    str_replace ( 'XXX', $id, translate ( 'Invalid entry id XXX.' ) );

  $catcolor = $categories[$id]['cat_color'];
  $catname = $categories[$id]['cat_name'];
  $catowner = $categories[$id]['cat_owner'];
  $catIcon = $icon_path . 'cat-' . $id . '.gif';
  $idStr = '<input name="id" type="hidden" value="' . $id . '" />';
} else
  $catcolor = '#000000';

$showIcon = ( ! empty ( $catIcon ) && file_exists ( $catIcon )
  ? 'visible' : 'hidden' );

print_header ( array ( 'js/visible.php' ) );

ob_start ();

echo '
    <h2>' . translate ( 'Categories' ) . '</h2>
    ' . display_admin_link ();

$add = getGetValue ( 'add' );
if ( empty ( $add ) )
  $add = 0;
// Adding/Editing category.
if ( ( ( $add == '1' ) || ( ! empty ( $id ) ) ) && empty ( $error ) ) {
  echo '
    <form action="category_handler.php" method="post" name="catform" '
   . 'enctype="multipart/form-data">' . $idStr . '
      <table cellspacing="2" cellpadding="3">
        <tr>
          <td width="25%"><label for="catname">' . translate ( 'Category Name' )
   . '</label></td>
          <td colspan="3"><input type="text" name="catname" size="20" value="'
   . htmlspecialchars ( $catname ) . '" /></td>
        </tr>' . ( $is_admin && empty ( $id ) ? '
        <tr>
          <td><label for="isglobal">' . $globalStr . ':</label></td>
          <td colspan="3">
            <label><input type="radio" name="isglobal" value="N" '
     . ( ! empty ( $catowner ) || empty ( $id ) ? ' checked = "checked"' : '' )
     . ' />&nbsp;' . translate ( 'No' ) . '</label>&nbsp;&nbsp;
            <label><input type="radio" name="isglobal" value="Y" '
     . ( empty ( $catowner ) && ! empty ( $id ) ? ' checked = "checked"' : '' )
     . ' />&nbsp;' . translate ( 'Yes' ) . '</label>
          </td>
        </tr>' : '' ) . '
        <tr>
          <td>'
   . print_color_input_html ( 'catcolor', translate ( 'Color' ), $catcolor )
   . '</td>
        </tr>
        <tr id="cat_icon" style="visibility: ' . $showIcon . '">
          <td><label>' . $catIconStr . ':</label></td>
          <td colspan="3"><img src="' . $catIcon
   . '" name="urlpic" id="urlpic" alt="' . $catIconStr . '" /></td>
        </tr>
        <tr id="remove_icon" style="visibility: ' . $showIcon . '">
          <td><label for="delIcon">' . translate ( 'Remove Icon' )
   . '</label></td>
          <td colspan="3"><input type="checkbox" name="delIcon" value="Y" /></td>
        </tr>
        <tr>
          <td colspan="4">
            <label for="FileName">' . ( is_dir ( $icon_path ) &&
    ( $ENABLE_ICON_UPLOADS == 'Y' || $is_admin )
    ? translate ( 'Add Icon to Category' ) . '</label><br />&nbsp;&nbsp;&nbsp;'
     . translate ( 'Upload' ) . '&nbsp;<span style="font-size:small;">'
     . translate ( 'gif 3kb max' ) . '</span>:
            <input type="file" name="FileName" id="fileupload" size="45" '
     . 'maxlength="50" value=""/>
          </td>
        </tr>
        </tr>
          <td colspan="4">
            <input type="hidden" name="urlname" size="50" />&nbsp;&nbsp;&nbsp;
            <input type="button" value="'
     . translate ( 'Search for existing icons' )
     . '" onclick="window.open( \'icons.php\', \'icons\',\''
     . 'dependent,menubar=no,scrollbars=n0,height=300,width=400,outerHeight=320'
     . ',outerWidth=420\' );" />
          </td>
        </tr>
        </tr>
          <td colspan="4">' : '' ) // end test of ENABLE_ICON_UPLOADS
  . '
            <input type="submit" name="action" value="'
   . ( $add == '1' ? translate ( 'Add' ) : translate ( 'Save' ) ) . '" />'
   . ( ! empty ( $id ) ? '
            <input type="submit" name="delete" value="'
     . translate ( 'Delete' ) . '" onclick="return confirm( '
     . str_replace ( 'XXX', translate ( 'entry', true ),
      translate ( 'Are you sure you want to delete this XXX?', true ) )
     . '\' )" />' : '' ) . '
          </td>
        </tr>
      </table>
    </form>';
} else
if ( empty ( $error ) ) {
  // Displaying Categories.
  $global_found = false;
  if ( ! empty ( $categories ) ) {
    echo '
    <ul>';
    foreach ( $categories as $K => $V ) {
      if ( $K < 1 )
        continue;
      $catIcon = $icon_path . 'cat-' . $K . '.gif';
      $catStr = '<span style="color: '
       . ( ! empty ( $V['cat_color'] ) ? $V['cat_color'] : '#000000' )
       . ';">' . htmlentities ( $V['cat_name'] ) . '</span>';
      echo '
      <li>' . ( $V['cat_owner'] == $login || $is_admin
        ? '<a href="category.php?id=' . $K . '">' . $catStr . '</a>' : $catStr );

      if ( empty ( $V['cat_owner'] ) ) {
        echo '<sup>*</sup>';
        $global_found = true;
      }

      echo ( file_exists ( $catIcon ) ? '<img src="' . $catIcon . '" alt="'
         . $catIconStr . '" title="' . $catIconStr . '" />' : '' ) . '</li>';
    }
    echo '
    </ul>';
  }
  echo ( $global_found ? '<br /><br />
    <sup>*</sup> ' . $globalStr : '' ) . '
    <p><a href="category.php?add=1">' . translate ( 'Make New Category' )
   . '</a></p><br />';
}
ob_end_flush ();
echo ( ! empty ( $error ) ? print_error ( $error ) : '' ) . print_trailer ();

?>
