<?php
include_once 'includes/init.php';

$error = "";

if ( $action == "Delete" || $action == translate ("Delete") ) {
  // delete this view
  dbi_query ( "DELETE FROM webcal_view WHERE cal_view_id = $id " .
    "AND cal_owner = '$login'" );
} else {
  if ( empty ( $viewname ) ) {
    $error = translate("You must specify a view name");
  }
  else if ( ! empty ( $id ) ) {
    # update
    if ( ! dbi_query ( "UPDATE webcal_view SET cal_name = " .
      "'$viewname', cal_view_type = '$viewtype' " .
      "WHERE cal_view_id = $id AND cal_owner = '$login'" ) ) {
      $error = translate ("Database error") . ": " . dbi_error();
    }
  } else {
    # new... get new id first
    $res = dbi_query ( "SELECT MAX(cal_view_id) FROM webcal_view" );
    if ( $res ) {
      $row = dbi_fetch_row ( $res );
      $id = $row[0];
      $id++;
      dbi_free_result ( $res );
      $sql = "INSERT INTO webcal_view " .
        "( cal_view_id, cal_owner, cal_name, cal_view_type ) VALUES ( " .
        "$id, '$login', '$viewname', '$viewtype' )";
      if ( ! dbi_query ( $sql ) ) {
        $error = translate ("Database error") . ": " . dbi_error();
      }
    } else {
      $error = translate ("Database error") . ": " . dbi_error();
    }
  }

  # update user list
  if ( $error == "" ) {
    dbi_query ( "DELETE FROM webcal_view_user WHERE cal_view_id = $id" );
    for ( $i = 0; $i < count ( $users ); $i++ ) {
      dbi_query ( "INSERT INTO webcal_view_user ( cal_view_id, cal_login ) " .
        "VALUES ( $id, '$users[$i]' )" );
    }
  }
}



if ( $error == "" ) {
  do_redirect ( "views.php" );
}
print_header();
?>

<h2><?php etranslate("Error")?></h2>

<blockquote>
<?php

echo $error;
//if ( $sql != "" )
//  echo "<br /><br /><span style=\"font-weight:bold;\">SQL:</span> $sql";
//?>
</blockquote>

<?php print_trailer(); ?>
</body>
</html>
