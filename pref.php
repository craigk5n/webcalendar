<?php // $Id: pref.php,v 1.168.2.2 2013/01/24 21:15:09 cknudsen Exp $
include_once 'includes/init.php';
require_valid_referring_url ();

// Force the CSS cache to clear by incrementing webcalendar_csscache cookie.
$webcalendar_csscache = 1;
if  ( isset ( $_COOKIE['webcalendar_csscache'] ) ) {
  $webcalendar_csscache += $_COOKIE['webcalendar_csscache'];
}
SetCookie ( 'webcalendar_csscache', $webcalendar_csscache );

function save_pref( $prefs, $src) {
  global $my_theme, $prefuser;
  while ( list ( $key, $value ) = each ( $prefs ) ) {
    if ( $src == 'post' ) {
      $setting = substr ( $key, 5 );
      $prefix = substr ( $key, 0, 5 );
      if ( $prefix != 'pref_')
        continue;
      // Validate key name. Should start with "pref_" and not include
      // any unusual characters that might be an SQL injection attack.
      if ( ! preg_match ( '/pref_[A-Za-z0-9_]+$/', $key ) )
        die_miserable_death ( str_replace ( 'XXX', $key,
            translate ( 'Invalid setting name XXX.' ) ) );

    } else {
      $setting = $key;
      $prefix = 'pref_';
    }
    //echo "Setting = $setting, key = $key, prefix = $prefix<br />\n";
    if ( strlen ( $setting ) > 0 && $prefix == 'pref_' ) {
      if ( $setting == 'THEME' &&  $value != 'none' )
        $my_theme = strtolower ( $value );
      $sql = 'DELETE FROM webcal_user_pref WHERE cal_login = ? ' .
        'AND cal_setting = ?';
      dbi_execute ( $sql, [$prefuser, $setting] );
      if ( strlen ( $value ) > 0 ) {
      $setting = strtoupper ( $setting );
        $sql = 'INSERT INTO webcal_user_pref ' .
          '( cal_login, cal_setting, cal_value ) VALUES ' .
          '( ?, ?, ? )';
        if ( ! dbi_execute ( $sql, [$prefuser, $setting, $value] ) ) {
          $error = 'Unable to update preference: ' . dbi_error() .
   '<br /><br /><span class="bold colon">SQL</span>' . $sql;
          break;
        }
      }
    }
  }
}
$currenttab = '';
$public = getGetValue ('public');
$user = getGetValue ('user');
$updating_public = false;
  load_global_settings();

if ( $is_admin && ! empty ( $public ) && $PUBLIC_ACCESS == 'Y' ) {
  $updating_public = true;
  load_user_preferences ( '__public__' );
  $prefuser = '__public__';
} elseif ( ! empty ( $user ) && $user != $login && ($is_admin || $is_nonuser_admin)) {
  $prefuser = $user;
    load_user_preferences ( $user );
} else {
  $prefuser = $login;
  // Reload preferences so any css changes will take effect
  load_user_preferences();
}

//get list of theme files from /themes directory
$themes = [];
$dir = 'themes/';
if (is_dir ($dir)) {
   if ($dh = opendir ($dir)) {
       while (($file = readdir ($dh)) !== false) {
         if ( strpos ( $file, '_pref.php' ) )
           $themes[] = str_replace ( '_pref.php', '', $file );
       }
       sort ( $themes );
       closedir ($dh);
   }
}

// Check for malicious 'pref_THEME' passed in (LFI vulnerability)
if ( ! empty ( $_POST ) && empty ( $error ) ) {
  $t = $_POST['pref_THEME'];
  if ( ! empty ( $t ) ) {
    $valid = false;
    foreach ( $themes as $theme ) {
      if ( $theme == $t )
        $valid = true;
    }
    if ( ! $valid )
      $error = translate('Invalid theme');
  }
}

if ( ! empty ( $_POST ) && empty ( $error )) {
  $my_theme = '';
  $currenttab = getPostValue ( 'currenttab' );
  save_pref ( $_POST, 'post' );

  if ( ! empty ( $my_theme ) ) {
    $theme = 'themes/'. $my_theme . '_pref.php';
    include_once $theme;
    save_pref ( $webcal_theme, 'theme' );
  }
  // Reload preferences
  load_user_preferences();
}


if ($user != $login)
  $user = (($is_admin || $is_nonuser_admin) && $user) ? $user : $login;

// Load categories only if editing our own calendar
//if (!$user || $user == $login) load_user_categories();
load_user_categories();
// Reload preferences into $prefarray[].
// Get system settings first.
$prefarray = [];
$res = dbi_execute ( 'SELECT cal_setting, cal_value FROM webcal_config ' );
if ( $res ) {
  while ( $row = dbi_fetch_row ( $res ) ) {
    $prefarray[$row[0]] = $row[1];
  }
  dbi_free_result ( $res );
}
//get user settings
$res = dbi_execute ( 'SELECT cal_setting, cal_value FROM webcal_user_pref
  WHERE cal_login = ?', [$prefuser] );
if ( $res ) {
  while ( $row = dbi_fetch_row ( $res ) ) {
    $prefarray[$row[0]] = $row[1];
  }
  dbi_free_result ( $res );
}

//this will force $LANGUAGE to to the current value and eliminate having
//to double click the 'SAVE' buton
$translation_loaded = false;

//move this include here to allow proper translation
include 'includes/date_formats.php';

//get list of menu themes
$menuthemes = [];
$dir = 'includes/menu/themes/';
if ( is_dir ( $dir ) ) {
   if ( $dh = opendir ( $dir ) ) {
       while ( ( $file = readdir ( $dh ) ) !== false ) {
         if ( $file == '.' || $file == '..' || $file == 'CVS' ||
           $file == 'default') continue;
         if ( is_dir ( $dir.$file ) ) $menuthemes[] = $file;
       }
       closedir ($dh);
   }
}

// Make sure global values passed to styles.php are for this user.
// Makes the demo calendar accurate.
$GLOBALS['BGCOLOR'] = $prefarray['BGCOLOR'];
$GLOBALS['H2COLOR'] = $prefarray['H2COLOR'];
$GLOBALS['MENU_THEME'] = $prefarray['MENU_THEME'];
$GLOBALS['TODAYCELLBG'] = $prefarray['TODAYCELLBG'];
$GLOBALS['TABLEBG'] = $prefarray['TABLEBG'];
$GLOBALS['TABLEBG'] = $prefarray['TABLEBG'];
$GLOBALS['THBG'] = $prefarray['THBG'];
$GLOBALS['CELLBG'] = $prefarray['CELLBG'];
$GLOBALS['WEEKENDBG'] = $prefarray['WEEKENDBG'];
$GLOBALS['OTHERMONTHBG'] = $prefarray['OTHERMONTHBG'];
$GLOBALS['FONTS'] = $prefarray['FONTS'];
$GLOBALS['MYEVENTS'] = $prefarray['MYEVENTS'];

//determine if we can set timezones, if not don't display any options
$can_set_timezone = set_env ( 'TZ', $prefarray['TIMEZONE'] );
$dateYmd = date ( 'Ymd' );
$selected = ' selected="selected" ';

$minutesStr = translate ( 'minutes' );

//allow css_cache to display public or NUC values
@session_start();
$_SESSION['webcal_tmp_login'] = $prefuser;
//Prh ... add user to edit_template to get/set correct template
$openStr ="\"window.open( 'edit_template.php?type=%s&user=%s','cal_template','dependent,menubar,scrollbars,height=500,width=500,outerHeight=520,outerWidth=520' );\"";

$currenttab = getPostValue ( 'currenttab', 'settings' );
$currenttab = ( ! empty ( $currenttab) ? $currenttab : 'settings' );

$BodyX = 'onload="altrows();showTab( \'' . $currenttab . '\' );"';
$INC = array ('js/visible.php', 'js/pref.php');
print_header($INC, '', $BodyX);
?>

<h2><?php
 if ( $updating_public )
  echo translate ($PUBLIC_ACCESS_FULLNAME) . '&nbsp;';
 etranslate ( 'Preferences' );
 if ( $is_nonuser_admin || ( $is_admin && substr ( $prefuser, 0, 5 ) == '_NUC_' ) ) {
  nonuser_load_variables ( $user, 'nonuser' );
  echo '<br /><strong>-- ' .
   translate ( 'Admin mode' ) . ': '.$nonuserfullname." --</strong>\n";
 }
$qryStr = ( ! empty ( $_SERVER['QUERY_STRING'] ) ? '?' . $_SERVER['QUERY_STRING'] : '' );
$formaction = substr ($self, strrpos($self, '/') + 1) . $qryStr;

?>&nbsp;<img src="images/help.gif" alt="<?php etranslate ( 'Help' )?>" class="help" onclick="window.open( 'help_pref.php', 'cal_help', 'dependent,menubar,scrollbars,height=400,width=400,innerHeight=420,outerWidth=420' );" /></h2>


<form action="<?php echo htmlspecialchars($formaction) ?>" method="post" onsubmit="return valid_form( this );" name="prefform">
<input type="hidden" name="currenttab" id="currenttab" value="<?php echo $currenttab ?>" />
<?php
 if ($user)
  echo "<input type=\"hidden\" name=\"user\" value=\"$user\" />\n";

echo display_admin_link();
?>
<input type="submit" value="<?php etranslate ( 'Save Preferences' )?>" name="" />
&nbsp;&nbsp;&nbsp;
<?php if ( $updating_public ) { ?>
 <input type="hidden" name="public" value="1" />
<?php } /*if ( $updating_public )*/


// If user is admin of a non-user cal, and non-user cal is "public"
// (meaning it is a public calendar that requires no login), then allow
// the current user to modify prefs for that nonuser cal
if ( $is_admin && ! $updating_public ) {
  if ( empty ( $public ) && ! empty ( $PUBLIC_ACCESS ) &&
    $PUBLIC_ACCESS == 'Y' ) {
      $public_option = '<option value="pref.php?public=1">'
        . translate( 'Public Access calendar' ) . "</option>\n";
  }
}

if ( $NONUSER_ENABLED == 'Y' || $PUBLIC_ACCESS == 'Y' ) {
  if ( ( empty ( $user ) || $user == $login ) && ! $updating_public ) {
    $nulist = get_my_nonusers ( $login );
    echo '<select onchange="location=this.options[this.selectedIndex].value;">' ."\n";
    echo "<option $selected disabled=\"disabled\" value=\"\">" .
      translate ( 'Modify Non User Calendar Preferences') . "</option>\n";
    if ( ! empty ( $public_option ) ) echo $public_option . "\n";
    for ( $i = 0, $cnt = count ( $nulist ); $i < $cnt; $i++ ) {
      echo '<option value="pref.php?user='. $nulist[$i]['cal_login']. '">' .
        $nulist[$i]['cal_fullname'] . "</option>\n";
    }
    echo "</select>\n";
  } else {
    $linktext = translate ( 'Return to My Preferences' );
    echo "<a title=\"$linktext\" class=\"nav\" href=\"pref.php\">&laquo;&nbsp; $linktext </a>";
  }
}
?>

<br /><br />

<!-- TABS -->
<div id="tabs">
 <span class="tabfor" id="tab_settings"><a href="" onclick="return setTab( 'settings' );"><?php etranslate ( 'Settings' )?></a></span>
 <?php if ( $ALLOW_USER_THEMES == 'Y' || $is_admin ) { ?>
 <span class="tabbak" id="tab_themes"><a href="" onclick="return setTab( 'themes' );"><?php etranslate ( 'Themes' )?></a></span>
<?php }
 if ( $SEND_EMAIL == 'Y' ) { ?>
 <span class="tabbak" id="tab_email"><a href="" onclick="return setTab( 'email' );"><?php etranslate ( 'Email' )?></a></span>
<?php } ?>
 <span class="tabbak" id="tab_boss"><a href="" onclick="return setTab( 'boss' );"><?php etranslate ( 'When I am the boss' )?></a></span>
<?php if ( $PUBLISH_ENABLED == 'Y'  || $RSS_ENABLED == 'Y' ) { ?>
 <span class="tabbak" id="tab_subscribe"><a href="" onclick="return setTab( 'subscribe' );"><?php etranslate ( 'Subscribe/Publish' )?></a></span>
<?php }
if ( $ALLOW_USER_HEADER == 'Y' && ( $CUSTOM_SCRIPT == 'Y' || $CUSTOM_HEADER == 'Y' ||
   $CUSTOM_TRAILER == 'Y' ) ) { ?>
 <span class="tabbak" id="tab_header"><a href="" onclick="return setTab( 'header' );"><?php etranslate ( 'Custom Scripts' )?></a></span>
<?php }
if ( $ALLOW_COLOR_CUSTOMIZATION == 'Y' ) { ?>
 <span class="tabbak" id="tab_colors" title="<?php etooltip ( 'colors-help' )?>"><a href="" onclick="return setTab( 'colors' );"><?php etranslate ( 'Colors' )?></a></span>
<?php } ?>
</div>

<!-- TABS BODY -->
<div id="tabscontent" style="width: 98%;">
 <!-- DETAILS -->
<div id="tabscontent_settings">
<fieldset>
 <legend><?php etranslate ('Language')?></legend>
<table cellspacing="1" cellpadding="2">
<tr><td class="tooltipselect" title="<?php etooltip ("language-help");?>">
 <label for="pref_lang" class="colon"><?php etranslate ( 'Language' )?></label></td><td>
 <select name="pref_LANGUAGE" id="pref_lang">
<?php
 define_languages(); //load the language list
 reset ( $languages );
 while ( list ( $key, $val ) = each ( $languages ) ) {
   // Don't allow users to select browser-defined. We want them to pick
   // a language so that when we send reminders (done without the benefit
   // of a browser-preferred language), we'll know which language to use.
   // DO let them select browser-defined for the public user or NUC.
   if ( $key != 'Browser-defined' || $updating_public || $is_admin ||
              $is_nonuser_admin ) {
     echo '<option value="' . $val . '"';
     if ( $val == $prefarray['LANGUAGE'] ) echo $selected;
     echo '>' . $key . "</option>\n";
   }
 }
?>
 </select>
 <br />
<?php echo str_replace( 'XXX', translate( get_browser_language( true ) ),
    translate( 'Your browser default language is XXX.' ) ); ?>
</td></tr>
</table>
</fieldset>
<fieldset>
 <legend><?php etranslate ('Date and Time')?></legend>
<table cellspacing="1" cellpadding="2">
<?php if ( $can_set_timezone == true ) { ?>
<tr><td class="tooltipselect" title="<?php etooltip ( 'tz-help' )?>">
  <label for="pref_TIMEZONE" class="colon"><?php etranslate ( 'Timezone Selection' )?></label></td><td>
  <?php
   if ( empty ( $prefarray['TIMEZONE'] ) ) $prefarray['TIMEZONE'] = $SERVER_TIMEZONE;
   echo print_timezone_select_html ( 'pref_', $prefarray['TIMEZONE']);
  ?>
</td></tr>
 <?php } //end $can_set_timezone ?>
<tr><td class="tooltipselect colon" title="<?php etooltip ( 'date-format-help' );?>">
 <?php etranslate ( 'Date format' )?></td><td>
 <select name="pref_DATE_FORMAT">
  <?php
  for ( $i = 0, $cnt = count ( $datestyles ); $i < $cnt; $i += 2 ) {
    echo '<option value="' . $datestyles[$i] . '"';
    if ( $prefarray['DATE_FORMAT'] == $datestyles[$i] )
      echo $selected;
    echo '>' . $datestyles[$i + 1] . "</option>\n";
  }
  ?>
</select>&nbsp;<?php echo date_to_str ( $dateYmd,
    $DATE_FORMAT, false, false );?>
<br />
<select name="pref_DATE_FORMAT_MY">
<?php
  for ( $i = 0, $cnt = count ( $datestyles_my ); $i < $cnt; $i += 2 ) {
    echo '<option value="' . $datestyles_my[$i] . '"';
    if ( $prefarray['DATE_FORMAT_MY'] == $datestyles_my[$i] )
      echo $selected;
    echo '>' . $datestyles_my[$i + 1] . "</option>\n";
  }
?>
</select>&nbsp;<?php echo date_to_str ( $dateYmd,
    $DATE_FORMAT_MY, false, false );?>
<br />
<select name="pref_DATE_FORMAT_MD">
<?php
  for ( $i = 0, $cnt = count ( $datestyles_md ); $i < $cnt; $i += 2 ) {
    echo '<option value="' . $datestyles_md[$i] . '"';
    if ( $prefarray['DATE_FORMAT_MD'] == $datestyles_md[$i] )
      echo $selected;
    echo '>' . $datestyles_md[$i + 1] . "</option>\n";
  }
?>
</select>&nbsp;<?php echo date_to_str ( $dateYmd,
    $DATE_FORMAT_MD, false, false );?>
<br />
<select name="pref_DATE_FORMAT_TASK">
<?php
  for ( $i = 0, $cnt = count ( $datestyles_task ); $i < $cnt; $i += 2 ) {
    echo '<option value="' . $datestyles_task[$i] . '"';
    if ( $prefarray['DATE_FORMAT_TASK'] == $datestyles_task[$i] )
      echo $selected;
    echo '>' . $datestyles_task[$i + 1] . "</option>\n";
  }
?>
</select>&nbsp;<?php echo translate ( 'Small Task Date' ) . ' ' .
  date_to_str( $dateYmd, $DATE_FORMAT_TASK, false, false );?>
</td></tr>

<tr><td class="tooltip colon" title="<?php etooltip ( 'time-format-help' )?>">
 <?php etranslate ( 'Time format' )?></td><td>
 <?php echo print_radio ( 'TIME_FORMAT',
    ['12'=>translate ( '12 hour' ), '24'=>translate ( '24 hour' )] ) ?>
</td></tr>
<tr><td class="tooltip colon" title="<?php etooltip ( 'display-week-starts-on' )?>">
 <?php etranslate ( 'Week starts on' )?></td><td>
 <select name="pref_WEEK_START" id="pref_WEEK_START">
<?php
 for ( $i = 0; $i < 7; $i++ ) {
  echo "<option value=\"$i\"" .
   ( $i == $prefarray['WEEK_START'] ? $selected : '' ) .
   '>' . weekday_name ( $i ) . "</option>\n";
 }
?>
 </select>
</td></tr>
<tr><td class="tooltip colon" title="<?php etooltip ( 'display-weekend-starts-on' )?>">
 <?php etranslate ( 'Weekend starts on' )?></td><td>
 <select name="pref_WEEKEND_START" id="pref_WEEKEND_START">
<?php
 for ( $i = -1; $i < 6; $i++ ) {
  $j = ( $i == -1 ? 6 : $i ); //make sure start with Saturday
  echo "<option value=\"$j\"" .
   ( $j == $prefarray['WEEKEND_START'] ? $selected : '' ) .
   '>' . weekday_name ( $j ) . "</option>\n";
 }
?>
 </select>
</td></tr>
<tr><td class="tooltip colon" title="<?php etooltip ( 'work-hours-help' )?>">
 <?php etranslate ( 'Work hours' )?></td><td>
 <label for="pref_starthr"><?php etranslate ( 'From' )?></label>
 <select name="pref_WORK_DAY_START_HOUR" id="pref_starthr">
<?php
  for ( $i = 0; $i < 24; $i++ ) {
    echo "<option value=\"$i\"" .
      ( $i == $prefarray['WORK_DAY_START_HOUR'] ? $selected :'' ) .
      ">" . display_time ( $i * 10000, 1 ) . "</option>\n";
  }
?>
 </select>
 <label for="pref_endhr"><?php etranslate ( 'to' )?></label>
 <select name="pref_WORK_DAY_END_HOUR" id="pref_endhr">
<?php
 for ( $i = 0; $i < 24; $i++ ) {
  echo "<option value=\"$i\"" .
   ( $i == $prefarray['WORK_DAY_END_HOUR'] ? $selected : '' ) .
   ">" . display_time ( $i * 10000, 1 ) . "</option>\n";
 }
?>
 </select>
</td></tr>

</table>
</fieldset>
<fieldset>
 <legend><?php etranslate ('Appearance')?></legend>
<table cellspacing="1" cellpadding="2">
<tr><td class="tooltip colon" title="<?php etooltip ( 'preferred-view-help' );?>"><?php
etranslate ( 'Preferred view' )?></td><td>
<select name="pref_STARTVIEW">
<?php
// For backwards compatibility. We used to store without the .php extension
if ( $prefarray['STARTVIEW'] == 'month' || $prefarray['STARTVIEW'] == 'day' ||
  $prefarray['STARTVIEW'] == 'week' || $prefarray['STARTVIEW'] == 'year' )
  $prefarray['STARTVIEW'] .= '.php';
$choices = $choices_text = [];
if ( access_can_access_function ( ACCESS_DAY, $user ) ) {
  $choices[] = 'day.php';
  $choices_text[] = translate ( 'Day' );
}
if ( access_can_access_function ( ACCESS_WEEK, $user ) ) {
  $choices[] = 'week.php';
  $choices_text[] = translate ( 'Week' );
}
if ( access_can_access_function ( ACCESS_MONTH, $user ) ) {
  $choices[] = 'month.php';
  $choices_text[] = translate ( 'Month' );
}
if ( access_can_access_function ( ACCESS_YEAR, $user ) ) {
  $choices[] = 'year.php';
  $choices_text[] = translate ( 'Year' );
}
// combo.php contains day, week, month and agenda views..
$choices[] = 'combo.php';
$choices_text[] = translate ( 'Multiview' );
for ( $i = 0, $cnt = count ( $choices ); $i < $cnt; $i++ ) {
  echo '<option value="' . $choices[$i] . '" ';
  if ( $prefarray['STARTVIEW'] == $choices[$i] )
    echo $selected;
  echo ' >' . htmlspecialchars ( $choices_text[$i] ) . "</option>\n";
}
// Allow user to select a view also
for ( $i = 0, $cnt = count ( $views ); $i < $cnt; $i++ ) {
  if ( $views[$i]['cal_owner'] != $user && $views[$i]['cal_is_global'] != 'Y' )
    continue;
  $xurl = $views[$i]['url'];
  echo '<option value="';
  echo $xurl . '" ';
  $xurl_strip = str_replace ( '&amp;', '&', $xurl );
  if ( $prefarray['STARTVIEW'] == $xurl_strip )
    echo $selected;
  echo '>' . htmlspecialchars ( $views[$i]['cal_name'] ) . "</option>\n";
}
?>
</select>
</td></tr>

<tr><td class="tooltipselect colon" title="<?php etooltip ( 'fonts-help' )?>">
 <label for="pref_font"><?php etranslate ( 'Fonts')?></label></td><td>
 <input type="text" size="40" name="pref_FONTS" id="pref_font" value="<?php echo htmlspecialchars ( $prefarray['FONTS'] );?>" />
</td></tr>

<tr><td class="tooltip colon" title="<?php etooltip ( 'display-sm_month-help' );?>">
 <?php etranslate ( 'Display small months' )?></td><td>
 <?php echo print_radio ( 'DISPLAY_SM_MONTH' ) ?>
</td></tr>

<tr><td class="tooltip colon" title="<?php etooltip ( 'display-weekends-help' );?>">
 <?php etranslate ( 'Display weekends' )?></td><td>
 <?php echo print_radio ( 'DISPLAY_WEEKENDS' ) ?>
</td></tr>
 <tr><td class="tooltip colon" title="<?php etooltip ( 'display-long-daynames-help' );?>">
  <?php etranslate ( 'Display long day names' )?></td><td>
  <?php echo print_radio ( 'DISPLAY_LONG_DAYS' ) ?>
 </td></tr>
<tr><td class="tooltip colon" title="<?php etooltip ("display-minutes-help")?>">
 <?php etranslate ( 'Display 00 minutes always' )?></td><td>
 <?php echo print_radio ( 'DISPLAY_MINUTES' ) ?>
</td></tr>
<tr><td class="tooltip colon" title="<?php etooltip ("display-end-times-help")?>">
 <?php etranslate ( 'Display end times on calendars' )?></td><td>
 <?php echo print_radio ( 'DISPLAY_END_TIMES' ) ?>
</td></tr>
<tr><td class="tooltip colon" title="<?php etooltip ( 'display-alldays-help' );?>">
  <?php etranslate ( 'Display all days in month view' )?></td><td>
  <?php echo print_radio ( 'DISPLAY_ALL_DAYS_IN_MONTH' ) ?>
 </td></tr>
<tr><td class="tooltip colon" title="<?php etooltip ( 'display-week-number-help' )?>">
 <?php etranslate ( 'Display week number' )?></td><td>
 <?php echo print_radio ( 'DISPLAY_WEEKNUMBER' ) ?>
</td></tr>
<tr><td class="tooltip colon" title="<?php etooltip ( 'display-tasks-help' )?>">
 <?php etranslate ( 'Display small task list' )?></td><td>
 <?php echo print_radio ( 'DISPLAY_TASKS' ) ?>
</td></tr>
<tr><td class="tooltip colon" title="<?php etooltip ( 'display-tasks-in-grid-help' )?>">
 <?php etranslate ( 'Display tasks in Calendars' )?></td><td>
 <?php echo print_radio ( 'DISPLAY_TASKS_IN_GRID' ) ?>
</td></tr>

<tr><td class="tooltip colon" title="<?php etooltip ( 'lunar-help' )?>">
 <?php etranslate ( 'Display Lunar Phases in month view' )?></td><td>
 <?php echo print_radio ( 'DISPLAY_MOON_PHASES' ) ?>
</td></tr>

</table>
</fieldset>
<fieldset>
 <legend><?php etranslate ('Events')?></legend>
<table cellspacing="1" cellpadding="2">

<tr><td class="tooltip colon" title="<?php etooltip ( 'display-unapproved-help' );?>">
 <?php etranslate ( 'Display unapproved' )?></td><td>
 <?php echo print_radio ( 'DISPLAY_UNAPPROVED' ) ?>
</td></tr>

<tr><td class="tooltip colon" title="<?php etooltip ( 'timed-evt-len-help' );?>">
 <?php etranslate ( 'Specify timed event length by' )?></td><td>
 <?php echo print_radio ( 'TIMED_EVT_LEN',
    ['D'=>translate ( 'Duration' ), 'E'=>translate ( 'End Time' )] ) ?>
</td></tr>

<?php if ( ! empty ( $categories ) ) { ?>
<tr><td>
 <label for="pref_cat" class="colon"><?php etranslate ( 'Default Category' )?></label></td><td>
 <select name="pref_CATEGORY_VIEW" id="pref_cat">
<?php
 if ( ! empty ( $categories ) ) {
  foreach ( $categories as $K => $V ) {
   echo "<option value=\"$K\"";
   if ( ! empty ( $prefarray['CATEGORY_VIEW'] ) &&
    $prefarray['CATEGORY_VIEW'] == $K ) echo $selected;
   echo ">{" . htmlentities ( $V['cat_name'] ) . "}</option>\n";
  }
 }
?>
 </select>
</td></tr>
<?php } //end if (! empty ($categories ) ) ?>
<tr><td class="tooltip colon" title="<?php etooltip ( 'crossday-help' )?>">
 <?php etranslate ( 'Disable Cross-Day Events' )?></td><td>
 <?php echo print_radio ( 'DISABLE_CROSSDAY_EVENTS' ) ?>
</td></tr>
<tr><td class="tooltip colon" title="<?php etooltip ( 'display-desc-print-day-help' );?>">
 <?php etranslate ( 'Display description in printer day view' )?></td><td>
 <?php echo print_radio ( 'DISPLAY_DESC_PRINT_DAY' ) ?>
</td></tr>

<tr><td class="tooltip colon" title="<?php etooltip ( 'entry-interval-help' )?>">
 <?php etranslate ( 'Entry interval' )?></td><td>
 <select name="pref_ENTRY_SLOTS">
  <option value="24" <?php if ( $prefarray['ENTRY_SLOTS'] == "24" )
    echo $selected?>>1 <?php etranslate ( 'hour' )?></option>
  <option value="48" <?php if ( $prefarray['ENTRY_SLOTS'] == "48" )
    echo $selected?>>30 <?php echo $minutesStr ?></option>
  <option value="72" <?php if ( $prefarray['ENTRY_SLOTS'] == "72" )
    echo $selected?>>20 <?php echo $minutesStr ?></option>
  <option value="96" <?php if ( $prefarray['ENTRY_SLOTS'] == "96" )
    echo $selected?>>15 <?php echo $minutesStr ?></option>
  <option value="144" <?php if ( $prefarray['ENTRY_SLOTS'] == "144" )
    echo $selected?>>10 <?php echo $minutesStr ?></option>
  <option value="288" <?php if ( $prefarray['ENTRY_SLOTS'] == "288" )
    echo $selected?>>5 <?php echo $minutesStr ?></option>
  <option value="1440" <?php if ( $prefarray['ENTRY_SLOTS'] == "1440" )
    echo $selected?>>1 <?php etranslate ( 'minute' )?></option>
 </select>
</td></tr>
<tr><td class="tooltip colon" title="<?php etooltip ( 'time-interval-help' )?>">
 <?php etranslate ( 'Time interval' )?></td><td>
 <select name="pref_TIME_SLOTS">
  <option value="24" <?php if ( $prefarray['TIME_SLOTS'] == "24" )
  echo $selected?>>1 <?php etranslate ( 'hour' )?></option>
  <option value="48" <?php if ( $prefarray['TIME_SLOTS'] == "48" )
  echo $selected?>>30 <?php echo $minutesStr ?></option>
  <option value="72" <?php if ( $prefarray['TIME_SLOTS'] == "72" )
  echo $selected?>>20 <?php echo $minutesStr ?></option>
  <option value="96" <?php if ( $prefarray['TIME_SLOTS'] == "96" )
  echo $selected?>>15 <?php echo $minutesStr ?></option>
  <option value="144" <?php if ( $prefarray['TIME_SLOTS'] == "144" )
  echo $selected?>>10 <?php echo $minutesStr ?></option>
 </select>
</td></tr>
</table>
</fieldset>
<fieldset>
 <legend><?php etranslate ('Miscellaneous')?></legend>
<table cellspacing="1" cellpadding="2">

<tr><td class="tooltip colon" title="<?php etooltip ( 'auto-refresh-help' );?>">
 <?php etranslate ( 'Auto-refresh calendars' )?></td><td>
 <?php echo print_radio ( 'AUTO_REFRESH' ) ?>
</td></tr>

<tr><td class="tooltip colon" title="<?php etooltip ( 'auto-refresh-time-help' );?>">
 &nbsp;&nbsp;&nbsp;&nbsp;<?php etranslate ( 'Auto-refresh time' )?></td><td>
 <input type="text" name="pref_AUTO_REFRESH_TIME" size="4" value="<?php echo ( empty ( $prefarray['AUTO_REFRESH_TIME'] ) ? 0 : $prefarray['AUTO_REFRESH_TIME'] ); ?>" /> <?php etranslate ( 'minutes' )?>
</td></tr>
</table>
</fieldset>
</div>
<!-- END SETTINGS -->

<?php if ( $ALLOW_USER_THEMES == 'Y' || $is_admin ) { ?>
<div id="tabscontent_themes">
<table cellspacing="1" cellpadding="2" width="35%">
<tr><td class="tooltip"  title="<?php etooltip ( 'theme-reload-help' );?>"colspan="3"><?php
etranslate ( 'Page may need to be reloaded for new Theme to take effect' )?></td></tr>
<tr><td  class="tooltipselect colon" title="<?php etooltip ( 'themes-help' );?>">
 <label for="pref_THEME"><?php etranslate ( 'Themes' )?></label></td><td>
 <select name="pref_THEME" id="pref_THEME">
<?php
  echo "<option value=\"none\" disabled=\"disabled\"  $selected>" .
    translate ( 'AVAILABLE THEMES' ) . "</option>\n";
  //always use 'none' as default so we don't overwrite manual settings
  // echo '<option value="none"' . $selected . translate ( 'None' ) . "</option>\n";
  foreach ( $themes as $theme ) {
   echo '<option value="' . $theme . '">' . $theme . "</option>\n";
  }
?>
 </select></td><td>
 <input type="button" name="preview" value="<?php etranslate ( 'Preview' ) ?>" onclick="return showPreview()" />
</td></tr>
<?php if ( $MENU_ENABLED == 'Y' ) { ?>
 <tr><td  class="tooltip colon" title="<?php etooltip ( 'menu-themes-help' );?>">
 <label for="pref_MENU_THEME"><?php etranslate ( 'Menu theme' )?></label></td><td>
 <select name="pref_MENU_THEME" id="pref_MENU_THEME">
<?php
  echo '<option value="default" ' . ($prefarray['MENU_THEME'] == 'default' ?
    $selected : '' ) . ">default</option>\n";
  foreach ( $menuthemes as $menutheme ) {
     echo '<option value="' . $menutheme . '"';
     if ($prefarray['MENU_THEME'] == $menutheme ) echo $selected;
     echo '>' . $menutheme . "</option>\n";
  }
?>
 </select>
 </td></tr>
<?php } //end Menu enabled test ?>
</table>
</div>
<!-- END THEMES -->
<?php }

if ( ! $updating_public ) {
if ( $SEND_EMAIL == 'Y' ) { ?>
<div id="tabscontent_email">
<table cellspacing="1" cellpadding="2">
<tr><td class="tooltip">
<tr><td class="tooltip colon" title="<?php etooltip('email-format');?>">
 <?php etranslate ( 'Email format preference' )?></td><td>
 <?php echo print_radio ( 'EMAIL_HTML',
    ['Y'=> translate ( 'HTML' ), 'N'=>translate ( 'Plain Text' )] ) ?>
</td></tr>

<tr><td class="tooltip colon" title="<?php etooltip('email-include-ics');?>">
 <?php etranslate ( 'Include iCalendar attachments' )?></td><td>
 <?php echo print_radio ( 'EMAIL_ATTACH_ICS', '', '', 0 ) ?>
</td></tr>

<tr><td class="tooltip colon" title="<?php etooltip('email-event-reminders-help');?>">
 <?php etranslate ( 'Event reminders' )?></td><td>
 <?php echo print_radio ( 'EMAIL_REMINDER' ) ?>
</td></tr>

<tr><td class="tooltip colon" title="<?php etooltip('email-event-added');?>">
 <?php etranslate ( 'Events added to my calendar' )?></td><td>
 <?php echo print_radio ( 'EMAIL_EVENT_ADDED' ) ?>
</td></tr>

<tr><td class="tooltip colon" title="<?php etooltip('email-event-updated');?>">
 <?php etranslate ( 'Events updated on my calendar' )?></td><td>
 <?php echo print_radio ( 'EMAIL_EVENT_UPDATED' ) ?>
</td></tr>

<tr><td class="tooltip colon" title="<?php etooltip('email-event-deleted');?>">
 <?php etranslate ( 'Events removed from my calendar' )?></td><td>
 <?php echo print_radio ( 'EMAIL_EVENT_DELETED' ) ?>
</td></tr>

<tr><td class="tooltip colon" title="<?php etooltip('email-event-rejected');?>">
 <?php etranslate ( 'Event rejected by participant' )?></td><td>
 <?php echo print_radio ( 'EMAIL_EVENT_REJECTED' ) ?>
</td></tr>

<tr><td class="tooltip colon" title="<?php etooltip('email-event-create');?>">
 <?php etranslate ( 'Event that I create' )?></td><td>
 <?php echo print_radio ( 'EMAIL_EVENT_CREATE' ) ?>
</td></tr>
</table>
</div>
<!-- END EMAIL -->
<?php } ?>

<div id="tabscontent_boss">
<table cellspacing="1" cellpadding="2">
<?php if ( $SEND_EMAIL == 'Y' ) { ?>
<tr><td class="tooltip colon"><?php etranslate ( 'Email me event notification' )?></td><td>
 <?php echo print_radio ( 'EMAIL_ASSISTANT_EVENTS' ) ?>
</td></tr>
<?php } //end email ?>
<tr><td class="tooltip colon"><?php etranslate ( 'I want to approve events' )?></td><td>
 <?php echo print_radio ( 'APPROVE_ASSISTANT_EVENT' ) ?>
</td></tr>

<tr><td class="tooltip colon" title="<?php etooltip ( 'display_byproxy-help' )?>"><?php
  etranslate ( 'Display if created by Assistant' )?></td><td>
  <?php echo print_radio ( 'DISPLAY_CREATED_BYPROXY' ) ?>
</td></tr>
</table>
</div>
<!-- END BOSS -->

<?php } /* if ( ! $updating_public ) */ ?>
<div id="tabscontent_subscribe">
<table cellspacing="1" cellpadding="2">
<?php if ( $PUBLISH_ENABLED == 'Y' || $RSS_ENABLED == 'Y') { ?>
<tr><td class="tooltipselect colon" title="<?php etooltip ( 'allow-view-subscriptions-help' )?>"><?php etranslate ( 'Allow remote viewing of' );
$publish_access = ( empty( $prefarray['USER_REMOTE_ACCESS'] )
   ? 0 : $prefarray['USER_REMOTE_ACCESS'] );
?></td><td>
  <select name="pref_USER_REMOTE_ACCESS">
   <option value="0" <?php echo ( $publish_access == '0' ?
     $selected : '' ) . ' >' . translate ( 'Public' ) . ' ' .
     translate ( 'entries' )?></option>
   <option value="1" <?php echo ( $publish_access == '1' ?
     $selected : '' ) . ' >' . translate ( 'Public' ) . ' &amp; ' .
      translate ( 'Confidential' ) . ' ' . translate ( 'entries' )?></option>
   <option value="2" <?php echo ( $publish_access == '2' ?
     $selected : '' ) . ' >' . translate ( 'All' ) . ' ' .
     translate ( 'entries' )?></option>
  </select>
  </td></tr>
<?php }
if ( $PUBLISH_ENABLED == 'Y' ) { ?>
<tr><td class="tooltipselect colon" title="<?php etooltip ( 'allow-remote-subscriptions-help' )?>"><?php etranslate ( 'Allow remote subscriptions' )?></td><td>
  <?php echo print_radio ( 'USER_PUBLISH_ENABLED' ) ?>
</td></tr>
<?php if ( ! empty ( $SERVER_URL ) ) { ?>
<tr><td class="tooltipselect colon" title="<?php etooltip ( 'remote-subscriptions-url-help' )?>">&nbsp;&nbsp;&nbsp;&nbsp;<?php etranslate ( 'URL' )?></td>
  <td>
  <?php
    echo htmlspecialchars ( $SERVER_URL ) .
      'publish.php/' . ( $updating_public ? '__public__' : $user ) . '.ics';
    echo "<br />\n";
    echo htmlspecialchars ( $SERVER_URL ) .
      'publish.php?user=' . ( $updating_public ? '__public__' : $user );
  ?></td></tr>
<?php } /* $SERVER_URL */ ?>

<tr><td class="tooltipselect colon" title="<?php
 etooltip ( 'allow-remote-publishing-help' )?>"><?php
 etranslate ( 'Allow remote publishing' )?></td>
  <td>
  <?php echo print_radio ( 'USER_PUBLISH_RW_ENABLED' ) ?>
</td></tr>
<?php if ( ! empty ( $SERVER_URL ) ) { ?>
<tr><td class="tooltipselect colon" title="<?php etooltip ( 'remote-publishing-url-help' )?>">&nbsp;&nbsp;&nbsp;&nbsp;<?php etranslate ( 'URL' )?></td>
  <td>
  <?php
    echo htmlspecialchars ( $SERVER_URL ) .
      'icalclient.php';
  ?></td></tr>
<?php } /* $SERVER_URL */

} /* $PUBLISH_ENABLED */

if ( $RSS_ENABLED == 'Y' ) { ?>
<tr><td class="tooltipselect colon" title="<?php etooltip ( 'rss-enabled-help' )?>"><?php
  etranslate ( 'Enable RSS feed' )?></td>
  <td>
  <?php echo print_radio ( 'USER_RSS_ENABLED' ) ?>
</td></tr>
<?php if ( ! empty ( $SERVER_URL ) ) { ?>
<tr><td class="tooltipselect colon" title="<?php etooltip ( 'rss-feed-url-help' )?>">&nbsp;&nbsp;&nbsp;&nbsp;<?php etranslate ( 'URL' )?></td>
  <td>
  <?php
    echo htmlspecialchars ( $SERVER_URL ) .
      'rss.php?user=' . ( $updating_public ? '__public__' : $user );
  ?></td></tr>
<?php } /* $SERVER_URL */
} /* $RSS_ENABLED */ ?>

<tr><td class="tooltipselect colon" title="<?php etooltip ( 'freebusy-enabled-help' )?>"><?php etranslate ( 'Enable FreeBusy publishing' )?></td>
  <td>
  <?php echo print_radio ( 'FREEBUSY_ENABLED' ) ?>
</td></tr>
<?php if ( ! empty ( $SERVER_URL ) ) { ?>
<tr><td class="tooltipselect colon" title="<?php etooltip ( 'freebusy-url-help' )?>">&nbsp;&nbsp;&nbsp;&nbsp;<?php etranslate ( 'URL' )?></td>
  <td>
  <?php
    echo htmlspecialchars ( $SERVER_URL ) .
      'freebusy.php/' . ( $updating_public ? '__public__' : $user ) . '.ifb';
    echo "<br />\n";
    echo htmlspecialchars ( $SERVER_URL ) .
      'freebusy.php?user=' . ( $updating_public ? '__public__' : $user );
  ?></td></tr>
<?php } /* $SERVER_URL */ ?>
</table>
</div>
<!-- END SUBSCRIBE -->

<?php if ( $ALLOW_USER_HEADER == 'Y' ) { ?>
<div id="tabscontent_header">
<table cellspacing="1" cellpadding="2">
<?php if ( $CUSTOM_SCRIPT == 'Y' ) { ?>
 <tr><td class="tooltip colon" title="<?php etooltip ( 'custom-script-help' );?>">
  <?php etranslate ( 'Custom script/stylesheet' )?></td><td>
  <input type="button" value="<?php etranslate ( 'Edit' );?>..." onclick=<?php
    printf ( $openStr, 'S',$prefuser ) ?> name="" />
 </td></tr>
<?php }

if ( $CUSTOM_HEADER == 'Y' ) { ?>
 <tr><td class="tooltip colon" title="<?php etooltip ( 'custom-header-help' );?>">
  <?php etranslate ( 'Custom header' )?></td><td>
  <input type="button" value="<?php etranslate ( 'Edit' );?>..." onclick=<?php
    printf ( $openStr, 'H',$prefuser ) ?> name="" />
 </td></tr>
<?php }

if ( $CUSTOM_TRAILER == 'Y' ) { ?>
 <tr><td class="tooltip colon" title="<?php etooltip ( 'custom-trailer-help' );?>">
  <?php etranslate ( 'Custom trailer' )?></td><td>
  <input type="button" value="<?php etranslate ( 'Edit' );?>..." onclick=<?php
    printf ( $openStr, 'T',$prefuser ) ?> name="" />
 </td></tr>
<?php } ?>
</table>
</div>
<!-- END HEADER -->
<?php } // if $ALLOW_USER_HEADER ?>

<!-- BEGIN COLORS -->

<?php if ( $ALLOW_COLOR_CUSTOMIZATION == 'Y' ) { ?>
<div id="tabscontent_colors">
<table>
<tr class="ignore"><td class="aligntop">
<table>
 <tr><td>
  <?php echo print_color_input_html ( 'BGCOLOR',
    translate ( 'Document background' ) ) ?>
 </td></tr>
 <tr><td>
  <?php echo print_color_input_html ( 'H2COLOR',
    translate ( 'Document title' ) ) ?>
 </td></tr>
 <tr><td>
  <?php echo print_color_input_html ( 'TEXTCOLOR',
    translate ( 'Document text' ) ) ?>
</td></tr>
 <tr><td>
  <?php echo print_color_input_html ( 'MYEVENTS',
    translate ( 'My event text' ) ) ?>
 </td></tr>
 <tr><td>
  <?php echo print_color_input_html ( 'TABLEBG',
    translate ( 'Table grid color' ) ) ?>
 </td></tr>
 <tr><td>
  <?php echo print_color_input_html ( 'THBG',
    translate ( 'Table header background' ) ) ?>
 </td></tr>
 <tr><td>
  <?php echo print_color_input_html ( 'THFG',
    translate ( 'Table header text' ) ) ?>
 </td></tr>
 <tr><td>
  <?php echo print_color_input_html ( 'CELLBG',
    translate ( 'Table cell background' ) ) ?>
 </td></tr>
 <tr><td>
  <?php echo print_color_input_html ( 'TODAYCELLBG',
    translate ( 'Table cell background for current day' ) ) ?>
 </td></tr>
 <tr><td>
  <?php echo print_color_input_html ( 'HASEVENTSBG',
    translate ( 'Table cell background for days with events' ) ) ?>
</td></tr>
 <tr><td>
   <?php echo print_color_input_html ( 'WEEKENDBG',
     translate ( 'Table cell background for weekends' ) ) ?>
 </td></tr>
   <tr><td>
  <?php echo print_color_input_html ( 'OTHERMONTHBG',
    translate ( 'Table cell background for other month' ) ) ?>
  </td></tr>
<tr><td>
  <?php echo print_color_input_html ( 'WEEKNUMBER',
    translate ( 'Table cell background for other month' ) ) ?>
</td></tr>
   <tr><td>
     <?php echo print_color_input_html ( 'POPUP_BG',
       translate ( 'Event popup background' ) ) ?>
  </td></tr>
   <tr><td>
    <?php echo print_color_input_html ( 'POPUP_FG',
      translate ( 'Event popup text' ) ) ?>
  </td></tr>
</table>

</td><td class="aligncenter aligntop">
<br />
<!-- BEGIN EXAMPLE MONTH -->
<table style="width:90%; background-color:<?php echo $BGCOLOR?>"><tr>
<td width="1%" rowspan="3">&nbsp;</td>
<td style="text-align:center; color:<?php
  echo $H2COLOR?>; font-weight:bold;"><?php
  echo date_to_str ( $dateYmd, $DATE_FORMAT_MY, false );?></td>
<td width="1%" rowspan="3">&nbsp;</td></tr>
<tr><td bgcolor="<?php echo $BGCOLOR?>">
<?php
set_today( $dateYmd );
echo display_month ( date ( 'm' ), date( 'Y' ), true );
?>
</td></tr>
<tr><td>&nbsp;</td></tr>
</table>
<!-- END EXAMPLE MONTH -->
</td></tr></table>
</div>
<!-- END COLORS -->
<?php } // if $ALLOW_COLOR_CUSTOMIZATION ?>
</div>

<!-- END TABS -->
<br /><br />
<div>
<input type="submit" value="<?php etranslate ( 'Save Preferences' )?>" name="" />
<br /><br />
</div>
</form>

<?php echo print_trailer(); ?>

