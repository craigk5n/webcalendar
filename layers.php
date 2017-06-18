<?php
/* $Id: layers.php,v 1.48.2.4 2008/03/11 13:57:24 cknudsen Exp $ */
include_once 'includes/init.php';
send_no_cache_header ();

$layer_user = $login;
$u_url = '';
$updating_public = false;

$public = getValue ( 'public' );

if ( $is_admin && ! empty ( $public ) && $PUBLIC_ACCESS == 'Y' ) {
  $layer_user = '__public__';
  $u_url = '&amp;public=1';
  $updating_public = true;
}

load_user_layers ( $layer_user, 1 );

$layers_enabled = 0;
$res = dbi_execute ( 'SELECT cal_value FROM webcal_user_pref
  WHERE cal_setting = \'LAYERS_STATUS\' AND cal_login = ?',
  array ( $layer_user ) );
if ( $res ) {
  $row = dbi_fetch_row ( $res );
  $layers_enabled = ( $row[0] == 'Y' ? 1 : 0 );
  dbi_free_result ( $res );
}

$layerStr = translate ( 'Layer' );
$editLayerStr = translate ( 'Edit layer' );
$editStr = translate ( 'Edit' );
$deleteStr = translate ( 'Delete' );
$deleteLayerStr = translate ( 'Delete layer' );
$areYouSureStr = translate ( 'Are you sure you want to delete this XXX?' );
$sourceStr = translate ( 'Source' );
$colorStr = translate ( 'Color' );
$duplicatesStr = translate ( 'Duplicates' );
$noStr = translate ( 'No' );
$yesStr = translate ( 'Yes' );
$disabledStr = translate ( 'Disabled' );
$enableLayersStr = translate ( 'Enable layers' );

print_header ();

ob_start ();

if ( $ALLOW_VIEW_OTHER != 'Y' )
  echo print_not_auth (7);
else {
  echo '
    <h2>' . ( $updating_public
    ? translate ( $PUBLIC_ACCESS_FULLNAME ) . '&nbsp;' : '' )
   . translate ( 'Layers' ) . '&nbsp;<img src="images/help.gif" alt="'
   . translate ( 'Help' ) . '" class="help" onclick="window.open( '
   . '\'help_layers.php\', \'cal_help\', \'dependent,menubar,scrollbars,'
   . 'height=400,width=400,innerHeight=420,outerWidth=420\' );" /></h2>
    ' . display_admin_link () . translate ( 'Layers are currently' )
   . '&nbsp;<strong>';

  if ( $layers_enabled ) {
    echo translate ( 'Enabled' ) . '</strong>. (<a class="nav" '
     . 'href="layers_toggle.php?status=off' . $u_url . '">'
     . translate ( 'Disable Layers' ) . '</a>)<br />'
     . ( $is_admin && empty ( $public ) &&
      ( ! empty ( $PUBLIC_ACCESS ) && $PUBLIC_ACCESS == 'Y' ) ? '
    <blockquote>
      <a href="layers.php?public=1">'
       . translate ( 'Click here' ) . '&nbsp;'
       . translate ( 'to modify the layers settings for the' ) . '&nbsp;'
       . translate ( $PUBLIC_ACCESS_FULLNAME ) . '&nbsp;'
       . translate ( 'calendar' ) . '.</a>
    </blockquote>' : '' ) . '
    <a href="edit_layer.php' . ( $updating_public ? '?public=1' : '' )
     . '">' . translate ( 'Add layer') . '</a><br />';

    $layer_count = 1;
    if ( $layers ) {
      foreach ( $layers as $layer ) {
        user_load_variables ( $layer['cal_layeruser'], 'layer' );

        echo '
    <div class="layers" style="color: ' . $layer['cal_color'] . '">
      <h4>' . $layerStr . '&nbsp;' . $layer_count . '
        (<a title="' . $editLayerStr
         . '" href="edit_layer.php?id=' . $layer['cal_layerid'] . $u_url . '">'
         . $editStr . '</a> /
        <a title="' . $deleteLayerStr
         . '" href="del_layer.php?id=' . $layer['cal_layerid'] . $u_url
         . '" onclick="return confirm( \''
         . str_replace ( 'XXX', $layerStr, $areYouSureStr )
         . '\' );">' . $deleteStr . '</a>)</h4>
      <p><label>' . $sourceStr . ': </label>' . $layerfullname
         . '</p>
      <p><label>' . $colorStr . ': </label>'
         . $layer['cal_color'] . ')</p>
      <p><label>' . $duplicatesStr . ': </label>'
         . ( $layer['cal_dups'] == 'N'
          ? $noStr : $yesStr ) . '</p>
    </div>';

        $layer_count++;
      }
    }
  } else
    echo $disabledStr . '</strong>. (<a class="nav" '
     . 'href="layers_toggle.php?status=on' . $u_url . '">'
     . $enableLayersStr . '</a>)<br />';
}

ob_end_flush ();

echo print_trailer ();

?>
