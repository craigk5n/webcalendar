<?php php_track_vars?>
<?php

include "includes/config.inc";
include "includes/php-dbi.inc";
include "includes/functions.inc";
include "includes/user.inc";
include "includes/validate.inc";
include "includes/connect.inc";

load_user_preferences ();
load_user_layers ();

include "includes/translate.inc";

$error = "";

// Only proceed if id was passed
if ( $id > 0 ) {

  // double check to make sure user doesn't already have the event
  $is_my_event = false;
  $sql = "SELECT cal_id FROM webcal_entry_user " .
    "WHERE cal_login = '$login' AND cal_id = $id";
  $res = dbi_query ( $sql );
  if ( $res ) {
    $row = dbi_fetch_row ( $res );
    if ( $row[0] == $id ) {
      $is_my_event = true;
      echo "Event # " . $id . " is already on your calendar.";
      exit;
    }
    dbi_free_result ( $res );
  }

  // Now lets make sure the user is allowed to add the event (not private)

  $sql = "SELECT cal_access FROM webcal_entry WHERE cal_id = " . $id;
  $res = dbi_query ( $sql );
  if ( ! $res ) {
    echo translate("Invalid entry id") . ": $id";
    exit;
  }
  $row = dbi_fetch_row ( $res );

  if ( $row[0] == "R" && ! $is_my_event ) {
    $is_private = true;
    etranslate("This is a private event and may not be added to your calendar.");
    exit;
  } else {
    $is_private = false;
  }

   // add the event
  if ( ! $readonly && ! $is_my_event && ! $is_private )  {
    if ( ! dbi_query ( "INSERT INTO webcal_entry_user VALUES ($id, '$login', 'A')") ) {
      $error = translate("Error adding event") . ": " . dbi_error ();
    }
  }
}

do_redirect ( "$STARTVIEW.php" . ( $thisdate > 0 ? "?date=$thisdate" : "" ) );
exit;
?>
