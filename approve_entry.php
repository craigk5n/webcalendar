<?php php_track_vars?>
<?php

include "includes/config.inc";
include "includes/php-dbi.inc";
include "includes/functions.inc";
include "includes/validate.inc";
include "includes/connect.inc";

load_user_preferences ();
load_user_layers ();

include "includes/translate.inc";

$error = "";

if ( $id > 0 ) {
  if ( ! dbi_query ( "UPDATE webcal_entry_user SET cal_status = 'A' " .
    "WHERE cal_login = '$login' AND cal_id = $id" ) ) {
    $error = translate("Error approving event") . ": " . dbi_error ();
  }
}

if ( $ret == "list" )
  do_redirect ( "list_unapproved.php" );
else
  do_redirect ( "view_entry.php?id=$id" );
?>
