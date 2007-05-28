<?php
/* $Id$ */
$prad = $translation_loaded = $updating_public = false;

include_once 'includes/init.php';
include 'includes/date_formats.php';
include 'includes/common_admin_pref.php';

$public = getGetValue ( 'public' );
$user = getGetValue ( 'user' );

load_global_settings ();

if ( $is_admin && ! empty ( $public ) && $PUBLIC_ACCESS == 'Y' ) {
  $prefuser = '__public__';
  $updating_public = true;
  load_user_preferences ( '__public__' );
} elseif ( ! empty ( $user ) && $user != $login &&
    ( $is_admin || $is_nonuser_admin ) ) {
  $prefuser = $user;
  load_user_preferences ( $user );
} else {
  $prefuser = $login;
  // Reload preferences so any CSS changes will take effect.
  load_user_preferences ();
}

if ( ! empty ( $_POST ) && empty ( $error ) ) {
  $currenttab = getPostValue ( 'currenttab' );
  $my_theme = '';

  save_pref ( $_POST, 'post' );

  if ( ! empty ( $my_theme ) ) {
    include_once 'themes/' . $my_theme . '_pref.php';
    save_pref ( $webcal_theme, 'theme' );
  }
}

if ( $user != $login )
  $user = ( ( $is_admin || $is_nonuser_admin ) && $user ? $user : $login );

load_user_categories ();
// .
// Reload preferences into $prefarray[].
// Get user settings.
$res = dbi_execute ( 'SELECT cal_setting, cal_value FROM webcal_user_pref
  WHERE cal_login = ?', array ( $prefuser ) );
if ( $res ) {
  while ( $row == dbi_fetch_row ( $res ) ) {
    $prefarray[$row[0]] = $row[1];
  }
  dbi_free_result ( $res );
}
// .
// Make sure globals values passed to styles.php are for this user.
// Makes the demo calendar accurate.
$GLOBALS['BGCOLOR'] = $prefarray['BGCOLOR'];
$GLOBALS['CELLBG'] = $prefarray['CELLBG'];
$GLOBALS['FONTS'] = $prefarray['FONTS'];
$GLOBALS['H2COLOR'] = $prefarray['H2COLOR'];
$GLOBALS['MENU_THEME'] = $prefarray['MENU_THEME'];
$GLOBALS['MYEVENTS'] = $prefarray['MYEVENTS'];
$GLOBALS['OTHERMONTHBG'] = $prefarray['OTHERMONTHBG'];
$GLOBALS['TABLEBG'] = $prefarray['TABLEBG'];
$GLOBALS['THBG'] = $prefarray['THBG'];
$GLOBALS['TODAYCELLBG'] = $prefarray['TODAYCELLBG'];
$GLOBALS['WEEKENDBG'] = $prefarray['WEEKENDBG'];

$dateYmd = date ( 'Ymd' );

$entryStr = ' ' . translate ( 'entries' ) . '</option>';
$minutesStr = ' ' . translate ( 'minutes' ) . '</option>';
$publicStr = translate ( 'Public' );
$saveStr = '<input type="submit" value="' . translate ( 'Save Preferences' )
 . '" name="" />';
$urlStr = translate ( 'URL' );

// The commented ones moved here just to keep them together in the language files.
$zerStr = translate ( '0' ) . $minutesStr;
$oneStr = translate ( '1' );
$twoStr = translate ( '2' );
$triStr = translate ( '3' );
// translate ('4')
$fivStr = translate ( '5' ) . $minutesStr;
// translate ('6') translate ('7') translate ('8') translate ('9')
$tenStr = $oneStr . $zerStr;
$fifteenStr = $oneStr . $fivStr;
$twentyStr = $twoStr . $zerStr;
$thirtyStr = $triStr . $zerStr;
// .
// Allow css_cache to display public or NUC values.
@session_start ();
$_SESSION['webcal_tmp_login'] = $prefuser;

print_header (
  array ( 'js/pref.php', 'js/visible.php' ),
  '',
  ( empty ( $currenttab )
    ? '' : 'onload="showTab ( \'' . $currenttab . '\' );"' ) );

$tmp = '';
if ( $is_nonuser_admin ||
  ( $is_admin && substr ( $prefuser, 0, 5 ) == '_NUC_' ) ) {
  nonuser_load_variables ( $user, 'nonuser' );
  $tmp = '<br /><strong>-- ' . translate ( 'Admin mode' ) . ': '
   . $nonuserfullname . ' --</strong>';
}
// .
// If user is admin of a non-user cal, and non-user cal is "public"
// (meaning it is a public calendar that requires no login),
// then allow the current user to modify prefs for that nonuser cal.
$public_option = ( $is_admin && ! $updating_public && empty ( $public ) && !
  empty ( $PUBLIC_ACCESS ) && $PUBLIC_ACCESS == 'Y' ?
  $option . 'pref.php?public=1">'
   . translate ( 'Public Access calendar' ) . '</option>' : '' );

ob_start ();

echo '
    <h2>'
 . ( $updating_public ? translate ( $PUBLIC_ACCESS_FULLNAME ) . '&nbsp;' : '' )
 . translate ( 'Preferences' ) . $tmp
 . '<img src="images/help.gif" alt="' . translate ( 'Help' )
 . '" class="help" onclick="window.open ( \'help_pref.php\', \'cal_help\', '
 . '\'dependent,menubar,scrollbars,height=400,width=400,innerHeight=420,'
 . 'outerWidth=420\' );" /></h2>
    <form action="' . substr ( $self, strrpos ( $self, '/' ) + 1 )
 . ( empty ( $_SERVER['QUERY_STRING'] ) ? '' : '?' . $_SERVER['QUERY_STRING'] )
 . '" method="post" onsubmit="return valid_form ( this );" name="prefform">
      <input type="hidden" name="currenttab" id="currenttab" value="'
 . $currenttab . '" />' . ( $user ? '
      <input type="hidden" name="user" value="' . $user . '" />' : '' )
 . display_admin_link () . $saveStr . ( $updating_public ? '
      <input type="hidden" name="public" value="1" />' : '' );

if ( ( empty ( $user ) || $user == $login ) && ! $updating_public ) {
  $nulist = get_my_nonusers ( $login );
  echo '
      <select onchange="location=this.options[this.selectedIndex].value;">
        <option' . $selected . ' disabled="disabled" value="">'
   . translate ( 'Modify Non User Calendar Preferences' ) . '</option>'
   . ( empty ( $public_option ) ? '' : $public_option );

  for ( $i = 0, $cnt = count ( $nulist ); $i < $cnt; $i++ ) {
    echo $option . 'pref.php?user=' . $nulist[$i]['cal_login'] . '">'
     . $nulist[$i]['cal_fullname'] . '</option>';
  }
  echo '
      </select>';
} else {
  $linktext = translate ( 'Return to My Preferences' );
  echo '
      <a title="' . $linktext . '" class="nav" href="pref.php">&laquo;&nbsp; '
   . $linktext . '</a>';
}

if ( empty ( $prefarray['TIMEZONE'] ) )
  $prefarray['TIMEZONE'] = $SERVER_TIMEZONE;

/* For backwards compatibility.  We used to store without the .php extension. */
if ( $prefarray['STARTVIEW'] == 'month' || $prefarray['STARTVIEW'] == 'day' ||
  ( $prefarray['STARTVIEW'] == 'week' ) || $prefarray['STARTVIEW'] == 'year' )
  $prefarray['STARTVIEW'] .= '.php';

if ( ! empty ( $categories ) ) {
  $catStr .= '
              <p><label for="pref_cat">'
   . translate ( 'Default Category' ) . ':</label>
                <select name="pref_CATEGORY_VIEW" id="pref_cat">';

  foreach ( $categories as $K => $V ) {
    $catStr .= $option . "$K\""
     . ( ! empty ( $prefarray['CATEGORY_VIEW'] ) && $prefarray['CATEGORY_VIEW'] == $K
      ? $selected : '' ) . '>' . $V['cat_name'] . '</option>';
  }
  $catStr .= '
                </select></p>';
}
$hourStr = translate ( 'hour' );
echo '

<!-- TABS -->
      <div id="tabs">' . $tabs . '
      </div>

<!-- TABS BODY -->
      <div id="tabscontent">
<!-- DETAILS -->
        <div id="tabscontent_settings">
          <fieldset>
            <legend>' . translate ( 'Language' ) . '</legend>
            <p><label for="pref_lang" title="' . tooltip ( 'language-help' )
 . '">' . translate ( 'Language' ) . ':</label>
              <select name="pref_LANGUAGE" id="pref_lang">' . $lang_list . '
              </select>' . str_replace ( 'XXX',
  translate ( get_browser_language ( true ) ),
  translate ( 'Your browser default language is XXX.' ) ) . '</p>
          </fieldset>
          <fieldset>
            <legend>' . translate ( 'Date and Time' ) . '</legend>'
// Determine if we can set timezones.  If not, don't display any options.
 . ( set_env ( 'TZ', $prefarray['TIMEZONE'] ) ? '
              <p><label for="pref_TIMEZONE" title="' . tooltip ( 'tz-help' )
   . '">' . translate ( 'Timezone Selection' ) . ':</label>'
   . print_timezone_select_html ( 'pref_', $prefarray['TIMEZONE'] ) . '</p>' : '' ) . '
              <p><label title="' . tooltip ( 'date-format-help' ) . '">'
 . translate ( 'Date format' ) . ':</label>
                <select name="pref_DATE_FORMAT">' . $datestyle_ymd . '
                </select>'
 . date_to_str ( $dateYmd, $DATE_FORMAT, false, false ) . '</p>
              <p><label>&nbsp;</label>
                <select name="pref_DATE_FORMAT_MY">' . $datestyle_my . '
                </select>'
 . date_to_str ( $dateYmd, $DATE_FORMAT_MY, false, false ) . '</p>
              <p><label>&nbsp;</label>
                <select name="pref_DATE_FORMAT_MD">' . $datestyle_md . '
                </select>'
 . date_to_str ( $dateYmd, $DATE_FORMAT_MD, false, false ) . '</p>
              <p><label>&nbsp;</label>
                <select name="pref_DATE_FORMAT_TASK">' . $datestyle_tk . '
                </select>'
 . translate ( 'Small Task Date' ) . ' '
 . date_to_str ( $dateYmd, $DATE_FORMAT_TASK, false, false ) . '</p>
              <p><label title="' . tooltip ( 'time-format-help' ) . '">'
 . translate ( 'Time format' ) . ':</label>' . print_radio ( 'TIME_FORMAT',
  array ( '12' => translate ( '12 hour' ), '24' => translate ( '24 hour' ) ) )
 . '</p>
              <p><label title="' . tooltip ( 'display-week-starts-on' ) . '">'
 . translate ( 'Week starts on' ) . ':</label>
                <select name="pref_WEEK_START" id="pref_WEEK_START">'
 . $start_wk_on . '
                </select></p>
              <p><label title="' . tooltip ( 'display-weekend-starts-on' ) . '">'
 . translate ( 'Weekend starts on' ) . ':</label>
                <select name="pref_WEEKEND_START" id="pref_WEEKEND_START">'
 . $start_wkend_on . '
                </select></p>
              <p><label title="' . tooltip ( 'work-hours-help' ) . '">'
 . translate ( 'Work hours' ) . ':</label>' . translate ( 'From' ) . '
                <select name="pref_WORK_DAY_START_HOUR" id="pref_starthr">'
 . $work_hr_start . '
                </select>' . translate ( 'to' ) . '
                <select name="pref_WORK_DAY_END_HOUR" id="pref_endhr">'
 . $work_hr_end . '
                </select></p>
          </fieldset>
          <fieldset>
            <legend>' . translate ( 'Appearance' ) . '</legend>
              <p><label title="' . tooltip ( 'preferred-view-help' ) . '">'
 . translate ( 'Preferred view' ) . ':</label>
                <select name="pref_STARTVIEW">' . $prefer_vu . $user_vu . '
                </select></p>
              <p><label for="pref_font" title="' . tooltip ( 'fonts-help' )
 . '>' . translate ( 'Fonts' )
 . ':</label><input type="text" size="40" name="pref_FONTS" id="pref_font" value="'
 . htmlspecialchars ( $prefarray['FONTS'] ) . '" /></p>
              <p><label title="' . tooltip ( 'display-sm_month-help' ) . '">'
 . translate ( 'Display small months' ) . ':</label>'
 . print_radio ( 'DISPLAY_SM_MONTH' ) . '</p>
              <p><label title="' . tooltip ( 'display-weekends-help' ) . '">'
 . translate ( 'Display weekends' ) . ':</label>'
 . print_radio ( 'DISPLAY_WEEKENDS' ) . '</p>
              <p><label title="' . tooltip ( 'display-long-daynames-help' ) . '">'
 . translate ( 'Display long day names' ) . ':</label>'
 . print_radio ( 'DISPLAY_LONG_DAYS' ) . '</p>
              <p><label title="' . tooltip ( 'display-minutes-help' ) . '">'
 . translate ( 'Display 00 minutes always' ) . ':</label>'
 . print_radio ( 'DISPLAY_MINUTES' ) . '</p>
              <p><label title="' . tooltip ( 'display-end-times-help' ) . '">'
 . translate ( 'Display end times on calendars' ) . ':</label>'
 . print_radio ( 'DISPLAY_END_TIMES' ) . '</p>
              <p><label title="' . tooltip ( 'display-alldays-help' ) . '">'
 . translate ( 'Display all days in month view' ) . ':</label>'
 . print_radio ( 'DISPLAY_ALL_DAYS_IN_MONTH' ) . '</p>
              <p><label title="' . tooltip ( 'display-week-number-help' ) . '">'
 . translate ( 'Display week number' ) . ':</label>'
 . print_radio ( 'DISPLAY_WEEKNUMBER' ) . '</p>
              <p><label title="' . tooltip ( 'display-tasks-help' ) . '">'
 . translate ( 'Display small task list' ) . ':</label>'
 . print_radio ( 'DISPLAY_TASKS' ) . '</p>
              <p><label title="' . tooltip ( 'display-tasks-in-grid-help' ) . '">'
 . translate ( 'Display tasks in Calendars' ) . ':</label>'
 . print_radio ( 'DISPLAY_TASKS_IN_GRID' ) . '</p>
              <p><label title="' . tooltip ( 'lunar-help' ) . '">'
 . translate ( 'Display Lunar Phases in month view' ) . ':</label>'
 . print_radio ( 'DISPLAY_MOON_PHASES' ) . '</p>
          </fieldset>
          <fieldset>
            <legend>' . translate ( 'Events' ) . '</legend>
              <p><label title="' . tooltip ( 'display-unapproved-help' ) . '">'
 . translate ( 'Display unapproved' ) . ':</label>'
 . print_radio ( 'DISPLAY_UNAPPROVED' ) . '</p>
              <p><label title="' . tooltip ( 'timed-evt-len-help' ) . '">'
 . translate ( 'Specify timed event length by' ) . ':</label>'
 . print_radio ( 'TIMED_EVT_LEN', array ( 'D' => translate ( 'Duration' ),
    'E' => translate ( 'End Time' ) ) ) . '</p>' . $catStr . '
              <p><label title="' . tooltip ( 'crossday-help' ) . '">'
 . translate ( 'Disable Cross-Day Events' ) . ':</label>'
 . print_radio ( 'DISABLE_CROSSDAY_EVENTS' ) . '</p>
              <p><label title="' . tooltip ( 'display-desc-print-day-help' )
 . '">' . translate ( 'Display description in printer day view' ) . ':</label>'
 . print_radio ( 'DISPLAY_DESC_PRINT_DAY' ) . '</p>
              <p><label title="' . tooltip ( 'entry-interval-help' ) . '">'
 . translate ( 'Entry interval' ) . ':</label>
                <select name="pref_ENTRY_SLOTS">'
 . $option . '24"' . ( $prefarray['ENTRY_SLOTS'] == '24' ? $selected : '' )
 . '>' . $oneStr . ' ' . $hourStr . '</option>'
 . $option . '48"' . ( $prefarray['ENTRY_SLOTS'] == '48' ? $selected : '' )
 . '>' . $thirtyStr
 . $option . '72"' . ( $prefarray['ENTRY_SLOTS'] == '72' ? $selected : '' )
 . '>' . $twentyStr
 . $option . '96"' . ( $prefarray['ENTRY_SLOTS'] == '96' ? $selected : '' )
 . '>' . $fifteenStr
 . $option . '144"' . ( $prefarray['ENTRY_SLOTS'] == '144' ? $selected : '' )
 . '>' . $tenStr
 . $option . '288"' . ( $prefarray['ENTRY_SLOTS'] == '288' ? $selected : '' )
 . '>' . $fivStr
 . $option . '1440"' . ( $prefarray['ENTRY_SLOTS'] == '1440' ? $selected : '' )
 . '>' . $oneStr . ' ' . translate ( 'minute' ) . '</option>
                </select></p>
              <p><label title="' . tooltip ( 'time-interval-help' ) . '">'
 . translate ( 'Time interval' ) . ':</label>
                <select name="pref_TIME_SLOTS">'
 . $option . '24"' . ( $prefarray['TIME_SLOTS'] == '24' ? $selected : '' )
 . '>' . $oneStr . ' ' . $hourStr . '</option>'
 . $option . '48"' . ( $prefarray['TIME_SLOTS'] == '48' ? $selected : '' )
 . '>' . $thirtyStr
 . $option . '72"' . ( $prefarray['TIME_SLOTS'] == '72' ? $selected : '' )
 . '>' . $twentyStr
 . $option . '96"' . ( $prefarray['TIME_SLOTS'] == '96' ? $selected : '' )
 . '>' . $fifteenStr
 . $option . '144"' . ( $prefarray['TIME_SLOTS'] == '144' ? $selected : '' )
 . '>' . $tenStr . '
                </select></p>
          </fieldset>
          <fieldset>
            <legend>' . translate ( 'Miscellaneous' ) . '</legend>
              <p><label title="' . tooltip ( 'auto-refresh-help' ) . '">'
 . translate ( 'Auto-refresh calendars' ) . ':</label>'
 . print_radio ( 'AUTO_REFRESH' ) . '</p>
              <p><label title="' . tooltip ( 'auto-refresh-time-help' ) . '">'
 . translate ( 'Auto-refresh time' )
 . ':</label><input type="text" name="pref_AUTO_REFRESH_TIME" size="4" value="'
 . ( empty ( $prefarray['AUTO_REFRESH_TIME'] ) ? 0 : $prefarray['AUTO_REFRESH_TIME'] )
 . '" />' . translate ( 'minutes' ) . '</p>
          </fieldset>
        </div>
<!-- END SETTINGS -->';

if ( $ALLOW_USER_THEMES == 'Y' || $is_admin ) {
  echo '
        <div id="tabscontent_themes">
          <p title="' . tooltip ( 'theme-reload-help' ) . '">'
   . translate ( 'Page may need to be reloaded for new Theme to take effect' )
   . '</p>
          <p><label for="pref_THEME" title="' . tooltip ( 'themes-help' )
   . '">' . translate ( 'Themes' ) . ':</label>
            <select name="pref_THEME" id="pref_THEME">'
  // Always use 'none' as default so we don't overwrite manual settings.
  . $option . 'none" disabled="disabled"' . $selected . '>'
   . translate ( 'AVAILABLE THEMES' ) . '</option>';

  foreach ( $themes as $theme ) {
    echo $option . $theme . '">' . $theme . '</option>';
  }

  echo '
            </select><input type="button" name="preview" value="'
   . translate ( 'Preview' ) . '" onclick="return showPreview ()" /></p>';

  if ( $MENU_ENABLED == 'Y' ) {
    echo '
          <p><label for="pref_MENU_THEME" title="' . tooltip ( 'menu-themes-help' )
     . '">' . translate ( 'Menu theme' ) . ':</label>
            <select name="pref_MENU_THEME" id="pref_MENU_THEME">' . $option
     . 'default" ' . ( $prefarray['MENU_THEME'] == 'default' ? $selected : '' )
     . '>default</option>';

    foreach ( $menuthemes as $menutheme ) {
      echo $option . $menutheme . '"'
       . ( $prefarray['MENU_THEME'] == $menutheme ? $selected : '' ) . '>'
       . $menutheme . '</option>';
    }

    echo '
            </select></p>';
  }
  echo '
        </div>
<!-- END THEMES -->';
}
$publish_access = ( empty ( $prefarray['USER_REMOTE_ACCESS'] )
  ? 0 : $prefarray['USER_REMOTE_ACCESS'] );

echo ( $updating_public ? '' : ( $SEND_EMAIL != 'Y' ? '' : '
        <div id="tabscontent_email">
          <p><label>' . translate ( 'Email format preference' ) . ':</label>'
     . print_radio ( 'EMAIL_HTML', array ( 'Y' => translate ( 'HTML' ),
        'N' => translate ( 'Plain Text' ) ) ) . '</p>
          <p><label>' . translate ( 'Event reminders' ) . ':</label>'
     . print_radio ( 'EMAIL_REMINDER' ) . '</p>
          <p><label>' . translate ( 'Events added to my calendar' ) . ':</label>'
     . print_radio ( 'EMAIL_EVENT_ADDED' ) . '</p>
          <p><label>' . translate ( 'Events updated on my calendar' ) . ':</label>'
     . print_radio ( 'EMAIL_EVENT_UPDATED' ) . '</p>
          <p><label>' . translate ( 'Events removed from my calendar' )
     . ':</label>' . print_radio ( 'EMAIL_EVENT_DELETED' ) . '</p>
          <p><label>' . translate ( 'Event rejected by participant' )
     . ':</label>' . print_radio ( 'EMAIL_EVENT_REJECTED' ) . '</p>
          <p><label>' . translate ( 'Event that I create' ) . ':</label>'
     . print_radio ( 'EMAIL_EVENT_CREATE' ) . '</p>
        </div>
<!-- END EMAIL -->' ) . '

        <div id="tabscontent_boss">' . ( $SEND_EMAIL == 'Y' ? '
          <p><label>' . translate ( 'Email me event notification' ) . ':</label>'
     . print_radio ( 'EMAIL_ASSISTANT_EVENTS' ) . '</p>' : '' ) . '
          <p><label>' . translate ( 'I want to approve events' ) . ':</label>'
   . print_radio ( 'APPROVE_ASSISTANT_EVENT' ) . '</p>
          <p><label title="' . tooltip ( 'display_byproxy-help' ) . '">'
   . translate ( 'Display if created by Assistant' ) . ':</label>'
   . print_radio ( 'DISPLAY_CREATED_BYPROXY' ) . '</p>
        </div>
<!-- END BOSS -->' ) . '
        <div id="tabscontent_subscribe">'
 . ( $PUBLISH_ENABLED == 'Y' || $RSS_ENABLED == 'Y' ? '
          <p><label title="'
   . tooltip ( 'allow-view-subscriptions-help' ) . '">'
   . translate ( 'Allow remote viewing of' ) . ':</label>
            <select name="pref_USER_REMOTE_ACCESS">'
   . $option . '0"' . ( $publish_access == '0' ? $selected : '' ) . ' >'
   . $publicStr . $entryStr
   . $option . '1"' . ( $publish_access == '1' ? $selected : '' ) . ' >'
   . $publicStr . ' &amp; ' . translate ( 'Confidential' ) . $entryStr
   . $option . '2"' . ( $publish_access == '2' ? $selected : '' ) . ' >'
   . translate ( 'All' ) . $entryStr . '
            </select></p>' : '' ) . ( $PUBLISH_ENABLED != 'Y' ? '' : '
          <p><label title="' . tooltip ( 'allow-remote-subscriptions-help' )
   . '">' . translate ( 'Allow remote subscriptions' ) . ':</label>'
   . print_radio ( 'USER_PUBLISH_ENABLED' )
   . '<span id="rem_subscribe">' . ( empty ( $SERVER_URL ) ? '' : '<br />' . $urlStr
     . ': <a title="' . tooltip ( 'remote-subscriptions-url-help' )
     . '" href="' . htmlspecialchars ( $SERVER_URL ) . 'publish.php/'
     . ( $updating_public ? 'public' : $user ) . '.ics">'
     . htmlspecialchars ( $SERVER_URL ) . 'publish.php?user='
     . ( $updating_public ? 'public' : $user ) . '</a>' ) . '</span></p>' . '
          <p><label title="' . tooltip ( 'allow-remote-publishing-help' ) . '">'
   . translate ( 'Allow remote publishing' ) . ':</label>'
   . print_radio ( 'USER_PUBLISH_RW_ENABLED' ).'<span id="rem_publish"'
   . ( empty ( $SERVER_URL ) ? '' : '<br />' . $urlStr
     . ': <a title="' . tooltip ( 'remote-publishing-url-help' )
     . '" href="' . htmlspecialchars ( $SERVER_URL )
     . 'icalclient.php">' . htmlspecialchars ( $SERVER_URL )
     . 'icalclient.php</a>' ) . '</span></p>' ) . ( $RSS_ENABLED != 'Y' ? '' : '
          <p><label title="' . tooltip ( 'rss-enabled-help' ) . '">'
   . translate ( 'Enable RSS feed' ) . ':</label>'
   . print_radio ( 'USER_RSS_ENABLED' ) . '<span id="rss_able"'
   .( empty ( $SERVER_URL ) ? '' : '<br />'
     . $urlStr . ': <a title="' . tooltip ( 'rss-feed-url-help' )
     . '" href="'.htmlspecialchars ( $SERVER_URL ) . 'rss.php?user='
     . ( $updating_public ? 'public' : $user ) . '">'
     . htmlspecialchars ( $SERVER_URL ) . 'rss.php?user='
     . ( $updating_public ? 'public' : $user ) . '</a>' ) . '</span></p>' ) . '
          <p><label title="' . tooltip ( 'freebusy-enabled-help' ) . '">'
 . translate ( 'Enable FreeBusy publishing' ) . ':</label>'
 . print_radio ( 'FREEBUSY_ENABLED' ) . '<span id="free_busy"'
 .( empty ( $SERVER_URL ) ? '' : '<br />'
   . $urlStr . ': <a title="' . tooltip ( 'freebusy-url-help' )
   . '" href="' . htmlspecialchars ( $SERVER_URL ) . 'freebusy.php/'
   . ( $updating_public ? 'public' : $user ) . '.ifb">'
   . htmlspecialchars ( $SERVER_URL ) . 'freebusy.php?user='
   . ( $updating_public ? 'public' : $user ) . '</a>' ) . '</span></p>' . '
        </div>
<!-- END SUBSCRIBE -->';

if ( $ALLOW_USER_HEADER == 'Y' ) {
  echo '
        <div id="tabscontent_header">';

  if ( $CUSTOM_SCRIPT == 'Y' ) {
    echo '
          <p><label title="' . tooltip ( 'custom-script-help' ) . '">'
     . translate ( 'Custom script/stylesheet' ) . ':</label>';
    printf ( $editStr, 'S' );
  }

  if ( $CUSTOM_HEADER == 'Y' ) {
    echo '
          <p><label title="' . tooltip ( 'custom-header-help' ) . '">'
     . translate ( 'Custom header' ) . ':</label>';
    printf ( $editStr, 'H' );
  }

  if ( $CUSTOM_TRAILER == 'Y' ) {
    echo '
          <p><label title="' . tooltip ( 'custom-trailer-help' ) . '">'
     . translate ( 'Custom trailer' ) . ':</label>';
    printf ( $editStr, 'T' );
  }
  echo '
        </div>
<!-- END HEADER -->';
} // if $ALLOW_USER_HEADER
if ( $ALLOW_COLOR_CUSTOMIZATION == 'Y' ) {
  set_today ( $dateYmd );

  echo '

<!-- BEGIN COLORS -->
        <div id="tabscontent_colors">' . $example_month . $color_sets . '
        </div>
<!-- END COLORS -->';
} // if $ALLOW_COLOR_CUSTOMIZATION
ob_end_flush ();

echo '
      </div>
<!-- END TABS -->
      <div id="saver">' . $saveStr . '
      </div>
    </form>' . print_trailer ();

?>
