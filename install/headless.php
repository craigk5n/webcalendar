<?php
/* This script can be used to update the database headlessly rather than using the 
 * installation script.
 * 
 * You must copy the settings.php file from your original installation, or create it
 * yourself in the case of a new install. This script will not prompt you for any of
 * your settings; and requires settings.php to be present and complete.
 */

if (php_sapi_name() !== 'cli'){
    echo 'This is a CLI script and should not be invoked via the web server';
    exit;
}

include_once __DIR__.'/../includes/translate.php';
include_once __DIR__.'/../includes/dbi4php.php';
include_once __DIR__.'/../includes/config.php';
include_once __DIR__.'/default_config.php';
include_once __DIR__.'/install_functions.php';
include_once __DIR__.'/sql/upgrade_matrix.php';

define( '__WC_BASEDIR', __DIR__.'/../' );
$fileDir = __WC_BASEDIR . 'includes';
$file    = $fileDir . '/settings.php';
chdir(__WC_BASEDIR);

// We need the $_SESSION superglobal to pass data to and from some of the update
// functions. Sessions are basically useless in CLI mode, but technically the 
// session functions *do* work.
session_start();


// Load the settings.php file
$fd = @fopen( $file, 'rb', false );
if( ! empty( $fd ) ) {
  while( ! feof( $fd ) ) {
    $buffer = trim( fgets( $fd, 4096 ) );

    if( preg_match( '/^#|\/\*/', $buffer ) // comments
        || preg_match( '/^<\?/', $buffer ) // start php code
        || preg_match( '/^\?>/', $buffer ) // end php code
      ) {
        continue;
    }
    if( preg_match( '/(\S+):\s*(.*)/', $buffer, $matches ) )
      $settings[$matches[1]] = $matches[2];
  }
  fclose( $fd );
}

// We'll grab database settings from settings.php.
$db_database = $settings['db_database'];
$db_host     = $settings['db_host'];
$db_login    = $settings['db_login'];
$db_password = ( empty( $settings['db_password'] )
    ? '' : $settings['db_password'] );
$db_persistent = false;
$db_type       = $settings['db_type'];
$real_db       = ( $db_type== 'sqlite' || $db_type == 'sqlite3'
    ? get_full_include_path( $db_database ) : $db_database );


$c = dbi_connect( $db_host, $db_login, $db_password, $real_db, false );
// It's possible that the tables were created manually
// and we just want to do the database population routines.
    get_installed_version();
if( $c && ! empty( $_SESSION['install_file'] ) ) {
    $sess_install = $_SESSION['install_file'];
    $install_filename = ( $sess_install == 'tables' ? 'tables-' : 'upgrade-' );
    switch( $db_type ) {
    case 'ibase':
    case 'mssql':
    case 'oracle':
        $install_filename .= $db_type . '.sql';
        break;
    case 'ibm_db2':
        $install_filename .= 'db2.sql';
        break;
    case 'odbc':
        $install_filename .= $_SESSION['odbc_db'] . '.sql';
        break;
    case 'postgresql':
        $install_filename .= 'postgres.sql';
        break;
    case 'sqlite':
        include_once 'sql/tables-sqlite.php';
        populate_sqlite_db( $real_db, $c );
        $install_filename = '';
        break;
    case 'sqlite3':
        include_once 'sql/tables-sqlite3.php';
        populate_sqlite_db( $real_db, $c );
        $install_filename = '';
        break;
    default:
        $install_filename .= 'mysql.sql';
    }
    db_populate( $install_filename, $display_sql );
}

// Convert passwords to secure hashes if needed.
$res = dbi_execute( 'SELECT cal_login, cal_passwd FROM webcal_user',
array(), false, $show_all_errors );
if( $res ) {
while( $row = dbi_fetch_row( $res ) ) {
    if( strlen( $row[1] ) < 30 )
    dbi_execute( 'UPDATE webcal_user SET cal_passwd = ?
        WHERE cal_login = ?', array( password_hash( $row[1], PASSWORD_DEFAULT ), $row[0] ) );
}
dbi_free_result( $res );
}

// If new install, run 0 GMT offset
// just to set webcal_config.WEBCAL_TZ_CONVERSION.
if( $_SESSION['old_program_version'] == 'new_install' )
convert_server_to_GMT();

// For upgrade to v1.1b
// we need to convert existing categories and repeating events.
do_v11b_updates();

// v1.1e requires converting webcal_site_extras to webcal_reminders.
do_v11e_updates();

// Update the version info.
get_installed_version( true );