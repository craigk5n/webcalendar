<?php php_track_vars?>
<?php

include "includes/config.inc";
include "includes/php-dbi.inc";
include "includes/functions.inc";
include "includes/validate.inc";
include "includes/connect.inc";

load_user_preferences ();
load_user_layers ();

include "includes/translate.inc";

$error = "";

while ( list ( $key, $value ) = each ( $HTTP_POST_VARS ) ) {
  $setting = substr ( $key, 5 );
  $sql =
    "DELETE FROM webcal_user_pref WHERE cal_login = '$login' " .
    "AND cal_setting = '$setting'";
  dbi_query ( $sql );
  if ( strlen ( $value ) > 0 ) {
    $sql = "INSERT INTO webcal_user_pref " .
      "( cal_login, cal_setting, cal_value ) VALUES " .
      "( '$login', '$setting', '$value' )";
    if ( ! dbi_query ( $sql ) ) {
      $error = "Unable to update preference: " . dbi_error () .
        "<P><B>SQL:</B> $sql";
      break;
    }
  }
}

if ( strlen ( $error ) == 0 ) {
  do_redirect ( "$STARTVIEW.php" );
}

?>
<HTML>
<HEAD><TITLE><?php etranslate("Title")?></TITLE>
<?php include "includes/styles.inc"; ?>
</HEAD>
<BODY BGCOLOR="<?php echo $BGCOLOR; ?>">

<H2><FONT COLOR="<?php echo $H2COLOR;?>"><?php etranslate("Error")?></FONT></H2>

<?php etranslate("The following error occurred")?>:
<BLOCKQUOTE>
<?php echo $error; ?>
</BLOCKQUOTE>

<?php include "includes/trailer.inc"; ?>

</BODY>
</HTML>
