<?php
include_once 'includes/init.php';
include_once 'includes/date_formats.php';
if ( file_exists ( 'install/default_config.php' ) )
  include_once 'install/default_config.php';

// Force the CSS cache to clear by incrementing webcalendar_csscache cookie.
// admin.php will not use this cached CSS, but we want to make sure it's flushed.
$webcalendar_csscache = 1;
if ( isset ( $_COOKIE['webcalendar_csscache'] ) )
  $webcalendar_csscache += $_COOKIE['webcalendar_csscache'];

sendCookie ( 'webcalendar_csscache', $webcalendar_csscache );

function save_pref ( $prefs, $src ) {
  global $error;

  foreach ($prefs as $key => $value) {
    if ( $src == 'post' ) {
      $prefix = substr ( $key, 0, 6 );
      $setting = substr ( $key, 6 );

      // Validate key name. Should start with "admin_" and not include
      // any unusual characters that might be an SQL injection attack.
      if ( $key == 'csrf_form_key' ) {
        // Ignore this for validation...
      } else if ( ! preg_match ( '/admin_[A-Za-z0-9_]+$/', $key ) ) {
        die_miserable_death ( str_replace ( 'XXX', $key,
            translate ( 'Invalid setting name XXX.' ) ) );
     }
    } else {
      $prefix = 'admin_';
      $setting = $key;
    }
    if ( strlen ( $setting ) > 0 && $prefix == 'admin_' ) {
      $setting = strtoupper ( $setting );
      $sql = 'DELETE FROM webcal_config WHERE cal_setting = ?';

      if ( ! dbi_execute ( $sql, [$setting] ) ) {
        $error = db_error ( false, $sql );
        break;
      }
      if ( strlen ( $value ) > 0 ) {
        $sql = 'INSERT INTO webcal_config ( cal_setting, cal_value ) VALUES ( ?, ? )';

        if ( ! dbi_execute ( $sql, [$setting, $value] ) ) {
          $error = db_error ( false, $sql );
          break;
        }
      }
    }
  }
  // Reload preferences so any CSS changes will take effect.
  load_global_settings();
  load_user_preferences();
}
$error = ( $is_admin ? '' : print_not_auth() );

if ( ! empty ( $_POST ) && empty ( $error ) ) {
  save_pref ( $_POST, 'post' );
}

// Load any new config settings. Existing ones will not be affected.
// This function is in the install/default_config.php file.
if ( function_exists ( 'db_load_config' ) && empty ( $_POST ) )
  db_load_config();

$s = [];

$res = dbi_execute ( 'SELECT cal_setting, cal_value FROM webcal_config' );

if ( $res ) {
  while ( $row = dbi_fetch_row ( $res ) ) {
    $setting = $row[0];
    $s[$setting] = $value = $row[1];
  }
  dbi_free_result ( $res );
}

print_header ( ['js/translate.js.php','js/admin.js','js/visible.js'], '',
  'onload="init_admin();"' );

if ( ! $error ) {
  // Make sure globals values passed to styles.php are for this user.
  // Makes the demo calendar and Page title accurate.
  $GLOBALS['APPLICATION_NAME'] = $s['APPLICATION_NAME'];
  $GLOBALS['BGCOLOR'] = $s['BGCOLOR'];
  $GLOBALS['CELLBG'] = $s['CELLBG'];
  $GLOBALS['FONTS'] = $s['FONTS'];
  $GLOBALS['H2COLOR'] = $s['H2COLOR'];
  $GLOBALS['HASEVENTSBG'] = $s['HASEVENTSBG'];
  $GLOBALS['MYEVENTS'] = $s['MYEVENTS'];
  $GLOBALS['OTHERMONTHBG'] = $s['OTHERMONTHBG'];
  $GLOBALS['TABLEBG'] = $s['TABLEBG'];
  $GLOBALS['TEXTCOLOR'] = $s['TEXTCOLOR'];
  $GLOBALS['THBG'] = $s['THBG'];
  $GLOBALS['THFG'] = $s['THFG'];
  $GLOBALS['TODAYCELLBG'] = $s['TODAYCELLBG'];
  $GLOBALS['WEEKENDBG'] = $s['WEEKENDBG'];
  $GLOBALS['WEEKNUMBER'] = $s['WEEKNUMBER'];

  define_languages(); // Load the language list.
  reset ( $languages );

  $checked = ' checked="checked"';
  $selected = ' selected="selected"';
  $select = translate ( 'Select' ) . '...';

  // Allow css_cache of webcal_config values.
  @session_start();
  $_SESSION['webcal_tmp_login'] = 'blahblahblah';

  $editStr = '<input type="button" value="' . translate ( 'Edit' )
   . "...\" onclick=\"window.open( 'edit_template.php?type=%s','cal_template','"
   . 'dependent,menubar,scrollbars,height=500,width=500,outerHeight=520,'
   . 'outerWidth=520\' );" name="" />';
  $choices = ['day.php', 'week.php', 'month.php', 'year.php'];
  $choices_text = [translate ( 'Day' ), translate ( 'Week' ),
    translate ( 'Month' ), translate ( 'Year' )];

  $bottomStr = translate ( 'Bottom' );
  $topStr = translate ( 'Top' );

  $anyoneStr = translate ( 'Anyone' );
  $partyStr = translate ( 'Participant' );

  $saveStr = translate ( 'Save' );

  $option = '
                <option value="';
  $color_sets = $datestyle_md = $datestyle_my = $datestyle_tk = '';
  $datestyle_ymd = $lang_list = $prefer_vu = '';
  $start_wk_on = $start_wkend_on = $tabs = $user_vu = '';
  $work_hr_end = $work_hr_start = '';

  // This should be easier to add more tabs if needed.
  $tabs_ar = [
    'settings', translate ( 'Settings' ),
    'public', translate ( 'Public Access' ),
    'uac', translate ( 'User Access Control' ),
    'groups', translate ( 'Groups' ),
    'nonuser', translate ( 'Resource Calendars' ),
    'other', translate ( 'Other' ),
    'email', translate ( 'Email' ),
    'colors', translate ( 'Colors' )];
  $tabs = '<ul class="nav nav-tabs">';
  for ($i = 0, $cnt = count($tabs_ar); $i < $cnt; $i += 2) {
    $tabs .= '<li class="nav-item"><a class="nav-link ' .
    ($i == 0 ? ' active' : '') . '" data-toggle="tab" href="#' . $tabs_ar[$i] . '">' . $tabs_ar[$i + 1] . '</a></li>';
  }
  $tabs .= "</ul>\n";
  // Move the loops here and combine a few.
  foreach ($languages as $key => $val) {
    $lang_list .= $option . $val . '"'
     . ( $val == $s['LANGUAGE'] ? $selected : '' )
     . '>' . $key . '</option>';
  }
  for ( $i = 0, $cnt = count ( $datestyles ); $i < $cnt; $i += 2 ) {
    $datestyle_ymd .= $option . $datestyles[$i] . '"'
     . ( $s['DATE_FORMAT'] == $datestyles[$i] ? $selected : '' )
     . '>' . $datestyles[$i + 1] . '</option>';
  }
  for ( $i = 0, $cnt = count ( $datestyles_my ); $i < $cnt; $i += 2 ) {
    $datestyle_my .= $option . $datestyles_my[$i] . '"'
     . ( $s['DATE_FORMAT_MY'] == $datestyles_my[$i] ? $selected : '' )
     . '>' . $datestyles_my[$i + 1] . '</option>';
  }
  for ( $i = 0, $cnt = count ( $datestyles_md ); $i < $cnt; $i += 2 ) {
    $datestyle_md .= $option . $datestyles_md[$i] . '"'
     . ( $s['DATE_FORMAT_MD'] == $datestyles_md[$i] ? $selected : '' )
     . '>' . $datestyles_md[$i + 1] . '</option>';
  }
  for ( $i = 0, $cnt = count ( $datestyles_task ); $i < $cnt; $i += 2 ) {
    $datestyle_tk .= $option . $datestyles_task[$i] . '"'
     . ( $s['DATE_FORMAT_TASK'] == $datestyles_task[$i] ? $selected : '' )
     . '>' . $datestyles_task[$i + 1] . '</option>';
  }
  for ( $i = 0; $i < 7; $i++ ) {
    $start_wk_on .= $option . "$i\""
     . ( $i == $s['WEEK_START'] ? $selected : '' )
     . '>' . weekday_name ( $i ) . '</option>';
    $j = ( $i == 0 ? 6 : $i - 1 ); // Make sure to start with Saturday.
    $start_wkend_on .= $option . "$j\""
     . ( $j == $s['WEEKEND_START'] ? $selected : '' )
     . '>' . weekday_name ( $j ) . '</option>';
  }
  for ( $i = 0; $i < 24; $i++ ) {
    $tmp = display_time ( $i * 10000, 1 );
    $work_hr_start .= $option . "$i\""
     . ( $i == $s['WORK_DAY_START_HOUR'] ? $selected : '' )
     . '>' . $tmp . '</option>';
    $work_hr_end .= $option . "$i\""
     . ( $i == $s['WORK_DAY_END_HOUR'] ? $selected : '' )
     . '>' . $tmp . '</option>';
  }
  for ( $i = 0, $cnt = count ( $choices ); $i < $cnt; $i++ ) {
    $prefer_vu .= $option . $choices[$i] . '"'
     . ( $s['STARTVIEW'] == $choices[$i] ? $selected : '' )
     . '>' . $choices_text[$i] . '</option>';
  }
  // Allow user to select a view also.
  for ( $i = 0, $cnt = count ( $views ); $i < $cnt; $i++ ) {
    if ( $views[$i]['cal_is_global'] != 'Y' )
      continue;

    $xurl = $views[$i]['url'];
    $xurl_strip = str_replace ( '&amp;', '&', $xurl );
    $user_vu .= $option . $xurl . '"'
     . ( $s['STARTVIEW'] == $xurl_strip ? $selected : '' )
     . '>' . $views[$i]['cal_name'] . '</option>';
  }
  $colors = [
    'BGCOLOR' => translate('Document background'),
    'H2COLOR' => translate('Document title'),
    'TEXTCOLOR' => translate('Document text'),
    'MYEVENTS' => translate('My event text'),
    'TABLEBG' => translate('Table grid color'),
    'THBG' => translate('Table header background'),
    'THFG' => translate('Table header text'),
    'CELLBG' => translate('Table cell background'),
    'TODAYCELLBG' => translate('Table cell background for current day'),
    'HASEVENTSBG' => translate('Table cell background for days with events'),
    'WEEKENDBG' => translate('Table cell background for weekends'),
    'OTHERMONTHBG' => translate('Table cell background for other month'),
    'WEEKNUMBER' => translate('Week number color'),
    'POPUP_BG' => translate('Event popup background'),
    'POPUP_FG' => translate('Event popup text')
  ];
  foreach ( $colors as $k => $v ) {
    $handler = 'color_change_handler_' . $k;
    $color_sets .= print_color_input_html ( $k, $v, '', '', 'p', '', $handler );
  }

  set_today ( date ( 'Ymd' ) );

  echo '
    <h2>' . translate ( 'System Settings' )
   . '<img src="images/bootstrap-icons/question-circle-fill.svg" alt="' . translate ( 'Help' )
   . '" class="help" onclick="window.open( \'help_admin.php\', \'cal_help\', '
   . '\'dependent,menubar,scrollbars,height=400,width=400,innerHeight=420,'
   . 'outerWidth=420\' );" /></h2>
    <form action="admin.php" method="post" onsubmit="return valid_form( this );"'
   . ' name="prefform">' . csrf_form_key()
   . display_admin_link() . '
      <input class="btn btn-primary" type="submit" value="' . $saveStr
   . '" name="" /><br /><br />

<!-- TABS -->' . $tabs . '
<!-- TABS BODY -->
    <div class="tab-content mb-12">
      <div class="tab-pane container active" id="' . $tabs_ar[0] . '">
      <div class="form-group">
<!-- DETAILS -->
          <fieldset class="border p-2">
            <legend>' . translate ( 'System options' ) . '</legend>
            <div class="form-inline mt-1 mb-2"><label for="admin_APPLICATION_NAME" title="'
   . tooltip ( 'app-name-help' ) . '">' . translate ( 'Application Name' )
   . ':</label>
              <input type="text" size="40" name="admin_APPLICATION_NAME" '
   . 'id="admin_APPLICATION_NAME" value="'
   . htmlspecialchars ( $s['APPLICATION_NAME'] ) . '" />'
   . ( $s['APPLICATION_NAME'] == 'Title'
    ? str_replace ( 'XXX', translate ( 'Title' ),
      translate ( 'Translated Name (XXX)' ) ) : '' ) . '</div>
            <div class="form-inline mt-1 mb-2"><label for="admin_SERVER_URL" title="'
   . tooltip ( 'server-url-help' ) . '">' . translate ( 'Server URL' )
   . ':</label>
              <input type="text" size="70" name="admin_SERVER_URL" '
   . 'id="admin_SERVER_URL" value="' . htmlspecialchars ( $s['SERVER_URL'] )
   . '" /></div>
            <div class="form-inline mt-1 mb-2"><label for="admin_HOME_LINK" title="'
   . tooltip ( 'home-url-help' ) . '">' . translate ( 'Home URL' ) . ':</label>
              <input type="text" size="40" name="admin_HOME_LINK" '
   . 'id="admin_HOME_LINK" value="'
   . ( empty ( $s['HOME_LINK'] ) ? '' : htmlspecialchars ( $s['HOME_LINK'] ) )
   . '" /></div>
            <div class="form-inline mt-1 mb-2"><label for="admin_LANGUAGE" title="' . tooltip ( 'language-help' )
   . '">' . translate ( 'Language' ) . ':</label>
              <select name="admin_LANGUAGE" id="admin_LANGUAGE">' . $lang_list . '
              </select>'
   . str_replace( 'XXX', translate( get_browser_language( true ) ),
    translate( 'Your browser default language is XXX.' ) ) . '</div>
            </fieldset>
          <fieldset class="border p-2">
            <legend>' . translate ( 'Site customization' ) . '</legend>
            <div class="form-inline mt-1 mb-2">
            <label for="admin_CUSTOM_SCRIPT" title="' . tooltip ( 'custom-script-help' ) . '">'
   . translate ( 'Custom script/stylesheet' ) . ':</label>'
   . print_radio ( 'CUSTOM_SCRIPT' );
  printf ( $editStr, 'S' );
  echo '</div>
    <div class="form-inline mt-1 mb-2"><label title="' . tooltip ( 'custom-header-help' ) . '">'
   . translate ( 'Custom header' ) . ':</label>'
   . print_radio ( 'CUSTOM_HEADER' );
  printf ( $editStr, 'H' );
  echo '</div>
    <div class="form-inline mt-1 mb-2"><label title="' . tooltip ( 'custom-trailer-help' ) . '">'
   . translate ( 'Custom trailer' ) . ':</label>'
   . print_radio ( 'CUSTOM_TRAILER' );
  printf ( $editStr, 'T' );
  echo '</div>
    <div class="form-inline mt-1 mb-2"><label title="' . tooltip ( 'enable-external-header-help' ) . '">'
   . translate ( 'Allow external file for header/script/trailer' ) . ':</label>'
   . print_radio ( 'ALLOW_EXTERNAL_HEADER' ) . '</div>
   <div class="form-inline mt-1 mb-2"><label>' . translate ( 'Allow user to override header/trailer' )
   . ':</label>' . print_radio ( 'ALLOW_USER_HEADER' ) . '</div>
          </fieldset>
          <fieldset class="border p-2">
            <legend>' . translate ( 'Date and Time' ) . '</legend>'
  /* Determine if we can set timezones. If not don't display any options. */
   . ( set_env ( 'TZ', $s['SERVER_TIMEZONE'] ) ? '
      <div class="form-inline mt-1 mb-2"><label for="admin_SERVER_TIMEZONE" title="'
     . tooltip ( 'server-tz-help' ) . '">' . translate ( 'Server Timezone Selection' )
     . ':</label>' . print_timezone_select_html ( 'admin_SERVER_', $s['SERVER_TIMEZONE'] )
     . '</div>' : '' ) . '
     <div class="form-inline mt-1 mb-2"><label for="admin_TIMEZONE" title="'
     . tooltip ( 'tz-help' ) . '">' . translate ( 'Default Client Timezone Selection' )
     . ':</label>' . print_timezone_select_html ( 'admin_', $s['TIMEZONE'] )
     . '</div>' . '
     <div class="form-inline mt-1 mb-2"><label title="' . tooltip ( 'display-general-use-gmt-help' )
   . '">' . translate ( 'Display Common Use Date/Times as GMT' ) . ':</label>'
   . print_radio ( 'GENERAL_USE_GMT' ) . '</div>
   <div class="form-inline mt-1 mb-2"><label title="' . tooltip ( 'date-format-help' ) . '">'
   . translate ( 'Date format' ) . ':</label>
              <select name="admin_DATE_FORMAT">' . $datestyle_ymd . '
              </select>' . $choices_text[2] . ' ' . $choices_text[0] . ' '
   . $choices_text[3] . '</div>
   <div class="form-inline mt-1 mb-2"><label>&nbsp;</label>
              <select name="admin_DATE_FORMAT_MY">' . $datestyle_my . '
              </select>' . $choices_text[2] . ' ' . $choices_text[3] . '</div>
            <div class="form-inline mt-1 mb-2"><label>&nbsp;</label>
              <select name="admin_DATE_FORMAT_MD">' . $datestyle_md . '
              </select>' . $choices_text[2] . ' ' . $choices_text[0] . '</div>
            <div class="form-inline mt-1 mb-2"><label>&nbsp;</label>
              <select name="admin_DATE_FORMAT_TASK">' . $datestyle_tk . '
              </select>' . translate ( 'Small Task Date' ) . '</div>
            <div class="form-inline mt-1 mb-2"><label title="' . tooltip ( 'display-week-starts-on' ) . '">'
   . translate ( 'Week starts on' ) . ':</label>
              <select name="admin_WEEK_START" id="admin_WEEK_START">'
   . $start_wk_on . '
              </select></div>
            <div class="form-inline mt-1 mb-2"><label title="' . tooltip ( 'display-weekend-starts-on' ) . '">'
   . translate ( 'Weekend starts on' ) . ':</label>
              <select name="admin_WEEKEND_START" id="admin_WEEKEND_START">'
   . $start_wkend_on . '
              </select></div>
              <div class="form-inline mt-1 mb-2"><label title="' . tooltip ( 'time-format-help' ) . '">'
   . translate ( 'Time format' ) . ':</label>' . print_radio ( 'TIME_FORMAT',
    ['12' => translate ( '12 hour' ), '24' => translate ( '24 hour' )] )
   . '</div>
      <div class="form-inline mt-1 mb-2"><label title="' . tooltip ( 'timed-evt-len-help' ) . '">'
   . translate ( 'Specify timed event length by' ) . ':</label>'
   . print_radio ( 'TIMED_EVT_LEN',
    ['D' => translate ( 'Duration' ), 'E' => translate ( 'End Time' )] )
   . '</div>
      <div class="form-inline mt-1 mb-2"><label for="admin_WORK_DAY_START_HOUR" title="'
   . tooltip ( 'work-hours-help' ) . '">' . translate ( 'Work hours' )
   . ': </label>&nbsp;' . translate ( 'From' ) . '
              <select name="admin_WORK_DAY_START_HOUR" id="admin_WORK_DAY_START_HOUR">'
   . $work_hr_start . '
              </select>' . translate ( 'to' ) . '
              <select name="admin_WORK_DAY_END_HOUR" id="admin_WORK_DAY_END_HOUR">'
   . $work_hr_end . '
              </select></div>
          </fieldset>
          <fieldset class="border p-2">
            <legend>' . translate ( 'Appearance' ) . '</legend>
            <div class="form-inline mt-1 mb-2"><label for="admin_STARTVIEW" title="'
   . tooltip ( 'preferred-view-help' ) . '">' . translate ( 'Preferred view' )
   . ':</label>
              <select name="admin_STARTVIEW" id="admin_STARTVIEW">' . $prefer_vu
   . $user_vu . '
              </select></div>
            <div class="form-inline mt-1 mb-2"><label>' . translate ( 'Allow top menu' ) . ':</label>'
   . print_radio ( 'MENU_ENABLED' ) . '</div>
      <div class="form-inline mt-1 mb-2"><label>' . translate ( 'Date Selectors position' ) . ':</label>'
   . print_radio ( 'MENU_DATE_TOP', ['Y' => $topStr, 'N' => $bottomStr] )
   . '</div>
      <div class="form-inline mt-1 mb-2"><label for="admin_FONTS" title="' . tooltip ( 'fonts-help' )
   . '">' . translate ( 'Fonts' )
   . ':</label><input type="text" size="40" name="admin_FONTS" id="admin_FONTS" value="'
   . htmlspecialchars ( $s['FONTS'] ) . '" /></div>
      <div class="form-inline mt-1 mb-2"><label title="' . tooltip ( 'display-sm_month-help' ) . '">'
   . translate ( 'Display small months' ) . ':</label>'
   . print_radio ( 'DISPLAY_SM_MONTH' ) . '</div>
      <div class="form-inline mt-1 mb-2"><label title="' . tooltip ( 'display-weekends-help' ) . '">'
   . translate ( 'Display weekends' ) . ':</label>'
   . print_radio ( 'DISPLAY_WEEKENDS' ) . '</div>
      <div class="form-inline mt-1 mb-2"><label title="' . tooltip ( 'display-long-daynames-help' ) . '">'
   . translate ( 'Display long day names' ) . ':</label>'
   . print_radio ( 'DISPLAY_LONG_DAYS' ) . '</div>
    <div class="form-inline mt-1 mb-2"><label title="' . tooltip ( 'display-alldays-help' ) . '">'
   . translate ( 'Display all days in month view' ) . ':</label>'
   . print_radio ( 'DISPLAY_ALL_DAYS_IN_MONTH' ) . '</div>
    <div class="form-inline mt-1 mb-2"><label title="' . tooltip ( 'display-week-number-help' ) . '">'
   . translate ( 'Display week number' ) . ':</label>'
   . print_radio ( 'DISPLAY_WEEKNUMBER' ) . '</div>
    <div class="form-inline mt-1 mb-2"><label title="' . tooltip ( 'display-desc-print-day-help' ) . '">'
   . translate ( 'Display description in printer day view' ) . ':</label>'
   . print_radio ( 'DISPLAY_DESC_PRINT_DAY' ) . '</div>
    <div class="form-inline mt-1 mb-2"><label title="' . tooltip ( 'yearly-shows-events-help' ) . '">'
   . translate ( 'Display days with events in bold in month and year views' )
   . ':</label>' . print_radio ( 'BOLD_DAYS_IN_YEAR' ) . '</div>
    <div class="form-inline mt-1 mb-2"><label title="' . tooltip ( 'display-minutes-help' ) . '">'
   . translate ( 'Display 00 minutes always' ) . ':</label>'
   . print_radio ( 'DISPLAY_MINUTES' ) . '</div>
    <div class="form-inline mt-1 mb-2"><label title="' . tooltip ( 'display-end-times-help' ) . '">'
   . translate ( 'Display end times on calendars' ) . ':</label>'
   . print_radio ( 'DISPLAY_END_TIMES' ) . '</div>
    <div class="form-inline mt-1 mb-2"><label title="' . tooltip ( 'allow-view-add-help' ) . '">'
   . translate ( 'Include add event link in views' ) . ':</label>'
   . print_radio ( 'ADD_LINK_IN_VIEWS' ) . '</div>
    <div class="form-inline mt-1 mb-2"><label title="' . tooltip ( 'lunar-help' ) . '">'
   . translate ( 'Display Lunar Phases in month view' ) . ':</label>'
   . print_radio ( 'DISPLAY_MOON_PHASES' ) . '</div>
          </fieldset>
          <fieldset class="border p-2">
            <legend>' . translate ( 'Restrictions' ) . '</legend>
            <div class="form-inline mt-1 mb-2"><label title="' . tooltip ( 'allow-view-other-help' ) . '">'
   . translate ( 'Allow viewing other users calendars' ) . ':</label>'
   . print_radio ( 'ALLOW_VIEW_OTHER' ) . '</div>
    <div class="form-inline mt-1 mb-2"><label title="' . tooltip ( 'require-approvals-help' ) . '">'
   . translate ( 'Require event approvals' ) . ':</label>'
   . print_radio ( 'REQUIRE_APPROVALS' ) . '</div>
    <div class="form-inline mt-1 mb-2"><label title="' . tooltip ( 'display-unapproved-help' ) . '">'
   . translate ( 'Display unapproved' ) . ':</label>'
   . print_radio ( 'DISPLAY_UNAPPROVED' ) . '</div>
    <div class="form-inline mt-1 mb-2"><label title="' . tooltip ( 'conflict-check-help' ) . '">'
   . translate ( 'Check for event conflicts' ) . ':</label>'
  /* This control is logically reversed. */
   . print_radio ( 'ALLOW_CONFLICTS',
    ['N' => translate ( 'Yes' ), 'Y' => translate ( 'No' )] ) . '</div>
      <div class="form-inline mt-1 mb-2"><label title="' . tooltip ( 'conflict-months-help' ) . '">'
   . translate ( 'Conflict checking months' ) . ':</label>
              <input type="text" size="3" '
   . 'name="admin_CONFLICT_REPEAT_MONTHS" value="'
   . htmlspecialchars ( $s['CONFLICT_REPEAT_MONTHS'] ) . '" /></div>
    <div class="form-inline mt-1 mb-2"><label title="' . tooltip ( 'conflict-check-override-help' )
   . '">' . translate ( 'Allow users to override conflicts' ) . ':</label>'
   . print_radio ( 'ALLOW_CONFLICT_OVERRIDE' ) . '</div>
    <div class="form-inline mt-1 mb-2"><label title="' . tooltip ( 'limit-appts-help' ) . '">'
   . translate ( 'Limit number of timed events per day' ) . ':</label>'
   . print_radio ( 'LIMIT_APPTS' ) . '</div>
    <div class="form-inline mt-1 mb-2"><label title="' . tooltip ( 'limit-appts-number-help' ) . '">'
   . translate ( 'Maximum timed events per day' ) . ':</label>
              <input type="text" size="3" name="admin_LIMIT_APPTS_NUMBER" value="'
   . htmlspecialchars ( $s['LIMIT_APPTS_NUMBER'] ) . '" /></div>
    <div class="form-inline mt-1 mb-2"><label title="' . tooltip ( 'crossday-help' ) . '">'
   . translate ( 'Disable Cross-Day Events' ) . ':</label>'
   . print_radio ( 'DISABLE_CROSSDAY_EVENTS' ) . '</div>
          </fieldset>
          <fieldset class="border p-2">
            <legend>' . translate ( 'Events' ) . '</legend>
            <div class="form-inline mt-1 mb-2"><label title="' . tooltip ( 'disable-location-field-help' ) . '">'
   . translate ( 'Disable Location field' ) . ':</label>'
   . print_radio ( 'DISABLE_LOCATION_FIELD' ) . '</div>
    <div class="form-inline mt-1 mb-2"><label title="' . tooltip ( 'disable-url-field-help' ) . '">'
   . translate ( 'Disable URL field' ) . ':</label>'
   . print_radio ( 'DISABLE_URL_FIELD' ) . '</div>
    <div class="form-inline mt-1 mb-2"><label title="' . tooltip ( 'disable-priority-field-help' ) . '">'
   . translate ( 'Disable Priority field' ) . ':</label>'
   . print_radio ( 'DISABLE_PRIORITY_FIELD' ) . '</div>
    <div class="form-inline mt-1 mb-2"><label title="' . tooltip ( 'disable-access-field-help' ) . '">'
   . translate ( 'Disable Access field' ) . ':</label>'
   . print_radio ( 'DISABLE_ACCESS_FIELD' ) . '</div>
    <div class="form-inline mt-1 mb-2"><label title="' . tooltip ( 'disable-participants-field-help' )
   . '">' . translate ( 'Disable Participants field' ) . ':</label>'
   . print_radio ( 'DISABLE_PARTICIPANTS_FIELD' ) . '</div>
    <div class="form-inline mt-1 mb-2"><label title="' . tooltip ( 'disable-repeating-field-help' )
   . '">' . translate ( 'Disable Repeating field' ) . ':</label>'
   . print_radio ( 'DISABLE_REPEATING_FIELD' ) . '</div>
    <div class="form-inline mt-1 mb-2"><label title="' . tooltip ( 'allow-html-description-help' )
   . '">' . translate ( 'Allow HTML in Description' ) . ':</label>'
   . print_radio ( 'ALLOW_HTML_DESCRIPTION' ) . '</div>
          </fieldset>
          <fieldset class="border p-2">
            <legend>' . translate ( 'Popups' ) . '</legend>
            <div class="form-inline mt-1 mb-2"><label title="' . tooltip ( 'disable-popups-help' ) . '">'
   . translate ( 'Disable Pop-Ups' ) . ':</label>'
   . print_radio ( 'DISABLE_POPUPS', '', 'popup_handler' ) . '</div>
            <div id="pop">
            <div class="form-inline mt-1 mb-2"><label title="' . tooltip ( 'popup-includes-siteextras-help' )
   . '">' . translate ( 'Display Site Extras in popup' ) . ':</label>'
   . print_radio ( 'SITE_EXTRAS_IN_POPUP' ) . '</div>
    <div class="form-inline mt-1 mb-2"><label title="' . tooltip ( 'popup-includes-participants-help' )
   . '">' . translate ( 'Display Participants in popup' ) . ':</label>'
   . print_radio ( 'PARTICIPANTS_IN_POPUP' ) . '</div>
            </div>
          </fieldset>
          <fieldset class="border p-2">
            <legend>' . translate ( 'Miscellaneous' ) . '</legend>
            <div class="form-inline mt-1 mb-2"><label title="' . tooltip ( 'remember-last-login-help' ) . '">'
   . translate ( 'Remember last login' ) . ':</label>'
   . print_radio ( 'REMEMBER_LAST_LOGIN' ) . '</div>
    <div class="form-inline mt-1 mb-2"><label title="' . tooltip ( 'summary_length-help' ) . '">'
   . translate ( 'Brief Description Length' )
   . ':</label><input type="text" size="3" name="admin_SUMMARY_LENGTH" value="'
   . $s['SUMMARY_LENGTH'] . '" /></div>
    <div class="form-inline mt-1 mb-2"><label for="admin_USER_SORT_ORDER" title="'
   . tooltip ( 'user_sort-help' ) . '">' . translate ( 'User Sort Order' )
   . ':</label>
              <select name="admin_USER_SORT_ORDER" id="admin_USER_SORT_ORDER">'
   . $option . 'cal_lastname, cal_firstname" '
   . ( $s['USER_SORT_ORDER'] == 'cal_lastname, cal_firstname' ? $selected : '' )
   . '>' . translate ( 'Lastname, Firstname' ) . '</option>' . $option
   . 'cal_firstname, cal_lastname" '
   . ( $s['USER_SORT_ORDER'] == 'cal_firstname, cal_lastname' ? $selected : '' )
   . '>' . translate ( 'Firstname, Lastname' ) . '</option>
              </select></div>
          </fieldset>
        </div>
      </div>
<!-- END SETTINGS -->

<!-- BEGIN PUBLIC ACCESS -->
  <div class="tab-pane container fade" id="' . $tabs_ar[2] . '">
  <div class="form-group">
    <div class="form-inline mt-1 mb-2"><label title=" ' . tooltip ( 'allow-public-access-help' ) . '">'
   . translate ( 'Allow public access' ) . ':</label>'
   . print_radio ( 'PUBLIC_ACCESS', '', 'public_handler' ) . '</div>
          <div id="pa">
          <div class="form-inline mt-1 mb-2"><label title="' . tooltip ( 'public-access-default-visible' )
   . '">' . translate ( 'Public access visible by default' ) . ':</label>'
   . print_radio ( 'PUBLIC_ACCESS_DEFAULT_VISIBLE' ) . '</div>
            <div class="form-inline mt-1 mb-2"><label title="' . tooltip ( 'public-access-default-selected' )
   . '">' . translate ( 'Public access is default participant' ) . ':</label>'
   . print_radio ( 'PUBLIC_ACCESS_DEFAULT_SELECTED' ) . '</div>
            <div class="form-inline mt-1 mb-2"><label title="' . tooltip ( 'public-access-view-others-help' )
   . '">' . translate ( 'Public access can view other users' ) . ':</label>'
   . print_radio ( 'PUBLIC_ACCESS_OTHERS' ) . '</div>
            <div class="form-inline mt-1 mb-2"><label title="' . tooltip ( 'public-access-can-add-help' ) . '">'
   . translate ( 'Public access can add events' ) . ':</label>'
   . print_radio ( 'PUBLIC_ACCESS_CAN_ADD' ) . '</div>
            <div class="form-inline mt-1 mb-2"><label title="' . tooltip ( 'public-access-add-requires-approval-help' )
   . '">' . translate ( 'Public access new events require approval' ) . ':</label>'
   . print_radio ( 'PUBLIC_ACCESS_ADD_NEEDS_APPROVAL' ) . '</div>
            <div class="form-inline mt-1 mb-2"><label title="' . tooltip ( 'public-access-sees-participants-help' )
   . '">' . translate ( 'Public access can view participants' ) . ':</label>'
   . print_radio ( 'PUBLIC_ACCESS_VIEW_PART' ) . '</div>
            <div class="form-inline mt-1 mb-2"><label title="' . tooltip ( 'public-access-override-help' ) . '">'
   . translate ( 'Override event name/description for public access' )
   . ':</label>' . print_radio ( 'OVERRIDE_PUBLIC' ) . '</div>
            <div class="form-inline mt-1 mb-2"><label title="' . tooltip ( 'public-access-override-text-help' )
   . '">' . translate ( 'Text to display to public access' )
   . ':</label><input name="admin_OVERRIDE_PUBLIC_TEXT" value="'
   . $s['OVERRIDE_PUBLIC_TEXT'] . '" size="25" /></div>
            <div class="form-inline mt-1 mb-2"><label title="' . tooltip ( 'public-access-captcha-help' ) . '">'
   . translate ( 'Require CAPTCHA validation for public access new events' )
   . ':</label>' . print_radio ( 'ENABLE_CAPTCHA' ) . '</div>
           <div style="clear:both;"></div>
          </div>
        </div>
      </div>

<!-- BEGIN USER ACCESS CONTROL -->
  <div class="tab-pane container fade" id="' . $tabs_ar[4] . '">
  <div class="form-group">
        <div id="uac" class="form-inline mt-1 mb-2"><label title="' . tooltip ( 'uac-enabled-help' )
   . '">' . translate ( 'User Access Control enabled' ) . ':</label>'
   . print_radio ( 'UAC_ENABLED' ) . '</div>
  </div>
  </div>

<!-- BEGIN GROUPS -->
  <div class="tab-pane container fade" id="' . $tabs_ar[6] . '">
  <div class="form-group">
          <div class="form-inline mt-1 mb-2"><label title="' . tooltip ( 'groups-enabled-help' ) . '">'
   . translate ( 'Groups enabled' ) . ':</label>'
   . print_radio ( 'GROUPS_ENABLED' ) . '</div>
          <div class="form-inline mt-1 mb-2"><label title="' . tooltip ( 'user-sees-his-group-help' ) . '">'
   . translate ( 'User sees only his groups' ) . ':</label>'
   . print_radio ( 'USER_SEES_ONLY_HIS_GROUPS' ) . '</div>
        </div>
        </div>

<!-- BEGIN NONUSER -->
  <div class="tab-pane container fade" id="' . $tabs_ar[8] . '">
  <div class="form-group">
          <div class="form-inline mt-1 mb-2"><label title="' . tooltip ( 'resource-enabled-help' ) . '">'
   . translate ( 'Resource Calendars enabled' ) . ':</label>'
   . print_radio ( 'NONUSER_ENABLED' ) . '</div>
          <div class="form-inline mt-1 mb-2"><label title="' . tooltip ( 'resource-list-help' ) . '">'
   . translate ( 'Display in participants list at' ) . ':</label>'
   . print_radio ( 'NONUSER_AT_TOP', ['Y' => $topStr, 'N' => $bottomStr] )
   . '</div>
        </div>
        </div>

<!-- BEGIN OTHER -->
  <div class="tab-pane container fade" id="' . $tabs_ar[10] . '">
  <div class="form-group">
   <fieldset class="border p-2"><legend>' . translate('Upcoming Events') . '</legend>
   ' . htmlspecialchars( $SERVER_URL ) . 'upcoming.php<br />
   <div class="form-inline mt-1 mb-2"><label title="' . tooltip ( 'upcoming-events-help' ) . '">'
   . translate ( 'Enabled' ) . ':</label>'
   . print_radio ( 'UPCOMING_EVENTS', '', '', 'N' ) . '</div>

   <div class="form-inline mt-1 mb-2"><label title="' . tooltip( 'upcoming-events-allow-override' ) . '">'
   . translate ( 'Allow user override' ) . ':</label>'
   . print_radio ( 'UPCOMING_ALLOW_OVR', '', '', 'N' ) . '</div>

   <div class="form-inline mt-1 mb-2"><label title="' . tooltip( 'upcoming-events-display-caticons' ) . '">'
   . translate ( 'Display category icons' ) . ':</label>'
   . print_radio ( 'UPCOMING_DISPLAY_CAT_ICONS', '', '', 'Y' ) . '</div>

     <div class="form-inline mt-1 mb-2"><label title="' . tooltip( 'upcoming-events-display-layers' ) . '">'
   . translate ( 'Display layers' ) . ':</label>'
   . print_radio ( 'UPCOMING_DISPLAY_LAYERS', '', '', 'N' ) . '</div>

     <div class="form-inline mt-1 mb-2"><label title="' . tooltip( 'upcoming-events-display-links' ) . '">'
   . translate ( 'Display links to events' ) . ':</label>'
   . print_radio ( 'UPCOMING_DISPLAY_LINKS', '', '', 'Y' ) . '</div>

     <div class="form-inline mt-1 mb-2"><label title="' . tooltip( 'upcoming-events-display-popups' ) . '">'
   . translate ( 'Display event popups' ) . ':</label>'
   . print_radio ( 'UPCOMING_DISPLAY_POPUPS', '', '', 'Y' ) . '</div>
   </fieldset>

<!-- BEGIN REPORTS -->
          <div class="form-inline mt-1 mb-2"><label title="' . tooltip ( 'reports-enabled-help' ) . '">'
   . translate ( 'Reports enabled' ) . ':</label>'
   . print_radio ( 'REPORTS_ENABLED' ) . '</div>

<!-- BEGIN PUBLISHING -->
          <div class="form-inline mt-1 mb-2"><label title="' . tooltip ( 'subscriptions-enabled-help' ) . '">'
   . translate ( 'Allow remote subscriptions' ) . ':</label>'
   . print_radio ( 'PUBLISH_ENABLED' ) . '</div>'
  /* Determine if allow_url_fopen is enabled. */
   . ( preg_match ( '/(On|1|true|yes)/i', ini_get ( 'allow_url_fopen' ) ) ? '
          <div class="form-inline mt-1 mb-2"><label title="' . tooltip ( 'remotes-enabled-help' ) . '">'
     . translate ( 'Allow remote calendars' ) . ':</label>'
     . print_radio ( 'REMOTES_ENABLED' ) . '</div>' : '' ) . '
          <div class="form-inline mt-1 mb-2"><label title="' . tooltip ( 'rss-enabled-help' ) . '">'
   . translate ( 'Enable RSS feed' ) . ':</label>'
   . print_radio ( 'RSS_ENABLED' ) . '</div>

<!-- BEGIN CATEGORIES -->
          <div class="form-inline mt-1 mb-2"><label title="' . tooltip ( 'categories-enabled-help' ) . '">'
   . translate ( 'Categories enabled' ) . ':</label>'
   . print_radio ( 'CATEGORIES_ENABLED' ) . '</div>
          <div class="form-inline mt-1 mb-2"><label title="' . tooltip ( 'icon_upload-enabled-help' ) . '">'
   . translate ( 'Category Icon Upload enabled' ) . ':</label>'
   . print_radio ( 'ENABLE_ICON_UPLOADS' ) . '' . ( ! is_dir ( 'wc-icons/' )
    ? str_replace ( 'XXX', 'wc-icons',
      translate ( '(Requires XXX folder to exist.)' ) ) : '' ) . '</div>

<!-- DISPLAY TASK PREFERENCES -->
          <div class="form-inline mt-1 mb-2"><label title="' . tooltip ( 'display-tasks-help' ) . '">'
   . translate ( 'Display small task list' ) . ':</label>'
   . print_radio ( 'DISPLAY_TASKS' ) . '</div>
          <div class="form-inline mt-1 mb-2"><label title="' . tooltip ( 'display-tasks-in-grid-help' ) . '">'
   . translate ( 'Display tasks in Calendars' ) . ':</label>'
   . print_radio ( 'DISPLAY_TASKS_IN_GRID' ) . '</div>

<!-- BEGIN EXT PARTICIPANTS -->
          <div class="form-inline mt-1 mb-2"><label title="' . tooltip ( 'allow-external-users-help' ) . '">'
   . translate ( 'Allow external users' ) . ':</label>'
   . print_radio ( 'ALLOW_EXTERNAL_USERS', '', 'eu_handler' ) . '</div>
          <div id="eu">
            <div class="form-inline mt-1 mb-2"><label title="' . tooltip ( 'external-can-receive-notification-help' )
   . '">' . translate ( 'External users can receive email notifications' )
   . ':</label>' . print_radio ( 'EXTERNAL_NOTIFICATIONS' ) . '</div>
            <div class="form-inline mt-1 mb-2"><label title="' . tooltip ( 'external-can-receive-reminder-help' )
   . '">' . translate ( 'External users can receive email reminders' )
   . ':</label>' . print_radio ( 'EXTERNAL_REMINDERS' ) . '</div>
          </div>

 <!-- BEGIN SELF REGISTRATION -->
          <div class="form-inline mt-1 mb-2"><label title="' . tooltip ( 'allow-self-registration-help' ) . '">'
   . translate ( 'Allow self-registration' ) . ':</label>'
   . print_radio ( 'ALLOW_SELF_REGISTRATION', '', 'sr_handler' ) . '</div>
          <div id="sr">
            <div class="form-inline mt-1 mb-2"><label title="' . tooltip ( 'use-blacklist-help' ) . '">'
   . translate ( 'Restrict self-registration to blacklist' ) . ':</label>'
   . print_radio ( 'SELF_REGISTRATION_BLACKLIST' ) . '</div>
            <div class="form-inline mt-1 mb-2"><label title="' . tooltip ( 'allow-self-registration-full-help' )
   . '">' . translate ( 'Use self-registration email notifications' )
   . ':</label>' . print_radio ( 'SELF_REGISTRATION_FULL' ) . '</div><br />
          </div>

<!-- TODO add account aging feature. -->

<!-- BEGIN ATTACHMENTS/COMMENTS -->
        <p class="form-inline mt-1 mb-2"><label title="'
   . tooltip ( 'allow-attachment-help' ) . '">'
   . translate ( 'Allow file attachments to events' ) . ':</label>'
   . print_radio ( 'ALLOW_ATTACH', '', 'attach_handler' )
    . '</p><p class="form-inline mt-1 mb-2" id="at1" style="margin-left:25%"><strong>Note: </strong>'
   . translate ( 'Admin and owner can always add attachments if enabled.' )
   . '</p><p class="form-inline mt-1 mb-2" id="at1a" style="margin-left:25%">' . print_checkbox ( ['ALLOW_ATTACH_PART', 'Y', $partyStr] )
   . print_checkbox ( ['ALLOW_ATTACH_ANY', 'Y', $anyoneStr] )
   . '</p><br /><p class="form-inline mt-1 mb-2"><label title="'
   . tooltip ( 'allow-comments-help' ) . '">'
   . translate ( 'Allow comments to events' ) . ':</label>'
   . print_radio ( 'ALLOW_COMMENTS', '', 'comment_handler' )
   . '</p><p id="com1" style="margin-left:25%"><strong>Note: </strong>'
   . translate ( 'Admin and owner can always add comments if enabled.' )
   . '</p><p class="form-inline mt-1 mb-2" id="com1a" style="margin-left:25%">' . print_checkbox ( ['ALLOW_COMMENTS_PART', 'Y', $partyStr] )
   . print_checkbox ( ['ALLOW_COMMENTS_ANY', 'Y', $anyoneStr] )
   . '</p></div></div>

<!-- BEGIN EMAIL -->
  <div class="tab-pane container fade" id="' . $tabs_ar[12] . '">
  <div class="form-group">
          <div class="form-inline mt-1 mb-2"><label title="' . tooltip ( 'email-enabled-help' ) . '">'
   . translate ( 'Email enabled' ) . ':</label>'
   . print_radio ( 'SEND_EMAIL', '', 'email_handler' ) . '</div>
          <div id="em">
            <div class="form-inline mt-1 mb-2"><label title="' . tooltip ( 'email-default-sender' ) . '">'
   . translate ( 'Default sender address' )
   . ':</label><input type="text" size="30" name="admin_EMAIL_FALLBACK_FROM" value="'
   . htmlspecialchars ( $EMAIL_FALLBACK_FROM ) . '" /></div>
            <div class="form-inline mt-1 mb-2"><label title="' . tooltip ( 'email-mailer' ) . '">'
   . translate ( 'Email Mailer' ) . ':</label>
              <select name="admin_EMAIL_MAILER" onchange="email_handler()">'
   . $option . 'smtp" ' . ( $s['EMAIL_MAILER'] == 'smtp' ? $selected : '' )
   . '>SMTP</option>' . $option . 'mail" '
   . ( $s['EMAIL_MAILER'] == 'mail' ? $selected : '' ) . '>PHP mail</option>'
   . $option . 'sendmail" '
   . ( $s['EMAIL_MAILER'] == 'sendmail' ? $selected : '' ) . '>sendmail</option>
              </select></div>
            <div id="em_smtp">
              <div class="form-inline mt-1 mb-2"><label title="' . tooltip ( 'email-smtp-host' ) . '">'
   . translate ( 'SMTP Host name(s)' )
   . ':</label><input type="text" size="50" name="admin_SMTP_HOST" value="'
   . $s['SMTP_HOST'] . '" /></div>
              <div class="form-inline mt-1 mb-2"><label title="' . tooltip ( 'email-smtp-port' ) . '">'
   . translate ( 'SMTP Port Number' )
   . ':</label><input type="text" size="4" name="admin_SMTP_PORT" value="'
   . $s['SMTP_PORT'] . '" /></div>
              <div class="form-inline mt-1 mb-2"><label title="' . tooltip ( 'email-smtp-tls' ) . '">'
   . translate ( 'Use STARTTLS' )
   . print_radio ( 'SMTP_STARTTLS', '', 'email_handler' ) . '</div>
              <div class="form-inline mt-1 mb-2"><label title="' . tooltip ( 'email-smtp-auth' ) . '">'
   . translate ( 'SMTP Authentication' ) . ':</label>'
   . print_radio ( 'SMTP_AUTH', '', 'email_handler' ) . '</div>
              <div id="em_auth">
                <div class="form-inline mt-1 mb-2"><label title="' . tooltip ( 'email-smtp-username' ) . '">'
   . translate ( 'SMTP Username' )
   . ':</label><input type="text" size="30" name="admin_SMTP_USERNAME" value="'
   . ( empty ( $s['SMTP_USERNAME'] ) ? '' : $s['SMTP_USERNAME'] ) . '" /></div>
                <div class="form-inline mt-1 mb-2"><label title="' . tooltip ( 'email-smtp-password' ) . '">'
   . translate ( 'SMTP Password' )
   . ':</label><input type="text" size="30" name="admin_SMTP_PASSWORD" value="'
   . ( empty ( $s['SMTP_PASSWORD'] ) ? '' : $s['SMTP_PASSWORD'] ) . '" /></div>
              </div>
            </div>
            <p class="bold">' . translate ( 'Default user settings' ) . ':</p>'
   . "<span id=\"default-user-settings\">\n"
   . '<div class="form-inline mt-1 mb-2"><label title="' . tooltip ( 'email-format' ) . '">'
   . translate ( 'Email format preference' ) . ':</label>'
   . print_radio ( 'EMAIL_HTML',
    ['Y'=> translate ( 'HTML' ), 'N'=>translate( 'Plain Text' )] ) . '</div>'
   . '<div class="form-inline mt-1 mb-2"><label title="' . tooltip ( 'email-include-ics' ) . '">'
   . translate ( 'Include iCalendar attachments' ) . ':</label>'
   . print_radio( 'EMAIL_ATTACH_ICS' ) . '</div>'
   . '<div class="form-inline mt-1 mb-2"><label title="' . tooltip ( 'email-event-reminders-help' ) . '">'
   . translate ( 'Event reminders' ) . ':</label>'
   . print_radio( 'EMAIL_REMINDER' ) . '</div>'
   . '<div class="form-inline mt-1 mb-2"><label title="' . tooltip ( 'email-event-added' ) . '">'
   . translate ( 'Events added to my calendar' ) . ':</label>'
   . print_radio ( 'EMAIL_EVENT_ADDED' ) . '</div>
            <div class="form-inline mt-1 mb-2"><label title="' . tooltip ( 'email-event-updated' ) . '">'
   . translate ( 'Events updated on my calendar' ) . ':</label>'
   . print_radio ( 'EMAIL_EVENT_UPDATED' ) . '</div>
            <div class="form-inline mt-1 mb-2"><label title="' . tooltip ( 'email-event-deleted' ) . '">'
   . translate ( 'Events removed from my calendar' ) . ':</label>'
   . print_radio ( 'EMAIL_EVENT_DELETED' ) . '</div>
            <div class="form-inline mt-1 mb-2"><label title="' . tooltip ( 'email-event-rejected' ) . '">'
   . translate ( 'Event rejected by participant' ) . ':</label>'
   . print_radio ( 'EMAIL_EVENT_REJECTED' ) . '</div>
            <div class="form-inline mt-1 mb-2"><label title="' . tooltip ( 'email-event-create' ) . '">'
   . translate ( 'Event that I create' ) . ':</label>'
   . print_radio ( 'EMAIL_EVENT_CREATE' ) . '</div>
          </span>
          </div>
        </div>
        </div>

<!-- BEGIN COLORS -->
  <div class="tab-pane container fade" id="' . $tabs_ar[14] . '">
  <div class="form-group">
          <fieldset class="border p-2">
            <legend>' . translate ( 'Color options' ) . '</legend>
<!-- BEGIN EXAMPLE MONTH -->
            <p style="float:right; width:45%; margin:0; background: var(--background)">
              <p id="monthtitle" class="bold" style="text-align:center; color: var(--h2color)">' . date_to_str ( date ( 'Ymd' ), $DATE_FORMAT_MY, false ) . '</p>'
   . display_month ( date ( 'm' ), date ( 'Y' ), true ) . '
            
<!-- END EXAMPLE MONTH -->
            <p class="form-inline mt-1 mb-2"><label>' . translate ( 'Allow user to customize colors' )
   . ':</label>' . print_radio ( 'ALLOW_COLOR_CUSTOMIZATION' ) . '</p>
            <p class="form-inline mt-1 mb-2"><label title="' . tooltip ( 'gradient-colors' ) . '">'
   . translate ( 'Enable gradient images for background colors' ) . ':</label>'
   . ( function_exists ( 'imagepng' ) || function_exists ( 'imagegif' )
    ? print_radio ( 'ENABLE_GRADIENTS' ) : translate ( 'Not available' ) )
   . '</p><br />' . $color_sets . '
          <div><a href="#" class="btn btn-secondary" onclick="reset_colors(); return false;">' .
          translate('Reset Colors') . '</a></div>
          </fieldset>
          <fieldset class="border p-2">
            <legend>' . translate ( 'Background Image options' ) . '</legend>
            <p class="form-inline mt-1 mb-2"><label for="admin_BGIMAGE" title="' . tooltip ( 'bgimage-help' )
   . '">' . translate ( 'Background Image' )
   . ':</label><input type="text" size="75" name="admin_BGIMAGE" id="admin_BGIMAGE" value="'
   . ( empty ( $s['BGIMAGE'] ) ? '' : htmlspecialchars ( $s['BGIMAGE'] ) ) . '" /></p>
            <p class="form-inline mt-1 mb-2"><label for="admin_BGREPEAT" title="' . tooltip ( 'bgrepeat-help' )
   . '">' . translate ( 'Background Repeat' )
   . ':</label><input type="text" size="30" name="admin_BGREPEAT" id="admin_BGREPEAT" value="'
   . ( empty ( $s['BGREPEAT'] ) ? '' : $s['BGREPEAT'] ) . '" /></p>
          </fieldset>
        </div>
      </div>
      </div>
      <div style="clear:both;">
        <input class="btn btn-primary" type="submit" value="' . $saveStr . '" name="" />
      </div>
    </form>
  </div>
</div>
</div>';

echo "\n<script>\n";

// Change the color in the current page
foreach ( $colors as $k => $v ) {
  echo "function color_change_handler_$k() {\n";
    echo "  var color = $('#admin_" . $k . "').val();\n";
    echo "  $('body').get(0).style.setProperty('--" . strtolower($k) . "', color);\n";
  echo "}\n";
}

?>
function reset_colors() {
  <?php
    foreach ( $colors as $k => $v ) {
      echo "  $('body').get(0).style.setProperty('--" . strtolower($k) . "', '$GLOBALS[$k]');\n";
      echo "  $('#admin_" . $k . "').val('$GLOBALS[$k]');\n";
    }
  ?>
}

</script>
<?php

} else {
  // if $error
  echo print_error ( $error, true );
}

echo print_trailer();
?>
