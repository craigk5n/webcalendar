<?php

include "includes/config.php";
include "includes/php-dbi.php";
include "includes/functions.php";
include "includes/$user_inc";
include "includes/validate.php";
include "includes/connect.php";

load_global_settings ();
load_user_preferences ();
// reset LAYERS_STATUS to make sure layers get loaded
$LAYERS_STATUS = "Y";
load_user_layers ();

if ( strlen ( $layers[$id]['cal_layeruser'] ) > 0 &&
  ( $is_admin || $readonly == "N" ) ) {
  $layeruser = $layers[$id]['cal_layeruser'];

  dbi_query ( "DELETE FROM webcal_user_layers WHERE cal_login = '$login' AND cal_layeruser = '$layeruser'" );
}

do_redirect ( "layers.php" );
?>
