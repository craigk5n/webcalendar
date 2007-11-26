<?php
/* $Id$ */
include_once 'includes/init.php';
include_once 'includes/date_formats.php';
include_once 'install/default_config.php';

function save_pref ( $prefs, $src ) {
  global $my_theme, $webcalConfig;

  // We now use checkboxes instead of radio controls. If not checked, still
  // need to store 'N' in the database. We loop through $webcalConfig and look
  // for Y/N settings and if missing from $prefs, we insert an 'N' value.
  if ( $src == 'post' ) {
    while ( list ( $key, $value ) = each ( $webcalConfig ) ) {
      if ( ( $value == 'Y' || $value == 'N' ) &&
          empty ( $prefs['admin_' . $key] ) )
        $prefs['admin_' . $key] = 'N';
    }
  }
  // do_debug ( print_r ( $prefs,true ) );
  while ( list ( $key, $value ) = each ( $prefs ) ) {
    if ( $src == 'post' ) {
      $prefix = substr ( $key, 0, 6 );
      $setting = substr ( $key, 6 );
      if ( $key == 'currenttab' )
        continue;

      // Validate key name. Should start with "admin_" and not include
      // any unusual characters that might be an SQL injection attack.
      if ( ! preg_match ( '/admin_[A-Za-z0-9_]+$/', $key ) )
        die_miserable_death ( 'Invalid admin setting name "' . $key . '"' );
    } else {
      $prefix = 'admin_';
      $setting = $key;
    }
    if ( strlen ( $setting ) > 0 && $prefix == 'admin_' ) {
      if ( $setting == 'THEME' && $value != 'none' )
        $my_theme = strtolower ( $value );

      $setting = strtoupper ( $setting );
      if ( strlen ( $value ) > 0 ) {
        $sql = 'UPDATE webcal_config SET cal_value = ?
          WHERE cal_setting = \'' . $setting . '\'';
        if ( ! dbi_execute ( $sql, array ( $value ) ) ) {
          $error = db_error ( false, $sql );
          break;
        }
      }
    }
  }
  generate_CSS ( true );
}

$currenttab = 'settings';
$error = '';

if ( ! $WC->isAdmin() )
  $error = print_not_auth();

if ( ! empty ( $_POST ) && empty ( $error ) ) {
  $currenttab = $WC->getPOST ( 'currenttab' );
  $my_theme = '';
  if ( $error == '' )
    save_pref ( $_POST, 'post' );

  if ( ! empty ( $my_theme ) ) {
    $theme = 'themes/' . strtolower ( $my_theme ) . '.php';
    include_once $theme;
    save_pref ( $webcal_theme, 'theme' );
  }
}

// Load any new config settings. Existing ones will not be affected.
// This function is in the install/default_config.php file.
if ( function_exists ( 'db_load_config' ) && empty ( $_POST ) )
  db_load_config();

$smarty->LoadVars ( '', false );

// Get list of theme files from /themes directory.
$dir = 'themes';
$themes = array();
if ( @is_dir ( $dir ) ) {
  if ( $dh = opendir ( $dir ) ) {
    while ( ( $file = readdir ( $dh ) ) !== false ) {
      $k = str_replace ( '.php', '', $file );
      if ( strpos ( $file, '_admin.php' ) )
        $themes[$k] = strtoupper ( str_replace ( '_admin.php', '', $file ) );
      elseif ( strpos ( $file, '_pref.php' ) )
        $themes[$k] = strtolower ( str_replace ( '_pref.php', '', $file ) );
    }
    asort ( $themes );
    closedir ( $dh );
  }
}

$openStr = "window.open( 'edit_template.php?type=%s','cal_template','dependent,"
 . 'menubar,scrollbars,height=500,width=500,outerHeight=520,outerWidth=520\' );';

$smarty->assign ( 'choices', array ( 'day.php' => translate ( 'Day' ),
    'week.php' => translate ( 'Week' ),
    'month.php' => translate ( 'Month' ),
    'year.php' => translate ( 'Year' ) ) );
$smarty->assign ( 'tabs_ar', array ( 'settings' => translate ( 'Settings' ),
    'events' => translate ( 'Events' ),
    'groups' => translate ( 'Groups' ),
    'users' => translate ( 'User Settings' ),
    'other' => translate ( 'Other' ),
    'email' => translate ( 'Email' ),
    'colors' => translate ( 'Colors' ) ) );
$smarty->assign ( 'views', loadViews ( '', '', true ) );

if ( function_exists ( 'imagepng' ) || function_exists ( 'imagegif' ) )
  $smarty->assign ( 'enable_gradients', true );

build_header ( array( 'admin.js' ), '',
  'onload="init_admin();showTab( \'' . $currenttab . '\' );"' );
if ( ! $error ) {
  $smarty->assign ( 'allow_url_fopen',
    ( preg_match ( '/(On|1|true|yes)/i', ini_get ( 'allow_url_fopen' ) ) ) );
  $smarty->assign ( 'can_set_timezone',
    set_env ( 'TZ', getPref ( '_SERVER_TIMEZONE', 2 ) ) );
  $smarty->assign ( 'currenttab', $currenttab );
  $smarty->assign ( 'icons_dir_notice', ! @is_dir ( 'icons/' ) );
  $smarty->assign ( 'languages', define_languages() );
  $smarty->assign ( 'openH', sprintf ( $openStr, 'H' ) );
  $smarty->assign ( 'openS', sprintf ( $openStr, 'S' ) );
  $smarty->assign ( 'openT', sprintf ( $openStr, 'T' ) );
  $smarty->assign ( 'themes', $themes );
  $smarty->assign ( 'time_format_array',
    array ( '12' => translate ( '12 hour' ), '24' => translate ( '24 hour' ) ) );
  $smarty->assign ( 'timed_evt_len_array',
    array ( 'D' => translate ( 'Duration' ), 'E' => translate ( 'End Time' ) ) );
  $smarty->assign ( 'top_bottom_array',
    array ( 'Y' => translate ( 'Top' ), 'N' => translate ( 'Bottom' ) ) );
  $smarty->assign ( 'userlist', $WC->User->getUsers() );

  $smarty->display ( 'admin.tpl' );
} else // if $error
  echo print_error ( $error, true );

?>
