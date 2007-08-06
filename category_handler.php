<?php
/* $Id$ */
include_once 'includes/init.php';

$icon_max_size = '3000';
$icon_path = 'icons/';

$isglobal = $WC->getPost ( 'isglobal' );
$catname = $WC->getPost ( 'catname' );
$catcolor  = $WC->getPost ( 'catcolor ' );

/* Rename any icons associated with this cat_id. */
function renameIcon ( $eid ) {
  global $icon_path;
  $bakIcon = $catIcon = $icon_path . 'cat-';
  $bakIcon .= date ( 'YmdHis' ) . '.gif';
  $catIcon .= $eid . '.gif';
  if ( file_exists ( $catIcon ) )
    rename ( $catIcon, $bakIcon );
}

// Does the category belong to the user?
$is_my_event = false;
if ( empty ( $eid ) )
  $is_my_event = true; // New event.
else {
  $res = dbi_execute ( 'SELECT cat_id, cat_owner FROM webcal_categories
    WHERE cat_id = ?', array( $eid ) );
  if ( $res ) {
    $row = dbi_fetch_row ( $res );

    $is_my_event = ( $row[0] == $eid && $WC->isLogin( $row[1] ) ||
      ( empty ( $row[1] ) && $WC->isAdmin() ) );

    dbi_free_result ( $res );
  } else
    $error = db_error ();
}

if ( ! empty ( $_FILES['FileName'] ) )
  $file = $_FILES['FileName'];

// Make sure we clear $file if no file was upoaded.
if ( ! empty ( $file['tmp_name'] ) && $file['tmp_name'] == 'none' )
  $file = '';

if ( ! $is_my_event )
  $error = print_not_auth ();

$delete = $WC->getPOST ( 'delete' );
if ( empty ( $error ) && ! empty ( $delete ) ) {
  // Delete this category.
  if ( ! dbi_execute( 'DELETE FROM webcal_categories
    WHERE cat_id = ? AND ( cat_owner = ?'
       . ( $WC->isAdmin() ? ' OR cat_owner IS NULL )' : ' )' ),
        array( $eid, $WC->loginId() ) ) ) {
    $error = db_error ();
  }

  if ( ! dbi_execute( 'DELETE FROM webcal_entry_categories
    WHERE cat_id = ? AND ( cat_owner = ?'
       . ( $WC->isAdmin() ? ' OR cat_owner IS NULL )' : ' )' ),
        array( $eid, $WC->loginId() ) ) ) {
    $error = db_error ();
  }
  // Rename any icons associated with this cat_id.
  renameIcon ( $eid );
} else if ( empty ( $error ) ) {
  if ( ! empty ( $eid ) ) {
    # Update (don't let them change global status).
    if ( ! dbi_execute ( 'UPDATE webcal_categories
      SET cat_name = ?, cat_color = ? WHERE cat_id = ?',
        array( $catname, $catcolor, $eid ) ) )
      $error = db_error ();

    $delIcon = $WC->getPOST ( 'delIcon' );
    if ( ! empty ( $delIcon ) && $delIcon == 'Y' )
      renameIcon ( $eid );
  } else {
    // Add new category.
    // Get new id.
    $res = dbi_execute ( 'SELECT MAX( cat_id ) FROM webcal_categories' );
    if ( $res ) {
      $row = dbi_fetch_row ( $res );
      $eid = $row[0] + 1;
      dbi_free_result ( $res );
      $catowner = ( $WC->isAdmin()
        ? ( $isglobal == 'Y' ? null : $WC->loginId() )
        : $WC->loginId() );

      if ( ! dbi_execute ( 'INSERT INTO webcal_categories ( cat_id, cat_owner,
        cat_name, cat_color ) VALUES ( ?, ?, ?, ? )',
          array( $eid, $catowner, $catname, $catcolor ) ) )
        $error = db_error ();
    } else
      $error = db_error ();
  }
  if ( empty ( $delIcon ) && @is_dir( $icon_path ) && 
    ( getPref ('ENABLE_ICON_UPLOADS') ||
        $WC->isAdmin() ) ) {
    // Save icon if uploaded.
    if ( ! empty ( $file['tmp_name'] ) ) {
      if ( $file['type'] == 'image/gif' && $file['size'] <= $icon_max_size ) {
        // $icon_props = getimagesize ( $file['tmp_name']  );
        // print_r ($icon_props );
        $path_parts = pathinfo ( $_SERVER['SCRIPT_FILENAME'] );
        $fullIcon = $path_parts['dirname'] . '/'
         . $icon_path . 'cat-' . $eid . '.gif';
        renameIcon ( $eid );
        $file_result = move_uploaded_file ( $file['tmp_name'], $fullIcon );
        // echo "Upload Result:" . $file_result;
      } else
      if ( $file['size'] > $icon_max_size )
        $error = translate ( 'File size exceeds maximum.' );
      else
      if ( $file['type'] != 'image/gif' )
        $error = translate ( 'File is not a gif image.' );
    }
    // Copy icon if local file specified.
    $urlname = $WC->getPOST ( 'urlname' );
    if ( ! empty ( $urlname ) && file_exists ( $icon_path . $urlname ) )
      copy ( $icon_path . $urlname, $icon_path . 'cat-' . $eid . '.gif' );
  }
}

if ( empty ( $error ) )
  do_redirect ( 'category.php' );

build_header ();
$smarty->assign ( 'errorStr', $error );
$smarty->display ( 'error.tpl' );

?>
