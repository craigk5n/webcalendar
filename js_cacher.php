<?php
// If the JavaScript doesn't need any input from PHP,
// this file should not be called.
define( '_ISVALID', true );

if( empty( $inc ) )
  $inc = $_GET['inc'];

if( empty( $inc ) && ! empty( $_REQUEST['inc'] ) )
  $inc = $_REQUEST['inc'];

$arinc = explode( '/', $inc );

// Only a file directly inside includes/js may be included. Segments after
// the file name are arguments some of those files read, never path parts.
// "." and ".." are listed by readdir(), so they are excluded explicitly.
if( count( $arinc ) < 2 || $arinc[0] !== 'js'
    || $arinc[1] === '.' || $arinc[1] === '..' )
  return false;

$newinc = is_dir( 'includes' ) ? 'includes/js' : '../includes/js';

if( ! in_array( $arinc[1], scandir( $newinc ), true )
    || ! is_file( "$newinc/$arinc[1]" ) )
  return false;

$newinc .= '/' . $arinc[1];

require_once 'includes/translate.php';
require_once 'includes/config.php';
require_once 'includes/dbi4php.php';
require_once 'includes/formvars.php';
require_once 'includes/functions.php';

do_config();

require_once "includes/$user_inc";
require_once 'includes/access.php';
require_once 'includes/gradient.php';
require_once 'includes/validate.php';

header( 'Content-type: text/javascript' );
header( 'Cache-Control: Public' );
header( 'Pragma: Public' );

send_no_cache_header();
load_global_settings();
session_name(getSessionName());
@session_start();

$login = ( empty( $_SESSION['webcal_login'] )
  ? '__public__' : $_SESSION['webcal_login'] );

load_user_preferences();

include_once $newinc;

?>
