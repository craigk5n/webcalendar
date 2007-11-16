<?php
/* $Id$ */
include_once 'includes/init.php';

$icon_max_size = '3000';
$icon_path = 'icons/';

$isglobal = $WC->getPost ( 'isglobal' );
$catname = $WC->getPost ( 'catname' );
$catcolor  = $WC->getPost ( 'catcolor ' );
$caticonname  = $WC->getPost ( 'caticonname ' );
$caticon  = $WC->getPost ( 'caticon ' );

if ( ! empty ( $_FILES['FileName'] ) )
  $file = $_FILES['FileName'];

// Make sure we clear $file if no file was upoaded.
if ( ! empty ( $file['tmp_name'] ) && $file['tmp_name'] == 'none' )
  $file = '';

if ( ! $WC->isMyCat ( $cid ) )
  $error = print_not_auth ();

$delete = $WC->getPOST ( 'delete' );
if ( empty ( $error ) && ! empty ( $delete ) ) {
  // Delete this category.
  $WC->deleteCat ( $cid );

} else if ( empty ( $error ) ) {
  if ( ! empty ( $cid ) ) {
	  $delIcon = $WC->getPOST ( 'delIcon' );
    if ( ! empty ( $delIcon ) && $delIcon == 'Y' )
      $caticon = '';
    # Update (don't let them change global status).
    if ( ! dbi_execute ( 'UPDATE webcal_categories
      SET cat_name = ?, cat_color = ?, cat_icon = ? WHERE cat_id = ?',
        array( $catname, $catcolor, $caticon, $cid ) ) )
      $error = db_error ();

  } else {
    // Add new category.
    // Get new id.
    $res = dbi_execute ( 'SELECT MAX( cat_id ) FROM webcal_categories' );
    if ( $res ) {
      $row = dbi_fetch_row ( $res );
      $cid = $row[0] + 1;
      dbi_free_result ( $res );
      $catowner = ( $WC->isAdmin()
        ? ( $isglobal == 'Y' ? null : $WC->loginId() )
        : $WC->loginId() );

      if ( ! dbi_execute ( 'INSERT INTO webcal_categories ( cat_id,
        cat_name, cat_color ) VALUES ( ?, ?, ? )',
          array( $cid, $catname, $catcolor ) ) )
        $error = db_error ();
    } else
      $error = db_error ();
  }
  if ( empty ( $delIcon ) && @is_dir( $icon_path ) && 
    ( getPref ('ENABLE_ICON_UPLOADS') ||
        $WC->isAdmin() ) ) {
    // Save icon if uploaded.
    if ( ! empty ( $file['tmp_name'] ) ) {
      if ( ( $file['type'] == 'image/gif' || 
			       $file['type'] == 'image/jpg' || 
						 $file['type'] == 'image/png') && $file['size'] <= $icon_max_size ) {

        $path_parts = pathinfo ( $_SERVER['SCRIPT_FILENAME'] );
        $fullIcon = $path_parts['dirname'] . '/'
         . $icon_path . 'cat-' . $cid . '.gif';

        $file_result = move_uploaded_file ( $file['tmp_name'], $fullIcon );
        // echo "Upload Result:" . $file_result;
      } else
      if ( $file['size'] > $icon_max_size )
        $error = translate ( 'File size exceeds maximum.' );
      else
      if ( $file['type'] != 'image/gif' || $file['type'] != 'image/jpg' || 
				$file['type'] != 'image/png' )
        $error = translate ( 'File type is not supported' ) . ' (' . $file['type'] . ')';
    }
  }
}

if ( empty ( $error ) )
  do_redirect ( 'category.php' );

build_header ();
$smarty->assign ( 'errorStr', $error );
$smarty->display ( 'error.tpl' );

?>
