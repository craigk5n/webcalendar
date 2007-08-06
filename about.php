<?php
/* $Id$ */
include_once 'includes/init.php';

$credits = $WC->getPOST ( 'Credits' );
$data = '';

if ( ! empty ( $credits ) ) {
  // Get Names from AUTHORS file
  if ( $fd = @fopen ( 'AUTHORS', 'r' ) ) {
    // Read in contents of entire file first.
    while ( ! feof ( $fd ) && empty ( $error ) ) {
      $data .= fgets ( $fd, 4096 );
    }
    fclose ( $fd );
  }
  // $data = unhtmlentities ( $data );
  $data = preg_replace ( '/<.+>+/', '', $data );
  $data = preg_replace ( "/\n\s/", '<br />&nbsp;', $data );
  $data = preg_replace ( '/\s\s+/', '&nbsp;&nbsp;', $data );
  $data = preg_replace ( '/\n/', '<br />', $data );
}
build_header ( '', '', '' );
$smarty->assign ( 'credits', $credits );
$smarty->assign ( 'data', $data );
$smarty->display ( 'about.tpl' );
?>

