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
  if ($user) echo "<input type=\"hidden\" name=\"user\" value=\"$user\">";
  if ( $is_nonuser_admin ) {
    nonuser_load_variables ( $user, "nonuser" );
    echo "<H2><FONT COLOR=\"$H2COLOR\">" . $nonuserfullname . " " . translate("Assistants")
        ."<BR>\n<B>-- " . translate("Admin mode") . " --</B></FONT></H2>\n";
  } else {
    echo "<H2><FONT COLOR=\"$H2COLOR\">" . translate("Yours assistants") . "</FONT></H2>\n";
  }
?>

<TABLE BORDER="0">
<TR><TD VALIGN="top">
<B><?php etranslate("Assistants"); ?>:</B></TD>
<TD>
<SELECT NAME="users[]" SIZE="10" MULTIPLE>
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
    echo "<OPTION VALUE=\"$u\" ";
    if ( ! empty ( $assistantuser[$u] ) ) {
      echo "SELECTED";
    }
    echo "> " . $users[$i]['cal_fullname'];
  }
?>
</SELECT>
<?php
if ( $groups_enabled == "Y" ) {
  echo "<INPUT TYPE=\"button\" ONCLICK=\"selectUsers()\" VALUE=\"" .
    translate("Select") . "...\">";
}
echo "</TD></TR>\n";
?>
</TD></TR>
<TR><TD COLSPAN="2">
<BR><BR>
<CENTER>
<INPUT TYPE="submit" NAME="action" VALUE="<?php etranslate("Save"); ?>" >

</CENTER>
</TD></TR>
</TABLE>

</FORM>

<?php print_trailer(); ?>
</BODY>
</HTML>
