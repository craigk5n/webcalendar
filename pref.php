<?php
include_once 'includes/init.php';

// Force the CSS cache to clear by incrementing webcalendar_csscache cookie.
$webcalendar_csscache = 1;
if  ( isset ( $_COOKIE['webcalendar_csscache'] ) ) {
  $webcalendar_csscache += $_COOKIE['webcalendar_csscache'];
}
sendCookie ( 'webcalendar_csscache', $webcalendar_csscache );

function save_pref( $prefs, $src) {
  global $prefuser;
  foreach ($prefs as $key => $value) {
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

$message = '';

// Handle "Reset Preferences" button
$action = getValue('action');
if ($action == "reset" && empty($error)) {
  $user = getValue('user');
  if ($user != $login && ! $is_admin) {
    // Make sure this person is either an admin or the owner/admin of the nonuser cal.
    if (!user_is_nonuser_admin($login, $user)) {
      // This user not authorized.
      $error = translate('Not authorized');
    }
  }
  if(empty($error)) {
    dbi_execute('DELETE FROM webcal_user_pref WHERE cal_login = ?', [$user]);
    $message = translate('Preferences reset to system defaults.');
  }
}

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

if ( ! empty ( $_POST ) && empty ( $error )) {
  save_pref ( $_POST, 'post' );

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

// Make sure global values passed to styles.php are for this user.
// Makes the demo calendar accurate.
$GLOBALS['BGCOLOR'] = $prefarray['BGCOLOR'];
$GLOBALS['H2COLOR'] = $prefarray['H2COLOR'];
$GLOBALS['TODAYCELLBG'] = $prefarray['TODAYCELLBG'];
$GLOBALS['TABLEBG'] = $prefarray['TABLEBG'];
$GLOBALS['TABLEBG'] = $prefarray['TABLEBG'];
$GLOBALS['THBG'] = $prefarray['THBG'];
$GLOBALS['CELLBG'] = $prefarray['CELLBG'];
$GLOBALS['WEEKENDBG'] = $prefarray['WEEKENDBG'];
$GLOBALS['OTHERMONTHBG'] = $prefarray['OTHERMONTHBG'];
$GLOBALS['FONTS'] = $prefarray['FONTS'];
$GLOBALS['MYEVENTS'] = $prefarray['MYEVENTS'];

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
$color_sets = '';
foreach ($colors as $k => $v) {
  $handler = 'color_change_handler_' . $k;
  $color_sets .= print_color_input_html ( $k, $v, '', '', 'p', '', $handler );
}

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

$INC = array ();
print_header($INC, '', '');
?>

<h2><?php
 if ( $updating_public )
  echo translate ($PUBLIC_ACCESS_FULLNAME) . '&nbsp;';
 etranslate ( 'Preferences' );
 if ( $is_nonuser_admin || ( $is_admin && substr ( $prefuser, 0, 5 ) == '_NUC_' ) ) {
  nonuser_load_variables ( $user, 'nonuser' );
  echo ': ' . $nonuserfullname . "\n";
 }
$qryStr = ( ! empty ( $_SERVER['QUERY_STRING'] ) ? '?' . $_SERVER['QUERY_STRING'] : '' );
$formaction = substr ($self, strrpos($self, '/') + 1) . $qryStr;
$formaction = preg_replace('/action=reset/', 'action=save', $formaction);

?>&nbsp;<img src="images/bootstrap-icons/question-circle-fill.svg" alt="<?php etranslate ( 'Help' )?>" class="help" onclick="window.open( 'help_pref.php', 'cal_help', 'dependent,menubar,scrollbars,height=400,width=400,innerHeight=420,outerWidth=420' );" /></h2>

<!-- Message -->
<div id="main-dialog-message" class="alert alert-info" style="<?php echo (empty($message) ? "display: none" : "display: block");?>"">
    <span id="infoMessage"><?php echo $message;?></span>
    <button type="button" class="close" onclick="$('.alert').hide()">&times;</button>
</div>

<form action="<?php echo htmlspecialchars($formaction) ?>" method="post" name="prefform">
<?php
print_form_key();
if ($user)
  echo "<input type=\"hidden\" name=\"user\" value=\"$user\" />\n";

echo display_admin_link();
$resetConfirm = translate('Are you sure you want to reset preferences for XXX?');
$resetConfirm = str_replace("XXX", $user, $resetConfirm);
?>
<div class="form-row">
<input class="btn btn-primary mr-2" type="submit" value="<?php etranslate ( 'Save Preferences' )?>" name="" />
<input type="hidden" name="action" value="save"/>
<a class="btn btn-secondary mr-2" href="pref.php?action=reset&user=<?php echo $user;?>&csrf_form_key=<?php echo getFormKey();?>"
  onclick="return confirm('<?php echo $resetConfirm;?>')"><?php etranslate("Reset Preferences");?></a>

<?php if ( $updating_public ) { ?>
 <input type="hidden" name="public" value="1" />
<?php } /*if ( $updating_public )*/


// If user is admin of a non-user cal, and non-user cal is "public"
// (meaning it is a public calendar that requires no login), then allow
// the current user to modify prefs for that nonuser cal
if ( $is_admin && ! $updating_public ) {
  if ( empty ( $public ) && ! empty ( $PUBLIC_ACCESS ) &&
    $PUBLIC_ACCESS == 'Y' ) {
      $public_option = '<a class="dropdown-item" href="pref.php?public=1">'
        . translate( 'Public Access calendar' ) . "</a>\n";
  }
}

if ( $NONUSER_ENABLED == 'Y' || $PUBLIC_ACCESS == 'Y' ) {
  if ( ( empty ( $user ) || $user == $login ) && ! $updating_public ) {
    $nulist = get_my_nonusers ( $login );
    if (!empty($nulist)) {
    ?>
    <div class="dropdown">
      <button type="button" class="btn btn-secondary dropdown-toggle" data-toggle="dropdown">
        <?php etranslate('Manage Preferences for Resource, Remote and Public Calendars');?>
      </button>
      <div class="dropdown-menu">
        <?php
          if (!empty($public_option)) {
            echo $public_option . "\n";
          }
          for ($i = 0, $cnt = count($nulist); $i < $cnt; $i++) {
            echo '<a class="dropdown-item" href="pref.php?user=' . $nulist[$i]['cal_login'] . '">' .
            $nulist[$i]['cal_fullname'] . "</a>\n";
          }
        ?>
      </div>
    </div>
    <?php
    }
  } else {
    $linktext = translate ( 'Return to My Preferences' );
    echo "<br><a title=\"$linktext\" class=\"btn btn-secondary\" href=\"pref.php\">&laquo;&nbsp; $linktext </a>";
  }
}
?>
</div>

<br /><br />

<!-- TABS -->
<ul class="nav nav-tabs">
<li class="nav-item"><a class="nav-link active" data-toggle="tab" href="#settings"><?php etranslate('Settings');?></a></li>
<?php if ( $SEND_EMAIL == 'Y' ) { ?>
  <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#email"><?php etranslate('Email');?></a></li>
<?php } ?>
<li class="nav-item"><a class="nav-link" data-toggle="tab" href="#boss"><?php etranslate('When I am the boss');?></a></li>
<?php if ( $PUBLISH_ENABLED == 'Y'  || $RSS_ENABLED == 'Y' ) { ?>
  <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#subscribe"><?php etranslate('Subscribe/Publish');?></a></li>
<?php } ?>
<?php if ( $ALLOW_USER_HEADER == 'Y' && ( $CUSTOM_SCRIPT == 'Y' || $CUSTOM_HEADER == 'Y' ||
   $CUSTOM_TRAILER == 'Y' ) ) { ?>
  <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#header"><?php etranslate('Custom Scripts');?></a></li>
<?php } ?>
<?php if ( $ALLOW_COLOR_CUSTOMIZATION == 'Y' ) { ?>
  <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#colors"><?php etranslate('Colors');?></a></li>
<?php } ?>

</ul>

<!-- TABS BODY -->
<div class="tab-content mb-12">

 <!-- DETAILS -->
 <div class="tab-pane container active" id="settings"><div class="form-group">
<fieldset class="border p-2">
 <legend><?php etranslate ('Language')?></legend>
<table cellspacing="1" cellpadding="2">
<tr><td data-toggle="tooltip" data-placement="top" title="<?php etooltip ("language-help");?>" valign="top">
 <label for="pref_lang"><?php etranslate ('Language')?>:</label></td><td>
 <select class="form-control" name="pref_LANGUAGE" id="pref_lang">
<?php
 define_languages(); //load the language list
 reset ( $languages );
 foreach ($languages as $key => $val) {
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
<fieldset class="border p-2">
 <legend><?php etranslate ('Date and Time')?></legend>
<table cellspacing="1" cellpadding="2">
<?php if ( $can_set_timezone == true ) { ?>
<tr><td data-toggle="tooltip" data-placement="top" title="<?php etooltip ("tz-help");?>" valign="top">
  <label for="pref_TIMEZONE" class="colon"><?php etranslate ('Timezone Selection')?>:</label></td><td>
  <?php
   if ( empty ( $prefarray['TIMEZONE'] ) ) $prefarray['TIMEZONE'] = $SERVER_TIMEZONE;
   echo print_timezone_select_html ( 'pref_', $prefarray['TIMEZONE']);
  ?>
</td></tr>
<tr><td height="0.5 em"><!-- small vertical spacing--><span style="font-size: 25%">&nbsp;</span> </td></tr>

 <?php } //end $can_set_timezone ?>

<tr><td data-toggle="tooltip" data-placement="top" title="<?php etooltip ("date-format-help");?>" valign="top">
 <label for="pref_DATE_FORMAT"><?php etranslate ('Date format')?>:</label></td><td>
 <select class="form-control" name="pref_DATE_FORMAT">
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
<select class="form-control" name="pref_DATE_FORMAT_MY">
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
<select class="form-control" name="pref_DATE_FORMAT_MD">
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
<select class="form-control" name="pref_DATE_FORMAT_TASK">
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

<tr><td height="0.5 em"><!-- small vertical spacing--><span style="font-size: 25%">&nbsp;</span> </td></tr>

<tr><td data-toggle="tooltip" data-placement="top" title="<?php etooltip ("time-format-help");?>">
 <label for="pref_TIME_FORMAT"><?php etranslate ('Time format')?>:</label></td><td class="form-inline mt-1 mb-2">
 <?php echo print_radio ( 'TIME_FORMAT',
    ['12'=>translate ( '12 hour' ), '24'=>translate ( '24 hour' )] ) ?>
</td></tr>
<tr><td data-toggle="tooltip" data-placement="top" title="<?php etooltip ("display-week-starts-on");?>">
 <label for="pref_WEEK_START"><?php etranslate ('Week starts on')?>:</label></td><td>
 <select class="form-control" name="pref_WEEK_START" id="pref_WEEK_START">
<?php
 for ( $i = 0; $i < 7; $i++ ) {
  echo "<option value=\"$i\"" .
   ( $i == $prefarray['WEEK_START'] ? $selected : '' ) .
   '>' . weekday_name ( $i ) . "</option>\n";
 }
?>
 </select>
</td></tr>
<tr><td data-toggle="tooltip" data-placement="top" title="<?php etooltip ("display-weekend-starts-on");?>">
 <label for="pref_WEEKEND_START"><?php etranslate ('Weekend starts on')?>:</label></td><td>
 <select class="form-control" name="pref_WEEKEND_START" id="pref_WEEKEND_START">
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
<tr><td data-toggle="tooltip" data-placement="top" title="<?php etooltip ("work-hours-help");?>">
 <label for="pref_starthr"><?php etranslate ('Work hours')?>:</label></td><td class="form-inline mt-1 mb-2">
 <?php etranslate ('From')?>
 <select class="form-control" name="pref_WORK_DAY_START_HOUR" id="pref_starthr">
<?php
  for ( $i = 0; $i < 24; $i++ ) {
    echo "<option value=\"$i\"" .
      ( $i == $prefarray['WORK_DAY_START_HOUR'] ? $selected :'' ) .
      ">" . display_time ( $i * 10000, 1 ) . "</option>\n";
  }
?>
 </select>
 <?php etranslate ('to')?>
 <select class="form-control" name="pref_WORK_DAY_END_HOUR" id="pref_endhr">
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
<fieldset class="border p-2">
 <legend><?php etranslate ('Appearance')?></legend>
<table cellspacing="1" cellpadding="2">
<tr><td data-toggle="tooltip" data-placement="top" title="<?php etooltip ("preferred-view-help");?>"><label for="pref_STARTVIEW"><?php
etranslate ('Preferred view')?>:</label></td><td>
<select class="form-control" name="pref_STARTVIEW" id="pref_STARTVIEW">
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

<tr><td data-toggle="tooltip" data-placement="top" title="<?php etooltip ("fonts-help");?>">
 <label for="pref_font"><?php etranslate ( 'Fonts')?></label></td><td>
 <input class="form-control" type="text" size="40" name="pref_FONTS" id="pref_font" value="<?php echo htmlspecialchars ( $prefarray['FONTS'] );?>" />
</td></tr>

<tr><td data-toggle="tooltip" data-placement="top" title="<?php etooltip ("display-sm_month-help");?>">
 <label for="pref_DISPLAY_SM_MONTH"><?php etranslate ( 'Display small months' )?>:</label></td><td class="form-inline mt-1 mb-2">
 <?php echo print_radio ( 'DISPLAY_SM_MONTH' ) ?>
</td></tr>

<tr><td data-toggle="tooltip" data-placement="top" title="<?php etooltip ("display-weekends-help");?>">
 <label for="pref_DISPLAY_WEEKENDS"><?php etranslate ( 'Display weekends' )?>:</label></td><td class="form-inline mt-1 mb-2">
 <?php echo print_radio ( 'DISPLAY_WEEKENDS' ) ?>
</td></tr>
 <tr><td data-toggle="tooltip" data-placement="top" title="<?php etooltip ("display-long-daynames-help");?>">
  <label for="pref_DISPLAY_LONG_DAYS"><?php etranslate ( 'Display long day names' )?>:</label></td><td class="form-inline mt-1 mb-2">
  <?php echo print_radio ( 'DISPLAY_LONG_DAYS' ) ?>
 </td></tr>
<tr><td data-toggle="tooltip" data-placement="top" title="<?php etooltip ("display-minutes-help");?>">
 <label for="pref_DISPLAY_MINUTES"><?php etranslate ( 'Display 00 minutes always' )?>:</label></td><td class="form-inline mt-1">
 <?php echo print_radio ( 'DISPLAY_MINUTES' ) ?>
</td></tr>
<tr><td data-toggle="tooltip" data-placement="top" title="<?php etooltip ("display-end-times-help");?>">
 <label for="pref_DISPLAY_END_TIMES"><?php etranslate ( 'Display end times on calendars' )?>:</label></td><td class="form-inline mt-1">
 <?php echo print_radio ( 'DISPLAY_END_TIMES' ) ?>
</td></tr>
<tr><td data-toggle="tooltip" data-placement="top" title="<?php etooltip ("display-alldays-help");?>">
  <label for="pref_DISPLAY_ALL_DAYS_IN_MONTH"><?php etranslate ( 'Display all days in month view' )?>:</label></td><td class="form-inline mt-1">
  <?php echo print_radio ( 'DISPLAY_ALL_DAYS_IN_MONTH' ) ?>
 </td></tr>
<tr><td data-toggle="tooltip" data-placement="top" title="<?php etooltip ("display-week-number-help");?>">
 <label for="pref_DISPLAY_WEEKNUMBER"><?php etranslate ( 'Display week number' )?>:</label></td><td class="form-inline mt-1">
 <?php echo print_radio ( 'DISPLAY_WEEKNUMBER' ) ?>
</td></tr>
<tr><td data-toggle="tooltip" data-placement="top" title="<?php etooltip ("display-tasks-help");?>">
 <label for="pref_DISPLAY_TASKS"><?php etranslate ( 'Display small task list' )?>:</label></td><td class="form-inline mt-1">
 <?php echo print_radio ( 'DISPLAY_TASKS' ) ?>
</td></tr>
<tr><td data-toggle="tooltip" data-placement="top" title="<?php etooltip ("display-tasks-in-grid-help");?>">
 <label for="pref_DISPLAY_TASKS_IN_GRID"><?php etranslate ( 'Display tasks in Calendars' )?>:</label></td><td class="form-inline mt-1">
 <?php echo print_radio ( 'DISPLAY_TASKS_IN_GRID' ) ?>
</td></tr>

<tr><td data-toggle="tooltip" data-placement="top" title="<?php etooltip ("lunar-help");?>">
 <label for="pref_DISPLAY_MOON_PHASES"><?php etranslate ( 'Display Lunar Phases in month view' )?>:</label></td><td class="form-inline mt-1">
 <?php echo print_radio ( 'DISPLAY_MOON_PHASES' ) ?>
</td></tr>

</table>
</fieldset>
<fieldset class="border p-2">
 <legend><?php etranslate ('Events')?></legend>
<table cellspacing="1" cellpadding="2">

<tr><td data-toggle="tooltip" data-placement="top" title="<?php etooltip ("display-unapproved-help");?>">
 <label for="pref_DISPLAY_UNAPPROVED"><?php etranslate ( 'Display unapproved' )?>:</label></td><td class="form-inline mt-1">
 <?php echo print_radio ( 'DISPLAY_UNAPPROVED' ) ?>
</td></tr>

<tr><td data-toggle="tooltip" data-placement="top" title="<?php etooltip ("timed-evt-len-help");?>">
 <label for="pref_TIMED_EVT_LEN"><?php etranslate ( 'Specify timed event length by' )?>:</label></td><td class="form-inline mt-1">
 <?php echo print_radio ( 'TIMED_EVT_LEN',
    ['D'=>translate ( 'Duration' ), 'E'=>translate ( 'End Time' )] ) ?>
</td></tr>

<?php if ( ! empty ( $categories ) ) { ?>
<tr><td>
 <label for="pref_cat"><?php etranslate ( 'Default Category' )?>:</label></td><td>
 <select class="form-control" name="pref_CATEGORY_VIEW" id="pref_cat">
<?php
 if ( ! empty ( $categories ) ) {
  foreach ( $categories as $K => $V ) {
   echo "<option value=\"$K\"";
   if ( ! empty ( $prefarray['CATEGORY_VIEW'] ) &&
    $prefarray['CATEGORY_VIEW'] == $K ) echo $selected;
   echo ">" . htmlentities ( $V['cat_name'] ) . "</option>\n";
  }
 }
?>
 </select>
</td></tr>
<?php } //end if (! empty ($categories ) ) ?>
<tr><td data-toggle="tooltip" data-placement="top" title="<?php etooltip ("crossday-help");?>">
 <label><?php etranslate ( 'Disable Cross-Day Events' )?>:</label></td><td class="form-inline mt-1">
 <?php echo print_radio ( 'DISABLE_CROSSDAY_EVENTS' ) ?>
</td></tr>
<tr><td data-toggle="tooltip" data-placement="top" title="<?php etooltip ("display-desc-print-day-help");?>">
 <label for="pref_DISPLAY_DESC_PRINT_DAY"><?php etranslate ( 'Display description in printer day view' )?>:</label></td><td class="form-inline mt-1">
 <?php echo print_radio ( 'DISPLAY_DESC_PRINT_DAY' ) ?>
</td></tr>

<tr><td data-toggle="tooltip" data-placement="top" title="<?php etooltip ("entry-interval-help");?>">
 <label for="pref_ENTRY_SLOTS"><?php etranslate ( 'Entry interval' )?>:</label></td><td>
 <select class="form-control" name="pref_ENTRY_SLOTS">
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
<tr><td data-toggle="tooltip" data-placement="top" title="<?php etooltip ("time-interval-help");?>">
 <label for="pref_TIME_SLOTS"><?php etranslate ( 'Time interval' )?>:</label></td><td>
 <select class="form-control" name="pref_TIME_SLOTS">
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
<fieldset class="border p-2">
 <legend><?php etranslate ('Miscellaneous')?></legend>
<table cellspacing="1" cellpadding="2">

<tr><td data-toggle="tooltip" data-placement="top" title="<?php etooltip ("auto-refresh-help");?>">
 <label for="pref_AUTO_REFRESH"><?php etranslate ( 'Auto-refresh calendars' )?>:</label></td><td class="form-inline mt-1">
 <?php echo print_radio ( 'AUTO_REFRESH' ) ?>
</td></tr>

<tr><td data-toggle="tooltip" data-placement="top" title="<?php etooltip ("auto-refresh-time-help");?>">
 &nbsp;&nbsp;&nbsp;&nbsp;<label for="pref_AUTO_REFRESH_TIME"><?php etranslate ( 'Auto-refresh time' )?>:</label></td><td class="form-inline mt-1">
 <nobr><input class="form-control" type="text" name="pref_AUTO_REFRESH_TIME" size="3" value="<?php echo ( empty ( $prefarray['AUTO_REFRESH_TIME'] ) ? 0 : $prefarray['AUTO_REFRESH_TIME'] ); ?>" /> <?php etranslate ( 'minutes' )?></nobr>
</td></tr>
</table>
</fieldset>
</div>
</div>
<!-- END SETTINGS -->

<?php
if ( ! $updating_public ) {
if ( $SEND_EMAIL == 'Y' ) { ?>
<div class="tab-pane container fade" id="email"><div class="form-group">
<table cellspacing="1" cellpadding="2">
<tr><td class="xtooltip">
<tr><td data-toggle="tooltip" data-placement="top" title="<?php etooltip ("email-format");?>">
 <label for="pref_EMAIL_HTML"><?php etranslate ( 'Email format preference' )?>:</label></td><td class="form-inline mt-1">
 <?php echo print_radio ( 'EMAIL_HTML',
    ['Y'=> translate ( 'HTML' ), 'N'=>translate ( 'Plain Text' )] ) ?>
</td></tr>

<tr><td data-toggle="tooltip" data-placement="top" title="<?php etooltip ("email-include-ics");?>">
 <label for="pref_EMAIL_ATTACH_ICS"><?php etranslate ( 'Include iCalendar attachments' )?>:</label></td><td class="form-inline mt-1">
 <?php echo print_radio ( 'EMAIL_ATTACH_ICS', '', '', 0 ) ?>
</td></tr>

<tr><td data-toggle="tooltip" data-placement="top" title="<?php etooltip ("email-event-reminders-help");?>">
 <label for="pref_EMAIL_REMINDER"><?php etranslate ( 'Event reminders' )?>:</label></td><td class="form-inline mt-1">
 <?php echo print_radio ( 'EMAIL_REMINDER' ) ?>
</td></tr>

<tr><td data-toggle="tooltip" data-placement="top" title="<?php etooltip ("email-event-added");?>">
 <label for="pref_EMAIL_EVENT_ADDED"><?php etranslate ( 'Events added to my calendar' )?>:</label></td><td class="form-inline mt-1">
 <?php echo print_radio ( 'EMAIL_EVENT_ADDED' ) ?>
</td></tr>

<tr><td data-toggle="tooltip" data-placement="top" title="<?php etooltip ("email-event-updated");?>">
 <label for="pref_EMAIL_EVENT_UPDATED"><?php etranslate ( 'Events updated on my calendar' )?>:</label></td><td class="form-inline mt-1">
 <?php echo print_radio ( 'EMAIL_EVENT_UPDATED' ) ?>
</td></tr>

<tr><td data-toggle="tooltip" data-placement="top" title="<?php etooltip ("email-event-deleted");?>">
 <label for="pref_EMAIL_EVENT_DELETED"><?php etranslate ( 'Events removed from my calendar' )?>:</label></td><td class="form-inline mt-1">
 <?php echo print_radio ( 'EMAIL_EVENT_DELETED' ) ?>
</td></tr>

<tr><td data-toggle="tooltip" data-placement="top" title="<?php etooltip ("email-event-rejected");?>">
 <label for="pref_EMAIL_EVENT_REJECTED"><?php etranslate ( 'Event rejected by participant' )?>:</label></td><td class="form-inline mt-1">
 <?php echo print_radio ( 'EMAIL_EVENT_REJECTED' ) ?>
</td></tr>

<tr><td data-toggle="tooltip" data-placement="top" title="<?php etooltip ("email-event-create");?>">
 <label for="pref_EMAIL_EVENT_CREATE"><?php etranslate ( 'Event that I create' )?>:</label></td><td class="form-inline mt-1">
 <?php echo print_radio ( 'EMAIL_EVENT_CREATE' ) ?>
</td></tr>
</table>
</div></div>
<!-- END EMAIL -->
<?php } ?>

<div class="tab-pane container fade" id="boss"><div class="form-group">
<table cellspacing="1" cellpadding="2">
<?php if ( $SEND_EMAIL == 'Y' ) { ?>
<tr><td><label for="pref_EMAIL_ASSISTANT_EVENTS"><?php etranslate ( 'Email me event notification' )?>:</label></td><td class="form-inline mt-1">
 <?php echo print_radio ( 'EMAIL_ASSISTANT_EVENTS' ) ?>
</td></tr>
<?php } //end email ?>
<tr><td><label for="pref_APPROVE_ASSISTANT_EVENT"><?php etranslate ( 'I want to approve events' )?>:</label></td><td class="form-inline mt-1">
 <?php echo print_radio ( 'APPROVE_ASSISTANT_EVENT' ) ?>
</td></tr>

<tr><td data-toggle="tooltip" data-placement="top" title="<?php etooltip ("display_byproxy-help");?>">
  <label for="pref_DISPLAY_CREATED_BYPROXY"><?php etranslate ( 'Display if created by Assistant' )?>:</label></td>
  <td class="form-inline mt-1">
  <?php echo print_radio ( 'DISPLAY_CREATED_BYPROXY' ) ?>
</td></tr>
</table>
</div></div>
<!-- END BOSS -->

<?php } /* if ( ! $updating_public ) */ ?>
<div class="tab-pane container fade" id="subscribe"><div class="form-group">
<table cellspacing="1" cellpadding="2">
<?php if ( $PUBLISH_ENABLED == 'Y' || $RSS_ENABLED == 'Y') { ?>
<tr><td data-toggle="tooltip" data-placement="top" title="<?php etooltip ("allow-view-subscriptions-help");?>">
<label for="pref_USER_REMOTE_ACCESS"><?php etranslate ( 'Allow remote viewing of' );
$publish_access = ( empty( $prefarray['USER_REMOTE_ACCESS'] )
   ? 0 : $prefarray['USER_REMOTE_ACCESS'] );
?>:</label></td><td>
  <select class="form-control" name="pref_USER_REMOTE_ACCESS" id="pref_USER_REMOTE_ACCESS">
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
<tr><td data-toggle="tooltip" data-placement="top" title="<?php etooltip ("allow-remote-subscriptions-help");?>">
  <label for="USER_PUBLISH_ENABLED"><?php etranslate ( 'Allow remote subscriptions' )?>:</label></td><td class="form-inline mt-1">
  <?php echo print_radio ( 'USER_PUBLISH_ENABLED' ) ?>
</td></tr>
<?php if ( ! empty ( $SERVER_URL ) ) { ?>
<tr><td data-toggle="tooltip" data-placement="top" title="<?php etooltip ("remote-subscriptions-url-help");?>">&nbsp;&nbsp;&nbsp;&nbsp;
  <label><?php etranslate ( 'URL' )?>:</label></td>
  <td>
  <?php
    echo htmlspecialchars ( $SERVER_URL ) .
      'publish.php/' . ( $updating_public ? '__public__' : $user ) . '.ics';
    echo "<br />\n";
    echo htmlspecialchars ( $SERVER_URL ) .
      'publish.php?user=' . ( $updating_public ? '__public__' : $user );
  ?></td></tr>
  <tr><td height="0.5 em"><!-- small vertical spacing--><span style="font-size: 25%">&nbsp;</span> </td></tr>
<?php } /* $SERVER_URL */ ?>

<tr><td data-toggle="tooltip" data-placement="top" title="<?php etooltip ("allow-remote-publishing-help");?>">
  <label for="pref_USER_PUBLISH_RW_ENABLED"><?php etranslate ( 'Allow remote publishing' )?>:</label></td>
  <td class="form-inline mt-1">
  <?php echo print_radio ( 'USER_PUBLISH_RW_ENABLED' ) ?>
</td></tr>
<?php if ( ! empty ( $SERVER_URL ) ) { ?>
<tr><td data-toggle="tooltip" data-placement="top" title="<?php etooltip ("remote-publishing-url-help");?>">
  &nbsp;&nbsp;&nbsp;&nbsp;<label><?php etranslate ( 'URL' )?>:</label></td>
  <td>
  <?php
    echo htmlspecialchars ( $SERVER_URL ) .
      'icalclient.php';
  ?></td></tr>
  <tr><td height="0.5 em"><!-- small vertical spacing--><span style="font-size: 25%">&nbsp;</span> </td></tr>
<?php } /* $SERVER_URL */

} /* $PUBLISH_ENABLED */

if ( $RSS_ENABLED == 'Y' ) { ?>
<tr><td data-toggle="tooltip" data-placement="top" title="<?php etooltip ("rss-enabled-help");?>">
<label for="pref_USER_RSS_ENABLED"><?php etranslate ( 'Enable RSS feed' )?>:</label></td>
  <td class="form-inline mt-1">
  <?php echo print_radio ( 'USER_RSS_ENABLED' ) ?>
</td></tr>
<?php if ( ! empty ( $SERVER_URL ) ) { ?>
<tr><td data-toggle="tooltip" data-placement="top" title="<?php etooltip ("rss-feed-url-help");?>">
  &nbsp;&nbsp;&nbsp;&nbsp;<label><?php etranslate ( 'URL' )?>:</label></td>
  <td>
  <?php
    echo htmlspecialchars ( $SERVER_URL ) .
      'rss.php?user=' . ( $updating_public ? '__public__' : $user );
  ?></td></tr>
  <tr><td height="0.5 em"><!-- small vertical spacing--><span style="font-size: 25%">&nbsp;</span> </td></tr>
<?php } /* $SERVER_URL */
} /* $RSS_ENABLED */ ?>

<tr><td data-toggle="tooltip" data-placement="top" title="<?php etooltip ("freebusy-enabled-help");?>">
  <label for="pref_FREEBUSY_ENABLED"><?php etranslate ( 'Enable FreeBusy publishing' )?>:</label></td>
  <td class="form-inline mt-1">
  <?php echo print_radio ( 'FREEBUSY_ENABLED' ) ?>
</td></tr>
<?php if ( ! empty ( $SERVER_URL ) ) { ?>
<tr><td data-toggle="tooltip" data-placement="top" title="<?php etooltip ("freebusy-url-help");?>">
  &nbsp;&nbsp;&nbsp;&nbsp;<label><?php etranslate ( 'URL' )?>:</label></td>
  <td>
  <?php
    echo htmlspecialchars ( $SERVER_URL ) .
      'freebusy.php/' . ( $updating_public ? '__public__' : $user ) . '.ifb';
    echo "<br />\n";
    echo htmlspecialchars ( $SERVER_URL ) .
      'freebusy.php?user=' . ( $updating_public ? '__public__' : $user );
  ?></td></tr>
  <tr><td height="0.5 em"><!-- small vertical spacing--><span style="font-size: 25%">&nbsp;</span> </td></tr>
<?php } /* $SERVER_URL */ ?>
</table>
</div></div>
<!-- END SUBSCRIBE -->

<?php if ( $ALLOW_USER_HEADER == 'Y' ) { ?>
  <div class="tab-pane container fade" id="header"><div class="form-group">
<table cellspacing="1" cellpadding="2">
<?php if ( $CUSTOM_SCRIPT == 'Y' ) { ?>
 <tr><td data-toggle="tooltip" data-placement="top" title="<?php etooltip ("custom-script-help");?>">
  <label><?php etranslate ( 'Custom script/stylesheet' )?>:</label></td><td>
  <input class="form-control btn bth-secondary" type="button" value="<?php etranslate ( 'Edit' );?>..." onclick=<?php
    printf ( $openStr, 'S',$prefuser ) ?> name="" />
 </td></tr>
<?php }

if ( $CUSTOM_HEADER == 'Y' ) { ?>
 <tr><td data-toggle="tooltip" data-placement="top" title="<?php etooltip ("custom-header-help");?>">
  <label><?php etranslate ( 'Custom header' )?>:</label></td><td>
  <input class="form-control btn btn-secondary" type="button" value="<?php etranslate ( 'Edit' );?>..." onclick=<?php
    printf ( $openStr, 'H',$prefuser ) ?> name="" />
 </td></tr>
<?php }

if ( $CUSTOM_TRAILER == 'Y' ) { ?>
 <tr><td data-toggle="tooltip" data-placement="top" title="<?php etooltip ("custom-trailer-help");?>">
  <label><?php etranslate ( 'Custom trailer' )?>:</label></td><td>
  <input class="form-control btn btn-secondary" type="button" value="<?php etranslate ( 'Edit' );?>..." onclick=<?php
    printf ( $openStr, 'T',$prefuser ) ?> name="" />
 </td></tr>
<?php } ?>
</table>
</div></div>
<!-- END HEADER -->
<?php } // if $ALLOW_USER_HEADER ?>

<!-- BEGIN COLORS -->

<?php if ( $ALLOW_COLOR_CUSTOMIZATION == 'Y' ) { ?>
  <div class="tab-pane container fade" id="colors"><div class="form-group">
<table>
<tr class="ignore"><td class="aligntop" width="50%">
<?php echo $color_sets;?>
<div><a href="#" class="btn btn-secondary" onclick="reset_colors(); return false;"><?php etranslate('Reset Colors');?></a></div>


</td><td class="aligncenter aligntop" width="50%">
<br />
<!-- BEGIN EXAMPLE MONTH -->
<p class="bold" style="text-align:center; color: var(--h2color)">
<?php
  echo date_to_str(date('Ymd'), $DATE_FORMAT_MY, false);
  echo "</p>\n";
  echo display_month(date('m'), date('Y'), true);
?>
<!-- END EXAMPLE MONTH -->
</td></tr></table>
</div>
<!-- END COLORS -->
<?php } // if $ALLOW_COLOR_CUSTOMIZATION ?>
</div>

<!-- END TABS -->
<br /><br />
<div>
<input class="btn btn-primary" type="submit" value="<?php etranslate ( 'Save Preferences' )?>" name="" />
<br /><br />
</div>
</form>

<script>

<?php
// Change the color in the current page
foreach ( $colors as $k => $v ) {
  echo "function color_change_handler_$k() {\n";
    echo "  var color = $('#pref_" . $k . "').val();\n";
    echo "  $('body').get(0).style.setProperty('--" . strtolower($k) . "', color);\n";
  echo "}\n";
}
?>

function reset_colors() {
  <?php
    foreach ( $colors as $k => $v ) {
      echo "  $('body').get(0).style.setProperty('--" . strtolower($k) . "', '$GLOBALS[$k]');\n";
      echo "  $('#pref_" . $k . "').val('$GLOBALS[$k]');\n";
    }
  ?>
}

$(document).ready(function(){
  $('[data-toggle="tooltip"]').tooltip();
});
</script>

<?php echo print_trailer(); ?>

