<?php
// Do a sanity check.  Make sure we can access webcal_config table.
// We call this right after the first call to dbi_connect() (from
// either the WebCalendar class or here in validate.php).
function doDbSanityCheck () {
  global $db_login, $db_host, $db_database;
  $res = @dbi_query ( "SELECT COUNT(cal_value) FROM webcal_config",
    false, false );
  if ( $res ) {
    if ( $row = dbi_fetch_row ( $res ) ) {
      // Found database.  All is peachy.
      dbi_free_result ( $res );
    } else {
      // Error accessing table.
      // User has wrong db name or has not created tables.
      // Note: cannot translate this since we have not included
      // translate.php yet.
      dbi_free_result ( $res );
      die_miserable_death (
        "Error finding WebCalendar tables in database '$db_database' " .
        "using db login '$db_login' on db server '$db_host'.<br/><br/>\n" .
        "Have you created the database tables as specified in the " .
        "<a href=\"docs/WebCalendar-SysAdmin.html\" target=\"other\">WebCalendar " .
        "System Administrator's Guide</a>?" );
    }
  } else {
    // Error accessing table.
    // User has wrong db name or has not created tables.
    // Note: cannot translate this since we have not included translate.php yet.
    die_miserable_death (
      "Error finding WebCalendar tables in database '$db_database' " .
      "using db login '$db_login' on db server '$db_host'.<br/><br/>\n" .
      "Have you created the database tables as specified in the " .
      "<a href=\"docs/WebCalendar-SysAdmin.html\" target=\"other\">WebCalendar " .
      "System Administrator's Guide</a>?" );
  }
}

?>
