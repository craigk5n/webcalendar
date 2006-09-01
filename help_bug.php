<?php
/* $Id$ */
include_once 'includes/init.php';
include_once 'includes/help_list.php';  
print_header('', '', '', true);
echo $helpListStr;
$rowStr = '</td></tr><tr><td>';
?>

<h2><?php etranslate( 'Report Bug' )?></h2>

<?php 
  //No need to translate the text below since I want all bugs
  //reported in English.
  //Americans only speak English, of course ;-)
?>
<p>Please include all the information below when reporting a bug.</p>
<?php if ( $LANGUAGE != 'English-US' ) { ?>
  Also.. when reporting a bug, please use <strong>English</strong> rather than <?php echo $LANGUAGE?>.
<?php } ?>
</p>
<form action="http://sourceforge.net/tracker/" target="_new">
  <input type="hidden" name="func" value="add" />
  <input type="hidden" name="group_id" value="3870" />
  <input type="hidden" name="atid" value="103870" />
  <input type="submit" value="<?php etranslate( 'Report Bug' )?>" />
</form>
<br /><br />

<h3><?php etranslate( 'System Settings' )?></h3>
<?php
if ( empty ( $SERVER_SOFTWARE ) )
  $SERVER_SOFTWARE = $_SERVER['SERVER_SOFTWARE'];
if ( empty ( $HTTP_USER_AGENT ) )
  $HTTP_USER_AGENT = $_SERVER['HTTP_USER_AGENT'];
if ( empty ( $HTTP_USER_AGENT ) )
  $HTTP_USER_AGENT = $_SERVER['HTTP_USER_AGENT'];
echo '<table border="0"><tr><td>';
printf ( "%-25s:</td><td> %s\n", 'PROGRAM_NAME', $PROGRAM_NAME );
echo $rowStr;
printf ( "%-25s:</td><td> %s\n", 'SERVER_SOFTWARE', $SERVER_SOFTWARE );
echo $rowStr;
printf ( "%-25s:</td><td> %s\n", 'Web Browser', $HTTP_USER_AGENT );
echo $rowStr;
printf ( "%-25s:</td><td> %s\n", 'PHP Version', phpversion() );
echo $rowStr;
printf ( "%-25s:</td><td> %s\n", 'db_type', $db_type );
echo $rowStr;
printf ( "%-25s:</td><td> %s\n", 'readonly', $readonly );
echo $rowStr;
printf ( "%-25s:</td><td> %s\n", 'single_user', $single_user );
echo $rowStr;
printf ( "%-25s:</td><td> %s\n", 'single_user_login', $single_user_login );
echo $rowStr;
printf ( "%-25s:</td><td> %s\n", 'use_http_auth', $use_http_auth ? 'true': 'false');
echo $rowStr;
printf ( "%-25s:</td><td> %s\n", 'user_inc', $user_inc );
echo $rowStr;

$res = dbi_execute ( 'SELECT cal_setting, cal_value FROM webcal_config ORDER BY cal_setting' );
if ( $res ) {
  while ( $row = dbi_fetch_row ( $res ) ) {
    printf ( "%-25s:</td><td> %s\n", $row[0], $row[1] );
    echo $rowStr;
  }
  dbi_free_result ( $res );
}
echo '</td></tr></table>';

echo print_trailer( false, true, true );
?>
</body>
</html>
