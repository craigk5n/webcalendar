<?php

include "includes/config.php";
include "includes/php-dbi.php";
include "includes/functions.php";
include "includes/$user_inc";
include "includes/validate.php";
include "includes/connect.php";

load_global_settings ();
load_user_preferences ();
$save_status = $LAYERS_STATUS;
$LAYERS_STATUS = "Y";
load_user_layers ();
$LAYERS_STATUS = $save_status;

include "includes/translate.php";

$error = "";

if ( empty ( $dups ) )
  $dups = 'N';

if ( $login == $layeruser )
  $error = translate ("You cannot create a layer for yourself") . ".";

if ( ! empty ( $layeruser ) && $error == "" ) {
  // existing layer entry
  if ( ! empty ( $layers[$id]['cal_layeruser'] ) ) {
    // update existing layer entry for this user
    $layerid = $layers[$id]['cal_layerid'];

    dbi_query ( "UPDATE webcal_user_layers SET cal_layeruser = '$layeruser', cal_color = '$layercolor', cal_dups = '$dups' WHERE cal_layerid = '$layerid'");

  } else {
    // new layer entry
    // check for existing layer for user.  can only have one layer per user
    $res = dbi_query ( "SELECT COUNT(cal_layerid) FROM webcal_user_layers " .
      "WHERE cal_login = '$login' AND cal_layeruser = '$layeruser'" );
    if ( $res ) {
      $row = dbi_fetch_row ( $res );
      if ( $row[0] > 0 ) {
        $error = translate ("You can only create one layer for each user") . ".";
      }
      dbi_free_result ( $res );
    }
    if ( $error == "" ) {
      $res = dbi_query ( "SELECT MAX(cal_layerid) FROM webcal_user_layers" );
      if ( $res ) {
        $row = dbi_fetch_row ( $res );
        $layerid = $row[0] + 1;
      } else {
        $layerid = 1;
      }
      dbi_query ( "INSERT INTO webcal_user_layers ( ".
        "cal_layerid, cal_login, cal_layeruser, cal_color, cal_dups ) " .
	"VALUES ('$layerid', '$login', '$layeruser', " .
	"'$layercolor', '$dups')");
    }
  }
}

if ( $error == "" ) {
  do_redirect ( "layers.php" );
  exit;
}

?>
<HTML>
<HEAD><TITLE><?php etranslate($application_name)?></TITLE>
<?php include "includes/styles.php"; ?>
</HEAD>
<BODY BGCOLOR="<?php echo $BGCOLOR; ?>" CLASS="defaulttext">

<H2><FONT COLOR="<?php echo $H2COLOR;?>"><?php etranslate("Error")?></H2></FONT>
<BLOCKQUOTE>
<?php echo $error; ?>
</BLOCKQUOTE>

<?php include "includes/trailer.php"; ?>

</BODY>
</HTML>
