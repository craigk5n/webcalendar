<?php
include_once 'includes/init.php';
include_once 'includes/date_formats.php';

// I know we've already loaded the global settings above, but read them
// in again and store them in a different place because they may have
// been superceded by local user preferences.
// We will store value in the array $s[].
$res = dbi_query ( "SELECT cal_setting, cal_value FROM webcal_config" );
$s = array ();
if ( $res ) {
  while ( $row = dbi_fetch_row ( $res ) ) {
    $setting = $row[0];
    $value = $row[1];
    $s[$setting] = $value;
    //echo "Setting '$setting' to '$value' <br />\n";
  }
  dbi_free_result ( $res );
}
//get list of theme files from /themes directory
$themes = array();
$dir = "themes";
if (is_dir($dir)) {
   if ($dh = opendir($dir)) {
       while (($file = readdir($dh)) !== false) {
         if ( strpos ( $file, "_admin.php" ) ) {
           $themes[0][] = strtoupper( str_replace ( "_admin.php", "", $file ) );
           $themes[1][] = strtoupper( str_replace ( ".php", "", $file ) );
        } else if ( strpos ( $file, "_pref.php" ) ) {
           $themes[0][] = strtolower( str_replace ( "_pref.php", "", $file ) );
           $themes[1][] = strtolower( str_replace ( ".php", "", $file ) );
        }
       }
       sort ( $themes );
       closedir($dh);
   }
}
//make globals values passed to styles.php are for this user
//makes the demo calendar accurate
$GLOBALS['TODAYCELLBG'] = $s['TODAYCELLBG'];
$GLOBALS['TABLEBG'] = $s['TABLEBG'];
$GLOBALS['TABLEBG'] = $s['TABLEBG'];
$GLOBALS['THBG'] = $s['THBG'];
$GLOBALS['CELLBG'] = $s['CELLBG'];
$GLOBALS['WEEKENDBG'] = $s['WEEKENDBG'];
$GLOBALS['OTHERMONTHBG'] = $s['OTHERMONTHBG'];

$BodyX = 'onload="public_handler(); eu_handler(); sr_handler(); attach_handler(); comment_handler(); email_handler();"';
$INC = array('js/admin.php','js/visible.php');
print_header ( $INC, '', $BodyX );
?>

<h2><?php etranslate("System Settings")?>&nbsp;<img src="help.gif" alt="<?php etranslate("Help")?>" class="help" onclick="window.open ( 'help_admin.php', 'cal_help', 'dependent,menubar,scrollbars,height=400,width=400,innerHeight=420,outerWidth=420');" /></h2>
<?php
$error = false;

if ( ! $is_admin ) {
  etranslate ( "You are not authorized" );
  $error = true;
}
if ( ! $error ) {
 echo "<a title=\"" . translate("Admin") . "\" class=\"nav\" href=\"adminhome.php\">&laquo;&nbsp;" . translate("Admin") . "</a><br /><br />\n";
}

if ( empty ( $ovrd ) && ! $error ) {
  echo "<blockquote>" . translate ( "Note" ) . ":&nbsp;" .
    "<a href=\"pref.php\">" .
    translate ( "Your user preferences" ) . "</a>&nbsp;" .
    translate ( "may be affecting the appearance of this page.") . "&nbsp;" .
    "<a href=\"admin.php?ovrd=1\">" .
    translate ( "Click here" ) . "&nbsp;" .
    translate ( "to not use your user preferences when viewing this page" ) .
    ".</a></blockquote>\n";
} else if ( ! $error ) {
  echo "<blockquote>" . translate ( "Note" ) . ":&nbsp;" .
    "<a href=\"pref.php\">" .
    translate ( "Your user preferences" ) . "</a>&nbsp;" .
    translate ( "are being ignored while viewing this page.") . "&nbsp;" .
    "<a href=\"admin.php\">" .
    translate ( "Click here" ) . "&nbsp;" .
    translate ( "to load your user preferences when viewing this page" ) .
    ".</a></blockquote>\n";
}

if ( ! $error ) {
?>

<form action="admin_handler.php" method="post" onsubmit="return valid_form(this);" name="prefform">
<table style="border-width:0px;"><tr><td>
 <input type="submit" value="<?php etranslate("Save")?>" name="" />
</td></tr></table>
<br />

<?php if ( ! empty ( $ovrd ) ) { ?>
  <input type="hidden" name="ovrd" id="ovrd" value="1" />
<?php } ?>

<!-- TABS -->
<div id="tabs">
 <span class="tabfor" id="tab_settings"><a href="#tabsettings" onclick="return showTab('settings')"><?php etranslate("Settings")?></a></span>
 <span class="tabbak" id="tab_public"><a href="#tabpublic" onclick="return showTab('public')"><?php etranslate("Public Access")?></a></span>
 <span class="tabbak" id="tab_uac"><a href="#tabuac" onclick="return showTab('uac')"><?php etranslate("User Access Control")?></a></span>
 <span class="tabbak" id="tab_groups"><a href="#tabgroups" onclick="return showTab('groups')"><?php etranslate("Groups")?></a></span>
 <span class="tabbak" id="tab_nonuser"><a href="#tabnonuser" onclick="return showTab('nonuser')"><?php etranslate("NonUser Calendars")?></a></span>
 <span class="tabbak" id="tab_other"><a href="#tabother" onclick="return showTab('other')"><?php etranslate("Other")?></a></span>
 <span class="tabbak" id="tab_email"><a href="#tabemail" onclick="return showTab('email')"><?php etranslate("Email")?></a></span>
 <span class="tabbak" id="tab_colors" title="<?php etooltip("colors-help")?>"><a href="#tabcolors" onclick="return showTab('colors')"><?php etranslate("Colors")?></a></span>
</div>

<!-- TABS BODY -->
<div id="tabscontent">
 <!-- DETAILS -->
 <a name="tabsettings"></a>
 <div id="tabscontent_settings">
 <table cellspacing="0" cellpadding="3">
 <tr><td class="tooltip" title="<?php etooltip("app-name-help")?>">
  <label for="admin_APPLICATION_NAME"><?php etranslate("Application Name")?>:</label></td><td>
  <input type="text" size="40" name="admin_APPLICATION_NAME" id="admin_APPLICATION_NAME" value="<?php 
   echo htmlspecialchars ( $APPLICATION_NAME );
  ?>" />
 </td></tr>
 <tr><td class="tooltip" title="<?php etooltip("server-url-help")?>">
  <label for="admin_SERVER_URL"><?php etranslate("Server URL")?>:</label></td><td>
  <input type="text" size="40" name="admin_SERVER_URL" id="admin_SERVER_URL" value="<?php 
   echo htmlspecialchars ( $SERVER_URL );
  ?>" />
 </td></tr>
 <tr><td class="tooltip" title="<?php etooltip("home-url-help")?>">
  <label for="admin_HOME_LINK"><?php etranslate("Home URL")?>:</label></td><td>
  <input type="text" size="40" name="admin_HOME_LINK" id="admin_HOME_LINK" value="<?php 
   if ( ! empty ( $HOME_LINK ) ) echo htmlspecialchars ( $HOME_LINK );
  ?>" />
 </td></tr>
 <tr><td class="tooltipselect" title="<?php etooltip("language-help");?>">
  <label for="admin_LANGUAGE"><?php etranslate("Language")?>:</label></td><td>
  <select name="admin_LANGUAGE" id="admin_LANGUAGE">
   <?php
    reset ( $languages );
    while ( list ( $key, $val ) = each ( $languages ) ) {
     echo "<option value=\"" . $val . "\"";
     if ( $val == $s['LANGUAGE'] ) echo " selected=\"selected\"";
     echo ">" . translate ( $key ) . "</option>\n";
    }
   ?>
  </select><br />
  <?php etranslate("Your browser default language is"); echo " "; etranslate(get_browser_language()); echo "."; ?>
 </td></tr>
<tr><td><label>
 <?php etranslate("Allow user to use themes")?>:</label></td><td colspan="3">
 <label><input type="radio" name="admin_ALLOW_USER_THEMES" value="Y"<?php if ( $s["ALLOW_USER_THEMES"] != "N" ) echo " checked=\"checked\"";?> />&nbsp;<?php etranslate("Yes")?></label>&nbsp;
 <label><input type="radio" name="admin_ALLOW_USER_THEMES" value="N"<?php if ( $s["ALLOW_USER_THEMES"] == "N" ) echo " checked=\"checked\"";?> />&nbsp;<?php etranslate("No")?></label>
</td></tr> 
 <tr><td  class="tooltip" title="<?php etooltip("themes-help");?>">
 <label for="admin_THEME"><?php etranslate("Themes")?>:</label></td><td>
 <select name="admin_THEME" id="admin_THEME">
<?php
  echo "<option disabled>" . translate("AVAILABLE THEMES") . "</option>\n";
  //always use 'none' as default so we don't overwrite manual settings
  echo "<option  value=\"none\" selected=\"selected\">" . translate("None") . "</option>\n";
  for ( $i = 0; $i <= count ( $themes); $i++ ) {
     echo "<option value=\"" . $themes[1][$i] . "\">" . $themes[0][$i] . "</option>\n";
  }
?>
 </select>&nbsp;&nbsp;&nbsp;
 <input type="button" name="preview" value="<?php etranslate ( "Preview" ) ?>" onclick="return showPreview()" />
 </td></tr> 
 <tr><td class="tooltip" title="<?php etooltip("fonts-help") ?>">
  <label for="admin_FONTS"><?php etranslate("Fonts")?>:</label></td><td>
  <input type="text" size="40" name="admin_FONTS" id="admin_FONTS" value="<?php 
            echo htmlspecialchars ( $FONTS );
           ?>" />
 </td></tr>

 <tr><td class="tooltip" title="<?php etooltip("custom-script-help");?>">
  <?php etranslate("Custom script/stylesheet")?>:</td><td>
  <label><input type="radio" name="admin_CUSTOM_SCRIPT" value="Y"<?php if ( $s["CUSTOM_SCRIPT"] == "Y" ) echo " checked=\"checked\"";?> />&nbsp;<?php etranslate("Yes")?></label>&nbsp;
  <label><input type="radio" name="admin_CUSTOM_SCRIPT" value="N"<?php if ( $s["CUSTOM_SCRIPT"] != "Y" ) echo " checked=\"checked\"";?> />&nbsp;<?php etranslate("No")?></label>&nbsp;&nbsp;
  <input type="button" value="<?php etranslate("Edit");?>..." onclick="window.open('edit_template.php?type=S','cal_template','dependent,menubar,scrollbars,height=500,width=500,outerHeight=520,outerWidth=520');" name="" />
 </td></tr>
 <tr><td class="tooltip" title="<?php etooltip("custom-header-help");?>">
  <?php etranslate("Custom header")?>:</td><td>
  <label><input type="radio" name="admin_CUSTOM_HEADER" value="Y"<?php if ( $s["CUSTOM_HEADER"] == "Y" ) echo " checked=\"checked\"";?> />&nbsp;<?php etranslate("Yes")?></label>&nbsp;
  <label><input type="radio" name="admin_CUSTOM_HEADER" value="N"<?php if ( $s["CUSTOM_HEADER"] != "Y" ) echo " checked=\"checked\"";?> />&nbsp;<?php etranslate("No")?></label>&nbsp;&nbsp;
  <input type="button" value="<?php etranslate("Edit");?>..." onclick="window.open('edit_template.php?type=H','cal_template','dependent,menubar,scrollbars,height=500,width=500,outerHeight=520,outerWidth=520');" name="" />
 </td></tr>
 <tr><td class="tooltip" title="<?php etooltip("custom-trailer-help");?>">
  <?php etranslate("Custom trailer")?>:</td><td>
  <label><input type="radio" name="admin_CUSTOM_TRAILER" value="Y"<?php if ( $s["CUSTOM_TRAILER"] == "Y" ) echo " checked=\"checked\"";?> />&nbsp;<?php etranslate("Yes")?></label>&nbsp;
  <label><input type="radio" name="admin_CUSTOM_TRAILER" value="N"<?php if ( $s["CUSTOM_TRAILER"] != "Y" ) echo " checked=\"checked\"";?> />&nbsp;<?php etranslate("No")?></label>&nbsp;&nbsp;
  <input type="button" value="<?php etranslate("Edit");?>..." onclick="window.open('edit_template.php?type=T','cal_template','dependent,menubar,scrollbars,height=500,width=500,outerHeight=520,outerWidth=520');" name="" />
 </td></tr>

 <tr><td class="tooltip" title="<?php etooltip("enable-external-header-help");?>">
  <?php etranslate("Allow external file for header/script/trailer")?>:</td><td>
  <label><input type="radio" name="admin_ALLOW_EXTERNAL_HEADER" value="Y"<?php if ( $s["ALLOW_EXTERNAL_HEADER"] == "Y" ) echo " checked=\"checked\"";?> />&nbsp;<?php etranslate("Yes")?></label>&nbsp;
  <label><input type="radio" name="admin_ALLOW_EXTERNAL_HEADER" value="N"<?php if ( $s["ALLOW_EXTERNAL_HEADER"] != "Y" ) echo " checked=\"checked\"";?> />&nbsp;<?php etranslate("No")?></label>
 </td></tr>

<tr><td><label>
 <?php etranslate("Allow user to override header/trailer")?>:</label></td><td colspan="3">
 <label><input type="radio" name="admin_ALLOW_USER_HEADER" value="Y"<?php if ( $s["ALLOW_USER_HEADER"] != "N" ) echo " checked=\"checked\"";?> />&nbsp;<?php etranslate("Yes")?></label>&nbsp;
 <label><input type="radio" name="admin_ALLOW_USER_HEADER" value="N"<?php if ( $s["ALLOW_USER_HEADER"] == "N" ) echo " checked=\"checked\"";?> />&nbsp;<?php etranslate("No")?></label>
</td></tr>

 <tr><td class="tooltip" title="<?php etooltip("preferred-view-help");?>">
  <label for="admin_STARTVIEW"><?php etranslate("Preferred view")?>:</label></td><td>
<select name="admin_STARTVIEW" id="admin_STARTVIEW">
<?php
$choices = array ( "day.php", "week.php", "month.php", "year.php" );
$choices_text = array ( translate ( "Day" ), translate ( "Week" ),
  translate ( "Month" ), translate ( "Year" ) );

for ( $i = 0; $i < count ( $choices ); $i++ ) {
  echo "<option value=\"" . $choices[$i] . "\" ";
  if ( $s['STARTVIEW'] == $choices[$i] )
    echo " selected=\"selected\"";
  echo " >" . $choices_text[$i] . "</option>\n";
}

// Allow user to select a view also
for ( $i = 0; $i < count ( $views ); $i++ ) {
  if ( $views[$i]['cal_is_global'] != 'Y' )
    continue;
  $xurl = $views[$i]['url'];
  echo "<option value=\"";
  echo $xurl . "\" ";
  $xurl_strip = str_replace ( "&amp;", "&", $xurl );
  if ( $s['STARTVIEW'] == $xurl_strip )
    echo "selected=\"selected\" ";
  echo ">" . $views[$i]['cal_name'] . "</option>\n";
}
?>
</select>
 </td></tr>
  <tr><td class="tooltip" title="<?php etooltip("display-sm_month-help");?>">
  <?php etranslate("Display small months")?>:</td><td>
  <label><input type="radio" name="admin_DISPLAY_SM_MONTH" value="Y" <?php if ( $s["DISPLAY_SM_MONTH"] != "N" ) echo " checked=\"checked\"";?> />&nbsp;<?php etranslate("Yes")?></label>&nbsp;
  <label><input type="radio" name="admin_DISPLAY_SM_MONTH" value="N" <?php if ( $s["DISPLAY_SM_MONTH"] == "N" ) echo " checked=\"checked\"";?> />&nbsp;<?php etranslate("No")?></label>
 </td></tr>
 <tr><td class="tooltip" title="<?php etooltip("display-weekends-help");?>">
  <?php etranslate("Display weekends")?>:</td><td>
  <label><input type="radio" name="admin_DISPLAY_WEEKENDS" value="Y" <?php if ( $s["DISPLAY_WEEKENDS"] != "N" ) echo " checked=\"checked\"";?> />&nbsp;<?php etranslate("Yes")?></label>&nbsp;
  <label><input type="radio" name="admin_DISPLAY_WEEKENDS" value="N" <?php if ( $s["DISPLAY_WEEKENDS"] == "N" ) echo " checked=\"checked\"";?> />&nbsp;<?php etranslate("No")?></label>
 </td></tr>
 <tr><td class="tooltip" title="<?php etooltip("display-alldays-help");?>">
  <?php etranslate("Display all days in month view")?>:</td><td>
  <label><input type="radio" name="admin_DISPLAY_ALL_DAYS_IN_MONTH" value="Y" <?php if ( $s["DISPLAY_ALL_DAYS_IN_MONTH"] != "N" ) echo " checked=\"checked\"";?> />&nbsp;<?php etranslate("Yes")?></label>&nbsp;
  <label><input type="radio" name="admin_DISPLAY_ALL_DAYS_IN_MONTH" value="N" <?php if ( $s["DISPLAY_ALL_DAYS_IN_MONTH"] == "N" ) echo " checked=\"checked\"";?> />&nbsp;<?php etranslate("No")?></label>
 </td></tr>  
 <tr><td class="tooltip" title="<?php etooltip("yearly-shows-events-help");?>">
  <?php etranslate("Display days with events in bold in month and year views")?>:</td><td>
  <label><input type="radio" name="admin_BOLD_DAYS_IN_YEAR" value="Y" <?php if ( $s["BOLD_DAYS_IN_YEAR"] == "Y" ) echo " checked=\"checked\"";?> />&nbsp;<?php etranslate("Yes")?></label>&nbsp;
  <label><input type="radio" name="admin_BOLD_DAYS_IN_YEAR" value="N" <?php if ( $s["BOLD_DAYS_IN_YEAR"] != "Y" ) echo " checked=\"checked\"";?> />&nbsp;<?php etranslate("No")?></label>
 </td></tr>
 <tr><td class="tooltip" title="<?php etooltip("display-desc-print-day-help");?>">
  <?php etranslate("Display description in printer day view")?>:</td><td>
  <label><input type="radio" name="admin_DISPLAY_DESC_PRINT_DAY" value="Y" <?php if ( $s["DISPLAY_DESC_PRINT_DAY"] == "Y" ) echo " checked=\"checked\"";?> />&nbsp;<?php etranslate("Yes")?></label>&nbsp;
  <label><input type="radio" name="admin_DISPLAY_DESC_PRINT_DAY" value="N" <?php if ( $s["DISPLAY_DESC_PRINT_DAY"] != "Y" ) echo " checked=\"checked\"";?> />&nbsp;<?php etranslate("No")?></label>
 </td></tr>
 <tr><td class="tooltipselect" title="<?php etooltip("tz-help")?>">
  <label for="admin_SERVER_TIMEZONE"><?php etranslate("Server Timezone Selection")?>:</label></td><td>
  <?php
   $tz_offset = get_tz_offset ( $s['SERVER_TIMEZONE'], time() );
   echo print_timezone_select_html ( "admin_", $s['SERVER_TIMEZONE']);
   echo  translate("Your current GMT offset is")  . " " . $tz_offset[0] . " " . translate("hours") . ".";
  ?>&nbsp;&nbsp;
 <input type="button" value="<?php etranslate("Load Timezone Data");?>" onclick="window.open('load_tz_data.php','_blank','dependent,height=200,width=400,outerHeight=220,outerWidth=420');" name="" />
</td></tr>
 <tr><td class="tooltipselect" title="<?php etooltip("date-format-help");?>">
  <?php etranslate("Date format")?>:</td><td>
  <select name="admin_DATE_FORMAT">
   <?php
    for ( $i = 0; $i < count ( $datestyles ); $i += 2 ) {
     echo "<option value=\"" . $datestyles[$i] . "\"";
     if ( $s["DATE_FORMAT"] == $datestyles[$i] )
      echo " selected=\"selected\"";
     echo ">" . $datestyles[$i + 1] . "</option>\n";
    }
   ?>
  </select><br />

  <select name="admin_DATE_FORMAT_MY">
   <?php
    for ( $i = 0; $i < count ( $datestyles_my ); $i += 2 ) {
     echo "<option value=\"" . $datestyles_my[$i] . "\"";
     if ( $s["DATE_FORMAT_MY"] == $datestyles_my[$i] )
      echo " selected=\"selected\"";
     echo ">" . $datestyles_my[$i + 1] . "</option>\n";
    }
   ?>
  </select><br />

  <select name="admin_DATE_FORMAT_MD">
   <?php
    for ( $i = 0; $i < count ( $datestyles_md ); $i += 2 ) {
     echo "<option value=\"" . $datestyles_md[$i] . "\"";
     if ( $s["DATE_FORMAT_MD"] == $datestyles_md[$i] )
      echo " selected=\"selected\"";
     echo ">" . $datestyles_md[$i + 1] . "</option>\n";
    }
   ?>
  </select>
 </td></tr>
 <tr><td class="tooltip" title="<?php etooltip("time-format-help")?>">
  <?php etranslate("Time format")?>:</td><td>
  <label><input type="radio" name="admin_TIME_FORMAT" value="12" <?php if ( $s["TIME_FORMAT"] == "12" ) echo " checked=\"checked\"";?> />&nbsp;<?php etranslate("12 hour")?></label>&nbsp;
  <label><input type="radio" name="admin_TIME_FORMAT" value="24" <?php if ( $s["TIME_FORMAT"] != "12" ) echo " checked=\"checked\"";?> />&nbsp;<?php etranslate("24 hour")?></label>
 </td></tr>
 <tr><td class="tooltip" title="<?php etooltip("time-interval-help")?>">
  <label for="admin_TIME_SLOTS"><?php etranslate("Time interval")?>:</label></td><td>
  <select name="admin_TIME_SLOTS" id="admin_TIME_SLOTS">
   <option value="24" <?php if ( $s["TIME_SLOTS"] == "24" ) echo " selected=\"selected\""?>>1 <?php etranslate("hour")?></option>
   <option value="48" <?php if ( $s["TIME_SLOTS"] == "48" ) echo " selected=\"selected\""?>>30 <?php etranslate("minutes")?></option>
   <option value="72" <?php if ( $s["TIME_SLOTS"] == "72" ) echo " selected=\"selected\""?>>20 <?php etranslate("minutes")?></option>
   <option value="96" <?php if ( $s["TIME_SLOTS"] == "96" ) echo " selected=\"selected\""?>>15 <?php etranslate("minutes")?></option>
   <option value="144" <?php if ( $s["TIME_SLOTS"] == "144" ) echo " selected=\"selected\""?>>10 <?php etranslate("minutes")?></option>
  </select>
 </td></tr>
 <tr><td class="tooltip" title="<?php etooltip("auto-refresh-help");?>">
  <?php etranslate("Auto-refresh calendars")?>:</td><td>
  <label><input type="radio" name="admin_AUTO_REFRESH" value="Y" <?php if ( $s["AUTO_REFRESH"] == "Y" ) echo " checked=\"checked\"";?> />&nbsp;<?php etranslate("Yes")?></label>&nbsp;
  <label><input type="radio" name="admin_AUTO_REFRESH" value="N" <?php if ( $s["AUTO_REFRESH"] != "Y" ) echo " checked=\"checked\"";?> />&nbsp;<?php etranslate("No")?></label>
 </td></tr>
 <tr><td class="tooltip" title="<?php etooltip("auto-refresh-time-help");?>">
  &nbsp;&nbsp;&nbsp;&nbsp;<?php etranslate("Auto-refresh time")?>:</td><td>
  <input type="text" name="admin_AUTO_REFRESH_TIME" size="4" value="<?php if ( empty ( $s["AUTO_REFRESH_TIME"] ) ) echo "0"; else echo $s["AUTO_REFRESH_TIME"]; ?>" />&nbsp;<?php etranslate("minutes")?>
 </td></tr>
 <tr><td class="tooltip" title="<?php etooltip("require-approvals-help");?>">
  <?php etranslate("Require event approvals")?>:</td><td>
  <label><input type="radio" name="admin_REQUIRE_APPROVALS" value="Y" <?php if ( $s["REQUIRE_APPROVALS"] != "N" ) echo " checked=\"checked\"";?> />&nbsp;<?php etranslate("Yes")?></label>&nbsp;
  <label><input type="radio" name="admin_REQUIRE_APPROVALS" value="N" <?php if ( $s["REQUIRE_APPROVALS"] == "N" ) echo " checked=\"checked\"";?> />&nbsp;<?php etranslate("No")?></label>
 </td></tr>
 <tr><td class="tooltip" title="<?php etooltip("display-unapproved-help");?>">
  &nbsp;&nbsp;&nbsp;&nbsp;<?php etranslate("Display unapproved")?>:</td><td>
  <label><input type="radio" name="admin_DISPLAY_UNAPPROVED" value="Y" <?php if ( $s["DISPLAY_UNAPPROVED"] != "N" ) echo " checked=\"checked\"";?> />&nbsp;<?php etranslate("Yes")?></label>&nbsp;
  <label><input type="radio" name="admin_DISPLAY_UNAPPROVED" value="N" <?php if ( $s["DISPLAY_UNAPPROVED"] == "N" ) echo " checked=\"checked\"";?> />&nbsp;<?php etranslate("No")?></label>
 </td></tr>
 <tr><td class="tooltip" title="<?php etooltip("display-week-number-help")?>">
  <?php etranslate("Display week number")?>:</td><td>
  <label><input type="radio" name="admin_DISPLAY_WEEKNUMBER" value="Y" <?php if ( $s["DISPLAY_WEEKNUMBER"] != "N" ) echo " checked=\"checked\"";?> />&nbsp;<?php etranslate("Yes")?></label>&nbsp;
  <label><input type="radio" name="admin_DISPLAY_WEEKNUMBER" value="N" <?php if ( $s["DISPLAY_WEEKNUMBER"] == "N" ) echo " checked=\"checked\"";?> />&nbsp;<?php etranslate("No")?></label>
 </td></tr>
 <tr><td class="tooltip" title="<?php etooltip("display-week-starts-on")?>">
  <?php etranslate("Week starts on")?>:</td><td>
  <label><input type="radio" name="admin_WEEK_START" value="0" <?php if ( $s["WEEK_START"] != "1" ) echo " checked=\"checked\"";?> />&nbsp;<?php etranslate("Sunday")?></label>&nbsp;
  <label><input type="radio" name="admin_WEEK_START" value="1" <?php if ( $s["WEEK_START"] == "1" ) echo " checked=\"checked\"";?> />&nbsp;<?php etranslate("Monday")?></label>
 </td></tr>
 <tr><td class="tooltip" title="<?php etooltip("work-hours-help")?>">
  <?php etranslate("Work hours")?>:</td><td>
  <label for="admin_WORK_DAY_START_HOUR"><?php etranslate("From")?>&nbsp;</label>
  <select name="admin_WORK_DAY_START_HOUR" id="admin_WORK_DAY_START_HOUR">
   <?php
    for ( $i = 0; $i < 24; $i++ ) {
     echo "<option value=\"$i\"" .
      ( $i == $s["WORK_DAY_START_HOUR"] ? " selected=\"selected\"" : "" ) .
     ">" . display_time ( $i * 10000, 1 ) . "</option>\n";
    }
   ?>
  </select>&nbsp;
  <label for="admin_WORK_DAY_END_HOUR"><?php etranslate("to")?>&nbsp;</label>
  <select name="admin_WORK_DAY_END_HOUR" id="admin_WORK_DAY_END_HOUR">
   <?php
    for ( $i = 0; $i <= 24; $i++ ) {
     echo "<option value=\"$i\"" .
      ( $i == $s["WORK_DAY_END_HOUR"] ? " selected=\"selected\"" : "" ) .
     ">" . display_time ( $i * 10000, 1 ) . "</option>\n";
    }
   ?>
  </select>
 </td></tr>
  <tr><td class="tooltip" title="<?php etooltip("disable-popups-help")?>">
  <?php etranslate("Disable Pop-Ups")?>:</td><td>
  <label><input type="radio" name="admin_DISABLE_POPUPS" value="Y"<?php if ( $s["DISABLE_POPUPS"] != "N" ) echo " checked=\"checked\"";?> />&nbsp;<?php etranslate("Yes")?></label> 
  <label><input type="radio" name="admin_DISABLE_POPUPS" value="N"<?php if ( $s["DISABLE_POPUPS"] == "N" ) echo " checked=\"checked\"";?> />&nbsp;<?php etranslate("No")?></label>
 </td></tr>
  <tr><td class="tooltip" title="<?php etooltip("disable-location-field-help")?>">
  <?php etranslate("Disable Location field")?>:</td><td>
  <label><input type="radio" name="admin_DISABLE_LOCATION_FIELD" value="Y"<?php if ( $s["DISABLE_LOCATION_FIELD"] != "N" ) echo " checked=\"checked\"";?> />&nbsp;<?php etranslate("Yes")?></label> 
  <label><input type="radio" name="admin_DISABLE_LOCATION_FIELD" value="N"<?php if ( $s["DISABLE_LOCATION_FIELD"] == "N" ) echo " checked=\"checked\"";?> />&nbsp;<?php etranslate("No")?></label>
 </td></tr>
 <tr><td class="tooltip" title="<?php etooltip("disable-priority-field-help")?>">
  <?php etranslate("Disable Priority field")?>:</td><td>
  <label><input type="radio" name="admin_DISABLE_PRIORITY_FIELD" value="Y"<?php if ( $s["DISABLE_PRIORITY_FIELD"] != "N" ) echo " checked=\"checked\"";?> />&nbsp;<?php etranslate("Yes")?></label> 
  <label><input type="radio" name="admin_DISABLE_PRIORITY_FIELD" value="N"<?php if ( $s["DISABLE_PRIORITY_FIELD"] == "N" ) echo " checked=\"checked\"";?> />&nbsp;<?php etranslate("No")?></label>
 </td></tr>
 <tr><td class="tooltip" title="<?php etooltip("disable-access-field-help")?>">
  <?php etranslate("Disable Access field")?>:</td><td>
  <label><input type="radio" name="admin_DISABLE_ACCESS_FIELD" value="Y"<?php if ( $s["DISABLE_ACCESS_FIELD"] != "N" ) echo " checked=\"checked\"";?> />&nbsp;<?php etranslate("Yes")?></label> 
  <label><input type="radio" name="admin_DISABLE_ACCESS_FIELD" value="N"<?php if ( $s["DISABLE_ACCESS_FIELD"] == "N" ) echo " checked=\"checked\"";?> />&nbsp;<?php etranslate("No")?></label>
 </td></tr>
 <tr><td class="tooltip" title="<?php etooltip("disable-participants-field-help")?>">
  <?php etranslate("Disable Participants field")?>:</td><td>
  <label><input type="radio" name="admin_DISABLE_PARTICIPANTS_FIELD" value="Y" <?php if ( $s["DISABLE_PARTICIPANTS_FIELD"] != "N" ) echo " checked=\"checked\"";?> />&nbsp;<?php etranslate("Yes")?></label> 
  <label><input type="radio" name="admin_DISABLE_PARTICIPANTS_FIELD" value="N" <?php if ( $s["DISABLE_PARTICIPANTS_FIELD"] == "N" ) echo " checked=\"checked\"";?> />&nbsp;<?php etranslate("No")?></label>
 </td></tr>
 <tr><td class="tooltip" title="<?php etooltip("disable-repeating-field-help")?>">
  <?php etranslate("Disable Repeating field")?>:</td><td>
  <label><input type="radio" name="admin_DISABLE_REPEATING_FIELD" value="Y" <?php if ( $s["DISABLE_REPEATING_FIELD"] != "N" ) echo " checked=\"checked\"";?> />&nbsp;<?php etranslate("Yes")?></label> 
  <label><input type="radio" name="admin_DISABLE_REPEATING_FIELD" value="N" <?php if ( $s["DISABLE_REPEATING_FIELD"] == "N" ) echo " checked=\"checked\"";?> />&nbsp;<?php etranslate("No")?></label>
 </td></tr>
 <tr><td class="tooltip" title="<?php etooltip("popup-includes-siteextras-help")?>">
  <?php etranslate("Display Site Extras in popup")?>:</td><td>
  <label><input type="radio" name="admin_SITE_EXTRAS_IN_POPUP" value="Y" <?php if ( $s["SITE_EXTRAS_IN_POPUP"] == "Y" ) echo " checked=\"checked\"";?> />&nbsp;<?php etranslate("Yes")?></label> 
  <label><input type="radio" name="admin_SITE_EXTRAS_IN_POPUP" value="N" <?php if ( $s["SITE_EXTRAS_IN_POPUP"] != "Y" ) echo " checked=\"checked\"";?> />&nbsp;<?php etranslate("No")?></label>
 </td></tr>
 <tr><td class="tooltip" title="<?php etooltip("allow-html-description-help")?>">
  <?php etranslate("Allow HTML in Description")?>:</td><td>
  <label><input type="radio" name="admin_ALLOW_HTML_DESCRIPTION" value="Y" <?php if ( $s["ALLOW_HTML_DESCRIPTION"] == "Y" ) echo " checked=\"checked\"";?> />&nbsp;<?php etranslate("Yes")?></label> 
  <label><input type="radio" name="admin_ALLOW_HTML_DESCRIPTION" value="N" <?php if ( $s["ALLOW_HTML_DESCRIPTION"] != "Y" ) echo " checked=\"checked\"";?> />&nbsp;<?php etranslate("No")?></label>
 </td></tr>
 <tr><td class="tooltip" title="<?php etooltip("allow-view-other-help")?>">
  <?php etranslate("Allow viewing other user's calendars")?>:</td><td>
  <label><input type="radio" name="admin_ALLOW_VIEW_OTHER" value="Y" <?php if ( $s["ALLOW_VIEW_OTHER"] != "N" ) echo " checked=\"checked\"";?> />&nbsp;<?php etranslate("Yes")?></label>&nbsp;
  <label><input type="radio" name="admin_ALLOW_VIEW_OTHER" value="N" <?php if ( $s["ALLOW_VIEW_OTHER"] == "N" ) echo " checked=\"checked\"";?> />&nbsp;<?php etranslate("No")?></label>
 </td></tr>
 <tr><td class="tooltip" title="<?php etooltip("allow-view-add-help")?>">
  <?php etranslate("Include add event link in views")?>:</td><td>
  <label><input type="radio" name="admin_ADD_LINK_IN_VIEWS" value="Y" <?php if ( $s["ADD_LINK_IN_VIEWS"] != "N" ) echo " checked=\"checked\"";?> />&nbsp;<?php etranslate("Yes")?></label>&nbsp;
  <label><input type="radio" name="admin_ADD_LINK_IN_VIEWS" value="N" <?php if ( $s["ADD_LINK_IN_VIEWS"] == "N" ) echo " checked=\"checked\"";?> />&nbsp;<?php etranslate("No")?></label>
 </td></tr>
 <tr><td class="tooltip" title="<?php etooltip("remember-last-login-help")?>">
  <?php etranslate("Remember last login")?>:</td><td>
  <label><input type="radio" name="admin_REMEMBER_LAST_LOGIN" value="Y" <?php if ( $s["REMEMBER_LAST_LOGIN"] != "N" ) echo " checked=\"checked\"";?> />&nbsp;<?php etranslate("Yes")?></label>&nbsp;
  <label><input type="radio" name="admin_REMEMBER_LAST_LOGIN" value="N" <?php if ( $s["REMEMBER_LAST_LOGIN"] == "N" ) echo " checked=\"checked\"";?> />&nbsp;<?php etranslate("No")?></label>
 </td></tr>
 <tr><td class="tooltip" title="<?php etooltip("conflict-check-help")?>">
  <?php etranslate("Check for event conflicts")?>:</td><td>
  <label><input type="radio" name="admin_ALLOW_CONFLICTS" value="N" <?php if ( $s["ALLOW_CONFLICTS"] == "N" ) echo " checked=\"checked\"";?> />&nbsp;<?php etranslate("Yes")?></label> 
  <label><input type="radio" name="admin_ALLOW_CONFLICTS" value="Y" <?php if ( $s["ALLOW_CONFLICTS"] != "N" ) echo " checked=\"checked\"";?> />&nbsp;<?php etranslate("No")?></label>
 </td></tr>
 <tr><td class="tooltip" title="<?php etooltip("conflict-months-help")?>">
  &nbsp;&nbsp;&nbsp;&nbsp;<?php etranslate("Conflict checking months")?>:</td><td>
  <input type="text" size="3" name="admin_CONFLICT_REPEAT_MONTHS" value="<?php echo htmlspecialchars ( $CONFLICT_REPEAT_MONTHS );?>" />
 </td></tr>
 <tr><td class="tooltip" title="<?php etooltip("conflict-check-override-help")?>">
  &nbsp;&nbsp;&nbsp;&nbsp;<?php etranslate("Allow users to override conflicts")?>:</td><td>
  <label><input type="radio" name="admin_ALLOW_CONFLICT_OVERRIDE" value="Y" <?php if ( $s["ALLOW_CONFLICT_OVERRIDE"] == "Y" ) echo " checked=\"checked\"";?> />&nbsp;<?php etranslate("Yes")?></label> 
  <label><input type="radio" name="admin_ALLOW_CONFLICT_OVERRIDE" value="N" <?php if ( $s["ALLOW_CONFLICT_OVERRIDE"] != "Y" ) echo " checked=\"checked\"";?> />&nbsp;<?php etranslate("No")?></label>
 </td></tr>
 <tr><td class="tooltip" title="<?php etooltip("limit-appts-help")?>">
  <?php etranslate("Limit number of timed events per day")?>:</td><td>
  <label><input type="radio" name="admin_LIMIT_APPTS" value="Y" <?php if ( $s["LIMIT_APPTS"] == "Y" ) echo " checked=\"checked\"";?> />&nbsp;<?php etranslate("Yes")?></label> 
  <label><input type="radio" name="admin_LIMIT_APPTS" value="N" <?php if ( $s["LIMIT_APPTS"] != "Y" ) echo " checked=\"checked\"";?> />&nbsp;<?php etranslate("No")?></label>
 </td></tr>
 <tr><td class="tooltip" title="<?php etooltip("limit-appts-number-help")?>">
  &nbsp;&nbsp;&nbsp;&nbsp;<?php etranslate("Maximum timed events per day")?>:</td><td>
  <input type="text" size="3" name="admin_LIMIT_APPTS_number" value="<?php echo htmlspecialchars ( $LIMIT_APPTS_NUMBER );?>" />
 </td></tr>
 <tr><td class="tooltip" title="<?php etooltip("timed-evt-len-help")?>">
  <?php etranslate("Specify timed event length by")?>:</td><td>
  <label><input type="radio" name="admin_TIMED_EVT_LEN" value="D" <?php if ( $s["TIMED_EVT_LEN"] != "E" ) echo " checked=\"checked\"";?> />&nbsp;<?php etranslate("Duration")?></label> 
  <label><input type="radio" name="admin_TIMED_EVT_LEN" value="E" <?php if ( $s["TIMED_EVT_LEN"] == "E" ) echo " checked=\"checked\"";?> />&nbsp;<?php etranslate("End Time")?></label>
 </td></tr>
<tr><td class="tooltip" title="<?php etooltip("summary_length-help")?>">
  <?php etranslate("Brief Description Length")?>:</td><td>
  <input type="text" size="3" name="admin_SUMMARY_LENGTH" value="<?php echo $SUMMARY_LENGTH;?>" />
 </td></tr>
</table>
</div>
<!-- END SETTINGS -->


<!--
// <h3><?php etranslate("Plugins")?></h3>
// <table class="standard" cellspacing="1" cellpadding="2">
// <tr><td class="tooltip" title="<?php etooltip("plugins-enabled-help");?>"><?php etranslate("Enable Plugins")?>:</td>
//   <td><label><input type="radio" name="admin_PLUGINS_ENABLED" value="Y" <?php if ( $s["PLUGINS_ENABLED"] == "Y" ) echo " checked=\"checked\"";?> />&nbsp;<?php etranslate("Yes")?></label>&nbsp;
//       <label><input type="radio" name="admin_PLUGINS_ENABLED" value="N" <?php if ( $s["PLUGINS_ENABLED"] != "Y" ) echo " checked=\"checked\"";?> />&nbsp;<?php etranslate("No")?></label>
//</td></tr>

// <?php
// if ( $PLUGINS_ENABLED == "Y" ) {
//   $plugins = get_plugin_list ( true );

//   for ( $i = 0; $i < count ( $plugins ); $i++ ) {
//     $val = $s[$plugins[$i] . ".plugin_status"];
//     echo "<tr><td class=\"tooltip\" title=\"" .
//       tooltip("plugins-sort-key-help") . "\">&nbsp;&nbsp;&nbsp;" .
//       translate("Plugin") . " " . $plugins[$i] . ":</td>\n";
//     echo "<td><input type=\"radio\" name=\"admin_" .
//        $plugins[$i] . "_plugin_status\" value=\"Y\" ";
//     if ( $val != "N" ) echo " checked=\"checked\"";
//     echo " /> " . translate("Yes");
//     echo "<input type=\"radio\" name=\"admin_" .
//        $plugins[$i] . "_plugin_status\" VALUE=\"N\" ";
//     if ( $val == "N" ) echo " checked=\"checked\"";
//     echo " /> " . translate("No") . "</td></tr>\n";
//   }
// }
// ?>
//</table>
-->

<!-- BEGIN PUBLIC ACCESS -->
<a name="tabpublic"></a>
<div id="tabscontent_public">
 <table cellspacing="0" cellpadding="3">
  <tr><td class="tooltip" title="<?php etooltip("allow-public-access-help")?>">
   <?php etranslate("Allow public access")?>:</td><td>
   <label><input type="radio" name="admin_PUBLIC_ACCESS" value="Y" <?php if ( $s["PUBLIC_ACCESS"] == "Y" ) echo " checked=\"checked\"";?> onclick="public_handler()" />&nbsp;<?php etranslate("Yes")?></label>&nbsp;
   <label><input type="radio" name="admin_PUBLIC_ACCESS" value="N" <?php if ( $s["PUBLIC_ACCESS"] != "Y" ) echo " checked=\"checked\"";?> onclick="public_handler()" />&nbsp;<?php etranslate("No")?></label>
  </td></tr>
  <tr id="pa1"><td class="tooltip" title="<?php etooltip("public-access-default-visible")?>">
   &nbsp;&nbsp;&nbsp;&nbsp;<?php etranslate("Public access visible by default")?>:</td><td>
   <label><input type="radio" name="admin_PUBLIC_ACCESS_DEFAULT_VISIBLE" value="Y" <?php if ( $s["PUBLIC_ACCESS_DEFAULT_VISIBLE"] == "Y" ) echo " checked=\"checked\"";?> />&nbsp;<?php etranslate("Yes")?></label>&nbsp;
   <label><input type="radio" name="admin_PUBLIC_ACCESS_DEFAULT_VISIBLE" value="N" <?php if ( $s["PUBLIC_ACCESS_DEFAULT_VISIBLE"] != "Y" ) echo " checked=\"checked\"";?> />&nbsp;<?php etranslate("No")?></label>
  </td></tr>
  <tr id="pa2"><td class="tooltip" title="<?php etooltip("public-access-default-selected")?>">
   &nbsp;&nbsp;&nbsp;&nbsp;<?php etranslate("Public access is default participant")?>:</td><td>
   <label><input type="radio" name="admin_PUBLIC_ACCESS_DEFAULT_SELECTED" value="Y" <?php if ( $s["PUBLIC_ACCESS_DEFAULT_SELECTED"] == "Y" ) echo " checked=\"checked\"";?> />&nbsp;<?php etranslate("Yes")?></label>&nbsp;
   <label><input type="radio" name="admin_PUBLIC_ACCESS_DEFAULT_SELECTED" value="N" <?php if ( $s["PUBLIC_ACCESS_DEFAULT_SELECTED"] != "Y" ) echo " checked=\"checked\"";?> />&nbsp;<?php etranslate("No")?></label>
  </td></tr>
  <tr id="pa3"><td class="tooltip" title="<?php etooltip("public-access-view-others-help")?>">
   &nbsp;&nbsp;&nbsp;&nbsp;<?php etranslate("Public access can view other users")?>:</td><td>
   <label><input type="radio" name="admin_PUBLIC_ACCESS_OTHERS" value="Y" <?php if ( $s["PUBLIC_ACCESS_OTHERS"] == "Y" ) echo " checked=\"checked\"";?> />&nbsp;<?php etranslate("Yes")?></label>&nbsp;
   <label><input type="radio" name="admin_PUBLIC_ACCESS_OTHERS" value="N" <?php if ( $s["PUBLIC_ACCESS_OTHERS"] != "Y" ) echo " checked=\"checked\"";?> />&nbsp;<?php etranslate("No")?></label>
  </td></tr>
  <tr id="pa4"><td class="tooltip" title="<?php etooltip("public-access-can-add-help")?>">
   &nbsp;&nbsp;&nbsp;&nbsp;<?php etranslate("Public access can add events")?>:</td><td>
   <label><input type="radio" name="admin_PUBLIC_ACCESS_CAN_ADD" value="Y" <?php if ( $s["PUBLIC_ACCESS_CAN_ADD"] == "Y" ) echo " checked=\"checked\"";?> />&nbsp;<?php etranslate("Yes")?></label>&nbsp;
   <label><input type="radio" name="admin_PUBLIC_ACCESS_CAN_ADD" value="N" <?php if ( $s["PUBLIC_ACCESS_CAN_ADD"] != "Y" ) echo " checked=\"checked\"";?> />&nbsp;<?php etranslate("No")?></label>
  </td></tr>
  <tr id="pa5"><td class="tooltip" title="<?php etooltip("public-access-add-requires-approval-help")?>">
   &nbsp;&nbsp;&nbsp;&nbsp;<?php etranslate("Public access new events require approval")?>:</td><td>
   <label><input type="radio" name="admin_PUBLIC_ACCESS_ADD_NEEDS_APPROVAL" value="Y" <?php if ( $s["PUBLIC_ACCESS_ADD_NEEDS_APPROVAL"] != "N" ) echo " checked=\"checked\"";?> />&nbsp;<?php etranslate("Yes")?></label>&nbsp;
   <label><input type="radio" name="admin_PUBLIC_ACCESS_ADD_NEEDS_APPROVAL" value="N" <?php if ( $s["PUBLIC_ACCESS_ADD_NEEDS_APPROVAL"] == "N" ) echo " checked=\"checked\"";?> />&nbsp;<?php etranslate("No")?></label>
  </td></tr>
  <tr id="pa6"><td class="tooltip" title="<?php etooltip("public-access-sees-participants-help")?>">
   &nbsp;&nbsp;&nbsp;&nbsp;<?php etranslate("Public access can view participants")?>:</td><td>
   <label><input type="radio" name="admin_PUBLIC_ACCESS_VIEW_PART" value="Y" <?php if ( $s["PUBLIC_ACCESS_VIEW_PART"] != "N" ) echo " checked=\"checked\"";?> />&nbsp;<?php etranslate("Yes")?></label>&nbsp;
   <label><input type="radio" name="admin_PUBLIC_ACCESS_VIEW_PART" value="N" <?php if ( $s["PUBLIC_ACCESS_VIEW_PART"] == "N" ) echo " checked=\"checked\"";?> />&nbsp;<?php etranslate("No")?></label>
  </td></tr>
  <tr id="pa7" valign="top"><td class="tooltip" title="<?php etooltip("public-access-override-help")?>">
   &nbsp;&nbsp;&nbsp;&nbsp;<?php etranslate("Override event name/description for public access")?>:</td><td>
   <label><input type="radio" name="admin_OVERRIDE_PUBLIC" value="Y" <?php if ( $s["OVERRIDE_PUBLIC"] != "N" ) echo " checked=\"checked\"";?> />&nbsp;<?php etranslate("Yes")?></label>&nbsp;
   <label><input type="radio" name="admin_OVERRIDE_PUBLIC" value="N" <?php if ( $s["OVERRIDE_PUBLIC"] == "N" ) echo " checked=\"checked\"";?> />&nbsp;<?php etranslate("No")?></label>
  </td></tr>
  <tr id="pa7a" valign="top"><td class="tooltip" title="<?php etooltip("public-access-override-text-help")?>">
   &nbsp;&nbsp;&nbsp;&nbsp;
   &nbsp;&nbsp;&nbsp;&nbsp;<?php etranslate("Text to display to public access")?>:</td><td>
   <label><input name="admin_OVERRIDE_PUBLIC_TEXT" value="<?php echo $s["OVERRIDE_PUBLIC_TEXT"];?>" size="25" /></label>
  </td></tr>
</table>
</div>

<!-- BEGIN USER ACCESS CONTROL -->
<div id="tabscontent_uac">
<table cellspacing="0" cellpadding="3">
<tr><td class="tooltip" title="<?php etooltip("uac-enabled-help")?>">
   <?php etranslate("User Access Control enabled")?>:</td><td>
   <label><input type="radio" name="admin_UAC_ENABLED" value="Y" <?php if ( ! empty ( $s["UAC_ENABLED"] ) && $s["UAC_ENABLED"] == "Y" ) echo " checked=\"checked\"";?> />&nbsp;<?php etranslate("Yes")?></label>&nbsp;
   <label><input type="radio" name="admin_UAC_ENABLED" value="N" <?php if ( empty ( $s["UAC_ENABLED"] ) || $s["UAC_ENABLED"] != "Y" ) echo " checked=\"checked\"";?> />&nbsp;<?php etranslate("No")?></label>
</td></tr>
</table>
</div>


<!-- BEGIN GROUPS -->
<div id="tabscontent_groups">
<table cellspacing="0" cellpadding="3">
 <tr><td class="tooltip" title="<?php etooltip("groups-enabled-help")?>">
  <?php etranslate("Groups enabled")?>:</td><td>
  <label><input type="radio" name="admin_GROUPS_ENABLED" value="Y" <?php if ( $s["GROUPS_ENABLED"] == "Y" ) echo " checked=\"checked\"";?> />&nbsp;<?php etranslate("Yes")?></label>&nbsp;
  <label><input type="radio" name="admin_GROUPS_ENABLED" value="N" <?php if ( $s["GROUPS_ENABLED"] != "Y" ) echo " checked=\"checked\"";?> />&nbsp;<?php etranslate("No")?></label>
 </td></tr>
 <tr><td class="tooltip" title="<?php etooltip("user-sees-his-group-help")?>">
  <?php etranslate("User sees only his groups")?>:</td><td>
  <label><input type="radio" name="admin_USER_SEES_ONLY_HIS_GROUPS" value="Y" <?php if ( $s["USER_SEES_ONLY_HIS_GROUPS"] == "Y" ) echo " checked=\"checked\"";?> />&nbsp;<?php etranslate("Yes")?></label>&nbsp;
  <label><input type="radio" name="admin_USER_SEES_ONLY_HIS_GROUPS" value="N" <?php if ( $s["USER_SEES_ONLY_HIS_GROUPS"] != "Y" ) echo " checked=\"checked\"";?> />&nbsp;<?php etranslate("No")?></label>
 </td></tr>
</table>
</div>

<!-- BEGIN NONUSER -->
<div id="tabscontent_nonuser">
<table cellspacing="0" cellpadding="3">
 <tr><td class="tooltip" title="<?php etooltip("nonuser-enabled-help")?>">
  <?php etranslate("Nonuser enabled")?>:</td><td>
  <label><input type="radio" name="admin_NONUSER_ENABLED" value="Y" <?php if ( $s["NONUSER_ENABLED"] == "Y" ) echo " checked=\"checked\"";?> />&nbsp;<?php etranslate("Yes")?></label>&nbsp;
  <label><input type="radio" name="admin_NONUSER_ENABLED" value="N" <?php if ( $s["NONUSER_ENABLED"] != "Y" ) echo " checked=\"checked\"";?> />&nbsp;<?php etranslate("No")?></label>
 </td></tr>
 <tr><td class="tooltip" title="<?php etooltip("nonuser-list-help")?>">
  <?php etranslate("Nonuser list")?>:</td><td>
  <label><input type="radio" name="admin_NONUSER_AT_TOP" value="Y" <?php if ( $s["NONUSER_AT_TOP"] == "Y" ) echo " checked=\"checked\"";?> />&nbsp;<?php etranslate("Top")?></label>&nbsp;
  <label><input type="radio" name="admin_NONUSER_AT_TOP" value="N" <?php if ( $s["NONUSER_AT_TOP"] != "Y" ) echo " checked=\"checked\"";?> />&nbsp;<?php etranslate("Bottom")?></label>
</td></tr>
</table>
</div>

<!-- BEGIN REPORTS -->
<div id="tabscontent_other">
<table cellspacing="0" cellpadding="3">
<tr><td class="tooltip" title="<?php etooltip("reports-enabled-help")?>">
 <?php etranslate("Reports enabled")?>:</td><td>
 <label><input type="radio" name="admin_REPORTS_ENABLED" value="Y" <?php if ( $s["REPORTS_ENABLED"] == "Y" ) echo " checked=\"checked\"";?> />&nbsp;<?php etranslate("Yes")?></label>&nbsp;
 <label><input type="radio" name="admin_REPORTS_ENABLED" value="N" <?php if ( $s["REPORTS_ENABLED"] != "Y" ) echo " checked=\"checked\"";?> />&nbsp;<?php etranslate("No")?></label>
</td></tr>


<!-- BEGIN PUBLISHING -->

<tr><td class="tooltip" title="<?php etooltip("subscriptions-enabled-help")?>">
 <?php etranslate("Allow remote subscriptions")?>:</td><td>
 <label><input type="radio" name="admin_PUBLISH_ENABLED" value="Y" <?php if ( $s["PUBLISH_ENABLED"] == "Y" ) echo " checked=\"checked\"";?> />&nbsp;<?php etranslate("Yes")?></label>&nbsp;
 <label><input type="radio" name="admin_PUBLISH_ENABLED" value="N" <?php if ( $s["PUBLISH_ENABLED"] != "Y" ) echo " checked=\"checked\"";?> />&nbsp;<?php etranslate("No")?></label>
</td></tr>
<tr><td class="tooltip" title="<?php etooltip("rss-enabled-help")?>">
 <?php etranslate("Enable RSS feed")?>:</td><td>
 <label><input type="radio" name="admin_RSS_ENABLED" value="Y" <?php if ( $s["RSS_ENABLED"] == "Y" ) echo " checked=\"checked\"";?> />&nbsp;<?php etranslate("Yes")?></label>&nbsp;
 <label><input type="radio" name="admin_RSS_ENABLED" value="N" <?php if ( $s["RSS_ENABLED"] != "Y" ) echo " checked=\"checked\"";?> />&nbsp;<?php etranslate("No")?></label>
</td></tr>


<!-- BEGIN CATEGORIES -->

 <tr><td class="tooltip" title="<?php etooltip("categories-enabled-help")?>">
  <?php etranslate("Categories enabled")?>:</td><td>
  <label><input type="radio" name="admin_CATEGORIES_ENABLED" value="Y" <?php if ( $s["CATEGORIES_ENABLED"] == "Y" ) echo " checked=\"checked\"";?> />&nbsp;<?php etranslate("Yes")?></label>&nbsp;
  <label><input type="radio" name="admin_CATEGORIES_ENABLED" value="N" <?php if ( $s["CATEGORIES_ENABLED"] != "Y" ) echo " checked=\"checked\"";?> />&nbsp;<?php etranslate("No")?></label>
 </td></tr>

<!-- Display Task Preferences -->
 <tr><td class="tooltip" title="<?php etooltip("display-tasks-help")?>">
  <?php etranslate("Display small task list")?>:</td><td>
  <label><input type="radio" name="admin_DISPLAY_TASKS" value="Y" <?php if ( ! empty ( $s["DISPLAY_TASKS"] ) && $s["DISPLAY_TASKS"] == "Y" ) echo " checked=\"checked\"";?>  />&nbsp;<?php etranslate("Yes")?></label>&nbsp;
  <label><input type="radio" name="admin_DISPLAY_TASKS" value="N" <?php if ( ! empty ( $s["DISPLAY_TASKS"] ) && $s["DISPLAY_TASKS"] != "Y" ) echo " checked=\"checked\"";?> />&nbsp;<?php etranslate("No")?></label>
 </td></tr>
 <tr><td class="tooltip" title="<?php etooltip("display-tasks-in-grid-help")?>">
  <?php etranslate("Display tasks in Calendars" )?>:</td><td>
  <label><input type="radio" name="admin_DISPLAY_TASKS_IN_GRID" value="Y" <?php if ( ! empty ( $s["DISPLAY_TASKS_IN_GRID"] ) && $s["DISPLAY_TASKS_IN_GRID"] == "Y" ) echo " checked=\"checked\"";?>  />&nbsp;<?php etranslate("Yes")?></label>&nbsp;
  <label><input type="radio" name="admin_DISPLAY_TASKS_IN_GRID" value="N" <?php if ( ! empty ( $s["DISPLAY_TASKS_IN_GRID"] ) && $s["DISPLAY_TASKS_IN_GRID"] != "Y" ) echo " checked=\"checked\"";?>  />&nbsp;<?php etranslate("No")?></label>
 </td></tr>

<!-- BEGIN USE TIMEZONES IN ICS-->
 <tr><td class="tooltip" title="<?php etooltip("export-ics-timezones-help")?>">
  <?php etranslate("Export VTIMEZONE in ics files" )?>:</td><td>
  <label><input type="radio" name="admin_ICS_TIMEZONES" value="Y" <?php if ( ! empty ( $s["ICS_TIMEZONES"] ) && $s["ICS_TIMEZONES"] == "Y" ) echo " checked=\"checked\"";?>  />&nbsp;<?php etranslate("Yes")?></label>&nbsp;
  <label><input type="radio" name="admin_ICS_TIMEZONES" value="N" <?php if ( ! empty ( $s["ICS_TIMEZONES"] ) && $s["ICS_TIMEZONES"] != "Y" ) echo " checked=\"checked\"";?>  />&nbsp;<?php etranslate("No")?></label>
 </td></tr> 
<!-- BEGIN EXT PARTICIPANTS -->

 <tr><td class="tooltip" title="<?php etooltip("allow-external-users-help")?>">
  <?php etranslate("Allow external users")?>:</td><td>
  <label><input type="radio" name="admin_ALLOW_EXTERNAL_USERS" value="Y" <?php if ( $s["ALLOW_EXTERNAL_USERS"] == "Y" ) echo " checked=\"checked\"";?> onclick="eu_handler()" />&nbsp;<?php etranslate("Yes")?></label>&nbsp;
  <label><input type="radio" name="admin_ALLOW_EXTERNAL_USERS" value="N" <?php if ( $s["ALLOW_EXTERNAL_USERS"] != "Y" ) echo " checked=\"checked\"";?> onclick="eu_handler()" />&nbsp;<?php etranslate("No")?></label>
 </td></tr>
 <tr id="eu1"><td class="tooltip" title="<?php etooltip("external-can-receive-notification-help")?>">
  &nbsp;&nbsp;&nbsp;&nbsp;<?php etranslate("External users can receive email notifications")?>:</td><td>
  <label><input type="radio" name="admin_EXTERNAL_NOTIFICATIONS" value="Y" <?php if ( $s["EXTERNAL_NOTIFICATIONS"] == "Y" ) echo " checked=\"checked\"";?> />&nbsp;<?php etranslate("Yes")?></label>&nbsp;
  <label><input type="radio" name="admin_EXTERNAL_NOTIFICATIONS" value="N" <?php if ( $s["EXTERNAL_NOTIFICATIONS"] != "Y" ) echo " checked=\"checked\"";?> />&nbsp;<?php etranslate("No")?></label>
 </td></tr>
 <tr id="eu2"><td class="tooltip" title="<?php etooltip("external-can-receive-reminder-help")?>">
  &nbsp;&nbsp;&nbsp;&nbsp;<?php etranslate("External users can receive email reminders")?>:</td><td>
  <label><input type="radio" name="admin_EXTERNAL_REMINDERS" value="Y" <?php if ( $s["EXTERNAL_REMINDERS"] == "Y" ) echo " checked=\"checked\"";?> />&nbsp;<?php etranslate("Yes")?></label>&nbsp;
  <label><input type="radio" name="admin_EXTERNAL_REMINDERS" value="N" <?php if ( $s["EXTERNAL_REMINDERS"] != "Y" ) echo " checked=\"checked\"";?> />&nbsp;<?php etranslate("No")?></label>
 </td></tr>
 
 <!-- BEGIN SELF REGISTRATION -->

 <tr><td class="tooltip" title="<?php etooltip("allow-self-registration-help")?>">
  <?php etranslate("Allow self-registration")?>:</td><td>
  <label><input type="radio" name="admin_ALLOW_SELF_REGISTRATION" value="Y" <?php if ( ! empty ( $s["ALLOW_SELF_REGISTRATION"] ) && $s["ALLOW_SELF_REGISTRATION"] == "Y" ) echo " checked=\"checked\"";?> onclick="sr_handler()" />&nbsp;<?php etranslate("Yes")?></label>&nbsp;
  <label><input type="radio" name="admin_ALLOW_SELF_REGISTRATION" value="N" <?php if ( ! empty ( $s["ALLOW_SELF_REGISTRATION"] ) && $s["ALLOW_SELF_REGISTRATION"] != "Y" ) echo " checked=\"checked\"";?> onclick="sr_handler()" />&nbsp;<?php etranslate("No")?></label>
 </td></tr>
 <tr id="sr1"><td class="tooltip" title="<?php etooltip("use-blacklist-help")?>">
  &nbsp;&nbsp;&nbsp;&nbsp;<?php etranslate("Restrict self-registration to blacklist" )?>:</td><td>
  <label><input type="radio" name="admin_SELF_REGISTRATION_BLACKLIST" value="Y" <?php if ( ! empty ( $s["SELF_REGISTRATION_BLACKLIST"] ) && $s["SELF_REGISTRATION_BLACKLIST"] == "Y" ) echo " checked=\"checked\"";?> onclick="sr_handler()" />&nbsp;<?php etranslate("Yes")?></label>&nbsp;
  <label><input type="radio" name="admin_SELF_REGISTRATION_BLACKLIST" value="N" <?php if ( ! empty ( $s["SELF_REGISTRATION_BLACKLIST"] ) && $s["SELF_REGISTRATION_BLACKLIST"] != "Y" ) echo " checked=\"checked\"";?> onclick="sr_handler()" />&nbsp;<?php etranslate("No")?></label>
 </td></tr>
 <tr id="sr2"><td class="tooltip" title="<?php etooltip("allow-self-registration-full-help")?>">
  &nbsp;&nbsp;&nbsp;&nbsp;<?php etranslate("Use self-registration email notifications" )?>:</td><td>
  <label><input type="radio" name="admin_SELF_REGISTRATION_FULL" value="N" <?php if ( ! empty ( $s["SELF_REGISTRATION_FULL"] ) && $s["SELF_REGISTRATION_FULL"] == "N" ) echo " checked=\"checked\"";?> onclick="sr_handler()" />&nbsp;<?php etranslate("Yes")?></label>&nbsp;
  <label><input type="radio" name="admin_SELF_REGISTRATION_FULL" value="Y" <?php if ( ! empty ( $s["SELF_REGISTRATION_FULL"] ) && $s["SELF_REGISTRATION_FULL"] == "Y" ) echo " checked=\"checked\"";?> onclick="sr_handler()" />&nbsp;<?php etranslate("No")?></label>
 </td></tr>
<!-- TODO add account aging feature -->


 <!-- BEGIN ATTACHMENTS/COMMENTS -->

 <tr><td class="tooltip" title="<?php etooltip("allow-attachment-help")?>">
  <?php etranslate("Allow file attachments to events")?>:</td><td>
  <label><input type="radio" name="admin_ALLOW_ATTACH" value="Y" <?php if ( ! empty ( $s["ALLOW_ATTACH"] ) && $s["ALLOW_ATTACH"] == "Y" ) echo " checked=\"checked\"";?> onclick="attach_handler()" />&nbsp;<?php etranslate("Yes")?></label>&nbsp;
  <label><input type="radio" name="admin_ALLOW_ATTACH" value="N" <?php if ( empty ( $s["ALLOW_ATTACH"] ) || $s["ALLOW_ATTACH"] != "Y" ) echo " checked=\"checked\"";?> onclick="attach_handler()" />&nbsp;<?php etranslate("No")?></label>
  <span id="at1">
  <br/><strong>Note:</strong>
  <?php etranslate("Admin and owner can always add attachments if enabled");?><br/>
  <label><input type="checkbox" value="Y" name="admin_ALLOW_ATTACH_PART"
    <?php if ( ! empty ( $s['ALLOW_ATTACH_PART'] ) && $s['ALLOW_ATTACH_PART'] == 'Y' ) echo ' CHECKED ';?> > <?php etranslate('Participant')?> </label>
  <label><input type="checkbox" value="Y" name="admin_ALLOW_ATTACH_ANY"
    <?php if ( ! empty ( $s['ALLOW_ATTACH_ANY'] ) && $s['ALLOW_ATTACH_ANY'] == 'Y' ) echo ' CHECKED ';?> > <?php etranslate('Anyone')?> </label>
  </span>
 </td></tr>

 <tr><td class="tooltip" title="<?php etooltip("allow-comments-help")?>">
  <?php etranslate("Allow comments to events")?>:</td><td>
  <label><input type="radio" name="admin_ALLOW_COMMENTS" value="Y" <?php if ( ! empty ( $s["ALLOW_COMMENTS"] ) && $s["ALLOW_COMMENTS"] == "Y" ) echo " checked=\"checked\"";?> onclick="comment_handler()" />&nbsp;<?php etranslate("Yes")?></label>&nbsp;
  <label><input type="radio" name="admin_ALLOW_COMMENTS" value="N" <?php if ( empty ( $s["ALLOW_COMMENTS"] ) || $s["ALLOW_COMMENTS"] != "Y" ) echo " checked=\"checked\"";?> onclick="comment_handler()" />&nbsp;<?php etranslate("No")?></label>
  <br/>
  <span id="com1">
  <br/><strong>Note:</strong>
  <?php etranslate("Admin and owner can always add comments if enabled");?><br/>
  <label><input type="checkbox" value="Y" name="admin_ALLOW_COMMENTS_PART"
    <?php if ( empty ( $s['ALLOW_COMMENTS_PART'] ) || $s['ALLOW_COMMENTS_PART'] == 'Y' ) echo ' CHECKED ';?> > <?php etranslate('Participant')?> </label>
  <label><input type="checkbox" value="Y" name="admin_ALLOW_COMMENTS_ANY"
    <?php if ( ! empty ( $s['ALLOW_COMMENTS_ANY'] ) && $s['ALLOW_COMMENTS_ANY'] == 'Y' ) echo ' CHECKED ';?> > <?php etranslate('Anyone')?> </label>
  </span>
 </td></tr>

 <!-- END ATTACHMENTS/COMMENTS -->

</table>
</div>

<!-- BEGIN EMAIL -->
<div id="tabscontent_email">
<table cellspacing="0" cellpadding="3">
<tr><td class="tooltip" title="<?php etooltip("email-enabled-help")?>">
 <?php etranslate("Email enabled")?>:</td><td>
 <label><input type="radio" name="admin_SEND_EMAIL" value="Y" <?php if ( $s["SEND_EMAIL"] == "Y" ) echo " checked=\"checked\"";?> onclick="email_handler()" />&nbsp;<?php etranslate("Yes")?></label>&nbsp;
 <label><input type="radio" name="admin_SEND_EMAIL" value="N" <?php if ( $s["SEND_EMAIL"] != "Y" ) echo " checked=\"checked\"";?> onclick="email_handler()" />&nbsp;<?php etranslate("No")?></label>
</td></tr>
<tr id="em1"><td class="tooltip" title="<?php etooltip("email-default-sender")?>">
 &nbsp;&nbsp;&nbsp;&nbsp;<?php etranslate("Default sender address")?>:</td><td>
 <input type="text" size="30" name="admin_EMAIL_FALLBACK_FROM" value="<?php echo htmlspecialchars ($EMAIL_FALLBACK_FROM );?>" />
</td></tr>

<tr id="em2"><td class="tooltip" title="<?php etooltip("email-mailer")?>">
<?php etranslate("Email Mailer")?>:</td><td>
 <select name="admin_EMAIL_MAILER"  onchange="email_handler()">
   <option value="smtp" <?php if ( $s["EMAIL_MAILER"] == 
     "smtp" ) echo " selected=\"selected\""?>>SMTP</option>
   <option value="mail" <?php if ( $s["EMAIL_MAILER"] == 
     "mail" ) echo " selected=\"selected\""?>>PHP mail</option>
   <option value="sendmail" <?php if ( $s["EMAIL_MAILER"] == 
     "sendmail" ) echo " selected=\"selected\""?>>sendmail</option>
  </select>   
</td></tr>

<tr id="em3"><td class="tooltip" title="<?php etooltip("email-smtp-host")?>">
<?php etranslate("SMTP Host name(s)")?>:</td><td>
 <input type="text" size="50" name="admin_SMTP_HOST" value="<?php echo $s["SMTP_HOST"];?>" />
</td></tr>
<tr id="em3a"><td class="tooltip" title="<?php etooltip("email-smtp-port")?>">
<?php etranslate("SMTP Port Number")?>:</td><td>
 <input type="text" size="4" name="admin_SMTP_PORT" value="<?php echo $s["SMTP_PORT"];?>" />
</td></tr>

<tr id="em4"><td class="tooltip" title="<?php etooltip("email-smtp-auth")?>">
 <?php etranslate("SMTP Authentication")?>:</td><td>
 <label><input type="radio" name="admin_SMTP_AUTH" value="Y" <?php if ( $s["SMTP_AUTH"] == "Y" ) echo " checked=\"checked\"";?> onclick="email_handler()" />&nbsp;<?php etranslate("Yes")?></label>&nbsp;
 <label><input type="radio" name="admin_SMTP_AUTH" value="N" <?php if ( $s["SMTP_AUTH"] != "Y" ) echo " checked=\"checked\"";?> onclick="email_handler()" />&nbsp;<?php etranslate("No")?></label>
</td></tr>

<tr id="em5"><td class="tooltip" title="<?php etooltip("email-smtp-username")?>">
 &nbsp;&nbsp;&nbsp;&nbsp;<?php etranslate("SMTP Username")?>:</td><td>
 <input type="text" size="30" name="admin_SMTP_USERNAME" value="<?php echo $s["SMTP_USERNAME"];?>" />
</td></tr>

<tr id="em6"><td class="tooltip" title="<?php etooltip("email-smtp-password")?>">
 &nbsp;&nbsp;&nbsp;&nbsp;<?php etranslate("SMTP Password")?>:</td><td>
 <input type="text" size="30" name="admin_SMTP_PASSWORD" value="<?php echo $s["SMTP_PASSWORD"];?>" />
</td></tr>

<tr id="em7"><td colspan="2" style="font-weight:bold;">
 <?php etranslate("Default user settings")?>:
</td></tr>
<tr id="em8"><td class="tooltip" title="<?php etooltip("email-event-reminders-help")?>">
 &nbsp;&nbsp;&nbsp;&nbsp;<?php etranslate("Event reminders")?>:</td><td>
 <label><input type="radio" name="admin_EMAIL_REMINDER" value="Y" <?php if ( $s["EMAIL_REMINDER"] != "N" ) echo " checked=\"checked\"";?> />&nbsp;<?php etranslate("Yes")?></label>&nbsp;
 <label><input type="radio" name="admin_EMAIL_REMINDER" value="N" <?php if ( $s["EMAIL_REMINDER"] == "N" ) echo " checked=\"checked\"";?> />&nbsp;<?php etranslate("No")?></label>
</td></tr>
<tr id="em9"><td class="tooltip" title="<?php etooltip("email-event-added")?>">
 &nbsp;&nbsp;&nbsp;&nbsp;<?php etranslate("Events added to my calendar")?>:</td><td>
 <label><input type="radio" name="admin_EMAIL_EVENT_ADDED" value="Y" <?php if ( $s["EMAIL_EVENT_ADDED"] != "N" ) echo " checked=\"checked\"";?> />&nbsp;<?php etranslate("Yes")?></label>&nbsp;
 <label><input type="radio" name="admin_EMAIL_EVENT_ADDED" value="N" <?php if ( $s["EMAIL_EVENT_ADDED"] == "N" ) echo " checked=\"checked\"";?> />&nbsp;<?php etranslate("No")?></label>
</td></tr>
<tr id="em10"><td class="tooltip" title="<?php etooltip("email-event-updated")?>">
 &nbsp;&nbsp;&nbsp;&nbsp;<?php etranslate("Events updated on my calendar")?>:</td><td>
 <label><input type="radio" name="admin_EMAIL_EVENT_UPDATED" value="Y" <?php if ( $s["EMAIL_EVENT_UPDATED"] != "N" ) echo " checked=\"checked\"";?> />&nbsp;<?php etranslate("Yes")?></label>&nbsp;
 <label><input type="radio" name="admin_EMAIL_EVENT_UPDATED" value="N" <?php if ( $s["EMAIL_EVENT_UPDATED"] == "N" ) echo " checked=\"checked\"";?> />&nbsp;<?php etranslate("No")?></label>
</td></tr>
<tr id="em11"><td class="tooltip" title="<?php etooltip("email-event-deleted");?>">
 &nbsp;&nbsp;&nbsp;&nbsp;<?php etranslate("Events removed from my calendar")?>:</td><td>
 <label><input type="radio" name="admin_EMAIL_EVENT_DELETED" value="Y" <?php if ( $s["EMAIL_EVENT_DELETED"] != "N" ) echo " checked=\"checked\"";?> />&nbsp;<?php etranslate("Yes")?></label>&nbsp;
 <label><input type="radio" name="admin_EMAIL_EVENT_DELETED" value="N" <?php if ( $s["EMAIL_EVENT_DELETED"] == "N" ) echo " checked=\"checked\"";?> />&nbsp;<?php etranslate("No")?></label>
</td></tr>
<tr id="em12"><td class="tooltip" title="<?php etooltip("email-event-rejected")?>">
 &nbsp;&nbsp;&nbsp;&nbsp;<?php etranslate("Event rejected by participant")?>:</td><td>
 <label><input type="radio" name="admin_EMAIL_EVENT_REJECTED" value="Y" <?php if ( $s["EMAIL_EVENT_REJECTED"] != "N" ) echo " checked=\"checked\"";?> />&nbsp;<?php etranslate("Yes")?></label>&nbsp;
 <label><input type="radio" name="admin_EMAIL_EVENT_REJECTED" value="N" <?php if ( $s["EMAIL_EVENT_REJECTED"] == "N" ) echo " checked=\"checked\"";?> />&nbsp;<?php etranslate("No")?></label>
</td></tr>
</table>
</div>

<!-- BEGIN COLORS -->
<div id="tabscontent_colors">
<table cellspacing="0" cellpadding="3"  width="100%">
<tr><td><label>
 <?php etranslate("Allow user to customize colors")?>:</label></td><td colspan="5">
 <label><input type="radio" name="admin_ALLOW_COLOR_CUSTOMIZATION" value="Y"<?php if ( $s["ALLOW_COLOR_CUSTOMIZATION"] != "N" ) echo " checked=\"checked\"";?> />&nbsp;<?php etranslate("Yes")?></label>&nbsp;
 <label><input type="radio" name="admin_ALLOW_COLOR_CUSTOMIZATION" value="N"<?php if ( $s["ALLOW_COLOR_CUSTOMIZATION"] == "N" ) echo " checked=\"checked\"";?> />&nbsp;<?php etranslate("No")?></label>
</td></tr>
<tr><td class="tooltip" title="<?php etooltip("gradient-colors")?>"><label>
 <?php etranslate("Enable gradient images for background colors")?>:</label></td><td colspan="5">
<?php if ( function_exists ( "imagepng" ) ) { ?>
 <label><input type="radio" name="admin_ENABLE_GRADIENTS" value="Y"<?php if ( $s["ENABLE_GRADIENTS"] != "N" ) echo " checked=\"checked\"";?> />&nbsp;<?php etranslate("Yes")?></label>&nbsp;
 <label><input type="radio" name="admin_ENABLE_GRADIENTS" value="N"<?php if ( $s["ENABLE_GRADIENTS"] == "N" ) echo " checked=\"checked\"";?> />&nbsp;<?php etranslate("No")?></label>
<?php } else {
        etranslate("Not available");
      } ?>
</td></tr>
<tr><td>
 <label for="admin_BGCOLOR"><?php etranslate("Document background")?>:</label></td><td>
 <input type="text" name="admin_BGCOLOR" id="admin_BGCOLOR" size="8" maxlength="7" value="<?php echo $s["BGCOLOR"]; ?>" onkeyup="updateColor(this);" /></td><td class="sample" style="background-color:<?php echo $s["BGCOLOR"]?>;">
 &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td><td>
 <input type="button" onclick="selectColor('admin_BGCOLOR')" value="<?php etranslate("Select")?>..." name="" />
</td>
<td rowspan="12" width="10%">&nbsp;</td>
<td rowspan="12" width="50%">
<!-- BEGIN EXAMPLE MONTH -->
<table style="border:0px; width:100%;"><tr>
<td style="text-align:center; color:<?php echo $H2COLOR?>; font-weight:bold;"><?php
echo date_to_str ( date ("Ymd"), $DATE_FORMAT_MY, false, false );?></td></tr>
</table>
<?php 
set_today( date ("Ymd") );
display_month ( date ("m") , date("Y") , 's');
?>
<!-- END EXAMPLE MONTH -->
</td>
</tr>
<tr><td>
 <label for="admin_H2COLOR"><?php etranslate("Document title")?>:</label></td><td>
 <input type="text" name="admin_H2COLOR" id="admin_H2COLOR" size="8" maxlength="7" value="<?php echo $s["H2COLOR"]; ?>" onkeyup="updateColor(this);" /></td><td class="sample" style="background-color:<?php echo $s["H2COLOR"]?>;">
 &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td><td>
 <input type="button" onclick="selectColor('admin_H2COLOR')" value="<?php etranslate("Select")?>..." name="" />
</td></tr>
<tr><td>
 <label for="admin_TEXTCOLOR"><?php etranslate("Document text")?>:</label></td><td>
 <input type="text" name="admin_TEXTCOLOR" id="admin_TEXTCOLOR" size="8" maxlength="7" value="<?php echo $s["TEXTCOLOR"]; ?>" onkeyup="updateColor(this);" /></td><td class="sample" style="background-color:<?php echo $s["TEXTCOLOR"]?>;">
 &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td><td>
 <input type="button" onclick="selectColor('admin_TEXTCOLOR')" value="<?php etranslate("Select")?>..." name="" />
</td></tr>
<tr><td>
 <label for="admin_TABLEBG"><?php etranslate("Table grid color")?>:</label></td><td>
 <input type="text" name="admin_TABLEBG" id="admin_TABLEBG" size="8" maxlength="7" value="<?php echo $s["TABLEBG"]; ?>" onkeyup="updateColor(this);" /></td><td class="sample" style="background-color:<?php echo $s["TABLEBG"]?>;">
 &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td><td>
 <input type="button" onclick="selectColor('admin_TABLEBG')" value="<?php etranslate("Select")?>..." name="" />
</td></tr>
<tr><td>
 <label for="admin_THBG"><?php etranslate("Table header background")?>:</label></td><td>
 <input type="text" name="admin_THBG" id="admin_THBG" size="8" maxlength="7" value="<?php echo $s["THBG"]; ?>" onkeyup="updateColor(this);" /></td><td class="sample" style="background-color:<?php echo $s["THBG"]?>;">
 &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td><td>
 <input type="button" onclick="selectColor('admin_THBG')" value="<?php etranslate("Select")?>..." name="" />
</td></tr>
<tr><td>
 <label for="admin_THFG"><?php etranslate("Table header text")?>:</label></td><td>
 <input type="text" name="admin_THFG" id="admin_THFG" size="8" maxlength="7" value="<?php echo $s["THFG"]; ?>" onkeyup="updateColor(this);" /></td><td class="sample" style="background-color:<?php echo $s["THFG"]?>;">
 &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td><td>
 <input type="button" onclick="selectColor('admin_THFG')" value="<?php etranslate("Select")?>..." name="" />
</td></tr>
<tr><td>
 <label for="admin_CELLBG"><?php etranslate("Table cell background")?>:</label></td><td>
 <input type="text" name="admin_CELLBG" id="admin_CELLBG" size="8" maxlength="7" value="<?php echo $s["CELLBG"]; ?>" onkeyup="updateColor(this);" /></td><td class="sample" style="background-color:<?php echo $s["CELLBG"]?>;">
 &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td><td>
 <input type="button" onclick="selectColor('admin_CELLBG')" value="<?php etranslate("Select")?>..." name="" />
</td></tr>
<tr><td>
 <label for="admin_TODAYCELLBG"><?php etranslate("Table cell background for current day")?>:</label></td><td>
 <input type="text" name="admin_TODAYCELLBG" id="admin_TODAYCELLBG" size="8" maxlength="7" value="<?php echo $s["TODAYCELLBG"]; ?>" onkeyup="updateColor(this);" /></td><td class="sample" style="background-color:<?php echo $s["TODAYCELLBG"]?>;">
 &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td><td>
 <input type="button" onclick="selectColor('admin_TODAYCELLBG')" value="<?php etranslate("Select")?>..." name="" />
</td></tr>
<tr><td>
 <label for="admin_HASEVENTSBG"><?php etranslate("Table cell background for days with events")?>:</label></td><td>
 <input type="text" name="admin_HASEVENTSBG" id="admin_HASEVENTSBG" size="8" maxlength="7" value="<?php echo $s["HASEVENTSBG"]; ?>" onkeyup="updateColor(this);" /></td><td class="sample" style="background-color:<?php echo $s["HASEVENTSBG"]?>;">
 &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td><td>
 <input type="button" onclick="selectColor('admin_HASEVENTSBG')" value="<?php etranslate("Select")?>..." name="" />
</td></tr>
<tr><td>
 <label for="admin_WEEKENDBG"><?php etranslate("Table cell background for weekends")?>:</label></td><td>
 <input type="text" name="admin_WEEKENDBG" id="admin_WEEKENDBG" size="8" maxlength="7" value="<?php echo $s["WEEKENDBG"]; ?>" onkeyup="updateColor(this);" /></td><td class="sample" style="background-color:<?php echo $s["WEEKENDBG"]?>;">
 &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td><td>
 <input type="button" onclick="selectColor('admin_WEEKENDBG')" value="<?php etranslate("Select")?>..." name="" />
</td></tr>
<tr><td>
  <label for="tdbgothermonth"><?php etranslate("Table cell background for other month")?>:</label></td><td>
  <input type="text" name="admin_OTHERMONTHBG" id="tdbgothermonth" size="8" maxlength="7" value="<?php echo $s["OTHERMONTHBG"]; ?>" onkeyup="updateColor(this);" /></td><td class="sample" style="background-color:<?php echo $s["OTHERMONTHBG"]?>;">
  &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td><td>
  <input type="button" onclick="selectColor('admin_OTHERMONTHBG')" value="<?php etranslate("Select")?>..." name="" />
</td></tr>
<tr><td>
 <label for="admin_POPUP_BG"><?php etranslate("Event popup background")?>:</label></td><td>
 <input type="text" name="admin_POPUP_BG" id="admin_POPUP_BG" size="8" maxlength="7" value="<?php echo $s["POPUP_BG"]; ?>" onkeyup="updateColor(this);" /></td><td class="sample" style="background-color:<?php echo $s["POPUP_BG"]?>;">
 &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td><td>
 <input type="button" onclick="selectColor('admin_POPUP_BG')" value="<?php etranslate("Select")?>..." name="" />
</td></tr>
<tr><td>
 <label for="admin_POPUP_FG"><?php etranslate("Event popup text")?>:</label></td><td>
 <input type="text" name="admin_POPUP_FG" id="admin_POPUP_FG" size="8" maxlength="7" value="<?php echo $s["POPUP_FG"]; ?>" onkeyup="updateColor(this);" /></td><td class="sample" style="background-color:<?php echo $s["POPUP_FG"]?>;">
 &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td><td>
   <input type="button" onclick="selectColor('admin_POPUP_FG')" value="<?php etranslate("Select")?>..." name="" />
</td></tr>
</table>
</div>
</div>

<br /><br />
<div style="border-width:0px;">
 <input type="submit" value="<?php etranslate("Save")?>" name="" />
</div>
</form>

<?php } // if $error ?>

<?php print_trailer (); ?>
</body>
</html>
