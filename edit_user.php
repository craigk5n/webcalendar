<?php
include_once 'includes/init.php';

if ( ! $is_admin )
  $user = $login;

// cannot edit public user.
if ( $user == '__public__' )
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

<table border="0">
<tr><td valign="top" width="50%">


<?php

if ( ! empty ( $user ) ) {
  user_load_variables ( $user, "u" );
  echo "<h2><font color=\"$H2COLOR\">" . translate("Edit User") . "</font></h2>\n";
} else {
  echo "<h2><font color=\"$H2COLOR\">" . translate("Add User") . "</font></h2>\n";
}
?>

<form action="edit_user_handler.php" method="POST">
<input type="hidden" name="formtype" value="edituser" />

<?php
if ( empty ( $user ) ) {
  echo "<input type=\"hidden\" name=\"add\" value=\"1\" />\n";
}
?>

<table border="0">
<tr><td><b><?php etranslate("Username")?>:</b></td>
  <td><?php
    if ( ! empty ( $user ) ) {
      if ( $is_admin )
        echo $user . "<input name=\"user\" type=\"hidden\" value=\"" .
          htmlspecialchars ( $user ) . "\" />";
      else
        echo $user;
    } else {
      echo "<input name=\"user\" size=\"20\" maxlength=\"20\" />";
    }
?></td></tr>
<tr><td><b><?php etranslate("First Name")?>:</b></td>
  <td><input name="ufirstname" size="20" value="<?php echo htmlspecialchars ( $ufirstname );?>" /></td></tr>
<tr><td><b><?php etranslate("Last Name")?>:</b></td>
  <td><input name="ulastname" size="20" value="<?php echo htmlspecialchars ( $ulastname );?>" /></td></tr>
<tr><td><b><?php etranslate("E-mail address")?>:</b></td>
  <td><input name="uemail" size="20" value="<?php echo htmlspecialchars ( $uemail );?>"></td></tr>
<?php if ( empty ( $user ) && ! $use_http_auth && $user_can_update_password ) { ?>
<tr><TD><b><?php etranslate("Password")?>:</b></td>
  <td><input name="upassword1" size="15" value="" type="password" /></td></tr>
<tr><td><b><?php etranslate("Password")?> (<?php etranslate("again")?>):</b></td>
  <td><input name="upassword2" size="15" value="" type="password" /></td></tr>
<?php }
  if ( $is_admin ) { ?>
<tr><td><b><?php etranslate("Admin")?>:</b></td>
  <td><input type="radio" name="uis_admin" value="N" <?php if ( $uis_admin != "Y" ) echo "CHECKED=\"CHECKED\"";?>><?php etranslate("No")?> <input type="radio" name="uis_admin" value="Y" <?php if ( $uis_admin == "Y" ) echo "CHECKED=\"CHECKED\"";?>><?php etranslate("Yes")?></td></tr>
<?php } ?>
<tr><td colspan="2">
<?php if ( $demo_mode == "Y" ) { ?>
  <input type="button" value="<?php etranslate("Save")?>" onclick="alert('<?php etranslate("Disabled for demo")?>')" />
  <?php if ( $is_admin && ! empty ( $user ) ) { ?>
    <input type="submit" name="action" value="<?php etranslate("Delete")?>" onclick="alert('<?php etranslate("Disabled for demo")?>')" />
   <?php }?>
<?php } else { ?>
  <input type="submit" value="<?php etranslate("Save")?>" />
  <?php if ( $is_admin && ! empty ( $user ) ) {
          if ( $admin_can_delete_user )
    ?>
    <input type="submit" name="action" value="<?php etranslate("Delete")?>" onclick="return confirm('<?php etranslate("Are you sure you want to delete this entry?"); ?>')" />
  <?php } ?>
<?php } ?>
</td></tr>
</table>

</form>

<?php if ( ! empty ( $user ) && ! $use_http_auth &&
  ( isset ( $user_can_update_password ) && $user_can_update_password ) ) { ?>

</td>
<td>&nbsp;&nbsp;</td>
<td valign="top">

<h2><font color="<?php echo $H2COLOR;?>"><?php etranslate("Change Password")?></font></h2>
<form action="edit_user_handler.php" method="POST">
<input type="hidden" name="formtype" value="setpassword" />
<?php if ( $is_admin ) { ?>
<input type="hidden" name="user" value="<?php echo $user;?>" />
<?php } ?>
<table border="0">
<tr><td><b><?php etranslate("New Password")?>:</b></td>
  <td><input name="upassword1" type="password" size="15" /></td></tr>
<tr><td><b><?php etranslate("New Password")?> (<?php etranslate("again")?>):</b></td>
  <td><input name="upassword2" type="password" size="15" /></td></tr>
<tr><td colspan="2">
  <?php if ( $demo_mode == "Y" ) { ?>
    <input type="button" value="<?php etranslate("Set Password")?>" onclick="alert('<?php etranslate("Disabled for demo")?>')" />
  <?php } else { ?>
    <input type="submit" value="<?php etranslate("Set Password")?>" />
  <?php } ?>
</td></tr>
</table>
</form>

<?php } ?>
</td></tr></table>

<?php print_trailer(); ?>
</body>
</html>
