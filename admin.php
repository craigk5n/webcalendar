<?php
/* $Id$ */
include_once 'includes/init.php';
include_once 'includes/date_formats.php';
if ( file_exists ( 'install/default_config.php' ) )
  include_once 'install/default_config.php';

 
function save_pref( $prefs, $src) {
  global $my_theme;
  while ( list ( $key, $value ) = each ( $prefs ) ) {
    if ( $src == 'post' ) {
      $setting = substr ( $key, 6 );
      $prefix = substr ( $key, 0, 6 );
      if ( $key == 'currenttab')
        continue;
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
    if ( strlen ( $setting ) > 0 && $prefix == 'admin_' ) {
      if ( $setting == 'THEME' &&  $value != 'none' )
        $my_theme = strtolower ( $value );
      $setting = strtoupper ( $setting );
      $sql = 'DELETE FROM webcal_config WHERE cal_setting = ?';
      if ( ! dbi_execute ( $sql, array( $setting ) ) ) {
        $error = db_error ( false, $sql );
        break;
      }
      if ( strlen ( $value ) > 0 ) {
        $sql = 'INSERT INTO webcal_config ' .
          '( cal_setting, cal_value ) VALUES ( ?, ? )';
        if ( ! dbi_execute ( $sql, array( $setting, $value ) ) ) {
          $error = db_error ( false, $sql );
          break;
        }
      }
    }
  }
	generate_CSS ( true ); 
}

$error = '';
$currenttab = 'settings';

if ( ! $WC->isAdmin() ) {
  $error = print_not_auth ();
}

if ( ! empty ( $_POST ) && empty ( $error )) {
  $my_theme = '';
  $currenttab = $WC->getPOST ( 'currenttab' );    
  if ( $error == '' ) {
    save_pref ( $_POST, 'post' );
  }
  
  if ( ! empty ( $my_theme ) ) {
    $theme = 'themes/'. strtolower ( $my_theme ). '.php';
    include_once $theme;
    save_pref ( $webcal_theme, 'theme' );  
  }
}  

//load any new config settings. Existing ones will not be affected
//this function is in the install/default_config.php file
if ( function_exists ( 'db_load_config' ) && empty ( $_POST )  )
  db_load_config ();


$smarty->LoadVars( '', false );

//get list of theme files from /themes directory
$themes = array();
$dir = 'themes';
if ( @is_dir($dir) ) {
   if ($dh = opendir($dir)) {
       while (($file = readdir($dh)) !== false) {
         if ( strpos ( $file, '_admin.php' ) ) {
           $themes[0][] = strtoupper( str_replace ( '_admin.php', '', $file ) );
           $themes[1][] = strtoupper( str_replace ( '.php', '', $file ) );
        } else if ( strpos ( $file, '_pref.php' ) ) {
           $themes[0][] = strtolower( str_replace ( '_pref.php', '', $file ) );
           $themes[1][] = strtolower( str_replace ( '.php', '', $file ) );
        }
       }
       sort ( $themes );
       closedir($dh);
   }
}

//allow css_cache to webcal_config values
@session_start (); 
$_SESSION['webcal_tmp_login'] = 'blahblahblah';

$openStr ="window.open('edit_template.php?type=%s','cal_template','dependent,menubar,scrollbars,height=500,width=500,outerHeight=520,outerWidth=520');";

$smarty->assign ( 'choices', array ( 'day.php'=>translate ( 'Day' ),
  'week.php'=>translate ( 'Week' ),
  'month.php'=>translate ( 'Month' ),
  'year.php'=>translate ( 'Year' ) ) );

$smarty->assign ( 'views', loadViews ( '', true ) );

$smarty->assign ( 'tabs_ar', array (
    'settings' => translate ( 'Settings' ),
    'groups' => translate ( 'Groups' ),
    'nonuser' => translate ( 'NonUser Calendars' ),
    'other' => translate ( 'Other' ),
    'email' => translate ( 'Email' ),
    'colors' => translate ( 'Colors' ) ) );

if ( function_exists ( 'imagepng' ) || function_exists ( 'imagegif' )) 
	$smarty->assign ( 'enable_gradients', true );
		
//determine if allow_url_fopen is enabled
$allow_url_fopen = preg_match ( "/(On|1|true|yes)/i", ini_get ( 'allow_url_fopen' ) );

$BodyX = 'onload="init_admin();showTab(\''. $currenttab . '\');"';
$INC = array('admin.js','visible.js');
build_header ( $INC, '', $BodyX );
if ( ! $error ) {
  $smarty->assign ( 'icons_dir_notice', ! @is_dir ( 'icons/' ) );
	$smarty->assign ( 'languages', define_languages () );
	$smarty->assign ( 'themes', $themes );
	$smarty->assign ( 'can_set_timezone', 
	  set_env ( 'TZ', getPref ('SERVER_TIMEZONE',2 ) ) );
  $smarty->assign ( 'selected', SELECTED );
  $smarty->assign ( 'currenttab', $currenttab );
	$smarty->assign ( 'openS', sprintf ( $openStr, 'S' ) );
	$smarty->assign ( 'openH', sprintf ( $openStr, 'H' ) );
	$smarty->assign ( 'openT', sprintf ( $openStr, 'T' ) );
	$smarty->assign ( 'time_format_array', 
	  array ( '12'=>translate( '12 hour' ), '24'=>translate( '24 hour' ) ) );
	$smarty->assign ( 'timed_evt_len_array', 
	  array ('D'=>translate( 'Duration' ), 'E'=>translate( 'End Time' ) ) );
	$smarty->assign ( 'top_bottom_array', 
	  array ( 'Y'=>translate ( 'Top' ), 'N'=>translate ( 'Bottom' ) ) );
  $smarty->display ( 'admin.tpl' );

} else {// if $error 
  echo print_error ( $error, true );  
}


