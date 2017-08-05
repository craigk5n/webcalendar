<?php // $Id: default_config.php,v 1.75 2009/11/22 16:47:47 bbannon Exp $
/**
 * The file contains a listing of all the current WebCalendar config settings and their default values.
 */
$webcalConfig = [
  'ADD_LINK_IN_VIEWS' => 'N',
  'ADMIN_OVERRIDE_UAC' => 'Y',
  'ALLOW_ATTACH' => 'N',
  'ALLOW_ATTACH_ANY' => 'N',
  'ALLOW_ATTACH_PART' => 'N',
  'ALLOW_COLOR_CUSTOMIZATION' => 'Y',
  'ALLOW_COMMENTS' => 'N',
  'ALLOW_COMMENTS_ANY' => 'N',
  'ALLOW_COMMENTS_PART' => 'N',
  'ALLOW_CONFLICTS' => 'N',
  'ALLOW_CONFLICT_OVERRIDE' => 'Y',
  'ALLOW_EXTERNAL_HEADER' => 'N',
  'ALLOW_EXTERNAL_USERS' => 'N',
  'ALLOW_HTML_DESCRIPTION' => 'Y',
  'ALLOW_SELF_REGISTRATION' => 'N',
  'ALLOW_USER_HEADER' => 'N',
  'ALLOW_USER_THEMES' => 'Y',
  'ALLOW_VIEW_OTHER' => 'Y',
  'APPLICATION_NAME' => 'Title',
  'APPROVE_ASSISTANT_EVENT' => 'Y',
  'AUTO_REFRESH' => 'N',
  'AUTO_REFRESH_TIME' => '0',
  'BGCOLOR' => '#FFFFFF',
  'BGIMAGE' => '',
  'BGREPEAT' => 'repeat fixed center',
  'BOLD_DAYS_IN_YEAR' => 'Y',
  'CAPTIONS' => '#B04040',
  'CATEGORIES_ENABLED' => 'Y',
  'CELLBG' => '#C0C0C0',
  'CONFLICT_REPEAT_MONTHS' => '6',
  'CUSTOM_HEADER' => 'N',
  'CUSTOM_SCRIPT' => 'N',
  'CUSTOM_TRAILER' => 'N',
  'DATE_FORMAT' => 'LANGUAGE_DEFINED',
  'DATE_FORMAT_MD' => 'LANGUAGE_DEFINED',
  'DATE_FORMAT_MY' => 'LANGUAGE_DEFINED',
  'DATE_FORMAT_TASK' => 'LANGUAGE_DEFINED',
  'DEMO_MODE' => 'N',
  'DISABLE_ACCESS_FIELD' => 'N',
  'DISABLE_CROSSDAY_EVENTS' => 'N',
  'DISABLE_LOCATION_FIELD' => 'N',
  'DISABLE_PARTICIPANTS_FIELD' => 'N',
  'DISABLE_POPUPS' => 'N',
  'DISABLE_PRIORITY_FIELD' => 'N',
  'DISABLE_REMINDER_FIELD' => 'N',
  'DISABLE_REPEATING_FIELD' => 'N',
  'DISABLE_URL_FIELD' => 'Y',
  'DISPLAY_ALL_DAYS_IN_MONTH' => 'N',
  'DISPLAY_CREATED_BYPROXY' => 'Y',
  'DISPLAY_DESC_PRINT_DAY' => 'Y',
  'DISPLAY_END_TIMES' => 'N',
  'DISPLAY_LOCATION' => 'N',
  'DISPLAY_LONG_DAYS' => 'N',
  'DISPLAY_MINUTES' => 'N',
  'DISPLAY_MOON_PHASES' => 'N',
  'DISPLAY_SM_MONTH' => 'Y',
  'DISPLAY_TASKS' => 'N',
  'DISPLAY_TASKS_IN_GRID' => 'N',
  'DISPLAY_UNAPPROVED' => 'Y',
  'DISPLAY_WEEKENDS' => 'Y',
  'DISPLAY_WEEKNUMBER' => 'Y',
  'EMAIL_ASSISTANT_EVENTS' => 'Y',
  'EMAIL_ATTACH_ICS' => 'N',
  'EMAIL_EVENT_ADDED' => 'Y',
  'EMAIL_EVENT_CREATE' => 'N',
  'EMAIL_EVENT_DELETED' => 'Y',
  'EMAIL_EVENT_REJECTED' => 'Y',
  'EMAIL_EVENT_UPDATED' => 'Y',
  'EMAIL_FALLBACK_FROM' => 'youremailhere',
  'EMAIL_HTML' => 'N',
  'EMAIL_MAILER' => 'mail',
  'EMAIL_REMINDER' => 'Y',
  'ENABLE_CAPTCHA' => 'N',
  'ENABLE_GRADIENTS' => 'N',
  'ENABLE_ICON_UPLOADS' => 'N',
  'ENTRY_SLOTS' => '144',
  'EXTERNAL_NOTIFICATIONS' => 'N',
  'EXTERNAL_REMINDERS' => 'N',
  'FONTS' => 'Arial, Helvetica, sans-serif',
  'FREEBUSY_ENABLED' => 'N',
  'GENERAL_USE_GMT' => 'Y',
  'GROUPS_ENABLED' => 'N',
  'H2COLOR' => '#000000',
  'HASEVENTSBG' => '#FFFF33',
  'IMPORT_CATEGORIES' => 'Y',
  'LANGUAGE' => 'none',
  'LIMIT_APPTS' => 'N',
  'LIMIT_APPTS_NUMBER' => '6',
  'LIMIT_DESCRIPTION_SIZE' => 'N',
  'MENU_DATE_TOP' => 'Y',
  'MENU_ENABLED' => 'Y',
  'MENU_THEME' => 'default',
  'MYEVENTS' => '#006000',
  'NONUSER_AT_TOP' => 'Y',
  'NONUSER_ENABLED' => 'Y',
  'OTHERMONTHBG' => '#D0D0D0',
  'OVERRIDE_PUBLIC' => 'N',
  'OVERRIDE_PUBLIC_TEXT' => 'Not available',
  'PARTICIPANTS_IN_POPUP' => 'N',
  'PLUGINS_ENABLED' => 'N',
  'POPUP_BG' => '#FFFFFF',
  'POPUP_FG' => '#000000',
  'PUBLIC_ACCESS' => 'N',
  'PUBLIC_ACCESS_ADD_NEEDS_APPROVAL' => 'Y',
  'PUBLIC_ACCESS_CAN_ADD' => 'N',
  'PUBLIC_ACCESS_DEFAULT_SELECTED' => 'N',
  'PUBLIC_ACCESS_DEFAULT_VISIBLE' => 'N',
  'PUBLIC_ACCESS_OTHERS' => 'Y',
  'PUBLIC_ACCESS_VIEW_PART' => 'N',
  'PUBLISH_ENABLED' => 'Y',
  'PULLDOWN_WEEKNUMBER' => 'N',
  'REMEMBER_LAST_LOGIN' => 'Y',
  'REMINDER_DEFAULT' => 'N',
  'REMINDER_OFFSET' => '240',
  'REMINDER_WITH_DATE' => 'N',
  'REMOTES_ENABLED' => 'N',
  'REPORTS_ENABLED' => 'N',
  'REQUIRE_APPROVALS' => 'Y',
  'RSS_ENABLED' => 'N',
  'SELF_REGISTRATION_BLACKLIST' => 'N',
  'SELF_REGISTRATION_FULL' => 'Y',
  'SEND_EMAIL' => 'N',
  'SERVER_TIMEZONE' => 'UTC',
  'SITE_EXTRAS_IN_POPUP' => 'N',
  'SMTP_AUTH' => 'N',
  'SMTP_HOST' => 'localhost',
  'SMTP_PASSWORD' => '',
  'SMTP_PORT' => '25',
  'SMTP_USERNAME' => '',
  'STARTVIEW' => 'month.php',
  'SUMMARY_LENGTH' => '80',
  'TABLEBG' => '#000000',
  'TEXTCOLOR' => '#000000',
  'THBG' => '#FFFFFF',
  'THFG' => '#000000',
  'TIMED_EVT_LEN' => 'D',
  'TIMEZONE' => ini_get ( 'date.timezone' ),
  'TIME_FORMAT' => '12',
  'TIME_SLOTS' => '24',
  'TIME_SPACER' => '&raquo;&nbsp;',
  'TODAYCELLBG' => '#FFFF33',
  'UAC_ENABLED' => 'N',
  'UPCOMING_ALLOW_OVR' => 'N',
  'UPCOMING_DISPLAY_CAT_ICONS' => 'Y',
  'UPCOMING_DISPLAY_LAYERS' => 'N',
  'UPCOMING_DISPLAY_LINKS' => 'Y',
  'UPCOMING_DISPLAY_POPUPS' => 'Y',
  'UPCOMING_EVENTS' => '0',
  'USER_PUBLISH_ENABLED' => 'Y',
  'USER_PUBLISH_RW_ENABLED' => 'Y',
  'USER_RSS_ENABLED' => 'N',
  'USER_SEES_ONLY_HIS_GROUPS' => 'Y',
  'USER_SORT_ORDER' => 'cal_lastname, cal_firstname',
  'WEBCAL_PROGRAM_VERSION' => 'v1.3.0',
  'WEEKENDBG' => '#D0D0D0',
  'WEEKEND_START' => '6',
  'WEEKNUMBER' => '#FF6633',
  'WEEK_START' => '0',
  'WORK_DAY_END_HOUR' => '17',
  'WORK_DAY_START_HOUR' => '8',


/* Things either not in the code at all yet,
   or not user ajustable from "admin.php" or "prefs.php".
   
   Or, maybe, just need to be moved to a central location.
   We shouldn't bundle stuff with WebCalendar that we don't maintain.
   We should give users instructions on how to install if they want to use it,
   with SUGGESTED versions. And put in the hooks to use it if we find it.

  "/common/" directory is just for example purposes.
  And it's outside WebCalendar directory structure; ie http://mysite.com/common/
 */

  'ACCESSIFY_FROM'         => '//yatil-cdn.s3.amazonaws.com/accessifyhtml5.min.js', # Put in screen reader compatible ARIA tags.
  'CKEDITOR_FROM'          => '//cdn.ckeditor.com/4.6.0/basic/ckeditor.js',
  'HKIT_FROM'              => '/common/hkit/',
  'JQUERY_FROM'            => '//code.jquery.com/jquery-3.2.1.min.js integrity="sha256-hwg4gsxgFZhOsEEamdOYGBf13FyQuiTwlAQgxVSNgt4=" crossorigin="anonymous"',
  'JSCOOKMENU_FROM'        => '/common/JSCookMenu/',
  'MODALBOX_FROM'          => '/common/modalbox/',
  'NORMALIZE_CSS_FROM'     => '//necolas.github.io/normalize.css/7.0.0/normalize.css',
  'PHPMAILER_FROM'         => '/common/phpmailer/',
  'PROTOTYPEJS_FROM'       => '//ajax.googleapis.com/ajax/libs/prototype/1.7.3.0/prototype.js//ajax.googleapis.com/ajax/libs/prototype/1.7.3.0/prototype.js',
  'RECAPTCHA_FROM'         => '//www.google.com/recaptcha/api.js',
  'SCRIPTACULOUS_FROM'     => '/common/scriptaculous/',
  'SMARTY_FROM'            => '/common/smarty/',
  'TABCONTENT_FROM'        => '/common/tabcontent/',

/* I read recently that plain JavaScript with CSS can do almost everything that
   it took jQuery, or prototype, hkit, modalbox and scriptaculous to do before.
   Unless we still want to support IE6 or Netscape 4.
 */

  'DEFAULT_CHARSET'        => ini_get ( 'default_charset' ),
  'DEFAULT_LATITUDE'       => ini_get ( 'date.default_latitude' ),
  'DEFAULT_LONGITUDE'      => ini_get ( 'date.default_longitude' ),

  'NEXTMONTHBG'            => '#D0FFD0', # Instead of just "other", if users want to differentiate.
  'PREVMONTHBG'            => '#FFD0D0',

  'RECAPTCHA_PUBLIC_KEY'   => '', # From https://www.google.com/recaptcha/admin#list
  'STAY_IN_VIEW'           => 'N',
  'USER_CHANGES_TIMEZONES' => 'N', # Keep track of boss/assistant who travel.
  'YEAR_ROWS'              => '3'];

/**
 * db_load_config (needs description)
 *
 * This function is defined here because admin.php calls it during startup.
 */
function db_load_config() {
  global $webcalConfig;

  $sql = 'INSERT INTO webcal_config ( cal_setting, cal_value ) VALUES ( ?, ? )';

  while( list( $key, $val ) = each( $webcalConfig ) ) {
    $res = dbi_execute( 'SELECT cal_value FROM webcal_config
      WHERE cal_setting = ?', array( $key ), false, false );
    if( ! $res )
      dbi_execute( $sql, array( $key, $val ) );
    else { // SQLite returns $res always.
      $row = dbi_fetch_row( $res );
      if( ! isset( $row[0] ) )
        dbi_execute( $sql, array( $key, $val ) );

      dbi_free_result( $res );
    }
  }
}

?>
