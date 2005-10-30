<?php
/*
 * $Id: 
 *
 * Page Description:
 * This page will allow admins to load the timezone data in
 * /install/timezone in to the webcal_tz tables
 *
 *
 * Security:
 * Admin permissions are checked by the WebCalendar class.
 */
include_once 'includes/init.php';
include_once 'install/tz_import.php';

if ( ! $is_admin ) {
  $error = translate( "You are not authorized" );
}

//assert ( ( $is_admin && ! access_is_enabled () ) ||
//  access_can_access_function ( ACCESS_SYSTEM_SETTINGS ) );

$action = getPostValue ( 'action' );
if ( ! empty ( $action ) && $action == translate( "Load" ) ) {
  $ret = do_tz_import ( "install/timezone/" );
}



print_header( '', '', '', true );


if ( ! empty ( $error ) ) {
  echo "<h2>" . translate("Error") . "</h2>\n" .
    $error . "\n";
} else {
?>
<?php if ( empty ( $ret ) ) { ?>
  <form action="load_tz_data.php" method="post" name="loadform">
  <table align="center" border="0" ><tr><td colspan="2" align="center">

  <h3><?php etranslate("Loading Timezone Data into Database")?></h3>
  <?php etranslate("This could take several minutes to complete")?>
  </td>
  </tr><tr>
  <tr><td></tr></tr>
  <td align="center">
  <input type="button" value="<?php etranslate("Cancel")?>" onclick="window.close();" />
  </td><td align="center">
  <input name="action" type="submit" value="<?php etranslate("Load")?>" />
  </td></tr>
  </table>
  </form>
<?php } else { ?>
  <form action="" method="post" name="loadstatus">
  <table align="center" border="0" ><tr><td align="center">

  <h3><?php echo $ret; ?></h3>

  </td>
  </tr><tr>
  <tr><td></tr></tr>
  <td align="center">
  <input type="button" value="<?php etranslate("Close")?>" onclick="window.close();" />
  </td></tr>
  </table>
  </form>   
<?php }  ?>
<?php }
 print_trailer ( false, true, true );
?>
</body>
</html>
