<?php // $Id$
/**
 * Do a sanity check. Make sure we can access webcal_config table.
 * We call this right after the first call to dbi_connect()
 * (from either the WebCalendar class or here in validate.php).
 */
function doDbSanityCheck() {
  global $db_database, $db_host, $db_login;

  // Since "translate.php" is gettng loaded sooner...
  $dieMsgStr = str_replace( array( 'XXX', 'YYY', 'ZZZ' ),
    array( $db_database, $db_login, $db_host ),
    translate( 'cant find tables in db XXX' ) )
   . '<br><br>'
   . str_replace( 'XXX',
     '<a href="docs/WebCalendar-SysAdmin.html" target="other">'
       . translate( 'WebCal SysAdmin Guide' ) . '</a>',
     translate( 'Have you created db XXX' ) );
  $res = @dbi_execute ( 'SELECT COUNT( cal_value ) FROM webcal_config',
    array(), false, false );
  if ( $res ) {
    if ( $row = dbi_fetch_row ( $res ) )
      // Found database. All is peachy.
      dbi_free_result ( $res );
    else {
      // Error accessing table.
      // User has wrong db name or has not created tables.
      dbi_free_result ( $res );
      die_miserable_death ( $dieMsgStr );
    }
  } else
    die_miserable_death ( $dieMsgStr );
}

?>
