<?php
include_once 'includes/init.php';
print_header();

if ( ! $is_admin ) {
  echo "<h2>" . translate("Error") . "</h2>\n" . 
  		translate("You are not authorized") . ".\n";
  print_trailer ();
  echo "</body>\n</html>";
  exit;
}
if ( ! $NONUSER_PREFIX ) {
  echo "<h2>" . translate("Error") . "</h2>\n" . 
  		translate("NONUSER_PREFIX not set") . ".\n";
  print_trailer ();
  echo "</body>\n</html>";
  exit;
}
$add = getValue ( "add" );
?>

<h2><?php etranslate("NonUser")?></h2>
<a title="<?php etranslate("Admin") ?>" class="nav" href="adminhome.php">&laquo;&nbsp;<?php etranslate("Admin") ?></a><br /><br />

<?php
// Adding/Editing nonuser calendar
if ( ( ( $add == '1' ) || ( ! empty ( $nid ) ) ) && empty ( $error ) ) {
  $userlist = get_my_users ();
  $button = translate("Add");
  $nid = clean_html($nid);
  ?>
  <form action="nonusers_handler.php" method="post">
  <?php
  if ( ! empty ( $nid ) ) {
    nonuser_load_variables ( $nid, 'nonusertemp_' );
    $id_display = "$nid <input name=\"nid\" type=\"hidden\" value=\"$nid\" />";
    $button = translate("Save");
    $nonusertemp_login = substr($nonusertemp_login, strlen($NONUSER_PREFIX));
  } else {
    $id_display = "<input name=\"nid\" size=\"20\" maxlength=\"20\" /> " . translate ("word characters only");
  }
  ?>
<table>
	<tr><td>
		<?php etranslate("Calendar ID")?>:</td><td>
		<?php echo $id_display ?>
	</td></tr>
	<tr><td>
		<?php etranslate("First Name")?>:</td><td>
		<input type="text" name="nfirstname" size="20" maxlength="25" value="<?php echo htmlspecialchars ( $nonusertemp_firstname ); ?>" />
	</td></tr>
	<tr><td>
		<?php etranslate("Last Name")?>:</td><td>
		<input type="text" name="nlastname" size="20" maxlength="25" value="<?php echo htmlspecialchars ( $nonusertemp_lastname ); ?>" />
	</td></tr>
	<tr><td>
		<?php etranslate("Admin")?>:</td><td>
		<select name="nadmin">
  <?php
  for ( $i = 0; $i < count ( $userlist ); $i++ ) {
    echo "<option value=\"".$userlist[$i]['cal_login']."\"";
    if ($nonusertemp_admin == $userlist[$i]['cal_login'] ) echo " selected=\"selected\"";
    echo ">".$userlist[$i]['cal_fullname']."</option>\n";
  }
  ?>
		</select>
	</td></tr>
</table>

  <br /><br />
  <input type="submit" name="action" value="<?php echo $button;?>" />
  <?php if ( ! empty ( $nid ) ) {  ?>
    <input type="submit" name="action" value="<?php etranslate("Delete");?>" onclick="return confirm('<?php etranslate("Are you sure you want to delete this entry?"); ?>')" />
  <?php }  ?>
  </form>
  <?php
} else if ( empty ( $error ) ) {
  // Displaying NonUser Calendars
  $userlist = get_nonuser_cals ();
  if ( ! empty ( $userlist ) ) {
    echo "<ul>";
    for ( $i = 0; $i < count ( $userlist ); $i++ ) {
      echo "<li><a title=\"" . 
      	$userlist[$i]['cal_fullname'] . "\" href=\"nonusers.php?nid=" . $userlist[$i]["cal_login"] . "\">" . 
	$userlist[$i]['cal_fullname'] . "</a></li>\n";
    }
    echo "</ul>";
  }
  echo "<p><a title=\"" . 
  	translate("Add New NonUser Calendar") . "\" href=\"nonusers.php?add=1\">" . 
	translate("Add New NonUser Calendar") . "</a></p><br />\n";
}
?>

<?php print_trailer(); ?>
</body>
</html>
