<?php
/* $Id: del_layer.php,v 1.20.2.2 2012/02/28 02:07:45 cknudsen Exp $ */
include_once 'includes/init.php';

require_valide_referring_url ();

$id = getGetValue ( 'id' );

if ( $ALLOW_VIEW_OTHER != 'Y' || empty ( $id ) ) {
  print_header ();
  echo print_not_auth (7) . print_trailer ();
  exit;
}
$id = getGetValue ( 'id' );
$updating_public = false;
if ( $is_admin && ! empty ( $public ) && $PUBLIC_ACCESS == 'Y' ) {
  $updating_public = true;
  $layer_user = '__public__';
} else
  $layer_user = $login;

load_user_layers ( $layer_user, 1 );

if ( strlen ( $layers[$id]['cal_layeruser'] ) > 0 &&
    ( $is_admin || $readonly == 'N' ) ) {
  $layeruser = $layers[$id]['cal_layeruser'];

  dbi_execute ( 'DELETE FROM webcal_user_layers WHERE cal_login = ?
    AND cal_layeruser = ?', array ( $layer_user, $layeruser ) );
}

  do_redirect ( 'layers.php' . ( $updating_public ? '?public=1' : '' ) );

?>
