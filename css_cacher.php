<?php // $Id$
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
load_user_preferences( ! empty( $_GET['login'] )
  ? $_GET['login']
  : ( ! empty( $_REQUEST['login'] )
    ? $_REQUEST['login']
    : ( $empTmp
      ? $_SESSION['webcal_tmp_login']
      : ( empty( $_SESSION['webcal_login'] )
        ? '__public__'
        : $_SESSION['webcal_login'] ) ) ) );

unset( $_SESSION['webcal_tmp_login'] );

// If we are calling from admin or pref, expire CSS yesterday.
// Otherwise, expire tomorrow.
$expTime = gmmktime() + 86400;
if ( $empTmp )
  $expTime = gmmktime() - 86400;

$fmt = 'D, d M Y H:i:s \G\M\T'
ob_start( ini_get( 'zlib.output_compression' ) != 1 ? 'ob_gzhandler' : '' );

header( 'Content-type: text/css' );
header( 'Last-Modified: ' . gmdate( $fmt, $expTime - 600 ) );
header( 'Expires: ' . gmdate( $fmt, $expTime ) );
header( 'Cache-Control: Public' );
header( 'Pragma: Public' );

include_once 'includes/styles.php';

ob_end_flush();

?>
