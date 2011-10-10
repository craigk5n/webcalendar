<?php // $Id$

include_once 'includes/init.php';
// Load user and global cats.
load_user_categories();

if ( $CATEGORIES_ENABLED == 'N' ) {
  send_to_preferred_view();
  exit;
}

// Verify that permissions allow writing to the "icons" directory.
$canWrite = $permError = false;
if ( $ENABLE_ICON_UPLOADS == 'Y' || $is_admin ) {
  $testFile = 'icons/testWrite.txt';
  $testFd = @fopen ( $testFile, 'w+b', false );
  @fclose ( $testFd );
  $canWrite = file_exists ( $testFile );
  if ( ! $canWrite ) {
    $permError = true;
  } else {
    @unlink ( $testFile );
  }
}

$catIcon = $catname = $error = $idStr = '';
$catIconStr = translate ( 'Category Icon' );
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
  // Try PNG if GIF not found
  if ( ! file_exists ( $catIcon ) )
    $catIcon = $icon_path . 'cat-' . $id . '.png';
  $idStr = '<input name="id" type="hidden" value="' . $id . '">';
} else
  $catcolor = '#000000';

$showIconStyle = ( ! empty ( $catIcon ) && file_exists ( $catIcon )
  ? '' : 'display: none;' );

ob_start();
print_header();

echo '
    <h2>' . translate ( 'Categories' ) . '</h2>
    ' . display_admin_link( false );

// Display permission error if found above.
if ( $permError && $is_admin ) {
  print_error_box (
    translate('icons dir is read-only') );
}

$add = getGetValue ( 'add' );
if ( empty ( $add ) )
  $add = 0;
// Adding/Editing category.
if ( ( $add == '1' || ! empty( $id ) ) && empty( $error ) ) {
  echo '
    <form action="category_handler.php" method="post" name="catform" '
   . 'enctype="multipart/form-data">' . $idStr . '
      <table cellspacing="2" cellpadding="3" summary="">
        <tr>
          <td width="25%"><label for="catname">' . translate ( 'Category Name' )
   . '</label></td>
          <td colspan="3"><input type="text" name="catname" size="20" value="'
   . htmlspecialchars( $catname ) . '"></td>
        </tr>' . ( $is_admin && empty ( $id ) ? '
        <tr>
          <td><label for="isglobal">' . translate( 'Global_' ) . '</label></td>
          <td colspan="3">
            <label><input type="radio" name="isglobal" value="N"'
     . ( ! empty ( $catowner ) || empty ( $id ) ? ' checked' : '' )
     . '>&nbsp;' . $noStr . '</label>&nbsp;&nbsp;
            <label><input type="radio" name="isglobal" value="Y"'
     . ( empty ( $catowner ) && ! empty ( $id ) ? ' checked' : '' )
     . '>&nbsp;' . $yesStr . '</label>
          </td>
        </tr>' : '' ) . '
        <tr>
          <td>'
   . print_color_input_html ( 'catcolor', translate ( 'Color' ), $catcolor )
   . '</td>
        </tr>
        <tr id="cat_icon" style="' . $showIconStyle . '">
          <td><label>' . translate( 'Category Icon_' ) . '</label></td>
          <td colspan="3"><img src="' . $catIcon
   . '" name="urlpic" id="urlpic" alt="' . $catIconStr . '"></td>
        </tr>
        <tr id="remove_icon" style="' . $showIconStyle . '">
          <td><label for="delIcon">' . translate ( 'Remove Icon' )
   . '</label></td>
          <td colspan="3"><input type="checkbox" name="delIcon" value="Y"></td>
        </tr>
        <tr>
          <td colspan="4">
            <label for="FileName">' . ( is_dir ( $icon_path ) &&
    ( ( $ENABLE_ICON_UPLOADS == 'Y' || $is_admin ) && $canWrite )
    ? translate( 'Add Icon to Category' ) . '</label><br>&nbsp;&nbsp;&nbsp;'
     . translate ( 'Upload' ) . '&nbsp;<span style="font-size:small;">'
     . translate ( 'GIF or PNG 3kb max' ) . '</span>:
            <input type="file" name="FileName" id="fileupload" size="45" '
     . 'maxlength="50" value="">
          </td>
        </tr>
        </tr>
          <td colspan="4">
            <input type="hidden" name="urlname" size="50">&nbsp;&nbsp;&nbsp;
            <input type="button" id="searchIcon" value="'
     . translate( 'Search for existing icons' ) . '">
          </td>
        </tr>
        </tr>
          <td colspan="4">' : '' ) // end test of ENABLE_ICON_UPLOADS
  . '
            <input type="submit" name="action" value="'
   . ( $add == '1' ? $addStr : $saveStr ) . '">'
   . ( ! empty ( $id ) ? '
            <input type="submit" id="deleIcon" name="delete" value="'
     . $deleteStr . '">' : '' ) . '
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
      if ( ! file_exists ( $catIcon ) )
        $catIcon = $icon_path . 'cat-' . $K . '.png';

      $catColStr = '<span style="color: '
       . ( ! empty ( $V['cat_color'] ) ? $V['cat_color'] : '#000000' )
       . ';">' . $V['cat_name'] . '</span>';
      echo '
      <li>' . ( $V['cat_owner'] == $login || $is_admin
        ? '<a href="category.php?id=' . $K . '">' . $catColStr . '</a>' : $catColStr );

      if ( empty ( $V['cat_owner'] ) ) {
        echo '<sup>*</sup>';
        $global_found = true;
      }

      echo ( file_exists ( $catIcon ) ? '<img src="' . $catIcon . '" alt="'
         . $catIconStr . '" title="' . $catIconStr . '">' : '' ) . '</li>';
    }
    echo '
    </ul>';
  }
  echo ( $global_found ? '<br><br>
    <sup>*</sup> ' . translate( 'Global' ) : '' ) . '
    <p><a href="category.php?add=1">' . translate ( 'Make New Category' )
   . '</a></p><br>';
}
echo ( ! empty ( $error ) ? print_error ( $error ) : '' ) . print_trailer();
ob_end_flush();

?>
