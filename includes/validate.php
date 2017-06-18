<?php
/* $Id: validate.php,v 1.25 2007/07/12 19:29:12 bbannon Exp $ */
// Do a sanity check. Make sure we can access webcal_config table. We call this
// right after the first call to dbi_connect ()
// (from either the WebCalendar class or here in validate.php).
function doDbSanityCheck () {
  global $db_database, $db_host, $db_login;
  $dieMsgStr = 'Error finding WebCalendar tables in database "' . $db_database
   . '" using db login "' . $db_login . '" on db server "' . $db_host
   . '".<br /><br />
Have you created the database tables as specified in the
<a href="docs/WebCalendar-SysAdmin.html" '
   . '  target="other">WebCalendar System Administrator\'s Guide</a>?';
  $res = @dbi_execute ( 'SELECT COUNT( cal_value ) FROM webcal_config',
    array (), false, false );
  if ( $res ) {
    if ( $row = dbi_fetch_row ( $res ) )
      // Found database. All is peachy.
      dbi_free_result ( $res );
    else {
      // Error accessing table.
      // User has wrong db name or has not created tables.
      // Note: can't translate this since translate.php is not included yet.
      dbi_free_result ( $res );
      die_miserable_death ( $dieMsgStr );
    }
  } else
    die_miserable_death ( $dieMsgStr );
}

?>
