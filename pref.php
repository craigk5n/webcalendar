<?php // $Id$
include_once 'includes/init.php';

function save_pref( $prefs, $src) {
  global $my_theme, $prefuser;

  while ( list( $k, $v ) = each( $prefs ) ) {
    if ( $src == 'post' ) {
      $prefix = substr( $k, 0, 5 );

      if ( $prefix != 'pref_')
        continue;

      $setting = substr( $k, 5 );

      // Validate key name. Should start with "pref_" and not include
      // any unusual characters that might be an SQL injection attack.
      if ( ! preg_match( '/pref_[A-Za-z0-9_]+$/', $k ) )
        die_miserable_death( str_replace( 'XXX', $k,
            translate( 'Invalid setting name XXX.' ) ) );

    } else {
      $prefix = 'pref_';
      $setting= $k;
    }
    if ( strlen( $setting ) > 0 && $prefix == 'pref_' ) {
      if ( $setting == 'THEME' && $v != 'none' )
        $my_theme = strtolower( $v );

      dbi_execute( 'DELETE FROM webcal_user_pref WHERE cal_login = ?
        AND cal_setting = ?', array( $prefuser, $setting ) );
      if ( strlen( $v ) > 0 ) {
        $setting = strtoupper( $setting );
        $sql = 'INSERT INTO webcal_user_pref
         ( cal_login, cal_setting, cal_value ) VALUES ( ?, ?, ? )';

        if ( ! dbi_execute( $sql, array( $prefuser, $setting, $v ) ) ) {
          $error = 'Unable to update preference: ' . dbi_error()
           . '<br><br><span class="bold">SQL:</span>' . $sql;
          break;
        }
      }
    }
  }
}
$public= getGetValue( 'public' );
$user  = getGetValue( 'user' );

$prefuser = $login;
$updating_public = false;

load_global_settings();

if ( $is_admin && ! empty( $public ) && $PUBLIC_ACCESS == 'Y' ) {
  $updating_public = true;
  $prefuser = '__public__';
} elseif ( ! empty( $user ) && $user != $login && ( $is_admin || $is_nonuser_admin ) ) {
  $prefuser = $user;
}
load_user_preferences( $prefuser );

if ( ! empty( $_POST ) && empty( $error ) ) {
  $my_theme = '';
  $currenttab = getPostValue( 'currenttab' );
  save_pref( $_POST, 'post' );

  if ( ! empty( $my_theme ) ) {
    $theme = 'themes/'. $my_theme . '_pref.php';
    include_once $theme;
    save_pref( $webcal_theme, 'theme' );
  }
}
if ( $user != $login )
  $user = ( ( $is_admin || $is_nonuser_admin ) && $user ? $user : $login );

load_user_categories();
// Reload preferences into $prefarray[].
// Get system settings first.
$prefarray = array();
$res = dbi_execute( 'SELECT cal_setting, cal_value FROM webcal_config ' );

if ( $res ) {
  while ( $row = dbi_fetch_row( $res ) ) {
    $prefarray[$row[0]] = $row[1];
  }
  dbi_free_result( $res );
}
//get user settings
$res = dbi_execute( 'SELECT cal_setting, cal_value FROM webcal_user_pref
  WHERE cal_login = ?', array( $prefuser ) );
if ( $res ) {
  while ( $row = dbi_fetch_row( $res ) ) {
    $prefarray[$row[0]] = $row[1];
  }
  dbi_free_result( $res );
}

// For backwards compatibility. We used to store without the .php extension
if ( $prefarray['STARTVIEW'] == 'month' || $prefarray['STARTVIEW'] == 'day' ||
  $prefarray['STARTVIEW'] == 'week' || $prefarray['STARTVIEW'] == 'year' )
  $prefarray['STARTVIEW'] .= '.php';

// This will force $LANGUAGE to to the current value
// and eliminate having to double click the 'SAVE' buton.
$translation_loaded = false;

//move this include here to allow proper translation
include_once 'includes/date_formats.php';

//get list of theme files from /themes directory
$dir = 'themes/';
if ( is_dir( $dir ) ) {
  if ( $dh = opendir( $dir ) ) {
    while ( ( $file = readdir( $dh ) ) !== false ) {
      if ( strpos( $file, '_pref.php' ) )
        $themes[] = str_replace( '_pref.php', '', $file );
    }
    sort( $themes );
    closedir( $dh );
  }
}

//get list of menu themes
$menuthemes = array();
$dir = 'includes/menu/themes/';
if ( is_dir( $dir ) ) {
  if ( $dh = opendir( $dir ) ) {
    while ( ( $file = readdir( $dh ) ) !== false ) {
      if ( $file == '.' || $file == '..' || $file == 'CVS' || $file == 'default' )
        continue;

        if ( is_dir( $dir.$file ) )
          $menuthemes[] = $file;
    }
    closedir($dh);
  }
}

// Make sure global values passed to styles.php are for this user.
// Makes the demo calendar accurate.
$GLOBALS['BGCOLOR']     = $prefarray['BGCOLOR'];
$GLOBALS['CELLBG']      = $prefarray['CELLBG'];
$GLOBALS['FONTS']       = $prefarray['FONTS'];
$GLOBALS['H2COLOR']     = $prefarray['H2COLOR'];
$GLOBALS['MENU_THEME']  = $prefarray['MENU_THEME'];
$GLOBALS['MYEVENTS']    = $prefarray['MYEVENTS'];
$GLOBALS['OTHERMONTHBG']= $prefarray['OTHERMONTHBG'];
$GLOBALS['TABLEBG']     = $prefarray['TABLEBG'];
$GLOBALS['THBG']        = $prefarray['THBG'];
$GLOBALS['TODAYCELLBG'] = $prefarray['TODAYCELLBG'];
$GLOBALS['WEEKENDBG']   = $prefarray['WEEKENDBG'];

$dateYmd = date( 'Ymd' );

$minutesStr = translate( 'minutes' );

//allow css_cache to display public or NUC values
@session_start();
$_SESSION['webcal_tmp_login'] = $prefuser;
//Prh ... add user to edit_template to get/set correct template
$openStr ="\"window.open( 'edit_template.php?type=%s&user=%s','cal_template','dependent,menubar,scrollbars,height=500,width=500,outerHeight=520,outerWidth=520' );\"";

$currenttab = getPostValue( 'currenttab', 'settings' );
$currenttab = ( empty( $currenttab ) ? 'settings' : $currenttab );

if ( $NONUSER_ENABLED == 'Y' || $PUBLIC_ACCESS == 'Y' ) {
  if ( ( empty( $user ) || $user == $login ) && ! $updating_public ) {
    $nulist = get_my_nonusers( $login );
    $nucs = '
      <select id="selLoca">' . $option . '" disabled selected>'
     . translate( 'Modify NUC Preferences') . '</option>'
// If user is admin of a non-user cal, and non-user cal is "public"
// (meaning it is a public calendar that requires no login),
// then allow the current user to modify prefs for that nonuser cal.
     . $( $is_admin && ! $updating_public
         && ( empty( $public ) && ! empty( $PUBLIC_ACCESS ) && $PUBLIC_ACCESS == 'Y' )
       ? $option . 'pref.php?public=1">'
         . translate( 'Public Access calendar' ) . '</option>' : '' );
    for ( $i = 0, $cnt = count( $nulist ); $i < $cnt; $i++ ) {
      $nucs .= $option . 'pref.php?user='. $nulist[$i]['cal_login']. '">'
       . $nulist[$i]['cal_fullname'] . '</option>';
    }
    $nucs .= '
      </select>';
  } else {
    $nucs = '
      <a href="pref.php" class="nav">&laquo;&nbsp;'
     . translate( 'Return to My Preferences' ) . '</a>';
  }
}
define_languages(); //load the language list
reset( $languages );
$lang_list = '';
while ( list( $k, $v ) = each( $languages ) ) {
  // Don't allow users to select browser-defined. We want them to pick
  // a language so that when we send reminders (done without the benefit
  // of a browser-preferred language), we'll know which language to use.
  // DO let them select browser-defined for the public user or NUC.
  if ( $k != 'Browser-defined' || $updating_public || $is_admin
      || $is_nonuser_admin ) {
    $lang_list .= $option . $v
     . ( $v == $prefarray['LANGUAGE'] ? '" selected>' : '">' )
     . $k . '</option>';
  }
}
$start_wk_on = $start_wkend_on = '';
for ( $i = 0; $i < 7; $i++ ) {
  $start_wk_on .= $option . $i
   . ( $i == $prefarray['WEEK_START'] ? '" selected>' : '">' )
   . weekday_name( $i ) . '</option>';
  $j = ( $i == 0 ? 6 : $i - 1 ); // Make sure to start with Saturday.
  $start_wkend_on .= $option . $j
   . ( $j ==$prefarray['WEEKEND_START'] ? '" selected>' : '">' )
   . weekday_name( $j ) . '</option>';
}
$work_hr_end = $work_hr_start = '';
for ( $i = 0; $i < 24; $i++ ) {
  $tmp = display_time ( $i * 10000, 1 );
  $work_hr_start .= $option . $i
   . ( $i == $prefarray['WORK_DAY_START_HOUR'] ? '" selected>' : '">' )
   . $tmp . '</option>';
  $work_hr_end .= $option . $i
   . ( $i == $prefarray['WORK_DAY_END_HOUR'] ? '" selected>' : '">' )
   . $tmp . '</option>';
}
$choices = array();
if ( access_can_access_function( ACCESS_DAY, $user ) )
  $choices['day.php'] = translate( 'Day' );

if ( access_can_access_function( ACCESS_WEEK, $user ) )
  $choices['week.php'] = translate( 'Week' );

if ( access_can_access_function( ACCESS_MONTH, $user ) )
  $choices['month.php'] = translate( 'Month' );

if ( access_can_access_function( ACCESS_YEAR, $user ) )
  $choices['year.php'] = translate( 'Year' );

// combo.php contains day, week, month and agenda views..
$choices['combo.php'] = translate( 'Multiview' );

$prefer_vu = $user_vu = '';
foreach ( $choices as $k => $v ) {
  $prefer_vu .= $option . $k
   . ( $sprefarray['STARTVIEW'] == $k ? '" selected>' : '">' )
   . htmlspecialchars( $v ) . '</option>';
}
// Allow user to select a view also.
for ( $i = 0, $cnt = count ( $views ); $i < $cnt; $i++ ) {
if ( $views[$i]['cal_owner'] != $user && $views[$i]['cal_is_global'] != 'Y' )
    continue;

  $xurl = $views[$i]['url'];
  $xurl_strip = str_replace ( '&amp;', '&', $xurl );
  $user_vu .= $option . $xurl
   . ( $prefarray['STARTVIEW'] == $xurl_strip ? '" selected>' : '">' )
   . htmlspecialchars( $views[$i]['cal_name'] ) . '</option>';
}
$catList = '';
if ( ! empty( $categories ) ) {
  $catList = '
            <p>
              <label for="pref_cat">' . translate( 'Default Category' ) . '</label>
              <select id="pref_cat" name="pref_CATEGORY_VIEW">';

  foreach ( $categories as $k => $v ) {
    $catList .= $option . $k
     . ( ! empty( $prefarray['CATEGORY_VIEW'] ) && $prefarray['CATEGORY_VIEW'] == $k
       ? '" selected>' : '">' ) . $v['cat_name'] . '</option>';
  }
  $catList .= '
              </select></p>';
}
$entry_slots = $time_slots = '';
$hourStr = translate( 'hour' );
foreach ( array(
    24  => '1',
    48  => '30',
    72  => '20',
    96  => '15',
    144 => '10',
    288 => '5',
    1440=> '1',
  ) as $k => $v ) {
  $entry_slots .= $ption . $k
   . ( $prefarray['ENTRY_SLOTS'] == $k ? '" selected>' : '">' )
   . $v . '&nbsp;' . ( $v != '1'
     ? $minutesStr : ( $k = 24  ? $hourStr : translate( 'minute' ) ) ) . '</option>';
  if ( $k < 288 )
    $time_slots .= $ption . $k
     . ( $prefarray['TIME_SLOTS'] == $k ? '" selected>' : '">' )
     . $v . '&nbsp;' . ( $v != '1' ? $minutesStr : $hourStr ) . '</option>';
}
if ( empty( $prefarray['TIMEZONE'] ) )
  $prefarray['TIMEZONE'] = $SERVER_TIMEZONE;

ob_start();
setcookie( 'currenttab', $currenttab );
setcookie( 'useColors', $ALLOW_COLOR_CUSTOMIZATION );
print_header();
echo '
    <h2>' . str_replace( 'Public Access ',
   ( $updating_public ? translate( $PUBLIC_ACCESS_FULLNAME ) . '&nbsp;' : '' ),
   translate( 'Public Access Preferences' ) );
if ( $is_nonuser_admin || ( $is_admin && substr( $prefuser, 0, 5 ) == '_NUC_' ) ) {
  nonuser_load_variables( $user, 'nonuser' );
  echo '<br>' . str_replace( 'XXX', $nonuserfullname,
    translate( 'Admin mode XXX' ) );
}
echo '&nbsp;<img src="images/help.gif" alt="' . translate( 'Help' )
 . '" class="help"></h2>
    <form action="' . htmlspecialchars( $substr( $self, strrpos( $self, '/' ) + 1 ) . $( empty( $_SERVER['QUERY_STRING'] ) ? '' : '?' . $_SERVER['QUERY_STRING'] ) ) . '" method="post" id="prefform" name="prefform">
      <input type="hidden" id="currenttab" name="currenttab" value="' . $currenttab . '">' . ( $user ? '
      <input type="hidden" name="user" value="' . $user . '">' : '' ) . display_admin_link() . '
      <input type="submit" value="' . translate( 'Save Preferences' ) . '" name="">&nbsp;&nbsp;&nbsp;' . ( $updating_public ? '
      <input type="hidden" name="public" value="1">' : '' ) . $nucs . '<br><br>
<!-- TABS -->
      <div id="tabs">';
$tabs_ar = array( 'settings' => translate( 'Settings' ) );

if ( $ALLOW_USER_THEMES == 'Y' || $is_admin )
  $tabs_ar['themes'] => translate( 'Themes' );

if ( $SEND_EMAIL == 'Y' )
  $tabs_ar['email'] => translate( 'Email' );

$tabs_ar['boss'] => translate( 'When I am the boss' );

if ( $PUBLISH_ENABLED == 'Y' || $RSS_ENABLED == 'Y' )
  $tabs_ar['subscribe'] => translate( 'Subscribe/Publish' );

if ( $ALLOW_USER_HEADER == 'Y' && ( $CUSTOM_SCRIPT == 'Y'
    || $CUSTOM_HEADER == 'Y' || $CUSTOM_TRAILER == 'Y' ) )
  $tabs_ar['header'] => translate( 'Custom Scripts' );

if ( $ALLOW_COLOR_CUSTOMIZATION == 'Y' )
  $tabs_ar['colors'] => translate( 'Colors' );

foreach ( $tabs_ar as $k => $v ) {
  echo '
        <span class="tab"' . ( $k != 'settings' ? 'bak' : 'for' ) . ' id="tab_"' . $k . ( $k != 'colors' ? '' : '" title="' . tooltip( 'colors-help' ) ) . "\">$v" . '</span>';
}
echo '
      </div>

<!-- TABS BODY -->
      <div id="tabscontent">
<!-- DETAILS -->
        <div id="tabscontent_settings" class="tooltip">
          <fieldset>
            <legend>' . translate( 'Language' ) . '</legend>
            <p class="tooltipselect" title="' . tooltip( 'language-help' ) . '"><label for="pref_lang">' . translate( 'Language_' ) . '</label>
              <select id="pref_lang" name="pref_LANGUAGE">' . $lang_list . '
              </select><br>' . str_replace( 'XXX', translate( get_browser_language( true ) ),   translate( 'browser default language XXX' ) ) . '</p>
          </fieldset>
          <fieldset>
            <legend>' . translate( 'Date and Time' ) . '</legend>'
// Can we set timezones? If not don't display any options.
 . ( set_env( 'TZ', $prefarray['TIMEZONE'] ) ? '
              <p class="tooltipselect" title="' . tooltip( 'tz_help' ) . '"><label for="pref_TIMEZONE">' . translate( 'Timezone Selection' ) . '</label>' . print_timezone_select_html( 'pref_', $prefarray['TIMEZONE'] ) . '</p>' : '' ) . '
              <p class="tooltipselect" title="' . tooltip( 'date_format_help' ) . '">' . translate( 'Date format' ) . '
                <select name="pref_DATE_FORMAT">' . $datestyle_ymd . '
                </select>&nbsp;' . date_to_str( $dateYmd, $DATE_FORMAT, false ) . '<br>
                <select name="pref_DATE_FORMAT_MY">' . $datestyle_my . '
                </select>&nbsp;' . date_to_str( $dateYmd, $DATE_FORMAT_MY, false ) . '<br>
                <select name="pref_DATE_FORMAT_MD">' . $datestyle_md . '
                </select>&nbsp;' . date_to_str( $dateYmd, $DATE_FORMAT_MD, false ) . '<br>
                <select name="pref_DATE_FORMAT_TASK">' . $datestyle_tk . '
                </select>&nbsp;' . translate( 'Small Task Date' ) . ' ' . date_to_str( $dateYmd, $DATE_FORMAT_TASK, false ) . '</p>
              <p title="' . tooltip( 'time_format_help' ) . '">' . translate( 'Time format' ) . print_radio( 'TIME_FORMAT', array( '12'=>translate( '12 hour' ), '24'=>translate( '24 hour' ) ) ) . '</p>
              <p title="' . tooltip( 'display_week_starts_on' ) . '">' . translate( 'Week starts on' ) . '
                <select id="pref_WEEK_START" name="pref_WEEK_START">' . $start_wk_on . '
                </select></p>
              <p title="' . tooltip( 'display_weekend_starts_on' ) . '">' . translate( 'Weekend starts on' ) . '
                <select id="pref_WEEKEND_START" name="pref_WEEKEND_START">' . $start_wkend_on . '
                </select></p>
              <p title="' . tooltip( 'work_hours_help' ) . '">' . translate( 'Work hours' ) . '
                <label for="pref_starthr">' . translate( 'From' ) . '</label>
                <select id="pref_starthr" name="pref_WORK_DAY_START_HOUR">' . $work_hr_start . '
                </select>
                <label for="pref_endhr">' . translate( 'to' ) . '</label>
                <select id="pref_endhr" name="pref_WORK_DAY_END_HOUR">' . $work_hr_end . '
                </select></p>
          </fieldset>
          <fieldset>
            <legend>' . translate( 'Appearance' ) . '</legend>
            <p title="' . tooltip( 'preferred_view_help' ) . '">' . translate( 'Preferred view' ) . '
              <select name="pref_STARTVIEW">' . $prefer_vu . $user_vu . '
              </select></p>
            <p class="tooltipselect" title="' . tooltip( 'fonts_help' ) . '">
              <label for="pref_font">' . translate( 'Fonts' ) . '</label>
              <input type="text" id="pref_font" name="pref_FONTS" size="40" value="' . htmlspecialchars( $prefarray['FONTS'] ) . '"></p>';
foreach ( array(
    // tooltip( 'display_sm_month_help' )
    'sm_month' => translate( 'Display small months' ),
    // tooltip( 'display_weekends_help' )
    'weekends' => translate( 'Display weekends' ),
    // tooltip( 'display_long_days_help' )
    'long_days' => translate( 'Display long day names' ),
    // tooltip( 'display_minutes_help' )
    'minutes' => translate( 'Display 00 minutes always' ),
    // tooltip( 'display_end_times_help' )
    'end_times' => translate( 'Display end times on calendars' ),
    // tooltip( 'display_all_days_in_month_help' )
    'all_days_in_month' => translate( 'Display all days in month view' ),
    // tooltip( 'display_weeknumber_help' )
    'weeknumber' => translate( 'Display week number' ),
    // tooltip( 'display_tasks_help' )
    'tasks' => translate( 'Display small task list' ),
    // tooltip( 'display_tasks_in_grid_help' )
    'tasks_in_grid' => translate( 'Display tasks in Calendars' ),
    // tooltip( 'display_moon_phases_help' )
    'moon_phases' => translate( 'Display Lunar Phases in month view' ),
  ) as $k => $v ) } {
  echo '
            <p title="' . tooltip( // bypass update_translation.pl
   'display_' . $k . '_help' ) . '">' . $v . print_radio( 'DISPLAY_' . strtoupper( $k ) ) . '</p>';
}
echo '
          </fieldset>
          <fieldset>
            <legend>' . translate( 'Events' ) . '</legend>
            <p title="' . tooltip( 'display_unapproved_help' ) . '">' . translate( 'Display unapproved' ) . print_radio( 'DISPLAY_UNAPPROVED' ) . '</p>
            <p title="' . tooltip( 'timed_evt_len_help' ) . '">' . translate( 'Specify timed event length by' ) . print_radio( 'TIMED_EVT_LEN', array( 'D' => translate( 'Duration' ), 'E' => translate( 'End Time' ) ) ) . '</p>' . $catList . '
            <p title="' . tooltip( 'disable_crossday_events_help' ) . '">' . translate( 'Disable Cross-Day Events' ) . print_radio( 'DISABLE_CROSSDAY_EVENTS' ) . '</p>
            <p title="' . tooltip( 'display_desc_print_day_help' ) . '">' . translate( 'desc in printer day view' ) . print_radio( 'DISPLAY_DESC_PRINT_DAY' ) . '</p>
            <p title="' . tooltip( 'entry-interval-help' ) . '">' . translate( 'Entry interval' ) . '
              <select name="pref_ENTRY_SLOTS">' . $entry_slots . '
              </select></p>
            <p title="' . tooltip( 'time-interval-help' ) . '">' . translate( 'Time interval' ) . '
              <select name="pref_TIME_SLOTS">' . $time_slots . '
              </select></p>
          </fieldset>
          <fieldset>
            <legend>' . translate( 'Miscellaneous' ) . '</legend>
            <p title="' . tooltip( 'auto-refresh-help' ) . '">' . translate( 'Auto-refresh calendars' ) . print_radio( 'AUTO_REFRESH' ) . '</p>
            <p title="' . tooltip( 'auto-refresh-time-help' ) . '">&nbsp;&nbsp;&nbsp;&nbsp;' . translate( 'Auto-refresh time' ) . '
              <input type="text" name="pref_AUTO_REFRESH_TIME" size="4" value="' . ( empty( $prefarray['AUTO_REFRESH_TIME'] ) ? 0 : $prefarray['AUTO_REFRESH_TIME'] ) . '">' .  $minutesStr . '</p>
          </fieldset>
        </div>
<!-- END SETTINGS -->';

if ( $ALLOW_USER_THEMES == 'Y' || $is_admin ) {
  echo '
        <div id="tabscontent_themes" class="tooltip">
          <p title="' . tooltip( 'theme-reload-help' ) . '" colspan="3">' . translate( 'reload page to show Theme' ) . '</p>
          <p class="tooltipselect" title="' . tooltip( 'themes-help' ) . '"><label for="pref_THEME">' . translate( 'Themes_' ) . '</label>
            <select id="pref_THEME" name="pref_THEME">' . $option . 'none" disabled selected>' . translate( 'AVAILABLE THEMES' ) . '</option>';
  foreach ( $themes as $theme ) {
    echo $option . $theme . '">' . $theme . '</option>';
  }
  echo '
            </select>
            <input type="button" id="previewBtn" name="preview" value="' . translate( 'Preview' ) . '"></p>';
  if ( $MENU_ENABLED == 'Y' ) {
    echo '
          <p title="' . tooltip( 'menu_themes_help' ) . '"><label for="pref_MENU_THEME">' . translate( 'Menu theme' ) . '</label>
            <select id="pref_MENU_THEME" name="pref_MENU_THEME">' . $option . 'default"' . ( $prefarray['MENU_THEME'] == 'default' ? ' selected' : '' ) . '>default</option>';
    foreach ( $menuthemes as $menutheme ) {
      echo $option . $menutheme . ( $prefarray['MENU_THEME'] == $menutheme ? '" selected>' : '">' ) . $menutheme . '</option>';
    }
    echo '
            </select></p>';
  }
  echo '
        </div>
<!-- END THEMES -->';
}

echo ( $updating_public ? '' : ( $SEND_EMAIL == 'Y' ? '
        <div id="tabscontent_email" class="tooltip">
          <p title="' . tooltip( 'email-format' ) . '">' . translate( 'Email format preference' ) . print_radio( 'EMAIL_HTML', array( 'Y' => translate( 'HTML' ), 'N' => translate( 'Plain Text' ) ) ) . '</p>
          <p title="' . tooltip( 'email_attach_ics' ) . '">' . translate( 'Include iCalendar attachments' ) . print_radio( 'EMAIL_ATTACH_ICS', '', '', 0 ) . '</p>
          <p title="' . tooltip( 'email_reminder_help' ) . '">' . translate( 'Event reminders' ) . print_radio( 'EMAIL_REMINDER' ) . '</p>
          <p title="' . tooltip( 'email_event_added' ) . '">' . translate( 'Events added to my calendar' ) . print_radio( 'EMAIL_EVENT_ADDED' ) . '</p>
          <p title="' . tooltip( 'email_event_updated' ) . '">' . translate( 'Events updated on my calendar' ) . print_radio( 'EMAIL_EVENT_UPDATED' ) . '</p>
          <p title="' . tooltip( 'email_event_deleted' ) . '">' . translate( 'Events removed from my calendar' ) . print_radio( 'EMAIL_EVENT_DELETED' ) . '</p>
          <p title="' . tooltip( 'email_event_rejected' ) . '">' . translate( 'Event rejected by participant' ) . print_radio( 'EMAIL_EVENT_REJECTED' ) . '</p>
          <p title="' . tooltip( 'email_event_create' ) . '">' . translate( 'Event that I create' ) . print_radio( 'EMAIL_EVENT_CREATE' ) . '</p>
        </div>
<!-- END EMAIL -->' : '' ) . '
        <div id="tabscontent_boss" class="tooltip">' . ( $SEND_EMAIL == 'Y' ? '
          <p>' . translate( 'Email me event notification' ) . print_radio( 'EMAIL_ASSISTANT_EVENTS' ) . '</p>' : '' ) . '
          <p>' . translate( 'I want to approve events' ) . print_radio( 'APPROVE_ASSISTANT_EVENT' ) . '</p>
          <p title="' . tooltip( 'display_byproxy-help' ) . '">' . translate( 'Display if created by Assistant' ) . print_radio( 'DISPLAY_CREATED_BYPROXY' ) . '</p>
        </div>
<!-- END BOSS -->' );

if ( $PUBLISH_ENABLED == 'Y' || $RSS_ENABLED == 'Y') {
  $publish_access = ( empty( $prefarray['USER_REMOTE_ACCESS'] )
    ? 0 : $prefarray['USER_REMOTE_ACCESS'] );
  echo '
        <div id="tabscontent_subscribe" class="tooltipselect">
          <p title="' . tooltip( 'allow-view-subscriptions-help' ) . '">' . translate( 'Allow remote viewing of' ) . '
            <select name="pref_USER_REMOTE_ACCESS">';
  $tmp = array(
    translate( 'Public entries' ),
    translate( 'Public and Confidential entries' ),
    translate( 'All entries' ),
  );
  for ( $i = 0; $i < 3; $i++ ) {
    echo $option . $i . ( $publish_access == $i ? '" selected>' : '">' ) . $tmp[$i] . '</option>';
  }
  $empServ = empty( $SERVER_URL );
  $upPub = ( $updating_public ? '__public__' : $user );
  $htmlServURL = htmlspecialchars( $SERVER_URL );
  $longURLStr = '&nbsp;&nbsp;&nbsp;&nbsp;' . $urlStr . $htmlServURL;
  echo '
            </select></p>' . ( $PUBLISH_ENABLED == 'Y' ? '
          <p title="' . tooltip( 'allow-remote-subscriptions-help' ) . '">' . translate( 'Allow remote subscriptions' ) . print_radio( 'USER_PUBLISH_ENABLED' ) . '</p>' . ( $empServ ? '' : '
          <p title="' . tooltip( 'remote-subscriptions-url-help' ) . '">' . $longURLStr . 'publish.php/' . $upPub . '.ics<br>' . $htmlServURL . 'publish.php?user=' . $upPub . '</p>' ) . '
          <p title="' . tooltip( 'allow-remote-publishing-help' ) . '">' . translate( 'Allow remote publishing' ) . print_radio( 'USER_PUBLISH_RW_ENABLED' ) . '</p>' . ( $empServ ? '' : '
          <p title="' . tooltip( 'remote-publishing-url-help' ) . '">' . $longURLStr . 'icalclient.php</p>' ) : '' ) . ( $RSS_ENABLED == 'Y' ? '
          <p title="' . tooltip( 'rss-enabled-help' ) . '">' . translate( 'Enable RSS feed' ) . print_radio( 'USER_RSS_ENABLED' ) . '</p>' . ( $empServ ? '' : '
          <p title="' . tooltip( 'rss-feed-url-help' ) . '">' . $longURLStr . 'rss.php?user=' . $upPub . '</p>' ) : '' ) . '
          <p title="' . tooltip( 'freebusy-enabled-help' ) . '">' . translate( 'Enable FreeBusy publishing' ) . print_radio( 'FREEBUSY_ENABLED' ) . '</p>' . ( $empServ ? '' : '
          <p title="' . tooltip( 'freebusy-url-help' ) . '">' . $longURLStr . 'freebusy.php/' . $upPub . '.ifb<br>' . $htmlServURL . 'freebusy.php?user=' .$upPub . '</p>' ) . '
        </div>
<!-- END SUBSCRIBE -->';
}
setcookie( 'user', '' );

if ( $ALLOW_USER_HEADER == 'Y' ) {
  setcookie( 'user', $prefuser, 180 );
  $tmp = array();

  if ( $CUSTOM_SCRIPT == 'Y' )
    // tooltip( 'custom_script_help' )
    $tmp['script'] => translate( 'Custom script' );

  if ( $CUSTOM_HEADER == 'Y' )
    // tooltip( 'custom_header_help' )
    $tmp['header'] => translate( 'Custom header' );

  if ( $CUSTOM_TRAILER == 'Y' )
    // tooltip( 'custom_trailer_help' )
    $tmp['trailer'] => translate( 'Custom trailer' );

  foreach ( $tmp as $k => $v ) {
    echo '
          <p title="' . tooltip( // bypass update_translation.pl
      'custom-' . $k . '-help' ) . '">' . $v . '
            <input type="button" id="' . $k . 'Btn" value="' . $editStr . '" name=""></p>';
  }
  echo '
        </div>
<!-- END HEADER -->';
}
echo '
<!-- BEGIN COLORS -->';

if ( $ALLOW_COLOR_CUSTOMIZATION == 'Y' ) {
  set_today( $dateYmd );
  $tmp = translate( 'Table cell other month BG' );
  echo '
        <div id="tabscontent_colors">';
  foreach ( array(
      'BGCOLOR'     => translate( 'Document BG' ),
      'H2COLOR'     => translate( 'Document title' ),
      'TEXTCOLOR'   => translate( 'Document text' ),
      'MYEVENTS'    => translate( 'My event text' ),
      'TABLEBG'     => translate( 'Table grid color' ),
      'THBG'        => translate( 'Table header BG' ),
      'THFG'        => translate( 'Table header text' ),
      'CELLBG'      => translate( 'Table cell BG' ),
      'TODAYCELLBG' => translate( 'Table cell today BG' ),
      'HASEVENTSBG' => translate( 'Table cell events BG' ),
      'WEEKENDBG'   => translate( 'Table cell weekends BG' ),
      'OTHERMONTHBG'=> $tmp,
      'WEEKNUMBER'  => $tmp,
      'POPUP_BG'    => translate( 'Event popup BG' ),
      'POPUP_FG'    => translate( 'Event popup text' ),
    ) as $k => $v ) {
    echo '
          <p>' . print_color_input_html( $k, $v ) ) . '</p>';
  }
  echo '
<!-- BEGIN EXAMPLE MONTH -->
          <div id="example_month">
            <p>' . date_to_str( date( 'Ymd' ), $DATE_FORMAT_MY, false ) . '</p>'
   . display_month( date( 'm' ), date( 'Y' ), true ) . '
          </div>
<!-- END EXAMPLE MONTH -->
        </div>
<!-- END COLORS -->
}
echo '
      </div>

<!-- END TABS -->
      <br><br>
      <div>
        <input type="submit" value="' . translate( 'Save Preferences' ) . '" name=""><br><br>
      </div>
    </form>' .  print_trailer();

ob_end_flush();

?>
