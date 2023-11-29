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
 * @author Craig Knudsen <craig@k5n.us>
 * @copyright Craig Knudsen, <craig@k5n.us>, https://k5n.us/
 * @license https://gnu.org/licenses/old-licenses/gpl-2.0.html GNU GPL
 * @package WebCalendar
 */

// Pull in Bootstrap and JQuery from load_assets.php.
// $ASSETS will contain a string of the HTML to load them.
// See composer.json for version.
require_once 'load_assets.php';

// Define possible app settings and their types
$config_possible_settings = [
  'install_password' => 'string',
  'install_password_hint' => 'string',
  'db_cachedir'      => 'string',
  'db_database'      => 'string',
  'db_debug'         => 'boolean',
  'db_host'          => 'string',
  'db_login'         => 'string',
  'db_password'      => 'string',
  'db_persistent'    => 'boolean',
  'db_type'          => 'string',
  'readonly'         => 'string', # "Y" or "N"
  'single_user'      => 'string', # "Y" or "N"
  'use_http_auth'    => 'boolean',
  'user_inc'         => 'string',
  'config_inc'       => 'string',
  'mode'             => 'string'  # "dev" or "prod"
];

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
function die_miserable_death($error, $anchor = '')
{
  global $APPLICATION_NAME, $LANGUAGE, $login, $TROUBLE_URL;

  // Make sure app name is set.
  $appStr = (empty($APPLICATION_NAME) ? 'WebCalendar' : $APPLICATION_NAME);
  $url = $TROUBLE_URL;
  if (!empty($anchor)) {
    $args = explode('#', $TROUBLE_URL);
    $url = $args[0] . '#' . $anchor;
  }


  echo <<<EOT
<html>
  <head><title>{$appStr}: Fatal Error</title></head>
  <body>
    <h2>{$appStr} Error</h2>
    <p>{$error}</p><hr>
    <p><a href="{$url}" target="_blank">Troubleshooting Help</a></p>
  </body>
</html>
EOT;
  exit;
}

function db_error($doExit = false, $sql = '')
{
  global $settings;

  $ret = str_replace('XXX', dbi_error(), translate('Database error XXX.'))
    . (!empty($settings['mode']) && $settings['mode'] == 'dev'
      && !empty($sql) ? '<br />SQL:<br />' . $sql : '');

  if ($doExit) {
    echo $ret;
    exit;
  } else
    return $ret;
}

/**
 * Get the full path to a file located in the webcalendar includes directory.
 */
function get_full_include_path($filename)
{
  if (preg_match('/(.*)config.php/', __FILE__, $matches)) {
    $fileLoc = $matches[1] . $filename;
    return $fileLoc;
  } else
    // Oops. This file is not named config.php!
    die_miserable_death('Crap! Someone renamed config.php');
}

/**
 * Initializes application configurations.
 *
 * The function fetches configuration settings either from environment variables
 * (when WEBCALENDAR_USE_ENV is set to true) or the settings.php file. It sets up
 * the database connection based on the obtained settings and ensures the application
 * is running on the correct version by comparing with the stored database version.
 * If there's a mismatch, it redirects the user to the installation page. Additionally,
 * the function also initializes nonuser calendar prefixes and handles single user mode settings.
 *
 * Global variables affected:
 * - $db_database
 * - $db_host
 * - $db_login
 * - $db_password
 * - $db_persistent
 * - $db_type
 * - $phpdbiVerbose
 * - $run_mode
 * - $settings
 * - $single_user
 * - $single_user_login
 * - $TROUBLE_URL
 * - $user_inc
 *
 * @return array An array of settings after all the necessary initialization.
 *
 * @global string $db_database         The name of the database.
 * @global string $db_host             The hostname of the database server.
 * @global string $db_login            Database user's login name.
 * @global string $db_password         Password for the database user.
 * @global string $db_persistent       Specifies if persistent database connections should be used.
 * @global string $db_type             The type of database server.
 * @global string $phpdbiVerbose      Determines the verbosity of the PHP database interface.
 * @global string $run_mode            The mode in which the application is running (dev/prod).
 * @global array  $settings            An array holding various settings for the application.
 * @global string $single_user         Specifies if the application is in single user mode.
 * @global string $single_user_login   If in single user mode, this specifies the login name.
 * @global string $TROUBLE_URL         URL pointing to the Troubleshooting section.
 * @global string $user_inc            Indicates the type of user authentication.
 */
function do_config($callingFromInstall=false)
{
  global $db_database, $db_debug, $db_host, $db_login, $db_password, $db_persistent,
    $db_type, $ignore_user_case, $NONUSER_PREFIX, $phpdbiVerbose, $PROGRAM_DATE,
    $PROGRAM_NAME, $PROGRAM_URL, $PROGRAM_VERSION, $readonly, $run_mode, $settings,
    $single_user, $single_user_login, $TROUBLE_URL, $user_inc, $use_http_auth;
  global $config_possible_settings;

  // Define possible app settings and their types
  $possible_settings = $config_possible_settings;

  // When changing PROGRAM VERSION, also change it in install/default_config.php
  $PROGRAM_VERSION = 'v1.9.12';
  // Update PROGRAM_DATE with official release data
  $PROGRAM_DATE = '03 Nov 2023';

  $PROGRAM_NAME = 'WebCalendar ' . "$PROGRAM_VERSION ($PROGRAM_DATE)";
  $PROGRAM_URL = 'http://k5n.us/wp/webcalendar/';
  $TROUBLE_URL = 'docs/WebCalendar-SysAdmin.html#trouble';

  $settings = [];

  // Decide the source based on the WEBCALENDAR_USE_ENV env variable
  $use_env = getenv('WEBCALENDAR_USE_ENV');
  if ($use_env && strtolower($use_env) === "true") {
    // Load from environment variables
    foreach ($possible_settings as $key => $type) {
      $env_key = 'WEBCALENDAR_' . strtoupper($key);
      $env_value = getenv($env_key);

      if ($env_value !== false) {
        $settings[$key] = ($type === 'boolean') ? filter_var($env_value, FILTER_VALIDATE_BOOLEAN) : $env_value;
      } else {
        if ($type === 'boolean') {
          $settings[$key] = false;
        }
      }
    }
  } else if (!file_exists(__DIR__ . '/settings.php') && !$callingFromInstall) {
    // Redirect to installer
    if (file_exists(__DIR__ . '/../install/index.php')) {
      header('Location: install/index.php');
      exit;
    } else {
      die_miserable_death(translate('Could not find settings.php file...'));
    }
  } else {
    // Load from settings.php file
    $settings_content = @file_get_contents(__DIR__ . '/settings.php');
    if (empty($settings_content)) {
      if ($callingFromInstall) {
        return; // not an error during install
      }
      // There is no settings.php file.
      // Redirect user to install page if it exists.
      if (file_exists('install/index.php')) {
        header('Location: install/index.php');
        exit;
      } else {
        die_miserable_death(translate('Could not find settings.php file...'));
      }
    }

    foreach ($possible_settings as $key => $type) {
      if (preg_match('/' . $key . ':\s*(.*)/', $settings_content, $matches)) {
        $value = trim($matches[1]);
        $settings[$key] = ($type === 'boolean') ? filter_var($value, FILTER_VALIDATE_BOOLEAN) : $value;
      } else {
        // Setting not found
        if ($type === 'boolean') {
          $settings[$key] = false;
        }
      }
    }
  }

  if (isset($settings['config_inc'])) {
    # Load 3rd party configs from external app
    require get_full_include_path($settings['config_inc']);
    $settings = do_external_configs($settings);
  }

  // Extract db settings into global vars.
  $db_database = $settings['db_database'] ?? '';
  $db_host     = $settings['db_host'] ?? '';
  $db_login    = $settings['db_login'] ?? '';
  $db_password = $settings['db_password'] ?? '';
  $db_persistent = (preg_match(
    '/(1|yes|true|on)/i',
    $settings['db_persistent']
  ) ? true : false );
  $db_debug = (preg_match(
    '/(1|yes|true|on)/i',
    $settings['db_debug']
  ) ? true : false);
  $db_type = $settings['db_type'] ?? '';

  // If no db settings, then user has likely started install but not yet
  // completed. So, send them back to the install script.
  if (empty($db_type)) {
    if ($callingFromInstall) {
      return; // not an error during install
    }
    if (file_exists('install/index.php')) {
      header('Location: install/index.php');
      exit;
    } else
      die_miserable_death(translate('Incomplete settings.php file...'));
  }

  // Use 'db_cachedir' if found, otherwise look for 'cachedir'.
  if (!$callingFromInstall) {
    if (!empty($settings['db_cachedir']))
      dbi_init_cache($settings['db_cachedir']);
    else
    if (!empty($settings['cachedir']))
      dbi_init_cache($settings['cachedir']);
  }

  if (
    !empty($settings['db_debug'])
    && preg_match('/(1|true|yes|enable|on)/i', $settings['db_debug'])
  )
    dbi_set_debug(true);

  if (!$callingFromInstall) {
    foreach ( ['db_type', 'db_host', 'db_login'] as $s) {
      if (empty($settings[$s]))
        die_miserable_death(str_replace(
          'XXX',
          $s,
          translate('Could not find XXX defined in...')
        ));
    }
  }

  // Allow special settings of 'none' in some settings[] values.
  // This can be used for db servers not using TCP port for connection.
  $db_host = ($db_host == 'none' ? '' : $db_host);
  $db_password = (empty($db_password) || $db_password == 'none'
    ? '' : $db_password);

  $readonly = $settings['readonly'] = (!empty($settings['readonly'])
    && preg_match('/(1|true|yes|enable|on)/i', $settings['readonly'])) ? 'Y' : 'N';

  if (empty($settings['mode']))
    $settings['mode'] = 'prod';

  $run_mode = (preg_match('/(dev)/i', $settings['mode']) ? 'dev' : 'prod');
  $phpdbiVerbose = ($run_mode == 'dev');
  $single_user = $settings['single_user'] = (!empty($settings['single_user'])
    && preg_match('/(1|true|yes|enable|on)/i', $settings['single_user'])) ? 'Y' : 'N';
  if (isset($single_user) && $single_user == 'Y') {
    $single_user_login = $settings['single_user_login'];
    if (!$callingFromInstall) {
      if (empty($single_user_login))
        die_miserable_death(str_replace(
          'XXX',
          'single_user_login',
          translate('You must define XXX in')
        ));
    }
  } else {
    $single_user = 'N';
    $single_user_login = '';
  }

  // Type of user authentication.
  $user_inc = $settings['user_inc'];

  // If SQLite, the db file is in the includes directory.
  if ($db_type == 'sqlite' || $db_type == 'sqlite3') {
    if (substr($db_database, 0, 1) != '/' && !file_exists($db_database))
      $db_database = get_full_include_path($db_database);
  }

  $locateStr = 'Location: install/index.php';

  // Check the current installation version.
  // Redirect user to install page if it is different from stored value.
  // This will prevent running WebCalendar the database is updated
  // (typically through the web-based install pages).
  $c = @dbi_connect($db_host, $db_login, $db_password, $db_database, false);

  if ($c && !$callingFromInstall) {
    $rows = dbi_get_cached_rows('SELECT cal_value FROM webcal_config
      WHERE cal_setting = \'WEBCAL_PROGRAM_VERSION\'');

    //echo "<pre>"; print_r($rows); echo "</pre>"; exit;
    if (!$rows || empty($rows) || empty($rows[0])) {
      header($locateStr);
      exit;
    } else {
      $versionInDb = $rows[0][0];
      if ($versionInDb != $PROGRAM_VERSION) {
        // New version has been installed on filesystem but db says it is an
        // older version.  See if we can just bump up the version in the db
        // (only an option when there are no database schema changes between
        // the version and the new version.)
        if (upgrade_requires_db_changes($db_type, $versionInDb, $PROGRAM_VERSION)) {
          header($locateStr);
          exit;
        } else {
          // We can just update the version in the database and move on.
          if (!update_webcalendar_version_in_db($versionInDb, $PROGRAM_VERSION)) {
            die_miserable_death("Unable to update version in database");
          }
        }
      }
    }
    dbi_close($c);
  } else {
    if (!$callingFromInstall) {
      // Must mean we don't have a settings.php file or env variables.
      header($locateStr);
      exit;
    }
  }

  // We can add extra "nonuser" calendars such as a holiday, corporate,
  // departmental, etc. We need a unique prefix for these calendars
  // so we don't get them mixed up with real logins. This prefix should be
  // a maximum of 5 characters and should NOT change once set!
  $NONUSER_PREFIX = '_NUC_';

  if ($single_user != 'Y')
    $single_user_login = '';

  return $settings;
}


function setSettingsInSession() {
  global $config_possible_settings, $settings;
  //echo "<pre>settings:\n"; print_r($settings); echo "</pre>";
  foreach ($config_possible_settings as $key => $type) {
    if (isset($settings[$key])) {
      $_SESSION[$key] = $settings[$key];
    }
  }
}
