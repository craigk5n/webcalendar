<?php

include "includes/config.php";
include "includes/php-dbi.php";
include "includes/functions.php";
include "includes/$user_inc";
include "includes/validate.php";
include "includes/connect.php";

load_global_settings ();
load_user_preferences ();
load_user_layers ();

include "includes/translate.php";

// echo "ret: $ret\n"; exit;

$updating_public = false;
if ( $is_admin && ! empty ( $public ) && $public_access == "Y" ) {
  $updating_public = true;
  $layer_user = "__public__";
  $u_url = "&public=1";
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
    "<P><B>SQL:</B> $sql";
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

?>
<HTML>
<HEAD><TITLE><?php etranslate($application_name)?></TITLE>
<?php include "includes/styles.php"; ?>
</HEAD>
<BODY BGCOLOR="<?php echo $BGCOLOR; ?>" CLASS="defaulttext">

<H2><FONT COLOR="<?php echo $H2COLOR;?>"><?php etranslate("Error")?></FONT></H2>

<?php etranslate("The following error occurred")?>:
<BLOCKQUOTE>
<?php echo $error; ?>
</BLOCKQUOTE>

<?php include "includes/trailer.php"; ?>

</BODY>
</HTML>
