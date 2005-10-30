<?php
include_once 'includes/init.php';
load_user_categories();

$error = "";

if ( $readonly == 'Y' ) {
  $error = translate("You are not authorized");
}

// Allow administrators to approve public events
if ( $PUBLIC_ACCESS == "Y" && ! empty ( $public ) && $is_admin )
  $app_user = "__public__";
else
  $app_user = ( $is_assistant || $is_nonuser_admin ? $user : $login );

// If User Access Control is enabled, we check to see if they are
// allowed to approve for the specified user.
if ( access_is_enabled () && ! empty ( $user ) &&
  $user != $login ) {
  if ( access_can_approve_user_calendar ( $user ) )
    $app_user = $user;
}

if ( empty ( $error ) && $id > 0 ) {
  $approve_type = LOG_APPROVE; //used in activity log below
	// Update any extension events related to this one.
  $res = dbi_query ( "SELECT cal_id, cal_type FROM webcal_entry " .
    "WHERE cal_ext_for_id = $id" );
  if ( $res ) {
    if ( $row = dbi_fetch_row ( $res ) ) {
      $ext_id = $row[0];
			$approve_type = ( $row[1] == 'E' || $row[1] == 'M'? LOG_APPROVE : LOG_APPROVE_T ); 
      if ( ! dbi_query ( "UPDATE webcal_entry_user SET cal_status = 'A' " .
        "WHERE cal_login = '$app_user' AND cal_id = $ext_id" ) ) {
        $error = translate("Error approving event") . ": " . dbi_error ();
      }
    }
    dbi_free_result ( $res );
  }
	
	if ( ! dbi_query ( "UPDATE webcal_entry_user SET cal_status = 'A' " .
    "WHERE cal_login = '$app_user' AND cal_id = $id" ) ) {
    $error = translate("Error approving event") . ": " . dbi_error ();
  } else {
    activity_log ( $id, $login, $app_user, $approve_type, "" );
  }
}

if ( empty ( $error ) ) {
  if ( ! empty ( $ret ) && $ret == "listall" )
    do_redirect ( "list_unapproved.php" );
  else if ( ! empty ( $ret ) && $ret == "list" )
    do_redirect ( "list_unapproved.php?user=$app_user" );
  else
    do_redirect ( "view_entry.php?id=$id&amp;user=$app_user" );
  exit;
}
print_header ();
echo "<h2>" . translate("Error") . "</h2>\n";
echo "<p>" . $error . "</p>\n";
print_trailer ();
?>
