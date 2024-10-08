<?php
/* Declares the WebCalendar class.
 *
 * @author Adam Roben <adam.roben@gmail.com>
 * @copyright Craig Knudsen, <craig@k5n.us>, http://k5n.us/
 * @license https://gnu.org/licenses/old-licenses/gpl-2.0.html GNU GPL
 * @package WebCalendar
 */

/**
 * The WebCalendar.
 *
 * Right now this class's functionality is limited to initialization routines.
 *
 * @todo Get rid of all the global variables.
 * @todo Organize initialization steps more logically.
 */
class WebCalendar {
  /**
   * Filename of the page the user is viewing.
   *
   * @var string
   *
   * @access private
   */
  var $_filename;

  /**
   * WebCalendar install directory.
   *
   * @var string
   *
   * @access private
   */
  var $_directory;

  /**
   * A map from filenames to initialization phases.
   *
   * This array holds the initialization steps for each page. Steps are
   * separated into phases, and listed in the order they should be executed,
   * and are the names of the WebCalendar::methods that should be called,
   * without the `_Init' prefix.
   *
   * @var array
   *
   * @access private
   *
   * @todo Make it possible to distinguish between files in different directories
   * (e.g. login.php and ws/login.php).
   */
  var $_filePhaseMap = [
    '/^(about|nulogin|login|login-app|register|controlpanel|upcoming)\.php$/' =>
    [
      ['Config', 'PHPDBI', 'Functions'],
      ['User', 'Connect']],
    '/^(ajax|layers_ajax|events_ajax|users_ajax|autocomplete_ajax|css_cacher|js_cacher|icalclient|freebusy|publish|rss|rss_unapproved|rss_activity_log|get_reminders|get_events|ws)\.php$/' =>
    [
      ['Config', 'PHPDBI', 'Functions'],
      ['User', 'Validate', 'Connect', 'SiteExtras', 'Access']],
    '/^convert_passwords\.php$/' =>
    [
      ['Config', 'PHPDBI'],
      []],
    '/^send_reminders|send_test_email|populate_sqlite3|reload_remotes\.php$/' =>
    [
      ['Config', 'PHPDBI', 'Functions'],
      ['User', 'SiteExtras']],
    /* This is for files which have called include('includes/init.php'). */
    '/^init\.php$/' =>
    [
      ['InitFirstPhase', 'Config', 'PHPDBI', 'Functions'],
      ['User', 'Validate', 'Connect', 'SiteExtras', 'Access', 'InitSecondPhase']]];
    // Provide translation of translation file (trimming the ".txt" to the proper value for mb_language)
    private $mb_language_map = [
      'Arabic_utf8' => 'Arabic',
      'Chinese-Big5' => 'Chinese',
      'Chinese-GB2312' => 'Chinese',
      'German' => 'German',
      'German_utf8' => 'German',
      'Hebrew_utf8' => 'Hebrew',
      'Japanese-eucjp' => 'Japanese',
      'Japanese-sjis' => 'Japanese',
      'Japanese' => 'Japanese',
      'Korean' => 'Korean',
      'Russian' => 'Russian',
      'Russian_utf8' => 'Russian',
      'Spanish' => 'Spanish',
    ];

  /**
   * WebCalendar constructor.
   *
   * @param  string  $path  full path of file being viewed
   *
   * @return WebCalendar new WebCalendar object
   *
   * @access public
   */
  function __construct ( $path ) {
    $this->_filename = basename ( $path );
    $this->_directory = dirname ( __FILE__ ) . '/../../';
    // Define a value to prevent direct access to files.
    define ( '_ISVALID', 1 );
  }

  // cek: This function is used by some other apps that I have developed
  // but have not released.
  function addExternalPage ( $pattern, $initArray ) {
    $this->_filePhaseMap[$pattern] = $initArray;
  }

  /**
   * First part of initializations from includes/init.php.
   *
   * @access private
   */
  function _initInitFirstPhase() {
    global $DMW, $HTTP_GET_VARS, $HTTP_POST_VARS,
    $PHP_SELF, $SCRIPT, $self, $special, $user_inc;

    // Make sure another app in the same domain doesn't have a 'user' cookie.
    if ( empty ( $HTTP_GET_VARS ) )
      $HTTP_GET_VARS = $_GET;

    if ( empty ( $HTTP_POST_VARS ) )
      $HTTP_POST_VARS = $_POST;

    if ( ! empty ( $HTTP_GET_VARS ) && empty ( $HTTP_GET_VARS['user'] ) && ! empty ( $HTTP_POST_VARS ) && empty ( $HTTP_POST_VARS['user'] ) &&
        isset ( $GLOBALS['user'] ) )
      unset ( $GLOBALS['user'] );

    // Get script name.
    $self = htmlspecialchars($_SERVER['PHP_SELF']);
    if ( empty ( $self ) )
      $self = htmlspecialchars($PHP_SELF);

    preg_match ( '/\/(\w+\.php)/', $self, $match );
    $SCRIPT = $match[1];
    // Security precaution.  Don't allow <script> to be included in
    // a URL in any way.  This includes the directory names as well.
    if ( preg_match ( '/\s*script/i', $_SERVER['QUERY_STRING'] . $self ) ) {
      // No need to have a graceful exit for this since it should only
      // happen to malicious crapweasels.
      echo "<html><body><h2>User Error</h2><p>Bite me.</p></html>\n";
      exit;
    }
    // Don't allow "img src" to be in the URL either.
    if ( preg_match ( '/\s*img.*src/i', $_SERVER['QUERY_STRING'] . $self ) ) {
      // No need to have a graceful exit for this since it should only
      // happen to malicious crapweasels.
      echo "<html><body><h2>User Error</h2><p>Bite me.</p></html>\n";
      exit;
    }


    // Several files need a no-cache header and some of the same code.
    $special = ['month.php', 'day.php', 'week.php',
      'week_details.php', 'year.php', 'minical.php', 'users_ajax.php',
      'layers_ajax.php', 'autocomplete_ajax.php'];
    $DMW = in_array ( $SCRIPT, $special );

    // Unset some variables that shouldn't be set.
    unset ( $user_inc );
  }

  /**
   * Second part of initializations from includes/init.php.
   *
   * @access private
   */
  function _initInitSecondPhase() {
    global $ALLOW_VIEW_OTHER, $can_add, $cat_id, $CATEGORIES_ENABLED,
    $CATEGORY_VIEW, $caturl, $date, $DMW, $friendly, $fullname, $GROUPS_ENABLED,
    $hour, $id, $ignore_user_case, $is_admin, $is_assistant, $is_nonuser_admin,
    $is_nonuser, $login, $minute, $month, $NONUSER_ENABLED, $nonusers,
    $override, $ovrd, $PUBLIC_ACCESS_CAN_ADD, $PUBLIC_ACCESS_FULLNAME,
    $PUBLIC_ACCESS_OTHERS, $PUBLIC_ACCESS, $readonly, $u_url, $user_fullname,
    $USER_SEES_ONLY_HIS_GROUPS, $user, $userlist, $valid_user, $year;

    load_global_settings();

    $this->setLanguage();
    $user = getValue ( 'user', '[A-Za-z0-9_\.=@,\-]*', true );

    if ( empty ( $ovrd ) )
      load_user_preferences();

    // Error-check some commonly used form variable names.
    $cat_id  = getValue ( 'cat_id', '[\-0-9,]+' );
    $date    = getValue ( 'date', '[0-9]+' );
    $friendly= getValue ( 'friendly', '[01]' );
    $override= getValue ( 'override', '[01]' );
    $hour    = getValue ( 'hour', '[0-9]+' );
    $id      = getValue ( 'id', '[0-9]+', true );
    $minute  = getValue ( 'minute', '[0-9]+' );
    $month   = getValue ( 'month', '[0-9]+' );
    $year    = getValue ( 'year', '[0-9]+' );

    if ( empty ( $PUBLIC_ACCESS ) )
      $PUBLIC_ACCESS = 'N';

    // Initialize access settings ($user_access string)
    // and make sure user is allowed to view the current page.
    access_init();
    if ( ! access_can_view_page() ) {
      $user_BGCOLOR = get_pref_setting ( $login, 'BGCOLOR' );
      echo '<html>
  <head>
    <title>' . generate_application_name() . ' ' . translate ( 'Error' ) . '</title>
  </head>
  <body bgcolor="' . $user_BGCOLOR . '">
    ' . print_not_auth ( true ) . '
  </body>
</html>';
      exit;
    }

    $can_add = false;
    // Load if $SCRIPT is in $special array:
    if ( $DMW ) {
      // Tell the browser not to cache.
      // send_no_cache_header();

      if ( $ALLOW_VIEW_OTHER != 'Y' && ! $is_admin && ! $is_assistant )
        $user = '';

      $can_add = ( $readonly == 'N' || $is_admin == 'Y' );
      if ( $PUBLIC_ACCESS == 'Y' && $login == '__public__' ) {
        if ( $PUBLIC_ACCESS_CAN_ADD != 'Y' )
          $can_add = false;

        if ( $PUBLIC_ACCESS_OTHERS != 'Y' )
          $user = ''; // Security precaution.
      }
      if ( ! $is_admin && ! $is_assistant && ! $is_nonuser_admin ) {
	if ($is_nonuser)
          $can_add = false;
	elseif ( ! empty ( $user ) && $user !== $login && $user !== '__public__' )
	  $can_add = false;
      }

      if ( $GROUPS_ENABLED == 'Y' && $USER_SEES_ONLY_HIS_GROUPS == 'Y' && ! $is_admin ) {
        $userlist = get_my_users();
        $valid_user = false;
        if ( ! empty ( $NONUSER_ENABLED ) && $NONUSER_ENABLED == 'Y' ) {
          $nonusers = get_my_nonusers ( $login, true );
          $userlist = array_merge ( $nonusers, $userlist );
        }
        for ( $i = 0; $i < count ( $userlist ); $i++ ) {
          if ( $user == $userlist[$i]['cal_login'] )
            $valid_user = true;
        }
        if ( ! $valid_user )
          $user = ''; // Security precaution.
      }

      if ( ! empty ( $user ) ) {
        $u_url = 'user=' . $user . '&amp;';
        if ( ! user_load_variables ( $user, 'user_' ) )
          nonuser_load_variables($user, 'user_');
        if ( $user == '__public__' )
          $user_fullname = translate ( $PUBLIC_ACCESS_FULLNAME );
      } else {
        $u_url = '';
        $user_fullname = ( $login == '__public__'
          ? translate ( $PUBLIC_ACCESS_FULLNAME ) : $fullname );
      }

      set_today ( $date );

      remember_this_view();

      if ( $CATEGORIES_ENABLED == 'Y' ) {
        if ( ! empty ( $cat_id ) ) {
        } elseif ( ! empty ( $CATEGORY_VIEW ) && ! isset ( $_GET['cat_id'] ) )
          $cat_id = $CATEGORY_VIEW;
        else
          $cat_id = '';
      } else
        $cat_id = '';

      $caturl = ( empty ( $cat_id ) ? '' : '&amp;cat_id=' . $cat_id );
    }
  }

  /**
   * Initializations from includes/assert.php.
   *
   * @access private
   */
  function _initAssert() {
    // Initialize assert options.
    assert_options ( ASSERT_CALLBACK, 'assert_handler' );
    assert_options ( ASSERT_ACTIVE, 1 );
  }

  /**
   * Initializations from includes/config.php.
   *
   * @access private
   */
  function _initConfig() {
    do_config ();
  }

  /**
   * Initializations from includes/dbi4php.php.
   *
   * @access private
   */
  function _initPHPDBI() {
    global $phpdbiVerbose;

    // Enable the following to show the actual database error in the browser.
    // It is more secure to not show this info, so this should only be turned
    // on for debugging purposes.
    if ( ! isset ( $phpdbiVerbose ) )
      $phpdbiVerbose = false;
  }

  /**
   * Initializations from includes/functions.php.
   *
   * @access private
   */
  function _initFunctions() {
    global $byday_names, $byday_values, $days_per_month,
    $db_login, $db_password, $ldays_per_month,
    $offsets, $PHP_SELF, $settings, $weekday_names;

    /**#@+
     * Used for activity log.
     */
    define ( 'LOG_APPROVE',       'A' );
    define ( 'LOG_APPROVE_J',     'P' );
    define ( 'LOG_APPROVE_T',     'H' );
    define ( 'LOG_ATTACHMENT',    'T' );
    define ( 'LOG_COMMENT',       'M' );
    define ( 'LOG_CREATE',        'C' );
    define ( 'LOG_CREATE_J',      'I' );
    define ( 'LOG_CREATE_T',      'G' );
    define ( 'LOG_DELETE',        'D' );
    define ( 'LOG_DELETE_J',      'V' );
    define ( 'LOG_DELETE_T',      'L' );
    define ( 'LOG_LOGIN_FAILURE', 'x' );
    define ( 'LOG_NEWUSER_EMAIL', 'E' );
    define ( 'LOG_NEWUSER_FULL',  'F' );
    define ( 'LOG_NOTIFICATION',  'N' );
    define ( 'LOG_REJECT',        'X' );
    define ( 'LOG_REJECT_J',      'Q' );
    define ( 'LOG_REJECT_T',      'J' );
    define ( 'LOG_REMINDER',      'R' );
    define ( 'LOG_UPDATE',        'U' );
    define ( 'LOG_UPDATE_J',      'S' );
    define ( 'LOG_UPDATE_T',      'K' );
    define ( 'LOG_USER_ADD',      'a' );
    define ( 'LOG_USER_DELETE',   'd' );
    define ( 'LOG_USER_UPDATE',   'u' );
    define ( 'SECURITY_VIOLATION','Z' );
    define ( 'LOG_SYSTEM',        'Y' );
    /**#@-*/

    /**
     * Number of seconds in:
     */
    define ( 'ONE_HOUR', 3600 );
    define ( 'ONE_DAY',  86400 );
    define ( 'ONE_WEEK', 604800 );

    /**
     * Arrays containing the number of days in each month
     * in a leap year and a non-leap year.
     *
     * @global array $ldays_per_month
     * @global array $days_per_month
     */
    $ldays_per_month =
    $days_per_month = [0, 31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31];
    $ldays_per_month[2] = 29;

    /**
     * Array containing the short names for the days of the week.
     *
     * @global array $weekday_names
     */
    $weekday_names = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];

    /**
     * Array containing the BYDAY names for the days of the week.
     *
     * @global array $byday_name
     */
    $byday_names = ['SU', 'MO', 'TU', 'WE', 'TH', 'FR', 'SA'];

    /**
     * Array containing the number value of the days of the week.
     *
     * @global array $days_per_week
     */
    $days_of_week = array_flip ( $weekday_names );

    /**
     * Array containing the number value of the ical ByDay abbreviations.
     *
     * @global array $byday_values
     */
    $byday_values = array_flip ( $byday_names );

    /* Pull out cookies and place them in global variables */
    if ( ! empty ( $_COOKIE['webcalendar_session'] ) )
      $GLOBALS['webcalendar_session'] = $_COOKIE['webcalendar_session'];
    if ( ! empty ( $_COOKIE['webcalendar_login'] ) )
      $GLOBALS['webcalendar_login'] = $_COOKIE['webcalendar_login'];
    if ( ! empty ( $_COOKIE['webcalendar_last_view'] ) )
      $GLOBALS['webcalendar_last_view'] = $_COOKIE['webcalendar_last_view'];
    if ( ! empty ( $_COOKIE['webcalendar_csscache'] ) )
      $GLOBALS['webcalendar_csscache'] = $_COOKIE['webcalendar_csscache'];

    // Don't allow a user to put "login=XXX" in the URL
    // if they are not coming from the login.php page.
    if ( empty ( $PHP_SELF ) && ! empty ( $_SERVER['PHP_SELF'] ) )
      $PHP_SELF = $_SERVER['PHP_SELF']; // Backward compatibility.

    if ( empty ( $PHP_SELF ) )
      $PHP_SELF = ''; // This happens when running send_reminders.php from CL.

    if ( ! strstr ( $PHP_SELF, 'login.php' ) && ! empty ( $GLOBALS['login'] ) )
      $GLOBALS['login'] = '';

    // Define an array to use to jumble up the key: $offsets
    // We define a unique key to scramble the cookie we generate.
    // We use the admin install password that the user set to make
    // the salt unique for each WebCalendar install.
    $salt = ( ! empty ( $settings ) && ! empty ( $settings['install_password'] )
      ? $settings['install_password'] : md5 ( $db_login ) );
    $salt_len = strlen ( $salt );

    $salt2 = md5( empty( $db_password ) ? 'oogabooga' : $db_password );
    $salt2_len = strlen ( $salt2 );

    $offsets = [];
    for ( $i = 0; $i < $salt_len || $i < $salt2_len; $i++ ) {
      $offsets[$i] = 0;
      if ( $i < $salt_len )
        $offsets[$i] += ord ( substr ( $salt, $i, 1 ) );

      if ( $i < $salt2_len )
        $offsets[$i] += ord ( substr ( $salt2, $i, 1 ) );

      $offsets[$i] %= 128;
    }
  }

  /**
   * Initializations from includes/user*.php.
   *
   * This is a placeholder for now. We are letting includes/user*.php handle
   * its own initialization.
   *
   * @access private
   *
   * @todo Make an Authentication interface class and create a subclass for
   *       each user*.php page.
   */
  function _initUser() {
  }

  /**
   * Initializations from includes/validate.php.
   *
   * @access private
   */
  function _initValidate() {
    global $c, $cryptpw, $db_database, $db_host, $db_login, $db_password,
    $encoded_login, $HTTP_ENV_VARS, $HTTP_SERVER_VARS, $ignore_user_case,
    $is_nonuser, $login, $login_return_path, $PHP_AUTH_USER, $REMOTE_USER,
    $SCRIPT, $session_not_found, $settings, $single_user, $single_user_login,
    $user_inc, $use_http_auth, $validate_redirect, $webcalendar_session;

    // Give the PHP session a name unique to this install, allowing multiple WebCalendar installs
    // on the same server.
    $sessionName = 'WebCalendar-' . __DIR__;

    /* If WebCalendar is configured to use http authentication, then we can
     * use _initValidate(). If we are not using http auth, icalclient.php will
     * create its own http auth since an iCal client cannot login via a
     * web-based login. Publish.php does need to validate if not http_auth.
     */
    if ( ! $use_http_auth &&
      ( $this->_filename == 'css_cacher.php' ||
        $this->_filename == 'icalclient.php' ||
        $this->_filename == 'rss_unapproved.php' ||
        $this->_filename == 'rss_activity_log.php' ||
        $this->_filename == 'js_cacher.php' ||
        $this->_filename == 'publish.php' ) ) {
      return;
    }

    $is_nonuser = $session_not_found = $validate_redirect = false;

    // Catch-all for getting the username when using HTTP-authentication.
    if ( $use_http_auth ) {
      if ( empty ( $PHP_AUTH_USER ) ) {
        if ( ! empty ( $_SERVER ) && isset ( $_SERVER['PHP_AUTH_USER'] ) )
          $PHP_AUTH_USER = $_SERVER['PHP_AUTH_USER'];
        else
        if ( ! empty ( $HTTP_SERVER_VARS ) &&
            isset ( $HTTP_SERVER_VARS['PHP_AUTH_USER'] ) )
          $PHP_AUTH_USER = $HTTP_SERVER_VARS['PHP_AUTH_USER'];
        else
        if ( isset ( $REMOTE_USER ) )
          $PHP_AUTH_USER = $REMOTE_USER;
        else
        if ( ! empty ( $_ENV ) && isset ( $_ENV['REMOTE_USER'] ) )
          $PHP_AUTH_USER = $_ENV['REMOTE_USER'];
        else
        if ( ! empty ( $HTTP_ENV_VARS ) && isset ( $HTTP_ENV_VARS['REMOTE_USER'] ) )
          $PHP_AUTH_USER = $HTTP_ENV_VARS['REMOTE_USER'];
        else
        if ( @getenv ( 'REMOTE_USER' ) )
          $PHP_AUTH_USER = getenv ( 'REMOTE_USER' );
        else
        if ( isset ( $AUTH_USER ) )
          $PHP_AUTH_USER = $AUTH_USER;
        else
        if ( ! empty ( $_ENV ) && isset ( $_ENV['AUTH_USER'] ) )
          $PHP_AUTH_USER = $_ENV['AUTH_USER'];
        else
        if ( ! empty ( $HTTP_ENV_VARS ) && isset ( $HTTP_ENV_VARS['AUTH_USER'] ) )
          $PHP_AUTH_USER = $HTTP_ENV_VARS['AUTH_USER'];
        else
        if ( @getenv ( 'AUTH_USER' ) )
          $PHP_AUTH_USER = getenv ( 'AUTH_USER' );
      }
    }

    if ( $single_user == 'Y' )
      $login = $single_user_login;
    else {
      if ( $use_http_auth ) {
        // HTTP server did validation for us....
        if ( empty ( $PHP_AUTH_USER ) )
          $session_not_found = true;
        else
          $login = $PHP_AUTH_USER;
      } else
      if ( substr ( $user_inc, 0, 9 ) == 'user-app-' ) {
        // Make sure we are connected to the database for session check.
        $c = @dbi_connect ( $db_host, $db_login, $db_password, $db_database );
        if ( ! $c )
          die_miserable_death ( 'Error connecting to database:<blockquote>'
             . dbi_error() . '</blockquote>' );

        // Use another application's authentication.
        if ( ! $login = user_logged_in() )
          $session_not_found = true;
      } else {
        session_name(getSessionName());
        @session_start();
        if ( ! empty ( $_SESSION['webcal_login'] ) )
          $login = $_SESSION['webcal_login'];

        if ( ! empty ( $_SESSION['webcalendar_session'] ) )
          $webcalendar_session = $_SESSION['webcalendar_session'];

        if ( empty ( $login ) && empty ( $webcalendar_session ) )
          $session_not_found = true;
        else
        if ( empty ( $_SESSION['webcal_login'] ) &&
            // Check for cookie...
            ! empty ( $webcalendar_session ) ) {
          $encoded_login = $webcalendar_session;
          if ( empty ( $encoded_login ) ) {
            // Invalid session cookie.
            $session_not_found = true;
          } else {
            $cooie_check = explode('|', decode_string($encoded_login));
            // First time after switching to PHP8 you may have
            // incompatible cookies here.
            if ( empty($cooie_check[0]) || empty($cooie_check[1]))
              $session_not_found = true;
          }
          if ( ! $session_not_found ) {
            $login_pw = explode('|', decode_string($encoded_login));
            $login = $login_pw[0];
            $cryptpw = $login_pw[1];

            // Security fix. Don't allow certain types of characters in
            // the login. WebCalendar does not escape the login name in
            // SQL requests. So, if the user were able to set the login
            // name to be "x';drop table u;",
            // they may be able to affect the database.
            // NOTE: we also changed the cookie encoding from WebCalendar 1.0.X
            // to WebCalendar 1.1.X+, so this causes a bad cookie error.
            if ( ! empty ( $login ) && $login != addslashes ( $login ) ) {
              // The following deletes the bad cookie.
              // So, the user just needs to reload.
              sendCookie ( 'webcalendar_session', '', 0 );
              die_miserable_death ( 'Illegal characters in login <span class="tt">'
                 . htmlentities ( $login )
                 . '</span>. Press browser reload to clear bad cookie.' );
            }

            // Make sure we are connected to the database for password check.
            $c = @dbi_connect ( $db_host, $db_login, $db_password, $db_database );
            if ( ! $c )
              die_miserable_death ( 'Error connecting to database:<blockquote>'
                 . dbi_error() . '</blockquote>' );

            doDbSanityCheck();
            if ( $cryptpw == 'nonuser' ) {
              if ( ! nonuser_load_variables ( $login, 'nutemp_' ) )
                // No such nonuser cal.
                die_miserable_death ( 'Invalid nonuser calendar.' );

              if ( empty ( $GLOBALS['nutemp_is_public'] ) ||
                $GLOBALS['nutemp_is_public'] != 'Y' )
                die_miserable_death ( 'Nonuser calendar is not public.' );

              $is_nonuser = true;
            } else
            if ( ! user_valid_crypt ( $login, $cryptpw ) )
              do_redirect ( 'login.php' . ( empty ( $login_return_path )
                  ? '' : '?return_path=' . $login_return_path ) );

            @session_start();
            $_SESSION['webcal_login'] = $login;
            $_SESSION['webcalendar_session'] = $webcalendar_session;
          }
        }
      }
    }
  }
  /**
   * Initializations from includes/connect.php.
   *
   * @access private
   */
  function _initConnect() {
    global $c, $db_database, $db_host, $db_login, $db_password, $firstname,
    $fullname, $is_admin, $is_nonuser, $LANGUAGE, $lastname, $login,
    $login_email, $login_firstname, $login_fullname, $login_is_admin,
    $login_lastname, $login_login, $login_url, $not_auth, $PHP_AUTH_USER,
    $PHP_SELF, $PROGRAM_VERSION, $pub_acc_enabled, $PUBLIC_ACCESS_CAN_ADD,
    $readonly, $SCRIPT, $session_not_found, $single_user,
    $single_user_login, $use_http_auth, $user_email, $user_inc;

    // db settings are in config.php.

    // Establish a database connection.
    // This may have happened in validate.php, depending on settings.
    // If not, do it now.
    if ( empty ( $c ) ) {
      $c = dbi_connect ( $db_host, $db_login, $db_password, $db_database );
      if ( ! $c )
        die_miserable_death ( 'Error connecting to database:<blockquote>'
           . dbi_error() . '</blockquote>' );

      // Do a sanity check on the database,
      // making sure we can at least access the webcal_config table.
      if ( function_exists ( 'doDbSanityCheck' ) )
        doDbSanityCheck();

      // Check the current installation version.
      // Redirect user to install page if it is different from stored value.
      // This will prevent running WebCalendar until UPGRADING.html has been
      // read and required upgrade actions completed.
      $rows = dbi_get_cached_rows ( 'SELECT cal_value FROM webcal_config
         WHERE cal_setting = \'WEBCAL_PROGRAM_VERSION\'' );
      if ( $rows ) {
              $row = $rows[0];
        if ( $row[0] != $PROGRAM_VERSION ) {
          // &amp; does not work here...leave it as &
          header ( 'Location: install/index.php?action=mismatch&version='
                      . $row[0] );
        exit;}

      }
    }

    // If we are in single user mode,
    // make sure that the login selected is a valid login.
    if ( $single_user == 'Y' ) {
      if ( empty ( $single_user_login ) )
        die_miserable_death ( 'You have not defined <span class="tt">single_user_login</span> '
           . 'in <span class="tt">includes/settings.php</span>.' );

      $res = dbi_execute ( 'SELECT COUNT( * ) FROM webcal_user
  WHERE cal_login = ?', [$single_user_login] );
      if ( ! $res ) {
        echo 'Database error: ' . dbi_error();
        exit;
      }
      $row = dbi_fetch_row ( $res );
      if ( $row[0] == 0 ) {
        // User specified as single_user_login does not exist.
        if ( ! dbi_execute ( 'INSERT INTO webcal_user ( cal_login, cal_passwd,
          cal_is_admin ) VALUES ( ?, ?, ? )',
          [$single_user_login, md5 ( $single_user_login ), 'Y'] ) )
          die_miserable_death ( 'User <span class="tt">' . $single_user_login
             . '</span> does not exist in <span class="tt">webcal_user</span> table and we were '
             . 'not able to add it for you:<br><blockquote>' . dbi_error()
             . '</blockquote>' );

        // User was added... should we tell them?
      }
      dbi_free_result ( $res );
    }
    // Global settings have not been loaded yet, so check for public_access now.
    $rows = dbi_get_cached_rows ( 'SELECT cal_value FROM webcal_config
      WHERE cal_setting = \'PUBLIC_ACCESS\'' );
        if ( $rows )
          $row = $rows[0];

    $pub_acc_enabled = ( ! empty ( $row ) && $row[0] == 'Y' );

    if ( $pub_acc_enabled ) {
      $rows = dbi_get_cached_rows ( 'SELECT cal_value FROM webcal_config
        WHERE cal_setting = \'PUBLIC_ACCESS_CAN_ADD\'' );
      if ( $rows && $row == $rows[0] )
        $PUBLIC_ACCESS_CAN_ADD = $row[0];
    }

    if ( empty ( $PHP_SELF ) )
      $PHP_SELF = $_SERVER['PHP_SELF'];

    if ( empty ( $login_url ) )
      $login_url = 'login.php';

    $login_url .= ( strstr ( $login_url, '?' ) ? '&amp;' : '?' )
     . ( empty ( $login_return_path ) ? '' : 'return_path='
         . $login_return_path );

    // If sent here from an email and not logged in,
        //save URI and redirect to login.
    $em = getGetValue ( 'em' );
        $view_via_email = false;
    if ( ! empty ( $em ) && empty ( $login ) ) {
      remember_this_view();
      $view_via_email = true;
    }

    if ( empty ( $session_not_found ) )
      $session_not_found = false;

    if ( ! $view_via_email && $pub_acc_enabled && ! empty ( $session_not_found ) ) {
      $firstname = $lastname = $user_email = '';
      $fullname = 'Public Access'; // Will be translated after translation is loaded.
      $is_admin = false;
      $login = '__public__';
    } else
    if ( $view_via_email || ( ! $pub_acc_enabled && $session_not_found
          && ! $use_http_auth ) ) {
      if ( substr ( $user_inc, 0, 9 ) == 'user-app-' )
        app_login_screen ( clean_whitespace ( $SCRIPT ) );
      else {
        do_redirect ( $login_url );
        exit;
      }
    }

    $is_nonuser = false;

    if ( empty ( $login ) && $use_http_auth ) {
      if ( strstr ( $PHP_SELF, "login.php" ) ) {
        // Ignore since login.php will redirect to index.php.
      } else
        send_http_login();
    } else
    if ( ! empty ( $login ) ) {
      // They are already logged in ($login is set in validate.php).
      if ( strstr ( $PHP_SELF, 'login.php' ) ) {
        // Ignore since login.php will redirect to index.php.
      } else
      if ( $login == '__public__' ) {
        $firstname = $lastname = $user_email = '';
        $fullname = 'Public Access';
        $is_admin = false;
      } else {
        user_load_variables ( $login, 'login_' );
        if ( ! empty ( $login_login ) ) {
          $firstname = $login_firstname;
          $lastname = $login_lastname;
          $fullname = $login_fullname;
          $is_admin = ( $login_is_admin == 'Y' );
          $is_nonuser = ( ! empty ( $GLOBALS['login_is_nonuser'] )
              && $GLOBALS['login_is_nonuser'] );
          $user_email = $login_email;
        } else {
          // Invalid login.
          if ( $use_http_auth ) {
            if ( $pub_acc_enabled ) {
              $login = '__public__';
              $firstname = $lastname = $user_email = '';
              $fullname = 'Public Access';
              $is_admin = false;
            } else
              send_http_login();
          } else
            // This shouldn't happen since login should be validated in validate.php.
            // If it does happen, it means we received an invalid login cookie.
            do_redirect ( $login_url . '&amp;error=Invalid+session+found.' );
        }
      }
    }

    // If they are accessing using the public login,
    // Restrict them from using certain pages.
    $not_auth = false;
    if ( ! empty ( $login ) && $login == '__public__' || $is_nonuser ) {
      if ( strstr ( $PHP_SELF, 'activity_log.php' ) ||
        strstr ( $PHP_SELF, 'admin.php' ) ||
        strstr ( $PHP_SELF, 'admin_handler.php' ) ||
        strstr ( $PHP_SELF, 'adminhome.php' ) ||
        strstr ( $PHP_SELF, 'approve_entry.php' ) ||
        strstr ( $PHP_SELF, 'category.php' ) ||
        strstr ( $PHP_SELF, 'category_handler.php' ) ||
        strstr ( $PHP_SELF, 'del_entry.php' ) ||
        strstr ( $PHP_SELF, 'edit_remotes.php' ) ||
        strstr ( $PHP_SELF, 'edit_remotes_handler.php' ) ||
        strstr ( $PHP_SELF, 'edit_template.php' ) ||
        strstr ( $PHP_SELF, 'group_edit_handler.php' ) ||
        strstr ( $PHP_SELF, 'groups.php' ) ||
        strstr ( $PHP_SELF, 'import.php' ) ||
        strstr ( $PHP_SELF, 'import_handler.php' ) ||
        strstr ( $PHP_SELF, 'layer_toggle.php' ) ||
        strstr ( $PHP_SELF, 'layers.php' ) ||
        strstr ( $PHP_SELF, 'list_unapproved.php' ) ||
        strstr ( $PHP_SELF, 'pref.php' ) ||
        strstr ( $PHP_SELF, 'pref_handler.php' ) ||
        strstr ( $PHP_SELF, 'reject_entry.php' ) ||
        strstr ( $PHP_SELF, 'set_entry_cat.php' ) ||
        strstr ( $PHP_SELF, 'views.php' ) ||
        strstr ( $PHP_SELF, 'views_edit_handler.php' ) )
        $not_auth = true;
    }

    if ( empty ( $is_admin ) || ! $is_admin ) {
      //if ( strstr ( $PHP_SELF, 'activity_log.php' ) ||
      if ( strstr ( $PHP_SELF, 'admin.php' ) ||
        strstr ( $PHP_SELF, 'admin_handler.php' ) ||
        strstr ( $PHP_SELF, 'group_edit.php' ) ||
        strstr ( $PHP_SELF, 'group_edit_handler.php' ) ||
        strstr ( $PHP_SELF, 'groups.php' ) ) {
        $not_auth = true;
      }
    }

    // Restrict access if calendar is read-only.
    if ( $readonly == 'Y' ) {
      if ( strstr ( $PHP_SELF, 'activity_log.php' ) ||
        strstr ( $PHP_SELF, 'admin.php' ) ||
        strstr ( $PHP_SELF, 'adminhome.php' ) ||
        strstr ( $PHP_SELF, 'approve_entry.php' ) ||
        strstr ( $PHP_SELF, 'category.php' ) ||
        strstr ( $PHP_SELF, 'category_handler.php' ) ||
        strstr ( $PHP_SELF, 'del_entry.php' ) ||
        strstr ( $PHP_SELF, 'edit_report.php' ) ||
        strstr ( $PHP_SELF, 'edit_report_handler.php' ) ||
        strstr ( $PHP_SELF, 'edit_template.php' ) ||
        strstr ( $PHP_SELF, 'group_edit_handler.php' ) ||
        strstr ( $PHP_SELF, 'groups.php' ) ||
        strstr ( $PHP_SELF, 'import.php' ) ||
        strstr ( $PHP_SELF, 'import_handler.php' ) ||
        strstr ( $PHP_SELF, 'import_handler.php' ) ||
        strstr ( $PHP_SELF, 'layer_toggle.php' ) ||
        strstr ( $PHP_SELF, 'layers.php' ) ||
        strstr ( $PHP_SELF, 'list_unapproved.php' ) ||
        strstr ( $PHP_SELF, 'pref.php' ) ||
        strstr ( $PHP_SELF, 'pref_handler.php' ) ||
        strstr ( $PHP_SELF, 'pref_handler.php' ) ||
        strstr ( $PHP_SELF, 'purge.php' ) ||
        strstr ( $PHP_SELF, 'register.php' ) ||
        strstr ( $PHP_SELF, 'reject_entry.php' ) ||
        strstr ( $PHP_SELF, 'set_entry_cat.php' ) ||
        strstr ( $PHP_SELF, 'users.php' ) ||
        strstr ( $PHP_SELF, 'views.php' ) ||
        strstr ( $PHP_SELF, 'views_edit_handler.php' ) )
        $not_auth = true;
    }

    // An attempt will be made to translate
    if ( $not_auth ) {
      load_user_preferences();
      $error = ( function_exists ( 'translate' )
        ? translate ( 'You are not authorized.' ) : 'You are not authorized.' );
      die_miserable_death ( $error );
    }
  }

  /**
   * Initializations from includes/site-extras.php.
   *
   * This is a placeholder for now.
   *
   * @access private
   *
   * @todo Figure out what should go here.
   */
  function _initSiteExtras() {
  }

  /**
   * Initializations from includes/access.php.
   *
   * @access private
   */
  function _initAccess() {
    global $access_other_cals;

    // Global variable used to cache permissions
    $access_other_cals = [];
  }

  /**
   * Initializations from includes/translate.php.
   *
   * @access private
   */
  function _initTranslate() {
    global $enable_mbstring, $lang_file, $lang,
    $LANGUAGE, $PUBLIC_ACCESS_FULLNAME, $translation_loaded;

    if ( empty ( $LANGUAGE ) )
      $LANGUAGE = 'English-US'; // Default

    // If set to use browser settings,
    // use the user's language preferences from their browser.
    $lang = $LANGUAGE;
    if ( $LANGUAGE == 'Browser-defined' || $LANGUAGE == 'none' ) {
      $lang = get_browser_language();
      if ( $lang == 'none' )
        $lang = '';
    }
    if ( strlen ( $lang ) == 0 || $lang == 'none' )
      $lang = 'English-US'; // Default

    $lang_file = 'translations/' . $lang . '.txt';

    if (extension_loaded('mbstring')) {
      $mb_lang = strtok($lang, '-');
      // Check the language against the map, default to 'neutral' if not found
      $mapped_lang = $this->mb_language_map[$mb_lang] ?? 'neutral';
      if (@mb_language($mapped_lang) && mb_internal_encoding(translate('charset'))) {
          $enable_mbstring = true;
      } else {
          $enable_mbstring = false;
      }
  }

    $translation_loaded = false;

    $PUBLIC_ACCESS_FULLNAME = 'Public Access'; // default
  }

  /**
   * Gets the initialization phases for the page being viewed.
   *
   * @return array of initialization phases
   *
   * @access private
   */
  function _getPhases() {
    global $user_inc;

    foreach ( $this->_filePhaseMap as $pattern => $phases ) {
      if ( preg_match ( $pattern, $this->_filename ) !== 0 )
        return $phases;
    }
    die_miserable_death ( '_getPhases: could not find \'' . $this->_filename
       . '\' in _filePhaseMap.' );
  }

  /**
   * Gets the initialization steps for the current page and phase.
   *
   * @param  int  $phase  Initialization phase number
   *
   * @return array of initialization steps
   *
   * @access private
   */
  function _getSteps ( $phase ) {
    $phases = $this->_getPhases();

    return $phases[$phase - 1];
  }

  /**
   * Performs initialization steps.
   *
   * @param  int  $phase  Which step of initialization should we perform?
   *
   * @access private
   */
  function _doInit ( $phase ) {
    $steps = $this->_getSteps ( $phase );
    foreach ( $steps as $step ) {
      $function = "_init$step";
      $this->$function();
    }
  }

  /**
   * Begins initialization of WebCalendar.
   *
   * @param  string  $path  Full path of page being viewed
   *
   * @access public
   */
  function initializeFirstPhase() {
    $this->_doInit ( 1 );
  }

  /**
   * Continues initialization of WebCalendar.
   *
   * @param  string  $path  Full path of page being viewed
   *
   * @access public
   */
  function initializeSecondPhase() {
    $this->_doInit ( 2 );
  }

  /**
   * Sets the translation language.
   *
   * @access public
   */
  function setLanguage() {
    $this->_initTranslate();
  }

  /**
   * Construct an absolute path.
   *
   * @param  string  $path  relative to the WebCalendar install directory
   *
   * @return string The absolute path
   */
  function absolutePath ( $path ) {
    return $this->_directory . $path;
  }
}

?>
