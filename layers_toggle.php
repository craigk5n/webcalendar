<?php
/* $Id$ */
include_once 'includes/init.php';
load_user_layers ();

$status = getValue ( 'status', '(on|off)', true );

if ( $ALLOW_VIEW_OTHER != 'Y' ) {
  print_header ();
  etranslate( 'You are not authorized' );
  echo print_trailer ();
  exit;
}

$updating_public = false;
if ( $is_admin && ! empty ( $public ) && $PUBLIC_ACCESS == 'Y' ) {
  $updating_public = true;
  $layer_user = '__public__';
  $url = 'layers.php?public=1';
} else {
  $layer_user = $login;
  $url = 'layers.php';
}

$sql = 'DELETE FROM webcal_user_pref WHERE cal_login = ? ' .
  "AND cal_setting = 'LAYERS_STATUS'";
dbi_execute ( $sql , array ( $layer_user ) );

$value = ( $status == 'off' ? 'N': 'Y' );

$sql = 'INSERT INTO webcal_user_pref ' .
  '( cal_login, cal_setting, cal_value ) VALUES ' .
  "( ?, 'LAYERS_STATUS', ? )";
if ( ! dbi_execute ( $sql , array ( $layer_user, $value ) ) ) {
  $error = 'Unable to update preference: ' . dbi_error () .
    "<br /><br /><span class=\"bold\">SQL:</span> $sql";
  break;
}

if ( empty ( $error ) ) {
  do_redirect ( $url );
}

print_header();
echo print_error ( $error, true);
echo print_trailer(); 
?>

