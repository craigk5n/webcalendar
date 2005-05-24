<?php
/* $Id$ */
include_once 'includes/init.php';

$error = '';

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
    if ( ! access_can_access_function ( ACCESS_USER_MANAGEMENT ) ) {
      $error = translate ( "You are not authorized" );
    }
  }
  if ( ! $admin_can_add_user ) {
    // if adding users is not allowed...
    $error = translate ( "You are not authorized" );
  }
} else {
  // User is editing their account info
  if ( ! access_can_access_function ( ACCESS_ACCOUNT_INFO ) )
    $error = translate ( "You are not authorized" );
}

$disableCustom = true;

print_header ( '', '', '', $disableCustom );

if ( ! empty ( $error ) ) {
  echo "<h2>" . translate ( "Error" ) . "</h2>\n<p>" . $error . "</p>\n";
} else {
?>
<table style="border-width:0px;">
<tr><td style="vertical-align:top; width:50%;">
<h2><?php
	if ( ! empty ( $user ) ) {
		user_load_variables ( $user, "u" );
		echo translate("Edit User");
	} else {
		echo translate("Add User");
	}
?></h2>
<form action="edit_user_handler.php" method="post">
<input type="hidden" name="formtype" value="edituser" />
<?php
	if ( empty ( $user ) ) {
		echo "<input type=\"hidden\" name=\"add\" value=\"1\" />\n";
	}
?>
<table style="border-width:0px;">
	<tr><td>
		<label for="username"><?php etranslate("Username")?>:</label></td><td>
  <?php
    if ( ! empty ( $user ) ) {
      if ( $is_admin )
        echo $user . "<input name=\"user\" type=\"hidden\" value=\"" .
          htmlspecialchars ( $user ) . "\" />\n";
      else
        echo $user;
    } else {
      echo "<input type=\"text\" name=\"user\" id=\"username\" size=\"25\" maxlength=\"25\" />\n";
    }
?>
	</td></tr>
	<tr><td>
		<label for="ufirstname"><?php etranslate("First Name")?>:</label></td><td>
		<input type="text" name="ufirstname" id="ufirstname" size="20" value="<?php echo empty ( $ufirstname ) ? '' : htmlspecialchars ( $ufirstname );?>" />
	</td></tr>
	<tr><td>
		<label for="ulastname"><?php etranslate("Last Name")?>:</label></td><td>
		<input type="text" name="ulastname" id="ulastname" size="20" value="<?php echo empty ( $ulastname ) ? '' : htmlspecialchars ( $ulastname );?>" />
	</td></tr>
	<tr><td>
		<label for="uemail"><?php etranslate("E-mail address")?>:</label></td><td>
		<input type="text" name="uemail" id="uemail" size="20" value="<?php echo empty ( $uemail ) ? '' : htmlspecialchars ( $uemail );?>" />
	</td></tr>
<?php if ( empty ( $user ) && ! $use_http_auth && $user_can_update_password ) { ?>
	<tr><td>
		<label for="pass1"><?php etranslate("Password")?>:</label></td><td>
		<input name="upassword1" id="pass1" size="15" value="" type="password" />
	</td></tr>
	<tr><td>
		<label for="pass2"><?php etranslate("Password")?> (<?php etranslate("again")?>):</label></td><td>
		<input name="upassword2" id="pass2" size="15" value="" type="password" />
	</td></tr>
<?php }
if ( $is_admin ) { ?>
	<tr><td style="font-weight:bold;">
		<?php etranslate("Admin")?>:</td><td>
		<label><input type="radio" name="uis_admin" value="Y"<?php if ( ! empty ( $uis_admin ) && $uis_admin == "Y" ) echo " checked=\"checked\"";?> />&nbsp;<?php etranslate("Yes")?></label> 
		<label><input type="radio" name="uis_admin" value="N"<?php if ( empty ( $uis_admin ) || $uis_admin != "Y" ) echo " checked=\"checked\"";?> />&nbsp;<?php etranslate("No")?></label>
	</td></tr>
<?php } //end if ($is_admin ) ?>
	<tr><td colspan="2">
		<?php if ( $demo_mode == "Y" ) { ?>
			<input type="button" value="<?php etranslate("Save")?>" onclick="alert('<?php etranslate("Disabled for demo")?>')" />
			<?php if ( $is_admin && ! empty ( $user ) ) { ?>
				<input type="submit" name="action" value="<?php etranslate("Delete")?>" onclick="alert('<?php etranslate("Disabled for demo")?>')" />
			<?php } //end if ( $demo_mode == "Y" ) ?>
		<?php } else { ?>
			<input type="submit" value="<?php etranslate("Save")?>" />
			<?php if ( $is_admin && ! empty ( $user ) ) {
				if ( $admin_can_delete_user ) ?>
				<input type="submit" name="action" value="<?php etranslate("Delete")?>" onclick="return confirm('<?php etranslate("Are you sure you want to delete this user?"); ?>')" />
			<?php } ?>
		<?php } ?>
	</td></tr>
</table>
</form>

<?php if ( ! empty ( $user ) && ! $use_http_auth &&
  ( isset ( $user_can_update_password ) && $user_can_update_password ) ) { ?>
</td><td>&nbsp;&nbsp;</td>
<td style="vertical-align:top;">

<h2><?php etranslate("Change Password")?></h2>
<form action="edit_user_handler.php" method="post">
<input type="hidden" name="formtype" value="setpassword" />
<?php if ( $is_admin ) { ?>
	<input type="hidden" name="user" value="<?php echo $user;?>" />
<?php } ?>
<table style="border-width:0px;">
	<tr><td>
		<label for="newpass1"><?php etranslate("New Password")?>:</label></td><td>
		<input name="upassword1" id="newpass1" type="password" size="15" />
	</td></tr>
	<tr><td>
		<label for="newpass2"><?php etranslate("New Password")?> (<?php etranslate("again")?>):</label></td><td>
		<input name="upassword2" id="newpass2" type="password" size="15" />
	</td></tr>
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
<?php } ?>

<?php print_trailer ( false, true, true ); ?>
</body>
</html>
