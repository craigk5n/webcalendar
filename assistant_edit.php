<?php
include_once 'includes/init.php';

if ($user != $login)
  $user = (($is_admin || $is_nonuser_admin) && $user) ? $user : $login;

if ( $groups_enabled == "Y" ) {
  $INC = array('js/assistant_edit.php');
}
print_header($INC);
?>

<FORM ACTION="assistant_edit_handler.php" METHOD="POST" NAME="editentryform">
<?php
  if ($user) echo "<input type=\"hidden\" name=\"user\" value=\"$user\" />";
  if ( $is_nonuser_admin ) {
    nonuser_load_variables ( $user, "nonuser" );
    echo "<h2><font color=\"$H2COLOR\">" . $nonuserfullname . " " . translate("Assistants")
        ."<br />\n<b>-- " . translate("Admin mode") . " --</b></font></h2>\n";
  } else {
    echo "<h2><font color=\"$H2COLOR\">" . translate("Your assistants") . "</font></h2>\n";
  }
?>

<table border="0">
<tr><td valign="top">
<b><?php etranslate("Assistants"); ?>:</b></td>
<td>
<select name="users[]" size="10" MULTIPLE="MULTIPLE">
<?php
  // get list of all users
  $users = get_my_users ();
  // get list of users for this view
  $sql = "SELECT cal_boss, cal_assistant FROM webcal_asst WHERE cal_boss = '$user'";
  $res = dbi_query ( $sql );
  if ( $res ) {
    while ( $row = dbi_fetch_row ( $res ) ) {
      $assistantuser[$row[1]] = 1;
    }
    dbi_free_result ( $res );
  }
  for ( $i = 0; $i < count ( $users ); $i++ ) {
    $u = $users[$i]['cal_login'];
    if ($u == $login ) continue;
    if ($u == '__public__' ) continue;
    echo "<option value=\"$u\" ";
    if ( ! empty ( $assistantuser[$u] ) ) {
      echo "SELECTED=\"SELECTED\"";
    }
    echo "> " . $users[$i]['cal_fullname'];
  }
?>
</select>
<?php
if ( $groups_enabled == "Y" ) {
  echo "<input type=\"button\" onclick=\"selectUsers()\" value=\"" .
    translate("Select") . "...\">";
}
echo "</td></tr>\n";
?>
</td></tr>
<tr><td colspan="2">
<br /><br />
<center>
<input type="submit" name="action" value="<?php etranslate("Save"); ?>" />

</center>
</td></tr>
</table>

</form>

<?php print_trailer(); ?>
</body>
</html>
