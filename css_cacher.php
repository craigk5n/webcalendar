<?php // $Id$
define( '_ISVALID', true );

foreach( array(
    'access',
    'config',
    'dbi4php',
    'formvars',
    'functions',
    'translate',
    'validate',
  ) as $i ) {
  include_once 'includes/' . $i . '.php';
}
do_config( 'includes/settings.php' );

include_once 'includes/' . $user_inc;
include_once 'includes/gradient.php';

load_global_settings();
@session_start();

// If calling script uses 'guest', we must also.
$GLOBALS['user'] = ! empty( $_GET['login'] )
  ? $_GET['login']
  : ( ! empty( $_REQUEST['login'] )
    ? $_REQUEST['login']
    : ( ( ! empty( $_SESSION['webcal_tmp_login'] ) )
      ? $_SESSION['webcal_tmp_login']
      : ( empty( $_SESSION['webcal_login'] )
        ? '__public__'
        : $_SESSION['webcal_login'] ) ) );

load_user_preferences( $GLOBALS['user'] );

unset( $_SESSION['webcal_tmp_login'] );

// IE can handle compressed CSS OK.
ob_start();

header( 'Content-type: text/css' );
header( 'Cache-Control: Public' );
header( 'Pragma: Public' );

send_no_cache_header();

include_once 'includes/css/styles.php';

ob_end_flush();

?>
