<?php
include_once 'includes/init.php';

if ($user != $login)
  $user = (($is_admin || $is_nonuser_admin) && $user) ? $user : $login;

// Load categories only if editing our own calendar
if (!$user || $user == $login) load_user_categories ();

// Reload preferences into $prefarray[].
// Get system settings first.
$updating_public = false;
$prefarray = array ();
$prefarray['EMAIL_ASSISTANT_EVENTS'] =
  $prefarray['APPROVE_ASSISTANT_EVENT'] = ''; // no undefined vars message
$res = dbi_query ( "SELECT cal_setting, cal_value FROM webcal_config " );
if ( $res ) {
  while ( $row = dbi_fetch_row ( $res ) ) {
    $prefarray[$row[0]] = $row[1];
  }
  dbi_free_result ( $res );
}
if ( $is_admin && ! empty ( $public ) && $public_access == "Y" ) {
  $updating_public = true;
  $res = dbi_query ( "SELECT cal_setting, cal_value FROM webcal_user_pref " .
    "WHERE cal_login = '__public__'" );
} else {
  $res = dbi_query ( "SELECT cal_setting, cal_value FROM webcal_user_pref " .
    "WHERE cal_login = '$user'" );
}
if ( $res ) {
  while ( $row = dbi_fetch_row ( $res ) ) {
    $prefarray[$row[0]] = $row[1];
  }
  dbi_free_result ( $res );
}

$INC = array('js/pref.php');
print_header($INC);
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
?>&nbsp;<img src="help.gif" alt="<?php etranslate("Help")?>" class="help" onclick="window.open ( 'help_pref.php', 'cal_help', 'dependent,menubar,scrollbars,height=400,width=400,innerHeight=420,outerWidth=420');" /></h2>

<a title="<?php etranslate("Admin") ?>" class="nav" href="adminhome.php">&laquo;&nbsp;<?php etranslate("Admin") ?></a><br /><br />

<form action="pref_handler.php" method="post" onsubmit="return valid_form(this);" name="prefform">
<?php 
	if ($user) 
		echo "<input type=\"hidden\" name=\"user\" value=\"$user\" />\n";
?>

<?php if ( $updating_public ) { ?>
	<input type="hidden" name="public" value="1" />
<?php } /*if ( $updating_public )*/ ?>

<table style="border-width:0px;"><tr><td>
<input type="submit" value="<?php etranslate("Save Preferences")?>" name="" />
</td></tr></table>
<br />

<?php
	if ( $is_admin ) {
	  if ( empty ( $public ) && $public_access == "Y") {
	    echo "<blockquote><a href=\"pref.php?public=1\">" .
	      translate("Click here") . " " .
	      translate("to modify the preferences for the Public Access calendar") .
	      "</a></blockquote>\n";
	  }
	}
?>
<table class="standard" cellspacing="1" cellpadding="2">
<tr><th colspan="2"><?php etranslate("Settings");?></th></tr>
<tr><td class="tooltipselect" title="<?php etooltip("language-help");?>">
	<label for="pref_lang"><?php etranslate("Language")?>:</label></td><td>
	<select name="pref_LANGUAGE" id="pref_lang">
<?php
	reset ( $languages );
	while ( list ( $key, $val ) = each ( $languages ) ) {
	  // Don't allow users to select browser-defined.  We want them to pick
	  // a language so that when we send reminders (done without the benefit
	  // of a browser-preferred language), we'll know which language to use.
	  // DO let them select browser-defined for the public user.
	  if ( $key != "Browser-defined" || $updating_public ) {
	    echo "<option value=\"" . $val . "\"";
	    if ( $val == $prefarray["LANGUAGE"] ) echo " selected=\"selected\"";
	    echo ">" . translate( $key ) . "</option>\n";
	  }
	}
?>
	</select>
	<br />
	<?php echo translate("Your browser default language is") . " " . translate ( get_browser_language () ) . "."; ?>
</td></tr>
<tr><td class="tooltipselect" title="<?php etooltip("tz-help")?>">
	<label for="pref_tz"><?php etranslate("Timezone Offset")?>:</label></td><td>
	<select name="pref_TZ_OFFSET" id="pref_tz">
  <?php
  $text_add = translate("Add N hours to");
  $text_sub = translate("Subtract N hours from");
  if ( empty ( $prefarray["TZ_OFFSET"] ) )
    $prefarray["TZ_OFFSET"] = 0;
  for ( $i = -12; $i <= 12; $i++ ) {
    echo "<option value=\"$i\"";
    if ( $prefarray["TZ_OFFSET"] == $i ) echo " selected=\"selected\"";
    echo ">";
    if ( $i < 0 )
      echo str_replace ( "N", -$i, $text_sub ) . "</option>\n";
    else if ( $i == 0 )
      echo "" . etranslate("same as") . "</option>\n";
    else
      echo str_replace ( "N", $i, $text_add ) . "</option>\n";
  }
  ?>
	</select>&nbsp;<?php etranslate("server time");?>
</td></tr>
<tr><td class="tooltipselect" title="<?php etooltip("fonts-help")?>">
	<label for="pref_font"><?php etranslate("Fonts")?>:</label></td><td>
	<input type="text" size="40" name="pref_FONTS" id="pref_font" value="<?php echo htmlspecialchars ( $prefarray["FONTS"] );?>" />
</td></tr>

<tr><td class="tooltip" title="<?php etooltip("preferred-view-help");?>"><?php etranslate("Preferred view")?>:</td><td>
<select name="pref_STARTVIEW">
<?php
// For backwards compatibility.  We used to store without the .php extension
if ( $prefarray['STARTVIEW'] == 'month' || $prefarray['STARTVIEW'] == 'day' ||
  $prefarray['STARTVIEW'] == 'week' || $prefarray['STARTVIEW'] == 'year' )
  $prefarray['STARTVIEW'] .= '.php';
?>
<option value="day.php" <?php if ( $prefarray["STARTVIEW"] == "day.php" ) echo " selected=\"selected\"";?>><?php etranslate("Day")?></option>
<option value="week.php" <?php if ( $prefarray["STARTVIEW"] == "week.php" ) echo " selected=\"selected\"";?>><?php etranslate("Week")?></option>
<option value="month.php" <?php if ( $prefarray["STARTVIEW"] == "month.php" ) echo " selected=\"selected\"";?>><?php etranslate("Month")?></option>
<option value="year.php" <?php if ( $prefarray["STARTVIEW"] == "year.php" ) echo " selected=\"selected\"";?>><?php etranslate("Year")?></option>
<?php
// Allow user to select a view also
for ( $i = 0; $i < count ( $views ); $i++ ) {
  $xurl = $views[$i]['url'];
  echo "<option value=\"";
  echo $xurl . "\" ";
  $xurl_strip = str_replace ( "&amp;", "&", $xurl );
  if ( $STARTVIEW == $xurl_strip )
    echo "selected=\"selected\" ";
  echo ">" . $views[$i]['cal_name'] . "</option>\n";
}
?>
</select>
</td></tr>

<tr><td class="tooltip" title="<?php etooltip("display-weekends-help");?>">
	<?php etranslate("Display weekends in week view")?>:</td><td>
	<label><input type="radio" name="pref_DISPLAY_WEEKENDS" value="Y" <?php if ( $prefarray["DISPLAY_WEEKENDS"] != "N" ) echo " checked=\"checked\"";?> /> <?php etranslate("Yes")?></label> 
	<label><input type="radio" name="pref_DISPLAY_WEEKENDS" value="N" <?php if ( $prefarray["DISPLAY_WEEKENDS"] == "N" ) echo " checked=\"checked\"";?> /> <?php etranslate("No")?></label>
</td></tr>

<tr><td class="tooltip" title="<?php etooltip("display-desc-print-day-help");?>">
	<?php etranslate("Display description in printer day view")?>:</td><td>
	<label><input type="radio" name="pref_DISPLAY_DESC_PRINT_DAY" value="Y" <?php if ( $prefarray["DISPLAY_DESC_PRINT_DAY"] == "Y" ) echo " checked=\"checked\"";?> /> <?php etranslate("Yes")?></label> 
	<label><input type="radio" name="pref_DISPLAY_DESC_PRINT_DAY" value="N" <?php if ( $prefarray["DISPLAY_DESC_PRINT_DAY"] != "Y" ) echo " checked=\"checked\"";?> /> <?php etranslate("No")?></label>
</td></tr>

<tr><td class="tooltipselect" title="<?php etooltip("date-format-help");?>">
	<?php etranslate("Date format")?>:</td><td>
	<select name="pref_DATE_FORMAT">
  <?php
  // You can add new date formats below if you want.
  // but also add in admin.php.
  $datestyles = array (
    "__month__ __dd__, __yyyy__", translate("December") . " 31, 2000",
    "__dd__ __month__, __yyyy__", "31 " . translate("December") . ", 2000",
    "__dd__-__month__-__yyyy__", "31-" . translate("December") . "-2000",
    "__dd__-__month__-__yy__", "31-" . translate("December") . "-00",
    "__mm__/__dd__/__yyyy__", "12/31/2000",
    "__mm__/__dd__/__yy__", "12/31/00",
    "__mm__-__dd__-__yyyy__", "12-31-2000",
    "__mm__-__dd__-__yy__", "12-31-00",
    "__yyyy__-__mm__-__dd__", "2000-12-31",
    "__yy__-__mm__-__dd__", "00-12-31",
    "__yyyy__/__mm__/__dd__", "2000/12/31",
    "__yy__/__mm__/__dd__", "00/12/31",
    "__dd__/__mm__/__yyyy__", "31/12/2000",
    "__dd__/__mm__/__yy__", "31/12/00",
    "__dd__-__mm__-__yyyy__", "31-12-2000",
    "__dd__-__mm__-__yy__", "31-12-00"
  );
  for ( $i = 0; $i < count ( $datestyles ); $i += 2 ) {
    echo "<option value=\"" . $datestyles[$i] . "\"";
    if ( $prefarray["DATE_FORMAT"] == $datestyles[$i] )
      echo " selected=\"selected\"";
    echo ">" . $datestyles[$i + 1] . "</option>\n";
  }
  ?>
</select>
<br />
<select name="pref_DATE_FORMAT_MY">
<?php
  // Date format for a month and year (with no day of the month)
  // You can add new date formats below if you want.
  // but also add in admin.php.
  $datestyles = array (
    "__month__ __yyyy__", translate("December") . " 2000",
    "__month__ __yy__", translate("December") . " 00",
    "__month__-__yyyy__", translate("December") . "-2000",
    "__month__-yy", translate("December") . "-00",
    "__mm__/__yyyy__", "12/2000",
    "__mm__/__yy__", "12/00",
    "__mm__-__yyyy__", "12-2000",
    "__mm__-__yy__", "12-00",
    "__yyyy__-__mm__", "2000-12",
    "__yy__-__mm__", "00-12",
    "__yyyy__/__mm__", "2000/12",
    "__yy__/__mm__", "00/12"
  );
  for ( $i = 0; $i < count ( $datestyles ); $i += 2 ) {
    echo "<option value=\"" . $datestyles[$i] . "\"";
    if ( $prefarray["DATE_FORMAT_MY"] == $datestyles[$i] )
      echo " selected=\"selected\"";
    echo ">" . $datestyles[$i + 1] . "</option>\n";
  }
?>
</select>
<br />
<select name="pref_DATE_FORMAT_MD">
<?php
  // Date format for a month and day (with no year displayed)
  // You can add new date formats below if you want.
  // but also add in admin.php.
  $datestyles = array (
    "__month__ __dd__", translate("December") . " 31",
    "__month__-__dd__", translate("December") . "-31",
    "__mm__/__dd__", "12/31",
    "__mm__-__dd__", "12-31",
    "__dd__/__mm__", "31/12",
    "__dd__-__mm__", "31-12"
  );
  for ( $i = 0; $i < count ( $datestyles ); $i += 2 ) {
    echo "<option value=\"" . $datestyles[$i] . "\"";
    if ( $prefarray["DATE_FORMAT_MD"] == $datestyles[$i] )
      echo " selected=\"selected\"";
    echo ">" . $datestyles[$i + 1] . "</option>\n";
  }
?>
</select>
</td></tr>

<tr><td class="tooltip" title="<?php etooltip("time-format-help")?>">
	<?php etranslate("Time format")?>:</td><td>
	<label><input type="radio" name="pref_TIME_FORMAT" value="12" <?php if ( $prefarray["TIME_FORMAT"] == "12" ) echo " checked=\"checked\"";?> /> <?php etranslate("12 hour")?></label> 
	<label><input type="radio" name="pref_TIME_FORMAT" value="24" <?php if ( $prefarray["TIME_FORMAT"] != "12" ) echo " checked=\"checked\"";?> /> <?php etranslate("24 hour")?></label>
</td></tr>

<tr><td class="tooltip" title="<?php etooltip("time-interval-help")?>">
	<?php etranslate("Time interval")?>:</td><td>
	<select name="pref_TIME_SLOTS">
		<option value="24" <?php if ( $prefarray["TIME_SLOTS"] == "24" ) echo " selected=\"selected\""?>>1 <?php etranslate("hour")?></option>
		<option value="48" <?php if ( $prefarray["TIME_SLOTS"] == "48" ) echo " selected=\"selected\""?>>30 <?php etranslate("minutes")?></option>
		<option value="72" <?php if ( $prefarray["TIME_SLOTS"] == "72" ) echo " selected=\"selected\""?>>20 <?php etranslate("minutes")?></option>
		<option value="144" <?php if ( $prefarray["TIME_SLOTS"] == "144" ) echo " selected=\"selected\""?>>10 <?php etranslate("minutes")?></option>
	</select>
</td></tr>

<tr><td class="tooltip" title="<?php etooltip("auto-refresh-help");?>">
	<?php etranslate("Auto-refresh calendars")?>:</td><td>
	<label><input type="radio" name="pref_auto_refresh" value="Y" <?php if ( $prefarray["auto_refresh"] == "Y" ) echo " checked=\"checked\"";?> /> <?php etranslate("Yes")?></label>&nbsp;
	<label><input type="radio" name="pref_auto_refresh" value="N" <?php if ( $prefarray["auto_refresh"] != "Y" ) echo " checked=\"checked\"";?> /> <?php etranslate("No")?></label>
</td></tr>

<tr><td class="tooltip" title="<?php etooltip("auto-refresh-time-help");?>">
	&nbsp;&nbsp;&nbsp;&nbsp;<?php etranslate("Auto-refresh time")?>:</td><td>
	<input type="text" name="pref_auto_refresh_time" size="4" value="<?php if ( empty ( $prefarray["auto_refresh_time"] ) ) echo "0"; else echo $prefarray["auto_refresh_time"]; ?>" /> <?php etranslate("minutes")?>
</td></tr>

<tr><td class="tooltip" title="<?php etooltip("display-unapproved-help");?>">
	<?php etranslate("Display unapproved")?>:</td><td>
	<label><input type="radio" name="pref_DISPLAY_UNAPPROVED" value="Y" <?php if ( $prefarray["DISPLAY_UNAPPROVED"] != "N" ) echo " checked=\"checked\"";?> /> <?php etranslate("Yes")?></label>&nbsp;
	<label><input type="radio" name="pref_DISPLAY_UNAPPROVED" value="N" <?php if ( $prefarray["DISPLAY_UNAPPROVED"] == "N" ) echo " checked=\"checked\"";?> /> <?php etranslate("No")?></label>
</td></tr>
<tr><td class="tooltip" title="<?php etooltip("display-week-number-help")?>">
	<?php etranslate("Display week number")?>:</td><td>
	<label><input type="radio" name="pref_DISPLAY_WEEKNUMBER" value="Y" <?php if ( $DISPLAY_WEEKNUMBER != "N" ) echo " checked=\"checked\"";?> /> <?php etranslate("Yes")?></label>&nbsp;
	<label><input type="radio" name="pref_DISPLAY_WEEKNUMBER" value="N" <?php if ( $prefarray["DISPLAY_WEEKNUMBER"] == "N" ) echo " checked=\"checked\"";?> /> <?php etranslate("No")?></label>
</td></tr>
<tr><td class="tooltip" title="<?php etooltip("display-week-starts-on")?>">
	<?php etranslate("Week starts on")?>:</td><td>
	<label><input type="radio" name="pref_WEEK_START" value="0" <?php if ( $prefarray["WEEK_START"] != "1" ) echo " checked=\"checked\"";?> /> <?php etranslate("Sunday")?></label>&nbsp;
	<label><input type="radio" name="pref_WEEK_START" value="1" <?php if ( $prefarray["WEEK_START"] == "1" ) echo " checked=\"checked\"";?> /> <?php etranslate("Monday")?></label>
</td></tr>
<tr><td class="tooltip" title="<?php etooltip("work-hours-help")?>">
	<?php etranslate("Work hours")?>:</td><td>
	<label for="pref_starthr"><?php etranslate("From")?></label> 
	<select name="pref_WORK_DAY_START_HOUR" id="pref_starthr">
<?php
  if ( empty ( $prefarray["WORK_DAY_START_HOUR"] ) ) {
    $prefarray["WORK_DAY_START_HOUR"] = $WORK_DAY_START_HOUR;
    $prefarray["WORK_DAY_END_HOUR"] = $WORK_DAY_END_HOUR;
  }
  for ( $i = 0; $i < 24; $i++ ) {
    echo "<option value=\"$i\"" .
      ( $i == $prefarray["WORK_DAY_START_HOUR"] ? " selected=\"selected\"" : "" ) .
      ">" . display_time ( $i * 10000, 1 ) . "</option>\n";
  }
?>
	</select> 
	<label for="pref_endhr"><?php etranslate("to")?></label>
	<select name="pref_WORK_DAY_END_HOUR" id="pref_endhr">
<?php
	for ( $i = 0; $i < 24; $i++ ) {
		echo "<option value=\"$i\"" .
			( $i == $prefarray["WORK_DAY_END_HOUR"] ? " selected=\"selected\"" : "" ) .
			">" . display_time ( $i * 10000, 1 ) . "</option>\n";
	}
?>
	</select>
</td></tr>

<tr><td class="tooltip" title="<?php etooltip("timed-evt-len-help");?>">
	<?php etranslate("Specify timed event length by")?>:</td><td>
	<label><input type="radio" name="pref_TIMED_EVT_LEN" value="D" <?php if ( $prefarray["TIMED_EVT_LEN"] != "E" ) echo " checked=\"checked\"";?> /> <?php etranslate("Duration")?></label> 
	<label><input type="radio" name="pref_TIMED_EVT_LEN" value="E" <?php if ( $prefarray["TIMED_EVT_LEN"] == "E" ) echo " checked=\"checked\"";?> /> <?php etranslate("End Time")?></label>
</td></tr>

<?php if ( ! empty ( $categories ) ) { ?>
<tr><td>
	<label for="pref_cat"><?php etranslate("Default Category")?>:</label></td><td>
	<select name="pref_CATEGORY_VIEW" id="pref_cat">
<?php
	echo "<option value=\"\"";
	if ( empty ( $prefarray["CATEGORY_VIEW"] ) ) echo " selected=\"selected\"";
	echo ">".translate("All")."</option>\n";
	
	if ( ! empty ( $categories ) ) {
		foreach( $categories as $K => $V ){
			echo "<option value=\"$K\"";
			if ( ! empty ( $prefarray["CATEGORY_VIEW"] ) &&
				$prefarray["CATEGORY_VIEW"] == $K ) echo " selected=\"selected\"";
			echo ">$V</option>\n";
		}
	}
?>
	</select>
</td></tr>
<?php } //end if (! empty ($categories ) ) ?>
</table>

<?php if ( ! $updating_public ) { ?>
<br /><br />
<table class="standard" cellspacing="1" cellpadding="2">
<tr><th colspan="2"><?php etranslate("Email")?></th></tr>
<tr><td style="vertical-align:top; font-weight:bold;">
	<?php etranslate("Event reminders")?>:</td><td>
	<label><input type="radio" name="pref_EMAIL_REMINDER" value="Y" <?php if ( $prefarray["EMAIL_REMINDER"] != "N" ) echo " checked=\"checked\"";?> />&nbsp;<?php etranslate("Yes")?></label>&nbsp;
	<label><input type="radio" name="pref_EMAIL_REMINDER" value="N" <?php if ( $prefarray["EMAIL_REMINDER"] == "N" ) echo " checked=\"checked\"";?> />&nbsp;<?php etranslate("No")?></label>
</td></tr>

<tr><td style="vertical-align:top; font-weight:bold;">
	<?php etranslate("Events added to my calendar")?>:</td><td>
	<label><input type="radio" name="pref_EMAIL_EVENT_ADDED" value="Y" <?php if ( $prefarray["EMAIL_EVENT_ADDED"] != "N" ) echo " checked=\"checked\"";?> /> <?php etranslate("Yes")?></label> <label><input type="radio" name="pref_EMAIL_EVENT_ADDED" value="N" <?php if ( $prefarray["EMAIL_EVENT_ADDED"] == "N" ) echo " checked=\"checked\"";?> /> <?php etranslate("No")?></label></td></tr>

<tr><td style="vertical-align:top; font-weight:bold;">
	<?php etranslate("Events updated on my calendar")?>:</td><td>
	<label><input type="radio" name="pref_EMAIL_EVENT_UPDATED" value="Y" <?php if ( $prefarray["EMAIL_EVENT_UPDATED"] != "N" ) echo " checked=\"checked\"";?> /> <?php etranslate("Yes")?></label> <label><input type="radio" name="pref_EMAIL_EVENT_UPDATED" value="N" <?php if ( $prefarray["EMAIL_EVENT_UPDATED"] == "N" ) echo " checked=\"checked\"";?> /> <?php etranslate("No")?></label>
</td></tr>

<tr><td style="vertical-align:top; font-weight:bold;">
	<?php etranslate("Events removed from my calendar")?>:</td><td>
	<label><input type="radio" name="pref_EMAIL_EVENT_DELETED" value="Y" <?php if ( $prefarray["EMAIL_EVENT_DELETED"] != "N" ) echo " checked=\"checked\"";?> /> <?php etranslate("Yes")?></label> <label><input type="radio" name="pref_EMAIL_EVENT_DELETED" value="N" <?php if ( $prefarray["EMAIL_EVENT_DELETED"] == "N" ) echo " checked=\"checked\"";?> /> <?php etranslate("No")?></label>
</td></tr>

<tr><td style="vertical-align:top; font-weight:bold;">
	<?php etranslate("Event rejected by participant")?>:</td><td>
	<label><input type="radio" name="pref_EMAIL_EVENT_REJECTED" value="Y" <?php if ( $prefarray["EMAIL_EVENT_REJECTED"] != "N" ) echo " checked=\"checked\"";?> /> <?php etranslate("Yes")?></label> <label><input type="radio" name="pref_EMAIL_EVENT_REJECTED" value="N" <?php if ( $prefarray["EMAIL_EVENT_REJECTED"] == "N" ) echo " checked=\"checked\"";?> /> <?php etranslate("No")?></label>
</td></tr>
</table>

<br /><br />
<table class="standard" cellspacing="1" cellpadding="2">
<tr><th colspan="2"><?php etranslate("When I am the boss")?></th></tr>
<tr><td style="vertical-align:top; font-weight:bold;"><?php etranslate("Email me event notification")?>:</td>
  <td><label><input type="radio" name="pref_EMAIL_ASSISTANT_EVENTS" value="Y" <?php if ( $prefarray["EMAIL_ASSISTANT_EVENTS"] == "Y" ) echo " checked=\"checked\"";?> /> <?php etranslate("Yes")?></label> <label><input type="radio" name="pref_EMAIL_ASSISTANT_EVENTS" value="N" <?php if ( $prefarray["EMAIL_ASSISTANT_EVENTS"] == "N" ) echo " checked=\"checked\"";?> /> <?php etranslate("No")?></label></td></tr>

<tr><td style="vertical-align:top; font-weight:bold;"><?php etranslate("I want to approve events")?>:</td>
  <td><label><input type="radio" name="pref_APPROVE_ASSISTANT_EVENT" value="Y" <?php if ( $prefarray["APPROVE_ASSISTANT_EVENT"] == "Y" ) echo " checked=\"checked\"";?> /> <?php etranslate("Yes")?></label> <label><input type="radio" name="pref_APPROVE_ASSISTANT_EVENT" value="N" <?php if ( $prefarray["APPROVE_ASSISTANT_EVENT"] == "N" ) echo " checked=\"checked\"";?> /> <?php etranslate("No")?></label></td></tr>
</table>

<?php } /* if ( ! $updating_public ) */ ?>

<br /><br />

<?php if ( $PUBLISH_ENABLED == 'Y' ) { ?>
<table class="standard" cellspacing="1" cellpadding="2">
<tr><th colspan="2"><?php etranslate("Subscribe/Publish")?></th></tr>
<tr><td class="tooltipselect" title="<?php etooltip("allow-remote-subscriptions-help")?>"><?php etranslate("Allow remote subscriptions")?>:</td>
  <td><label><input type="radio" name="pref_USER_PUBLISH_ENABLED" value="Y" <?php if ( isset ( $prefarray["USER_PUBLISH_ENABLED"] ) && $prefarray["USER_PUBLISH_ENABLED"] == "Y" ) echo " checked=\"checked\"";?> /> <?php etranslate("Yes")?></label> <label><input type="radio" name="pref_USER_PUBLISH_ENABLED" value="N" <?php if ( empty ( $prefarray["USER_PUBLISH_ENABLED"] ) || $prefarray["USER_PUBLISH_ENABLED"] != "Y" ) echo " checked=\"checked\"";?> /> <?php etranslate("No")?></label></td></tr>
<?php if ( ! empty ( $server_url ) ) { ?>
<tr><td class="tooltipselect" title="<?php etooltip("remote-subscriptions-url-help")?>">&nbsp;&nbsp;&nbsp;&nbsp;<?php etranslate("URL")?>:</td>
  <td>
  <?php
    echo htmlspecialchars ( $server_url ) .
      "publish.php/" . ( $updating_public ? "public" : $login ) .  ".ics";
    echo "<br/>\n";
    echo htmlspecialchars ( $server_url ) .
      "publish.php?user=" . ( $updating_public ? "public" : $login );
  ?></td></tr>
<?php } /* $server_url */ ?>
</table>
<?php } /* $PUBLISH_ENABLED == 'Y' */ ?>

<?php if ( $allow_color_customization == 'Y' ) { ?>

<br /><br />

<table style="border-width:0px; width:100%;">
<tr><td style="vertical-align:top;">

<table class="standard" cellspacing="1" cellpadding="2">
	<tr><th colspan="4"><?php etranslate("Colors")?></th></tr>
	<tr><td style="font-weight:bold;">
		<label for="pref_bg"><?php etranslate("Document background")?>:</label></td><td>
		<input type="text" name="pref_BGCOLOR" id="pref_bg" size="8" maxlength="7" value="<?php echo $prefarray["BGCOLOR"]; ?>" onkeyup="updateColor(this);" /></td><td style="background-color:<?php echo $prefarray["BGCOLOR"]?>; border-style: groove;">
		&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td><td>
		<input type="button" onclick="selectColor('pref_BGCOLOR')" value="<?php etranslate("Select")?>..." />
	</td></tr>
	<tr><td style="font-weight:bold;">
		<label for="pref_h2"><?php etranslate("Document title")?>:</label></td><td>
		<input type="text" name="pref_H2COLOR" id="pref_h2" size="8" maxlength="7" value="<?php echo $prefarray["H2COLOR"]; ?>" onkeyup="updateColor(this);" /></td><td style="background-color:<?php echo $prefarray["H2COLOR"]?>; border-style: groove;">
	&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td><td>
		<input type="button" onclick="selectColor('pref_H2COLOR')" value="<?php etranslate("Select")?>..." />
	</td></tr>
	<tr><td style="font-weight:bold;">
		<label for="pref_cell"><?php etranslate("Table cell background")?>:</label></td><td>
		<input type="text" name="pref_CELLBG" id="pref_cell" size="8" maxlength="7" value="<?php echo $prefarray["CELLBG"]; ?>" onkeyup="updateColor(this);" /></td><td style="background-color:<?php echo $prefarray["CELLBG"]?>; border-style: groove;">
	&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td><td>
		<input type="button" onclick="selectColor('pref_CELLBG')" value="<?php etranslate("Select")?>..." />
	</td></tr>
	<tr><td style="font-weight:bold;">
		<label for="pref_today"><?php etranslate("Table cell background for current day")?>:</label></td><td>
		<input type="text" name="pref_TODAYCELLBG" id="pref_today" size="8" maxlength="7" value="<?php echo $prefarray["TODAYCELLBG"]; ?>" onkeyup="updateColor(this);" /></td><td style="background-color:<?php echo $prefarray["TODAYCELLBG"]?>; border-style: groove;">
	&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td><td>
		<input type="button" onclick="selectColor('pref_TODAYCELLBG')" value="<?php etranslate("Select")?>..." />
	</td></tr>
	<tr><td style="font-weight:bold;">
		<label for="pref_wkend"><?php etranslate("Table cell background for weekends")?>:</label></td><td>
		<input type="text" name="pref_WEEKENDBG" id="pref_wkend" size="8" maxlength="7" value="<?php echo $prefarray["WEEKENDBG"]; ?>" onkeyup="updateColor(this);" /></td><td style="background-color:<?php echo $prefarray["WEEKENDBG"]?>; border-style: groove;">
	&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td><td>
		<input type="button" onclick="selectColor('pref_WEEKENDBG')" value="<?php etranslate("Select")?>..." />
	</td></tr>
</table>

</td><td style="text-align:center; vertical-align:top; background-color:<?php echo $prefarray["BGCOLOR"]?>;">
<br />

<!-- BEGIN EXAMPLE MONTH -->
<table style="border:0px; width:100%;"><tr>
<td style="text-align:center; color:<?php echo $H2COLOR?>; font-weight:bold;"><?php
$today = mktime ( 3, 0, 0, 12, 13, 2000 );
if ( $prefarray["WEEK_START"] == 1 )
  $wkstart = get_monday_before ( 2000, 12, 1 );
else
  $wkstart = get_sunday_before ( 2000, 12, 1 );
echo date_to_str ( "20001201", $DATE_FORMAT_MY, false, false );?></td></tr>
</table>

<table style="border-width:0px; width:90%;" cellspacing="0" cellpadding="0">
<tr><td style="background-color:<?php echo $TABLEBG?>;">
<table style="border-width:0px; width:100%;" cellspacing="1" cellpadding="2">
<tr>
<?php if ( $prefarray["WEEK_START"] == 0 ) { ?>
<th style="width:14%;" class="tableheader"><?php etranslate("Sun")?></th>
<?php } ?>
<th style="width:14%;" class="tableheader"><?php etranslate("Mon")?></th>
<th style="width:14%;" class="tableheader"><?php etranslate("Tue")?></th>
<th style="width:14%;" class="tableheader"><?php etranslate("Wed")?></th>
<th style="width:14%;" class="tableheader"><?php etranslate("Thu")?></th>
<th style="width:14%;" class="tableheader"><?php etranslate("Fri")?></th>
<th style="width:14%;" class="tableheader"><?php etranslate("Sat")?></th>
<?php if ( $prefarray["WEEK_START"] == 1 ) { ?>
<th style="width:14%;"><?php etranslate("Sun")?></th>
<?php } ?>
</tr>
<?php
// generate values for first day and last day of month
$monthstart = mktime ( 3, 0, 0, 12, 1, 2000 );
$monthend = mktime ( 3, 0, 0, 13, 0, 2000 );

for ( $i = $wkstart; date ( "Ymd", $i ) <= date ( "Ymd", $monthend );
  $i += ( 24 * 3600 * 7 ) ) {
  print "<tr>\n";
  for ( $j = 0; $j < 7; $j++ ) {
    $date = $i + ( $j * 24 * 3600 );
    if ( date ( "Ymd", $date ) >= date ( "Ymd", $monthstart ) &&
      date ( "Ymd", $date ) <= date ( "Ymd", $monthend ) ) {
      $thiswday = date ( "w", $date );
      $is_weekend = ( $thiswday == 0 || $thiswday == 6 );
      if ( empty ( $prefarray["WEEKENDBG"] ) ) $is_weekend = false;
      $class = $is_weekend ? "weekend" : "tablecell";
      $color = $is_weekend ? $prefarray["WEEKENDBG"] : $prefarray["CELLBG"];
      print "<td style=\"vertical-align:top; height:30px;";
      if ( date ( "Ymd", $date ) == date ( "Ymd", $today ) )
        echo " background-color:$prefarray[TODAYCELLBG];\">";
      else
        echo " background-color:$color;\">";
      echo "&nbsp;";
      print "</td>\n";
    } else {
      print "<td style=\"vertical-align:top; height:30px; background-color:$prefarray[CELLBG];\">&nbsp;</td>\n";
    }
  }
  print "</tr>\n";
}

?>
</table>
</td></tr>
</table>

<!-- END EXAMPLE MONTH -->
<br /><br />

</td></tr></table>

<?php } // if $allow_color_customization ?>

<br /><br />
<table style="border-width:0px;"><tr><td>
<input type="submit" value="<?php etranslate("Save Preferences")?>" name="" />
</td></tr></table>
</form>

<?php print_trailer(); ?>
</body>
</html>
