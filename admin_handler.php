<?php
include_once 'includes/init.php';

$error = "";

if ( ! $is_admin ) {
  $error = translate("You are not authorized");
}

if ( $error == "" ) {
  while ( list ( $key, $value ) = each ( $HTTP_POST_VARS ) ) {
    $setting = substr ( $key, 6 );
    if ( strlen ( $setting ) > 0 ) {
      $sql = "DELETE FROM webcal_config WHERE cal_setting = '$setting'";
      if ( ! dbi_query ( $sql ) ) {
        $error = translate("Error") . ": " . dbi_error () .
          "<br /><br /><b>SQL:</b> $sql";
        break;
      }
      if ( strlen ( $value ) > 0 ) {
        $sql = "INSERT INTO webcal_config " .
          "( cal_setting, cal_value ) VALUES " .
          "( '$setting', '$value' )";
        if ( ! dbi_query ( $sql ) ) {
          $error = translate("Error") . ": " . dbi_error () .
            "<br /><br /><b>SQL:</b> $sql";
          break;
        }
      }
    }
  }
}

if ( empty ( $error ) ) {
  if ( empty ( $ovrd ) )
    do_redirect ( "admin.php" );
  else
    do_redirect ( "admin.php?ovrd=$ovrd" );
}

print_header();
?>

<h2><font color="<?php echo $H2COLOR;?>"><?php etranslate("Error")?></font></h2>

<?php etranslate("The following error occurred")?>:
<blockquote>
<?php echo $error; ?>
</blockquote>

<?php print_trailer(); ?>

</body>
</html>
