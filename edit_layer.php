<?php
include_once 'includes/init.php';

$updating_public = false;
if ( $is_admin && ! empty ( $public ) && $public_access == "Y" ) {
  $updating_public = true;
  $layer_user = "__public__";
} else {
  $layer_user = $login;
}

load_user_layers ( $layer_user, 1 );

$INC = array('js/edit_layer.php');
print_header($INC);
?>

<h2><?php
if ( $updating_public )
  echo translate($PUBLIC_ACCESS_FULLNAME) . " ";
if ( ! empty ( $layers[$id]['cal_layeruser'] ) )
  etranslate("Edit Layer");
else
  etranslate("Add Layer");
?>&nbsp;<img src="help.gif" alt="<?php etranslate("Help")?>" class="help" onclick="window.open ( 'help_layers.php', 'cal_help', 'dependent,menubar,scrollbars,height=400,width=400,innerHeight=420,outerWidth=420' );" /></h2>

<form action="edit_layer_handler.php" method="post" onsubmit="return valid_form(this);" name="prefform">

<?php if ( $updating_public ) { ?>
	<input type="hidden" name="public" value="1" />
<?php } ?>

<table style="border-width:0px;">
<?php
if ( $single_user == "N" ) {
  $userlist = get_my_users ();
  if ($nonuser_enabled == "Y" ) {
    $nonusers = get_nonuser_cals ();
    $userlist = ($nonuser_at_top == "Y") ? array_merge($nonusers, $userlist) : array_merge($userlist, $nonusers);
  }
  $num_users = 0;
  $size = 0;
  $users = "";
  for ( $i = 0; $i < count ( $userlist ); $i++ ) {
    if ( $userlist[$i]['cal_login'] != $layer_user ) {
      $size++;
      $users .= "<option value=\"" . $userlist[$i]['cal_login'] . "\"";
      if ( ! empty ( $layers[$id]['cal_layeruser'] ) ) {
        if ( $layers[$id]['cal_layeruser'] == $userlist[$i]['cal_login'] )
          $users .= " selected=\"selected\"";
      } 
      $users .= ">" . $userlist[$i]['cal_fullname'] . "</option>\n";
    }
  }
  if ( $size > 50 )
    $size = 15;
  else if ( $size > 5 )
    $size = 5;
  if ( $size >= 1 ) {
	print "<tr><td style=\"vertical-align:top; font-weight:bold;\">\n<label for=\"layeruser\">" .
      translate("Source") . ":</label></td><td>\n";
    print "<select name=\"layeruser\" id=\"layeruser\" size=\"1\">$users\n";
    print "</select>\n";
    print "</td></tr>\n";
  }
}
?>

<tr><td>
	<label for="layercolor"><?php etranslate("Color")?>:</label></td><td>
	<input type="text" name="layercolor" id="layercolor" size="7" maxlength="7" value="<?php echo empty ( $layers[$id]['cal_color'] ) ? "" :  $layers[$id]['cal_color']; ?>" />
	<input type="button" onclick="selectColor('layercolor')" value="<?php etranslate("Select")?>..." />
</td></tr>

<tr><td style="font-weight:bold;">
	<?php etranslate("Duplicates")?>:</td><td>
	<label><input type="checkbox" name="dups" value="Y" <?php if ( ! empty ( $layers[$id]['cal_dups'] ) && $layers[$id]['cal_dups'] == 'Y') echo " checked=\"checked\""; ?> />&nbsp;<?php etranslate("Show layer events that are the same as your own")?></label>
</td></tr>

<tr><td colspan="2">
	<input type="submit" value="<?php etranslate("Save")?>" />
</td></tr>
<?php
// If a layer already exists put a 'Delete Layer' link
if ( ! empty ( $layers[$id]['cal_layeruser'] ) ) { ?>
<tr><td>
	<br /><a href="del_layer.php?id=<?php echo $id; if ( $updating_public ) echo "&amp;public=1"; ?>" onclick="return confirm('<?php etranslate("Are you sure you want to delete this layer?")?>');"><?php etranslate("Delete layer")?></a><br />
</td></tr>
<?php
}  // end 'Delete Layer' link
?>
</table>

<?php if ( ! empty ( $layers[$id]['cal_layeruser'] ) )
	echo "<input type=\"hidden\" name=\"id\" value=\"$id\" />\n";
?>
</form>

<?php print_trailer(); ?>
</body>
</html>
