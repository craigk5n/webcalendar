<?php
include_once 'includes/init.php';
load_user_layers ();

// echo "ret: $ret\n"; exit;

$updating_public = false;
if ( $is_admin && ! empty ( $public ) && $public_access == "Y" ) {
  $updating_public = true;
  $layer_user = "__public__";
  $u_url = "&amp;public=1";
} else {
  $layer_user = $login;
  $u_url = "";
}

$sql = "DELETE FROM webcal_user_pref WHERE cal_login = '$layer_user' " .
  "AND cal_setting = 'LAYERS_STATUS'";
dbi_query ( $sql );

$value = ( $status == "off" ? "N" : "Y" );

$sql = "INSERT INTO webcal_user_pref " .
  "( cal_login, cal_setting, cal_value ) VALUES " .
  "( '$layer_user', 'LAYERS_STATUS', '$value' )";
if ( ! dbi_query ( $sql ) ) {
  $error = "Unable to update preference: " . dbi_error () .
    "<br /><br /><span style=\"font-weight:bold;\">SQL:</span> $sql";
  break;
}

if ( empty ( $error ) ) {
  // Go back to where we where if we can figure it out.
  if ( strlen ( $ret ) )
    do_redirect ( $ret );
  else if ( strlen ( $HTTP_REFERER ) )
    do_redirect ( $HTTP_REFERER );
  else if ( strlen ( get_last_view() )  )
    do_redirect ( get_last_view() );
  else
    do_redirect ( "$STARTVIEW.php" );
}

print_header();
?>

<h2><?php etranslate("Error")?></h2>

<?php etranslate("The following error occurred")?>:
<blockquote>
<?php echo $error; ?>
</blockquote>

<?php print_trailer(); ?>

</body>
</html>
