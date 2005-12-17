<?php
include_once 'includes/init.php';

$error = "";
$my_theme = '';

$updating_public = false;;
if ( $is_admin && ! empty ( $public ) && $PUBLIC_ACCESS == "Y" ) {
  $updating_public = true;
  $prefuser = "__public__";
} elseif (($user != $login) && ($is_admin || $is_nonuser_admin)) {
  $prefuser = "$user";
} else {
  $prefuser = "$login";
}

save_pref ( $HTTP_POST_VARS, 'post' );

if ( ! empty ( $my_theme ) ) {
  $theme = "themes/". $my_theme . "_pref.php";
  include_once $theme;
  save_pref ( $webcal_theme, 'theme' );  
}

function save_pref( $prefs, $src) {
  global $my_theme, $prefuser;
  while ( list ( $key, $value ) = each ( $prefs ) ) {
    if ( $src == 'post' ) {
      $setting = substr ( $key, 5 );
      $prefix = substr ( $key, 0, 5 );
      if ( $key == 'user' || $key == 'public' )
        continue;
      // validate key name.  should start with "pref_" and not include
      // any unusual characters that might cause SQL injection
      if ( ! preg_match ( '/pref_[A-Za-z0-9_]+$/', $key ) ) {
        die_miserable_death ( 'Invalid admin setting name "' .
        $key . '"' );
      }
    } else {
      $setting = $key;
      $prefix = 'pref_';    
    }
    //echo "Setting = $setting, key = $key, prefix = $prefix <br />\n";
    if ( strlen ( $setting ) > 0 && $prefix == "pref_" ) {
      if ( $setting == "THEME" &&  $value != 'none' )
        $my_theme = strtolower ( $value );
      $sql =
        "DELETE FROM webcal_user_pref WHERE cal_login = '$prefuser' " .
        "AND cal_setting = '$setting'";
      dbi_query ( $sql );
      if ( strlen ( $value ) > 0 ) {
      $setting = strtoupper ( $setting );
        $sql = "INSERT INTO webcal_user_pref " .
          "( cal_login, cal_setting, cal_value ) VALUES " .
          "( '$prefuser', '$setting', '$value' )";
        if ( ! dbi_query ( $sql ) ) {
          $error = "Unable to update preference: " . dbi_error () .
   "<br /><br /><span style=\"font-weight:bold;\">SQL:</span> $sql";
          break;
        }
      }
    }
  }
}

if ( empty ( $error ) ) {
  if ( $updating_public ) {
    do_redirect ( "pref.php?public=1" );
  } elseif (($is_admin || $is_nonuser_admin) && $login != $user ) {
    do_redirect ( "pref.php?user=$user" );
  } else {
    do_redirect ( "pref.php" );
  }
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
