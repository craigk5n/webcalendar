<?php
include_once 'includes/init.php';

if ( ! $is_admin )
  $user = $login;

$INC = array('js/views_edit.php');
print_header($INC);
?>

<form action="views_edit_handler.php" method="post" name="editviewform">

<?php

$newview = true;
$viewname = "";
$viewtype = "";


if ( empty ( $id ) ) {
  $viewname = translate("Unnamed View");
} else {
  // search for view by id
  for ( $i = 0; $i < count ( $views ); $i++ ) {
    if ( $views[$i]['cal_view_id'] == $id ) {
      $newview = false;
      $viewname = $views[$i]["cal_name"];
      if ( empty ( $viewname ) )
        $viewname = translate("Unnamed View");
      $viewtype = $views[$i]["cal_view_type"];
    }
  }
}


if ( $newview ) {
  $v = array ();
  echo "<h2 style=\"color:$H2COLOR;\">" . translate("Add View") . "</h2>\n";
  echo "<input type=\"hidden\" name=\"add\" value=\"1\" />\n";
} else {
  echo "<h2 style=\"color:$H2COLOR;\">" . translate("Edit View") . "</H2>\n";
  echo "<input name=\"id\" type=\"hidden\" value=\"$id\" />";
}
?>

<table border="0">
<tr><td><b><?php etranslate("View Name")?>:</b></td>
  <td><input name="viewname" size="20" value="<?php echo htmlspecialchars ( $viewname );?>"></td></tr>
<tr><td><b><?php etranslate("View Type")?>:</b></td>
  <TD><SELECT NAME="viewtype">
      <OPTION VALUE="D" <?php if ( $viewtype == "D" ) echo " selected=\"selected\"";?>><?php etranslate("Day"); ?></option>
      <OPTION VALUE="W" <?php if ( $viewtype == "W" ) echo " selected=\"selected\"";?>><?php etranslate("Week (Users horizontal)"); ?></option>
      <OPTION VALUE="V" <?php if ( $viewtype == "V" ) echo " selected=\"selected\"";?>><?php etranslate("Week (Users vertical)"); ?></option>
      <OPTION VALUE="T" <?php if ( $viewtype == "T" ) echo " selected=\"selected\"";?>><?php etranslate("Week (Timebar)"); ?></option>
      <OPTION VALUE="M" <?php if ( $viewtype == "M" ) echo " selected=\"selected\"";?>><?php etranslate("Month (side by side)"); ?></option>
      <OPTION VALUE="L" <?php if ( $viewtype == "L" ) echo " selected=\"selected\"";?>><?php etranslate("Month (on same calendar)"); ?></option>
      </SELECT>
      </TD></TR>
<TR><TD VALIGN="top">
<B><?php etranslate("Users"); ?>:</B></TD>
<TD>
<SELECT NAME="users[]" SIZE="10" multiple="multiple">
<?php
  // get list of all users
  $users = get_my_users ();
  if ($nonuser_enabled == "Y" ) {
    $nonusers = get_nonuser_cals ();
    $users = ($nonuser_at_top == "Y") ? array_merge($nonusers, $users) : array_merge($users, $nonusers);
  }
  // get list of users for this view
  if ( ! $newview ) {
    $sql = "SELECT cal_login FROM webcal_view_user WHERE cal_view_id = $id";
    $res = dbi_query ( $sql );
    if ( $res ) {
      while ( $row = dbi_fetch_row ( $res ) ) {
        $viewuser[$row[0]] = 1;
      }
      dbi_free_result ( $res );
    }
  }
  for ( $i = 0; $i < count ( $users ); $i++ ) {
    $u = $users[$i]['cal_login'];
    echo "<OPTION VALUE=\"$u\"";
    if ( ! empty ( $viewuser[$u] ) ) {
      echo " selected=\"selected\"";
    }
    echo "> " . $users[$i]['cal_fullname'];
  }
?>
</SELECT>
<?php if ( $groups_enabled == "Y" ) { ?>
  <INPUT TYPE="button" ONCLICK="selectUsers()" VALUE="<?php etranslate("Select");?>..." />
<?php } ?>
</TD></TR>
<TR><TD COLSPAN="2">
<BR><BR>
<CENTER>
<INPUT TYPE="submit" NAME="action" VALUE="<?php if ( $newview ) etranslate("Add"); else etranslate("Save"); ?>" >
<?php if ( ! $newview ) { ?>
<INPUT TYPE="submit" NAME="action" VALUE="<?php etranslate("Delete")?>" ONCLICK="return confirm('<?php etranslate("Are you sure you want to delete this entry?"); ?>')">
<?php } ?>
</CENTER>
</TD></TR>
</TABLE>

</FORM>

<?php print_trailer(); ?>
</BODY>
</HTML>
