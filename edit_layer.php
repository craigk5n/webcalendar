<?php
/* $Id$ */
include_once 'includes/init.php';

if ( ! getPref ( 'ALLOW_VIEW_OTHER' ) ) {
  build_header ();
	$smarty->assign ( 'not_auth', true );
  $smarty->display ( 'error.tpl' );
  exit;
}

$dups = $WC->getPOST ( 'dups', 'N' );
$layer_user = $WC->userLoginId();
$layer_cal = $WC->getPOST ( 'layeruser' );
$layercolor = $WC->getPOST ( 'layercolor' );
$cal_login = $WC->getPOST ( 'cal_login' );
$do_layer_edit = $WC->getPOST ( 'do_layer_edit' );
$lid = $WC->getValue( 'lid' );

//we are processing a layer change
if ( ! empty ( $do_layer_edit ) ) {
  if ( empty ( $cal_login ) )
    save_layer ( $layer_user, $layer_cal, $layercolor, $dups, $lid );
  else {
    // See if we are processing multiple layer_users as admin.
    if ( $WC->isAdmin() && ! empty ( $cal_login ) ) {
      for ( $i = 0, $cnt = count ( $cal_login ); $i < $cnt; $i++ ) {
        save_layer ( $cal_login[$i], $layer_cal, $layercolor, 'N', $lid, true );
      }
    }
  }
  echo error_check ( 'layers.php' . 
    ( $WC->userId() ? '?user=' . $WC->userId() : '' ) , false );
} else { //we are editing a layer


$layer_user = $WC->userLoginId();

$layers = loadLayers ( $layer_user, 1 );

if ( ! _WC_SINGLE_USER ) {
  $others = $userlist = get_my_users ( '', 'view' );
  if ( getpref ( 'NONUSER_ENABLED' ) ) {
    // Restrict NUC list if groups are enabled.
    $nonusers = get_my_nonusers ( $layer_user, true, 'view' );
    $userlist = ( getpref ( 'NONUSER_AT_TOP' )
      ? array_merge ( $nonusers, $userlist )
      : array_merge ( $userlist, $nonusers ) );
  }
  if ( getPref ( 'REMOTES_ENABLED', 2 ) ) {
    $remotes = get_nonuser_cals ( $layer_user, true );
    $userlist = ( getpref ( 'NONUSER_AT_TOP' )
      ? array_merge ( $remotes, $userlist )
      : array_merge ( $userlist, $remotes ) );
  }
	$smarty->assign ( 'userlist', $userlist );
	$smarty->assign ( 'others', $others );
}

if ( $lid )
  $smarty->assign ( 'layer', $layers[$lid] );

$INC = array ( 'edit_layer.js' );
build_header ( $INC, '', '', 5 );

$smarty->display ( 'edit_layer.tpl' );

} // end test if processing form post

function save_layer ( $layer_user, $layer_cal, $layercolor, $dups, $lid, $multi=false ) {
  global $error;
  if ( $layer_user == $layer_cal  ) {
	  if ( ! $multi )
      $error = translate ( 'You cannot create a layer for yourself' ) . '.';
    return false;
  }
  $layers = loadLayers ( $layer_user, 1 );

  if ( ! empty ( $layer_cal ) && $error == '' ) {
    // existing layer entry
    if ( ! empty ( $layers[$lid]['cal_layeruser_id'] ) ) {
      // Update existing layer entry for this user.
      $layerid = $layers[$lid]['cal_layerid'];

      dbi_execute ( 'UPDATE webcal_user_layers SET cal_layeruser_id = ?,
        cal_color = ?, cal_dups = ? WHERE cal_layerid = ?',
        array ( $layer_cal, $layercolor, $dups, $layerid ) );
    } else {
      // new layer entry
      // Check for existing layer for user. Can only have one layer per user.
      $res = dbi_execute ( 'SELECT COUNT(cal_layerid) FROM webcal_user_layers
        WHERE cal_login_id = ? AND cal_layeruser_id = ?',
        array ( $layer_user, $layer_cal ) );
      if ( $res ) {
        $row = dbi_fetch_row ( $res );
        $dup = ( $row[0] > 0 );
				if ( $dup && ! $multi )
          $error = translate ( 'You can only create one layer for each user' );
        dbi_free_result ( $res );
      }
      if ( $error == '' && ! $dup ) {
        $res =
        dbi_execute ( 'SELECT MAX( cal_layerid ) FROM webcal_user_layers' );
        if ( $res ) {
          $row = dbi_fetch_row ( $res );
          $layerid = $row[0] + 1;
        } else
          $layerid = 1;

        dbi_execute ( 'INSERT INTO webcal_user_layers ( cal_layerid, cal_login_id,
          cal_layeruser_id, cal_color, cal_dups ) VALUES ( ?, ?, ?, ?, ? )',
          array ( $layerid, $layer_user, $layer_cal, $layercolor, $dups ) );
      }
    }
  }
}
?>
