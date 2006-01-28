<?php
include_once 'includes/init.php';
send_no_cache_header ();

$updating_public = false;
if ( $is_admin && ! empty ( $public ) && $public_access == "Y" ) {
  $updating_public = true;
  $layer_user = "__public__";
  $u_url = "&amp;public=1";
} else {
  $layer_user = $login;
  $u_url = "";
}

load_user_layers ( $layer_user, 1 );

$layers_enabled = 0;
$sql = "SELECT cal_value FROM webcal_user_pref " .
  "WHERE cal_setting = 'LAYERS_STATUS' AND cal_login = '$layer_user'";
$res = dbi_query ( $sql );
if ( $res ) {
  $row = dbi_fetch_row ( $res );
  $layers_enabled = ( $row[0] == "Y" ? 1 : 0 );
  dbi_free_result ( $res );
}

print_header();

if ( $allow_view_other != 'Y') {
  echo "allow_view_other = $allow_view_other <br>";
  echo translate("You are not authorized");
} else {
?>

<h2><?php
if ($updating_public)
  echo translate($PUBLIC_ACCESS_FULLNAME) . "&nbsp;";
etranslate("Layers")?>&nbsp;<img src="help.gif" alt="<?php etranslate("Help")?>" class="help" onclick="window.open ( 'help_layers.php', 'cal_help', 'dependent,menubar,scrollbars,height=400,width=400,innerHeight=420,outerWidth=420');" /></h2>

<a title="<?php etranslate("Admin") ?>" class="nav" href="adminhome.php">&laquo;&nbsp;<?php etranslate("Admin") ?></a><br /><br />
<?php
etranslate("Layers are currently");

echo "&nbsp;<strong>";
if ($layers_enabled) {
	etranslate ("Enabled");
	echo "</strong>.&nbsp;(<a title=\"" . 
		translate ("Disable Layers") . "\" class=\"nav\" href=\"layers_toggle.php?status=off$u_url\">" .
		translate ("Disable Layers") . "</a>)\n";
} else {
	etranslate ("Disabled");
	echo "</strong>.&nbsp;(<a title=\"" .
		translate ("Enable Layers") . "\" class=\"nav\" href=\"layers_toggle.php?status=on$u_url\">" . 
		translate ("Enable Layers") . "</a>)\n";
}
echo "<br />";

if ($is_admin && $layers_enabled) {
  if ( empty ($public) && ! empty ( $public_access ) &&
    $public_access == 'Y' ) {
    echo "<blockquote><a href=\"layers.php?public=1\">" .
      translate("Click here") . "&nbsp;" . 
      translate("to modify the layers settings for the") . "&nbsp;" .
      translate($PUBLIC_ACCESS_FULLNAME) . "&nbsp;" .
      translate("calendar") . 
      "</a></blockquote>\n";
  }
}

if ($layers_enabled) { ?>

<a title="<?php etranslate("Add layer"); ?>" href="edit_layer.php<?php if ( $updating_public ) echo "?public=1";?>"><?php etranslate("Add layer"); ?></a><br /><br />

<?php
     $layer_count = 1;
     if ($layers) foreach ($layers as $layer) {
       $layeruser = $layer['cal_layeruser'];
       user_load_variables ( $layeruser, "layer" );
?>
	<span style="font-weight:bold;"><?php etranslate("Layer")?>&nbsp;<?php echo ($layer_count); ?></span>
	(<a title="<?php 
		etranslate("Edit layer"); ?>" href="edit_layer.php?id=<?php echo $layer['cal_layerid'] . $u_url; ?>"><?php 
		etranslate("Edit"); ?></a> / 
	<a title="<?php 
		etranslate("Delete layer")?>" href="del_layer.php?id=<?php echo $layer['cal_layerid'] . $u_url; ?>" onclick="return confirm('<?php etranslate("Are you sure you want to delete this layer?")?>');"><?php 
		etranslate("Delete")?></a>)

<table style="margin-left:20px; border-width:0px;">
	<tr><td style="vertical-align:top; font-weight:bold;">
		<?php etranslate("Source")?>:</td><td>
		<?php echo $layerfullname; ?>
	</td></tr>
	<tr><td style="font-weight:bold;">
		<?php etranslate("Color")?>:</td><td style="background-color:<?php echo $CELLBG;?>; color:<?php echo ( $layer['cal_color'] ); ?>;">
		<?php echo ( $layer['cal_color'] ); ?>
	</td></tr>
	<tr><td style="font-weight:bold;">
		<?php etranslate("Duplicates")?>:</td><td>
		<?php
			if( $layer['cal_dups'] == 'N')
				etranslate("No");
			else
				etranslate("Yes");
		?>
	</td></tr>
</table>
<?php
     $layer_count++;
   }
 }
}
?>

<?php print_trailer(); ?>
</body>
</html>
