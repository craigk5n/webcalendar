<?php
// If the JavaScript doesn't need any input from PHP,
// this file should not be called.
define( '_ISVALID', true );

if( empty( $inc ) )
  $inc = $_GET['inc'];

if( empty( $inc ) && ! empty( $_REQUEST['inc'] ) )
  $inc = $_REQUEST['inc'];

$arinc = explode( '/', $inc );

// We only allow includes if they exist in our js or HTMLarea directories.
if( $arinc[0] != 'js' && $arinc[0] != 'htmlarea' )
  return false;

$base = ( is_dir( 'includes' ) ? 'includes' : '../includes' );

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

// Resolve the requested asset safely: only a regular file inside
// includes/js or includes/htmlarea may be included (see js_cacher_safe_path).
$newinc = js_cacher_safe_path( $inc, $base );
if( $newinc !== false )
  include_once $newinc;

?>
