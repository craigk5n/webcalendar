<?php php_track_vars?>
<?php

include "includes/config.inc";
include "includes/php-dbi.inc";
include "includes/functions.inc";

if ( $use_external_auth )
  do_redirect ( "index.php" );

include "includes/validate.inc";
include "includes/connect.inc";

load_user_preferences ();
load_user_layers ();

include "includes/translate.inc";

$error = "";
if ( ! $is_admin )
  $user = $login;

if ( ( $action == "Delete" || $action == translate ("Delete") ) && $is_admin ) {
  $sql = "SELECT COUNT(cal_id) FROM webcal_entry_user WHERE cal_login = '$user'";
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
    if ( strlen ( $error ) == 0 ) {
      $sql = "DELETE FROM webcal_user WHERE cal_login = '$user'";
      dbi_query ( $sql );
    }
  }
} elseif ( strlen ( $user ) && strlen ( $error ) == 0 ) {
  if ( $upassword1 != $upassword2 ) {
    $error = translate("The passwords were not identical") . ".";
  } else {
    if ( strlen ( $uemail ) )
      $uemail_ = "'" . $uemail . "'";
    else
      $uemail_ = "NULL";
    if ( strlen ( $add ) && $is_admin )
      $sql = "INSERT INTO webcal_user " .
	"( cal_login, cal_lastname, cal_firstname, " .
	"cal_is_admin, cal_passwd, cal_email ) " .
	"VALUES ( '$user', '$ulastname', '$ufirstname', " .
	"'$uis_admin', '$upassword1', $uemail_ )";
    else if ( strlen ( $ulastname ) ) {
      $sql = "UPDATE webcal_user SET cal_lastname = '$ulastname', " .
	"cal_firstname = '$ufirstname'";
      if ( $is_admin && strlen ( $uis_admin ) )
	$sql .= ", cal_is_admin = '$uis_admin'";
      $sql .= ", cal_email = $uemail_";
      $sql .= " WHERE cal_login = '$user'";
    } else if ( strlen ( $upassword1 ) && strlen ( $user ) )
      $sql = "UPDATE webcal_user SET cal_passwd = '$upassword1' " .
	"WHERE cal_login = '$user'";
    else
      $error = translate("You have not entered a password");
  }
}
if ( strlen ( $error ) == 0 ) {
    if ( ! dbi_query ( $sql ) )
      $error = dbi_error ();
    else {
      if ( $is_admin )
        do_redirect ( "users.php" );
      else
        do_redirect ( "edit_user.php" );
    }
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
