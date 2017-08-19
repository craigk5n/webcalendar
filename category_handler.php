<?php
include_once 'includes/init.php';
require_valid_referring_url ();

$icon_max_size = '6000';
$icon_path = 'icons/';

/**
 * Rename any icons associated with this cat_id.
 */
function renameIcon ( $id ) {
  global $icon_path;
  $bakIcon = $catIcon = $icon_path . 'cat-';
  $bakIcon .= date ( 'YmdHis' ) . '.gif';
  $catIcon .= $id . '.gif';
  if ( ! file_exists ( $catIcon ) )
    $catIcon = 'icons/cat-' . $id . '.png';
  if ( file_exists ( $catIcon ) )
    rename ( $catIcon, $bakIcon );
}

// Does the category belong to the user?
$is_my_event = false;
$id = getValue ( 'id' );
$catname = getValue ( 'catname' );
// prohibit any html in category name (including <script>)
$catname = strip_tags ( $catname );
$catcolor = getValue ( 'catcolor' );
$isglobal = getValue ( 'isglobal' );
$delIcon = getPostValue ( 'delIcon' );
if ( empty ( $id ) )
  $is_my_event = true; // New event.
else {
  $res = dbi_execute ( 'SELECT cat_id, cat_owner FROM webcal_categories
  WHERE cat_id = ?', [$id] );
  if ( $res ) {
    $row = dbi_fetch_row ( $res );

    $is_my_event = ( $row[0] == $id && $row[1] == $login ||
      ( empty ( $row[1] ) && $is_admin ) );

    dbi_free_result ( $res );
  } else
    $error = db_error();
}

if ( ! empty ( $_FILES['FileName'] ) )
  $file = $_FILES['FileName'];

// Make sure we clear $file if no file was upoaded.
if ( ! empty ( $file['tmp_name'] ) && $file['tmp_name'] == 'none' )
  $file = '';

if ( ! $is_my_event )
  $error = print_not_auth();

$delete = getPostValue ( 'delete' );
if ( empty ( $error ) && ! empty ( $delete ) ) {
  // Delete this category.
  if ( ! dbi_execute ( 'DELETE FROM webcal_categories
    WHERE cat_id = ? AND ( cat_owner = ?'
       . ( $is_admin ? ' OR cat_owner IS NULL )' : ' )' ),
    [$id, $login] ) ) {
    $error = db_error();
  }

  if ( ! dbi_execute ( 'DELETE FROM webcal_entry_categories
    WHERE cat_id = ? AND ( cat_owner = ?'
       . ( $is_admin ? ' OR cat_owner IS NULL )' : ' )' ),
    [$id, $login] ) ) {
    $error = db_error();
  }
  // Rename any icons associated with this cat_id.
  renameIcon ( $id );
} else if ( empty ( $error ) && empty ( $catname ) ) {
  $error = translate ( 'Category name is required' );
} else if ( empty ( $error ) ) {
  if ( ! empty ( $id ) ) {
    # Update (don't let them change global status).
    if ( ! dbi_execute ( 'UPDATE webcal_categories
      SET cat_name = ?, cat_color = ? WHERE cat_id = ?',
      [$catname, $catcolor, $id] ) )
      $error = db_error();

    if ( ! empty ( $delIcon ) && $delIcon == 'Y' )
      renameIcon ( $id );
  } else {
    // Add new category.
    // Get new id.
    $res = dbi_execute ( 'SELECT MAX( cat_id ) FROM webcal_categories' );
    if ( $res ) {
      $row = dbi_fetch_row ( $res );
      $id = $row[0] + 1;
      dbi_free_result ( $res );
      $catowner = ( $is_admin
        ? ( $isglobal == 'Y' ? null : $login )
        : $login );

      if ( ! dbi_execute ( 'INSERT INTO webcal_categories ( cat_id, cat_owner,
        cat_name, cat_color ) VALUES ( ?, ?, ?, ? )',
        [$id, $catowner, $catname, $catcolor] ) )
        $error = db_error();
    } else
      $error = db_error();
  }
  if ( empty ( $delIcon ) && is_dir( $icon_path ) && ( !
        empty ( $ENABLE_ICON_UPLOADS ) && $ENABLE_ICON_UPLOADS == 'Y' ||
        $is_admin ) ) {
    // Save icon if uploaded.
    if ( ! empty ( $file['tmp_name'] ) ) {
      if ( ( $file['type'] == 'image/gif' || $file['type'] == 'image/png' )
        && $file['size'] <= $icon_max_size ) {
        // $icon_props = getimagesize( $file['tmp_name'] );
        // print_r ($icon_props );
        $path_parts = pathinfo ( $_SERVER['SCRIPT_FILENAME'] );
        $fullIcon = $path_parts['dirname'] . '/'
         . $icon_path . 'cat-' . $id;
        if ( $file['type'] == 'image/gif' )
          $fullIcon .= '.gif';
        else
          $fullIcon .= '.png';
        renameIcon ( $id );
        $file_result = move_uploaded_file ( $file['tmp_name'], $fullIcon );
        // echo "Upload Result:" . $file_result;
      } else if ( $file['size'] > $icon_max_size ) {
        $error = translate ( 'File size exceeds maximum.' );
      } else if ( $file['type'] != 'image/gif' &&
        $file['type'] != 'image/png' ) {
        $error = translate ( 'File is not a GIF or PNG image' ) . ': '
          . $file['type'];
      }
    }
    // Copy icon if local file specified.
    $urlname = getPostvalue ( 'urlname' );
    if ( ! empty ( $urlname ) && file_exists ( $icon_path . $urlname ) ) {
      if ( preg_match ( '/.(gif|GIF)$/', $urlname ) )
        copy ( $icon_path . $urlname, $icon_path . 'cat-' . $id . '.gif' );
      else
        copy ( $icon_path . $urlname, $icon_path . 'cat-' . $id . '.png' );
    }
  }
}

if ( empty ( $error ) )
  do_redirect ( 'category.php' );

print_header();
echo print_error ( $error ) . print_trailer();

?>
