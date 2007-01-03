<?php
/* $Id$ */
include_once 'includes/init.php';
send_no_cache_header ();

$layer_user = $login;
$u_url = '';
$updating_public = false;

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

print_header ();

ob_start ();

if ( $ALLOW_VIEW_OTHER != 'Y' )
  echo print_not_auth ();
else {
  echo '
    <h2>' . ( $updating_public
    ? translate ( $PUBLIC_ACCESS_FULLNAME ) . '&nbsp;' : '' )
   . $translations['Layers'] . '&nbsp;<img src="images/help.gif" alt="'
   . $translations['Help'] . '" class="help" onclick="window.open ( '
   . '\'help_layers.php\', \'cal_help\', \'dependent,menubar,scrollbars,'
   . 'height=400,width=400,innerHeight=420,outerWidth=420\');" /></h2>
    ' . display_admin_link () . translate ( 'Layers are currently' )
   . '&nbsp;<strong>';

  if ( $layers_enabled ) {
    echo $translations['Enabled'] . '</strong>. (<a class="nav" '
     . 'href="layers_toggle.php?status=off' . $u_url . '">'
     . translate ( 'Disable Layers' ) . '</a>)<br />'
     . ( $is_admin && empty ( $public ) &&
      ( ! empty ( $PUBLIC_ACCESS ) && $PUBLIC_ACCESS == 'Y' ) ? '
    <blockquote>
      <a href="layers.php?public=1">'
       . translate ( 'Click here' ) . '&nbsp;'
       . translate ( 'to modify the layers settings for the' ) . '&nbsp;'
       . $translations[$PUBLIC_ACCESS_FULLNAME] . '&nbsp;'
       . $translations['calendar'] . '.</a>
    </blockquote>' : '' ) . '
    <a href="edit_layer.php' . ( $updating_public ? '?public=1' : '' )
     . '">' . $translations['Add layer'] . '</a><br />';

    $layer_count = 1;
    if ( $layers ) {
      foreach ( $layers as $layer ) {
        user_load_variables ( $layer['cal_layeruser'], 'layer' );

        echo '
    <div class="layers" style="color: ' . $layer['cal_color'] . '">
      <h4>' . translate ( 'Layer' ) . '&nbsp;' . $layer_count . '
        (<a title="' . $translations['Edit layer']
         . '" href="edit_layer.php?id=' . $layer['cal_layerid'] . $u_url . '">'
         . $translations['Edit'] . '</a> /
        <a title="' . $translations['Delete layer']
         . '" href="del_layer.php?id=' . $layer['cal_layerid'] . $u_url
         . '" onclick="return confirm (\''
         . str_replace ( 'XXX', $translations['layer'],
          $translations['Are you sure you want to delete this XXX?'] )
         . '\');">' . $translations['Delete'] . '</a>)</h4>
      <p><label>' . $translations['Source'] . ': </label>' . $layerfullname
         . '</p>
      <p><label>' . $translations['Color'] . ': </label>'
         . $layer['cal_color'] . ')</p>
      <p><label>' . $translations['Duplicates'] . ': </label>'
         . ( $layer['cal_dups'] == 'N'
          ? $translations['No'] : $translations['Yes'] ) . '</p>
    </div>';

        $layer_count++;
      }
    }
  } else
    echo $translations['Disabled'] . '</strong>. (<a class="nav" '
     . 'href="layers_toggle.php?status=on' . $u_url . '">'
     . translate ( 'Enable Layers' ) . '</a>)<br />';
}

ob_end_flush ();

echo print_trailer ();

?>
