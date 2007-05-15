<?php
/* $Id$ */
include_once 'includes/init.php';
include_once 'includes/date_formats.php';
if ( file_exists ( 'install/default_config.php' ) )
  include_once 'install/default_config.php';
// .
// Force the CSS cache to clear by incrementing webcalendar_csscache cookie.
// admin.php will not use this cached CSS, but we want to make sure it's flushed.
$webcalendar_csscache = 1;
if ( isset ( $_COOKIE['webcalendar_csscache'] ) )
  $webcalendar_csscache += $_COOKIE['webcalendar_csscache'];

SetCookie ( 'webcalendar_csscache', $webcalendar_csscache );

function save_pref ( $prefs, $src ) {
  global $error, $my_theme;

  while ( list ( $key, $value ) = each ( $prefs ) ) {
    if ( $src == 'post' ) {
      $prefix = substr ( $key, 0, 6 );
      $setting = substr ( $key, 6 );
      if ( $key == 'currenttab' )
        continue;
      // .
      // Validate key name.  Should start with "admin_" and not include
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
  load_global_settings ();
  load_user_preferences ();
}

$currenttab = '';
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

$menuthemes = $s = $themes = array ();

$res = dbi_execute ( 'SELECT cal_setting, cal_value FROM webcal_config' );

if ( $res ) {
  while ( $row = dbi_fetch_row ( $res ) ) {
    $setting = $row[0];
    $s[$setting] = $value = $row[1];
  }
  dbi_free_result ( $res );
}
// .
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
// .
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

print_header (
  array ( 'js/admin.php', 'js/visible.php' ),
  '',
  'onload="popup_handler (); public_handler (); eu_handler (); sr_handler (); '
   . 'attach_handler (); comment_handler (); email_handler ();'
   . ( empty ( $currenttab ) ? '"' : 'showTab ( \'' . $currenttab . '\' );"' ) );

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

  define_languages (); // Load the language list.
  reset ( $languages );

  $checked = ' checked="checked"';
  $selected = ' selected="selected"';
  $select = translate ( 'Select' ) . '...';
  // .
  // Allow css_cache of webcal_config values.
  @session_start ();
  $_SESSION['webcal_tmp_login'] = 'blahblahblah';

  $editStr = '&nbsp;&nbsp;<input type="button" value="' . translate ( 'Edit' )
   . "...\" onclick=\"window.open ( 'edit_template.php?type=%s','cal_template','"
   . 'dependent,menubar,scrollbars,height=500,width=500,outerHeight=520,'
   . 'outerWidth=520\' );" name="" />';
  $choices = array ( 'day.php', 'week.php', 'month.php', 'year.php' );
  $choices_text = array ( translate ( 'Day' ), translate ( 'Week' ),
    translate ( 'Month' ), translate ( 'Year' ) );

  $bottomStr = translate ( 'Bottom' );
  $topStr = translate ( 'Top' );

  $anyoneStr = translate ( 'Anyone' );
  $partyStr = translate ( 'Participant' );

  $option = '
                    <option value="';
  $datestyle_md = $datestyle_my = $datestyle_tk = $datestyle_ymd = '';
  $lang_list = $menu_theme_list = $prefer_vu = $start_wk_on = '';
  $start_wkend_on = $tabs = $theme_list = $user_vu = $work_hr_end = $work_hr_start = '';
  // .
  // This should be easier to add more tabs if needed.
  $tabs_ar = array ( // .
    'settings', translate ( 'Settings' ),
    'public', translate ( 'Public Access' ),
    'uac', translate ( 'User Access Control' ),
    'groups', translate ( 'Groups' ),
    'nonuser', translate ( 'NonUser Calendars' ),
    'other', translate ( 'Other' ),
    'email', translate ( 'Email' ),
    'colors', translate ( 'Colors' )
    );
  for ( $i = 0, $cnt = count ( $tabs_ar ); $i < $cnt; $i++ ) {
    $tabs .= '
        <span class="tab' . ( $i > 0 ? 'bak' : 'for' ) . '" id="tab_' . $tabs_ar[$i] . '"><a href="" onclick="return setTab ( \'' . $tabs_ar[$i] . '\' )">' . $tabs_ar[++$i] . '</a></span>';
  }
  // Move the loops here and combine a few.
  while ( list ( $key, $val ) = each ( $languages ) ) {
    $lang_list .= $option . $val . '"' . ( $val == $s['LANGUAGE'] ? $selected : '' ) . '>' . translate ( $key ) . '</option>';
  }
  for ( $i = 0, $cnt = count ( $themes[0] ); $i <= $cnt; $i++ ) {
    $theme_list .= $option . $themes[1][$i] . '">' . $themes[0][$i] . '</option>';
  }
  for ( $i = 0, $cnt = count ( $datestyles ); $i < $cnt; $i += 2 ) {
    $datestyle_ymd .= $option . $datestyles[$i] . '"' . ( $s['DATE_FORMAT'] == $datestyles[$i] ? $selected : '' ) . '>' . $datestyles[$i + 1] . '</option>';
  }
  for ( $i = 0, $cnt = count ( $datestyles_my ); $i < $cnt; $i += 2 ) {
    $datestyle_my .= $option . $datestyles_my[$i] . '"' . ( $s['DATE_FORMAT_MY'] == $datestyles_my[$i] ? $selected : '' ) . '>' . $datestyles_my[$i + 1] . '</option>';
  }
  for ( $i = 0, $cnt = count ( $datestyles_md ); $i < $cnt; $i += 2 ) {
    $datestyle_md .= $option . $datestyles_md[$i] . '"' . ( $s['DATE_FORMAT_MD'] == $datestyles_md[$i] ? $selected : '' ) . '>' . $datestyles_md[$i + 1] . '</option>';
  }
  for ( $i = 0, $cnt = count ( $datestyles_task ); $i < $cnt; $i += 2 ) {
    $datestyle_tk .= $option . $datestyles_task[$i] . '"' . ( $s['DATE_FORMAT_TASK'] == $datestyles_task[$i] ? $selected : '' ) . '>' . $datestyles_task[$i + 1] . '</option>';
  }
  for ( $i = 0; $i < 7; $i++ ) {
    $start_wk_on .= $option . "$i\"" . ( $i == $s['WEEK_START'] ? $selected : '' ) . '>' . weekday_name ( $i ) . '</option>';
    $j = ( $i == 0 ? 6 : $i-1 ); // Make sure to start with Saturday.
    $start_wkend_on .= $option . "$j\"" . ( $j == $s['WEEKEND_START'] ? $selected : '' ) . '>' . weekday_name ( $j ) . '</option>';
  }
  for ( $i = 0; $i < 24; $i++ ) {
    $tmp = display_time ( $i * 10000, 1 );
    $work_hr_start .= $option . "$i\"" . ( $i == $s['WORK_DAY_START_HOUR'] ? $selected : '' ) . '>' . $tmp . '</option>';
    $work_hr_end .= $option . "$i\"" . ( $i == $s['WORK_DAY_END_HOUR'] ? $selected : '' ) . '>' . $tmp . '</option>';
  }
  for ( $i = 0, $cnt = count ( $choices ); $i < $cnt; $i++ ) {
    $prefer_vu .= $option . $choices[$i] . '" ' . ( $s['STARTVIEW'] == $choices[$i] ? $selected : '' ) . '>' . $choices_text[$i] . '</option>';
  }
  // Allow user to select a view also.
  for ( $i = 0, $cnt = count ( $views ); $i < $cnt; $i++ ) {
    if ( $views[$i]['cal_is_global'] != 'Y' )
      continue;

    $xurl = $views[$i]['url'];
    $xurl_strip = str_replace ( '&amp;', '&', $xurl );
    $user_vu .= $option . $xurl . '" ' . ( $s['STARTVIEW'] == $xurl_strip ? $selected : '' ) . '>' . $views[$i]['cal_name'] . '</option>';
  }
  foreach ( $menuthemes as $menutheme ) {
    $menu_theme_list .= $option . $menutheme . '"' . ( $s['MENU_THEME'] == $menutheme ? $selected : '' ) . '>' . $menutheme . '</option>';
  }
  set_today ( date ( 'Ymd' ) );

  ob_start ();

  echo '
    <h2>' . translate ( 'System Settings' ) . '&nbsp;<img src="images/help.gif" alt="' . translate ( 'Help' ) . '" class="help" onclick="window.open ( \'help_admin.php\', \'cal_help\', ' . '\'dependent,menubar,scrollbars,height=400,width=400,innerHeight=420,' . 'outerWidth=420\' );" /></h2>
    <form action="admin.php" method="post" onsubmit="return valid_form ( this );" name="prefform">' . display_admin_link () . '&nbsp;&nbsp;&nbsp;
      <input type="hidden" name="currenttab" id="currenttab" value="' . $currenttab . '" />
      <input type="submit" value="' . translate ( 'Save' ) . '" name="" /><br /><br />

<!-- TABS -->
      <div id="tabs">' . $tabs . '
      </div>

<!-- TABS BODY -->
      <div id="tabscontent">
<!-- DETAILS -->
        <div id="tabscontent_settings">
          <fieldset>
            <legend>' . translate ( 'System options' ) . '</legend>
            <table width="100%">
              <tr>
                <td class="tooltip" title="' . tooltip ( 'app-name-help' ) . '"><label for="admin_APPLICATION_NAME">' . translate ( 'Application Name' ) . ':</label></td>
                <td><input type="text" size="40" name="admin_APPLICATION_NAME" id="admin_APPLICATION_NAME" value="' . htmlspecialchars ( $s['APPLICATION_NAME'] ) . '" />&nbsp;&nbsp;' . ( $s['APPLICATION_NAME'] == 'Title'/* translate ( 'Translated Name' ) */ ? str_replace ( 'XXX', translate ( 'Title' ), translate ( 'Translated Name (XXX)' ) ) : '' ) . '</td>
              </tr>
              <tr>
                <td class="tooltip" title="' . tooltip ( 'server-url-help' ) . '"><label for="admin_SERVER_URL">' . translate ( 'Server URL' ) . ':</label></td>
                <td><input type="text" size="40" name="admin_SERVER_URL" id="admin_SERVER_URL" value="' . htmlspecialchars ( $s['SERVER_URL'] ) . '" /></td>
              </tr>
              <tr>
                <td class="tooltip" title="' . tooltip ( 'home-url-help' ) . '"><label for="admin_HOME_LINK">' . translate ( 'Home URL' ) . ':</label></td>
                <td><input type="text" size="40" name="admin_HOME_LINK" id="admin_HOME_LINK" value="' . ( empty ( $s['HOME_LINK'] ) ? '' : htmlspecialchars ( $s['HOME_LINK'] ) ) . '" /></td>
              </tr>
              <tr>
                <td class="tooltipselect" title="' . tooltip ( 'language-help' ) . '"><label for="admin_LANGUAGE">' . translate ( 'Language' ) . ':</label></td>
                <td>
                  <select name="admin_LANGUAGE" id="admin_LANGUAGE">' . $lang_list . '
                  </select>&nbsp;&nbsp;'/* translate ( 'Your browser default language is' ) */ . str_replace ( 'XXX', translate ( get_browser_language ( true ) ), translate ( 'Your browser default language is XXX.' ) ) . '
                </td>
              </tr>
              <tr>
                <td><label>' . translate ( 'Allow user to use themes' ) . ':</label></td>
                <td colspan="3">' . print_radio ( 'ALLOW_USER_THEMES' ) . '</td>
              </tr>
              <tr>
                <td class="tooltip" title="' . tooltip ( 'themes-help' ) . '"><label for="admin_THEME">' . translate ( 'Themes' ) . ':</label></td>
                <td>
                  <select name="admin_THEME" id="admin_THEME">
                    <option disabled="disabled">' . translate ( 'AVAILABLE THEMES' ) . '</option>'/* Always use 'none' as default so we don't overwrite manual settings. */ . $option . 'none"' . $selected . '>' . translate ( 'None' ) . '</option>' . $theme_list . '
                  </select>&nbsp;&nbsp;&nbsp;
                  <input type="button" name="preview" value="' . translate ( 'Preview' ) . '" onclick="return showPreview ()" />
                </td>
              </tr>
            </table>
          </fieldset>
          <fieldset>
            <legend>' . translate ( 'Site customization' ) . '</legend>
            <table>
              <tr>
                <td class="tooltip" title="' . tooltip ( 'custom-script-help' ) . '">' . translate ( 'Custom script/stylesheet' ) . ':</td>
                <td>' . print_radio ( 'CUSTOM_SCRIPT' );
  printf ( $editStr, 'S' );
  echo '
                </td>
              </tr>
              <tr>
                <td class="tooltip" title="' . tooltip ( 'custom-header-help' ) . '">' . translate ( 'Custom header' ) . ':</td>
                <td>' . print_radio ( 'CUSTOM_HEADER' );
  printf ( $editStr, 'H' );
  echo '
                </td>
              </tr>
              <tr>
                <td class="tooltip" title="' . tooltip ( 'custom-trailer-help' ) . '">' . translate ( 'Custom trailer' ) . ':</td>
                <td>' . print_radio ( 'CUSTOM_TRAILER' );
  printf ( $editStr, 'T' );
  echo '
                </td>
              </tr>
              <tr>
                <td class="tooltip" title="' . tooltip ( 'enable-external-header-help' ) . '">' . translate ( 'Allow external file for header/script/trailer' ) . ':</td>
                <td>' . print_radio ( 'ALLOW_EXTERNAL_HEADER' ) . '</td>
              </tr>
              <tr>
                <td><label>' . translate ( 'Allow user to override header/trailer' ) . ':</label></td>
                <td colspan="3">' . print_radio ( 'ALLOW_USER_HEADER' ) . '</td>
              </tr>
            </table>
          </fieldset>
          <fieldset>
            <legend>' . translate ( 'Date and Time' ) . '</legend>
            <table>'/* Determine if we can set timezones.  If not don't display any options. */ . ( set_env ( 'TZ', $s['SERVER_TIMEZONE'] ) ? '
              <tr>
                <td class="tooltipselect" title="' . tooltip ( 'tz-help' ) . '"><label for="admin_SERVER_TIMEZONE">' . translate ( 'Server Timezone Selection' ) . ':</label></td>
                <td>' . print_timezone_select_html ( 'admin_', $s['SERVER_TIMEZONE'] ) . '</td>
              </tr>' : '' ) . '
              <tr>
                <td class="tooltip" title="' . tooltip ( 'display-general-use-gmt-help' ) . '">' . translate ( 'Display Common Use Date/Times as GMT' ) . ':</td>
                <td>' . print_radio ( 'GENERAL_USE_GMT' ) . '</td>
              </tr>
              <tr>
                <td class="tooltipselect" title="' . tooltip ( 'date-format-help' ) . '">' . translate ( 'Date format' ) . ':</td>
                <td>
                  <select name="admin_DATE_FORMAT">' . $datestyle_ymd . '
                  </select>&nbsp;' . $choices_text[2] . ' ' . $choices_text[0] . ' ' . $choices_text[3] . '<br />
                  <select name="admin_DATE_FORMAT_MY">' . $datestyle_my . '
                  </select>&nbsp;' . $choices_text[2] . ' ' . $choices_text[3] . '<br />
                  <select name="admin_DATE_FORMAT_MD">' . $datestyle_md . '
                  </select>&nbsp;' . $choices_text[2] . ' ' . $choices_text[0] . '<br />
                  <select name="admin_DATE_FORMAT_TASK">' . $datestyle_tk . '
                  </select>&nbsp;' . translate ( 'Small Task Date' ) . '
                </td>
              </tr>
              <tr>
                <td class="tooltip" title="' . tooltip ( 'display-week-starts-on' ) . '">' . translate ( 'Week starts on' ) . ':</td>
                <td>
                  <select name="admin_WEEK_START" id="admin_WEEK_START">' . $start_wk_on . '
                  </select>
                </td>
              </tr>
              <tr>
                <td class="tooltip" title="' . tooltip ( 'display-weekend-starts-on' ) . '">' . translate ( 'Weekend starts on' ) . ':</td>
                <td>
                  <select name="admin_WEEKEND_START" id="admin_WEEKEND_START">' . $start_wkend_on . '
                  </select>
                </td>
              </tr>
              <tr>
                <td class="tooltip" title="' . tooltip ( 'time-format-help' ) . '">' . translate ( 'Time format' ) . ':</td>
                <td>' . print_radio ( 'TIME_FORMAT', array ( '12' => translate ( '12 hour' ), '24' => translate ( '24 hour' ) ) ) . '</td>
              </tr>
              <tr>
                <td class="tooltip" title="' . tooltip ( 'timed-evt-len-help' ) . '">' . translate ( 'Specify timed event length by' ) . ':</td>
                <td>' . print_radio ( 'TIMED_EVT_LEN', array ( 'D' => translate ( 'Duration' ), 'E' => translate ( 'End Time' ) ) ) . '</td>
              </tr>
              <tr>
                <td class="tooltip" title="' . tooltip ( 'work-hours-help' ) . '">' . translate ( 'Work hours' ) . ':</td>
                <td>
                  <label for="admin_WORK_DAY_START_HOUR">' . translate ( 'From' ) . '&nbsp;</label>
                  <select name="admin_WORK_DAY_START_HOUR" id="admin_WORK_DAY_START_HOUR">' . $work_hr_start . '
                  </select>&nbsp;
                  <label for="admin_WORK_DAY_END_HOUR">' . translate ( 'to' ) . '&nbsp;</label>
                  <select name="admin_WORK_DAY_END_HOUR" id="admin_WORK_DAY_END_HOUR">' . $work_hr_end . '
                  </select>
                </td>
              </tr>
            </table>
          </fieldset>
          <fieldset>
            <legend>' . translate ( 'Appearance' ) . '</legend>
            <table>
              <tr>
                <td class="tooltip" title="' . tooltip ( 'preferred-view-help' ) . '"><label for="admin_STARTVIEW">' . translate ( 'Preferred view' ) . ':</label></td>
                <td>
                  <select name="admin_STARTVIEW" id="admin_STARTVIEW">' . $prefer_vu . $user_vu . '
                  </select>
                </td>
              </tr>
              <tr>
                <td><label>' . translate ( 'Allow top menu' ) . ':</label></td>
                <td colspan="3">' . print_radio ( 'MENU_ENABLED' ) . '</td>
              </tr>
              <tr>
                <td><label>' . translate ( 'Date Selectors position' ) . ':</label></td>
                <td colspan="3">' . print_radio ( 'MENU_DATE_TOP', array ( 'Y' => $topStr, 'N' => $bottomStr ) ) . '</td>
              </tr>
              <tr>
                <td class="tooltip" title="' . tooltip ( 'menu-themes-help' ) . '"><label for="admin_MENU_THEME">' . translate ( 'Menu theme' ) . ':</label></td>
                <td>
                  <select name="admin_MENU_THEME" id="admin_MENU_THEME">' . $option . 'none"' . $selected . '>' . translate ( 'None' ) . '</option>' . $menu_theme_list . '
                  </select>
                </td>
              </tr>
              <tr>
                <td class="tooltip" title="' . tooltip ( 'fonts-help' ) . '"><label for="admin_FONTS">' . translate ( 'Fonts' ) . ':</label></td>
                <td><input type="text" size="40" name="admin_FONTS" id="admin_FONTS" value="' . htmlspecialchars ( $s['FONTS'] ) . '" />
                </td>
              </tr>
              <tr>
                <td class="tooltip" title="' . tooltip ( 'display-sm_month-help' ) . '">' . translate ( 'Display small months' ) . ':</td>
                <td>' . print_radio ( 'DISPLAY_SM_MONTH' ) . '</td>
              </tr>
              <tr>
                <td class="tooltip" title="' . tooltip ( 'display-weekends-help' ) . '">' . translate ( 'Display weekends' ) . ':</td>
                <td>' . print_radio ( 'DISPLAY_WEEKENDS' ) . '</td>
              </tr>
              <tr>
                <td class="tooltip" title="' . tooltip ( 'display-long-daynames-help' ) . '">' . translate ( 'Display long day names' ) . ':</td>
                <td>' . print_radio ( 'DISPLAY_LONG_DAYS' ) . '</td>
              </tr>
              <tr>
                <td class="tooltip" title="' . tooltip ( 'display-alldays-help' ) . '">' . translate ( 'Display all days in month view' ) . ':</td>
                <td>' . print_radio ( 'DISPLAY_ALL_DAYS_IN_MONTH' ) . '</td>
              </tr>
              <tr>
                <td class="tooltip" title="' . tooltip ( 'display-week-number-help' ) . '">' . translate ( 'Display week number' ) . ':</td>
                <td>' . print_radio ( 'DISPLAY_WEEKNUMBER' ) . '</td>
              </tr>
              <tr>
                <td class="tooltip" title="' . tooltip ( 'display-desc-print-day-help' ) . '">' . translate ( 'Display description in printer day view' ) . ':</td>
                <td>' . print_radio ( 'DISPLAY_DESC_PRINT_DAY' ) . '</td>
              </tr>
              <tr>
                <td class="tooltip" title="' . tooltip ( 'yearly-shows-events-help' ) . '">' . translate ( 'Display days with events in bold in month and year views' ) . ':</td>
                <td>' . print_radio ( 'BOLD_DAYS_IN_YEAR' ) . '</td>
              </tr>
              <tr>
                <td class="tooltip" title="' . tooltip ( 'display-minutes-help' ) . '">' . translate ( 'Display 00 minutes always' ) . ':</td>
                <td>' . print_radio ( 'DISPLAY_MINUTES' ) . '</td>
              </tr>
              <tr>
                <td class="tooltip" title="' . tooltip ( 'display-end-times-help' ) . '">' . translate ( 'Display end times on calendars' ) . ':</td>
                <td>' . print_radio ( 'DISPLAY_END_TIMES' ) . '</td>
              </tr>
              <tr>
                <td class="tooltip" title="' . tooltip ( 'allow-view-add-help' ) . '">' . translate ( 'Include add event link in views' ) . ':</td>
                <td>' . print_radio ( 'ADD_LINK_IN_VIEWS' ) . '</td>
              </tr>
              <tr>
                <td class="tooltip" title="' . tooltip ( 'lunar-help' ) . '">' . translate ( 'Display Lunar Phases in month view' ) . ':</td>
                <td>' . print_radio ( 'DISPLAY_MOON_PHASES' ) . '</td>
              </tr>
            </table>
          </fieldset>
          <fieldset>
            <legend>' . translate ( 'Restrictions' ) . '</legend>
            <table>
              <tr>
                <td class="tooltip" title="' . tooltip ( 'allow-view-other-help' ) . '">' . translate ( 'Allow viewing other users calendars' ) . ':</td>
                <td>' . print_radio ( 'ALLOW_VIEW_OTHER' ) . '</td>
              </tr>
              <tr>
                <td class="tooltip" title="' . tooltip ( 'require-approvals-help' ) . '">' . translate ( 'Require event approvals' ) . ':</td>
                <td>' . print_radio ( 'REQUIRE_APPROVALS' ) . '</td>
              </tr>
              <tr>
                <td class="tooltip" title="' . tooltip ( 'display-unapproved-help' ) . '">&nbsp;&nbsp;&nbsp;&nbsp;' . translate ( 'Display unapproved' ) . ':</td>
                <td>' . print_radio ( 'DISPLAY_UNAPPROVED' ) . '</td>
              </tr>
              <tr>
                <td class="tooltip" title="' . tooltip ( 'conflict-check-help' ) . '">' . translate ( 'Check for event conflicts' ) . ':</td>
                <td>'/* This control is logically reversed. */ . print_radio ( 'ALLOW_CONFLICTS', array ( 'N' => translate ( 'Yes' ), 'Y' => translate ( 'No' ) ) ) . '</td>
              </tr>
              <tr>
                <td class="tooltip" title="' . tooltip ( 'conflict-months-help' ) . '">&nbsp;&nbsp;&nbsp;&nbsp;' . translate ( 'Conflict checking months' ) . ':</td>
                <td><input type="text" size="3" ' . 'name="admin_CONFLICT_REPEAT_MONTHS" value="' . htmlspecialchars ( $s['CONFLICT_REPEAT_MONTHS'] ) . '" /></td>
              </tr>
              <tr>
                <td class="tooltip" title="' . tooltip ( 'conflict-check-override-help' ) . '">&nbsp;&nbsp;&nbsp;&nbsp;' . translate ( 'Allow users to override conflicts' ) . ':</td>
                <td>' . print_radio ( 'ALLOW_CONFLICT_OVERRIDE' ) . '</td>
              </tr>
              <tr>
                <td class="tooltip" title="' . tooltip ( 'limit-appts-help' ) . '">' . translate ( 'Limit number of timed events per day' ) . ':</td>
                <td>' . print_radio ( 'LIMIT_APPTS' ) . '</td>
              </tr>
              <tr>
                <td class="tooltip" title="' . tooltip ( 'limit-appts-number-help' ) . '">&nbsp;&nbsp;&nbsp;&nbsp;' . translate ( 'Maximum timed events per day' ) . ':</td>
                <td><input type="text" size="3" name="admin_LIMIT_APPTS_NUMBER" value="' . htmlspecialchars ( $s['LIMIT_APPTS_NUMBER'] ) . '" /></td>
              </tr>
              <tr>
                <td class="tooltip" title="' . tooltip ( 'crossday-help' ) . '">' . translate ( 'Disable Cross-Day Events' ) . ':</td>
                <td>' . print_radio ( 'DISABLE_CROSSDAY_EVENTS' ) . '</td>
              </tr>
            </table>
          </fieldset>
          <fieldset>
            <legend>' . translate ( 'Events' ) . '</legend>
            <table>
              <tr>
                <td class="tooltip" title="' . tooltip ( 'disable-location-field-help' ) . '">' . translate ( 'Disable Location field' ) . ':</td>
                <td>' . print_radio ( 'DISABLE_LOCATION_FIELD' ) . '</td>
              </tr>
              <tr>
                <td class="tooltip" title="' . tooltip ( 'disable-url-field-help' ) . '">' . translate ( 'Disable URL field' ) . ':</td>
                <td>' . print_radio ( 'DISABLE_URL_FIELD' ) . '</td>
              </tr>
              <tr>
                <td class="tooltip" title="' . tooltip ( 'disable-priority-field-help' ) . '">' . translate ( 'Disable Priority field' ) . ':</td>
                <td>' . print_radio ( 'DISABLE_PRIORITY_FIELD' ) . '</td>
              </tr>
              <tr>
                <td class="tooltip" title="' . tooltip ( 'disable-access-field-help' ) . '">' . translate ( 'Disable Access field' ) . ':</td>
                <td>' . print_radio ( 'DISABLE_ACCESS_FIELD' ) . '</td>
              </tr>
              <tr>
                <td class="tooltip" title="' . tooltip ( 'disable-participants-field-help' ) . '">' . translate ( 'Disable Participants field' ) . ':</td>
                <td>' . print_radio ( 'DISABLE_PARTICIPANTS_FIELD' ) . '</td>
              </tr>
              <tr>
                <td class="tooltip" title="' . tooltip ( 'disable-repeating-field-help' ) . '">' . translate ( 'Disable Repeating field' ) . ':</td>
                <td>' . print_radio ( 'DISABLE_REPEATING_FIELD' ) . '</td>
              </tr>
              <tr>
                <td class="tooltip" title="' . tooltip ( 'allow-html-description-help' ) . '">' . translate ( 'Allow HTML in Description' ) . ':</td>
                <td>' . print_radio ( 'ALLOW_HTML_DESCRIPTION' ) . '</td>
              </tr>
            </table>
          </fieldset>
          <fieldset>
            <legend>' . translate ( 'Popups' ) . '</legend>
            <p><label class="tooltip" title="' . tooltip ( 'disable-popups-help' ) . '">' . translate ( 'Disable Pop-Ups' ) . ':</label>' . print_radio ( 'DISABLE_POPUPS', '', 'popup_handler' ) . '</p>
            <div id="pop">
              <p><label class="tooltip" title="' . tooltip ( 'popup-includes-siteextras-help' ) . '">' . translate ( 'Display Site Extras in popup' ) . ':</label>' . print_radio ( 'SITE_EXTRAS_IN_POPUP' ) . '</p>
              <p><label class="tooltip" title="' . tooltip ( 'popup-includes-participants-help' ) . '">' . translate ( 'Display Participants in popup' ) . ':</label>' . print_radio ( 'PARTICIPANTS_IN_POPUP' ) . '</p>
            </div>
          </fieldset>
          <fieldset>
            <legend>' . translate ( 'Miscellaneous' ) . '</legend>
            <table>
              <tr>
                <td class="tooltip" title="' . tooltip ( 'remember-last-login-help' ) . '">' . translate ( 'Remember last login' ) . ':</td>
                <td>' . print_radio ( 'REMEMBER_LAST_LOGIN' ) . '</td>
              </tr>
              <tr>
                <td class="tooltip" title="' . tooltip ( 'summary_length-help' ) . '">' . translate ( 'Brief Description Length' ) . ':</td>
                <td><input type="text" size="3" name="admin_SUMMARY_LENGTH" value="' . $s['SUMMARY_LENGTH'] . '" /></td>
              </tr>
              <tr>
                <td class="tooltip" title="' . tooltip ( 'user_sort-help' ) . '"><label for="admin_USER_SORT_ORDER">' . translate ( 'User Sort Order' ) . ':</label></td>
                <td>
                  <select name="admin_USER_SORT_ORDER" id="admin_USER_SORT_ORDER">' . $option . 'cal_lastname, cal_firstname" ' . ( $s['USER_SORT_ORDER'] == 'cal_lastname, cal_firstname' ? $selected : '' ) . '>' . translate ( 'Lastname, Firstname' ) . '</option>' . $option . 'cal_firstname, cal_lastname" ' . ( $s['USER_SORT_ORDER'] == 'cal_firstname, cal_lastname' ? $selected : '' ) . '>' . translate ( 'Firstname, Lastname' ) . '</option>
                  </select>
                </td>
              </tr>
            </table>
          </fieldset>
        </div>
<!-- END SETTINGS -->

<!-- BEGIN PUBLIC ACCESS -->
        <div id="tabscontent_public">
          <p><label class="tooltip" title=" ' . tooltip ( 'allow-public-access-help' ) . '">' . translate ( 'Allow public access' ) . ':</label>' . print_radio ( 'PUBLIC_ACCESS', '', 'public_handler' ) . '</p>
          <div id="pa">
            <p><label class="tooltip" title="' . tooltip ( 'public-access-default-visible' ) . '">' . translate ( 'Public access visible by default' ) . ':</label>' . print_radio ( 'PUBLIC_ACCESS_DEFAULT_VISIBLE' ) . '</p>
            <p><label class="tooltip" title="' . tooltip ( 'public-access-default-selected' ) . '">' . translate ( 'Public access is default participant' ) . ':</label>' . print_radio ( 'PUBLIC_ACCESS_DEFAULT_SELECTED' ) . '</p>
            <p><label class="tooltip" title="' . tooltip ( 'public-access-view-others-help' ) . '">' . translate ( 'Public access can view other users' ) . ':</label>' . print_radio ( 'PUBLIC_ACCESS_OTHERS' ) . '</p>
            <p><label class="tooltip" title="' . tooltip ( 'public-access-can-add-help' ) . '">' . translate ( 'Public access can add events' ) . ':</label>' . print_radio ( 'PUBLIC_ACCESS_CAN_ADD' ) . '</p>
            <p><label class="tooltip" title="' . tooltip ( 'public-access-add-requires-approval-help' ) . '">' . translate ( 'Public access new events require approval' ) . ':</label>' . print_radio ( 'PUBLIC_ACCESS_ADD_NEEDS_APPROVAL' ) . '</p>
            <p><label class="tooltip" title="' . tooltip ( 'public-access-sees-participants-help' ) . '">' . translate ( 'Public access can view participants' ) . ':</label>' . print_radio ( 'PUBLIC_ACCESS_VIEW_PART' ) . '</p>
            <p><label class="tooltip" title="' . tooltip ( 'public-access-override-help' ) . '">' . translate ( 'Override event name/description for public access' ) . ':</label>' . print_radio ( 'OVERRIDE_PUBLIC' ) . '</p>
            <p><label class="tooltip" title="' . tooltip ( 'public-access-override-text-help' ) . '">' . translate ( 'Text to display to public access' ) . ':</label>&nbsp;<label><input name="admin_OVERRIDE_PUBLIC_TEXT" value="' . $s['OVERRIDE_PUBLIC_TEXT'] . '" size="25" /></label></p>
            <p><label class="tooltip" title="' . tooltip ( 'public-access-captcha-help' ) . '">' . translate ( 'Require CAPTCHA validation for public access new events' ) . ':</label>' . print_radio ( 'ENABLE_CAPTCHA' ) . '</p>
          </div>
        </div>

<!-- BEGIN USER ACCESS CONTROL -->
        <div id="tabscontent_uac">
          <table>
            <tr>
              <td class="tooltip" title="' . tooltip ( 'uac-enabled-help' ) . '">' . translate ( 'User Access Control enabled' ) . ':</td>
              <td>' . print_radio ( 'UAC_ENABLED' ) . '</td>
            </tr>
          </table>
        </div>

<!-- BEGIN GROUPS -->
        <div id="tabscontent_groups">
          <table>
            <tr>
              <td class="tooltip" title="' . tooltip ( 'groups-enabled-help' ) . '">' . translate ( 'Groups enabled' ) . ':</td>
              <td>' . print_radio ( 'GROUPS_ENABLED' ) . '</td>
            </tr>
            <tr>
              <td class="tooltip" title="' . tooltip ( 'user-sees-his-group-help' ) . '">' . translate ( 'User sees only his groups' ) . ':</td>
              <td>' . print_radio ( 'USER_SEES_ONLY_HIS_GROUPS' ) . '</td>
            </tr>
          </table>
        </div>

<!-- BEGIN NONUSER -->
        <div id="tabscontent_nonuser">
          <table>
            <tr>
              <td class="tooltip" title="' . tooltip ( 'nonuser-enabled-help' ) . '">' . translate ( 'Nonuser enabled' ) . ':</td>
              <td>' . print_radio ( 'NONUSER_ENABLED' ) . '</td>
            </tr>
            <tr>
              <td class="tooltip" title="' . tooltip ( 'nonuser-list-help' ) . '">' . translate ( 'Nonuser list' ) . ':</td>
              <td>' . print_radio ( 'NONUSER_AT_TOP', array ( 'Y' => $topStr, 'N' => $bottomStr ) ) . '</td>
            </tr>
          </table>
        </div>

<!-- BEGIN REPORTS -->
        <div id="tabscontent_other">
          <table>
            <tr>
              <td class="tooltip" title="' . tooltip ( 'reports-enabled-help' ) . '">' . translate ( 'Reports enabled' ) . ':</td>
              <td>' . print_radio ( 'REPORTS_ENABLED' ) . '</td>
            </tr>

<!-- BEGIN PUBLISHING -->
            <tr>
              <td class="tooltip" title="' . tooltip ( 'subscriptions-enabled-help' ) . '">' . translate ( 'Allow remote subscriptions' ) . ':</td>
              <td>' . print_radio ( 'PUBLISH_ENABLED' ) . '</td>
            </tr>'/* Determine if allow_url_fopen is enabled. */ . ( preg_match ( '/(On|1|true|yes)/i', ini_get ( 'allow_url_fopen' ) ) ? '
            <tr>
              <td class="tooltip" title="' . tooltip ( 'remotes-enabled-help' ) . '">' . translate ( 'Allow remote calendars' ) . ':</td>
              <td>' . print_radio ( 'REMOTES_ENABLED' ) . '</td>
            </tr>' : '' ) . '
            <tr>
              <td class="tooltip" title="' . tooltip ( 'rss-enabled-help' ) . '">' . translate ( 'Enable RSS feed' ) . ':</td>
              <td>' . print_radio ( 'RSS_ENABLED' ) . '</td>
            </tr>

<!-- BEGIN CATEGORIES -->
            <tr>
              <td class="tooltip" title="' . tooltip ( 'categories-enabled-help' ) . '">' . translate ( 'Categories enabled' ) . ':</td>
              <td>' . print_radio ( 'CATEGORIES_ENABLED' ) . '</td>
            </tr>
            <tr>
              <td class="tooltip" title="' . tooltip ( 'icon_upload-enabled-help' ) . '">' . translate ( 'Category Icon Upload enabled' ) . ':</td>
              <td>' . print_radio ( 'ENABLE_ICON_UPLOADS' ) . '&nbsp;' . ( ! is_dir ( 'icons/' )/* translate ( 'Requires' ) translate ( 'folder to exist' ) */ ? str_replace ( 'XXX', 'icons', translate ( '(Requires XXX folder to exist.)' ) ) : '' ) . '</td>
            </tr>

<!-- DISPLAY TASK PREFERENCES -->
            <tr>
              <td class="tooltip" title="' . tooltip ( 'display-tasks-help' ) . '">' . translate ( 'Display small task list' ) . ':</td>
              <td>' . print_radio ( 'DISPLAY_TASKS' ) . '</td>
            </tr>
            <tr>
              <td class="tooltip" title="' . tooltip ( 'display-tasks-in-grid-help' ) . '">' . translate ( 'Display tasks in Calendars' ) . ':</td>
              <td>' . print_radio ( 'DISPLAY_TASKS_IN_GRID' ) . '</td>
            </tr>

<!-- BEGIN EXT PARTICIPANTS -->
            <tr>
              <td class="tooltip" title="' . tooltip ( 'allow-external-users-help' ) . '">' . translate ( 'Allow external users' ) . ':</td>
              <td>' . print_radio ( 'ALLOW_EXTERNAL_USERS', '', 'eu_handler' ) . '</td>
            </tr>
            <tr id="eu1">
              <td class="tooltip" title="' . tooltip ( 'external-can-receive-notification-help' ) . '">&nbsp;&nbsp;&nbsp;&nbsp;' . translate ( 'External users can receive email notifications' ) . ':</td>
              <td>' . print_radio ( 'EXTERNAL_NOTIFICATIONS' ) . '</td>
            </tr>
            <tr id="eu2">
              <td class="tooltip" title="' . tooltip ( 'external-can-receive-reminder-help' ) . '">&nbsp;&nbsp;&nbsp;&nbsp;' . translate ( 'External users can receive email reminders' ) . ':</td>
              <td>' . print_radio ( 'EXTERNAL_REMINDERS' ) . '</td>
            </tr>

 <!-- BEGIN SELF REGISTRATION -->
            <tr>
              <td class="tooltip" title="' . tooltip ( 'allow-self-registration-help' ) . '">' . translate ( 'Allow self-registration' ) . ':</td>
              <td>' . print_radio ( 'ALLOW_SELF_REGISTRATION', '', 'sr_handler' ) . '</td>
            </tr>
            <tr id="sr1">
              <td class="tooltip" title="' . tooltip ( 'use-blacklist-help' ) . '">&nbsp;&nbsp;&nbsp;&nbsp;' . translate ( 'Restrict self-registration to blacklist' ) . ':</td>
              <td>' . print_radio ( 'SELF_REGISTRATION_BLACKLIST', '', 'sr_handler' ) . '</td>
            </tr>
            <tr id="sr2">
              <td class="tooltip" title="' . tooltip ( 'allow-self-registration-full-help' ) . '">&nbsp;&nbsp;&nbsp;&nbsp;' . translate ( 'Use self-registration email notifications' ) . ':</td>
              <td>' . print_radio ( 'SELF_REGISTRATION_FULL', '', 'sr_handler' ) . '</td>
            </tr>

<!-- TODO add account aging feature. -->

<!-- BEGIN ATTACHMENTS/COMMENTS -->
            <tr>
              <td class="tooltip" title="' . tooltip ( 'allow-attachment-help' ) . '">' . translate ( 'Allow file attachments to events' ) . ':</td>
              <td>' . print_radio ( 'ALLOW_ATTACH', '', 'attach_handler' ) . '<span id="at1"><br /><strong>Note: </strong>' . translate ( 'Admin and owner can always add attachments if enabled.' ) . '<br />' . print_checkbox ( array ( 'ALLOW_ATTACH_PART', 'Y', $partyStr ) ) . print_checkbox ( array ( 'ALLOW_ATTACH_ANY', 'Y', $anyoneStr ) ) . '</span></td>
            </tr>
            <tr>
              <td class="tooltip" title="' . tooltip ( 'allow-comments-help' ) . '">' . translate ( 'Allow comments to events' ) . ':</td>
              <td>' . print_radio ( 'ALLOW_COMMENTS', '', 'comment_handler' ) . '<br /><span id="com1"><br /><strong>Note: </strong>' . translate ( 'Admin and owner can always add comments if enabled.' ) . '<br />' . print_checkbox ( array ( 'ALLOW_COMMENTS_PART', 'Y', $partyStr ) ) . print_checkbox ( array ( 'ALLOW_COMMENTS_ANY', 'Y', $anyoneStr ) ) . '</span></td>
            </tr>
<!-- END ATTACHMENTS/COMMENTS -->
          </table>
        </div>

<!-- BEGIN EMAIL -->
        <div id="tabscontent_email">
          <table>
            <tr>
              <td class="tooltip" title="' . tooltip ( 'email-enabled-help' ) . '">' . translate ( 'Email enabled' ) . ':</td>
              <td>' . print_radio ( 'SEND_EMAIL', '', 'email_handler' ) . '</td>
            </tr>
            <tr id="em1">
              <td class="tooltip" title="' . tooltip ( 'email-default-sender' ) . '">&nbsp;&nbsp;&nbsp;&nbsp;' . translate ( 'Default sender address' ) . ':</td>
              <td><input type="text" size="30" name="admin_EMAIL_FALLBACK_FROM" value="' . htmlspecialchars ( $EMAIL_FALLBACK_FROM ) . '" /></td>
            </tr>
            <tr id="em2">
              <td class="tooltip" title="' . tooltip ( 'email-mailer' ) . '">' . translate ( 'Email Mailer' ) . ':</td>
              <td>
                <select name="admin_EMAIL_MAILER"onchange="email_handler ()">' . $option . 'smtp" ' . ( $s['EMAIL_MAILER'] == 'smtp' ? $selected : '' ) . '>SMTP</option>' . $option . 'mail" ' . ( $s['EMAIL_MAILER'] == 'mail' ? $selected : '' ) . '>PHP mail</option>' . $option . 'sendmail" ' . ( $s['EMAIL_MAILER'] == 'sendmail' ? $selected : '' ) . '>sendmail</option>
                </select>
              </td>
            </tr>
            <tr id="em3">
              <td class="tooltip" title="' . tooltip ( 'email-smtp-host' ) . '">' . translate ( 'SMTP Host name(s)' ) . ':</td>
              <td><input type="text" size="50" name="admin_SMTP_HOST" value="' . $s['SMTP_HOST'] . '" /></td>
            </tr>
            <tr id="em3a">
              <td class="tooltip" title="' . tooltip ( 'email-smtp-port' ) . '">' . translate ( 'SMTP Port Number' ) . ':</td>
              <td><input type="text" size="4" name="admin_SMTP_PORT" value="' . $s['SMTP_PORT'] . '" /></td>
            </tr>
            <tr id="em4">
              <td class="tooltip" title="' . tooltip ( 'email-smtp-auth' ) . '">' . translate ( 'SMTP Authentication' ) . ':</td>
              <td>' . print_radio ( 'SMTP_AUTH', '', 'email_handler' ) . '</td>
            </tr>
            <tr id="em5">
              <td class="tooltip" title="' . tooltip ( 'email-smtp-username' ) . '">&nbsp;&nbsp;&nbsp;&nbsp;' . translate ( 'SMTP Username' ) . ':</td>
              <td><input type="text" size="30" name="admin_SMTP_USERNAME" value="' . ( empty ( $s['SMTP_USERNAME'] ) ? '' : $s['SMTP_USERNAME'] ) . '" /></td>
            </tr>
            <tr id="em6">
              <td class="tooltip" title="' . tooltip ( 'email-smtp-password' ) . '">&nbsp;&nbsp;&nbsp;&nbsp;' . translate ( 'SMTP Password' ) . ':</td>
              <td><input type="text" size="30" name="admin_SMTP_PASSWORD" value="' . ( empty ( $s['SMTP_PASSWORD'] ) ? '' : $s['SMTP_PASSWORD'] ) . '" /></td>
            </tr>
            <tr id="em7">
              <td colspan="2" class="bold">' . translate ( 'Default user settings' ) . ':</td>
            </tr>
            <tr id="em8">
              <td class="tooltip" title="' . tooltip ( 'email-event-reminders-help' ) . '">&nbsp;&nbsp;&nbsp;&nbsp;' . translate ( 'Event reminders' ) . ':</td>
              <td>' . print_radio ( 'EMAIL_REMINDER' ) . '</td>
            </tr>
            <tr id="em9">
              <td class="tooltip" title="' . tooltip ( 'email-event-added' ) . '">&nbsp;&nbsp;&nbsp;&nbsp;' . translate ( 'Events added to my calendar' ) . ':</td>
              <td>' . print_radio ( 'EMAIL_EVENT_ADDED' ) . '</td>
            </tr>
            <tr id="em10">
              <td class="tooltip" title="' . tooltip ( 'email-event-updated' ) . '">&nbsp;&nbsp;&nbsp;&nbsp;' . translate ( 'Events updated on my calendar' ) . ':</td>
              <td>' . print_radio ( 'EMAIL_EVENT_UPDATED' ) . '</td>
            </tr>
            <tr id="em11">
              <td class="tooltip" title="' . tooltip ( 'email-event-deleted' ) . '">&nbsp;&nbsp;&nbsp;&nbsp;' . translate ( 'Events removed from my calendar' ) . ':</td>
              <td>' . print_radio ( 'EMAIL_EVENT_DELETED' ) . '</td>
            </tr>
            <tr id="em12">
              <td class="tooltip" title="' . tooltip ( 'email-event-rejected' ) . '">&nbsp;&nbsp;&nbsp;&nbsp;' . translate ( 'Event rejected by participant' ) . ':</td>
              <td>' . print_radio ( 'EMAIL_EVENT_REJECTED' ) . '</td>
            </tr>
            <tr id="em13">
              <td class="tooltip" title="' . tooltip ( 'email-event-create' ) . '">&nbsp;&nbsp;&nbsp;&nbsp;' . translate ( 'Event that I create' ) . ':</td>
              <td>' . print_radio ( 'EMAIL_EVENT_CREATE' ) . '</td>
            </tr>
          </table>
        </div>

<!-- BEGIN COLORS -->
        <div id="tabscontent_colors">
          <fieldset>
            <legend>' . translate ( 'Color options' ) . '</legend>
            <table width="100%">
              <tr>
                <td width="30%"><label>' . translate ( 'Allow user to customize colors' ) . ':</label></td>
                <td colspan="5">' . print_radio ( 'ALLOW_COLOR_CUSTOMIZATION' ) . '</td>
              </tr>
              <tr>
                <td class="tooltip" title="' . tooltip ( 'gradient-colors' ) . '"><label>' . translate ( 'Enable gradient images for background colors' ) . ':</label></td>
                <td colspan="5">' . ( function_exists ( 'imagepng' ) || function_exists ( 'imagegif' ) ? print_radio ( 'ENABLE_GRADIENTS' ) : translate ( 'Not available' ) ) . '</td>
              </tr>
              <tr>
                <td>' . print_color_input_html ( 'BGCOLOR', translate ( 'Document background' ) ) . '</td>
                <td rowspan="15" width="1%">&nbsp;</td>
                <td rowspan="15" width="45%" class="aligncenter ligntop">
<!-- BEGIN EXAMPLE MONTH -->
                  <table style="width:90%; background-color:' . $BGCOLOR . '">
                    <tr>
                      <td width="1%" rowspan="3">&nbsp;</td>
                      <td style="text-align:center; color:' . $H2COLOR . '; font-weight:bold;">' . date_to_str ( date ( 'Ymd' ), $DATE_FORMAT_MY, false ) . '</td>
                      <td width="1%" rowspan="3">&nbsp;</td>
                    </tr>
                    <tr>
                      <td bgcolor="' . $BGCOLOR . '">' . display_month ( date ( 'm' ), date ( 'Y' ), true ) . '</td>
                    </tr>
                    <tr>
                      <td>&nbsp;</td>
                    </tr>
                  </table>
<!-- END EXAMPLE MONTH -->
                </td>
              </tr>
              <tr>
                <td>' . print_color_input_html ( 'H2COLOR', translate ( 'Document title' ) ) . '</td>
              </tr>
              <tr>
                <td>' . print_color_input_html ( 'TEXTCOLOR', translate ( 'Document text' ) ) . '</td>
              </tr>
              <tr>
                <td>' . print_color_input_html ( 'MYEVENTS', translate ( 'My event text' ) ) . '</td>
              </tr>
              <tr>
                <td>' . print_color_input_html ( 'TABLEBG', translate ( 'Table grid color' ) ) . '</td>
              </tr>
              <tr>
                <td>' . print_color_input_html ( 'THBG', translate ( 'Table header background' ) ) . '</td>
              </tr>
              <tr>
                <td>' . print_color_input_html ( 'THFG', translate ( 'Table header text' ) ) . '</td>
              </tr>
              <tr>
                <td>' . print_color_input_html ( 'CELLBG', translate ( 'Table cell background' ) ) . '</td>
              </tr>
              <tr>
                <td>' . print_color_input_html ( 'TODAYCELLBG', translate ( 'Table cell background for current day' ) ) . '</td>
              </tr>
              <tr>
                <td>' . print_color_input_html ( 'HASEVENTSBG', translate ( 'Table cell background for days with events' ) ) . '</td>
              </tr>
              <tr>
                <td>' . print_color_input_html ( 'WEEKENDBG', translate ( 'Table cell background for weekends' ) ) . '</td>
              </tr>
              <tr>
                <td>' . print_color_input_html ( 'OTHERMONTHBG', translate ( 'Table cell background for other month' ) ) . '</td>
              </tr>
              <tr>
                <td>' . print_color_input_html ( 'WEEKNUMBER', translate ( 'Week number color' ) ) . '</td>
              </tr>
              <tr>
                <td>' . print_color_input_html ( 'POPUP_BG', translate ( 'Event popup background' ) ) . '</td>
              </tr>
              <tr>
                <td>' . print_color_input_html ( 'POPUP_FG', translate ( 'Event popup text' ) ) . '</td>
              </tr>
            </table>
          </fieldset>
          <fieldset>
            <legend>' . translate ( 'Background Image options' ) . '</legend>
            <table>
              <tr>
                <td class="tooltip" title="' . tooltip ( 'bgimage-help' ) . '"><label for="admin_BGIMAGE">' . translate ( 'Background Image' ) . ':</label></td>
                <td><input type="text" size="75" name="admin_BGIMAGE" id="admin_BGIMAGE" value="' . ( empty ( $s['BGIMAGE'] ) ? '' : htmlspecialchars ( $s['BGIMAGE'] ) ) . '" /></td>
              </tr>
              <tr>
                <td class="tooltip" title="' . tooltip ( 'bgrepeat-help' ) . '"><label for="admin_BGREPEAT">' . translate ( 'Background Repeat' ) . ':</label></td>
                <td><input type="text" size="30" name="admin_BGREPEAT" id="admin_BGREPEAT" value="' . ( empty ( $s['BGREPEAT'] ) ? '' : $s['BGREPEAT'] ) . '" /></td>
              </tr>
            </table>
          </fieldset>
        </div>
      </div><br /><br />
      <div>
        <input type="submit" value="' . translate ( 'Save' ) . '" name="" />
      </div>
    </form>';

  ob_end_flush ();
} else // if $error
  echo print_error ( $error, true );

echo print_trailer ();

?>
