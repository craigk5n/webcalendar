<?php
include_once 'includes/init.php';

if ( $ALLOW_VIEW_OTHER != 'Y' ) {
  print_header ();
  etranslate("You are not authorized");
  print_trailer ();
  exit;
}

$updating_public = false;
if ( $is_admin && ! empty ( $public ) && $PUBLIC_ACCESS == "Y" ) {
  $updating_public = true;
  $layer_user = "__public__";
} else {
  $layer_user = $login;
}

load_user_layers ( $layer_user, 1 );

if ( strlen ( $layers[$id]['cal_layeruser'] ) > 0 &&
  ( $is_admin || $readonly == "N" ) ) {
  $layeruser = $layers[$id]['cal_layeruser'];

  dbi_execute ( "DELETE FROM webcal_user_layers WHERE cal_login = ? AND cal_layeruser = ?", array( $layer_user, $layeruser ) );
}

if ( $updating_public )
  do_redirect ( "layers.php?public=1" );
else
  do_redirect ( "layers.php" );
?>
