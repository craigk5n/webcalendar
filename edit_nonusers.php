<?php
include_once 'includes/init.php';
print_header( '', '', '', true );

if ( ! $is_admin ) {
  echo "<h2>" . translate("Error") . "</h2>\n" . 
  		translate("You are not authorized") . ".\n";
  echo "</body>\n</html>";
  exit;
}
if ( ! $NONUSER_PREFIX ) {
  echo "<h2>" . translate("Error") . "</h2>\n" . 
  		translate("NONUSER_PREFIX not set") . ".\n";
  echo "</body>\n</html>";
  exit;
}
$add = getValue ( "add" );
$nid = getValue ( "nid" );

// Adding/Editing nonuser calendar
if (( ($add == '1') || (! empty ($nid)) ) && empty ($error)) {
  $userlist = get_my_users ();
  $button = translate("Add");
  $nid = clean_html($nid);
?>

<form action="edit_nonusers_handler.php" method="post">
  <?php
  if ( ! empty ( $nid ) ) {
    nonuser_load_variables ( $nid, 'nonusertemp_' );
    $id_display = "$nid <input type=\"hidden\" name=\"nid\" value=\"$nid\" />";
    $button = translate("Save");
    $nonusertemp_login = substr($nonusertemp_login, strlen($NONUSER_PREFIX));
  } else {
    $id_display = "<input type=\"text\" name=\"nid\" id=\"calid\" size=\"20\" maxlength=\"20\" /> " . translate ("word characters only");
  }
  ?>
<h2><?php
  if ( ! empty ( $nid ) ) {
	nonuser_load_variables ( $nid, 'nonusertemp_' );
	echo translate("Edit User");
  } else {
	echo translate("Add User");
  }
?></h2>
<table>
	<tr><td>
		<label for="calid"><?php etranslate("Calendar ID")?>:</label></td><td>
		<?php echo $id_display ?>
	</td></tr>
	<tr><td>
		<label for="nfirstname"><?php etranslate("First Name")?>:</label></td><td>
		<input type="text" name="nfirstname" id="nfirstname" size="20" maxlength="25" value="<?php echo empty ( $nonusertemp_firstname ) ? '' : htmlspecialchars ( $nonusertemp_firstname ); ?>" />
	</td></tr>
	<tr><td>
		<label for="nlastname"><?php etranslate("Last Name")?>:</label></td><td>
		<input type="text" name="nlastname" id="nlastname" size="20" maxlength="25" value="<?php echo empty ( $nonusertemp_lastname ) ? '' : htmlspecialchars ( $nonusertemp_lastname ); ?>" />
	</td></tr>
	<tr><td>
		<label for="nadmin"><?php etranslate("Admin")?>:</label></td><td>
		<select name="nadmin" id="nadmin">
<?php
  for ( $i = 0; $i < count ( $userlist ); $i++ ) {
	echo "<option value=\"".$userlist[$i]['cal_login']."\"";
	if (! empty ( $nonusertemp_admin ) &&
            $nonusertemp_admin == $userlist[$i]['cal_login'] ) 
		echo " selected=\"selected\"";
	echo ">".$userlist[$i]['cal_fullname']."</option>\n";
  }
?>
		</select>
	</td></tr>
</table>
  <br />
  <input type="submit" name="action" value="<?php echo $button;?>" />
  <?php if ( ! empty ( $nid ) ) {  ?>
    <input type="submit" name="action" value="<?php etranslate("Delete");?>" onclick="return confirm('<?php etranslate("Are you sure you want to delete this entry?"); ?>')" />
  <?php }  ?>
  </form>
<?php } ?>
<?php print_trailer ( false, true, true ); ?>
</body>
</html>
