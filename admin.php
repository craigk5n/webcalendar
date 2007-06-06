<?php
/* $Id$ */
$prad = true;

include_once 'includes/init.php';
include_once 'includes/date_formats.php';
include 'includes/common_admin_pref.php';
if ( file_exists ( 'install/default_config.php' ) )
  include_once 'install/default_config.php';

$error = ( $is_admin ? '' : print_not_auth () );

if ( ! empty ( $_POST ) && empty ( $error ) ) {
  $currenttab = getPostValue ( 'currenttab' );
  $my_theme = '';

  save_pref ( $_POST, 'post' );

  if ( ! empty ( $my_theme ) ) {
    include_once 'themes/' . strtolower ( $my_theme ) . '.php';
    save_pref ( $webcal_theme, 'theme' );
  }
}
// .
// Load any new config settings.  Existing ones will not be affected.
// This function is in the install/default_config.php file.
if ( function_exists ( 'db_load_config' ) && empty ( $_POST ) )
  db_load_config ();

print_header (
  array ( 'js/admin.php', 'js/visible.php' ),
  '',
  'onload="init_admin ();'
   . ( empty ( $currenttab ) ? '"' : ' showTab ( \'' . $currenttab . '\' );"' ) );

if ( ! $error ) {
  $checked = ' checked="checked"';
  $select = translate ( 'Select' ) . '...';
  // .
  // Allow css_cache of webcal_config values.
  @session_start ();
  $_SESSION['webcal_tmp_login'] = 'blahblahblah';

  $bottomStr = translate ( 'Bottom' );
  $topStr = translate ( 'Top' );

  $anyoneStr = translate ( 'Anyone' );
  $partyStr = translate ( 'Participant' );

  $saveStr = '
      <input type="submit" value="' . translate ( 'Save' ) . '" name="" />';

  for ( $i = 0, $cnt = count ( $themes ); $i < $cnt; $i++ ) {
    $theme_list .= $option . $themes[$i] . '">' . $themes[$i] . '</option>';
  }
  foreach ( $menuthemes as $menutheme ) {
    $menu_theme_list .= $option . $menutheme . '"'
     . ( $s['MENU_THEME'] == $menutheme ? $selected : '' )
     . '>' . $menutheme . '</option>';
  }

  set_today ( date ( 'Ymd' ) );
  ob_start ();

  echo '
    <h2>' . translate ( 'System Settings' )
   . '<img src="images/help.gif" alt="' . translate ( 'Help' )
   . '" class="help" onclick="window.open ( \'help_admin.php\', \'cal_help\', '
   . '\'dependent,menubar,scrollbars,height=400,width=400,innerHeight=420,'
   . 'outerWidth=420\' );" /></h2>
    <form action="admin.php" method="post" onsubmit="return valid_form ( this );"'
   . ' name="prefform">' . display_admin_link () . '
      <input type="hidden" name="currenttab" id="currenttab" value="'
   . $currenttab . '" />' . $saveStr . '<br /><br />

<!-- TABS -->
      <div id="tabs">' . $tabs . '
      </div>

<!-- TABS BODY -->
      <div id="tabscontent">
<!-- DETAILS -->
        <div id="tabscontent_settings">
          <fieldset>
            <legend>' . translate ( 'System options' ) . '</legend>
            <p><label for="admin_APPLICATION_NAME" title="'
   . tooltip ( 'app-name-help' ) . '">' . translate ( 'Application Name' )
   . ':</label>
              <input type="text" size="40" name="admin_APPLICATION_NAME" '
   . 'id="admin_APPLICATION_NAME" value="'
   . htmlspecialchars ( $s['APPLICATION_NAME'] ) . '" />'
   . ( $s['APPLICATION_NAME'] == 'Title'
    /* translate ( 'Translated Name' ) */
    ? str_replace ( 'XXX', translate ( 'Title' ),
      translate ( 'Translated Name (XXX)' ) ) : '' ) . '</p>
            <p><label for="admin_SERVER_URL" title="'
   . tooltip ( 'server-url-help' ) . '">' . translate ( 'Server URL' )
   . ':</label>
              <input type="text" size="40" name="admin_SERVER_URL" '
   . 'id="admin_SERVER_URL" value="' . htmlspecialchars ( $s['SERVER_URL'] )
   . '" /></p>
            <p><label for="admin_HOME_LINK" title="'
   . tooltip ( 'home-url-help' ) . '">' . translate ( 'Home URL' ) . ':</label>
              <input type="text" size="40" name="admin_HOME_LINK" '
   . 'id="admin_HOME_LINK" value="'
   . ( empty ( $s['HOME_LINK'] ) ? '' : htmlspecialchars ( $s['HOME_LINK'] ) )
   . '" /></p>
            <p><label for="admin_LANGUAGE" title="' . tooltip ( 'language-help' )
   . '">' . translate ( 'Language' ) . ':</label>
              <select name="admin_LANGUAGE" id="admin_LANGUAGE">' . $lang_list . '
              </select>'/* translate ( 'Your browser default language is' ) */
   . str_replace ( 'XXX', translate ( get_browser_language ( true ) ),
    translate ( 'Your browser default language is XXX.' ) ) . '</p>
            <p><label>' . translate ( 'Allow user to use themes' ) . ':</label>'
   . print_radio ( 'ALLOW_USER_THEMES' ) . '</p>
            <p><label for="admin_THEME" title="' . tooltip ( 'themes-help' )
   . '">' . translate ( 'Themes' ) . ':</label>
              <select name="admin_THEME" id="admin_THEME">
                <option disabled="disabled">' . translate ( 'AVAILABLE THEMES' )
   . '</option>'
  /* Always use 'none' as default so we don't overwrite manual settings. */
   . $option . 'none"' . $selected . '>' . translate ( 'None' ) . '</option>'
   . $theme_list . '
              </select><input type="button" name="preview" value="'
   . translate ( 'Preview' ) . '" onclick="return showPreview ()" />
            </p>
          </fieldset>
          <fieldset>
            <legend>' . translate ( 'Site customization' ) . '</legend>
            <p><label title="' . tooltip ( 'custom-script-help' ) . '">'
   . translate ( 'Custom script/stylesheet' ) . ':</label>'
   . print_radio ( 'CUSTOM_SCRIPT' );
  printf ( $editStr, 'S' );
  echo '
            <p><label title="' . tooltip ( 'custom-header-help' ) . '">'
   . translate ( 'Custom header' ) . ':</label>'
   . print_radio ( 'CUSTOM_HEADER' );
  printf ( $editStr, 'H' );
  echo '
            <p><label title="' . tooltip ( 'custom-trailer-help' ) . '">'
   . translate ( 'Custom trailer' ) . ':</label>'
   . print_radio ( 'CUSTOM_TRAILER' );
  printf ( $editStr, 'T' );
  echo '
            <p><label title="' . tooltip ( 'enable-external-header-help' ) . '">'
   . translate ( 'Allow external file for header/script/trailer' ) . ':</label>'
   . print_radio ( 'ALLOW_EXTERNAL_HEADER' ) . '</p>
            <p><label>' . translate ( 'Allow user to override header/trailer' )
   . ':</label>' . print_radio ( 'ALLOW_USER_HEADER' ) . '</p>
          </fieldset>
          <fieldset>
            <legend>' . translate ( 'Date and Time' ) . '</legend>'
  /* Determine if we can set timezones.  If not don't display any options. */
   . ( set_env ( 'TZ', $s['SERVER_TIMEZONE'] ) ? '
            <p><label for="admin_SERVER_TIMEZONE" title="'
     . tooltip ( 'tz-help' ) . '">' . translate ( 'Server Timezone Selection' )
     . ':</label>' . print_timezone_select_html ( 'admin_', $s['SERVER_TIMEZONE'] )
     . '</p>' : '' ) . '
            <p><label title="' . tooltip ( 'display-general-use-gmt-help' )
   . '">' . translate ( 'Display Common Use Date/Times as GMT' ) . ':</label>'
   . print_radio ( 'GENERAL_USE_GMT' ) . '</p>
            <p><label title="' . tooltip ( 'date-format-help' ) . '">'
   . translate ( 'Date format' ) . ':</label>
              <select name="admin_DATE_FORMAT">' . $datestyle_ymd . '
              </select>' . $choices_text[2] . ' ' . $choices_text[0] . ' '
   . $choices_text[3] . '</p>
            <p><label>&nbsp;</label>
              <select name="admin_DATE_FORMAT_MY">' . $datestyle_my . '
              </select>' . $choices_text[2] . ' ' . $choices_text[3] . '</p>
            <p><label>&nbsp;</label>
              <select name="admin_DATE_FORMAT_MD">' . $datestyle_md . '
              </select>' . $choices_text[2] . ' ' . $choices_text[0] . '</p>
            <p><label>&nbsp;</label>
              <select name="admin_DATE_FORMAT_TASK">' . $datestyle_tk . '
              </select>' . translate ( 'Small Task Date' ) . '</p>
            <p><label title="' . tooltip ( 'display-week-starts-on' ) . '">'
   . translate ( 'Week starts on' ) . ':</label>
              <select name="admin_WEEK_START" id="admin_WEEK_START">'
   . $start_wk_on . '
              </select></p>
            <p><label title="' . tooltip ( 'display-weekend-starts-on' ) . '">'
   . translate ( 'Weekend starts on' ) . ':</label>
              <select name="admin_WEEKEND_START" id="admin_WEEKEND_START">'
   . $start_wkend_on . '
              </select></p>
            <p><label title="' . tooltip ( 'time-format-help' ) . '">'
   . translate ( 'Time format' ) . ':</label>' . print_radio ( 'TIME_FORMAT',
    array ( '12' => translate ( '12 hour' ), '24' => translate ( '24 hour' ) ) )
   . '</p>
            <p><label title="' . tooltip ( 'timed-evt-len-help' ) . '">'
   . translate ( 'Specify timed event length by' ) . ':</label>'
   . print_radio ( 'TIMED_EVT_LEN',
    array ( 'D' => translate ( 'Duration' ), 'E' => translate ( 'End Time' ) ) )
   . '</p>
            <p><label for="admin_WORK_DAY_START_HOUR" title="'
   . tooltip ( 'work-hours-help' ) . '">' . translate ( 'Work hours' )
   . ':</label>' . translate ( 'From' ) . '
              <select name="admin_WORK_DAY_START_HOUR" id="admin_WORK_DAY_START_HOUR">'
   . $work_hr_start . '
              </select>' . translate ( 'to' ) . '
              <select name="admin_WORK_DAY_END_HOUR" id="admin_WORK_DAY_END_HOUR">'
   . $work_hr_end . '
              </select></p>
          </fieldset>
          <fieldset>
            <legend>' . translate ( 'Appearance' ) . '</legend>
            <p><label for="admin_STARTVIEW" title="'
   . tooltip ( 'preferred-view-help' ) . '">' . translate ( 'Preferred view' )
   . ':</label>
              <select name="admin_STARTVIEW" id="admin_STARTVIEW">' . $prefer_vu
   . $user_vu . '
              </select></p>
            <p><label>' . translate ( 'Allow top menu' ) . ':</label>'
   . print_radio ( 'MENU_ENABLED' ) . '</p>
            <p><label>' . translate ( 'Date Selectors position' ) . ':</label>'
   . print_radio ( 'MENU_DATE_TOP', array ( 'Y' => $topStr, 'N' => $bottomStr ) )
   . '</p>
            <p><label for="admin_MENU_THEME" title="'
   . tooltip ( 'menu-themes-help' ) . '">' . translate ( 'Menu theme' )
   . ':</label>
              <select name="admin_MENU_THEME" id="admin_MENU_THEME">' . $option
   . 'none"' . $selected . '>' . translate ( 'None' ) . '</option>'
   . $menu_theme_list . '
              </select></p>
            <p><label for="admin_FONTS" title="' . tooltip ( 'fonts-help' )
   . '">' . translate ( 'Fonts' )
   . ':</label><input type="text" size="40" name="admin_FONTS" id="admin_FONTS" value="'
   . htmlspecialchars ( $s['FONTS'] ) . '" /></p>
            <p><label title="' . tooltip ( 'display-sm_month-help' ) . '">'
   . translate ( 'Display small months' ) . ':</label>'
   . print_radio ( 'DISPLAY_SM_MONTH' ) . '</p>
            <p><label title="' . tooltip ( 'display-weekends-help' ) . '">'
   . translate ( 'Display weekends' ) . ':</label>'
   . print_radio ( 'DISPLAY_WEEKENDS' ) . '</p>
            <p><label title="' . tooltip ( 'display-long-daynames-help' ) . '">'
   . translate ( 'Display long day names' ) . ':</label>'
   . print_radio ( 'DISPLAY_LONG_DAYS' ) . '</p>
            <p><label title="' . tooltip ( 'display-alldays-help' ) . '">'
   . translate ( 'Display all days in month view' ) . ':</label>'
   . print_radio ( 'DISPLAY_ALL_DAYS_IN_MONTH' ) . '</p>
            <p><label title="' . tooltip ( 'display-week-number-help' ) . '">'
   . translate ( 'Display week number' ) . ':</label>'
   . print_radio ( 'DISPLAY_WEEKNUMBER' ) . '</p>
            <p><label title="' . tooltip ( 'display-desc-print-day-help' ) . '">'
   . translate ( 'Display description in printer day view' ) . ':</label>'
   . print_radio ( 'DISPLAY_DESC_PRINT_DAY' ) . '</p>
            <p><label title="' . tooltip ( 'yearly-shows-events-help' ) . '">'
   . translate ( 'Display days with events in bold in month and year views' )
   . ':</label>' . print_radio ( 'BOLD_DAYS_IN_YEAR' ) . '</p>
            <p><label title="' . tooltip ( 'display-minutes-help' ) . '">'
   . translate ( 'Display 00 minutes always' ) . ':</label>'
   . print_radio ( 'DISPLAY_MINUTES' ) . '</p>
            <p><label title="' . tooltip ( 'display-end-times-help' ) . '">'
   . translate ( 'Display end times on calendars' ) . ':</label>'
   . print_radio ( 'DISPLAY_END_TIMES' ) . '</p>
            <p><label title="' . tooltip ( 'allow-view-add-help' ) . '">'
   . translate ( 'Include add event link in views' ) . ':</label>'
   . print_radio ( 'ADD_LINK_IN_VIEWS' ) . '</p>
            <p><label title="' . tooltip ( 'lunar-help' ) . '">'
   . translate ( 'Display Lunar Phases in month view' ) . ':</label>'
   . print_radio ( 'DISPLAY_MOON_PHASES' ) . '</p>
          </fieldset>
          <fieldset>
            <legend>' . translate ( 'Restrictions' ) . '</legend>
            <p><label title="' . tooltip ( 'allow-view-other-help' ) . '">'
   . translate ( 'Allow viewing other users calendars' ) . ':</label>'
   . print_radio ( 'ALLOW_VIEW_OTHER' ) . '</p>
            <p><label title="' . tooltip ( 'require-approvals-help' ) . '">'
   . translate ( 'Require event approvals' ) . ':</label>'
   . print_radio ( 'REQUIRE_APPROVALS' ) . '</p>
            <p><label title="' . tooltip ( 'display-unapproved-help' ) . '">'
   . translate ( 'Display unapproved' ) . ':</label>'
   . print_radio ( 'DISPLAY_UNAPPROVED' ) . '</p>
            <p><label title="' . tooltip ( 'conflict-check-help' ) . '">'
   . translate ( 'Check for event conflicts' ) . ':</label>'
  /* This control is logically reversed. */
   . print_radio ( 'ALLOW_CONFLICTS',
    array ( 'N' => translate ( 'Yes' ), 'Y' => translate ( 'No' ) ) ) . '</p>
            <p><label title="' . tooltip ( 'conflict-months-help' ) . '">'
   . translate ( 'Conflict checking months' ) . ':</label>
              <input type="text" size="3" '
   . 'name="admin_CONFLICT_REPEAT_MONTHS" value="'
   . htmlspecialchars ( $s['CONFLICT_REPEAT_MONTHS'] ) . '" /></p>
            <p><label title="' . tooltip ( 'conflict-check-override-help' )
   . '">' . translate ( 'Allow users to override conflicts' ) . ':</label>'
   . print_radio ( 'ALLOW_CONFLICT_OVERRIDE' ) . '</p>
            <p><label title="' . tooltip ( 'limit-appts-help' ) . '">'
   . translate ( 'Limit number of timed events per day' ) . ':</label>'
   . print_radio ( 'LIMIT_APPTS' ) . '</p>
            <p><label title="' . tooltip ( 'limit-appts-number-help' ) . '">'
   . translate ( 'Maximum timed events per day' ) . ':</label>
              <input type="text" size="3" name="admin_LIMIT_APPTS_NUMBER" value="'
   . htmlspecialchars ( $s['LIMIT_APPTS_NUMBER'] ) . '" /></p>
            <p><label title="' . tooltip ( 'crossday-help' ) . '">'
   . translate ( 'Disable Cross-Day Events' ) . ':</label>'
   . print_radio ( 'DISABLE_CROSSDAY_EVENTS' ) . '</p>
          </fieldset>
          <fieldset>
            <legend>' . translate ( 'Events' ) . '</legend>
            <p><label title="' . tooltip ( 'disable-location-field-help' ) . '">'
   . translate ( 'Disable Location field' ) . ':</label>'
   . print_radio ( 'DISABLE_LOCATION_FIELD' ) . '</p>
            <p><label title="' . tooltip ( 'disable-url-field-help' ) . '">'
   . translate ( 'Disable URL field' ) . ':</label>'
   . print_radio ( 'DISABLE_URL_FIELD' ) . '</p>
            <p><label title="' . tooltip ( 'disable-priority-field-help' ) . '">'
   . translate ( 'Disable Priority field' ) . ':</label>'
   . print_radio ( 'DISABLE_PRIORITY_FIELD' ) . '</p>
            <p><label title="' . tooltip ( 'disable-access-field-help' ) . '">'
   . translate ( 'Disable Access field' ) . ':</label>'
   . print_radio ( 'DISABLE_ACCESS_FIELD' ) . '</p>
            <p><label title="' . tooltip ( 'disable-participants-field-help' )
   . '">' . translate ( 'Disable Participants field' ) . ':</label>'
   . print_radio ( 'DISABLE_PARTICIPANTS_FIELD' ) . '</p>
            <p><label title="' . tooltip ( 'disable-repeating-field-help' )
   . '">' . translate ( 'Disable Repeating field' ) . ':</label>'
   . print_radio ( 'DISABLE_REPEATING_FIELD' ) . '</p>
            <p><label title="' . tooltip ( 'allow-html-description-help' )
   . '">' . translate ( 'Allow HTML in Description' ) . ':</label>'
   . print_radio ( 'ALLOW_HTML_DESCRIPTION' ) . '</p>
          </fieldset>
          <fieldset>
            <legend>' . translate ( 'Popups' ) . '</legend>
            <p><label title="' . tooltip ( 'disable-popups-help' ) . '">'
   . translate ( 'Disable Pop-Ups' ) . ':</label>'
   . print_radio ( 'DISABLE_POPUPS', '', 'popup_handler' ) . '</p>
            <div id="pop">
              <p><label title="' . tooltip ( 'popup-includes-siteextras-help' )
   . '">' . translate ( 'Display Site Extras in popup' ) . ':</label>'
   . print_radio ( 'SITE_EXTRAS_IN_POPUP' ) . '</p>
              <p><label title="' . tooltip ( 'popup-includes-participants-help' )
   . '">' . translate ( 'Display Participants in popup' ) . ':</label>'
   . print_radio ( 'PARTICIPANTS_IN_POPUP' ) . '</p>
            </div>
          </fieldset>
          <fieldset>
            <legend>' . translate ( 'Miscellaneous' ) . '</legend>
            <p><label title="' . tooltip ( 'remember-last-login-help' ) . '">'
   . translate ( 'Remember last login' ) . ':</label>'
   . print_radio ( 'REMEMBER_LAST_LOGIN' ) . '</p>
            <p><label title="' . tooltip ( 'summary_length-help' ) . '">'
   . translate ( 'Brief Description Length' )
   . ':</label><input type="text" size="3" name="admin_SUMMARY_LENGTH" value="'
   . $s['SUMMARY_LENGTH'] . '" /></p>
            <p><label for="admin_USER_SORT_ORDER" title="'
   . tooltip ( 'user_sort-help' ) . '">' . translate ( 'User Sort Order' )
   . ':</label>
              <select name="admin_USER_SORT_ORDER" id="admin_USER_SORT_ORDER">'
   . $option . 'cal_lastname, cal_firstname" '
   . ( $s['USER_SORT_ORDER'] == 'cal_lastname, cal_firstname' ? $selected : '' )
   . '>' . translate ( 'Lastname, Firstname' ) . '</option>' . $option
   . 'cal_firstname, cal_lastname" '
   . ( $s['USER_SORT_ORDER'] == 'cal_firstname, cal_lastname' ? $selected : '' )
   . '>' . translate ( 'Firstname, Lastname' ) . '</option>
              </select></p>
          </fieldset>
        </div>
<!-- END SETTINGS -->

<!-- BEGIN PUBLIC ACCESS -->
        <div id="tabscontent_public">
          <p><label title=" ' . tooltip ( 'allow-public-access-help' ) . '">'
   . translate ( 'Allow public access' ) . ':</label>'
   . print_radio ( 'PUBLIC_ACCESS', '', 'public_handler' ) . '</p>
          <div id="pa">
            <p><label title="' . tooltip ( 'public-access-default-visible' )
   . '">' . translate ( 'Public access visible by default' ) . ':</label>'
   . print_radio ( 'PUBLIC_ACCESS_DEFAULT_VISIBLE' ) . '</p>
            <p><label title="' . tooltip ( 'public-access-default-selected' )
   . '">' . translate ( 'Public access is default participant' ) . ':</label>'
   . print_radio ( 'PUBLIC_ACCESS_DEFAULT_SELECTED' ) . '</p>
            <p><label title="' . tooltip ( 'public-access-view-others-help' )
   . '">' . translate ( 'Public access can view other users' ) . ':</label>'
   . print_radio ( 'PUBLIC_ACCESS_OTHERS' ) . '</p>
            <p><label title="' . tooltip ( 'public-access-can-add-help' ) . '">'
   . translate ( 'Public access can add events' ) . ':</label>'
   . print_radio ( 'PUBLIC_ACCESS_CAN_ADD' ) . '</p>
            <p><label title="' . tooltip ( 'public-access-add-requires-approval-help' )
   . '">' . translate ( 'Public access new events require approval' ) . ':</label>'
   . print_radio ( 'PUBLIC_ACCESS_ADD_NEEDS_APPROVAL' ) . '</p>
            <p><label title="' . tooltip ( 'public-access-sees-participants-help' )
   . '">' . translate ( 'Public access can view participants' ) . ':</label>'
   . print_radio ( 'PUBLIC_ACCESS_VIEW_PART' ) . '</p>
            <p><label title="' . tooltip ( 'public-access-override-help' ) . '">'
   . translate ( 'Override event name/description for public access' )
   . ':</label>' . print_radio ( 'OVERRIDE_PUBLIC' ) . '</p>
            <p><label title="' . tooltip ( 'public-access-override-text-help' )
   . '">' . translate ( 'Text to display to public access' )
   . ':</label><input name="admin_OVERRIDE_PUBLIC_TEXT" value="'
   . $s['OVERRIDE_PUBLIC_TEXT'] . '" size="25" /></p>
            <p><label title="' . tooltip ( 'public-access-captcha-help' ) . '">'
   . translate ( 'Require CAPTCHA validation for public access new events' )
   . ':</label>' . print_radio ( 'ENABLE_CAPTCHA' ) . '</p>
          </div>
        </div>

<!-- BEGIN USER ACCESS CONTROL -->
        <p id="tabscontent_uac"><label title="' . tooltip ( 'uac-enabled-help' )
   . '">' . translate ( 'User Access Control enabled' ) . ':</label>'
   . print_radio ( 'UAC_ENABLED' ) . '</p>

<!-- BEGIN GROUPS -->
        <div id="tabscontent_groups">
          <p><label title="' . tooltip ( 'groups-enabled-help' ) . '">'
   . translate ( 'Groups enabled' ) . ':</label>'
   . print_radio ( 'GROUPS_ENABLED' ) . '</p>
          <p><label title="' . tooltip ( 'user-sees-his-group-help' ) . '">'
   . translate ( 'User sees only his groups' ) . ':</label>'
   . print_radio ( 'USER_SEES_ONLY_HIS_GROUPS' ) . '</p>
        </div>

<!-- BEGIN NONUSER -->
        <div id="tabscontent_nonuser">
          <p><label title="' . tooltip ( 'nonuser-enabled-help' ) . '">'
   . translate ( 'Nonuser enabled' ) . ':</label>'
   . print_radio ( 'NONUSER_ENABLED' ) . '</p>
          <p><label title="' . tooltip ( 'nonuser-list-help' ) . '">'
   . translate ( 'Nonuser list' ) . ':</label>'
   . print_radio ( 'NONUSER_AT_TOP', array ( 'Y' => $topStr, 'N' => $bottomStr ) )
   . '</p>
        </div>

<!-- BEGIN REPORTS -->
        <div id="tabscontent_other">
          <p><label title="' . tooltip ( 'reports-enabled-help' ) . '">'
   . translate ( 'Reports enabled' ) . ':</label>'
   . print_radio ( 'REPORTS_ENABLED' ) . '</p>

<!-- BEGIN PUBLISHING -->
          <p><label title="' . tooltip ( 'subscriptions-enabled-help' ) . '">'
   . translate ( 'Allow remote subscriptions' ) . ':</label>'
   . print_radio ( 'PUBLISH_ENABLED' ) . '</p>'
  /* Determine if allow_url_fopen is enabled. */
   . ( preg_match ( '/(On|1|true|yes)/i', ini_get ( 'allow_url_fopen' ) ) ? '
          <p><label title="' . tooltip ( 'remotes-enabled-help' ) . '">'
     . translate ( 'Allow remote calendars' ) . ':</label>'
     . print_radio ( 'REMOTES_ENABLED' ) . '</p>' : '' ) . '
          <p><label title="' . tooltip ( 'rss-enabled-help' ) . '">'
   . translate ( 'Enable RSS feed' ) . ':</label>'
   . print_radio ( 'RSS_ENABLED' ) . '</p>

<!-- BEGIN CATEGORIES -->
          <p><label title="' . tooltip ( 'categories-enabled-help' ) . '">'
   . translate ( 'Categories enabled' ) . ':</label>'
   . print_radio ( 'CATEGORIES_ENABLED' ) . '</p>
          <p><label title="' . tooltip ( 'icon_upload-enabled-help' ) . '">'
   . translate ( 'Category Icon Upload enabled' ) . ':</label>'
   . print_radio ( 'ENABLE_ICON_UPLOADS' ) . '' . ( ! is_dir ( 'icons/' )
    /* translate ( 'Requires' ) translate ( 'folder to exist' ) */
    ? str_replace ( 'XXX', 'icons',
      translate ( '(Requires XXX folder to exist.)' ) ) : '' ) . '</p>

<!-- DISPLAY TASK PREFERENCES -->
          <p><label title="' . tooltip ( 'display-tasks-help' ) . '">'
   . translate ( 'Display small task list' ) . ':</label>'
   . print_radio ( 'DISPLAY_TASKS' ) . '</p>
          <p><label title="' . tooltip ( 'display-tasks-in-grid-help' ) . '">'
   . translate ( 'Display tasks in Calendars' ) . ':</label>'
   . print_radio ( 'DISPLAY_TASKS_IN_GRID' ) . '</p>

<!-- BEGIN EXT PARTICIPANTS -->
          <p><label title="' . tooltip ( 'allow-external-users-help' ) . '">'
   . translate ( 'Allow external users' ) . ':</label>'
   . print_radio ( 'ALLOW_EXTERNAL_USERS', '', 'eu_handler' ) . '</p>
          <div id="eu">
            <p><label title="' . tooltip ( 'external-can-receive-notification-help' )
   . '">' . translate ( 'External users can receive email notifications' )
   . ':</label>' . print_radio ( 'EXTERNAL_NOTIFICATIONS' ) . '</p>
            <p><label title="' . tooltip ( 'external-can-receive-reminder-help' )
   . '">' . translate ( 'External users can receive email reminders' )
   . ':</label>' . print_radio ( 'EXTERNAL_REMINDERS' ) . '</p>
          </div>

 <!-- BEGIN SELF REGISTRATION -->
          <p><label title="' . tooltip ( 'allow-self-registration-help' ) . '">'
   . translate ( 'Allow self-registration' ) . ':</label>'
   . print_radio ( 'ALLOW_SELF_REGISTRATION', '', 'sr_handler' ) . '</p>
          <div id="sr">
            <p><label title="' . tooltip ( 'use-blacklist-help' ) . '">'
   . translate ( 'Restrict self-registration to blacklist' ) . ':</label>'
   . print_radio ( 'SELF_REGISTRATION_BLACKLIST' ) . '</p>
            <p><label title="' . tooltip ( 'allow-self-registration-full-help' )
   . '">' . translate ( 'Use self-registration email notifications' )
   . ':</label>' . print_radio ( 'SELF_REGISTRATION_FULL' ) . '</p>
          </div>

<!-- TODO add account aging feature. -->

<!-- BEGIN ATTACHMENTS/COMMENTS -->
          <p><label title="' . tooltip ( 'allow-attachment-help' ) . '">'
   . translate ( 'Allow file attachments to events' ) . ':</label>'
   . print_radio ( 'ALLOW_ATTACH', '', 'attach_handler' )
   . '<span id="at1"><br /><strong>Note: </strong>'
   . translate ( 'Admin and owner can always add attachments if enabled.' )
   . '<br />' . print_checkbox ( array ( 'ALLOW_ATTACH_PART', 'Y', $partyStr ) )
   . print_checkbox ( array ( 'ALLOW_ATTACH_ANY', 'Y', $anyoneStr ) )
   . '</span></p>
          <p><label title="' . tooltip ( 'allow-comments-help' ) . '">'
   . translate ( 'Allow comments to events' ) . ':</label>'
   . print_radio ( 'ALLOW_COMMENTS', '', 'comment_handler' )
   . '<span id="com1"><br /><strong>Note: </strong>'
   . translate ( 'Admin and owner can always add comments if enabled.' )
   . '<br />' . print_checkbox ( array ( 'ALLOW_COMMENTS_PART', 'Y', $partyStr ) )
   . print_checkbox ( array ( 'ALLOW_COMMENTS_ANY', 'Y', $anyoneStr ) )
   . '</span></p>
        </div>

<!-- BEGIN EMAIL -->
        <div id="tabscontent_email">
          <p><label title="' . tooltip ( 'email-enabled-help' ) . '">'
   . translate ( 'Email enabled' ) . ':</label>'
   . print_radio ( 'SEND_EMAIL', '', 'email_handler' ) . '</p>
          <div id="em">
            <p><label title="' . tooltip ( 'email-default-sender' ) . '">'
   . translate ( 'Default sender address' )
   . ':</label><input type="text" size="30" name="admin_EMAIL_FALLBACK_FROM" value="'
   . htmlspecialchars ( $EMAIL_FALLBACK_FROM ) . '" /></p>
            <p><label title="' . tooltip ( 'email-mailer' ) . '">'
   . translate ( 'Email Mailer' ) . ':</label>
              <select name="admin_EMAIL_MAILER"onchange="email_handler ()">'
   . $option . 'smtp" ' . ( $s['EMAIL_MAILER'] == 'smtp' ? $selected : '' )
   . '>SMTP</option>' . $option . 'mail" '
   . ( $s['EMAIL_MAILER'] == 'mail' ? $selected : '' ) . '>PHP mail</option>'
   . $option . 'sendmail" '
   . ( $s['EMAIL_MAILER'] == 'sendmail' ? $selected : '' ) . '>sendmail</option>
              </select></p>
            <div id="em_smtp">
              <p><label title="' . tooltip ( 'email-smtp-host' ) . '">'
   . translate ( 'SMTP Host name(s)' )
   . ':</label><input type="text" size="50" name="admin_SMTP_HOST" value="'
   . $s['SMTP_HOST'] . '" /></p>
              <p><label title="' . tooltip ( 'email-smtp-port' ) . '">'
   . translate ( 'SMTP Port Number' )
   . ':</label><input type="text" size="4" name="admin_SMTP_PORT" value="'
   . $s['SMTP_PORT'] . '" /></p>
              <p><label title="' . tooltip ( 'email-smtp-auth' ) . '">'
   . translate ( 'SMTP Authentication' ) . ':</label>'
   . print_radio ( 'SMTP_AUTH', '', 'email_handler' ) . '</p>
              <div id="em_auth">
                <p><label title="' . tooltip ( 'email-smtp-username' ) . '">'
   . translate ( 'SMTP Username' )
   . ':</label><input type="text" size="30" name="admin_SMTP_USERNAME" value="'
   . ( empty ( $s['SMTP_USERNAME'] ) ? '' : $s['SMTP_USERNAME'] ) . '" /></p>
                <p><label title="' . tooltip ( 'email-smtp-password' ) . '">'
   . translate ( 'SMTP Password' )
   . ':</label><input type="text" size="30" name="admin_SMTP_PASSWORD" value="'
   . ( empty ( $s['SMTP_PASSWORD'] ) ? '' : $s['SMTP_PASSWORD'] ) . '" /></p>
              </div>
            </div>
            <p class="bold">' . translate ( 'Default user settings' ) . ':</p>
            <p><label title="' . tooltip ( 'email-event-reminders-help' ) . '">'
   . translate ( 'Event reminders' ) . ':</label>'
   . print_radio ( 'EMAIL_REMINDER' ) . '</p>
            <p><label title="' . tooltip ( 'email-event-added' ) . '">'
   . translate ( 'Events added to my calendar' ) . ':</label>'
   . print_radio ( 'EMAIL_EVENT_ADDED' ) . '</p>
            <p><label title="' . tooltip ( 'email-event-updated' ) . '">'
   . translate ( 'Events updated on my calendar' ) . ':</label>'
   . print_radio ( 'EMAIL_EVENT_UPDATED' ) . '</p>
            <p><label title="' . tooltip ( 'email-event-deleted' ) . '">'
   . translate ( 'Events removed from my calendar' ) . ':</label>'
   . print_radio ( 'EMAIL_EVENT_DELETED' ) . '</p>
            <p><label title="' . tooltip ( 'email-event-rejected' ) . '">'
   . translate ( 'Event rejected by participant' ) . ':</label>'
   . print_radio ( 'EMAIL_EVENT_REJECTED' ) . '</p>
            <p><label title="' . tooltip ( 'email-event-create' ) . '">'
   . translate ( 'Event that I create' ) . ':</label>'
   . print_radio ( 'EMAIL_EVENT_CREATE' ) . '</p>
          </div>
        </div>

<!-- BEGIN COLORS -->
        <div id="tabscontent_colors">
          <fieldset>
            <legend>' . translate ( 'Color options' ) . '</legend>'.$example_month.'
            <p><label>' . translate ( 'Allow user to customize colors' )
   . ':</label>' . print_radio ( 'ALLOW_COLOR_CUSTOMIZATION' ) . '</p>
            <p><label title="' . tooltip ( 'gradient-colors' ) . '">'
   . translate ( 'Enable gradient images for background colors' ) . ':</label>'
   . ( function_exists ( 'imagepng' ) || function_exists ( 'imagegif' )
    ? print_radio ( 'ENABLE_GRADIENTS' ) : translate ( 'Not available' ) )
   . '</p>' . $color_sets . '
          </fieldset>
          <fieldset>
            <legend>' . translate ( 'Background Image options' ) . '</legend>
            <p><label for="admin_BGIMAGE" title="' . tooltip ( 'bgimage-help' )
   . '">' . translate ( 'Background Image' )
   . ':</label><input type="text" size="75" name="admin_BGIMAGE" '
   . 'id="admin_BGIMAGE" value="'
   . ( empty ( $s['BGIMAGE'] ) ? '' : htmlspecialchars ( $s['BGIMAGE'] ) )
   . '" /></p>
            <p><label for="admin_BGREPEAT" title="' . tooltip ( 'bgrepeat-help' )
   . '">' . translate ( 'Background Repeat' )
   . ':</label><input type="text" size="30" name="admin_BGREPEAT" '
   . 'id="admin_BGREPEAT" value="'
   . ( empty ( $s['BGREPEAT'] ) ? '' : $s['BGREPEAT'] ) . '" /></p>
          </fieldset>
        </div>
      </div>
      <div id="saver">' . $saveStr . '
      </div>
    </form>';

  ob_end_flush ();
} else // if $error
  echo print_error ( $error, true );
echo print_trailer ();

?>
