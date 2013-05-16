<?php // $Id$
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
      $error = print_not_auth();
    }
  }
  if ( ! $admin_can_add_user ) {
    // if adding users is not allowed...
    $error = print_not_auth();
  }
} else {
  // User is editing their account info
  if ( ! access_can_access_function ( ACCESS_ACCOUNT_INFO ) )
    $error = print_not_auth();
}

$disableCustom = true;
$INC = array('js/edit_user.php');
print_header( $INC, '', '', $disableCustom, '', true );

if ( ! empty ( $error ) ) {
  echo print_error ( $error );
} else {
?>
<table summary="">
<tr><td style="vertical-align:top; width:50%;">
<h2><?php
 if ( ! empty ( $user ) ) {
  user_load_variables ( $user, 'u' );
  echo translate ( 'Edit User' );
 } else {
  echo translate ( 'Add User' );
 }
?></h2>
<form action="edit_user_handler.php" name="edituser" method="post" onsubmit="return valid_form( this );">
<input type="hidden" name="formtype" value="edituser">
<?php
 if ( empty ( $user ) ) {
  echo '<input type="hidden" name="add" value="1">' . "\n";
 }
?>
<table summary="">
 <tr><td>
  <label for="username"><?php echo translate ( 'Username' )?></label></td><td>
  <?php
    if ( ! empty ( $user ) ) {
      if ( $is_admin )
        echo $user . '<input name="user" type="hidden" value="' .
          htmlspecialchars( $user ) . "\">\n";
      else
        echo $user;
    } else {
      echo '<input type="text" name="user" id="username" size="25" onchange="check_name();" maxlength="25">' . "\n";
    }
?>
 </td></tr>
 <tr><td>
  <label for="ufirstname"><?php echo translate ( 'First Name' )?></label></td><td>
  <input type="text" name="ufirstname" id="ufirstname" size="20" value="<?php
  echo empty( $ufirstname ) ? '' : htmlspecialchars( $ufirstname );?>">
 </td></tr>
 <tr><td>
  <label for="ulastname"><?php echo translate ( 'Last Name' )?></label></td><td>
  <input type="text" name="ulastname" id="ulastname" size="20" value="<?php
  echo empty( $ulastname ) ? '' : htmlspecialchars( $ulastname );?>">
 </td></tr>
 <tr><td>
  <label for="uemail"><?php echo translate ( 'E-mail address' )?></label></td><td>
  <input type="text" name="uemail" id="uemail" size="20" value="<?php echo
  empty( $uemail ) ? '' : htmlspecialchars( $uemail );?>" onchange="check_uemail();">
 </td></tr>
<?php if ( empty ( $user ) && ! $use_http_auth && $user_can_update_password ) { ?>
 <tr><td>
  <label for="pass1"><?php echo translate ( 'Password' )?></label></td><td>
  <input name="upassword1" id="pass1" size="15" value="" type="password">
 </td></tr>
 <tr><td>
  <label for="pass2"><?php echo translate ( 'Password (again)' )?></label></td><td>
  <input name="upassword2" id="pass2" size="15" value="" type="password">
 </td></tr>
<?php }
// An admin can't change their own Admin or Enabled status
if ( $is_admin && ( empty( $user ) || $user != $login ) ) { ?>
 <tr><td class="bold">
  <?php echo translate ( 'Admin_' )?></td><td>
  <?php
    $defIdx = ( ! empty ( $uis_admin ) && $uis_admin == 'Y' ? 'Y' : 'N' );
    echo print_radio ( 'uis_admin', '', '', $defIdx ) ?>
 </td></tr>
  <?php if ( ! empty( $admin_can_disable_user )
   && $admin_can_disable_user = true ) { ?>
 <tr><td class="bold">
  <?php echo translate ( 'Enabled_' )?></td><td>
  <?php
    $defIdx = ( ! empty ( $uenabled ) && $uenabled == 'N' ? 'N' : 'Y' );
    echo print_radio ( 'u_enabled', '', '', $defIdx ) ?>
 </td></tr>
  <?php } ?>
<?php }  else if ( $is_admin ) {  ?>
  <input type="hidden" name="uis_admin" value="Y">
  <input type="hidden" name="u_enabled" value="Y">
<?php } //end if ($is_admin ) ?>
 <tr><td colspan="2">
  <?php if ( $DEMO_MODE == 'Y' ) { ?>
   <input type="button" value="<?php echo $saveStr;?>" onclick="alert('<?php
  echo translate ( 'Disabled for demo', true )?>')">
   <?php if ( $is_admin && ! empty ( $user ) ) { ?>
    <input type="submit" name="delete" value="<?php
    echo $deleteStr;?>" onclick="alert('<?php
    echo translate ( 'Disabled for demo', true )?>')">
   <?php } //end if ( $DEMO_MODE == 'Y' )
   } else { ?>
   <input type="submit" value="<?php echo $saveStr;?>">
   <?php if ( $is_admin && ! empty ( $user ) && $user != $login ) {
    if ( $admin_can_delete_user ) ?>
    <input type="submit" name="delete" value="<?php
    echo $deleteStr;?>" onclick="return confirm('<?php
    echo translate( 'really delete user' ) ?>')">
   <?php }
  } ?>
 </td></tr>
</table>
</form>

<?php if ( ! empty ( $user ) && ! $use_http_auth &&
  ( $user_can_update_password ) ) { ?>
</td><td>&nbsp;&nbsp;</td>
<td class="aligntop">

<h2><?php echo translate ( 'Change Password' )?></h2>
<form action="edit_user_handler.php" method="post" onsubmit="return valid_form2( this );">
<input type="hidden" name="formtype" value="setpassword">
<?php if ( $is_admin ) { ?>
 <input type="hidden" name="user" value="<?php echo $user;?>">
<?php } ?>
<table summary="">
 <tr><td>
  <label for="newpass1"><?php echo translate ( 'New Password_' )?></label></td><td>
  <input name="upassword1" id="newpass1" type="password" size="15">
 </td></tr>
 <tr><td>
  <label for="newpass2"><?php echo translate ( 'New Password (again)' )?></label></td><td>
  <input name="upassword2" id="newpass2" type="password" size="15">
 </td></tr>
 <tr><td colspan="2">
  <?php if ( $DEMO_MODE == 'Y' ) { ?>
   <input type="button" value="<?php
   echo translate ( 'Set Password' )?>" onclick="alert('<?php
   echo translate ( 'Disabled for demo', true )?>')">
  <?php } else { ?>
   <input type="submit" value="<?php echo translate ( 'Set Password' )?>">
  <?php } ?>
 </td></tr>
</table>
</form>
<?php } ?>
</td></tr></table>
<?php }
echo print_trailer ( false, true, true ); ?>

