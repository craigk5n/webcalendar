<?php
/* $Id$ */
include_once 'includes/init.php';
load_user_layers ();


$status = getValue ( 'status', '(on|off)', true );


if ( $allow_view_other != 'Y' ) {
  print_header ();
  etranslate("You are not authorized");
  print_trailer ();
  exit;
}

$updating_public = false;
if ( $is_admin && ! empty ( $public ) && $public_access == "Y" ) {
  $updating_public = true;
  $layer_user = "__public__";
  $url = 'layers.php?public=1';
} else {
  $layer_user = $login;
  $url = 'layers.php';
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
  do_redirect ( $url );
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
