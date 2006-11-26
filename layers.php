<?php
/* $Id$ */
include_once 'includes/init.php';
send_no_cache_header ();

$updating_public = false;
if ( $is_admin && ! empty ( $public ) && $PUBLIC_ACCESS == 'Y' ) {
  $updating_public = true;
  $layer_user = '__public__';
  $u_url = '&amp;public=1';
} else {
  $layer_user = $login;
  $u_url = '';
}

load_user_layers ( $layer_user, 1 );

$layers_enabled = 0;
$sql = 'SELECT cal_value FROM webcal_user_pref ' .
  "WHERE cal_setting = 'LAYERS_STATUS' AND cal_login = ?";
$res = dbi_execute ( $sql , array ( $layer_user ) );
if ( $res ) {
  $row = dbi_fetch_row ( $res );
  $layers_enabled = ( $row[0] == 'Y' ? 1 : 0 );
  dbi_free_result ( $res );
}

print_header();

if ( $ALLOW_VIEW_OTHER != 'Y') {
  echo print_not_auth ();
} else {
?>

<h2><?php
if ($updating_public)
  echo translate($PUBLIC_ACCESS_FULLNAME) . '&nbsp;';
etranslate( 'Layers' )?>&nbsp;<img src="images/help.gif" alt="<?php etranslate( 'Help' )?>" class="help" onclick="window.open ( 'help_layers.php', 'cal_help', 'dependent,menubar,scrollbars,height=400,width=400,innerHeight=420,outerWidth=420');" /></h2>

<?php
echo display_admin_link();

etranslate( 'Layers are currently' );

echo '&nbsp;<strong>';
if ($layers_enabled) {
 etranslate ( 'Enabled' );
 echo '</strong>.&nbsp;(<a title="' . translate ( 'Disable Layers' ) . 
  '" class="nav" href="layers_toggle.php?status=off' . $u_url . '">' .
  translate ( 'Disable Layers' ) . "</a>)\n";
} else {
 etranslate ( 'Disabled' );
 echo '</strong>.&nbsp;(<a title="' .
  translate ( 'Enable Layers' ) . '\ class="nav" href="layers_toggle.php?status=on' . 
  $u_url .'">' . translate ( 'Enable Layers' ) . "</a>)\n";
}
echo '<br />';

if ($is_admin && $layers_enabled) {
  if ( empty ($public) && ! empty ( $PUBLIC_ACCESS ) &&
    $PUBLIC_ACCESS == 'Y' ) {
    echo '<blockquote><a href="layers.php?public=1">' .
      translate( 'Click here' ) . '&nbsp;' . 
      translate( 'to modify the layers settings for the' ) . '&nbsp;' .
      translate( $PUBLIC_ACCESS_FULLNAME ) . '&nbsp;' .
      translate( 'calendar' ) . 
      "</a></blockquote>\n";
  }
}

if ($layers_enabled) { ?>

<a title="<?php etranslate( 'Add layer' ); ?>" href="edit_layer.php<?php if ( $updating_public ) echo '?public=1';?>"><?php etranslate( 'Add layer' ); ?></a><br /><br />

<?php
     $layer_count = 1;
     if ($layers) foreach ($layers as $layer) {
       $layeruser = $layer['cal_layeruser'];
       user_load_variables ( $layeruser, 'layer' );
?>
 <span class="bold"><?php echo ucfirst ( translate ( 'layer' ) )?>&nbsp;<?php echo ($layer_count); ?></span>
 (<a title="<?php 
  etranslate( 'Edit layer' ); ?>" href="edit_layer.php?id=<?php echo $layer['cal_layerid'] . $u_url; ?>"><?php 
  etranslate( 'Edit' ); ?></a> / 
 <a title="<?php 
  etranslate( 'Delete layer' )?>" href="del_layer.php?id=<?php echo $layer['cal_layerid'] . $u_url; ?>" onclick="return confirm('<?php 
 echo str_replace ( 'XXX', $translations['layer'], $translations['Are you sure you want to delete this XXX?'] ) ?>');"><?php 
  etranslate( 'Delete' )?></a>)

<table style="margin-left:20px; border-width:0px;">
 <tr><td class="aligntop bold">
  <?php etranslate( 'Source' )?>:</td><td>
  <?php echo $layerfullname; ?>
 </td></tr>
 <tr><td class="bold">
  <?php etranslate( 'Color' )?>:</td><td style="background-color:<?php 
  echo $CELLBG;?>; color:<?php echo ( $layer['cal_color'] ); ?>;">
  <?php echo ( $layer['cal_color'] ); ?>
 </td></tr>
 <tr><td class="bold">
  <?php etranslate( 'Duplicates' )?>:</td><td>
  <?php
   if( $layer['cal_dups'] == 'N')
    etranslate ( 'No');
   else
    etranslate ( 'Yes');
  ?>
 </td></tr>
</table>
<?php
     $layer_count++;
   }
 }
}

echo print_trailer(); ?>

