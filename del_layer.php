<?php php_track_vars?>
<?php

include "includes/config.inc";
include "includes/php-dbi.inc";
include "includes/functions.inc";
include "includes/validate.inc";
include "includes/connect.inc";

load_user_preferences ();
load_user_layers ();

if ( strlen ( $layers[$id]['cal_layeruser'] ) > 0 &&
  ( $is_admin || ! $readonly ) ) {
  $layeruser = $layers[$id]['cal_layeruser'];

  dbi_query ( "DELETE FROM webcal_user_layers WHERE cal_login = '$login' AND cal_layeruser = '$layeruser'" );
}

do_redirect ( "layers.php" );
?>
