<?php
include_once 'includes/init.php';

if ( ! $is_admin ) {
  $error = "You are not authorized";
} else  {
  if ( $action == "Delete" || $action == translate ("Delete") ) {
    // delete this group
    dbi_query ( "DELETE FROM webcal_group WHERE cal_group_id = $id " );
    dbi_query ( "DELETE FROM webcal_group_user WHERE cal_group_id = $id " );
  } else {
    $date = date ( "Ymd" );
    if ( empty ( $groupname ) ) {
      $error = translate("You must specify a group name");
    }
    else if ( ! empty ( $id ) ) {
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

print_header();

if ( $error != "" ) { ?>
<h2><?php etranslate("Error")?></h2>

<blockquote>
<?php
echo $error;

//if ( $sql != "" )
//  echo "<br /><br /><span style=\"font-weight:bold;\">SQL:</span> $sql";
?>
</blockquote>

<?php } else if ( $error == "" ) {
	echo "Success! The changes you requested were saved.";
}
?>
</body>
</html>
