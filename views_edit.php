<?php
include_once 'includes/init.php';

if ( ! $is_admin )
  $user = $login;

$INC = array('js/'.$SCRIPT);
print_header($INC);
?>

<FORM ACTION="views_edit_handler.php" METHOD="POST" NAME="editviewform">

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
      $viewtype = $views[$i]["cal_view_type"];
    }
  }
}


if ( $newview ) {
  $v = array ();
  echo "<H2><FONT COLOR=\"$H2COLOR\">" . translate("Add View") . "</FONT></H2>\n";
  echo "<INPUT TYPE=\"hidden\" NAME=\"add\" VALUE=\"1\">\n";
} else {
  echo "<H2><FONT COLOR=\"$H2COLOR\">" . translate("Edit View") . "</FONT></H2>\n";
  echo "<INPUT NAME=\"id\" TYPE=\"hidden\" VALUE=\"$id\">";
}
?>

<TABLE BORDER="0">
<TR><TD><B><?php etranslate("View Name")?>:</B></TD>
  <TD><INPUT NAME="viewname" SIZE=20 VALUE="<?php echo htmlspecialchars ( $viewname );?>"></TD></TR>
<TR><TD><B><?php etranslate("View Type")?>:</B></TD>
  <TD><SELECT NAME="viewtype">
      <OPTION VALUE="W" <?php if ( $viewtype == "W" ) echo "SELECTED";?> >
        <?php etranslate("Week (Users horizontal)"); ?>
      <OPTION VALUE="V" <?php if ( $viewtype == "V" ) echo "SELECTED";?> >
        <?php etranslate("Week (Users vertical)"); ?>
      <OPTION VALUE="T" <?php if ( $viewtype == "T" ) echo "SELECTED";?> >
        <?php etranslate("Week (Timebar)"); ?>
      <OPTION VALUE="M" <?php if ( $viewtype == "M" ) echo "SELECTED";?> >
        <?php etranslate("Month"); ?>
      </SELECT>
      </TD></TR>
<TR><TD VALIGN="top">
<B><?php etranslate("Users"); ?>:</B></TD>
<TD>
<SELECT NAME="users[]" SIZE="10" MULTIPLE>
<?php
  // get list of all users
  $users = get_my_users ();
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
    echo "<OPTION VALUE=\"$u\" ";
    if ( ! empty ( $viewuser[$u] ) ) {
      echo "SELECTED";
    }
    echo "> " . $users[$i]['cal_fullname'];
  }
?>
</SELECT>
<?php if ( $groups_enabled == "Y" ) { ?>
  <INPUT TYPE="button" ONCLICK="selectUsers()" VALUE="<?php etranslate("Select");?>...">
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

<?php include_once "includes/trailer.php"; ?>
</BODY>
</HTML>