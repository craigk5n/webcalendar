<?php
/* $Id$
 *
 * Description
 * This is the handler for INSTALLER Ajax httpXmlRequests.
 */
define ( '_WC_BASE_DIR', '..' );
define ( '_WC_INCLUDE_DIR', _WC_BASE_DIR . '/includes/' );
define ( '_WC_RUN_MODE', 'prod' );

include_once _WC_INCLUDE_DIR . 'translate.php';
include_once _WC_INCLUDE_DIR . 'dbi4php.php';
include_once _WC_INCLUDE_DIR . 'config.php';
include_once _WC_INCLUDE_DIR . 'formvars.php';
include_once './install_functions.php';

$file = _WC_INCLUDE_DIR . 'settings.php';

reset_language ( 'none' );


$filename = getPostValue ( 'filename', false );
$page = getPostValue ( 'page', false );

// We're processing install/index.php
if ( $page == 'initPHP' || $filename == 'install' ) {
  $ret = 'errSingleUser = "' . translate ( 'Error you must specify a\\nSingle-User Login', true ) . '";
    errIMAP = "' . translate ( 'Error you must specify an\\nIMAP Server', true ) . '";
    errAppPath = "' . translate ( 'Error you must specify an\\nApplication Path', true ) . '";
    errServerURL = "' . translate ( 'Server URL is required', true ) . '";
    errServerSlash = "' . translate ( 'Server URL must end with &quot;/&quot;', true ) . '";
    errERROR = "' . translate ( 'Error', true ) . '";
    dbName = "' . translate ( 'Database Name' ) . '";
    fullPath = "' . translate ( 'Full Path (no backslashes)') . '";
    errIlegal = "' . translate ( 'The password contains illegal characters.', true ) . '";
    ';
	echo $ret;
}
?>
