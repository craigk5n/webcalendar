<?php
	include_once 'includes/init.php';
	print_header('','','',true);
?>

<h2><?php etranslate("Report Bug")?></h2>

<?php 
	//No need to translate the text below since I want all bugs
	//reported in English.
	//Americans only speak English, of course ;-)
?>
Please include all the information below when reporting a bug.
<?php if ( $LANGUAGE != "English-US" ) { ?>
	Also.. when reporting a bug, please use <strong>English</strong> rather than <?php echo $LANGUAGE?>.
<?php } ?>

<form action="http://sourceforge.net/tracker/" target="_new">
	<input type="hidden" name="func" value="add" />
	<input type="hidden" name="group_id" value="3870" />
	<input type="hidden" name="atid" value="103870" />
	<input type="submit" value="<?php etranslate("Report Bug")?>" />
</form>
<br /><br />

<h3><?php etranslate("System Settings")?></h3>
<?php
if ( empty ( $SERVER_SOFTWARE ) )
  $SERVER_SOFTWARE = $_SERVER["SERVER_SOFTWARE"];
if ( empty ( $HTTP_USER_AGENT ) )
  $HTTP_USER_AGENT = $_SERVER["HTTP_USER_AGENT"];
if ( empty ( $HTTP_USER_AGENT ) )
  $HTTP_USER_AGENT = $_SERVER["HTTP_USER_AGENT"];

echo "<pre>";
printf ( "%-25s: %s\n", "PROGRAM_NAME", $PROGRAM_NAME );
printf ( "%-25s: %s\n", "SERVER_SOFTWARE", $SERVER_SOFTWARE );
printf ( "%-25s: %s\n", "Web Browser", $HTTP_USER_AGENT );
printf ( "%-25s: %s\n", "db_type", $db_type );
printf ( "%-25s: %s\n", "readonly", $readonly );
printf ( "%-25s: %s\n", "single_user", $single_user );
printf ( "%-25s: %s\n", "single_user_login", $single_user_login );
printf ( "%-25s: %s\n", "use_http_auth", $use_http_auth ? "true" : "false" );
printf ( "%-25s: %s\n", "user_inc", $user_inc );

$res = dbi_query ( "SELECT cal_setting, cal_value FROM webcal_config" );
if ( $res ) {
  while ( $row = dbi_fetch_row ( $res ) ) {
    printf ( "%-25s: %s\n", $row[0], $row[1] );
  }
  dbi_free_result ( $res );
}
echo "</pre>\n";

include_once "includes/help_trailer.php";
?>
</body>
</html>