<?php
/* $Id$ */
include_once 'includes/init.php';
send_no_cache_header ();


$status = $WC->getValue ( 'status', '(on|off)', true );
$delete = $WC->getValue ( 'delete', '(1)', true );
$lid = $WC->getValue( 'lid' );
$layer_user =  $WC->userLoginId();

//we are processing a layer delete
if ( ! empty ( $delete ) ) {
  dbi_execute ( 'DELETE FROM webcal_user_layers WHERE cal_login_id = ?
    AND cal_layerid = ?', array ( $layer_user, $lid ) );
} //end test for delete

//we are processing layer toggle
if ( ! empty ( $status ) ) {
  $url = 'layers.php';

  dbi_execute ( 'DELETE FROM webcal_user_pref WHERE cal_login_id = ?
    AND cal_setting = \'LAYERS_STATUS\'', array ( $layer_user ) );

  $sql = 'INSERT INTO webcal_user_pref ( cal_login_id, cal_setting, cal_value )
    VALUES ( ?, \'LAYERS_STATUS\', ? )';
  if ( ! dbi_execute ( $sql, array ( $layer_user,
      ( $status == 'off' ? 'N': 'Y' ) ) ) ) {
    $error = translate ( 'Unable to update preference' ) . ': ' . dbi_error ()
    . '<br /><br /><span class="bold">SQL:</span> ' . $sql;
    break;
  }
  echo error_check ( 'layers.php', true );
} // end test for status

$smarty->assign ( 'u_url', $WC->getUserUrl () );
$layers = loadLayers ( $layer_user, 1 );
$smarty->assign ('layers', $layers);
$layerVars = $WC->User->loadVariables ( $layer_user );
	 
$smarty->assign ('layers_enabled', getPref ( 'LAYERS_STATUS', 0, $layer_user ) );

$smarty->assign('tabs_ar', array ( 'layers'=>translate ( 'Layers' ) ) );

if ( ! $WC->isUser () ) {
  $smarty->assign ('nulist', get_my_nonusers ( $WC->loginId() ) );
} else {
  $smarty->assign ('userStr', '<br /><strong>-- ' . 
   translate( 'Admin mode' ) . ': '. $layerVars['fullname'] .' --</strong>' );
}
	 
build_header ();


if ( ! getPref ( 'ALLOW_VIEW_OTHER' ) )
  echo print_not_auth ();
else
  $smarty->display ( 'layers.tpl' );
?>
