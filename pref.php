<?php
include_once 'includes/init.php';
include_once 'includes/date_formats.php';


//force the css cache to clear by incrementing webcalendar_csscache cookie
$webcalendar_csscache = 1;
if  ( isset ( $_COOKIE["webcalendar_csscache"] ) ) {
  $webcalendar_csscache += $_COOKIE["webcalendar_csscache"];
}
SetCookie ( "webcalendar_csscache", $webcalendar_csscache );

function save_pref( $prefs, $src) {
  global $my_theme, $prefuser, $MENU_THEME;
  while ( list ( $key, $value ) = each ( $prefs ) ) {
    if ( $src == 'post' ) {
      $setting = substr ( $key, 5 );
      $prefix = substr ( $key, 0, 5 );
      if ( $prefix != 'pref_')
        continue;
      // validate key name.  should start with "pref_" and not include
      // any unusual characters that might cause SQL injection
      if ( ! preg_match ( '/pref_[A-Za-z0-9_]+$/', $key ) ) {
        die_miserable_death ( 'Invalid pref setting name "' .
        $key . '"' );
      }
    } else {
      $setting = $key;
      $prefix = 'pref_';    
    }
    //echo "Setting = $setting, key = $key, prefix = $prefix <br />\n";
    if ( strlen ( $setting ) > 0 && $prefix == "pref_" ) {
      if ( $setting == "THEME" &&  $value != 'none' )
        $my_theme = strtolower ( $value ); 
      $sql =
        "DELETE FROM webcal_user_pref WHERE cal_login = ? " .
        "AND cal_setting = ?";
      dbi_execute ( $sql , array ( $prefuser , $setting ) );
      if ( strlen ( $value ) > 0 ) {
      $setting = strtoupper ( $setting );
        $sql = "INSERT INTO webcal_user_pref " .
          "( cal_login, cal_setting, cal_value ) VALUES " .
          "( ?, ?, ? )";
        if ( ! dbi_execute ( $sql , array ( $prefuser , $setting , $value ) ) ) {
          $error = "Unable to update preference: " . dbi_error () .
   "<br /><br /><span style=\"font-weight:bold;\">SQL:</span> $sql";
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

if ( $is_admin && ! empty ( $public ) && $PUBLIC_ACCESS == "Y" ) {
  $updating_public = true;
  load_user_preferences ( '__public__' );  
  $prefuser = "__public__";
} elseif ( ! empty ( $user ) && $user != $login && ($is_admin || $is_nonuser_admin)) {
  $prefuser = $user;
    load_user_preferences ();
} else {
  $prefuser = $login;
  // Reload preferences so any css changes will take effect
  load_user_preferences ();
}

if ( ! empty ( $_POST ) && empty ( $error )) {
  $my_theme = '';
  $currenttab = getPostValue ( 'currenttab' ); 
  save_pref ( $_POST, 'post' );
  
  if ( ! empty ( $my_theme ) ) {
    $theme = "themes/". $my_theme . "_pref.php";
    include_once $theme;
    save_pref ( $webcal_theme, 'theme' );  
  }
}


if ($user != $login)
  $user = (($is_admin || $is_nonuser_admin) && $user) ? $user : $login;

// Load categories only if editing our own calendar
if (!$user || $user == $login) load_user_categories ();
// Reload preferences into $prefarray[].
// Get system settings first.
$prefarray = array ();
$res = dbi_execute ( "SELECT cal_setting, cal_value FROM webcal_config " );
if ( $res ) {
  while ( $row = dbi_fetch_row ( $res ) ) {
    $prefarray[$row[0]] = $row[1];
  }
  dbi_free_result ( $res );
}
//get user settings
$res = dbi_execute ( "SELECT cal_setting, cal_value FROM webcal_user_pref " .
    "WHERE cal_login = ?" , array ( $prefuser ) );

if ( $res ) {
  while ( $row = dbi_fetch_row ( $res ) ) {
    $prefarray[$row[0]] = $row[1];
  }
  dbi_free_result ( $res );
}

//this will force $LANGUAGE to to the current value and eliminate having
//to double click the 'SAVE' buton
reset_language ( get_pref_setting ( $login, 'LANGUAGE' ) );

//get list of theme files from /themes directory
$themes = array();
$dir = "themes/";
if (is_dir($dir)) {
   if ($dh = opendir($dir)) {
       while (($file = readdir($dh)) !== false) {
         if ( strpos ( $file, "_pref.php" ) )
           $themes[] = strtoupper( str_replace ( "_pref.php", "", $file ) );
       }
       sort ( $themes );
       closedir($dh);
   }
}

//get list of menu themes
$menuthemes = array();
$dir = "includes/menu/themes/";
if ( is_dir( $dir ) ) {
   if ( $dh = opendir( $dir ) ) {
       while ( ( $file = readdir( $dh ) ) !== false ) {
         if ( $file == "." || $file == ".." || $file == "CVS" ) continue;
         if ( is_dir ( $dir.$file ) ) $menuthemes[] = $file;
       }
       closedir($dh);
   }
}

//make globals values passed to styles.php are for this user
//makes the demo calendar accurate
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
$can_set_timezone = set_env ( "TZ", $TIMEZONE );

$checked = " checked=\"checked\"";
$Yes = translate ( "Yes" );
$No = translate ( "No" );

$BodyX = ( ! empty ( $currenttab ) ? "onload=\"showTab( '". $currenttab . "' )\"" : '' );
$INC = array('js/pref.php','js/visible.php/true');
print_header($INC, '' , $BodyX);
?>

<h2><?php
 if ( $updating_public )
  echo translate($PUBLIC_ACCESS_FULLNAME) . "&nbsp;";
 etranslate("Preferences");
 if ( $is_nonuser_admin ) {
  nonuser_load_variables ( $user, "nonuser" );
  echo "<br /><strong>-- " . 
   translate("Admin mode") . ": ".$nonuserfullname." --</strong>\n";
 }
 
$formaction = substr($self, strrpos($self, '/') + 1) . "?" . $_SERVER['QUERY_STRING'];

?>&nbsp;<img src="images/help.gif" alt="<?php etranslate("Help")?>" class="help" onclick="window.open ( 'help_pref.php', 'cal_help', 'dependent,menubar,scrollbars,height=400,width=400,innerHeight=420,outerWidth=420');" /></h2>


<form action="<?php echo $formaction ?>" method="post" onsubmit="return valid_form(this);" name="prefform">
<input type="hidden" name="currenttab" id="currenttab" value="<?php echo $currenttab ?>" />
<?php 
 if ($user) 
  echo "<input type=\"hidden\" name=\"user\" value=\"$user\" />\n";
?>
<a title="<?php etranslate("Admin") ?>" class="nav" href="adminhome.php">&laquo;&nbsp;<?php etranslate("Admin") ?></a>&nbsp;&nbsp;
<input type="submit" value="<?php etranslate("Save Preferences")?>" name="" />
&nbsp;&nbsp;&nbsp;
<?php if ( $updating_public ) { ?>
 <input type="hidden" name="public" value="1" />
<?php } /*if ( $updating_public )*/ ?>


<?php
// If user is admin of a non-user cal, and non-user cal is "public"
// (meaning it is a public calendar that requires no login), then allow
// the current user to modify prefs for that nonuser cal
if ( $is_admin && ! $updating_public  ) {
  if ( empty ( $public ) && ! empty ( $PUBLIC_ACCESS ) &&
    $PUBLIC_ACCESS == 'Y' ) {
    $public_option =  "<option value=\"pref.php?public=1\">" .
      translate("Public Access calendar") .
      "</option>\n";
  }
}
if ( ( empty ( $user ) || $user == $login ) && ! $updating_public ) {
  $nulist = get_nonuser_cals ( $login );
  echo "<select onchange=\"location=this.options[this.selectedIndex].value;\">\n";
  echo "<option selected=\"selected\" disabled=\"disabled\" value=\"\">" . 
    translate ( "Modify Non User Calendar Preferences") . "</option>\n";
  if ( ! empty ( $public_option ) ) echo $public_option . "\n";
  for ( $i = 0; $i < count ( $nulist ); $i++ ) {
    echo "<option value=\"pref.php?user=". $nulist[$i]['cal_login']. "\">" . 
      $nulist[$i]['cal_fullname'] . "</option>\n";
  }
  echo "</select>\n";
} else {
  $linktext = translate ( "Return to My Preferences" );
  echo "<a title=\"$linktext\" class=\"nav\" href=\"pref.php\">&laquo;&nbsp; $linktext </a>";
}
?>

<br/><br />

<!-- TABS -->
<div id="tabs">
 <span class="tabfor" id="tab_settings"><a href="" onclick="return setTab('settings');"><?php etranslate("Settings")?></a></span>
 <?php if ( $ALLOW_USER_THEMES == "Y" || $MENU_ENABLED == "Y" ) { ?>
 <span class="tabbak" id="tab_themes"><a href="" onclick="return setTab('themes')"><?php etranslate("Themes")?></a></span>
<?php } ?> 
 <?php if ( $SEND_EMAIL == 'Y' ) { ?>
 <span class="tabbak" id="tab_email"><a href="" onclick="return setTab('email');"><?php etranslate("Email")?></a></span>
<?php } ?> 
 <span class="tabbak" id="tab_boss"><a href="" onclick="return setTab('boss');"><?php etranslate("When I am the boss")?></a></span>
<?php if ( $PUBLISH_ENABLED == 'Y'  || $RSS_ENABLED == 'Y' ) { ?> 
 <span class="tabbak" id="tab_subscribe"><a href="" onclick="return setTab('subscribe');"><?php etranslate("Subscribe/Publish")?></a></span>
<?php } ?> 
<?php if ( $ALLOW_USER_HEADER == 'Y') { ?> 
 <span class="tabbak" id="tab_header"><a href="" onclick="return setTab('header');"><?php etranslate("Custom Scripts")?></a></span>
<?php } ?> 
<?php if ( $ALLOW_COLOR_CUSTOMIZATION == 'Y' ) { ?>
 <span class="tabbak" id="tab_colors" title="<?php etooltip("colors-help")?>"><a href="" onclick="return setTab('colors');"><?php etranslate("Colors")?></a></span>
<?php } ?>
</div>

<!-- TABS BODY -->
<div id="tabscontent" style="width: 98%;">
 <!-- DETAILS -->
<div id="tabscontent_settings">
<table  cellspacing="1" cellpadding="2"  border="0">
<tr><td  class="tooltipselect" title="<?php etooltip("language-help");?>">
 <label for="pref_lang"><?php etranslate("Language")?>:</label></td><td>
 <select name="pref_LANGUAGE" id="pref_lang">
<?php
 reset ( $languages );
 while ( list ( $key, $val ) = each ( $languages ) ) {
   // Don't allow users to select browser-defined.  We want them to pick
   // a language so that when we send reminders (done without the benefit
   // of a browser-preferred language), we'll know which language to use.
   // DO let them select browser-defined for the public user.
   if ( $key != "Browser-defined" || $updating_public ||
              $is_nonuser_admin ) {
     echo "<option value=\"" . $val . "\"";
     if ( $val == $prefarray['LANGUAGE'] ) echo " selected=\"selected\"";
     echo ">" . translate( $key ) . "</option>\n";
   }
 }
?>
 </select>
 <br />
 <?php echo translate("Your browser default language is") . " " . 
   get_browser_language ( true )  . "."; ?>
</td></tr>
<?php if ( $can_set_timezone == true ) { ?>
<tr><td class="tooltipselect" title="<?php etooltip("tz-help")?>">
  <label for="pref_TIMEZONE"><?php etranslate("Timezone Selection")?>:</label></td><td>
  <?php 
   if ( empty ( $prefarray['TIMEZONE'] ) ) $prefarray['TIMEZONE'] = $SERVER_TIMEZONE;
   $tz_offset = date("Z") / ONE_HOUR;
   echo print_timezone_select_html ( "pref_", $prefarray['TIMEZONE']); 
   echo  translate("Your current GMT offset is") . "&nbsp;" .
     $tz_offset . "&nbsp;" .translate("hours") . ".";
  ?>
</td></tr>
 <?php } //end $can_set_timezone ?>
<tr><td class="tooltipselect" title="<?php etooltip("fonts-help")?>">
 <label for="pref_font"><?php etranslate("Fonts")?>:</label></td><td>
 <input type="text" size="40" name="pref_FONTS" id="pref_font" value="<?php echo htmlspecialchars ( $prefarray['FONTS'] );?>" />
</td></tr>

<tr><td class="tooltip" title="<?php etooltip("preferred-view-help");?>"><?php etranslate("Preferred view")?>:</td><td>
<select name="pref_STARTVIEW">
<?php
// For backwards compatibility.  We used to store without the .php extension
if ( $prefarray['STARTVIEW'] == 'month' || $prefarray['STARTVIEW'] == 'day' ||
  $prefarray['STARTVIEW'] == 'week' || $prefarray['STARTVIEW'] == 'year' )
  $prefarray['STARTVIEW'] .= '.php';
$choices = array ();
$choices_text = array ();
if ( access_can_access_function ( ACCESS_DAY ) ) {
  $choices[] = "day.php";
  $choices_text[] = translate ( "Day" );
}
if ( access_can_access_function ( ACCESS_WEEK ) ) {
  $choices[] = "week.php";
  $choices_text[] = translate ( "Week" );
}
if ( access_can_access_function ( ACCESS_MONTH ) ) {
  $choices[] = "month.php";
  $choices_text[] = translate ( "Month" );
}
if ( access_can_access_function ( ACCESS_YEAR ) ) {
  $choices[] = "year.php";
  $choices_text[] = translate ( "Year" );
}
for ( $i = 0; $i < count ( $choices ); $i++ ) {
  echo "<option value=\"" . $choices[$i] . "\" ";
  if ( $prefarray['STARTVIEW'] == $choices[$i] )
    echo " selected=\"selected\"";
  echo " >" . htmlspecialchars ( $choices_text[$i] ) . "</option>\n";
}
// Allow user to select a view also
for ( $i = 0; $i < count ( $views ); $i++ ) {
  if ( $updating_public && $views[$i]['cal_is_global'] != 'Y' )
    continue;
  $xurl = $views[$i]['url'];
  echo "<option value=\"";
  echo $xurl . "\" ";
  $xurl_strip = str_replace ( "&amp;", "&", $xurl );
  if ( $prefarray['STARTVIEW'] == $xurl_strip )
    echo "selected=\"selected\" ";
  echo ">" . htmlspecialchars ( $views[$i]['cal_name'] ) . "</option>\n";
}
?>
</select>
</td></tr>

<tr><td class="tooltip" title="<?php etooltip("display-sm_month-help");?>">
 <?php etranslate("Display small months")?>:</td><td>
 <label><input type="radio" name="pref_DISPLAY_SM_MONTH" value="Y" <?php if ( $prefarray['DISPLAY_SM_MONTH'] != "N" ) echo $checked;?> /> <?php echo $Yes?></label> 
 <label><input type="radio" name="pref_DISPLAY_SM_MONTH" value="N" <?php if ( $prefarray['DISPLAY_SM_MONTH'] == "N" ) echo $checked;?> /> <?php echo $No?></label>
</td></tr>

<tr><td class="tooltip" title="<?php etooltip("display-weekends-help");?>">
 <?php etranslate("Display weekends")?>:</td><td>
 <label><input type="radio" name="pref_DISPLAY_WEEKENDS" value="Y" <?php if ( $prefarray['DISPLAY_WEEKENDS'] != "N" ) echo $checked;?> /> <?php echo $Yes?></label> 
 <label><input type="radio" name="pref_DISPLAY_WEEKENDS" value="N" <?php if ( $prefarray['DISPLAY_WEEKENDS'] == "N" ) echo $checked;?> /> <?php echo $No?></label>
</td></tr>

<tr><td class="tooltip" title="<?php etooltip("display-desc-print-day-help");?>">
 <?php etranslate("Display description in printer day view")?>:</td><td>
 <label><input type="radio" name="pref_DISPLAY_DESC_PRINT_DAY" value="Y" <?php if ( $prefarray['DISPLAY_DESC_PRINT_DAY'] == "Y" ) echo $checked;?> /> <?php echo $Yes?></label> 
 <label><input type="radio" name="pref_DISPLAY_DESC_PRINT_DAY" value="N" <?php if ( $prefarray['DISPLAY_DESC_PRINT_DAY'] != "Y" ) echo $checked;?> /> <?php echo $No?></label>
</td></tr>

<tr><td class="tooltipselect" title="<?php etooltip("date-format-help");?>">
 <?php etranslate("Date format")?>:</td><td>
 <select name="pref_DATE_FORMAT">
  <?php
  for ( $i = 0; $i < count ( $datestyles ); $i += 2 ) {
    echo "<option value=\"" . $datestyles[$i] . "\"";
    if ( $prefarray['DATE_FORMAT'] == $datestyles[$i] )
      echo " selected=\"selected\"";
    echo ">" . $datestyles[$i + 1] . "</option>\n";
  }
  ?>
</select>&nbsp;<?php echo date_to_str ( date ("Ymd"), 
    $DATE_FORMAT, false, false );?>
<br />
<select name="pref_DATE_FORMAT_MY">
<?php
  for ( $i = 0; $i < count ( $datestyles_my ); $i += 2 ) {
    echo "<option value=\"" . $datestyles_my[$i] . "\"";
    if ( $prefarray['DATE_FORMAT_MY'] == $datestyles_my[$i] )
      echo " selected=\"selected\"";
    echo ">" . $datestyles_my[$i + 1] . "</option>\n";
  }
?>
</select>&nbsp;<?php echo date_to_str ( date ("Ymd"), 
    $DATE_FORMAT_MY, false, false );?>
<br />
<select name="pref_DATE_FORMAT_MD">
<?php
  for ( $i = 0; $i < count ( $datestyles_md ); $i += 2 ) {
    echo "<option value=\"" . $datestyles_md[$i] . "\"";
    if ( $prefarray['DATE_FORMAT_MD'] == $datestyles_md[$i] )
      echo " selected=\"selected\"";
    echo ">" . $datestyles_md[$i + 1] . "</option>\n";
  }
?>
</select>&nbsp;<?php echo date_to_str ( date ("Ymd"), 
    $DATE_FORMAT_MD, false, false );?>
</td></tr>

<tr><td class="tooltip" title="<?php etooltip("time-format-help")?>">
 <?php etranslate("Time format")?>:</td><td>
 <label><input type="radio" name="pref_TIME_FORMAT" value="12" <?php if ( $prefarray['TIME_FORMAT'] == "12" ) echo $checked;?> /> <?php etranslate("12 hour")?></label> 
 <label><input type="radio" name="pref_TIME_FORMAT" value="24" <?php if ( $prefarray['TIME_FORMAT'] != "12" ) echo $checked;?> /> <?php etranslate("24 hour")?></label>
</td></tr>

<tr><td class="tooltip" title="<?php etooltip("time-interval-help")?>">
 <?php etranslate("Time interval")?>:</td><td>
 <select name="pref_TIME_SLOTS">
  <option value="24" <?php if ( $prefarray['TIME_SLOTS'] == "24" ) echo " selected=\"selected\""?>>1 <?php etranslate("hour")?></option>
  <option value="48" <?php if ( $prefarray['TIME_SLOTS'] == "48" ) echo " selected=\"selected\""?>>30 <?php etranslate("minutes")?></option>
  <option value="72" <?php if ( $prefarray['TIME_SLOTS'] == "72" ) echo " selected=\"selected\""?>>20 <?php etranslate("minutes")?></option>
  <option value="96" <?php if ( $prefarray['TIME_SLOTS'] == "96" ) echo " selected=\"selected\""?>>15 <?php etranslate("minutes")?></option>
  <option value="144" <?php if ( $prefarray['TIME_SLOTS'] == "144" ) echo " selected=\"selected\""?>>10 <?php etranslate("minutes")?></option>
 </select>
</td></tr>

<tr><td class="tooltip" title="<?php etooltip("auto-refresh-help");?>">
 <?php etranslate("Auto-refresh calendars")?>:</td><td>
 <label><input type="radio" name="pref_AUTO_REFRESH" value="Y" <?php if ( $prefarray['AUTO_REFRESH'] == "Y" ) echo $checked;?> /> <?php echo $Yes?></label>&nbsp;
 <label><input type="radio" name="pref_AUTO_REFRESH" value="N" <?php if ( $prefarray['AUTO_REFRESH'] != "Y" ) echo $checked;?> /> <?php echo $No?></label>
</td></tr>

<tr><td class="tooltip" title="<?php etooltip("auto-refresh-time-help");?>">
 &nbsp;&nbsp;&nbsp;&nbsp;<?php etranslate("Auto-refresh time")?>:</td><td>
 <input type="text" name="pref_AUTO_REFRESH_TIME" size="4" value="<?php echo ( empty ( $prefarray['AUTO_REFRESH_TIME'] ) ? 0 : $prefarray['AUTO_REFRESH_TIME'] ); ?>" /> <?php etranslate("minutes")?>
</td></tr>

<tr><td class="tooltip" title="<?php etooltip("display-unapproved-help");?>">
 <?php etranslate("Display unapproved")?>:</td><td>
 <label><input type="radio" name="pref_DISPLAY_UNAPPROVED" value="Y" <?php if ( $prefarray['DISPLAY_UNAPPROVED'] != "N" ) echo $checked;?> /> <?php echo $Yes?></label>&nbsp;
 <label><input type="radio" name="pref_DISPLAY_UNAPPROVED" value="N" <?php if ( $prefarray['DISPLAY_UNAPPROVED'] == "N" ) echo $checked;?> /> <?php echo $No?></label>
</td></tr>
 <tr><td class="tooltip" title="<?php etooltip("display-alldays-help");?>">
  <?php etranslate("Display all days in month view")?>:</td><td>
  <label><input type="radio" name="pref_DISPLAY_ALL_DAYS_IN_MONTH" value="Y" <?php if ( $prefarray['DISPLAY_ALL_DAYS_IN_MONTH'] != "N" ) echo $checked;?> />&nbsp;<?php echo $Yes?></label>&nbsp;
  <label><input type="radio" name="pref_DISPLAY_ALL_DAYS_IN_MONTH" value="N" <?php if ( $prefarray['DISPLAY_ALL_DAYS_IN_MONTH'] == "N" ) echo $checked;?> />&nbsp;<?php echo $No?></label>
 </td></tr> 
<tr><td class="tooltip" title="<?php etooltip("display-week-number-help")?>">
 <?php etranslate("Display week number")?>:</td><td>
 <label><input type="radio" name="pref_DISPLAY_WEEKNUMBER" value="Y" <?php if ( $prefarray['DISPLAY_WEEKNUMBER']!= "N" ) echo $checked;?> /> <?php echo $Yes?></label>&nbsp;
 <label><input type="radio" name="pref_DISPLAY_WEEKNUMBER" value="N" <?php if ( $prefarray['DISPLAY_WEEKNUMBER'] == "N" ) echo $checked;?> /> <?php echo $No?></label>
</td></tr>
<tr><td class="tooltip" title="<?php etooltip("display-week-starts-on")?>">
 <?php etranslate("Week starts on")?>:</td><td>
 <label><input type="radio" name="pref_WEEK_START" value="0" <?php if ( $prefarray['WEEK_START'] != "1" ) echo $checked;?> /> <?php etranslate("Sunday")?></label>&nbsp;
 <label><input type="radio" name="pref_WEEK_START" value="1" <?php if ( $prefarray['WEEK_START'] == "1" ) echo $checked;?> /> <?php etranslate("Monday")?></label>
</td></tr>
<tr><td class="tooltip" title="<?php etooltip("work-hours-help")?>">
 <?php etranslate("Work hours")?>:</td><td>
 <label for="pref_starthr"><?php etranslate("From")?></label> 
 <select name="pref_WORK_DAY_START_HOUR" id="pref_starthr">
<?php
  for ( $i = 0; $i < 24; $i++ ) {
    echo "<option value=\"$i\"" .
      ( $i == $prefarray['WORK_DAY_START_HOUR'] ? " selected=\"selected\"" : "" ) .
      ">" . display_time ( $i * 10000, 1 ) . "</option>\n";
  }
?>
 </select> 
 <label for="pref_endhr"><?php etranslate("to")?></label>
 <select name="pref_WORK_DAY_END_HOUR" id="pref_endhr">
<?php
 for ( $i = 0; $i <= 24; $i++ ) {
  echo "<option value=\"$i\"" .
   ( $i == $prefarray['WORK_DAY_END_HOUR'] ? " selected=\"selected\"" : "" ) .
   ">" . display_time ( $i * 10000, 1 ) . "</option>\n";
 }
?>
 </select>
</td></tr>

<tr><td class="tooltip" title="<?php etooltip("timed-evt-len-help");?>">
 <?php etranslate("Specify timed event length by")?>:</td><td>
 <label><input type="radio" name="pref_TIMED_EVT_LEN" value="D" <?php if ( $prefarray['TIMED_EVT_LEN'] != "E" ) echo $checked;?> /> <?php etranslate("Duration")?></label> 
 <label><input type="radio" name="pref_TIMED_EVT_LEN" value="E" <?php if ( $prefarray['TIMED_EVT_LEN'] == "E" ) echo $checked;?> /> <?php etranslate("End Time")?></label>
</td></tr>

<?php if ( ! empty ( $categories ) ) { ?>
<tr><td>
 <label for="pref_cat"><?php etranslate("Default Category")?>:</label></td><td>
 <select name="pref_CATEGORY_VIEW" id="pref_cat">
<?php
 echo "<option value=\"\"";
 if ( empty ( $prefarray['CATEGORY_VIEW'] ) ) echo " selected=\"selected\"";
 echo ">".translate("All")."</option>\n";
 
 if ( ! empty ( $categories ) ) {
  foreach( $categories as $K => $V ){
   echo "<option value=\"$K\"";
   if ( ! empty ( $prefarray['CATEGORY_VIEW'] ) &&
    $prefarray['CATEGORY_VIEW'] == $K ) echo " selected=\"selected\"";
   echo ">$V</option>\n";
  }
 }
?>
 </select>
</td></tr>
<?php } //end if (! empty ($categories ) ) ?>
<tr><td class="tooltip" title="<?php etooltip("display-tasks-help")?>">
 <?php etranslate("Display small task list")?>:</td><td>
 <label><input type="radio" name="pref_DISPLAY_TASKS" value="Y" <?php if ( $prefarray['DISPLAY_TASKS'] != "N" ) echo $checked;?> /> <?php echo $Yes?></label>&nbsp;
 <label><input type="radio" name="pref_DISPLAY_TASKS" value="N" <?php if ( $prefarray['DISPLAY_TASKS'] == "N" ) echo $checked;?> /> <?php echo $No?></label>
</td></tr>
<tr><td class="tooltip" title="<?php etooltip("display-tasks-in-grid-help")?>">
 <?php etranslate("Display tasks in Calendars")?>:</td><td>
 <label><input type="radio" name="pref_DISPLAY_TASKS_IN_GRID" value="Y" <?php if (  $prefarray['DISPLAY_TASKS_IN_GRID']  != "N" ) echo $checked;?> /> <?php echo $Yes?></label>&nbsp;
 <label><input type="radio" name="pref_DISPLAY_TASKS_IN_GRID" value="N" <?php if (  $prefarray['DISPLAY_TASKS_IN_GRID'] == "N" ) echo $checked;?> /> <?php echo $No?></label>
</td></tr>
<tr><td class="tooltip" title="<?php etooltip("lunar-help")?>">
 <?php etranslate("Display Lunar Phases in month view")?>:</td><td>
 <label><input type="radio" name="pref_DISPLAY_MOON_PHASES" value="Y" <?php if (  $prefarray['DISPLAY_MOON_PHASES'] != "N" ) echo $checked;?> /> <?php echo $Yes?></label>&nbsp;
 <label><input type="radio" name="pref_DISPLAY_MOON_PHASES" value="N" <?php if (  $prefarray['DISPLAY_MOON_PHASES'] == "N" ) echo $checked;?> /> <?php echo $No?></label>
</td></tr>
<tr><td class="tooltip" title="<?php etooltip("crossday-help")?>">
 <?php etranslate("Disable Cross-Day Events")?>:</td><td>
 <label><input type="radio" name="pref_DISABLE_CROSSDAY_EVENTS" value="Y" <?php if (  $prefarray['DISABLE_CROSSDAY_EVENTS'] != "N" ) echo $checked;?> /> <?php echo $Yes?></label>&nbsp;
 <label><input type="radio" name="pref_DISABLE_CROSSDAY_EVENTS" value="N" <?php if (  $prefarray['DISABLE_CROSSDAY_EVENTS'] == "N" ) echo $checked;?> /> <?php echo $No?></label>
</td></tr>
</table>
</div>
<!-- END SETTINGS -->

<?php if ( $ALLOW_USER_THEMES == "Y" || $MENU_ENABLED == "Y" ) { ?>
<div id="tabscontent_themes">
<table  cellspacing="1" cellpadding="2"  border="0" width="35%">
<?php if ( $ALLOW_USER_THEMES == "Y" ) { ?>
<tr><td  class="tooltipselect" title="<?php etooltip("themes-help");?>">
 <label for="pref_THEME"><?php etranslate("Themes")?>:</label></td><td>
 <select name="pref_THEME" id="pref_THEME">
<?php
  echo "<option disabled>" . translate("AVAILABLE THEMES") . "</option>\n";
  //always use 'none' as default so we don't overwrite manual settings
  echo "<option value=\"none\" selected=\"selected\">" . translate("None") . "</option>\n";
    foreach ( $themes as $theme ) {
     echo "<option value=\"" . $theme . "\">" . $theme . "</option>\n";
  }
?>
 </select></td><td>
 <input type="button" name="preview" value="<?php etranslate ( "Preview" ) ?>" onclick="return showPreview()" />
</td></tr>
<?php } if ( $MENU_ENABLED == "Y" ) { ?>
 <tr><td  class="tooltip" title="<?php etooltip("menu-themes-help");?>">
 <label for="pref_MENU_THEME"><?php etranslate("Menu theme")?>:</label></td><td>
 <select name="pref_MENU_THEME" id="pref_MENU_THEME">
<?php
  echo "<option  value=\"none\" selected=\"selected\">" . translate("None") . "</option>\n";
  foreach ( $menuthemes as $menutheme ) {
     echo "<option value=\"" . $menutheme . "\"";
     if ($prefarray['MENU_THEME'] == $menutheme ) echo " selected=\"selected\"";
     echo ">" . $menutheme . "</option>\n";
  }
?>
 </select>
 </td></tr> 
<?php } ?>
</table>
</div>
<!-- END THEMES -->
<?php } ?>

<?php if ( ! $updating_public ) { ?>
<?php if ( $SEND_EMAIL == 'Y' ) { ?>
<div id="tabscontent_email">
<table  cellspacing="1" cellpadding="2">
<tr><td>
 <?php etranslate("Email format preference")?>:</td><td>
 <label><input type="radio" name="pref_EMAIL_HTML" value="Y" <?php if ( $prefarray['EMAIL_HTML'] != "N" ) echo $checked;?> />&nbsp;<?php etranslate("HTML")?></label>&nbsp;
 <label><input type="radio" name="pref_EMAIL_HTML" value="N" <?php if ( $prefarray['EMAIL_HTML'] == "N" ) echo $checked;?> />&nbsp;<?php etranslate("Plain Text")?></label>
</td></tr>

<tr><td>
 <?php etranslate("Event reminders")?>:</td><td>
 <label><input type="radio" name="pref_EMAIL_REMINDER" value="Y" <?php if ( $prefarray['EMAIL_REMINDER'] != "N" ) echo $checked;?> />&nbsp;<?php echo $Yes?></label>&nbsp;
 <label><input type="radio" name="pref_EMAIL_REMINDER" value="N" <?php if ( $prefarray['EMAIL_REMINDER'] == "N" ) echo $checked;?> />&nbsp;<?php echo $No?></label>
</td></tr>

<tr><td>
 <?php etranslate("Events added to my calendar")?>:</td><td>
 <label><input type="radio" name="pref_EMAIL_EVENT_ADDED" value="Y" <?php if ( $prefarray['EMAIL_EVENT_ADDED'] != "N" ) echo $checked;?> /> <?php echo $Yes?></label> <label><input type="radio" name="pref_EMAIL_EVENT_ADDED" value="N" <?php if ( $prefarray['EMAIL_EVENT_ADDED'] == "N" ) echo $checked;?> /> <?php echo $No?></label></td></tr>

<tr><td>
 <?php etranslate("Events updated on my calendar")?>:</td><td>
 <label><input type="radio" name="pref_EMAIL_EVENT_UPDATED" value="Y" <?php if ( $prefarray['EMAIL_EVENT_UPDATED'] != "N" ) echo $checked;?> /> <?php echo $Yes?></label> <label><input type="radio" name="pref_EMAIL_EVENT_UPDATED" value="N" <?php if ( $prefarray['EMAIL_EVENT_UPDATED'] == "N" ) echo $checked;?> /> <?php echo $No?></label>
</td></tr>

<tr><td>
 <?php etranslate("Events removed from my calendar")?>:</td><td>
 <label><input type="radio" name="pref_EMAIL_EVENT_DELETED" value="Y" <?php if ( $prefarray['EMAIL_EVENT_DELETED'] != "N" ) echo $checked;?> /> <?php echo $Yes?></label> <label><input type="radio" name="pref_EMAIL_EVENT_DELETED" value="N" <?php if ( $prefarray['EMAIL_EVENT_DELETED'] == "N" ) echo $checked;?> /> <?php echo $No?></label>
</td></tr>

<tr><td>
 <?php etranslate("Event rejected by participant")?>:</td><td>
 <label><input type="radio" name="pref_EMAIL_EVENT_REJECTED" value="Y" <?php if ( $prefarray['EMAIL_EVENT_REJECTED'] != "N" ) echo $checked;?> /> <?php echo $Yes?></label> <label><input type="radio" name="pref_EMAIL_EVENT_REJECTED" value="N" <?php if ( $prefarray['EMAIL_EVENT_REJECTED'] == "N" ) echo $checked;?> /> <?php echo $No?></label>
</td></tr>
</table>
</div>
<!-- END EMAIL -->
<?php } ?>

<div id="tabscontent_boss">
<table  cellspacing="1" cellpadding="2">
<?php if ( $SEND_EMAIL == 'Y' ) { ?>
<tr><td><?php etranslate("Email me event notification")?>:</td>
  <td><label><input type="radio" name="pref_EMAIL_ASSISTANT_EVENTS" value="Y" <?php if ( $prefarray['EMAIL_ASSISTANT_EVENTS'] == "Y" ) echo $checked;?> /> <?php echo $Yes?></label> <label><input type="radio" name="pref_EMAIL_ASSISTANT_EVENTS" value="N" <?php if ( $prefarray['EMAIL_ASSISTANT_EVENTS'] == "N" ) echo $checked;?> /> <?php echo $No?></label></td></tr>
<?php } //end email ?>
<tr><td><?php etranslate("I want to approve events")?>:</td>
  <td><label><input type="radio" name="pref_APPROVE_ASSISTANT_EVENT" value="Y" <?php if ( $prefarray['APPROVE_ASSISTANT_EVENT'] == "Y" ) echo $checked;?> /> <?php echo $Yes?></label> <label><input type="radio" name="pref_APPROVE_ASSISTANT_EVENT" value="N" <?php if ( $prefarray['APPROVE_ASSISTANT_EVENT'] == "N" ) echo $checked;?> /> <?php echo $No?></label></td></tr>

<tr><td class="tooltip" title="<?php etooltip("display_byproxy-help")?>"><?php etranslate("Display if created by Assistant")?>:</td>
  <td><label><input type="radio" name="pref_DISPLAY_CREATED_BYPROXY" value="Y" <?php if ( $prefarray['DISPLAY_CREATED_BYPROXY'] == "Y" ) echo $checked;?> /> <?php echo $Yes?></label> <label><input type="radio" name="pref_DISPLAY_CREATED_BYPROXY" value="N" <?php if ( $prefarray['DISPLAY_CREATED_BYPROXY'] == "N" ) echo $checked;?> /> <?php echo $No?></label></td></tr>
</table>
</div>
<!-- END BOSS -->

<?php } /* if ( ! $updating_public ) */ ?>
<div id="tabscontent_subscribe">
<table  cellspacing="1" cellpadding="2">
<?php if ( $PUBLISH_ENABLED == 'Y' ) { ?>
<tr><td class="tooltipselect" title="<?php etooltip("allow-remote-subscriptions-help")?>"><?php etranslate("Allow remote subscriptions")?>:</td>
  <td><label><input type="radio" name="pref_USER_PUBLISH_ENABLED" value="Y" <?php if ( isset ( $prefarray['USER_PUBLISH_ENABLED'] ) && $prefarray['USER_PUBLISH_ENABLED'] == "Y" ) echo $checked;?> /> <?php echo $Yes?></label> <label><input type="radio" name="pref_USER_PUBLISH_ENABLED" value="N" <?php if ( empty ( $prefarray['USER_PUBLISH_ENABLED'] ) || $prefarray['USER_PUBLISH_ENABLED'] != "Y" ) echo $checked;?> /> <?php echo $No?></label></td></tr>
<?php if ( ! empty ( $SERVER_URL ) ) { ?>
<tr><td class="tooltipselect" title="<?php etooltip("remote-subscriptions-url-help")?>">&nbsp;&nbsp;&nbsp;&nbsp;<?php etranslate("URL")?>:</td>
  <td>
  <?php
    echo htmlspecialchars ( $SERVER_URL ) .
      "publish.php/" . ( $updating_public ? "public" : $user ) .  ".ics";
    echo "<br />\n";
    echo htmlspecialchars ( $SERVER_URL ) .
      "publish.php?user=" . ( $updating_public ? "public" : $user );
  ?></td></tr>
<?php } /* $SERVER_URL */ ?>

<tr><td class="tooltipselect" title="<?php etooltip("allow-remote-publishing-help")?>"><?php etranslate("Allow remote publishing")?>:</td>
  <td><label><input type="radio" name="pref_USER_PUBLISH_RW_ENABLED" value="Y" <?php if ( isset ( $prefarray['USER_PUBLISH_RW_ENABLED'] ) && $prefarray['USER_PUBLISH_RW_ENABLED'] == "Y" ) echo $checked;?> /> <?php echo $Yes?></label> <label><input type="radio" name="pref_USER_PUBLISH_RW_ENABLED" value="N" <?php if ( empty ( $prefarray['USER_PUBLISH_RW_ENABLED'] ) || $prefarray['USER_PUBLISH_RW_ENABLED'] != "Y" ) echo $checked;?> /> <?php echo $No?></label></td></tr>
<?php if ( ! empty ( $SERVER_URL ) ) { ?>
<tr><td class="tooltipselect" title="<?php etooltip("remote-publishing-url-help")?>">&nbsp;&nbsp;&nbsp;&nbsp;<?php etranslate("URL")?>:</td>
  <td>
  <?php
    echo htmlspecialchars ( $SERVER_URL ) .
      "icalclient.php";
  ?></td></tr>
<?php } /* $SERVER_URL */ ?>

<?php } /* $PUBLISH_ENABLED */ ?>

<?php if ( $RSS_ENABLED == 'Y' ) { ?>
<tr><td class="tooltipselect" title="<?php etooltip("rss-enabled-help")?>"><?php etranslate("Enable RSS feed")?>:</td>
  <td><label><input type="radio" name="pref_USER_RSS_ENABLED" value="Y" <?php if ( isset ( $prefarray['USER_RSS_ENABLED'] ) && $prefarray['USER_RSS_ENABLED'] == "Y" ) echo $checked;?> /> <?php echo $Yes?></label> <label><input type="radio" name="pref_USER_RSS_ENABLED" value="N" <?php if ( empty ( $prefarray['USER_RSS_ENABLED'] ) || $prefarray['USER_RSS_ENABLED'] != "Y" ) echo $checked;?> /> <?php echo $No?></label></td></tr>
<?php if ( ! empty ( $SERVER_URL ) ) { ?>
<tr><td class="tooltipselect" title="<?php etooltip("rss-feed-url-help")?>">&nbsp;&nbsp;&nbsp;&nbsp;<?php etranslate("URL")?>:</td>
  <td>
  <?php
    echo htmlspecialchars ( $SERVER_URL ) .
      "rss.php?user=" . ( $updating_public ? "public" : $user );
  ?></td></tr>
<?php } /* $SERVER_URL */ ?>
<?php } /* $RSS_ENABLED */ ?>

<tr><td class="tooltipselect" title="<?php etooltip("freebusy-enabled-help")?>"><?php etranslate("Enable FreeBusy publishing")?>:</td>
  <td><label><input type="radio" name="pref_FREEBUSY_ENABLED" value="Y" <?php if ( $prefarray['FREEBUSY_ENABLED'] == "Y" ) echo $checked;?> /> <?php echo $Yes?></label> <label><input type="radio" name="pref_FREEBUSY_ENABLED" value="N" <?php if ( $prefarray['FREEBUSY_ENABLED'] != "Y" ) echo $checked;?> /> <?php echo $No?></label></td></tr>
<?php if ( ! empty ( $SERVER_URL ) ) { ?>
<tr><td class="tooltipselect" title="<?php etooltip("freebusy-url-help")?>">&nbsp;&nbsp;&nbsp;&nbsp;<?php etranslate("URL")?>:</td>
  <td>
  <?php
    echo htmlspecialchars ( $SERVER_URL ) .
      "freebusy.php/" . ( $updating_public ? "public" : $user ) . ".ifb";
    echo "<br />\n";
    echo htmlspecialchars ( $SERVER_URL ) .
      "freebusy.php?user=" . ( $updating_public ? "public" : $user );
  ?></td></tr>
<?php } /* $SERVER_URL */ ?>
</table>
</div>
<!-- END SUBSCRIBE -->

<?php if ( $ALLOW_USER_HEADER == 'Y' ) { ?>
<div id="tabscontent_header">
<table  cellspacing="1" cellpadding="2">
<?php if ( $CUSTOM_SCRIPT == 'Y' ) { ?>
 <tr><td class="tooltip" title="<?php etooltip("custom-script-help");?>">
  <?php etranslate("Custom script/stylesheet")?>:</td><td>
  <input type="button" value="<?php etranslate("Edit");?>..." onclick="window.open('edit_template.php?type=S&amp;user=<?php echo $user;?>','cal_template','dependent,menubar,scrollbars,height=500,width=500,outerHeight=520,outerWidth=520');" name="" />
 </td></tr>
<?php } ?>

<?php if ( $CUSTOM_HEADER == 'Y' ) { ?>
 <tr><td class="tooltip" title="<?php etooltip("custom-header-help");?>">
  <?php etranslate("Custom header")?>:</td><td>
  <input type="button" value="<?php etranslate("Edit");?>..." onclick="window.open('edit_template.php?type=H&amp;user=<?php echo $user;?>','cal_template','dependent,menubar,scrollbars,height=500,width=500,outerHeight=520,outerWidth=520');" name="" />
 </td></tr>
<?php } ?>

<?php if ( $CUSTOM_TRAILER == 'Y'  ) { ?>
 <tr><td class="tooltip" title="<?php etooltip("custom-trailer-help");?>">
  <?php etranslate("Custom trailer")?>:</td><td>
  <input type="button" value="<?php etranslate("Edit");?>..." onclick="window.open('edit_template.php?type=T&amp;user=<?php echo $user;?>','cal_template','dependent,menubar,scrollbars,height=500,width=500,outerHeight=520,outerWidth=520');" name="" />
 </td></tr>
<?php } ?>
</table>
</div>
<!-- END HEADER -->
<?php } // if $ALLOW_USER_HEADER ?>

<!-- BEGIN COLORS -->

<?php if ( $ALLOW_COLOR_CUSTOMIZATION == 'Y' ) { ?>
<div id="tabscontent_colors">
<table style="border-width:0px; width:100%;">
<tr><td style="vertical-align:top;">
<table  cellspacing="1" cellpadding="2">
 <tr><td>
  <label for="pref_bg"><?php etranslate("Document background")?>:</label></td><td>
  <input type="text" name="pref_BGCOLOR" id="pref_bg" size="8" maxlength="7" value="<?php echo $prefarray['BGCOLOR']; ?>" onkeyup="updateColor(this);" /></td><td style="background-color:<?php echo $prefarray['BGCOLOR']?>; border-style: groove;">
  &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td><td>
  <input type="button" onclick="selectColor('pref_BGCOLOR')" value="<?php etranslate("Select")?>..." />
 </td></tr>
 <tr><td>
  <label for="pref_h2"><?php etranslate("Document title")?>:</label></td><td>
  <input type="text" name="pref_H2COLOR" id="pref_h2" size="8" maxlength="7" value="<?php echo $prefarray['H2COLOR']; ?>" onkeyup="updateColor(this);" /></td><td style="background-color:<?php echo $prefarray['H2COLOR']?>; border-style: groove;">
 &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td><td>
  <input type="button" onclick="selectColor('pref_H2COLOR')" value="<?php etranslate("Select")?>..." />
 </td></tr>
 <tr><td>
 <label for="pref_TEXTCOLOR"><?php etranslate("Document text")?>:</label></td><td>
 <input type="text" name="pref_TEXTCOLOR" id="pref_TEXTCOLOR" size="8" maxlength="7" value="<?php echo $prefarray['TEXTCOLOR']; ?>" onkeyup="updateColor(this);" /><td style="background-color:<?php echo $prefarray['TEXTCOLOR']?>; border-style: groove;">
 &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td><td>
 <input type="button" onclick="selectColor('pref_TEXTCOLOR')" value="<?php etranslate("Select")?>..." name="" />
</td></tr>
 <tr><td>
  <label for="pref_MYEVENTS"><?php etranslate("My event text")?>:</label></td><td>
  <input type="text" name="pref_MYEVENTS" id="pref_MYEVENTS" size="8" maxlength="7" value="<?php echo $prefarray['MYEVENTS']; ?>" onkeyup="updateColor(this);" /></td><td style="background-color:<?php echo $prefarray['MYEVENTS']?>; border-style: groove;">
 &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td><td>
  <input type="button" onclick="selectColor('pref_MYEVENTS')" value="<?php etranslate("Select")?>..." />
 </td></tr>
 <tr><td>
  <label for="pref_TABLEBG"><?php etranslate("Table grid color")?>:</label></td><td>
  <input type="text" name="pref_TABLEBG" id="pref_TABLEBG" size="8" maxlength="7" value="<?php echo $prefarray['TABLEBG']; ?>" onkeyup="updateColor(this);" /></td><td style="background-color:<?php echo $prefarray['TABLEBG']?>; border-style: groove;">
 &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td><td>
  <input type="button" onclick="selectColor('pref_TABLEBG')" value="<?php etranslate("Select")?>..." />
 </td></tr>
 <tr><td>
  <label for="pref_TABLEBG"><?php etranslate("Table header background")?>:</label></td><td>
  <input type="text" name="pref_THBG" id="pref_THBG" size="8" maxlength="7" value="<?php echo $prefarray['THBG']; ?>" onkeyup="updateColor(this);" /></td><td style="background-color:<?php echo $prefarray['THBG']?>; border-style: groove;">
 &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td><td>
  <input type="button" onclick="selectColor('pref_THBG')" value="<?php etranslate("Select")?>..." />
 </td></tr>
 <tr><td>
  <label for="pref_TABLEBG"><?php etranslate("Table header text")?>:</label></td><td>
  <input type="text" name="pref_THFG" id="pref_THFG" size="8" maxlength="7" value="<?php echo $prefarray['THFG']; ?>" onkeyup="updateColor(this);" /></td><td style="background-color:<?php echo $prefarray['THFG']?>; border-style: groove;">
 &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td><td>
  <input type="button" onclick="selectColor('pref_THFG')" value="<?php etranslate("Select")?>..." />
 </td></tr>
 <tr><td>
  <label for="pref_cell"><?php etranslate("Table cell background")?>:</label></td><td>
  <input type="text" name="pref_CELLBG" id="pref_CELLBG" size="8" maxlength="7" value="<?php echo $prefarray['CELLBG']; ?>" onkeyup="updateColor(this);" /></td><td style="background-color:<?php echo $prefarray['CELLBG']?>; border-style: groove;">
 &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td><td>
  <input type="button" onclick="selectColor('pref_CELLBG')" value="<?php etranslate("Select")?>..." />
 </td></tr>
 <tr><td>
  <label for="pref_today"><?php etranslate("Table cell background for current day")?>:</label></td><td>
  <input type="text" name="pref_TODAYCELLBG" id="pref_TODAYCELLBG" size="8" maxlength="7" value="<?php echo $prefarray['TODAYCELLBG']; ?>" onkeyup="updateColor(this);" /></td><td style="background-color:<?php echo $prefarray['TODAYCELLBG']?>; border-style: groove;">
 &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td><td>
  <input type="button" onclick="selectColor('pref_TODAYCELLBG')" value="<?php etranslate("Select")?>..." />
 </td></tr>
 <tr><td>
 <label for="pref_HASEVENTSBG"><?php etranslate("Table cell background for days with events")?>:</label></td><td>
 <input type="text" name="pref_HASEVENTSBG" id="pref_HASEVENTSBG" size="8" maxlength="7" value="<?php echo $prefarray['HASEVENTSBG']; ?>" onkeyup="updateColor(this);" /></td><td class="sample" style="background-color:<?php echo $prefarray['HASEVENTSBG']?>;">
 &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td><td>
 <input type="button" onclick="selectColor('pref_HASEVENTSBG')" value="<?php etranslate("Select")?>..." name="" />
</td></tr>
 <tr><td>
  <label for="pref_wkend"><?php etranslate("Table cell background for weekends")?>:</label></td><td>
  <input type="text" name="pref_WEEKENDBG" id="pref_WEEKENDBG" size="8" maxlength="7" value="<?php echo $prefarray['WEEKENDBG']; ?>" onkeyup="updateColor(this);" /></td><td style="background-color:<?php echo $prefarray['WEEKENDBG']?>; border-style: groove;">
 &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td><td>
  <input type="button" onclick="selectColor('pref_WEEKENDBG')" value="<?php etranslate("Select")?>..." />
 </td></tr>
   <tr><td>
    <label for="pref_othmonth"><?php etranslate("Table cell background for other month")?>:</label></td><td>
  <input type="text" name="pref_OTHERMONTHBG" id="pref_OTHERMONTHBG" size="8" maxlength="7" value="<?php echo $prefarray['OTHERMONTHBG']; ?>" onkeyup="updateColor(this);" /></td><td style="background-color:<?php echo $prefarray['OTHERMONTHBG']?>; border-style: groove;">
  &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td><td>
    <input type="button" onclick="selectColor('pref_OTHERMONTHBG')" value="<?php etranslate("Select")?>..." />
  </td></tr>
<tr><td>
  <label for="pref_WEEKNUMBER"><?php etranslate("Week number color")?>:</label></td><td>
  <input type="text" name="pref_WEEKNUMBER" id="pref_WEEKNUMBER" size="8" maxlength="7" value="<?php echo $prefarray['WEEKNUMBER']; ?>" onkeyup="updateColor(this);" /></td><td class="sample" style="background-color:<?php echo $prefarray['WEEKNUMBER']?>;">
  &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td><td>
  <input type="button" onclick="selectColor('pref_WEEKNUMBER')" value="<?php etranslate("Select")?>..." name="" />
</td></tr>
   <tr><td>
    <label for="pref_othmonth"><?php etranslate("Event popup background")?>:</label></td><td>
  <input type="text" name="pref_POPUP_BG" id="pref_POPUP_BG" size="8" maxlength="7" value="<?php echo $prefarray['POPUP_BG']; ?>" onkeyup="updateColor(this);" /></td><td style="background-color:<?php echo $prefarray['POPUP_BG']?>; border-style: groove;">
  &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td><td>
    <input type="button" onclick="selectColor('pref_POPUP_BG')" value="<?php etranslate("Select")?>..." />
  </td></tr>
   <tr><td>
    <label for="pref_othmonth"><?php etranslate("Event popup text")?>:</label></td><td>
  <input type="text" name="pref_POPUP_FG" id="pref_POPUP_FG" size="8" maxlength="7" value="<?php echo $prefarray['POPUP_FG']; ?>" onkeyup="updateColor(this);" /></td><td style="background-color:<?php echo $prefarray['POPUP_FG']?>; border-style: groove;">
  &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td><td>
    <input type="button" onclick="selectColor('pref_POPUP_FG')" value="<?php etranslate("Select")?>..." />
  </td></tr>
</table>

</td><td style="text-align:center; vertical-align:top;;">
<br />
<!-- BEGIN EXAMPLE MONTH -->
<table style="border:0px; width:90%; background-color:<?php echo $BGCOLOR?>"><tr>
<td width="1%" rowspan="3">&nbsp;</td>
<td style="text-align:center; color:<?php 
  echo $H2COLOR?>; font-weight:bold;"><?php
  echo date_to_str ( date ("Ymd"), $DATE_FORMAT_MY, false );?></td>
<td width="1%" rowspan="3">&nbsp;</td></tr>
<tr><td bgcolor="<?php echo $BGCOLOR?>">
<?php 
set_today( date ("Ymd") );
display_month ( date ("m") , date('Y') , true);
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
<div style="border-width:0px;">
<input type="submit" value="<?php etranslate("Save Preferences")?>" name="" />
<br/><br/>
</div>
</form>

<?php print_trailer(); ?>
</body>
</html>
