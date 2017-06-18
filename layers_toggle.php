<?php
/* $Id: layers_toggle.php,v 1.29.2.2 2008/03/11 13:57:24 cknudsen Exp $ */
include_once 'includes/init.php';
load_user_layers ();

$status = getValue ( 'status', '(on|off)', true );
$public = getValue ( 'public' );

if ( $ALLOW_VIEW_OTHER != 'Y' ) {
  print_header ();
  echo print_not_auth (7) . print_trailer ();
  exit;
}

$updating_public = false;
$url = 'layers.php';

if ( $is_admin && ! empty ( $public ) && $PUBLIC_ACCESS == 'Y' ) {
  $updating_public = true;
  $layer_user = '__public__';
  $url .= '?public=1';
} else
  $layer_user = $login;

dbi_execute ( 'DELETE FROM webcal_user_pref WHERE cal_login = ?
  AND cal_setting = \'LAYERS_STATUS\'', array ( $layer_user ) );

$sql = 'INSERT INTO webcal_user_pref ( cal_login, cal_setting, cal_value )
  VALUES ( ?, \'LAYERS_STATUS\', ? )';
if ( ! dbi_execute ( $sql, array ( $layer_user,
      ( $status == 'off' ? 'N': 'Y' ) ) ) ) {
  $error = translate ( 'Unable to update preference' ) . ': ' . dbi_error ()
   . '<br /><br /><span class="bold">SQL:</span> ' . $sql;
  break;
}

if ( empty ( $error ) )
  do_redirect ( $url );

print_header ();
echo print_error ( $error, true ) . print_trailer ();

?>
