<?php
include_once 'includes/init.php';

$error = "";

$viewisglobal = getPostValue ( "is_global" );
if ( ! $is_admin || $viewisglobal != 'Y' )
  $viewisglobal = 'N'; // only admin can create global view
//echo "viewisglobal = $viewisglobal"; exit;

$delete = getPostValue ( 'delete' );
if ( ! empty ( $delete ) ) {
  // delete this view
  dbi_execute ( "DELETE FROM webcal_view WHERE cal_view_id = ? AND cal_owner = ?" , array ( $id , $login ) );
} else {
  if ( empty ( $viewname ) ) {
    $error = translate("You must specify a view name");
  }
  else if ( ! empty ( $id ) ) {
    // update
    if ( ! dbi_execute ( "UPDATE webcal_view SET cal_name = " .
      "?, cal_view_type = ?, " .
      "cal_is_global = ? " .
      "WHERE cal_view_id = ? AND cal_owner = ?" , array ( $viewname , $viewtype , $viewisglobal , $id , $login ) ) ) {
      $error = translate ("Database error") . ": " . dbi_error();
    }
  } else {
    # new... get new id first
    $res = dbi_execute ( "SELECT MAX(cal_view_id) FROM webcal_view" , array () );
    if ( $res ) {
      $row = dbi_fetch_row ( $res );
      $id = $row[0];
      $id++;
      dbi_free_result ( $res );
      $sql = "INSERT INTO webcal_view " .
        "( cal_view_id, cal_owner, cal_name, cal_view_type, cal_is_global ) " .
        " VALUES ( ?, ?, ?, ?, ? )";
      $sql_params = array ( $id , $login , $viewname , $viewtype , $viewisglobal );
      if ( ! dbi_execute ( $sql , $sql_params ) ) {
        $error = translate ("Database error") . ": " . dbi_error();
      }
    } else {
      $error = translate ("Database error") . ": " . dbi_error();
    }
  }

  # update user list
  if ( $error == "" ) {
    dbi_execute ( "DELETE FROM webcal_view_user WHERE cal_view_id = ?" , array ( $id ) );
    // If selected "All", then just put "__all__" in for username.
    if ( getPostValue ( "viewuserall" ) == "Y" )
      $users = array ( "__all__" );
    for ( $i = 0; ! empty ( $users ) && $i < count ( $users ); $i++ ) {
      dbi_execute ( "INSERT INTO webcal_view_user ( cal_view_id, cal_login ) " .
        "VALUES ( ?, ? )" , array ( $id , $users[$i] ) );
    }
  }
}

error_check('views.php');
?>
