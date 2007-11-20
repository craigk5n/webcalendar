<?php
/**
 * Declares the WebCalendar class.
 *
 * @author Adam Roben <adam.roben@gmail.com>
 * @copyright Craig Knudsen, <cknudsen@cknudsen.com>, http://www.k5n.us/cknudsen
 * @license http://www.gnu.org/licenses/gpl.html GNU GPL
 * @version $Id$
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
   * Name of the current logged in user
   *
   * @var string
   *
   * @access private
   */
  var $_login;
   /**
   * User ID of the current logged in user
   *
   * @var int
   *
   * @access private
   */
  var $_loginId;
  
   /**
   * Name of the user specified by user= in URL
   *
   * @var string
   *
   * @access private
   */
  var $_user = '';
   /**
   * User ID of the user specified by user= in URL
   *
   * @var int
   *
   * @access private
   */
  var $_userId;
  
   var $_eid;
   var $_date;
   var $hour;
   var $minute;
   var $_cat_id;
   var $_friendly;
   var $_lang;
   var $_browserLanguage;
   var $_year;
   var $_month;
   var $_day;
   var $today; //public
   var $todayYmd; //public
   var $thisyear; //public
   var $thismonth; //public
   var $thisday; //public
   var $thisdate; //public
   var $_categories;
   var $_isNonuserAdmin; //public
   var $User; //placeholder for User class
   var $byday_names;
   var $byday_values;
   var $_offsets;
   var $_startdate;
   var $_enddate;
   var $_webcalendar_session;
   var $_webcalendar_login;
   var $_webcalendar_custom_colors;
   var $_webcalendar_captcha;
   var $_webcalendar_last_view;
   var $_login_url;
   var $_logout_url;
   var $_isAdmin;
   var $lastName;
   var $firstName;
   var $fullName;
   var $userEmail;
   var $_isNonUser;
   var $_can_add;
   
  /**
   * A map from filenames to initialization phases.
   *
   * This array holds the initialization steps for each page. Steps are
   * separated into phases, and listed in the order they should be executed,
   * and are the names of the WebCalendar:: methods that should be called,
   * without the `_Init' prefix.
   *
   * @var array
   *
   * @access private
   *
   * @todo Make it possible to distinguish between files in different directories (e.g. login.php and ws/login.php).
   */
  var $_filePhaseMap = array('/^(about|login|applogin|freebusy|nulogin|register|controlpanel|upcoming)\.php$/' =>
  array(array('Config'), array('Connect')),
'/^(ajax|icalclient|publish|rss|get_reminders|get_events|ws)\.php$/' =>
  array(array('Config'),
  array('Validate', 'User', 'Connect', 'Access')),
'/^send_reminders|reload_remotes\.php$/' =>
  array(array('Config'),
  array('User')),
/* This is for files which have called include('includes/init.php') */
'/^init\.php$/' =>
  array(array('InitFirstPhase', 'Config'),
  array('Validate', 'User', 'Connect', 'Assert', 'Access', 'InitSecondPhase'))
);

  /**
   * WebCalendar constructor.
   *
   * @param string $path Full path of file being viewed.
   *
   * @return WebCalendar New WebCalendar object.
   *
   * @access public
   */
  function WebCalendar ( $path ) {
    $this->_filename = basename ( $path );

    $this->_directory = dirname ( __FILE__ ) . '/../../';

    // Get script name
    $self = $_SERVER['PHP_SELF'];
    //allow any word character  and '-'
    preg_match ( "/\/([\w-]+\.php)/", $self, $match);
    define ( '_WC_SCRIPT', $match[1] );

    // Set <body id="xxx" value
    $body_id = preg_replace ( '/(_|.php)/', '',
    substr ( $self, strrpos ( $self, '/' ) + 1 ) );
    define ( '_WC_BODY_ID', $body_id );
        
    //define  a value to prevent direct access to files
    define ( '_ISVALID', 1 );
    
    //setup up some date values based of the 'CALTYPE' constant value
    $caltype =  $this->getValue ( 'caltype' );
    if ( ! defined ( 'CALTYPE' ) )
      define ( 'CALTYPE', ( ! empty ( $caltype ) ? $caltype : false ) ); 
  }

  // cek: This function is used by some other apps that I have developed
  // but have not been released.
  function addExternalPage ( $pattern, $initArray )
  {
    $this->_filePhaseMap[$pattern] = $initArray;
  }

  /**
   * First part of initializations from includes/init.php.
   *
   * @access private
   */
  function _initInitFirstPhase() {
    global $DMW;

    // Several files need a no-cache header and some of the same code
    $special = array('month.php', 'day.php', 'week.php', 'week_details.php', 'year.php', 'minical.php');
    $DMW = in_array( _WC_SCRIPT, $special );
  
  }

  /**
   * Second part of initializations from includes/init.php.
   *
   * @access private
   */
  function _initInitSecondPhase() {
    global $smarty, $ovrd, $DMW,
      $can_add, $can_add,
      $valid_user, $userlist,
      $nonusers, $u_url, $user_fullname, $fullname,
      $caturl, $CATEGORY_VIEW;

    loadConfig ();
    $smarty->LoadVars ( $this->_loginId );

    $this->setLanguage();
    
    $this->loadCategories();
  
    if ( empty ( $ovrd ) )
      loadPreferences ();
    // error-check some commonly used form variable names
    $this->_eid = $this->getValue ( 'eid', '[0-9]+', true );
    $this->_userId = $this->getValue ( 'user', '-?[0-9]+', true );
    $this->_date = $this->getValue ( 'date', '[\-0-9]+' );
    $this->hour = $this->getValue ( 'hour', '[0-9]+' );
    $this->minute = $this->getValue ( 'minute', '[0-9]+' );
    if ( getPref ( '_ENABLE_CATEGORIES', 2 ) )
      $this->_cat_id = $this->getValue ( 'cat_id', '\-*[0-9]+\-*[,0-9]*' );
    $this->_friendly = $this->getValue ( 'friendly', '[01]' );
    $this->_year = $this->getValue ( 'year', '[0-9]+' );
    $this->_month = $this->getValue ( 'month', '[0-9]+' );
    $this->_day = $this->getValue ( 'day', '[0-9]+' );
    // Initialize access settings ($user_access string) and make sure user
    // is allowed to view the current page.
    access_init ();

    $can_add = false;

    $this->setToday( $this->_date );

    if ( CALTYPE == false || CALTYPE == 'month'  ) {
    $next = mktime ( 0, 0, 0, $this->thismonth + 1, 1, $this->thisyear );
    $prev = mktime ( 0, 0, 0, $this->thismonth - 1, 1, $this->thisyear );
    
  } else if ( CALTYPE == 'week' ) {
    $next = mktime ( 0, 0, 0, $this->thismonth, 
    $this->thisday + 7, $this->thisyear );
    $prev = mktime ( 0, 0, 0, $this->thismonth, 
    $this->thisday - 7, $this->thisyear );    
  } else if ( CALTYPE == 'year' ) {
    if ( empty ( $this->thisyear ) ) 
    $this->thisyear = date ( 'Y' );
    $prev = mktime ( 0, 0, 0, 1, 1, $this->thisyear -1 );
    $next = mktime ( 0, 0, 0, 1, 1, $this->thisyear +1 );
  } else if ( CALTYPE == 'day' ) {
    $prev = mktime ( 0, 0, 0, $this->thismonth, 
    $this->thisday - 1, $this->thisyear );    
    $next = mktime ( 0, 0, 0, $this->thismonth, 
    $this->thisday + 1, $this->thisyear );     
  }
  $this->nextYmd = date ( 'Ymd', $next );
  $this->nextyear = substr ( $this->nextYmd, 0, 4 );
  $this->nextmonth = substr ( $this->nextYmd, 4, 2 );    
  
  $this->prevYmd = date ( 'Ymd', $prev );
  $this->prevyear = substr ( $this->prevYmd, 0, 4 );
  $this->prevmonth = substr ( $this->prevYmd, 4, 2 );
  
  $smarty->assign('nextYmd', $this->nextYmd);
  $smarty->assign('prevYmd', $this->prevYmd);
  $smarty->assign('navDate', mktime ( 0, 0, 0, 
    $this->thismonth, $this->thisday, $this->thisyear ) );
            
  if (  CALTYPE == false || CALTYPE == 'month' ) {
    $this->_startdate = mktime ( 0, 0, 0, 
      $this->thismonth, 1, $this->thisyear );
    $this->_enddate = mktime ( 23, 59, 59, 
      $this->thismonth + 1, 0, $this->thisyear );      
  } else if ( CALTYPE == 'week' ) {
    $this->_startdate = get_weekday_before ( $this->thisyear, 
      $this->thismonth, $this->thisday +1 );
    $this->_enddate = $this->_startdate + ( ONE_DAY * ( 
      getPref ('DISPLAY_WEEKENDS') ? 7 : 5 ) );
//    }
  } else if ( CALTYPE == 'year' ) {
    $this->_startdate = mktime ( 0, 0, 0, 1, 1, $this->thisyear);
    $this->_enddate = mktime ( 23, 59, 59, 12, 31, $this->thisyear);   
  } else if ( CALTYPE == 'day' ) {
    $this->_startdate = mktime ( 0, 0, 0, 
    $this->thismonth, 1, $this->thisyear );
    $this->_enddate = mktime ( 23, 59, 59, 
    $this->thismonth + 1, 0, $this->thisyear );    
  }
  $smarty->assign('thisyear', $this->thisyear);
  $smarty->assign('thismonth', $this->thismonth);
  $smarty->assign('thisday', $this->thisday);         
     // Load if _WC_SCRIPT is in $special array:
    if ($DMW) {     
      if ( ! getPref ( '_ALLOW_VIEW_OTHER', 2 ) && ! $this->_isAdmin )
        $this->_userId = '';

      $can_add = ( !_WC_READONLY || $this->_isAdmin );
      if ( $this->_isNonuser )
        $can_add = false;

      if ( getPref ( '_ENABLE_GROUPS', 2 ) && 
        getPref ( '_USER_SEES_ONLY_HIS_GROUPS', 2 ) &&
        ! $this->_isAdmin ) {
        $valid_user = false;
        $userlist = get_my_users();
        if ( getPref ( '_ENABLE_NONUSERS', 2 )) {
          $nonusers = get_my_nonusers ( $this->_login, true );
          $userlist =  array_merge($nonusers, $userlist);
        }
        for ( $i = 0; $i < count ( $userlist ); $i++ ) {
          if ( $this->_userId == $userlist[$i]['cal_login_id'] ) $valid_user = true;
        } 
        if ($valid_user == false) { 
          $this->_userId = ''; // security precaution
        }
      }

      if ( $this->_userId ) {
        $u_url = 'user=' . $this->_userId  . '&amp;';
        //$this->User->loadVariables ( $this->_userId, 'user_' );
      } else {
        $u_url = '';
        $user_fullname = $fullname;
      }
      
      remember_this_view();

      if ( getPref ( '_ENABLE_CATEGORIES', 2 ) ) {
        if ( ! empty ( $CATEGORY_VIEW ) && ! $this->_cat_id ) {
          $this->_cat_id = $CATEGORY_VIEW;
        }
      }
      $caturl = ( $this->_cat_id ? '&amp;cat_id=' . $this->_cat_id : '' );
      $smarty->assign( 'caturl', $caturl );
      $smarty->assign('cat_id', $this->catId() );
    }
  }

  /**
   * Initializations from includes/assert.php.
   *
   * @access private
   */
  function _initAssert() {
    //Setup callback function only if settings.php mode == dev
    if ( _WC_RUN_MODE == 'dev' ) {
      assert_options ( ASSERT_CALLBACK, 'assert_handler' );
      assert_options ( ASSERT_ACTIVE, 1 );
    }
  }

  /**
   * Initialize values from settings.php into defines
   *
   * @access private
   */
  function _initConfig() {
    
    do_config ( $this->absolutePath ( 'includes/settings.php' ) );

    //Do include here to allow login var to be set
    $incDir =  ( defined ( '_WC_INCLUDE_DIR' ) ? _WC_INCLUDE_DIR : 'includes/' );
    include $incDir . 'classes/user/' . _WC_USER_INC . '.class.php';
    //We need to init the defines inside the current User class
    //We'll assign variables later in _initUser()
    $inc_class = _WC_USER_INC;
    $this->User =& new $inc_class;
         
    /**#@+
     * Used for activity log
     */
    define ( 'LOG_CREATE',        'CR' );
    define ( 'LOG_APPROVE',       'AP' );
    define ( 'LOG_REJECT',        'RE' );
    define ( 'LOG_UPDATE',        'UP' );
    define ( 'LOG_DELETE',        'DE' );
    define ( 'LOG_CREATE_T',      'CT' );
    define ( 'LOG_APPROVE_T',     'AT' );
    define ( 'LOG_REJECT_T',      'RT' );
    define ( 'LOG_UPDATE_T',      'UT' );
    define ( 'LOG_DELETE_T',      'DT' );
    define ( 'LOG_CREATE_J',      'CJ' );
    define ( 'LOG_APPROVE_J',     'AJ' );
    define ( 'LOG_REJECT_J',      'RJ' );
    define ( 'LOG_UPDATE_J',      'UJ' );
    define ( 'LOG_DELETE_J',      'DJ' );
    define ( 'LOG_NOTIFICATION',  'N' );
    define ( 'LOG_REMINDER',      'RM' );
    define ( 'LOG_NEWUSER_FULL',  'NF' );
    define ( 'LOG_NEWUSER_EMAIL', 'NE' );
    define ( 'LOG_ATTACHMENT',    'AA' );
    define ( 'LOG_COMMENT',       'AC' );
    define ( 'LOG_LOGIN_FAILURE', 'LF' );
    define ( 'LOG_USER_ADD',      'UA' );
    define ( 'LOG_USER_DELETE',   'UD' );
    define ( 'LOG_USER_UPDATE',   'UU' );
    /**#@-*/

    /**
     * Checked used by numerous pages
     */
    define ( 'CHECKED', ' checked="checked" ' );

    /**
     * Selected used by numerous pages
     */
    define ( 'SELECTED', ' selected="selected"' );
    

    /**
     * Disabled used by numerous pages
     */
    define ( 'DISABLED', ' disabled="disabled" ' );
        
    /**
     * Number of seconds in an hour
     */
    define ( 'ONE_HOUR', 3600 );

    /**
     * Number of seconds in a day
     */
    define ( 'ONE_DAY', 86400 );

    /**
     * Number of seconds in a week
     */
    define ( 'ONE_WEEK', 604800 );
  
    /**
     * Array containing the short names for the days of the week
     *
     * @global array $weekday_names
     */
    $this->weekday_names =  array ( 'Sun','Mon','Tue','Wed','Thu','Fri','Sat' );

     
    /**
     * Array containing the BYDAY names for the days of the week
     *
     * @global array $byday_name
     */ 
   $this->byday_names =  array ( 'SU','MO','TU','WE','TH','FR','SA' );


  /**
   * Array containing the number value of the ical ByDay abbreviations
   *
   * @global array $byday_values
   */
  $this->byday_values =  array (
   'SU' => 0,
   'MO' => 1,
   'TU' => 2,
   'WE' => 3,
   'TH' => 4,
   'FR' => 5,
   'SA' => 6
  );

    /**
     * Array of global variables which are not allowed to by set via HTTP GET/POST
     *
     * This is a security precaution to prevent users from overriding any global
     * variables
     *
     */
    $noSet = array (
      'login' => 1,      
      'is_admin' => 1,
      'can_add' => 1,
      'can_approve' => 1,
      'can_edit' => 1,
      'can_edit' => 1,
      'can_invite' => 1,      
      'languages' => 1,
      'browser_languages' => 1
    );
    
    if ( ! empty ( $_GET ) ) {
      while (list($key, $val) = @each($_GET)) {
        // don't allow anything to have <script> in it...
        if ( ! is_array ( $val ) ) {
          if ( preg_match ( "/<\s*script/i", $val ) ) {
            echo 'Security violation!'; exit;
          }
        }
        if ( $key == 'login' ) {
          if ( ! strstr ( $_SERVER['PHP_SELF'], 'nulogin.php' ) ) {
            $_GET[$key] = NULL;
          }
        } else if ( ! empty ( $noSet[$key] ) ) {
          $_GET[$key] = NULL;
        }
        //echo "GET var '$key' = '$val' <br />\n";
      }
    }
    if ( ! empty ( $_POST ) ) {
      while (list($key, $val) = @each($_POST)) {
        // don't allow anything to have <script> in it... except 'template'
        if ( ! is_array ( $val ) && $key != 'template' ) {
          if ( preg_match ( "/<\s*script/i", $val ) ) {
            echo 'Security violation!'; exit;
          }
        }
        if ( $key == 'login' ) {
          if ( ! strstr ( $_SERVER['PHP_SELF'], 'login.php' ) ) {
            $_POST[$key] = NULL;
          }
        } else if ( ! empty ( $noSet[$key] ) ) {
          $_POST[$key] = NULL;
        }
      }

    }
    //while (list($key, $val) = @each($_FILES)) {
    //       $GLOBALS[$key] = $val;
    //}
    //while (list($key, $val) = @each($_SESSION)) {
    //       $GLOBALS[$key] = $val;
    //}
    if ( ! empty ( $_COOKIE ) ) {
      while ( list($key, $val) = @each($_COOKIE )) {
        if ( substr($key,0,12) == "webcalendar_" ) {
          if ( ! empty ( $noSet[$key] ) ) {
            $_COOKIE[$key] = NULL;
          } else {
            $keyvar = '_' . $key;
            $this->$keyvar = $val;
          }
        }
        //echo "COOKIE var '$key' = '$val' <br />\n";
      }
    }

    // Define an array to use to jumble up the key: $this->_offsets
    // We define a unique key to scramble the cookie we generate.
    // We use the admin install password that the user sets to make
    // the salt unique for each WebCalendar install.
    $salt = ( defined ( '_WC_INSTALL_PASSWORD' ) ? 
      _WC_INSTALL_PASSWORD : md5 ( _WC_DB_LOGIN ) );
 
    $salt_len = strlen ( $salt );

    $salt2 = md5 ( _WC_DB_PASSWORD ? _WC_DB_PASSWORD : 'oogabooga' );
    $salt2_len = strlen ( $salt2 );

    for ( $i = 0; $i < $salt_len || $i < $salt2_len; $i++ ) {
      $this->_offsets[$i] = 0;
      if ( $i < $salt_len )
        $this->_offsets[$i] += ord ( substr ( $salt, $i, 1 ) );
      if ( $i < $salt2_len )
        $this->_offsets[$i] += ord ( substr ( $salt2, $i, 1 ) );
      $this->_offsets[$i] %= 128;
    }
    /* debugging code...
    for ( $i = 0; $i < count ( $this->_offsets ); $i++ ) {
      echo "offset $i: $this->_offsets[$i] <br />\n";
    }
    */
      
    // Logout/Login URL
    if ( ! _WC_HTTP_AUTH && !_WC_SINGLE_USER ) {
      $this->_login_url = 'login.php';
      $this->_logout_url = $this->_login_url . '?action=logout';
      // Should we use another application's login/logout pages?
      if ( substr( _WC_USER_INC, 0, 7 ) == 'UserApp' ) {
        $this->_login_url = 'applogin.php';
        if ($this->User->app_login_page['return'] )
           $this->_login_url .= '?return_path=' 
             . $this->User->app_login_page['return'];
        $this->_logout_url = $this->User->app_logout_page;
      } else if ( $this->User->loginReturnPath() ) {
        $this->_login_url .= '?return_path=' 
          . $this->User->loginReturnPath();
      }
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

    $this->_initLoginId ( $this->_login );  
        //do_debug ( _WC_SCRIPT );
        //do_debug ( $this->_login );
        //do_debug ( $this->_loginId );     
    //Set up some useful values
    if ( $this->_userId )
      $this->_isNonuserAdmin = 
      $this->isNonuserAdmin ( $this->_userId, $this->_loginId );

    if ( $this->_loginId ) {
      $loginData = $this->User->loadVariables ( $this->_loginId );
      if ( ! empty ( $loginData ['login_id'] ) ) {
        $this->_isAdmin   = $loginData ['is_admin'] == 'Y' ? true : false;
        $this->lastName  = $loginData ['lastname'];
        $this->firstName = $loginData ['firstname'];
        $this->fullName  = $loginData ['fullname'];
        $this->userEmail = $loginData ['email'];
        $this->_isNonuser = $loginData ['is_nonuser'] == 'Y' ? true : false;
      }
    } else {
      if (  _WC_SCRIPT == 'icalclient.php' )
        return;
        // This shouldn't happen since login should already be valid
        // If it does happen, it means we received an invalid login cookie.
        //echo "Error getting user info for login \"$this->_login\".";
        do_redirect ( $this->_login_url . "?error=Invalid+session+found." );
    }

  }

  /**
   * Validates the user via the $_SESSION data or cookies
   *
   * @access private
   */
  function _initValidate() {
    global  $PHP_AUTH_USER, $REMOTE_USER,  $c,
      $login_return_path;

    /* If WebCalendar is configured to use http authentication, then we can
     * use _initValidate().  If we are not using http auth, icalclient.php will
     * create its own http auth since an iCal client cannot login via a
     * web-based login. Publish.php does need to validate if not http_auth
     */
    if ( ( $this->_filename == 'icalclient.php' || 
      $this->_filename == 'publish.php' ) 
      && ! _WC_HTTP_AUTH ) {
      return;
    }
    $validate_redirect = false;


    // Catch-all for getting the username when using HTTP-authentication
    if ( _WC_HTTP_AUTH ) {
      if ( empty ( $PHP_AUTH_USER ) ) {
        if ( !empty ( $_SERVER ) && isset ( $_SERVER['PHP_AUTH_USER'] ) ) {
          $PHP_AUTH_USER = $_SERVER['PHP_AUTH_USER'];
        } else if ( isset ( $REMOTE_USER ) ) {
          $PHP_AUTH_USER = $REMOTE_USER;
        } else if ( !empty ( $_ENV ) && isset ( $_ENV['REMOTE_USER'] ) ) {
          $PHP_AUTH_USER = $_ENV['REMOTE_USER'];
        } else if ( @getenv ( 'REMOTE_USER' ) ) {
          $PHP_AUTH_USER = getenv ( 'REMOTE_USER' );
        } else if ( isset ( $AUTH_USER ) ) {
          $PHP_AUTH_USER = $AUTH_USER;
        } else if ( !empty ( $_ENV ) && isset ( $_ENV['AUTH_USER'] ) ) {
          $PHP_AUTH_USER = $_ENV['AUTH_USER'];
        } else if ( @getenv ( 'AUTH_USER' ) ) {
          $PHP_AUTH_USER = getenv ( 'AUTH_USER' );
        }
      }
    }
    if ( _WC_SINGLE_USER ) {
      $this->_initLogin ( _WC_SINGLE_USER_LOGIN );
    } else {
      if ( _WC_HTTP_AUTH ) {
        // HTTP server did validation for us....
        if ( ! empty ( $PHP_AUTH_USER ) )
          $this->_initLogin ( $PHP_AUTH_USER );

      } elseif ( substr( _WC_USER_INC, 0, 7 ) == 'UserApp' ) {
        // Make sure we are connected to the database for session check
        $c = @dbi_connect ( _WC_DB_HOST, _WC_DB_LOGIN, 
          _WC_DB_PASSWORD, _WC_DB_DATABASE );
        if ( ! $c ) {
          die_miserable_death (
            'Error connecting to database:<blockquote>' .
            dbi_error () . "</blockquote>\n" );
        }
        // Use another application's authentication
        $this->_login = $this->User->user_logged_in();
      } else {
        //Using WebCalendar Auth
        //Test  for SESSION first, then decode COOKIE if needed
        @session_start ();
        if ( ! empty ( $_SESSION['webcalendar_session'] ) ) {
          $this->_webcalendar_session = $_SESSION['webcalendar_session'];
        }
        if ( ! empty ( $_SESSION['webcalendar_login'] ) ) {
          $this->_initLogin ( $_SESSION['webcalendar_login'] );
          $this->_initLoginId ( $_SESSION['webcalendar_login'] );
        } else {
          // Check for cookie...
          if ( $this->_webcalendar_session ) {
            $login_pw = split('\|', 
              $this->_decode_string ( $this->_webcalendar_session ) );
            $this->_login = $login_pw[0];
            $cryptpw = $login_pw[1];
            // Security fix.  Don't allow certain types of characters in
            // the login.  WebCalendar does not escape the login name in
            // SQL requests.  So, if the user were able to set the login
            // name to be "x';drop table u;",
            // they may be able to affect the database.
            if ( ! empty ( $this->_login ) ) {
              if ( $this->_login != addslashes ( $this->_login ) ) {
                SetCookie ( 'webcalendar_session', '', 0 );
                die_miserable_death ( 'Illegal characters in login ' .
                  '<tt>' . htmlentities ( $this->_login ) . '</tt>' );
              }
            }
            // make sure we are connected to the database for password check
            $c = @dbi_connect ( _WC_DB_HOST, _WC_DB_LOGIN, 
             _WC_DB_PASSWORD, _WC_DB_DATABASE );
            if ( ! $c ) {
              die_miserable_death (
                'Error connecting to database:<blockquote>' .
                dbi_error () . "</blockquote>\n" );
            }
            $this->_doDbSanityCheck ();
            if ( $cryptpw == 'nonuser' ) {
              if ( ! $nucData = $this->User->loadVariables ( $this->_login ) ) {
                // no such nonuser cal
                die_miserable_death ( 'Invalid nonuser calendar' );
              }
              if ( $nucData['is_public'] != 'Y' ) {
                die_miserable_death ( 'Nonuser calendar is not public' );
              }
              $this->_loginId = $nucData['login_id'];
            } else if (! $this->User->validCrypt ( $this->_login, $cryptpw)) {
              //do_debug ( "User not logged in; redirecting to login page" );
              if ( empty ( $login_return_path ) )
                do_redirect ( 'login.php' );
              else
                do_redirect ( "login.php?return_path=$login_return_path" );
            }
            @session_start ();
            $_SESSION['webcalendar_session'] = $this->_webcalendar_session;
            $_SESSION['webcalendar_login'] = $this->_login;
            //do_debug ( "Decoded login from cookie: " .$this->_login );
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
    global $c, $PHP_AUTH_USER,
      $not_auth;

    // Establish a database connectio
    if ( empty ( $c ) ) {
      $c = dbi_connect ( _WC_DB_HOST, _WC_DB_LOGIN, 
      _WC_DB_PASSWORD, _WC_DB_DATABASE );
      if ( ! $c ) {
        die_miserable_death (
          'Error connecting to database:<blockquote>' .
          dbi_error () . "</blockquote>\n" );
      }
      // Do a sanity check on the database, making sure we can
      // at least access the webcal_config table.
      $this->_doDbSanityCheck ();
   
    }

    // If we are in single user mode, make sure that the login selected is
    // a valid login.
    if ( _WC_SINGLE_USER ) {
      if ( ! _WC_SINGLE_USER_LOGIN  ) {
        die_miserable_death ( 'You have not defined <tt>single_user_login</tt> 
          <tt>includes/settings.php</tt>' );
      }
      $res = dbi_execute ( "SELECT COUNT(*) FROM webcal_user " .
        "WHERE cal_login = ?", array( _WC_SINGLE_USER_LOGIN ) );
      if ( ! $res ) {
        echo 'Database error: ' . dbi_error (); exit;
      }
      $row = dbi_fetch_row ( $res );
      if ( $row[0] == 0 ) {
        // User specified as single_user_login does not exist
        if ( ! dbi_execute ( "INSERT INTO webcal_user ( cal_login, " .
          "cal_passwd, cal_is_admin ) VALUES ( ?, ?, ? )", 
          array( _WC_SINGLE_USER_LOGIN, md5(_WC_SINGLE_USER_LOGIN), 'Y' ) ) ) {
          die_miserable_death ( 'User <tt>' . _WC_SINGLE_USER. '</tt> does not ' .
            'exist in <tt>webcal_user</tt> table and was not able to add ' .
            'it for you:<br /><blockquote>' .
            dbi_error () . "</blockquote>" );
        }
        // User was added... should we tell them?
      }
      dbi_free_result ( $res );
    }
    
    if ( ! $this->_loginId && ! _WC_HTTP_AUTH ) {
      if ( substr( _WC_USER_INC, 0, 7 ) == 'UserApp' ) {
        app_login_screen( clean_whitespace( _WC_SCRIPT ) );
      } else if ( ! strstr ( $_SERVER['PHP_SELF'], 'login.php' ) ){ 
        do_redirect ( $this->_login_url );
        exit;
      }
    }
    $this->_isNonuser = false;

    if ( empty ( $this->_login ) && _WC_HTTP_AUTH 
      && _WC_SCRIPT != 'login.php' ) {
      send_http_login ();
    }
  }

  /**
   * Initializations from includes/access.php.
   *
   * @access private
   */
  function _initAccess() {
    global $access_other_cals;

    // Global variable used to cache permissions
    $access_other_cals = array ();
    
    //Allow UAC to override settings.php READONLY value
    define ( '_WC_READONLY', _WC_READ_ONLY_PROTO && 
      ! access_can_access_function ( ACCESS_READONLY ) );
      
    $this->_can_add = ! _WC_READONLY && 
  access_can_access_function ( ACCESS_EVENT_EDIT, $this->userId() );
  }

  /**
   * Initializations from includes/translate.php.
   *
   * @access private
   */
  function _initTranslate() {
    global $smarty, $lang, $lang_file, $translation_loaded;

    $this->_browserLanguage = get_browser_language ( true );
    
    $lang = getPref ( 'LANGUAGE', 1, '', 'English-US' );

    // If set to use browser settings, use the user's language preferences
    // from their browser.
    if ( $lang == 'Browser-defined' )
      $lang = $this->_browserLanguage;
     
    if ( strlen ( $lang ) == 0 || $lang == 'none' ) {
      $lang = 'English-US'; // Default
    }

    $lang_file = 'translations/' . $lang . '.txt';
    
    $this->_lang = $lang;
    
    $smarty->compile_id = $lang;
    
    reset_language ( $lang);
    
    //$translation_loaded = false;
  }

  /**
   * Gets the initialization phases for the page being viewed.
   *
   * @return array Array of initialization phases.
   *
   * @access private
   */
  function _getPhases() {

    foreach ( $this->_filePhaseMap as $pattern => $phases ) {
      if ( preg_match ( $pattern, $this->_filename ) !== 0 ) {
        return $phases;
      }
    }
    die_miserable_death ( "_getPhases: cound not find '" .
      $this->_filename . "' in _filePhaseMap" );
  }

  /**
   * Gets the initialization steps for the current page and phase.
   *
   * @param int $phase Initialization phase number
   *
   * @return array Array of initialization steps.
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
   * @param int $phase Which step of initialization should we perform?
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
  
  function _initLogin ( $name ) {
    if ( $name ) {
      $this->_login = $name;
      return true;
    }
    return false;
  }

  function _initLoginId ( $name ) {
    $name_id = $this->User->getUserId ( $name );
    if ( $name_id ) {
      $this->_loginId = $name_id;
      return true;
    }
    return false;
  }
  
  /**
   * Begins initialization of WebCalendar.
   *
   * @param string $path Full path of page being viewed
   *
   * @access public
   */
  function initializeFirstPhase() {
    $this->_doInit ( 1 );
  }

  /**
   * Continues initialization of WebCalendar.
   *
   * @param string $path Full path of page being viewed
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

/* Loads current user's category info and stuff it into category global variable.
 *
 * @param bool $ex_global Don't include global categories
 */
function loadCategories ( $ex_global = false ) {
  global  $smarty, $is_assistant;

  $categories = array ();
  // These are default values.
  $categories[0]['cat_name'] = translate ( 'All' );
  $categories[-1]['cat_name'] = translate ( 'None' );
  if ( getPref ( '_ENABLE_CATEGORIES' ) ) {
    $query_params = array ();
    $query_params[] = ( $this->_userId &&
      ( $is_assistant || $this->isAdmin ) ? $this->_userId : $this->_loginId );
    $rows = dbi_get_cached_rows ( 'SELECT cat_id, cat_name, 
      cat_owner, cat_color, cat_icon
      FROM webcal_categories WHERE ( cat_owner = ? ) ' . ( ! $ex_global 
        ? 'OR ( cat_owner IS NULL ) ORDER BY cat_owner,' : 'ORDER BY' )
       . ' cat_name', $query_params );
    if ( $rows ) {
      for ( $i = 0, $cnt = count ( $rows ); $i < $cnt; $i++ ) {
        $row = $rows[$i];
        $categories[$row[0]] = array (
          'cat_name' => $row[1],
          'cat_owner' => $row[2],
          'cat_color' => ( empty ( $row[3] ) ? '#000000' : $row[3] ),
          'cat_icon' => $row[4],
          );
      }
    }
  $this->_categories = $categories;
  $smarty->assign('categories', $categories );
  }
}

function isMyCat ( $cid='' ) {
  if ( !$cid ) return true;
  return ($this->_categories[$cid]['cat_owner'] == $this->_loginId ?
    true : false );
}

function deleteCat ( $cid='' ) {
  if ( ! $this->isMyCat ( $cid )) return false;
  dbi_execute ( 'DELETE FROM webcal_categories WHERE cat_id = ?' ,
    array ( $cid ) );
  dbi_execute ( 'DELETE FROM webcal_entry_categories WHERE cat_id = ?' ,
    array ( $cid ) );
}

/* Determines what the day is and sets class values.
 * All times are in the user's timezone
 *
 * @param string $date  date YYYYMMDD format
 */
function setToday ( $date='' ) {

  $this->today = mktime ();
  $this->todayYmd = date ( 'Ymd', $this->today );
  
  if ( empty ( $date ) || strlen ( $date ) != 8 )
    $this->_date = $date = date ( 'Ymd', $this->today );

  $this->thisyear  = ( !$this->_year  ? 
    substr ( $date, 0, 4 ) : $this->_year );
  $this->thismonth = ( !$this->_month ? 
    substr ( $date, 4, 2 ) : $this->_month );
  $this->thisday   = ( !$this->_day   ? 
    substr ( $date, 6, 2 ) : $this->_day );

  $this->thisdate = sprintf ( "%04d%02d%02d", 
    $this->thisyear, $this->thismonth, $this->thisday );
}

/* Takes an input string and encode it into a slightly encoded hexval that we
 * can use as a session cookie.
 *
 * @param string $instr  Text to encode
 *
 * @return string  The encoded text.
 *
 * @see _decode_string
 */
 function encode_string ( $instr ) {

   $cntOffsets = count ( $this->_offsets );
   $ret = '';
   for ( $i = 0, $cnt = strlen ( $instr ); $i < $cnt; $i++ ) {
     $ret .= bin2hex ( chr ( ( ord ( substr ( $instr, $i, 1 ) ) + 
     $this->_offsets[ $i % $cntOffsets ] ) % 256 ) );
   }
   return $ret;
 }

/* Extracts a user's name from a session id.
 *
 * This prevents users from begin able to edit their cookies.txt file and set
 * the username in plain text.
 *
 * @param string $instr  A hex-encoded string. "Hello" would be "678ea786a5".
 *
 * @return string  The decoded string.
 *
 * @see encode_string
 */
 function _decode_string ( $instr ) {

   $cntOffsets = count ( $this->_offsets );
   $orig = '';
   for ( $i = 0, $cnt = strlen ( $instr ); $i < $cnt; $i += 2 ) {
     $orig .= chr (
       ( hexdec  ( substr ( $instr, $i, 1 ) ) * 16 +
         hexdec  ( substr ( $instr, $i + 1, 1 ) ) - $this->_offsets[
         ( $i / 2 ) % $cntOffsets ] + 256 ) % 256 );
   }
   return $orig;
 }
  /**
   * Construct an absolute path.
   *
   * @param string $path The path relative to the WebCalendar install directory
   *
   * @return string The absolute path
   */
  function absolutePath ( $path ) {
    return $this->_directory . $path;
  }
  
  function _doDbSanityCheck () {
    $dieMsgStr = 'Error finding WebCalendar tables in database "' 
        . _WC_DB_DATABASE . '" using db login "' 
        . _WC_DB_LOGIN . '" on db server "' 
        . _WC_DB_HOST . '".<br /><br />
    Have you created the database tables as specified in the
    <a href="docs/WebCalendar-SysAdmin.html" '
       . '  target="other">WebCalendar System Administrator\'s Guide</a>?';
       
      $res = @dbi_execute ( 'SELECT COUNT( cal_value ) FROM webcal_config',
        array (), false, false );
      if ( $res ) {
        if ( $row = dbi_fetch_row ( $res ) ) 
          // Found database.  All is peachy.
          dbi_free_result ( $res );
        else {
          // Error accessing table.
          // User has wrong db name or has not created tables.
          // Note: can't translate this since translate.php is not included yet.
          dbi_free_result ( $res );
          die_miserable_death ( $dieMsgStr );
        } 
      } else
        die_miserable_death ( $dieMsgStr );
  } 
  
  /**
   * Return the current logged in username
   *
   * @return string The login value
   */
  function login () {
    return ( ! empty ( $this->_login ) ? $this->_login : false );
  }

  /**
   * Return the current logged in ID
   *
   * @return int The user's ID
   */
  function loginId () {
    return ( ! empty ( $this->_loginId ) ? $this->_loginId : false );
  } 
  
   
  /**
   * Return the username of the user specified by user= in the URL
   *
   * @return string The login value
   */
  function user () {
    return ( ! empty ( $this->_user ) ? $this->_user : false );
  }
  
    /**
   * Return the fullname of the user specified
   *
   * @return string The user's fullname
   */
  function getFullName ( $user_id='' ) {
    $user = ( empty ( $user_id ) ? $this->_loginId : $user_id );
    return $this->User->getFullName( $user );
  }

  /**
   * Return the user in a URL format for insertion in href
   *
   * @return string The user url 
   */
  function getUserUrl ( $url='' ) {
    $seperator = ( ! empty ( $url ) ? strstr ( $url, '?') ? '&amp;' : '?' : '' ); 
    if ( $url == '?' || $url == '&amp;' ) $seperator = $url;
    return ( ! empty ( $this->_userId ) && ! $this->isLogin()
    ? $seperator . 'user='. $this->_userId : false );
  }
  
  /**
   * Return the ID of the user specified by user= in the URL
   *
   * @return int The user's ID
   */
  function userId () {
    return ( ! empty ( $this->_userId ) ? $this->_userId : false );
  } 
   
    /**
   * Compare _login to another variable
   *
   * @return bool true if _login_id == $test
   */
  function isLogin ( $test='' ) {
    if ( empty ( $test ) )
      $test = $this->_userId;
    return ( $this->_loginId == $test ? true : false );
  }

    /**
   * Test for user not empty and not login
   *
   * @return bool $unique true if user is unique
   */
  function isUser ( $unique=true ) {
    if ( $unique )
      return ( ! empty ( $this->_userId ) && 
        $this->_userId != $this->_loginId ? true : false );
    else 
      return ( empty ( $this->_userId ) || 
        $this->_userId == $this->_loginId ? true : false );    
  }
 
     /**
   * Test for login being a non-user
   *
   * @return bool  true if user is non-user
   */
  function isNonUser ( ) {
      return $this->_isNonUser;    
  }
  
  function isNonuserAdmin ( $nonuser_id='', $login_id='' ){
  //if called with no parameters, return the basic value
  if ( empty ( $nonuser_id ) && empty ( $login_id ) )
    return $this->_isNonuserAdmin;
  $nonuser_id = ( ! empty ( $nonuser_id ) ? $nonuser_id : $this->_userId );
  $login_id = ( ! empty ( $login_id ) ? $login_id : $this->_loginId );
  if ( $user_id ) { 
    $rows = dbi_get_cached_rows ( 'SELECT cal_admin FROM webcal_user
      WHERE cal_login_id = ? AND cal_admin = ?', 
      array ( $nonuser_id, $login_id ) );
    return ( $rows && ! empty ( $rows[0] ) ? true : false );
  }
}
 
  /**
   * Return user_id if not empty else login_id
   *
   * @return int user_id or login_id
   */  
  function userLoginId () {
    return ( ! empty ( $this->_userId ) ? $this->_userId : $this->_loginId );
  }
  
  /**
   * Return the cat_id specified in the URL
   *
   * @return int cat_id
   */  
  function catId () {
    return ( ! empty ( $this->_cat_id) ? $this->_cat_id : false );
  }

  /**
   * Return the admin status of the logge din user
   *
   * @return bool _isAdmin
   */  
  function isAdmin () {
    return $this->_isAdmin;
  }
  
  /**
   * Return the cat_id in a URL format for insertion in href
   *
   * @return string The user url 
   */
  function getCatUrl () {
    return ( ! empty ( $this->_cat_id ) ? '&amp;cat_id='. $this->_cat_id : false );
  }  
   /**
   * Return the id specified in the URL
   *
   * @return int id
   */  
  function getId () {
    return ( ! empty ( $this->_eid ) ? $this->_eid : false );
  }
  
  /**
   * Return the date specified in the URL
   *
   * @return int  date
   */  
  function getDate () {
    return $this->_date;
  }
  
   /**
   * Return the current Language 
   *
   * @return string lang
   */  
  function lang () {
    return $this->_lang;
  }
     /**
   * Return the browser's language setting 
   *
   * @return string brower language
   */  
  function browserLang () {
    return $this->_browserLanguage;
  }
 
   /**
   * Return the current value of friendly
   *
   * @return bool 
   */  
  function friendly () {
    return ( $this->_friendly == 1 ? true : false );
  }
  
   /**
   * Return the current value of _can_add
   *
   * @return bool 
   */  
  function canAdd () {
    return $this->_can_add;
  }
  
   /**
   * Return the categories array for this user
   *
   * @return array 
   */  
  function categories () {
    return $this->_categories;
  }
  
   /**
   * Return the categories array for this user
   *
   * @return array 
   */  
  function closeDb () {
    global $c;
    if ( isset ( $c ) )
      dbi_close ( $c );
    unset ( $c );
  }
  
    function getStartDate() {
    return $this->_startdate;
  }
  
    function getEndDate () {
    return $this->_enddate;
  }


/* Gets the value resulting from an HTTP POST method.
 *
 * <b>Note:</b> The return value will be affected by the value of
 * <var>magic_quotes_gpc</var> in the php.ini file.
 *
 * @param string $name Name used in the HTML form
 * @param string  $default  Value to return if NULL
 *
 * @return string The value used in the HTML form
 *
 * @see getGetValue
 */
function getPOST ( $name, $default=NULL, $format = '' ) {
  $postName = $default;
  if ( isset ( $_POST ) && is_array ( $_POST ) && ! empty ( $_POST[$name] ) )
    $postName = ( get_magic_quotes_gpc () != 0
      ? $_POST[$name] : addslashes ( $_POST[$name] ) );
  if ( $postName != NULL && ! empty ( $format ) && ! preg_match ( '/^' 
    . $format . '$/', $postName ) ) {
    // ignore value
    return '';
  }
  return $postName;
}

/* Gets the value resulting from an HTTP GET method.
 *
 * Since this function is used in more than one place, with different names,
 * let's make it a separate 'include' file on it's own.
 *
 * <b>Note:</b> The return value will be affected by the value of
 * <var>magic_quotes_gpc</var> in the php.ini file.
 *
 * If you need to enforce a specific input format (such as numeric input), then
 * use the {@link getValue()} function.
 *
 * @param string  $name  Name used in the HTML form or found in the URL
 * @param string  $default  Value to return if NULL
 *
 * @return string        The value used in the HTML form (or URL)
 *
 * @see getPostValue
 */
function getGET ( $name, $default=NULL ) {
  $getName = $default;
  if ( isset ( $_GET ) && is_array ( $_GET ) && ! empty ( $_GET[$name] ) )
    $getName = ( get_magic_quotes_gpc () != 0
      ? $_GET[$name] : addslashes ( $_GET[$name] ) );
  return $getName;
}

/* Gets the value resulting from either HTTP GET method or HTTP POST method.
 *
 * <b>Note:</b> The return value will be affected by the value of
 * <var>magic_quotes_gpc</var> in the php.ini file.
 *
 * <b>Note:</b> If you need to get an integer value, you can use the
 * getIntValue function.
 *
 * @param string $name   Name used in the HTML form or found in the URL
 * @param string $format A regular expression format that the input must match.
 *                       If the input does not match, an empty string is
 *                       returned and a warning is sent to the browser.  If The
 *                       <var>$fatal</var> parameter is true, then execution
 *                       will also stop when the input does not match the
 *                       format.
 * @param bool   $fatal  Is it considered a fatal error requiring execution to
 *                       stop if the value retrieved does not match the format
 *                       regular expression?
 *
 * @return string The value used in the HTML form (or URL)
 *
 * @uses getGetValue
 * @uses getPostValue
 */
function getValue ( $name, $format = '', $fatal = false ) {

  $val = $this->getPOST ( $name );
  if ( ! isset ( $val ) )
    $val = $this->getGET ( $name );
  if ( ! isset ( $val ) )
    return '';
  if ( ! empty ( $format ) && ! preg_match ( '/^' . $format . '$/', $val ) ) {
    // does not match
    if ( $fatal ) {
      die_miserable_death ( translate ( 'Fatal Error' ) . ': '
         . translate ( 'Invalid data format for' ) . ' ' . $name . ' ' . $val );
    }
    // ignore value
    return '';
  }
  return $val;
}

/* Gets an integer value resulting from an HTTP GET or HTTP POST method.
 *
 * <b>Note:</b> The return value will be affected by the value of
 * <var>magic_quotes_gpc</var> in the php.ini file.
 *
 * @param string $name  Name used in the HTML form or found in the URL
 * @param bool   $fatal Is it considered a fatal error requiring execution to
 *                      stop if the value retrieved does not match the format
 *                      regular expression?
 *
 * @return string The value used in the HTML form (or URL)
 *
 * @uses getValue
 */
function getIntValue ( $name, $fatal=false ) {
  return $this->getValue ( $name, '-?[0-9]+', $fatal );
}
  
 
}
?>
