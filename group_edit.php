<?php
include_once 'includes/init.php';
print_header( '', '', '', true );
?>

<form action="group_edit_handler.php" method="post">
<?php
$newgroup = true;
$groupname = "";
$groupowner = "";
$groupupdated = "";


if ( empty ( $id ) ) {
  $groupname = translate("Unnamed Group");
} else {
  $newgroup = false;
  // get group by id
  $res = dbi_query ( "SELECT cal_owner, cal_name, cal_last_update, cal_owner " .
    "FROM webcal_group WHERE cal_group_id = $id" );
  if ( $res ) {
    if ( $row = dbi_fetch_row ( $res ) ) {
      $groupname = $row[1];
      $groupupdated = $row[2];
      user_load_variables ( $row[3], "temp" );
      $groupowner = $tempfullname;
    }
    dbi_fetch_row ( $res );
  }
}


if ( $newgroup ) {
  $v = array ();
  echo "<h2>" . translate("Add Group") . "</h2>\n";
  echo "<input type=\"hidden\" name=\"add\" value=\"1\" />\n";
} else {
  echo "<h2>" . translate("Edit Group") . "</h2>\n";
  echo "<input type=\"hidden\" name=\"id\" value=\"$id\" />";
}
?>

<table style="border-width:0px;">
<tr><td style="font-weight:bold;">
	<label for="groupname"><?php etranslate("Group name")?>:</label></td><td>
	<input type="text" name="groupname" id="groupname" size="20" value="<?php echo htmlspecialchars ( $groupname );?>" />
</td></tr>
<?php if ( ! $newgroup ) { ?>
	<tr><td style="vertical-align:top; font-weight:bold;">
		<?php etranslate("Updated"); ?>:</td><td>
		<?php echo date_to_str ( $groupupdated ); ?>
	</td></tr>
	<tr><td style="vertical-align:top; font-weight:bold;">
		<?php etranslate("Created by"); ?>:</td><td>
		<?php echo $groupowner; ?>
	</td></tr>
<?php } ?>
<tr><td style="vertical-align:top; font-weight:bold;">
	<label for="users"><?php etranslate("Users"); ?>:</label></td><td>
	<select name="users[]" id="users" size="10" multiple="multiple">
<?php
  // get list of all users
  $users = user_get_users ();
  if ($nonuser_enabled == "Y" ) {
    $nonusers = get_nonuser_cals ();
    $users = ($nonuser_at_top == "Y") ? array_merge($nonusers, $users) : array_merge($users, $nonusers);
  }

  // get list of users for this group
  if ( ! $newgroup ) {
    $sql = "SELECT cal_login FROM webcal_group_user WHERE cal_group_id = $id";
    $res = dbi_query ( $sql );
    if ( $res ) {
      while ( $row = dbi_fetch_row ( $res ) ) {
        $groupuser[$row[0]] = 1;
      }
      dbi_free_result ( $res );
    }
  }
  for ( $i = 0; $i < count ( $users ); $i++ ) {
    $u = $users[$i]['cal_login'];
    echo "<option value=\"$u\" ";
    if ( ! empty ( $groupuser[$u] ) ) {
      echo " selected=\"selected\"";
    }
    echo ">" . $users[$i]['cal_fullname'] . "</option>\n";
  }
?>
	</select>
</td></tr>
<tr><td colspan="2" style="text-align:center;">
	<br /><input type="submit" name="action" value="<?php if ( $newgroup ) etranslate("Add"); else etranslate("Save"); ?>" />
	<?php if ( ! $newgroup ) { ?>
		<input type="submit" name="action" value="<?php etranslate("Delete")?>" onclick="return confirm('<?php etranslate("Are you sure you want to delete this entry?"); ?>')" />
	<?php } ?>
</td></tr>
</table>
</form>

<?php print_trailer ( false, true, true ); ?>

</body>
</html>
