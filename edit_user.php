<?php
include_once 'includes/init.php';

if ( ! $is_admin )
  $user = $login;

// don't allow them to create new users if it's not allowed
if ( empty ( $user ) ) {
  // asking to create a new user
  if ( ! $is_admin ) {
    // must be admin...
    do_redirect ( empty ( $STARTVIEW ) ? "month.php" : "$STARTVIEW.php" );
    exit;
  }
  if ( ! $admin_can_add_user ) {
    // if adding users is not allowed...
    do_redirect ( empty ( $STARTVIEW ) ? "month.php" : "$STARTVIEW.php" );
    exit;
  }
}

print_header();
?>

<TABLE BORDER=0>
<TR><TD VALIGN="top" WIDTH=50%>

<FORM ACTION="edit_user_handler.php" METHOD="POST">
<INPUT TYPE="hidden" NAME="formtype" VALUE="edituser">

<?php

if ( ! empty ( $user ) ) {
  user_load_variables ( $user, "u" );
  echo "<H2><FONT COLOR=\"$H2COLOR\">" . translate("Edit User") . "</FONT></H2>\n";
} else {
  echo "<INPUT TYPE=\"hidden\" NAME=\"add\" VALUE=\"1\">\n";
  echo "<H2><FONT COLOR=\"$H2COLOR\">" . translate("Add User") . "</FONT></H2>\n";
}
?>

<TABLE BORDER=0>
<TR><TD><B><?php etranslate("Username")?>:</B></TD>
  <TD><?php
    if ( ! empty ( $user ) ) {
      if ( $is_admin )
        echo $user . "<INPUT NAME=\"user\" TYPE=\"hidden\" VALUE=\"" .
          htmlspecialchars ( $user ) . "\">";
      else
        echo $user;
    } else {
      echo "<INPUT NAME=\"user\" SIZE=20 MAXLENTH=20>";
    }
?></TD></TR>
<TR><TD><B><?php etranslate("First Name")?>:</B></TD>
  <TD><INPUT NAME="ufirstname" SIZE=20 VALUE="<?php echo htmlspecialchars ( $ufirstname );?>"></TD></TR>
<TR><TD><B><?php etranslate("Last Name")?>:</B></TD>
  <TD><INPUT NAME="ulastname" SIZE=20 VALUE="<?php echo htmlspecialchars ( $ulastname );?>"></TD></TR>
<TR><TD><B><?php etranslate("E-mail address")?>:</B></TD>
  <TD><INPUT NAME="uemail" SIZE=20 VALUE="<?php echo htmlspecialchars ( $uemail );?>"></TD></TR>
<?php if ( empty ( $user ) ) { ?>
<TR><TD><B><?php etranslate("Password")?>:</B></TD>
  <TD><INPUT NAME="upassword1" SIZE=15 VALUE="" TYPE="password"></TD></TR>
<TR><TD><B><?php etranslate("Password")?> (<?php etranslate("again")?>):</B></TD>
  <TD><INPUT NAME="upassword2" SIZE=15 VALUE="" TYPE="password"></TD></TR>
<?php }
  if ( $is_admin ) { ?>
<TR><TD><B><?php etranslate("Admin")?>:</B></TD>
  <TD><INPUT TYPE="radio" NAME="uis_admin" VALUE="N" <?php if ( $uis_admin != "Y" ) echo "CHECKED";?>><?php etranslate("No")?> <INPUT TYPE="radio" NAME="uis_admin" VALUE="Y" <?php if ( $uis_admin == "Y" ) echo "CHECKED";?>><?php etranslate("Yes")?> </TD></TR>
<?php } ?>
<TR><TD COLSPAN=2>
<?php if ( $demo_mode == "Y" ) { ?>
  <INPUT TYPE="button" VALUE="<?php etranslate("Save")?>" ONCLICK="alert('<?php etranslate("Disabled for demo")?>')">
  <?php if ( $is_admin && ! empty ( $user ) ) { ?>
    <INPUT TYPE="submit" NAME="action" VALUE="<?php etranslate("Delete")?>" ONCLICK="alert('<?php etranslate("Disabled for demo")?>')">
   <?php }?>
<?php } else { ?>
  <INPUT TYPE="submit" VALUE="<?php etranslate("Save")?>">
  <?php if ( $is_admin && ! empty ( $user ) ) {
          if ( $admin_can_delete_user )
    ?>
    <INPUT TYPE="submit" NAME="action" VALUE="<?php etranslate("Delete")?>" ONCLICK="return confirm('<?php etranslate("Are you sure you want to delete this entry?"); ?>')">
  <?php } ?>
<?php } ?>
</TD></TR>
</TABLE>

</FORM>

<?php if ( ! empty ( $user ) && ! $use_http_auth ) { ?>

</TD>
<TD>&nbsp;&nbsp;</TD>
<TD VALIGN="top">

<H2><FONT COLOR="<?php echo $H2COLOR;?>"><?php etranslate("Change Password")?></FONT></H2>
<FORM ACTION="edit_user_handler.php" METHOD="POST">
<INPUT TYPE="hidden" NAME="formtype" VALUE="setpassword">
<?php if ( $is_admin ) { ?>
<INPUT TYPE="hidden" NAME="user" VALUE="<?php echo $user;?>">
<?php } ?>
<TABLE BORDER=0>
<TR><TD><B><?php etranslate("New Password")?>:</B></TD>
  <TD><INPUT NAME="upassword1" TYPE="password" SIZE=15></TD></TR>
<TR><TD><B><?php etranslate("New Password")?> (<?php etranslate("again")?>):</B></TD>
  <TD><INPUT NAME="upassword2" TYPE="password" SIZE=15></TD></TR>
<TR><TD COLSPAN=2>
  <?php if ( $demo_mode == "Y" ) { ?>
    <INPUT TYPE="button" VALUE="<?php etranslate("Set Password")?>" ONCLICK="alert('<?php etranslate("Disabled for demo")?>')">
  <?php } else { ?>
    <INPUT TYPE="submit" VALUE="<?php etranslate("Set Password")?>">
  <?php } ?>
</TD></TR>
</TABLE>
</FORM>

<?php } ?>
</TD></TR></TABLE>

<?php print_trailer(); ?>
</BODY>
</HTML>
