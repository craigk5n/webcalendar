<?php
/**
 * This file loads configuration settings from the data file settings.php and
 * sets up some needed variables.
 *
 * The settings.php file is created during installation using the web-based db
 * setup page (install/index.php).
 *
 * To update the WebCalendar version (in order to make a new release or to
 * mark a db change), see the comments in install/index.php.
 *
 *
 * @author Craig Knudsen <cknudsen@cknudsen.com>
 * @copyright Craig Knudsen, <cknudsen@cknudsen.com>, http://www.k5n.us/cknudsen
 * @license http://www.gnu.org/licenses/gpl.html GNU GPL
 * @version $Id: config.php,v 1.92 2011/07/12 19:45:42 rjones6061 Exp $
 * @package WebCalendar
 */

/**
 * Prints a fatal error message to the user along with a link to the
 * Troubleshooting section of the WebCalendar System Administrator's Guide.
 *
 * Execution is aborted.
 *
 * @param string  $error  The error message to display
 * @param string  $anchor The section in WebCalendar-SysAdmin.html to
 *		display (should be marked with <a name="XXX">
 * @internal We don't normally put functions in this file. But, since this
 *           file is included before some of the others, this function either
 *           goes here or we repeat this code in multiple files.
 *           Additionally, we don't want to call too many external functions
 *           from here since we could end up calling the function that called
 *           this one. Infinite loops === "bad"!
 * NOTE: Don't call translate from here.
 *       This function is often called before translation stuff is initialized!
 */
function die_miserable_death( $error, $anchor='' ) {
  global $APPLICATION_NAME, $LANGUAGE, $login, $TROUBLE_URL;

  // Make sure app name is set.
  $appStr = ( empty( $APPLICATION_NAME ) ? 'WebCalendar' : $APPLICATION_NAME );
  $url = $TROUBLE_URL;
  if ( ! empty ( $anchor ) ) {
    $args = explode ( '#', $TROUBLE_URL );
    $url = $args[0] . '#' . $anchor;
  }


  echo <<<EOT
<html>
  <head><title>{$appStr}: Fatal Error</title></head>
  <body>
    <h2>{$appStr} Error</h2>
    <p>{$error}</p><hr />
    <p><a href="{$url}" target="_blank">Troubleshooting Help</a></p>
  </body>
</html>
EOT;
  exit;
}

function db_error( $doExit = false, $sql = '' ) {
  global $settings;

  $ret = str_replace( 'XXX', dbi_error(), translate( 'Database error XXX.' ) )
   . ( ! empty( $settings['mode'] ) && $settings['mode'] == 'dev'
    && ! empty( $sql ) ? '<br />SQL:<br />' . $sql : '' );

  if( $doExit ) {
    echo $ret;
    exit;
  } else
    return $ret;
}

/**
 * Get the full path to a file located in the webcalendar includes directory.
 */
function get_full_include_path( $filename ) {
  if( preg_match( '/(.*)config.php/', __FILE__, $matches ) ) {
    $fileLoc = $matches[1] . $filename;
    return $fileLoc;
  } else
    // Oops. This file is not named config.php!
    die_miserable_death( 'Crap! Someone renamed config.php' );
}

function do_config( $fileLoc ) {
  global $db_database, $db_host, $db_login, $db_password, $db_persistent,
  $db_type, $ignore_user_case, $NONUSER_PREFIX, $phpdbiVerbose, $PROGRAM_DATE,
  $PROGRAM_NAME, $PROGRAM_URL, $PROGRAM_VERSION, $readonly, $run_mode, $settings,
  $single_user, $single_user_login, $TROUBLE_URL, $user_inc, $use_http_auth;

  // When changing PROGRAM VERSION, also change it in install/default_config.php
  $PROGRAM_VERSION = 'v1.3.0';
  // Update PROGRAM_DATE with official release data
  $PROGRAM_DATE = '(15 Mar 2019)';

  $PROGRAM_NAME = 'WebCalendar ' . "$PROGRAM_VERSION ($PROGRAM_DATE)";
  $PROGRAM_URL = 'http://k5n.us/wp/webcalendar/';
  $TROUBLE_URL = 'docs/WebCalendar-SysAdmin.html#trouble';

  // Open settings file to read.
  $settings = array();

  if( file_exists( $fileLoc ) )
    $fd = @fopen( $fileLoc, 'rb', true );

  if( empty( $fd ) && defined( '__WC_INCLUDEDIR' ) ) {
    $fd = @fopen( __WC_INCLUDEDIR . '/settings.php', 'rb', true );

    if( $fd )
      $fileLoc = __WC_INCLUDEDIR . '/settings.php';
  }
  // If still empty.... use __FILE__.
  if( empty( $fd ) ) {
    $testName = get_full_include_path( 'settings.php' );
    $fd = @fopen( $fileLoc, 'rb', true );

    if( $fd )
      $fileLoc = $testName;
  }
  if( empty( $fd ) || filesize( $fileLoc ) == 0 ) {
    // There is no settings.php file.
    // Redirect user to install page if it exists.
    if( file_exists( 'install/index.php' ) ) {
      header( 'Location: install/index.php' );
      exit;
    } else
      die_miserable_death( translate( 'Could not find settings.php file...' ) );
  }

  // We don't use fgets() since it seems to have problems with Mac-formatted
  // text files. Instead, we read in the entire file, and split the lines manually.
  $data = '';
  while( ! feof( $fd ) ) {
    $data .= fgets( $fd, 4096 );
  }
  fclose( $fd );

  // Replace any combination of carriage return (\r) and new line (\n)
  // with a single new line.
  $data = preg_replace( "/[\r\n]+/", "\n", $data );

  // Split the data into lines.
  $configLines = explode( "\n", $data );

  for( $n = 0, $cnt = count( $configLines ); $n < $cnt; $n++ ) {
    $buffer = trim( $configLines[$n] );

    if( preg_match( '/^#|\/\*/', $buffer ) // comments
        || preg_match( '/^<\?/', $buffer ) // start PHP code
        || preg_match( '/^\?>/', $buffer ) // end PHP code
      ) {
      continue;
    }

    if( preg_match( '/(\S+):\s*(\S+)/', $buffer, $matches ) )
      $settings[$matches[1]] = $matches[2];
  }
  $configLines =
  $data = '';

  // Extract db settings into global vars.
  $db_database = $settings['db_database'];
  $db_host     = $settings['db_host'];
  $db_login    = $settings['db_login'];
  $db_password = ( empty( $settings['db_password'] )
    ? '' : $settings['db_password'] );
  $db_persistent = ( preg_match( '/(1|yes|true|on)/i',
    $settings['db_persistent'] ) ? '1' : '0' );
  $db_type = $settings['db_type'];

  // If no db settings, then user has likely started install but not yet
  // completed. So, send them back to the install script.
  if( empty( $db_type ) ) {
    if( file_exists( 'install/index.php' ) ) {
      header( 'Location: install/index.php' );
      exit;
    } else
      die_miserable_death( translate( 'Incomplete settings.php file...' ) );
  }

  // Use 'db_cachedir' if found, otherwise look for 'cachedir'.
  if( ! empty( $settings['db_cachedir'] ) )
    dbi_init_cache( $settings['db_cachedir'] );
  else
  if( ! empty( $settings['cachedir'] ) )
    dbi_init_cache( $settings['cachedir'] );

  if( ! empty( $settings['db_debug'] )
      && preg_match( '/(1|true|yes|enable|on)/i', $settings['db_debug'] ) )
    dbi_set_debug( true );

  foreach( array( 'db_type', 'db_host', 'db_login' ) as $s ) {
    if( empty( $settings[$s] ) )
      die_miserable_death( str_replace( 'XXX', $s,
          translate( 'Could not find XXX defined in...' ) ) );
  }

  // Allow special settings of 'none' in some settings[] values.
  // This can be used for db servers not using TCP port for connection.
  $db_host = ( $db_host == 'none' ? '' : $db_host );
  $db_password = ( empty( $db_password ) || $db_password == 'none'
    ? '' : $db_password );

  $readonly = preg_match( '/(1|yes|true|on)/i',
    $settings['readonly'] ) ? 'Y' : 'N';

  if( empty( $settings['mode'] ) )
    $settings['mode'] = 'prod';

  $run_mode = ( preg_match( '/(dev)/i', $settings['mode'] ) ? 'dev' : 'prod' );
  $phpdbiVerbose = ( $run_mode == 'dev' );
  $single_user = preg_match( '/(1|yes|true|on)/i',
    $settings['single_user'] ) ? 'Y' : 'N';

  if( $single_user == 'Y' )
    $single_user_login = $settings['single_user_login'];

  if( $single_user == 'Y' && empty( $single_user_login ) )
    die_miserable_death( str_replace( 'XXX', 'single_user_login',
        translate( 'You must define XXX in' ) ) );

  $use_http_auth = ( preg_match( '/(1|yes|true|on)/i',
      $settings['use_http_auth'] ) ? true : false );

  // Type of user authentication.
  $user_inc = $settings['user_inc'];

  // If SQLite, the db file is in the includes directory.
  if( $db_type == 'sqlite' || $db_type == 'sqlite3' ) {
    if ( substr ( $db_database, 0, 1 ) != '/' && ! file_exists ( $db_database ) )
      $db_database = get_full_include_path( $db_database );
  }

  // &amp; does not work here...leave it as &.
  $locateStr = 'Location: install/index.php?action=mismatch&version=';

  // Check the current installation version.
  // Redirect user to install page if it is different from stored value.
  // This will prevent running WebCalendar until UPGRADING.html has been
  // read and required upgrade actions completed.
  $c = @dbi_connect( $db_host, $db_login, $db_password, $db_database, false );

  if( $c ) {
    $rows = dbi_get_cached_rows( 'SELECT cal_value FROM webcal_config
      WHERE cal_setting = \'WEBCAL_PROGRAM_VERSION\'' );

    if( ! $rows ) {
      header( $locateStr . 'UNKNOWN' );
      exit;
    } else {
      $row = $rows[0];

      if( empty( $row ) || $row[0] != $PROGRAM_VERSION ) {
        header( $locateStr . '' . ( empty( $row ) ? 'UNKNOWN' : $row[0] ) );
        exit;
      }
    }
    dbi_close( $c );
  } else {
    // Must mean we don't have a settings.php file.
    header( $locateStr . 'UNKNOWN' );
    exit;
  }

  // We can add extra "nonuser" calendars such as a holiday, corporate,
  // departmental, etc. We need a unique prefix for these calendars
  // so we don't get them mixed up with real logins. This prefix should be
  // a maximum of 5 characters and should NOT change once set!
  $NONUSER_PREFIX = '_NUC_';

  if( $single_user != 'Y' )
    $single_user_login = '';
}

?>
