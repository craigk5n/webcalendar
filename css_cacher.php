<?php // $Id: css_cacher.php,v 1.26 2010/08/22 21:03:25 cknudsen Exp $
define( '_ISVALID', true );

include 'includes/translate.php';
include 'includes/config.php';
include 'includes/dbi4php.php';
include 'includes/formvars.php';
include 'includes/functions.php';

do_config( 'includes/settings.php' );
include 'includes/' . $user_inc;
include_once 'includes/access.php';
include_once 'includes/validate.php';
include_once 'includes/gradient.php';

load_global_settings();
@session_start();
$empTmp = ( ! empty( $_SESSION['webcal_tmp_login'] ) );

// If calling script uses 'guest', we must also.
$GLOBALS['user'] = ! empty( $_GET['login'] )
  ? $_GET['login']
  : ( ! empty( $_REQUEST['login'] )
    ? $_REQUEST['login']
    : ( $empTmp
      ? $_SESSION['webcal_tmp_login']
      : ( empty( $_SESSION['webcal_login'] )
        ? '__public__'
        : $_SESSION['webcal_login'] ) ) );
load_user_preferences( $GLOBALS['user'] );

unset( $_SESSION['webcal_tmp_login'] );

// If we are calling from admin or pref, expire CSS yesterday.
// Otherwise, expire tomorrow.
$expTime = time() + 86400;

if( $empTmp )
  $expTime = time() - 86400;

header( 'Content-type: text/css' );
header( 'Cache-Control: Public' );
header( 'Pragma: Public' );

send_no_cache_header();

include_once 'includes/css/styles.php';

?>
