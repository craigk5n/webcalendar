<?php /* $Id$ */
include_once 'includes/init.php';
include_once 'includes/date_formats.php';
if ( file_exists ( 'install/default_config.php' ) )
  include_once 'install/default_config.php';

function save_pref ( $prefs, $src ) {
  global $error, $my_theme;

  while ( list ( $key, $value ) = each ( $prefs ) ) {
    if ( $src == 'post' ) {
      $prefix = substr ( $key, 0, 6 );
      $setting = substr ( $key, 6 );
      if ( $key == 'currenttab' )
        continue;

      // Validate key name. Should start with "admin_" and not include
      // any unusual characters that might be an SQL injection attack.
      if ( ! preg_match ( '/admin_[A-Za-z0-9_]+$/', $key ) )
        die_miserable_death ( str_replace ( 'XXX', $key,
            translate ( 'Invalid setting name XXX.' ) ) );
    } else {
      $prefix = 'admin_';
      $setting = $key;
    }
    if ( strlen ( $setting ) > 0 && $prefix == 'admin_' ) {
      if ( $setting == 'THEME' && $value != 'none' )
        $my_theme = strtolower ( $value );

      $setting = strtoupper ( $setting );
      $sql = 'DELETE FROM webcal_config WHERE cal_setting = ?';
      if ( ! dbi_execute ( $sql, array ( $setting ) ) ) {
        $error = db_error ( false, $sql );
        break;
      }
      if ( strlen ( $value ) > 0 ) {
        $sql = 'INSERT INTO webcal_config ( cal_setting, cal_value ) VALUES ( ?, ? )';
        if ( ! dbi_execute ( $sql, array ( $setting, $value ) ) ) {
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
  $currenttab = getPostValue ( 'currenttab' );
  $my_theme = '';

  save_pref ( $_POST, 'post' );

  if ( ! empty ( $my_theme ) ) {
    include_once 'themes/' . strtolower ( $my_theme ) . '.php';
    save_pref ( $webcal_theme, 'theme' );
  }
}

// Load any new config settings. Existing ones will not be affected.
// This function is in "install/default_config.php".
if ( function_exists ( 'db_load_config' ) && empty ( $_POST ) )
  db_load_config();

$s = array();

$res = dbi_execute ( 'SELECT cal_setting, cal_value FROM webcal_config' );

if ( $res ) {
  while ( $row = dbi_fetch_row ( $res ) ) {
    $setting = $row[0];
    $s[$setting] = $value = $row[1];
  }
  dbi_free_result ( $res );
}

// Get list of theme files from /themes directory.
$dir = 'themes';
if ( is_dir ( $dir ) ) {
  if ( $dh = opendir ( $dir ) ) {
    while ( ( $file = readdir ( $dh ) ) !== false ) {
      if ( strpos ( $file, '_admin.php' ) ) {
        $themes[0][] = strtoupper ( str_replace ( '_admin.php', '', $file ) );
        $themes[1][] = strtoupper ( str_replace ( '.php', '', $file ) );
      } else
      if ( strpos ( $file, '_pref.php' ) ) {
        $themes[0][] = strtolower ( str_replace ( '_pref.php', '', $file ) );
        $themes[1][] = strtolower ( str_replace ( '.php', '', $file ) );
      }
    }
    sort ( $themes );
    closedir ( $dh );
  }
}

// Get list of menu themes.
$dir = 'includes/menu/themes/';
if ( is_dir ( $dir ) ) {
  if ( $dh = opendir ( $dir ) ) {
    while ( ( $file = readdir ( $dh ) ) !== false ) {
      if ( $file == '.' || $file == '..' || $file == 'CVS' )
        continue;

      if ( is_dir ( $dir . $file ) )
        $menuthemes[] = $file;
    }
    closedir ( $dh );
  }
}

$currenttab = getPostValue ( 'currenttab', 'settings' );
$currenttab = ( empty( $currenttab ) ? 'settings' : $currenttab );

ob_start();
setcookie( 'currenttab', $currenttab );
print_header();

if ( ! $error ) {
  // Make sure globals values passed to styles.php are for this user.
  // Makes the demo calendar and Page title accurate.
  $GLOBALS['APPLICATION_NAME'] = $s['APPLICATION_NAME'];
  $GLOBALS['BGCOLOR'] = $s['BGCOLOR'];
  $GLOBALS['CELLBG'] = $s['CELLBG'];
  $GLOBALS['FONTS'] = $s['FONTS'];
  $GLOBALS['H2COLOR'] = $s['H2COLOR'];
  $GLOBALS['HASEVENTSBG'] = $s['HASEVENTSBG'];
  $GLOBALS['MENU_THEME'] = $s['MENU_THEME'];
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

  // Allow css_cache of webcal_config values.
  @session_start();
  $_SESSION['webcal_tmp_login'] = 'blahblahblah';

  $choices = array(
    'day'  => translate( 'Day' ),
    'week' => translate( 'Week' ),
    'month'=> translate( 'Month' ),
    'year' => translate( 'Year' ),
  );

  $bottomStr = translate ( 'Bottom' );
  $topStr = translate ( 'Top' );

  $anyoneStr = translate ( 'Anyone' );
  $partyStr = translate ( 'Participant' );

  $color_sets = $lang_list = $menu_theme_list = $prefer_vu = '';
  $start_wk_on = $start_wkend_on = $tabs = $theme_list = $user_vu = '';
  $work_hr_end = $work_hr_start = '';

  // This should be easier to add more tabs if needed.
  foreach ( array(
      'settings'=> $setsStr,
      'public'  => translate( 'Public Access' ),
      'uac'     => translate( 'UAC' ),
      'groups'  => $groupsStr,
      'nonuser' => translate( 'NUCs' ),
      'other'   => translate( 'Other' ),
      'email'   => translate( 'Email' ),
      'colors'  => translate( 'Colors' ),
    ) as $k => $v ) {
    $tabs .= '
        <span class="tab' . ( $k != 'settings' ? 'bak' : 'for' ) . '" id="tab_'
     . $k . '">' . $v . '</span>';
  }
  // Move the loops here and combine a few.
  while ( list( $k, $v ) = each( $languages ) ) {
    $lang_list .= $option . $v
     . ( $v == $s['LANGUAGE'] ? '" selected>' : '">' ) . $k . '</option>';
  }
  for ( $i = 0; $themes[0][$i]; $i++ ) {
    $theme_list .= $option . $themes[1][$i] . '">' . $themes[0][$i] . '</option>';
  }
  for ( $i = 0; $i < 7; $i++ ) {
    $start_wk_on .= $option . $i
     . ( $i == $s['WEEK_START'] ? '" selected>' : '">' )
     . weekday_name( $i ) . '</option>';
    $j = ( $i == 0 ? 6 : $i - 1 ); // Make sure to start with Saturday.
    $start_wkend_on .= $option . $j
     . ( $j == $s['WEEKEND_START'] ? '" selected>' : '">' )
     . weekday_name( $j ) . '</option>';
  }
  for ( $i = 0; $i < 24; $i++ ) {
    $tmp = display_time ( $i * 10000, 1 );
    $work_hr_start .= $option . $i
     . ( $i == $s['WORK_DAY_START_HOUR'] ? '" selected>' : '">' )
     . $tmp . '</option>';
    $work_hr_end .= $option . $i
     . ( $i == $s['WORK_DAY_END_HOUR'] ? '" selected>' : '">' )
     . $tmp . '</option>';
  }
  foreach ( $choices as $k => $v ) {
    $k .= '.php';
    $prefer_vu .= $option . $k . ( $s['STARTVIEW'] == $k ? '" selected>' : '">' )
     . $v . '</option>';
  }
  // Allow user to select a view also.
  foreach ( $views as $i ) {
    if ( $i['cal_is_global'] != 'Y' )
      continue;

    $xurl = $i['url'];
    $xurl_strip = str_replace ( '&amp;', '&', $xurl );
    $user_vu .= $option . $xurl
     . ( $s['STARTVIEW'] == $xurl_strip ? '" selected>' : '">' )
     . $i['cal_name'] . '</option>';
  }
  foreach ( $menuthemes as $menutheme ) {
    $menu_theme_list .= $option . $menutheme
     . ( $s['MENU_THEME'] == $menutheme ? '" selected>' : '">' )
     . $menutheme . '</option>';
  }
  foreach ( array( // Document color choices.
      'H2COLOR'     => translate( 'Document title' ),
      'BGCOLOR'     => translate( 'Document BG' ),
      'TEXTCOLOR'   => translate( 'Document text' ),
      'MYEVENTS'    => translate( 'My event text_' ),
      'THBG'        => translate( 'Table header BG' ),
      'THFG'        => translate( 'Table header text' ),
      'TABLEBG'     => translate( 'Table grid color' ),
      'CELLBG'      => translate( 'Table cell BG' ),
      'HASEVENTSBG' => translate( 'Table cell events BG' ),
      'OTHERMONTHBG'=> translate( 'Table cell other month BG' ),
      'TODAYCELLBG' => translate( 'Table cell today BG' ),
      'WEEKENDBG'   => translate( 'Table cell weekends BG' ),
      'POPUP_BG'    => translate( 'Event popup BG' ),
      'POPUP_FG'    => translate( 'Event popup text' ),
      'WEEKNUMBER'  => translate( 'Week number color' ),
    ) as $k => $v ) {
    $color_sets .= print_color_input_html ( $k, $v );
  }

  set_today ( date ( 'Ymd' ) );

  echo '
    <h2>' . translate ( 'System Settings' )
   . '<img src="images/help.gif" alt="' . $helpStr  . '" class="help"></h2>
    <form action="admin.php" method="post" id="prefform" name="prefform">'
   . display_admin_link() . '
      <input type="hidden" id="currenttab" name="currenttab" value="'
   . $currenttab . '">
      <input type="submit" value="' . $saveStr . '" name=""><br><br>

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
   . '</label>
              <input type="text" id="admin_APPLICATION_NAME" '
   . 'name="admin_APPLICATION_NAME" size="40" value="'
   . htmlspecialchars( $s['APPLICATION_NAME'] ) . '">'
   . ( $s['APPLICATION_NAME'] == 'Title'
    ? str_replace ( 'XXX', translate ( 'Title' ),
      translate ( 'Translated Name (XXX)' ) ) : '' ) . '</p>
            <p><label for="admin_SERVER_URL" title="'
   . tooltip( 'server_url_help' ) . '">' . translate( 'Server URL' ) . '</label>
              <input type="text" id="admin_SERVER_URL" name="admin_SERVER_URL" '
   . 'size="70" value="' . htmlspecialchars( $s['SERVER_URL'] ) . '"></p>
            <p><label for="admin_HOME_LINK" title="'
   . tooltip ( 'home-url-help' ) . '">' . translate ( 'Home URL' ) . '</label>
              <input type="text" id="admin_HOME_LINK" name="admin_HOME_LINK" '
   . 'size="40" value="'
   . ( empty ( $s['HOME_LINK'] ) ? '' : htmlspecialchars ( $s['HOME_LINK'] ) )
   . '"></p>
            <p><label for="admin_LANGUAGE" title="' . tooltip ( 'language-help' )
   . '">' . translate ( 'Language_' ) . '</label>
              <select name="admin_LANGUAGE" id="admin_LANGUAGE">' . $lang_list . '
              </select>'
   . str_replace( 'XXX', translate( get_browser_language( true ) ),
    translate( 'browser default language XXX' ) ) . '</p>
            <p><label>' . translate( 'Allow user themes' ) . '</label>'
   . print_radio ( 'ALLOW_USER_THEMES' ) . '</p>
            <p><label for="admin_THEME" title="' . tooltip ( 'themes-help' )
   . '">' . translate ( 'Themes_' ) . '</label>
              <select name="admin_THEME" id="admin_THEME">
                <option disabled>' . translate( 'AVAILABLE THEMES' ) . '</option>'
   /* Always use 'none' as default so we don't overwrite manual settings. */
   . $option . 'none" selected>' . $noneStr . '</option>'
   . $theme_list . '
              </select>
              <input type="button" id="previewBtn" value="'
   . translate( 'Preview' ) . '"></p>
          </fieldset>
          <fieldset>
            <legend>' . translate( 'Site customization' ) . '</legend>';
  foreach ( array(
      // tooltip( 'custom_script_help' )
      'custom_script' => translate( 'Custom script' ),
      // tooltip( 'custom_header_help' )
      'custom_header' =>translate( 'Custom header' ),
      // tooltip( 'custom_trailer_help' )
      'custom_trailer' => translate( 'Custom trailer' ),
    ) as $k => $v ) {
    $tmp = strtoupper( $k );
    echo '
            <p><label title="' . tooltip( $k . '_help' ) . '">' . $v . '</label>'
     . print_radio( $tmp ) . '
              <input type="button" id="btn' . substr( $tmp, 7, 1 )
     . '" value="' . $editStr . '"></p>';
  }
  echo '
            <p><label title="' . tooltip( 'allow_external_header_help' ) . '">'
   . translate ( 'externals head/script/trail' ) . '</label>'
   . print_radio ( 'ALLOW_EXTERNAL_HEADER' ) . '</p>
            <p><label>' . translate( 'may user override head/trail' ) . '</label>'
   . print_radio( 'ALLOW_USER_HEADER' ) . '</p>
          </fieldset>
          <fieldset>
            <legend>' . translate ( 'Date and Time' ) . '</legend>'
   /* Can we set timezones? If not don't display any options. */
   . ( set_env ( 'TZ', $s['SERVER_TIMEZONE'] ) ? '
            <p><label for="admin_SERVER_TIMEZONE" title="'
     . tooltip( 'server_tz_help' ) . '">' . translate( 'Server TZ Selection' )
     . '</label>'
     . print_timezone_select_html( 'admin_SERVER_', $s['SERVER_TIMEZONE'] )
     . '</p>' : '' ) . '
            <p><label for="admin_TIMEZONE" title="'
   . tooltip( 'tz_help' ) . '">' . translate( 'Default Client TZ Selection' )
   . '</label>' . print_timezone_select_html( 'admin_', $s['TIMEZONE'] ) . '</p>
            <p><label title="' . tooltip( 'display_general_use_gmt_help' )
   . '">' . translate ( 'GMT Common Use Date/Times' ) . '</label>'
   . print_radio ( 'GENERAL_USE_GMT' ) . '</p>
            <p><label title="' . tooltip( 'date_format_help' ) . '">'
   . translate ( 'Date format' ) . '</label>
              <select name="admin_DATE_FORMAT">' . $datestyle_ymd . '
              </select>' . $choices['month'] . ' ' . $choices['day'] . ' '
   . $choices['year'] . '</p>
            <p><label>&nbsp;</label>
              <select name="admin_DATE_FORMAT_MY">' . $datestyle_my . '
              </select>' . $choices['month'] . ' ' . $choices['year'] . '</p>
            <p><label>&nbsp;</label>
              <select name="admin_DATE_FORMAT_MD">' . $datestyle_md . '
              </select>' . $choices['month'] . ' ' . $choices['day'] . '</p>
            <p><label>&nbsp;</label>
              <select name="admin_DATE_FORMAT_TASK">' . $datestyle_tk . '
              </select>' . translate ( 'Small Task Date' ) . '</p>
            <p><label title="' . tooltip( 'display_week_starts_on' ) . '">'
   . translate ( 'Week starts on' ) . '</label>
              <select name="admin_WEEK_START" id="admin_WEEK_START">'
   . $start_wk_on . '
              </select></p>
            <p><label title="' . tooltip( 'display_weekend_starts_on' ) . '">'
   . translate( 'Weekend starts on' ) . '</label>
              <select name="admin_WEEKEND_START" id="admin_WEEKEND_START">'
   . $start_wkend_on . '
              </select></p>
            <p><label title="' . tooltip( 'time_format_help' ) . '">'
   . translate ( 'Time format' ) . '</label>' . print_radio ( 'TIME_FORMAT',
    array ( '12' => translate ( '12 hour' ), '24' => translate ( '24 hour' ) ) )
   . '</p>
            <p><label title="' . tooltip( 'timed_evt_len_help' ) . '">'
   . translate ( 'Specify timed event length by' ) . '</label>'
   . print_radio ( 'TIMED_EVT_LEN',
    array ( 'D' => translate ( 'Duration' ), 'E' => translate ( 'End Time' ) ) )
   . '</p>
            <p><label for="admin_WORK_DAY_START_HOUR" title="'
   . tooltip( 'work_hours_help' ) . '">' . translate( 'Work hours' )
   . '</label>' . translate ( 'From' ) . '
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
   . tooltip( 'preferred_view_help' ) . '">' . translate( 'Preferred view' )
   . '</label>
              <select name="admin_STARTVIEW" id="admin_STARTVIEW">' . $prefer_vu
   . $user_vu . '
              </select></p>
            <p><label>' . translate( 'Allow top menu' ) . '</label>'
   . print_radio ( 'MENU_ENABLED' ) . '</p>
            <p><label>' . translate( 'Date Selectors position' ) . '</label>'
   . print_radio ( 'MENU_DATE_TOP', array ( 'Y' => $topStr, 'N' => $bottomStr ) )
   . '</p>
            <p><label for="admin_MENU_THEME" title="'
   . tooltip( 'menu_themes_help' ) . '">' . translate( 'Menu theme' ) . '</label>
              <select name="admin_MENU_THEME" id="admin_MENU_THEME">' . $option
   . 'none" selected>' . $noneStr . '</option>'
   . $menu_theme_list . '
              </select></p>
            <p><label for="admin_FONTS" title="' . tooltip( 'fonts_help' )
   . '">' . translate ( 'Fonts' )
   . '</label><input type="text" name="admin_FONTS" id="admin_FONTS" size="40" '
   . 'value="' . htmlspecialchars( $s['FONTS'] ) . '"></p>';
  foreach ( array(
      // tooltip( 'display_sm_month_help' )
      'sm_month' => translate( 'Display small months' ),
      // tooltip( 'display_weekends_help' )
      'weekends' => translate( 'Display weekends' ),
      // tooltip( 'display_long_days_help' )
      'long_days' => translate( 'Display long day names' ),
      // tooltip( 'display_all_days_in_month_help' )
      'all_days_in_month' => translate( 'Display all days in month view' ),
      // tooltip( 'display_weeknumber_help' )
      'weeknumber' => translate( 'Display week number' ),
      // tooltip( 'display_desc_print_day_help' )
      'desc_print_day' => translate( 'desc in printer day view' ),
      //  tooltip( 'display_bold_days_in_year_help' )
      'bold_days_in_year' => translate( 'bold events month/year views' ),
      // tooltip( 'display_minutes_help' )
      'minutes' => translate( 'Display 00 minutes always' ),
      // tooltip( 'display_end_times_help' )
      'end_times' => translate( 'Display end times on calendars' ),
      // tooltip( 'display_add_link_in_views_help' )
      'add_link_in_views' => translate( 'Include add event link in views' ),
      // tooltip( 'display_moon_phases_help' )
      'moon_phases' => translate( 'Display Lunar Phases in month view' ),
    ) as $k => $v ) {
    $tmp = 'display_' . $k;
    echo '
            <p><label title="' . tooltip( $tmp .  '_help' ) . '">' . $v . '</label>'
     . print_radio( strtoupper( $tmp ) ) . '</p>';
  }
  echo '
          </fieldset>
          <fieldset>
            <legend>' . translate ( 'Restrictions' ) . '</legend>
            <p><label title="' . tooltip( 'allow_view_other_help' ) . '">'
   . translate ( 'may view others cals' ) . '</label>'
   . print_radio ( 'ALLOW_VIEW_OTHER' ) . '</p>
            <p><label title="' . tooltip( 'require_approvals_help' ) . '">'
   . translate ( 'Require event approvals' ) . '</label>'
   . print_radio ( 'REQUIRE_APPROVALS' ) . '</p>
            <p><label title="' . tooltip( 'display_unapproved_help' ) . '">'
   . translate ( 'Display unapproved' ) . '</label>'
   . print_radio ( 'DISPLAY_UNAPPROVED' ) . '</p>
            <p><label title="' . tooltip( 'allow_conflicts_help' ) . '">'
   . translate ( 'Check for conflicts' ) . '</label>'
   /* This control is logically reversed. */
   . print_radio ( 'ALLOW_CONFLICTS',
     array( 'N' => $yesStr, 'Y' => $noStr ) ) . '</p>
            <p><label title="' . tooltip( 'conflict_months_help' ) . '">'
   . translate ( 'Conflict checking months' ) . '</label>
              <input type="text" name="admin_CONFLICT_REPEAT_MONTHS" size="3" '
   . 'value="' . htmlspecialchars( $s['CONFLICT_REPEAT_MONTHS'] ) . '"></p>
            <p><label title="' . tooltip( 'allow_conflict_override_help' )
   . '">' . translate ( 'may users override conflicts' ) . '</label>'
   . print_radio ( 'ALLOW_CONFLICT_OVERRIDE' ) . '</p>
            <p><label title="' . tooltip( 'limit_appts_help' ) . '">'
   . translate ( 'Limit timed events per day' ) . '</label>'
   . print_radio ( 'LIMIT_APPTS' ) . '</p>
            <p><label title="' . tooltip( 'limit_appts_number_help' ) . '">'
   . translate ( 'Maximum timed events per day' ) . '</label>
              <input type="text" name="admin_LIMIT_APPTS_NUMBER" size="3" value="'
   . htmlspecialchars( $s['LIMIT_APPTS_NUMBER'] ) . '"></p>
            <p><label title="' . tooltip( 'disable_crossday_events_help' ) . '">'
   . translate( 'Disable Cross-Day Events' ) . '</label>'
   . print_radio ( 'DISABLE_CROSSDAY_EVENTS' ) . '</p>
          </fieldset>
          <fieldset>
            <legend>' . translate( 'Events' ) . '</legend>';
  foreach ( array(
      // tooltip( 'disable_location_field_help' )
      'location' => translate( 'Disable Location field' ),
      // tooltip( 'disable_url_field_help' )
      'url' => translate( 'Disable URL field' ),
      // tooltip( 'disable_priority_field_help' )
      'priority' => translate( 'Disable Priority field' ),
      // tooltip( 'disable_access_field_help' )
      'access' => translate( 'Disable Access field' ),
      // tooltip( 'disable_participants_field_help' )
      'participants' => translate( 'Disable Participants field' ),
      // tooltip( 'disable_repeating_field_help' )
      'repeating' => translate( 'Disable Repeating field' ),
    ) as $k => $v ) {
    $tmp = 'disable_' . $k . '_field';
    echo '
            <p><label title="' . tooltip( $tmp . '_help' ) . '">' . $v . '</label>'
     . print_radio( strtoupper( $tmp ) ) . '</p>';
  }
  echo '
            <p><label title="' . tooltip( 'allow_html_description_help' )
   . '">' . translate ( 'Allow HTML in Description' ) . '</label>'
   . print_radio ( 'ALLOW_HTML_DESCRIPTION' ) . '</p>
          </fieldset>
          <fieldset>
            <legend>' . translate ( 'Popups' ) . '</legend>
            <p><label title="' . tooltip( 'disable_popups_help' ) . '">'
   . translate( 'Disable Pop-Ups' ) . '</label>'
   . print_radio ( 'DISABLE_POPUPS', '', 'popup_handler' ) . '</p>
            <div id="pop">
              <p><label title="' . tooltip( 'site_extras_in_popup_help' )
   . '">' . translate ( 'Display Site Extras in popup' ) . '</label>'
   . print_radio ( 'SITE_EXTRAS_IN_POPUP' ) . '</p>
              <p><label title="' . tooltip( 'participants_in_popup_help' )
   . '">' . translate( 'Display Participants in popup' ) . '</label>'
   . print_radio ( 'PARTICIPANTS_IN_POPUP' ) . '</p>
            </div>
          </fieldset>
          <fieldset>
            <legend>' . translate ( 'Miscellaneous' ) . '</legend>
            <p><label title="' . tooltip( 'remember_last_login_help' ) . '">'
   . translate ( 'Remember last login' ) . '</label>'
   . print_radio ( 'REMEMBER_LAST_LOGIN' ) . '</p>
            <p><label title="' . tooltip( 'summary_length_help' ) . '">'
   . translate ( 'Brief Description Length' )
   . '</label><input type="text" name="admin_SUMMARY_LENGTH" size="3" value="'
   . $s['SUMMARY_LENGTH'] . '"></p>
            <p><label for="admin_USER_SORT_ORDER" title="'
   . tooltip( 'user_sort_help' ) . '">' . translate( 'User Sort Order' )
   . '</label>
              <select name="admin_USER_SORT_ORDER" id="admin_USER_SORT_ORDER">'
   . $option . 'cal_lastname, cal_firstname" '
   . ( $s['USER_SORT_ORDER'] == 'cal_lastname, cal_firstname'
     ? ' selected>' : '>' )
   . translate( 'Lastname, Firstname' ) . '</option>' . $option
   . 'cal_firstname, cal_lastname" '
   . ( $s['USER_SORT_ORDER'] == 'cal_firstname, cal_lastname'
     ? ' selected>' : '>' )
   . translate( 'Firstname, Lastname' ) . '</option>
              </select></p>
          </fieldset>
        </div>
<!-- END SETTINGS -->

<!-- BEGIN PUBLIC ACCESS -->
        <div id="tabscontent_public">
          <p><label title="' . tooltip( 'allow_public_access_help' ) . '">'
   . translate ( 'Allow public access' ) . '</label>'
   . print_radio ( 'PUBLIC_ACCESS', '', 'public_handler' ) . '</p>
          <div id="pa">
            <p><label title="' . tooltip ( 'public-access-default-visible' )
   . '">' . translate ( 'Public visible by default' ) . '</label>'
   . print_radio ( 'PUBLIC_ACCESS_DEFAULT_VISIBLE' ) . '</p>
            <p><label title="' . tooltip ( 'public-access-default-selected' )
   . '">' . translate ( 'is public default party' ) . '</label>'
   . print_radio ( 'PUBLIC_ACCESS_DEFAULT_SELECTED' ) . '</p>
            <p><label title="' . tooltip ( 'public-access-view-others-help' )
   . '">' . translate ( 'may public view others' ) . '</label>'
   . print_radio ( 'PUBLIC_ACCESS_OTHERS' ) . '</p>
            <p><label title="' . tooltip ( 'public-access-can-add-help' ) . '">'
   . translate ( 'may public add events' ) . '</label>'
   . print_radio ( 'PUBLIC_ACCESS_CAN_ADD' ) . '</p>
            <p><label title="' . tooltip ( 'public-access-add-requires-approval-help' )
   . '">' . translate ( 'must approve public events' ) . '</label>'
   . print_radio ( 'PUBLIC_ACCESS_ADD_NEEDS_APPROVAL' ) . '</p>
            <p><label title="' . tooltip ( 'public-access-sees-participants-help' )
   . '">' . translate ( 'may public see participants' ) . '</label>'
   . print_radio ( 'PUBLIC_ACCESS_VIEW_PART' ) . '</p>
            <p><label title="' . tooltip ( 'public-access-override-help' ) . '">'
   . translate ( 'Override public event name/desc' )
   . '</label>' . print_radio ( 'OVERRIDE_PUBLIC' ) . '</p>
            <p><label title="' . tooltip ( 'public-access-override-text-help' )
   . '">' . translate ( 'public text display' )
   . '</label><input type="text" name="admin_OVERRIDE_PUBLIC_TEXT" size="25" value="'
   . $s['OVERRIDE_PUBLIC_TEXT'] . '"></p>
            <p><label title="' . tooltip( 'public_access_captcha_help' ) . '">'
   . translate ( 'require public CAPTCHA' )
   . '</label>' . print_radio ( 'ENABLE_CAPTCHA' ) . '</p>
           <div style="clear:both;"></div>
          </div>
        </div>

<!-- BEGIN USER ACCESS CONTROL -->
        <p id="tabscontent_uac"><label title="' . tooltip ( 'uac-enabled-help' )
   . '">' . translate( 'UAC enabled' ) . '</label>'
   . print_radio ( 'UAC_ENABLED' ) . '</p>

<!-- BEGIN GROUPS -->
        <div id="tabscontent_groups">
          <p><label title="' . tooltip ( 'groups-enabled-help' ) . '">'
   . translate ( 'Groups enabled' ) . '</label>'
   . print_radio ( 'GROUPS_ENABLED' ) . '</p>
          <p><label title="' . tooltip ( 'user-sees-his-group-help' ) . '">'
   . translate ( 'User sees only his groups' ) . '</label>'
   . print_radio ( 'USER_SEES_ONLY_HIS_GROUPS' ) . '</p>
        </div>

<!-- BEGIN NONUSER -->
        <div id="tabscontent_nonuser">
          <p><label title="' . tooltip ( 'nonuser-enabled-help' ) . '">'
   . translate ( 'NUCs enabled' ) . '</label>'
   . print_radio ( 'NONUSER_ENABLED' ) . '</p>
          <p><label title="' . tooltip ( 'nonuser-list-help' ) . '">'
   . translate ( 'list NUCs at' ) . '</label>'
   . print_radio ( 'NONUSER_AT_TOP', array ( 'Y' => $topStr, 'N' => $bottomStr ) )
   . '</p>
        </div>

        <div id="tabscontent_other">
<!-- BEGIN UPCOMING EVENTS -->
          <fieldset>
            <legend>' . translate( 'Upcoming Events' ) . '</legend>
            ' . htmlspecialchars( $SERVER_URL ) . 'upcoming.php<br>
            <p><label title="' . tooltip( 'upcoming_events_help' ) . '">'
   . translate ( 'Enabled_' ) . '</label>'
   . print_radio ( 'UPCOMING_EVENTS', '', '', 'N' ) . '</p>
            <p><label title="' . tooltip( 'upcoming_events_allow_override' )
   . '">' . translate( 'Allow user override' ) . '</label>'
   . print_radio ( 'UPCOMING_ALLOW_OVR', '', '', 'N' ) . '</p>
            <p><label title="' . tooltip( 'upcoming_events_display_caticons' )
   . '">' . translate( 'Display category icons' ) . '</label>'
   . print_radio ( 'UPCOMING_DISPLAY_CAT_ICONS', '', '', 'Y' ) . '</p>
            <p><label title="' . tooltip( 'upcoming_events_display_layers' )
   . '">' . translate( 'Display layers' ) . '</label>'
   . print_radio ( 'UPCOMING_DISPLAY_LAYERS', '', '', 'N' ) . '</p>
            <p><label title="' . tooltip( 'upcoming_events_display_links' )
   . '">' . translate( 'Display links to events' ) . '</label>'
   . print_radio ( 'UPCOMING_DISPLAY_LINKS', '', '', 'Y' ) . '</p>
            <p><label title="' . tooltip( 'upcoming_events_display_popups' )
   . '">' . translate( 'Display event popups' ) . '</label>'
   . print_radio ( 'UPCOMING_DISPLAY_POPUPS', '', '', 'Y' ) . '</p>
          </fieldset>

<!-- BEGIN REPORTS -->
          <p><label title="' . tooltip ( 'reports-enabled-help' ) . '">'
   . translate ( 'Reports enabled' ) . '</label>'
   . print_radio ( 'REPORTS_ENABLED' ) . '</p>

<!-- BEGIN PUBLISHING -->
          <p><label title="' . tooltip ( 'subscriptions-enabled-help' ) . '">'
   . translate ( 'Allow remote subscriptions' ) . '</label>'
   . print_radio ( 'PUBLISH_ENABLED' ) . '</p>'
  /* Determine if allow_url_fopen is enabled. */
   . ( preg_match ( '/(On|1|true|yes)/i', ini_get ( 'allow_url_fopen' ) ) ? '
          <p><label title="' . tooltip ( 'remotes-enabled-help' ) . '">'
     . translate( 'Allow remote calendars' ) . '</label>'
     . print_radio ( 'REMOTES_ENABLED' ) . '</p>' : '' ) . '
          <p><label title="' . tooltip ( 'rss-enabled-help' ) . '">'
   . translate ( 'Enable RSS feed' ) . '</label>'
   . print_radio ( 'RSS_ENABLED' ) . '</p>

<!-- BEGIN CATEGORIES -->
          <p><label title="' . tooltip ( 'categories-enabled-help' ) . '">'
   . translate ( 'Categories enabled' ) . '</label>'
   . print_radio ( 'CATEGORIES_ENABLED' ) . '</p>
          <p><label title="' . tooltip ( 'icon_upload-enabled-help' ) . '">'
   . translate( 'Category Icon Upload enabled' ) . '</label>'
   . print_radio( 'ENABLE_ICON_UPLOADS' ) . ( is_dir( 'icons/' ) ? ''
     : str_replace( 'XXX', 'icons',
        translate( '(Requires XXX folder to exist.)' ) ) ) . '</p>

<!-- DISPLAY TASK PREFERENCES -->
          <p><label title="' . tooltip( 'display_tasks_help' ) . '">'
   . translate( 'Display small task list' ) . '</label>'
   . print_radio ( 'DISPLAY_TASKS' ) . '</p>
          <p><label title="' . tooltip( 'display_tasks_in_grid_help' ) . '">'
   . translate( 'Display tasks in Calendars' ) . '</label>'
   . print_radio ( 'DISPLAY_TASKS_IN_GRID' ) . '</p>

<!-- BEGIN EXT PARTICIPANTS -->
          <p><label title="' . tooltip ( 'allow-external-users-help' ) . '">'
   . translate ( 'Allow external users' ) . '</label>'
   . print_radio ( 'ALLOW_EXTERNAL_USERS', '', 'eu_handler' ) . '</p>
          <div id="eu">
            <p><label title="' . tooltip ( 'external-can-receive-notification-help' )
   . '">' . translate ( 'may notify externals by email' )
   . '</label>' . print_radio ( 'EXTERNAL_NOTIFICATIONS' ) . '</p>
            <p><label title="' . tooltip ( 'external-can-receive-reminder-help' )
   . '">' . translate ( 'may remind externals by email' )
   . '</label>' . print_radio ( 'EXTERNAL_REMINDERS' ) . '</p>
          </div>

 <!-- BEGIN SELF REGISTRATION -->
          <p><label title="' . tooltip ( 'allow-self-registration-help' ) . '">'
   . translate( 'Allow self-registration' ) . '</label>'
   . print_radio ( 'ALLOW_SELF_REGISTRATION', '', 'sr_handler' ) . '</p>
          <div id="sr">
            <p><label title="' . tooltip ( 'use-blacklist-help' ) . '">'
   . translate ( 'Restrict self-reg to blacklist' ) . '</label>'
   . print_radio ( 'SELF_REGISTRATION_BLACKLIST' ) . '</p>
            <p><label title="' . tooltip ( 'allow-self-reg-full-help' )
   . '">' . translate ( 'send self-reg emails' )
   . '</label>' . print_radio( 'SELF_REGISTRATION_FULL' ) . '</p><br>
          </div>

<!-- TODO add account aging feature. -->

<!-- BEGIN ATTACHMENTS/COMMENTS -->
          <div>
            <p><label title="' . tooltip( 'allow_attachment_help' ) . '">'
   . translate( 'Allow event attachments' ) . '</label>'
   . print_radio( 'ALLOW_ATTACH', '', 'attach_handler' ) . '</p>
            <p id="at1">' . translate( 'owner can attach if enabled' )
   . print_checkbox( array( 'ALLOW_ATTACH_PART', 'Y', $partyStr ) )
   . print_checkbox( array( 'ALLOW_ATTACH_ANY', 'Y', $anyoneStr ) ) . '</p>
            <p><label title="' . tooltip( 'allow_comments_help' ) . '">'
   . translate( 'Allow event comments' ) . '</label>'
   . print_radio( 'ALLOW_COMMENTS', '', 'comment_handler' ) . '</p>
            <p id="com1">' . translate( 'owner can comment if enabled' )
   . print_checkbox( array( 'ALLOW_COMMENTS_PART', 'Y', $partyStr ) )
   . print_checkbox( array( 'ALLOW_COMMENTS_ANY', 'Y', $anyoneStr ) ) . '</p>
          </div>
        </div>

<!-- BEGIN EMAIL -->
        <div id="tabscontent_email">
          <p><label title="' . tooltip ( 'email-enabled-help' ) . '">'
   . translate ( 'Email enabled' ) . '</label>'
   . print_radio ( 'SEND_EMAIL', '', 'email_handler' ) . '</p>
          <div id="em">
            <p><label title="' . tooltip ( 'email-default-sender' ) . '">'
   . translate ( 'Default sender address' )
   . '</label><input type="text" name="admin_EMAIL_FALLBACK_FROM" size="30" '
   . 'value="' . htmlspecialchars( $EMAIL_FALLBACK_FROM ) . '"></p>
            <p><label title="' . tooltip ( 'email-mailer' ) . '">'
   . translate( 'Email Mailer' ) . '</label>
              <select name="admin_EMAIL_MAILER" onchange="email_handler()">'
   . $option . 'smtp"' . ( $s['EMAIL_MAILER'] == 'smtp' ? ' selected' : '' ) . '>SMTP</option>'
   . $option . 'mail"' . ( $s['EMAIL_MAILER'] == 'mail' ? ' selected' : '' ) . '>PHP mail</option>'
   . $option . 'sendmail"' . ( $s['EMAIL_MAILER'] == 'sendmail' ? ' selected' : '' ) . '>sendmail</option>
              </select></p>
            <div id="em_smtp">
              <p><label title="' . tooltip ( 'email-smtp-host' ) . '">'
   . translate ( 'SMTP Host name(s)' )
   . '</label><input type="text" name="admin_SMTP_HOST" size="50" value="'
   . $s['SMTP_HOST'] . '"></p>
              <p><label title="' . tooltip ( 'email-smtp-port' ) . '">'
   . translate ( 'SMTP Port Number' )
   . '</label><input type="text" name="admin_SMTP_PORT" size="4" value="'
   . $s['SMTP_PORT'] . '"></p>
              <p><label title="' . tooltip ( 'email-smtp-auth' ) . '">'
   . translate( 'SMTP Authentication' ) . '</label>'
   . print_radio ( 'SMTP_AUTH', '', 'email_handler' ) . '</p>
              <div id="em_auth">
                <p><label title="' . tooltip ( 'email-smtp-username' ) . '">'
   . translate ( 'SMTP Username' )
   . '</label><input type="text" name="admin_SMTP_USERNAME" size="30" value="'
   . ( empty( $s['SMTP_USERNAME'] ) ? '' : $s['SMTP_USERNAME'] ) . '"></p>
                <p><label title="' . tooltip ( 'email-smtp-password' ) . '">'
   . translate ( 'SMTP Password' )
   . '</label><input type="text" name="admin_SMTP_PASSWORD" size="30" value="'
   . ( empty( $s['SMTP_PASSWORD'] ) ? '' : $s['SMTP_PASSWORD'] ) . '"></p>
              </div>
            </div>
            <p class="bold">' . translate( 'Default user settings' ) . '</p>
            <blockquote id="default-user-settings">
              <p><label title="' . tooltip( 'email-format' ) . '">'
   . translate( 'Email format preference' ) . '</label>'
   . print_radio ( 'EMAIL_HTML',
     array( 'Y' => translate( 'HTML' ), 'N' => translate( 'Plain Text' ) ) ) . '</p>
              <p><label title="' . tooltip( 'email_attach_ics' ) . '">'
   . translate( 'Include iCalendar attachments' ) . '</label>'
   . print_radio( 'EMAIL_ATTACH_ICS' ) . '</p>
              <p><label title="' . tooltip( 'email_reminder_help' ) . '">'
   . translate ( 'Event reminders' ) . '</label>'
   . print_radio( 'EMAIL_REMINDER' ) . '</p>';
  foreach ( array(
      // tooltip( 'email_event_added' )
      'added' => translate( 'Events added to my calendar' ),
      // tooltip( 'email_event_updated' )
      'updated' => translate( 'Events updated on my calendar' ),
      // tooltip( 'email_event_deleted' )
      'deleted' => translate( 'Events removed from my calendar' ),
      // tooltip( 'email_event_rejected' )
      'rejected' => translate( 'Event rejected by participant' ),
      // tooltip( 'email_event_create' )
      'create' => translate( 'Event that I create' ),
    ) as $k => $v ) {
    $tmp = 'email_event_' . $k;
    echo '
              <p><label title="' . tooltip( $tmp ) . '">' . $v . '</label>'
     . print_radio( strtoupper( $k ) ) . '</p>';
  }
  echo '
            </blockquote>
          </div>
        </div>

<!-- BEGIN COLORS -->
        <div id="tabscontent_colors">
          <fieldset>
            <legend>' . translate ( 'Color options' ) . '</legend>
<!-- BEGIN EXAMPLE MONTH -->
            <div id="example_month">
              <p>' . date_to_str( date( 'Ymd' ), $DATE_FORMAT_MY, false ) . '</p>'
   . display_month ( date ( 'm' ), date ( 'Y' ), true ) . '
            </div>
<!-- END EXAMPLE MONTH -->
            <p><label>' . translate ( 'Allow user to customize colors' )
   . '</label>' . print_radio ( 'ALLOW_COLOR_CUSTOMIZATION' ) . '</p>
            <p><label title="' . tooltip ( 'gradient-colors' ) . '">'
   . translate ( 'Enable gradient images for BG' ) . '</label>'
   . ( function_exists ( 'imagepng' ) || function_exists ( 'imagegif' )
    ? print_radio ( 'ENABLE_GRADIENTS' ) : translate ( 'Not available' ) )
   . '</p><br>' . $color_sets . '
          </fieldset>
          <fieldset>
            <legend>' . translate ( 'Background Image options' ) . '</legend>
            <p><label for="admin_BGIMAGE" title="' . tooltip ( 'bgimage-help' )
   . '">' . translate ( 'Background Image' )
   . '</label><input type="text" id="admin_BGIMAGE" name="admin_BGIMAGE" '
   . 'size="75" value="'
   . ( empty( $s['BGIMAGE'] ) ? '' : htmlspecialchars( $s['BGIMAGE'] ) ) . '"></p>
            <p><label for="admin_BGREPEAT" title="' . tooltip ( 'bgrepeat-help' )
   . '">' . translate ( 'Background Repeat' )
   . '</label><input type="text" id="admin_BGREPEAT" name="admin_BGREPEAT" '
   . 'size="30" value="'
   . ( empty( $s['BGREPEAT'] ) ? '' : $s['BGREPEAT'] ) . '"></p>
          </fieldset>
        </div>
      </div>
      <div style="clear:both;">
        <input type="submit" value="' . $saveStr . '">
      </div>
    </form>';
} else // if $error
  echo print_error ( $error, true );

echo print_trailer();
ob_end_flush();

?>
