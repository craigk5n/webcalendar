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
  echo translate($PUBLIC_ACCESS_FULLNAME) . " ";
etranslate("Preferences");
if ( $is_nonuser_admin ) {
  nonuser_load_variables ( $user, "nonuser" );
  echo "<br />\n<b>-- " . translate("Admin mode") . ": ".$nonuserfullname." --</b></h2>\n";
}
?></h2>

<form action="pref_handler.php" method="post" onsubmit="return valid_form(this);" name="prefform">
<?php if ($user) echo "<input type=\"hidden\" name=\"user\" value=\"$user\" />"; ?>
<?php if ( $updating_public ) { ?>
  <input type="hidden" name="public" value="1" />
<?php } /*if ( $updating_public )*/ ?>

<h3><?php etranslate("Settings");?></h3>

<?php

if ( $is_admin ) {
  if ( empty ( $public ) ) {
    echo "<blockquote><a href=\"pref.php?public=1\">" .
      translate("Click here") . "</a> " .
      translate("to modify the preferences for the Public Access calendar") .
      "</blockquote>\n";
  }
}
?>

<table class="standard" cellspacing="1" cellpadding="2"><tr><td style="vertical-align:top; font-weight:bold;"><?php etranslate("Language")?>:</td>
<td><select name="pref_LANGUAGE">
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
<tr><td style="vertical-align:top;" class="tooltip" title="<?php etooltip("tz-help")?>"><?php etranslate("Timezone Offset")?>:</td>
  <td><select name="pref_TZ_OFFSET">
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
  </select>&nbsp;<?php etranslate("server time");?></td></tr>

<tr><td style="vertical-align:top;" class="tooltip" title="<?php etooltip("fonts-help")?>"><?php etranslate("Fonts")?>:</td>
  <td><input type="text" size="40" name="pref_FONTS" value="<?php echo htmlspecialchars ( $prefarray["FONTS"] );?>" /></td></tr>

<tr><td style="font-weight:bold;"><?php etranslate("Preferred view")?>:</td>
<td>
<select name="pref_STARTVIEW">
<option value="day" <?php if ( $prefarray["STARTVIEW"] == "day" ) echo " selected=\"selected\"";?>><?php etranslate("Day")?></option>
<option value="week" <?php if ( $prefarray["STARTVIEW"] == "week" ) echo " selected=\"selected\"";?>><?php etranslate("Week")?></option>
<option value="month" <?php if ( $prefarray["STARTVIEW"] == "month" ) echo " selected=\"selected\"";?>><?php etranslate("Month")?></option>
<option value="year" <?php if ( $prefarray["STARTVIEW"] == "year" ) echo " selected=\"selected\"";?>><?php etranslate("Year")?></option>
</select></td></tr>

<tr><td class="tooltip" title="<?php etooltip("display-weekends-help");?>"><?php etranslate("Display weekends in week view")?>:</td>
  <td><label><input type="radio" name="pref_DISPLAY_WEEKENDS" value="Y" <?php if ( $prefarray["DISPLAY_WEEKENDS"] != "N" ) echo " checked=\"checked\"";?> /> <?php etranslate("Yes")?></label> <label><input type="radio" name="pref_DISPLAY_WEEKENDS" value="N" <?php if ( $prefarray["DISPLAY_WEEKENDS"] == "N" ) echo " checked=\"checked\"";?> /> <?php etranslate("No")?></label></td></tr>

<tr><td class="tooltip" title="<?php etooltip("display-desc-print-day-help");?>"><?php etranslate("Display description in printer day view")?>:</td>
  <td><label><input type="radio" name="pref_DISPLAY_DESC_PRINT_DAY" value="Y" <?php if ( $prefarray["DISPLAY_DESC_PRINT_DAY"] == "Y" ) echo " checked=\"checked\"";?> /> <?php etranslate("Yes")?></label> <label><input type="radio" name="pref_DISPLAY_DESC_PRINT_DAY" value="N" <?php if ( $prefarray["DISPLAY_DESC_PRINT_DAY"] != "Y" ) echo " checked=\"checked\"";?> /> <?php etranslate("No")?></label></td></tr>

  <tr><td style="vertical-align:top; font-weight:bold;"><?php etranslate("Date format")?>:</td>
  <td>
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

<tr><td style="font-weight:bold;"><?php etranslate("Time format")?>:</td>
  <td><label><input type="radio" name="pref_TIME_FORMAT" value="12" <?php if ( $prefarray["TIME_FORMAT"] == "12" ) echo " checked=\"checked\"";?> /> <?php etranslate("12 hour")?></label> <label><input type="radio" name="pref_TIME_FORMAT" value="24" <?php if ( $prefarray["TIME_FORMAT"] != "12" ) echo " checked=\"checked\"";?> /> <?php etranslate("24 hour")?></label></td></tr>

<tr><td style="font-weight:bold;"><?php etranslate("Time interval")?>:</td>
  <td><select name="pref_TIME_SLOTS">
  <option value="24" <?php if ( $prefarray["TIME_SLOTS"] == "24" ) echo " selected=\"selected\""?>>1 <?php etranslate("hour")?></option>
  <option value="48" <?php if ( $prefarray["TIME_SLOTS"] == "48" ) echo " selected=\"selected\""?>>30 <?php etranslate("minutes")?></option>
  <option value="72" <?php if ( $prefarray["TIME_SLOTS"] == "72" ) echo " selected=\"selected\""?>>20 <?php etranslate("minutes")?></option>
  <option value="144" <?php if ( $prefarray["TIME_SLOTS"] == "144" ) echo " selected=\"selected\""?>>10 <?php etranslate("minutes")?></option>
  </select></td></tr>

<tr><td class="tooltip" title="<?php etooltip("auto-refresh-help");?>"><?php etranslate("Auto-refresh calendars")?>:</td>
  <td><label><input type="radio" name="pref_auto_refresh" value="Y" <?php if ( $prefarray["auto_refresh"] == "Y" ) echo " checked=\"checked\"";?> /> <?php etranslate("Yes")?></label> <label><input type="radio" name="pref_auto_refresh" value="N" <?php if ( $prefarray["auto_refresh"] != "Y" ) echo " checked=\"checked\"";?> /> <?php etranslate("No")?></label></td></tr>

<tr><td class="tooltip" title="<?php etooltip("auto-refresh-time-help");?>">&nbsp;&nbsp;&nbsp;&nbsp;<?php etranslate("Auto-refresh time")?>:</td>
  <td><input name="pref_auto_refresh_time" size="4" value="<?php if ( empty ( $prefarray["auto_refresh_time"] ) ) echo "0"; else echo $prefarray["auto_refresh_time"]; ?>" /> <?php etranslate("minutes")?></td></tr>


<tr><td style="font-weight:bold;"><?php etranslate("Display unapproved")?>:</td>
<td><label><input type="radio" name="pref_DISPLAY_UNAPPROVED" value="Y" <?php if ( $prefarray["DISPLAY_UNAPPROVED"] != "N" ) echo " checked=\"checked\"";?> /> <?php etranslate("Yes")?></label> <label><input type="radio" name="pref_DISPLAY_UNAPPROVED" value="N" <?php if ( $prefarray["DISPLAY_UNAPPROVED"] == "N" ) echo " checked=\"checked\"";?> /> <?php etranslate("No")?></label></td></tr>
<tr><td style="font-weight:bold;"><?php etranslate("Display week number")?>:</td>
<td><label><input type="radio" name="pref_DISPLAY_WEEKNUMBER" value="Y" <?php if ( $DISPLAY_WEEKNUMBER != "N" ) echo " checked=\"checked\"";?> /> <?php etranslate("Yes")?></label> <label><input type="radio" name="pref_DISPLAY_WEEKNUMBER" value="N" <?php if ( $prefarray["DISPLAY_WEEKNUMBER"] == "N" ) echo " checked=\"checked\"";?> /> <?php etranslate("No")?></label></td></tr>
<tr><td style="font-weight:bold;"><?php etranslate("Week starts on")?>:</td>
  <td><label><input type="radio" name="pref_WEEK_START" value="0" <?php if ( $prefarray["WEEK_START"] != "1" ) echo " checked=\"checked\"";?> /> <?php etranslate("Sunday")?></label> <label><input type="radio" name="pref_WEEK_START" value="1" <?php if ( $prefarray["WEEK_START"] == "1" ) echo " checked=\"checked\"";?> /> <?php etranslate("Monday")?></label></td></tr>
<tr><td style="font-weight:bold;"><?php etranslate("Work hours")?>:</td>
  <td>
  <?php etranslate("From")?> <select name="pref_WORK_DAY_START_HOUR">
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
  </select> <?php etranslate("to")?>
  <select name="pref_WORK_DAY_END_HOUR">
  <?php
  for ( $i = 0; $i < 24; $i++ ) {
    echo "<option value=\"$i\"" .
      ( $i == $prefarray["WORK_DAY_END_HOUR"] ? " selected=\"selected\"" : "" ) .
      ">" . display_time ( $i * 10000, 1 ) . "</option>\n";
  }
  ?>
  </select>
  </td></tr>

<tr><td class="tooltip" title="<?php etooltip("timed-evt-len-help");?>"><?php etranslate("Specify timed event length by")?>:</td>
  <td><label><input type="radio" name="pref_TIMED_EVT_LEN" value="D" <?php if ( $prefarray["TIMED_EVT_LEN"] != "E" ) echo " checked=\"checked\"";?> /> <?php etranslate("Duration")?></label> <label><input type="radio" name="pref_TIMED_EVT_LEN" value="E" <?php if ( $prefarray["TIMED_EVT_LEN"] == "E" ) echo " checked=\"checked\"";?> /> <?php etranslate("End Time")?></label></td></tr>

<?php if ( ! empty ( $categories ) ) { ?>
<tr><td style="font-weight:bold;"><?php etranslate("Default Category")?>:</td>
  <td><select name="pref_CATEGORY_VIEW">
  <?php
  echo "<option value=\"\"";
  if ( empty ( $prefarray["CATEGORY_VIEW"] ) ) echo " selected=\"selected\"";
  echo ">".translate('All')."</option>\n";
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
<?php } ?>

</table>

<?php if ( ! $updating_public ) { ?>
<h3><?php etranslate("Email")?></h3>
<table class="standard" cellspacing="1" cellpadding="2"><tr><td style="vertical-align:top; font-weight:bold;"><?php etranslate("Event reminders")?>:</td>
  <td><label><input type="radio" name="pref_EMAIL_REMINDER" value="Y" <?php if ( $prefarray["EMAIL_REMINDER"] != "N" ) echo " checked=\"checked\"";?> /> <?php etranslate("Yes")?></label> <label><input type="radio" name="pref_EMAIL_REMINDER" value="N" <?php if ( $prefarray["EMAIL_REMINDER"] == "N" ) echo " checked=\"checked\"";?> /> <?php etranslate("No")?></label></td></tr>

<tr><td style="vertical-align:top; font-weight:bold;"><?php etranslate("Events added to my calendar")?>:</td>
  <td><label><input type="radio" name="pref_EMAIL_EVENT_ADDED" value="Y" <?php if ( $prefarray["EMAIL_EVENT_ADDED"] != "N" ) echo " checked=\"checked\"";?> /> <?php etranslate("Yes")?></label> <label><input type="radio" name="pref_EMAIL_EVENT_ADDED" value="N" <?php if ( $prefarray["EMAIL_EVENT_ADDED"] == "N" ) echo " checked=\"checked\"";?> /> <?php etranslate("No")?></label></td></tr>

<tr><td style="vertical-align:top; font-weight:bold;"><?php etranslate("Events updated on my calendar")?>:</td>
  <td><label><input type="radio" name="pref_EMAIL_EVENT_UPDATED" value="Y" <?php if ( $prefarray["EMAIL_EVENT_UPDATED"] != "N" ) echo " checked=\"checked\"";?> /> <?php etranslate("Yes")?></label> <label><input type="radio" name="pref_EMAIL_EVENT_UPDATED" value="N" <?php if ( $prefarray["EMAIL_EVENT_UPDATED"] == "N" ) echo " checked=\"checked\"";?> /> <?php etranslate("No")?></label></td></tr>

<tr><td style="vertical-align:top; font-weight:bold;"><?php etranslate("Events removed from my calendar")?>:</td>
  <td><label><input type="radio" name="pref_EMAIL_EVENT_DELETED" value="Y" <?php if ( $prefarray["EMAIL_EVENT_DELETED"] != "N" ) echo " checked=\"checked\"";?> /> <?php etranslate("Yes")?></label> <label><input type="radio" name="pref_EMAIL_EVENT_DELETED" value="N" <?php if ( $prefarray["EMAIL_EVENT_DELETED"] == "N" ) echo " checked=\"checked\"";?> /> <?php etranslate("No")?></label></td></tr>

<tr><td style="vertical-align:top; font-weight:bold;"><?php etranslate("Event rejected by participant")?>:</td>
  <td><label><input type="radio" name="pref_EMAIL_EVENT_REJECTED" value="Y" <?php if ( $prefarray["EMAIL_EVENT_REJECTED"] != "N" ) echo " checked=\"checked\"";?> /> <?php etranslate("Yes")?></label> <label><input type="radio" name="pref_EMAIL_EVENT_REJECTED" value="N" <?php if ( $prefarray["EMAIL_EVENT_REJECTED"] == "N" ) echo " checked=\"checked\"";?> /> <?php etranslate("No")?></label></td></tr>
</table>

<?php } /* if ( ! $updating_public ) */ ?>

<?php if ( ! $updating_public ) { ?>
<h3><?php etranslate("When I am the boss")?></h3>
<table class="standard" cellspacing="1" cellpadding="2">
<tr><td style="vertical-align:top; font-weight:bold;"><?php etranslate("Email me event notification")?>:</td>
  <td><label><input type="radio" name="pref_EMAIL_ASSISTANT_EVENTS" value="Y" <?php if ( $prefarray["EMAIL_ASSISTANT_EVENTS"] == "Y" ) echo " checked=\"checked\"";?> /> <?php etranslate("Yes")?></label> <label><input type="radio" name="pref_EMAIL_ASSISTANT_EVENTS" value="N" <?php if ( $prefarray["EMAIL_ASSISTANT_EVENTS"] == "N" ) echo " checked=\"checked\"";?> /> <?php etranslate("No")?></label></td></tr>

<tr><td style="vertical-align:top; font-weight:bold;"><?php etranslate("I want to approve events")?>:</td>
  <td><label><input type="radio" name="pref_APPROVE_ASSISTANT_EVENT" value="Y" <?php if ( $prefarray["APPROVE_ASSISTANT_EVENT"] == "Y" ) echo " checked=\"checked\"";?> /> <?php etranslate("Yes")?></label> <label><input type="radio" name="pref_APPROVE_ASSISTANT_EVENT" value="N" <?php if ( $prefarray["APPROVE_ASSISTANT_EVENT"] == "N" ) echo " checked=\"checked\"";?> /> <?php etranslate("No")?></label></td></tr>
</table>

<?php } /* if ( ! $updating_public ) */ ?>

<?php if ( $PUBLISH_ENABLED == 'Y' ) { ?>
<h3><?php etranslate("Subscribe/Publish")?></h3>
<table class="standard" cellspacing="1" cellpadding="2">
<tr><td style="vertical-align:top;" class="tooltip" title="<?php etooltip("allow-remote-subscriptions-help")?>"><?php etranslate("Allow remote subscriptions")?>:</td>
  <td><label><input type="radio" name="pref_USER_PUBLISH_ENABLED" value="Y" <?php if ( $prefarray["USER_PUBLISH_ENABLED"] == "Y" ) echo " checked=\"checked\"";?> /> <?php etranslate("Yes")?></label> <label><input type="radio" name="pref_USER_PUBLISH_ENABLED" value="N" <?php if ( $prefarray["USER_PUBLISH_ENABLED"] != "Y" ) echo " checked=\"checked\"";?> /> <?php etranslate("No")?></label></td></tr>
<?php if ( ! empty ( $server_url ) ) { ?>
<tr><td style="vertical-align:top;" class="tooltip" title="<?php etooltip("remote-subscriptions-url-help")?>">&nbsp;&nbsp;&nbsp;&nbsp;<?php etranslate("URL")?>:</td>
  <td>
  <?php
    echo htmlentities ( $server_url ) .
      "publish.php/" . ( $updating_public ? "public" : $login ) .  ".ics";
  ?></td></tr>
<?php } /* $server_url */ ?>
</table>
<?php } /* $PUBLISH_ENABLED == 'Y' */ ?>

<?php if ( $allow_color_customization == 'Y' ) { ?>

<h3><?php etranslate("Colors")?></h3>

<table style="border-width:0px; width:100%;"><tr><td style="vertical-align:top; text-align:left;">

<table class="standard" cellspacing="1" cellpadding="2">
<tr><td style="font-weight:bold;"><?php etranslate("Document background")?>:</td>
  <td><input type="text" name="pref_BGCOLOR" size="8" maxlength="7" value="<?php echo $prefarray["BGCOLOR"]; ?>" /> <input type="button" onclick="selectColor('pref_BGCOLOR')" value="<?php etranslate("Select")?>..." /></td></tr>
<tr><td style="font-weight:bold;"><?php etranslate("Document title")?>:</td>
  <td><input type="text" name="pref_H2COLOR" size="8" maxlength="7" value="<?php echo $prefarray["H2COLOR"]; ?>" /> <input type="button" onclick="selectColor('pref_H2COLOR')" value="<?php etranslate("Select")?>..." /></td></tr>
<tr><td style="font-weight:bold;"><?php etranslate("Table cell background")?>:</td>
  <td><input type="text" name="pref_CELLBG" size="8" maxlength="7" value="<?php echo $prefarray["CELLBG"]; ?>" /> <input type="button" onclick="selectColor('pref_CELLBG')" value="<?php etranslate("Select")?>..." /></td></tr>
<tr><td style="font-weight:bold;"><?php etranslate("Table cell background for current day")?>:</td>
  <td><input type="text" name="pref_TODAYCELLBG" size="8" maxlength="7" value="<?php echo $prefarray["TODAYCELLBG"]; ?>" /> <input type="button" onclick="selectColor('pref_TODAYCELLBG')" value="<?php etranslate("Select")?>..." /></td></tr>
<tr><td style="font-weight:bold;"><?php etranslate("Table cell background for weekends")?>:</td>
  <td><input type="text" name="pref_WEEKENDBG" size="8" maxlength="7" value="<?php echo $prefarray["WEEKENDBG"]; ?>" /> <input type="button" onclick="selectColor('pref_WEEKENDBG')" value="<?php etranslate("Select")?>..." /></td></tr>
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
<th style="width:14%;" class="tableheader"><?php etranslate("Sun")?></th>
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
      $class = $is_weekend ? "tablecellweekenddemo" : "tablecelldemo";
      $color = $is_weekend ? $prefarray["WEEKENDBG"] : $prefarray["CELLBG"];
      if ( empty ( $color ) )
        $color = "#C0C0C0";
      print "<td style=\"vertical-align:top; height:30px;\"";
      if ( date ( "Ymd", $date ) == date ( "Ymd", $today ) )
        echo " bgcolor=\"$prefarray[TODAYCELLBG]\">";
      else
        echo " bgcolor=\"$color\">";
      echo "&nbsp;";
      print "</td>\n";
    } else {
      print "<td style=\"vertical-align:top; height:30px; background-color:$prefarray[CELLBG];\">&nbsp;</td>\n";
    }
  }
  print "</tr>\n";
}

?>
</table></td></tr></table>

<!-- END EXAMPLE MONTH -->
<br /><br />

</td></tr></table>

<?php } // if $allow_color_customization ?>

<br /><br />
<table style="border-width:0px;"><tr><td>
<input type="submit" value="<?php etranslate("Save Preferences")?>" name="" />
<input type="button" value="<?php etranslate("Help")?>..." onclick="window.open ('help_pref.php', 'cal_help', 'dependent,menubar,scrollbars,height=400,width=400,innerHeight=420,outerWidth=420');" />
</td></tr></table>
</form>

<?php print_trailer(); ?>
</body>
</html>
