<?php php_track_vars?>
<?php
include "includes/config.inc";
include "includes/php-dbi.inc";
include "includes/functions.inc";
include "includes/user.inc";

include "includes/validate.inc";
include "includes/connect.inc";

load_user_preferences ();
load_user_layers ();

include "includes/translate.inc";

?>
<HTML>
<HEAD>
<TITLE><?php etranslate("Title")?></TITLE>
<?php include "includes/styles.inc"; ?>
</HEAD>
<BODY BGCOLOR="<?php echo $BGCOLOR;?>">

<TABLE BORDER=0>
<TR><TD VALIGN="top" WIDTH=50%>

<FORM ACTION="edit_user_handler.php" METHOD="POST">

<?php

if ( ! $is_admin )
  $user = $login;

if ( strlen ( $user ) ) {
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
    if ( strlen ( $user ) ) {
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
<?php if ( strlen ( $user ) == 0 ) { ?>
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
<?php if ( $demo_mode ) { ?>
  <INPUT TYPE="button" VALUE="<?php etranslate("Save")?>" ONCLICK="alert('<?php etranslate("Disabled for demo")?>')">
  <?php if ( $is_admin && strlen ( $user ) && $admin_can_delete_user ) { ?>
    <INPUT TYPE="submit" NAME="action" VALUE="<?php etranslate("Delete")?>" ONCLICK="alert('<?php etranslate("Disabled for demo")?>')">
   <?php } ?>
<?php } else { ?>
  <INPUT TYPE="submit" VALUE="<?php etranslate("Save")?>">
  <?php if ( $is_admin && strlen ( $user ) ) { ?>
    <INPUT TYPE="submit" NAME="action" VALUE="<?php etranslate("Delete")?>">
  <?php } ?>
<?php } ?>
</TD></TR>
</TABLE>

</FORM>

<?php if ( strlen ( $user ) ) { ?>

</TD>
<TD>&nbsp;&nbsp;</TD>
<TD VALIGN="top">

<H2><FONT COLOR="<?php echo $H2COLOR;?>"><?php etranslate("Change Password")?></FONT></H2>
<FORM ACTION="edit_user_handler.php" METHOD="POST">
<?php if ( $is_admin ) { ?>
<INPUT TYPE="hidden" NAME="user" VALUE="<?php echo $user;?>">
<?php } ?>
<TABLE BORDER=0>
<TR><TD><B><?php etranslate("New Password")?>:</B></TD>
  <TD><INPUT NAME="upassword1" TYPE="password" SIZE=15></TD></TR>
<TR><TD><B><?php etranslate("New Password")?> (<?php etranslate("again")?>):</B></TD>
  <TD><INPUT NAME="upassword2" TYPE="password" SIZE=15></TD></TR>
<TR><TD COLSPAN=2>
  <?php if ( $demo_mode ) { ?>
    <INPUT TYPE="button" VALUE="<?php etranslate("Set Password")?>" ONCLICK="alert('<?php etranslate("Disabled for demo")?>')">
  <?php } else { ?>
    <INPUT TYPE="submit" VALUE="<?php etranslate("Set Password")?>">
  <?php } ?>
</TD></TR>
</TABLE>
</FORM>

<?php } ?>
</TD></TR></TABLE>

<?php include "includes/trailer.inc"; ?>
</BODY>
</HTML>
