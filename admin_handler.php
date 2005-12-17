<?php
include_once 'includes/init.php';

$error = "";
$my_theme = '';

if ( ! $is_admin ) {
  $error = translate("You are not authorized");
}

if ( $error == "" ) {
  save_pref ( $HTTP_POST_VARS, 'post' );
}

if ( ! empty ( $my_theme ) ) {
  $theme = "themes/". strtolower ( $my_theme ). ".php";
  include_once $theme;
  save_pref ( $webcal_theme, 'theme' );  
}

function save_pref( $prefs, $src) {
  global $my_theme;
  while ( list ( $key, $value ) = each ( $prefs ) ) {
    if ( $src == 'post' ) {
      $setting = substr ( $key, 6 );
      $prefix = substr ( $key, 0, 6 );
      // validate key name.  should start with "admin_" and not include
      // any unusual characters that might cause SQL injection
      if ( ! preg_match ( '/admin_[A-Za-z0-9_]+$/', $key ) ) {
        die_miserable_death ( 'Invalid admin setting name "' .
          $key . '"' );
      }
    } else {
      $setting = $key;
      $prefix = 'admin_';    
    }  
    if ( strlen ( $setting ) > 0 && $prefix == "admin_" ) {
      if ( $setting == "THEME" &&  $value != 'none' )
        $my_theme = strtolower ( $value );
      $setting = strtoupper ( $setting );
      $sql = "DELETE FROM webcal_config WHERE cal_setting = '$setting'";
      if ( ! dbi_query ( $sql ) ) {
        $error = translate("Error") . ": " . dbi_error () .
          "<br /><br /><span style=\"font-weight:bold;\">SQL:</span> $sql";
        break;
      }
      if ( strlen ( $value ) > 0 ) {
        $sql = "INSERT INTO webcal_config " .
          "( cal_setting, cal_value ) VALUES " .
          "( '$setting', '$value' )";
        if ( ! dbi_query ( $sql ) ) {
          $error = translate("Error") . ": " . dbi_error () .
            "<br /><br /><span style=\"font-weight:bold;\">SQL:</span> $sql";
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

<h2><?php etranslate("Error")?></h2>

<?php etranslate("The following error occurred")?>:
<blockquote>
<?php echo $error; ?>
</blockquote>

<?php print_trailer(); ?>

</body>
</html>
