<?php

include "includes/config.inc";
include "includes/php-dbi.inc";
include "includes/functions.inc";
include "includes/$user_inc";

include "includes/validate.inc";
include "includes/connect.inc";

load_user_preferences ();
load_user_layers ();

include "includes/translate.inc";

$error = "";
if ( ! $is_admin )
  $user = $login;

if ( ( $action == "Delete" || $action == translate ("Delete") ) && $is_admin ) {
  if ( $admin_can_delete_user ) {
    $sql = "SELECT COUNT(cal_id) FROM webcal_entry_user " .
      "WHERE cal_login = '$user'";
    $res =  dbi_query ( $sql );
    if ( $res ) {
      $row = dbi_fetch_row ( $res );
      if ( $row[0] > 0 ) {
        $error = translate("This user has") .  $row[0] . " " .
          translate("calendar entries") . ".  " .
          translate("They must all be deleted (or this user removed as a participant) before this user can be deleted") . ".";
        $sql = "";
      }
      dbi_free_result ( $res );
      if ( empty ( $error ) ) {
        user_delete_user ( $user );
      }
    }
  } else {
    $error = "Deleting users not supported.";
  }
} elseif ( ! empty ( $user ) && empty ( $error ) ) {
  if ( $upassword1 != $upassword2 ) {
    $error = translate("The passwords were not identical") . ".";
  } else {
    if ( strlen ( $add ) && $is_admin )
      user_add_user ( $user, $upassword1, $ufirstname, $ulastname,
        $uemail, $uis_admin );
    else if ( strlen ( $ulastname ) )
      user_update_user ( $user, $ufirstname, $ulastname, $uemail, $uis_admin );
    else if ( strlen ( $upassword1 ) && strlen ( $user ) )
      user_update_user_password ( $user, $upassword1 );
    else
      $error = translate("You have not entered a password");
  }
}
if ( empty ( $error ) ) {
  if ( $is_admin )
    do_redirect ( "users.php" );
  else
    do_redirect ( "edit_user.php" );
}
?>
<HTML>
<HEAD>
<TITLE><?php etranslate("Title")?></TITLE>
<?php include "includes/styles.inc"; ?>
</HEAD>
<BODY BGCOLOR="<?php echo $BGCOLOR;?>">

<H2><FONT COLOR="<?php echo $H2COLOR;?>"><?php etranslate("Error")?></FONT></H2>

<BLOCKQUOTE>
<?php

echo $error;
//if ( $sql != "" )
//  echo "<P><B>SQL:</B> $sql";
//?>
</BLOCKQUOTE>

<?php include "includes/trailer.inc"; ?>
</BODY>
</HTML>
