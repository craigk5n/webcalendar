<?php
/* Consolidating parts of admin.php and pref.php.
 * $Id: common_admin_pref.php,v 1.4.2.1 2012/02/20 01:29:20 cknudsen Exp $
 */
function_exists('translate') or die('You cannot access this file directly!');
// .
// Force the CSS cache to clear by incrementing webcalendar_csscache cookie.
$webcalendar_csscache = 1;
if ( isset ( $_COOKIE['webcalendar_csscache'] ) )
  $webcalendar_csscache += $_COOKIE['webcalendar_csscache'];

SetCookie ( 'webcalendar_csscache', $webcalendar_csscache );

$catStr = $color_sets = $currenttab = $datestyle_md = $datestyle_my = '';
$datestyle_tk = $datestyle_ymd = $lang_list = $menu_theme_list = '';
$theme_list = $prefer_vu = $start_wk_on = $start_wkend_on = $tabs = $tmp = '';
$user_vu = $work_hr_end = $work_hr_start = '';

$choices = $choices_text = $menuthemes = $prefarray = $s = $themes = array ();

$editStr = '<input type="button" value="' . translate ( 'Edit' )
 . "...\" onclick=\"window.open( 'edit_template.php?type=%s','cal_template','"
 . 'dependent,menubar,scrollbars,height=500,width=500,outerHeight=520,'
 . 'outerWidth=520\' );" name="" /></p>';
$option = '
            <option value="';
$selected = ' selected="selected"';
// .
// Get system settings.
$res = dbi_execute ( 'SELECT cal_setting, cal_value FROM webcal_config' );
if ( $res ) {
  while ( $row = dbi_fetch_row ( $res ) ) {
    $setting = $row[0];
    $prefarray[$setting] = $s[$setting] = $value = $row[1];
  }
  dbi_free_result ( $res );
}
// .
// Get list of theme files from "themes" directory.
$dir = 'themes/';
if ( is_dir ( $dir ) && $dh = opendir ( $dir ) ) {
  while ( ( $file = readdir ( $dh ) ) !== false ) {
    if ( strpos ( $file, '_admin.php' ) )
      $themes[] = strtoupper ( str_replace ( '_admin.php', '', $file ) );
    else
    if ( strpos ( $file, '_pref.php' ) )
      $themes[] = strtolower ( str_replace ( '_pref.php', '', $file ) );
  }
  sort ( $themes );
  closedir ( $dh );
}
// .
// Get list of menu themes.
$dir = 'includes/menu/themes/';
if ( is_dir ( $dir ) && $dh = opendir ( $dir ) ) {
  while ( ( $file = readdir ( $dh ) ) !== false ) {
    if ( $file == '.' || $file == '..' || $file == 'CVS' ||
      ( ! $prad && $file == 'default' ) )
      continue;

    if ( is_dir ( $dir . $file ) )
      $menuthemes[] = $file;
  }
  sort ( $menuthemes );
  closedir ( $dh );
}
// .
// Set globals values to be passed to styles.php.
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

if ( $prad || access_can_access_function ( ACCESS_DAY, $user ) ) {
  $choices[] = 'day.php';
  $choices_text[] = translate ( 'Day' );
}
if ( $prad || access_can_access_function ( ACCESS_WEEK, $user ) ) {
  $choices[] = 'week.php';
  $choices_text[] = translate ( 'Week' );
}
if ( $prad || access_can_access_function ( ACCESS_MONTH, $user ) ) {
  $choices[] = 'month.php';
  $choices_text[] = translate ( 'Month' );
}
if ( $prad || access_can_access_function ( ACCESS_YEAR, $user ) ) {
  $choices[] = 'year.php';
  $choices_text[] = translate ( 'Year' );
}
// .
// This should be easier to add more tabs if needed.
if ( $prad ) {
  $tabs_ar = array ( // tab name, title= (if any), display text
    'settings', '', translate ( 'Settings' ),
    'public', '', translate ( 'Public Access' ),
    'uac', '', translate ( 'User Access Control' ),
    'groups', '', translate ( 'Groups' ),
    'nonuser', '', translate ( 'NonUser Calendars' ),
    'other', '', translate ( 'Other' ),
    'email', '', translate ( 'Email' ),
    'colors', '', translate ( 'Colors' )
    );
} else {
  $tabs_ar = array ( // .
    'settings', '', translate ( 'Settings' ) );

  if ( $ALLOW_USER_THEMES == 'Y' || $is_admin ) {
    $tabs_ar[] = 'themes';
    $tabs_ar[] = '';
    $tabs_ar[] = translate ( 'Themes' );
  }
  if ( $SEND_EMAIL == 'Y' ) {
    $tabs_ar[] = 'email';
    $tabs_ar[] = '';
    $tabs_ar[] = translate ( 'Email' );
  }
  $tabs_ar[] = 'boss';
  $tabs_ar[] = '';
  $tabs_ar[] = translate ( 'When I am the boss' );

  if ( $PUBLISH_ENABLED == 'Y' || $RSS_ENABLED == 'Y' ) {
    $tabs_ar[] = 'subscribe';
    $tabs_ar[] = '';
    $tabs_ar[] = translate ( 'Subscribe/Publish' );
  }
  if ( $ALLOW_USER_HEADER == 'Y' &&
    ( $CUSTOM_SCRIPT == 'Y' || $CUSTOM_HEADER == 'Y' || $CUSTOM_TRAILER == 'Y' ) ) {
    $tabs_ar[] = 'header';
    $tabs_ar[] = '';
    $tabs_ar[] = translate ( 'Custom Scripts' );
  }
  if ( $ALLOW_COLOR_CUSTOMIZATION == 'Y' ) {
    $tabs_ar[] = 'colors';
    $tabs_ar[] = ' title="' . tooltip ( 'colors-help' );
    $tabs_ar[] = translate ( 'Colors' );
  }
}
// .
// Move the loops here and combine a few.
for ( $i = 0, $cnt = count ( $tabs_ar ); $i < $cnt; $i += 3 ) {
  $tabs .= '
        <span class="tab' . ( $i > 0 ? 'bak' : 'for' ) . '" id="tab_'
   . $tabs_ar[$i] . $tabs_ar[$i + 1] . '"><a href="" onclick="return setTab( \''
   . $tabs_ar[$i] . '\' );">' . $tabs_ar[$i + 2] . '</a></span>';
}
$tmp = ( $prad ? $s['LANGUAGE'] : $prefarray['LANGUAGE'] );
while ( list ( $key, $val ) = each ( $languages ) ) {
  // Don't allow users to select "browser-defined". We want them to pick
  // a language so that when we send reminders (done without the benefit
  // of a browser-preferred language), we'll know which language to use.
  // DO let them select browser-defined for the public user or NUC.
  if ( $prad ||
    ( ! $prad &&
      ( $key != 'Browser-defined' || $updating_public || $is_admin || $is_nonuser_admin ) ) )
    $lang_list .= $option . $val . '"' . ( $val == $tmp ? $selected : '' )
     . '>' . translate ( $key ) . '</option>';
}
$tmp = ( $prad ? $s['DATE_FORMAT'] : $prefarray['DATE_FORMAT'] );
for ( $i = 0, $cnt = count ( $datestyles ); $i < $cnt; $i++ ) {
  $datestyle_ymd .= $option . $datestyles[$i] . '"'
   . ( $tmp == $datestyles[$i] ? $selected : '' )
   . '>' . $datestyles[++$i] . '</option>';
}
$tmp = ( $prad ? $s['DATE_FORMAT_MY'] : $prefarray['DATE_FORMAT_MY'] );
for ( $i = 0, $cnt = count ( $datestyles_my ); $i < $cnt; $i++ ) {
  $datestyle_my .= $option . $datestyles_my[$i] . '"'
   . ( $tmp == $datestyles_my[$i] ? $selected : '' )
   . '>' . $datestyles_my[++$i] . '</option>';
}
$tmp = ( $prad ? $s['DATE_FORMAT_MD'] : $prefarray['DATE_FORMAT_MD'] );
for ( $i = 0, $cnt = count ( $datestyles_md ); $i < $cnt; $i++ ) {
  $datestyle_md .= $option . $datestyles_md[$i] . '"'
   . ( $tmp == $datestyles_md[$i] ? $selected : '' )
   . '>' . $datestyles_md[++$i] . '</option>';
}
$tmp = ( $prad ? $s['DATE_FORMAT_TASK'] : $prefarray['DATE_FORMAT_TASK'] );
for ( $i = 0, $cnt = count ( $datestyles_task ); $i < $cnt; $i++ ) {
  $datestyle_tk .= $option . $datestyles_task[$i] . '"'
   . ( $tmp == $datestyles_task[$i] ? $selected : '' )
   . '>' . $datestyles_task[++$i] . '</option>';
}
$tmp_wk = ( $prad ? $s['WEEK_START'] : $prefarray['WEEK_START'] );
$tmp_en = ( $prad ? $s['WEEKEND_START'] :$prefarray['WEEKEND_START'] );
for ( $i = 0; $i < 7; $i++ ) {
  $start_wk_on .= $option . "$i\""
   . ( $i == $tmp_wk ? $selected : '' )
   . '>' . weekday_name ( $i ) . '</option>';
  $j = ( $i == 0 ? 6 : $i - 1 ); // Make sure to start with Saturday.
  $start_wkend_on .= $option . "$j\""
   . ( $j == $tmp_en ? $selected : '' )
   . '>' . weekday_name ( $j ) . '</option>';
}
$tmp_st = ( $prad ? $s['WORK_DAY_START_HOUR'] : $prefarray['WORK_DAY_START_HOUR'] );
$tmp_en = ( $prad ? $s['WORK_DAY_END_HOUR'] : $prefarray['WORK_DAY_END_HOUR'] );
for ( $i = 0; $i < 24; $i++ ) {
  $tmp = display_time ( $i * 10000, 1 );
  $work_hr_start .= $option . "$i\""
   . ( $i == $tmp_st ? $selected : '' )
   . '>' . $tmp . '</option>';
  $work_hr_end .= $option . "$i\""
   . ( $i == $tmp_en ? $selected : '' )
   . '>' . $tmp . '</option>';
}
$tmp = ( $prad ? $s['STARTVIEW'] : $prefarray['STARTVIEW'] );
for ( $i = 0, $cnt = count ( $choices ); $i < $cnt; $i++ ) {
  $prefer_vu .= $option . $choices[$i] . '"'
   . ( $tmp == $choices[$i] ? $selected : '' )
   . '>' . htmlspecialchars ( $choices_text[$i] ) . '</option>';
}
// Allow user to select a view also.
$tmp = ( $prad ? $s['STARTVIEW'] : $prefarray['STARTVIEW'] );
for ( $i = 0, $cnt = count ( $views ); $i < $cnt; $i++ ) {
  if ( $views[$i]['cal_is_global'] != 'Y' &&
    ( $prad || $views[$i]['cal_owner'] != $user ) )
    continue;

  $xurl = $views[$i]['url'];
  $xurl_strip = str_replace ( '&amp;', '&', $xurl );
  $user_vu .= $option . $xurl . '"'
   . ( $tmp == $xurl_strip ? $selected : '' )
   . '>' . htmlspecialchars ( $views[$i]['cal_name'] ) . '</option>';
}
foreach ( array ( // .
    'BGCOLOR' => translate ( 'Document background' ),
    'H2COLOR' => translate ( 'Document title' ),
    'TEXTCOLOR' => translate ( 'Document text' ),
    'MYEVENTS' => translate ( 'My event text' ),
    'TABLEBG' => translate ( 'Table grid color' ),
    'THBG' => translate ( 'Table header background' ),
    'THFG' => translate ( 'Table header text' ),
    'CELLBG' => translate ( 'Table cell background' ),
    'TODAYCELLBG' => translate ( 'Table cell background for current day' ),
    'HASEVENTSBG' => translate ( 'Table cell background for days with events' ),
    'WEEKENDBG' => translate ( 'Table cell background for weekends' ),
    'OTHERMONTHBG' => translate ( 'Table cell background for other month' ),
    'WEEKNUMBER' => translate ( 'Week number color' ),
    'POPUP_BG' => translate ( 'Event popup background' ),
    'POPUP_FG' => translate ( 'Event popup text' ),
    ) as $k => $v ) {
  $color_sets .= print_color_input_html ( $k, $v );
}
$example_month = '

<!-- BEGIN EXAMPLE MONTH -->
          <div id="example_month">
            <p>' . date_to_str ( date ( 'Ymd' ), $DATE_FORMAT_MY, false ) . '</p>'
 . display_month ( date ( 'm' ), date ( 'Y' ), true ) . '
          </div>
<!-- END EXAMPLE MONTH -->
';
/* Save either system or user preferences.
 *
 * @param string  $prefs
 * @param string  $src
 * @param bool    _SYSTEM_ = true
 *                user     = false
 */
function save_pref ( $prefs, $src ) {
  global $error, $my_theme, $prad;

  if ( ! $prad )
    global $prefuser;

  $pos = ( $prad ? 6 : 5 );

  while ( list ( $key, $value ) = each ( $prefs ) ) {
    if ( $src == 'post' ) {
      $prefix = substr ( $key, 0, $pos );
      $setting = substr ( $key, $pos );
      if ( ( ! $prad && $prefix != 'pref_' ) || $prad && $key == 'currenttab' )
        continue;
      // .
      // Validate key name.
      // If $prad not true, should start with "pref_"
      // else should start with "admin_",
      // and not include any unusual characters that might be an SQL injection attack.
      if ( ( ! $prad && ! preg_match ( '/pref_[A-Za-z0-9_]+$/', $key ) ) ||
          ( $prad && ! preg_match ( '/admin_[A-Za-z0-9_]+$/', $key ) ) )
        die_miserable_death ( str_replace ( 'XXX', $key,
            translate ( 'Invalid setting name XXX.' ) ) );
    } else {
      $prefix = ( $prad ? 'admin_' : 'pref_' );
      $setting = $key;
    }
    if ( strlen ( $setting ) > 0 && ( $prefix == 'pref_' ) || $prefix == 'admin_' ) {
      if ( $setting == 'THEME' && $value != 'none' )
        $my_theme = strtolower ( $value );

      if ( $prad ) {
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
      } else {
        dbi_execute ( 'DELETE FROM webcal_user_pref WHERE cal_login = ?
          AND cal_setting = ?', array ( $prefuser, $setting ) );
        if ( strlen ( $value ) > 0 ) {
          $setting = strtoupper ( $setting );

          $sql = 'INSERT INTO webcal_user_pref ( cal_login, cal_setting,
            cal_value ) VALUES ( ?, ?, ? )';
          if ( ! dbi_execute ( $sql, array ( $prefuser, $setting, $value ) ) ) {
            $error = 'Unable to update preference: ' . dbi_error ()
             . '<br /><br /><span class="bold">SQL:</span>' . $sql;
            break;
          }
        }
      }
    }
  }
  // Reload preferences so any CSS changes will take effect.
  load_global_settings ();
  load_user_preferences ();
}

?>
