<?php
include_once 'includes/init.php';
$INC = array('js/edit_remotes.php/false', 'js/visible.php/true');
print_header( $INC, '', '', true );

if ( ! $NONUSER_PREFIX ) {
  echo "<h2>" . translate("Error") . "</h2>\n" . 
    translate("NONUSER_PREFIX not set") . ".\n";
  echo "</body>\n</html>";
  exit;
}
$add = getValue ( "add" );
$nid = getValue ( "nid" );

// Adding/Editing remote calendar
if (( ($add == '1') || (! empty ($nid)) ) && empty ($error)) {
  $userlist = get_nonuser_cals ( $login, true);
  $button = translate("Add");
  $nid = clean_html($nid);
?>

<form action="edit_remotes_handler.php" method="post"  name="prefform" onsubmit="return valid_form(this);">
  <?php
  if ( ! empty ( $nid ) ) {
    nonuser_load_variables ( $nid, 'remotestemp_' );
    $id_display = "$nid <input type=\"hidden\" name=\"nid\" value=\"$nid\" />";
    $button = translate("Save");
    $remotestemp_login = substr($remotestemp_login, strlen($NONUSER_PREFIX));
  } else {
    $id_display = "<input type=\"text\" name=\"nid\" id=\"calid\" size=\"20\" maxlength=\"20\" /> " . translate ("word characters only");
  }
  ?>
<h2><?php
  if ( ! empty ( $nid ) ) {
 nonuser_load_variables ( $nid, 'remotestemp_' );
 echo translate("Edit Remote Calendar");
  } else {
 echo translate("Add Remote Calendar");
  }
?></h2>
<table>
 <tr><td>
  <label for="calid"><?php etranslate("Calendar ID")?>:</label></td><td>
  <?php echo $id_display ?>
 </td></tr>
 <tr><td>
  <label for="nfirstname"><?php etranslate("First Name")?>:</label></td><td>
  <input type="text" name="nfirstname" id="nfirstname" size="20" maxlength="25" value="<?php echo empty ( $remotestemp_firstname ) ? '' : htmlspecialchars ( $remotestemp_firstname ); ?>" />
 </td></tr>
 <tr><td>
  <label for="nlastname"><?php etranslate("Last Name")?>:</label></td><td>
  <input type="text" name="nlastname" id="nlastname" size="20" maxlength="25" value="<?php echo empty ( $remotestemp_lastname ) ? '' : htmlspecialchars ( $remotestemp_lastname ); ?>" />
 </td></tr>
 <tr><td>
  <label for="url"><?php etranslate("URL")?>:</label></td><td>
  <input type="text" name="nurl" id="nurl" size="59" maxlength="75" value="<?php echo empty ( $remotestemp_url ) ? '' : htmlspecialchars ( $remotestemp_url ); ?>" />
 </td></tr>
 <?php if ( empty ( $nid ) ) { ?>
 <tr><td>
  <label for="url"><?php etranslate("Create Layer")?>:</label></td><td>
  <input type="checkbox" name="nlayer"  value="Y"  onchange="toggle_layercolor();"/>(<?php 
    etranslate("Required to View Remote Calendar")?>)  
 </td></tr>
<tr id="nlayercolor" style="visibility:hidden" ><td>
 <label for="layercolor"><?php etranslate("Color")?>:</label></td><td>
 <input type="text" name="layercolor" id="layercolor" size="7" maxlength="7" value="" />
 <input type="button" onclick="selectColor('layercolor')" value="<?php etranslate("Select")?>..." />
</td></tr> 
 <?php } ?>
</table>
  <br />
  <input type="hidden" name="nadmin" id="nadmin" value="<?php echo $login ?>" />
  <input type="submit" name="action" value="<?php echo $button;?>" />
  <?php if ( ! empty ( $nid ) ) {  ?>
    <input type="submit" name="delete" value="<?php etranslate("Delete");?>" onclick="return confirm('<?php etranslate("Are you sure you want to delete this entry?", true); ?>')" />
    <input type="submit" name="reload" value="<?php etranslate("Reload");?>" />
  <?php } ?>
  </form>
<?php } ?>
<?php print_trailer ( false, true, true ); ?>
</body>
</html>
