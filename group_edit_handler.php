<?php

include "includes/config.php";
include "includes/php-dbi.php";
include "includes/functions.php";
include "includes/$user_inc";

include "includes/validate.php";
include "includes/connect.php";

load_global_settings ();
load_user_preferences ();

include "includes/translate.php";

if ( ! $is_admin ) {
  $error = "You are not authorized";
} else  {
  if ( $action == "Delete" || $action == translate ("Delete") ) {
    // delete this group
    dbi_query ( "DELETE FROM webcal_group WHERE cal_group_id = $id " );
    dbi_query ( "DELETE FROM webcal_group_user WHERE cal_group_id = $id " );
  } else {
    $date = date ( "Ymd" );
    if ( ! empty ( $id ) ) {
      # update
      if ( ! dbi_query ( "UPDATE webcal_group SET cal_name = " .
        "'$groupname', cal_last_update = $date " .
        "WHERE cal_group_id = $id" ) ) {
        $error = translate ("Database error") . ": " . dbi_error();
      }
    } else {
      # new... get new id first
      $res = dbi_query ( "SELECT MAX(cal_group_id) FROM webcal_group" );
      if ( $res ) {
        $row = dbi_fetch_row ( $res );
        $id = $row[0];
        $id++;
        dbi_free_result ( $res );
        $sql = "INSERT INTO webcal_group " .
          "( cal_group_id, cal_owner, cal_name, cal_last_update ) VALUES ( " .
          "$id, '$login', '$groupname', $date )";
        if ( ! dbi_query ( $sql ) ) {
          $error = translate ("Database error") . ": " . dbi_error();
        }
      } else {
        $error = translate ("Database error") . ": " . dbi_error();
      }
    }
  
    # update user list
    if ( $error == "" ) {
      dbi_query ( "DELETE FROM webcal_group_user WHERE cal_group_id = $id" );
      for ( $i = 0; $i < count ( $users ); $i++ ) {
        dbi_query ( "INSERT INTO webcal_group_user ( cal_group_id, cal_login ) " .
          "VALUES ( $id, '$users[$i]' )" );
      }
    }
  }
}


if ( $error == "" ) {
  do_redirect ( "groups.php" );
}
?>
<HTML>
<HEAD>
<TITLE><?php etranslate($application_name)?></TITLE>
<?php include "includes/styles.php"; ?>
</HEAD>
<BODY BGCOLOR="<?php echo $BGCOLOR;?>" CLASS="defaulttext">

<H2><FONT COLOR="<?php echo $H2COLOR;?>"><?php etranslate("Error")?></FONT></H2>

<BLOCKQUOTE>
<?php

echo $error;
//if ( $sql != "" )
//  echo "<P><B>SQL:</B> $sql";
//?>
</BLOCKQUOTE>

<?php include "includes/trailer.php"; ?>
</BODY>
</HTML>
