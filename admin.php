<?php

include "includes/config.php";
include "includes/php-dbi.php";
include "includes/functions.php";
include "includes/$user_inc";
include "includes/validate.php";
include "includes/connect.php";

load_global_settings ();
if ( empty ( $ovrd ) )
  load_user_preferences ();
load_user_layers ();

function print_color_sample ( $color ) {
  echo "<TABLE BORDER=\"0\"><TR><TD BGCOLOR=\"$color\">&nbsp;&nbsp;</TD></TR></TABLE>";
}

include "includes/translate.php";

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
    //echo "Setting '$setting' to '$value' <br> \n";
  }
  dbi_free_result ( $res );
}

?>
<HTML>
<HEAD>
<TITLE><?php etranslate($application_name)?></TITLE>
<SCRIPT LANGUAGE="JavaScript">
// error check the colors
function valid_color ( str ) {
  var ch, j;
  var valid = "0123456789abcdefABCDEF";

  if ( str.length == 0 )
    return true;

  if ( str.charAt ( 0 ) != '#' || str.length != 7 )
    return false;

  for ( j = 1; j < str.length; j++ ) {
   ch = str.charAt ( j );
   if ( valid.indexOf ( ch ) < 0 )
     return false;
  }
  return true;
}

function valid_form ( form ) {
  var err = "";

  if ( form.admin_server_url.value == "" ) {
    err += "<?php etranslate("Server URL is required")?>.\n";
    form.admin_server_url.select ();
    form.admin_server_url.focus ();
  }
  else if ( form.admin_server_url.value.charAt (
    form.admin_server_url.value.length - 1 ) != '/' ) {
    err += "<?php etranslate("Server URL must end with '/'")?>.\n";
    form.admin_server_url.select ();
    form.admin_server_url.focus ();
  }

  if ( err != "" ) {
    alert ( "Error:\n\n" + err );
    return false;
  }

  if ( ! valid_color ( form.admin_BGCOLOR.value ) ) {
    err += "<?php etranslate("Invalid color for document background")?>.\n";
    form.admin_BGCOLOR.select ();
    form.admin_BGCOLOR.focus ();
  }
  else if ( ! valid_color ( form.admin_H2COLOR.value ) ) {
    err += "<?php etranslate("Invalid color for document title")?>.\n";
    form.admin_H2COLOR.select ();
    form.admin_H2COLOR.focus ();
  } else if ( ! valid_color ( form.admin_CELLBG.value ) ) {
    err += "<?php etranslate("Invalid color for table cell background")?>.\n";
    form.admin_CELLBG.select ();
    form.admin_CELLBG.focus ();
  } else if ( ! valid_color ( form.admin_TABLEBG.value ) ) {
    err += "<?php etranslate("Invalid color for table grid")?>.\n";
    form.admin_TABLEBG.select ();
    form.admin_TABLEBG.focus ();
  } else if ( ! valid_color ( form.admin_THBG.value ) ) {
    err += "<?php etranslate("Invalid color for table header background")?>.\n";
    form.admin_THBG.select ();
    form.admin_THBG.focus ();
  } else if ( ! valid_color ( form.admin_THFG.value ) ) {
    err += "<?php etranslate("Invalid color for table text background")?>.\n";
    form.admin_THFG.select ();
    form.admin_THFG.focus ();
  } else if ( ! valid_color ( form.admin_POPUP_BG.value ) ) {
    err += "<?php etranslate("Invalid color for event popup background")?>.\n";
    form.admin_POPUP_BG.select ();
    form.admin_POPUP_BG.focus ();
  } else if ( ! valid_color ( form.admin_POPUP_FG.value ) ) {
    err += "<?php etranslate("Invalid color for event popup text")?>.\n";
    form.admin_POPUP_FG.select ();
    form.admin_POPUP_FG.focus ();
  } else if ( ! valid_color ( form.admin_TODAYCELLBG.value ) ) {
    err += "<?php etranslate("Invalid color for table cell background for today")?>.\n";
    form.admin_TODAYCELLBG.select ();
    form.admin_TODAYCELLBG.focus ();
  }

  if ( err.length > 0 ) {
    alert ( "Error:\n\n" + err + "\n\n<?php etranslate("Color format should be '#RRGGBB'")?>" );
    return false;
  }
  return true;
}
function selectColor ( color ) {
  url = "colors.php?color=" + color;
  var colorWindow = window.open(url,"ColorSelection","width=390,height=350,resizable=yes,scrollbars=yes");
}
</SCRIPT>
<?php include "includes/styles.php"; ?>
</HEAD>
<BODY BGCOLOR="<?php echo $BGCOLOR;?>" CLASS="defaulttext">

<H2><FONT COLOR="<?php echo $H2COLOR;?>"><?php etranslate("System Settings")?></FONT></H2>

<?php

$error = $false;

if ( ! $is_admin ) {
  etranslate ( "You are not authorized" );
  $error = true;
}
if ( empty ( $ovrd ) && ! $error ) {
  echo "<BLOCKQUOTE>" . translate ( "Note" ) . ": " .
    "<A HREF=\"pref.php\">" .
    translate ( "Your user preferences" ) . "</A> " .
    translate ( "may be affecting the appearance of this page.") . ".  " .
    "<A HREF=\"admin.php?ovrd=1\">" .
    translate ( "Click here" ) . "</A> " .
    translate ( "to not use your user preferences when viewing this page" ) .
    ".</BLOCKQUOTE>\n";
} else if ( ! $error ) {
  echo "<BLOCKQUOTE>" . translate ( "Note" ) . ": " .
    "<A HREF=\"pref.php\">" .
    translate ( "Your user preferences" ) . "</A> " .
    translate ( "are being ignored while viewing this page.") . ".  " .
    "<A HREF=\"admin.php\">" .
    translate ( "Click here" ) . "</A> " .
    translate ( "to load your user preferences when viewing this page" ) .
    ".</BLOCKQUOTE>\n";
}


if ( ! $error ) {
?>

<FORM ACTION="admin_handler.php" METHOD="POST" ONSUBMIT="return valid_form(this);" NAME="prefform">

<TABLE BORDER=0><TR><TD>
<INPUT TYPE="submit" VALUE="<?php etranslate("Save")?>">
<SCRIPT LANGUAGE="JavaScript">
  document.writeln ( '<INPUT TYPE="button" VALUE="<?php etranslate("Help")?>..." ONCLICK="window.open ( \'help_admin.php\', \'cal_help\', \'dependent,menubar,scrollbars,height=400,width=400,innerHeight=420,outerWidth=420\');">' );
</SCRIPT>
</TD></TR></TABLE>
<BR>


<?php if ( ! empty ( $ovrd ) ) { ?>
  <INPUT TYPE="hidden" NAME="ovrd" VALUE="1">
<?php } ?>

<H3><?php etranslate("Settings")?></H3>

<TABLE BORDER="0" CELLSPACING="0" CELLPADDING="0"><TR><TD BGCOLOR="#000000"><TABLE BORDER="0" WIDTH="100%" CELLSPACING="1" CELLPADDING="2"><TR><TD WIDTH="100%" BGCOLOR="<?php echo $CELLBG ?>"><TABLE BORDER="0" WIDTH="100%">

<TR><TD VALIGN="top"><B CLASS="tooltip" TITLE="<?php etooltip("app-name-help")?>"><?php etranslate("Application Name")?>:</B></TD>
  <TD><INPUT SIZE="40" NAME="admin_application_name" VALUE="<?php echo htmlspecialchars ( $application_name );?>" </TD></TR>


<TR><TD VALIGN="top"><B CLASS="tooltip" TITLE="<?php etooltip("server-url-help")?>"><?php etranslate("Server URL")?>:</B></TD>
  <TD><INPUT SIZE="40" NAME="admin_server_url" VALUE="<?php echo htmlspecialchars ( $server_url );?>" </TD></TR>


<TR><TD VALIGN="top"><B CLASS="tooltip" TITLE="<?php etooltip("language-help");?>"><?php etranslate("Language")?>:</B></TD>
<TD><SELECT NAME="admin_LANGUAGE">
<?php
reset ( $languages );
while ( list ( $key, $val ) = each ( $languages ) ) {
  echo "<OPTION VALUE=\"" . $val . "\"";
  if ( $val == $LANGUAGE ) echo " SELECTED";
  echo "> " . $key . "\n";
}
?>
</SELECT>
<BR>
<?php etranslate("Your browser default language is"); echo " " . get_browser_language () . "."; ?>
</TD></TR>

<TR><TD VALIGN="top"><B CLASS="tooltip" TITLE="<?php etooltip("fonts-help")?>"><?php etranslate("Fonts")?>:</B></TD>
  <TD><INPUT SIZE="40" NAME="admin_FONTS" VALUE="<?php echo htmlspecialchars ( $FONTS );?>" </TD></TR>

<TR><TD><B CLASS="tooltip" HREF="#" TITLE="<?php etooltip("preferred-view-help");?>"><?php etranslate("Preferred view")?>:</B></TD>
<TD>
<SELECT NAME="admin_STARTVIEW">
<OPTION VALUE="day" <?php if ( $s["STARTVIEW"] == "day" ) echo "SELECTED";?> ><?php etranslate("Day")?>
<OPTION VALUE="week" <?php if ( $s["STARTVIEW"] == "week" ) echo "SELECTED";?> ><?php etranslate("Week")?>
<OPTION VALUE="month" <?php if ( $s["STARTVIEW"] == "month" ) echo "SELECTED";?> ><?php etranslate("Month")?>
<OPTION VALUE="year" <?php if ( $s["STARTVIEW"] == "year" ) echo "SELECTED";?> ><?php etranslate("Year")?>
</SELECT></TD></TR>

<TR><TD><B CLASS="tooltip" TITLE="<?php etooltip("display-weekends-help");?>"><?php etranslate("Display weekends in week view")?>:</B></TD>
  <TD><INPUT TYPE="radio" NAME="admin_DISPLAY_WEEKENDS" VALUE="Y" <?php if ( $s["DISPLAY_WEEKENDS"] != "N" ) echo "CHECKED";?>> <?php etranslate("Yes")?> <INPUT TYPE="radio" NAME="admin_DISPLAY_WEEKENDS" VALUE="N" <?php if ( $s["DISPLAY_WEEKENDS"] == "N" ) echo "CHECKED";?>> <?php etranslate("No")?></TD></TR>

<TR><TD VALIGN="top"><B CLASS="tooltip" TITLE="<?php etooltip("date-format-help");?>"><?php etranslate("Date format")?>:</B></TD>
  <TD><SELECT NAME="admin_DATE_FORMAT">
  <?php
  // You can add new date formats below if you want.
  // but also add in pref.php.
  $datestyles = array (
    "month dd, yyyy", translate("December") . " 31, 2000",
    "dd month, yyyy", "31 " . translate("December") . ", 2000",
    "dd-month-yyyy", "31-" . translate("December") . "-2000",
    "dd-month-yy", "31-" . translate("December") . "-00",
    "mm/dd/yyyy", "12/31/2000",
    "mm/dd/yy", "12/31/00",
    "mm-dd-yyyy", "12-31-2000",
    "mm-dd-yy", "12-31-00",
    "yyyy-mm-dd", "2000-12-31",
    "yy-mm-dd", "00-12-31",
    "yyyy/mm/dd", "2000/12/31",
    "yy/mm/dd", "00/12/31",
    "dd/mm/yyyy", "31/12/2000",
    "dd/mm/yy", "31/12/00",
    "dd-mm-yyyy", "31-12-2000",
    "dd-mm-yy", "31-12-00"
  );
  for ( $i = 0; $i < count ( $datestyles ); $i += 2 ) {
    echo "<OPTION VALUE=\"" . $datestyles[$i] . "\"";
    if ( $s["DATE_FORMAT"] == $datestyles[$i] )
      echo " SELECTED";
    echo "> " . $datestyles[$i + 1] . "\n";
  }
  ?>
</SELECT>
<BR>
  <SELECT NAME="admin_DATE_FORMAT_MY">
  <?php
  // Date format for a month and year (with no day of the month)
  // You can add new date formats below if you want.
  // but also add in admin.php.
  $datestyles = array (
    "month yyyy", translate("December") . " 2000",
    "month yy", translate("December") . " 00",
    "month-yyyy", translate("December") . "-2000",
    "month-yy", translate("December") . "-00",
    "mm/yyyy", "12/2000",
    "mm/yy", "12/00",
    "mm-yyyy", "12-2000",
    "mm-yy", "12-00",
    "yyyy-mm", "2000-12",
    "yy-mm", "00-12",
    "yyyy/mm", "2000/12",
    "yy/mm", "00/12"
  );
  for ( $i = 0; $i < count ( $datestyles ); $i += 2 ) {
    echo "<OPTION VALUE=\"" . $datestyles[$i] . "\"";
    if ( $prefarray["DATE_FORMAT_MY"] == $datestyles[$i] )
      echo " SELECTED";
    echo "> " . $datestyles[$i + 1] . "\n";
  }
  ?>
  </SELECT>
  <BR>
  <SELECT NAME="admin_DATE_FORMAT_MD">
  <?php
  // Date format for a month and day (with no year displayed)
  // You can add new date formats below if you want.
  // but also add in admin.php.
  $datestyles = array (
    "month dd", translate("December") . " 31",
    "month-dd", translate("December") . "-31",
    "mm/dd", "12/31",
    "mm-dd", "12-31",
    "dd/mm", "31/12",
    "dd-mm", "31-12"
  );
  for ( $i = 0; $i < count ( $datestyles ); $i += 2 ) {
    echo "<OPTION VALUE=\"" . $datestyles[$i] . "\"";
    if ( $prefarray["DATE_FORMAT_MD"] == $datestyles[$i] )
      echo " SELECTED";
    echo "> " . $datestyles[$i + 1] . "\n";
  }
  ?>
  </SELECT>
</TD></TR>

<TR><TD><B CLASS="tooltip" TITLE="<?php etooltip("time-format-help")?>"><?php etranslate("Time format")?>:</B></TD>
  <TD><INPUT TYPE="radio" NAME="admin_TIME_FORMAT" VALUE="12" <?php if ( $s["TIME_FORMAT"] == "12" ) echo "CHECKED";?>> <?php etranslate("12 hour")?> <INPUT TYPE="radio" NAME="admin_TIME_FORMAT" VALUE="24" <?php if ( $s["TIME_FORMAT"] != "12" ) echo "CHECKED";?>> <?php etranslate("24 hour")?></TD></TR>

<TR><TD><B CLASS="tooltip" TITLE="<?php etooltip("time-interval-help")?>"><?php etranslate("Time interval")?>:</B></TD>
  <TD><SELECT NAME="admin_TIME_SLOTS">
  <OPTION VALUE="24" <?php if ( $s["TIME_SLOTS"] == "24" ) echo "SELECTED"?>>1 <?php etranslate("hour")?>
  <OPTION VALUE="48" <?php if ( $s["TIME_SLOTS"] == "48" ) echo "SELECTED"?>>30 <?php etranslate("minutes")?>
  <OPTION VALUE="72" <?php if ( $s["TIME_SLOTS"] == "72" ) echo "SELECTED"?>>20 <?php etranslate("minutes")?>
  <OPTION VALUE="144" <?php if ( $s["TIME_SLOTS"] == "144" ) echo "SELECTED"?>>10 <?php etranslate("minutes")?>
  </SELECT></TD></TR>


<TR><TD><B CLASS="tooltip" TITLE="<?php etooltip("auto-refresh-help");?>"><?php etranslate("Auto-refresh calendars")?>:</B></TD>
  <TD><INPUT TYPE="radio" NAME="admin_auto_refresh" VALUE="Y" <?php if ( $s["auto_refresh"] == "Y" ) echo "CHECKED";?>> <?php etranslate("Yes")?> <INPUT TYPE="radio" NAME="admin_auto_refresh" VALUE="N" <?php if ( $s["auto_refresh"] != "Y" ) echo "CHECKED";?>> <?php etranslate("No")?></TD></TR>

<TR><TD>&nbsp;&nbsp;&nbsp;&nbsp;<B CLASS="tooltip" TITLE="<?php etooltip("auto-refresh-time-help");?>"><?php etranslate("Auto-refresh time")?>:</B></TD>
  <TD><INPUT NAME="admin_auto_refresh_time" SIZE="4" VALUE="<?php if ( empty ( $s["auto_refresh_time"] ) ) echo "0"; else echo $s["auto_refresh_time"]; ?>"> <?php etranslate("minutes")?></TD></TR>

<TR><TD><B CLASS="tooltip" TITLE="<?php etooltip("require-approvals-help");?>"><?php etranslate("Require event approvals")?>:</B></TD>
  <TD><INPUT TYPE="radio" NAME="admin_require_approvals" VALUE="Y" <?php if ( $s["require_approvals"] != "N" ) echo "CHECKED";?>> <?php etranslate("Yes")?> <INPUT TYPE="radio" NAME="admin_require_approvals" VALUE="N" <?php if ( $s["require_approvals"] == "N" ) echo "CHECKED";?>> <?php etranslate("No")?></TD></TR>

<TR><TD>&nbsp;&nbsp;&nbsp;&nbsp;<B CLASS="tooltip" TITLE="<?php etooltip("display-unapproved-help");?>"><?php etranslate("Display unapproved")?>:</B></TD>
  <TD><INPUT TYPE="radio" NAME="admin_DISPLAY_UNAPPROVED" VALUE="Y" <?php if ( $s["DISPLAY_UNAPPROVED"] != "N" ) echo "CHECKED";?>> <?php etranslate("Yes")?> <INPUT TYPE="radio" NAME="admin_DISPLAY_UNAPPROVED" VALUE="N" <?php if ( $s["DISPLAY_UNAPPROVED"] == "N" ) echo "CHECKED";?>> <?php etranslate("No")?></TD></TR>

<TR><TD><B CLASS="tooltip" TITLE="<?php etooltip("display-week-number-help")?>"><?php etranslate("Display week number")?>:</B></TD>
  <TD><INPUT TYPE="radio" NAME="admin_DISPLAY_WEEKNUMBER" VALUE="Y" <?php if ( $s["DISPLAY_WEEKNUMBER"] != "N" ) echo "CHECKED";?>> <?php etranslate("Yes")?> <INPUT TYPE="radio" NAME="admin_DISPLAY_WEEKNUMBER" VALUE="N" <?php if ( $s["DISPLAY_WEEKNUMBER"] == "N" ) echo "CHECKED";?>> <?php etranslate("No")?></TD></TR>

<TR><TD><B CLASS="tooltip" TITLE="<?php etooltip("display-week-starts-on")?>"><?php etranslate("Week starts on")?>:</B></TD>
  <TD><INPUT TYPE="radio" NAME="admin_WEEK_START" VALUE="0" <?php if ( $s["WEEK_START"] != "1" ) echo "CHECKED";?>> <?php etranslate("Sunday")?> <INPUT TYPE="radio" NAME="admin_WEEK_START" VALUE="1" <?php if ( $s["WEEK_START"] == "1" ) echo "CHECKED";?>> <?php etranslate("Monday")?></TD></TR>

<TR><TD><B CLASS="tooltip" TITLE="<?php etooltip("work-hours-help")?>"><?php etranslate("Work hours")?>:</B></TD>
  <TD>
  <?php etranslate("From")?> <SELECT NAME="admin_WORK_DAY_START_HOUR">
  <?php
  for ( $i = 0; $i < 24; $i++ ) {
    echo "<OPTION VALUE=\"$i\" " .
      ( $i == $s["WORK_DAY_START_HOUR"] ? "SELECTED " : "" ) .
      "> " . display_time ( $i * 10000 );
  }
  ?>
  </SELECT> <?php etranslate("to")?>
  <SELECT NAME="admin_WORK_DAY_END_HOUR">
  <?php
  for ( $i = 0; $i < 24; $i++ ) {
    echo "<OPTION VALUE=\"$i\" " .
      ( $i == $s["WORK_DAY_END_HOUR"] ? "SELECTED " : "" ) .
      "> " . display_time ( $i * 10000 );
  }
  ?>
  </SELECT>
  </TD></TR>

<TR><TD><B CLASS="tooltip" TITLE="<?php etooltip("disable-priority-field-help")?>"><?php etranslate("Disable Priority field")?>:</B></TD>
  <TD><INPUT TYPE="radio" NAME="admin_disable_priority_field" VALUE="Y" <?php if ( $s["disable_priority_field"] != "N" ) echo "CHECKED";?>> <?php etranslate("Yes")?> <INPUT TYPE="radio" NAME="admin_disable_priority_field" VALUE="N" <?php if ( $s["disable_priority_field"] == "N" ) echo "CHECKED";?>> <?php etranslate("No")?></TD></TR>

<TR><TD><B CLASS="tooltip" TITLE="<?php etooltip("disable-access-field-help")?>"><?php etranslate("Disable Access field")?>:</B></TD>
  <TD><INPUT TYPE="radio" NAME="admin_disable_access_field" VALUE="Y" <?php if ( $s["disable_access_field"] != "N" ) echo "CHECKED";?>> <?php etranslate("Yes")?> <INPUT TYPE="radio" NAME="admin_disable_access_field" VALUE="N" <?php if ( $s["disable_access_field"] == "N" ) echo "CHECKED";?>> <?php etranslate("No")?></TD></TR>

<TR><TD><B CLASS="tooltip" TITLE="<?php etooltip("disable-participants-field-help")?>"><?php etranslate("Disable Participants field")?>:</B></TD>
  <TD><INPUT TYPE="radio" NAME="admin_disable_participants_field" VALUE="Y" <?php if ( $s["disable_participants_field"] != "N" ) echo "CHECKED";?>> <?php etranslate("Yes")?> <INPUT TYPE="radio" NAME="admin_disable_participants_field" VALUE="N" <?php if ( $s["disable_participants_field"] == "N" ) echo "CHECKED";?>> <?php etranslate("No")?></TD></TR>

<TR><TD><B CLASS="tooltip" TITLE="<?php etooltip("disable-repeating-field-help")?>"><?php etranslate("Disable Repeating field")?>:</B></TD>
  <TD><INPUT TYPE="radio" NAME="admin_disable_repeating_field" VALUE="Y" <?php if ( $s["disable_repeating_field"] != "N" ) echo "CHECKED";?>> <?php etranslate("Yes")?> <INPUT TYPE="radio" NAME="admin_disable_repeating_field" VALUE="N" <?php if ( $s["disable_repeating_field"] == "N" ) echo "CHECKED";?>> <?php etranslate("No")?></TD></TR>

<TR><TD><B CLASS="tooltip" TITLE="<?php etooltip("allow-public-access-help")?>"><?php etranslate("Allow public access")?>:</B></TD>
  <TD><INPUT TYPE="radio" NAME="admin_public_access" VALUE="Y" <?php if ( $s["public_access"] == "Y" ) echo "CHECKED";?>> <?php etranslate("Yes")?> <INPUT TYPE="radio" NAME="admin_public_access" VALUE="N" <?php if ( $s["public_access"] != "Y" ) echo "CHECKED";?>> <?php etranslate("No")?></TD></TR>

<TR><TD>&nbsp;&nbsp;&nbsp;&nbsp;<B CLASS="tooltip" TITLE="<?php etooltip("public-access-view-others-help")?>"><?php etranslate("Public access can view other users")?>:</B></TD>
  <TD><INPUT TYPE="radio" NAME="admin_public_access_others" VALUE="Y" <?php if ( $s["public_access_others"] == "Y" ) echo "CHECKED";?>> <?php etranslate("Yes")?> <INPUT TYPE="radio" NAME="admin_public_access_others" VALUE="N" <?php if ( $s["public_access_others"] != "Y" ) echo "CHECKED";?>> <?php etranslate("No")?></TD></TR>

<TR><TD>&nbsp;&nbsp;&nbsp;&nbsp;<B CLASS="tooltip" TITLE="<?php etooltip("public-access-can-add-help")?>"><?php etranslate("Public access can add events")?>:</B></TD>
  <TD><INPUT TYPE="radio" NAME="admin_public_access_can_add" VALUE="Y" <?php if ( $s["public_access_can_add"] == "Y" ) echo "CHECKED";?>> <?php etranslate("Yes")?> <INPUT TYPE="radio" NAME="admin_public_access_can_add" VALUE="N" <?php if ( $s["public_access_can_add"] != "Y" ) echo "CHECKED";?>> <?php etranslate("No")?></TD></TR>

<TR><TD><B CLASS="tooltip" TITLE="<?php etooltip("allow-view-other-help")?>"><?php etranslate("Allow viewing other user's calendars")?>:</B></TD>
  <TD><INPUT TYPE="radio" NAME="admin_allow_view_other" VALUE="Y" <?php if ( $s["allow_view_other"] != "N" ) echo "CHECKED";?>> <?php etranslate("Yes")?> <INPUT TYPE="radio" NAME="admin_allow_view_other" VALUE="N" <?php if ( $s["allow_view_other"] == "N" ) echo "CHECKED";?>> <?php etranslate("No")?></TD></TR>

<TR><TD><B CLASS="tooltip" TITLE="<?php etooltip("remember-last-login-help")?>"><?php etranslate("Remember last login")?>:</B></TD>
  <TD><INPUT TYPE="radio" NAME="admin_remember_last_login" VALUE="Y" <?php if ( $s["remember_last_login"] != "N" ) echo "CHECKED";?>> <?php etranslate("Yes")?> <INPUT TYPE="radio" NAME="admin_remember_last_login" VALUE="N" <?php if ( $s["remember_last_login"] == "N" ) echo "CHECKED";?>> <?php etranslate("No")?></TD></TR>

<TR><TD><B CLASS="tooltip" TITLE="<?php etooltip("conflict-check-help")?>"><?php etranslate("Check for event conflicts")?>:</B></TD>
  <TD><INPUT TYPE="radio" NAME="admin_allow_conflicts" VALUE="N" <?php if ( $s["allow_conflicts"] == "N" ) echo "CHECKED";?>> <?php etranslate("Yes")?> <INPUT TYPE="radio" NAME="admin_allow_conflicts" VALUE="Y" <?php if ( $s["allow_conflicts"] != "N" ) echo "CHECKED";?>> <?php etranslate("No")?></TD></TR>

<TR><TD VALIGN="top">&nbsp;&nbsp;&nbsp;&nbsp;<B CLASS="tooltip" TITLE="<?php etooltip("conflict-months-help")?>"><?php etranslate("Conflict checking months")?>:</B></TD>
  <TD><INPUT SIZE="3" NAME="admin_conflict_repeat_months" VALUE="<?php echo htmlspecialchars ( $conflict_repeat_months );?>" </TD></TR>



</TABLE></TD></TR></TABLE></TD></TR></TABLE>




<H3><?php etranslate("Groups")?></H3>
<TABLE BORDER="0" CELLSPACING="0" CELLPADDING="0"><TR><TD BGCOLOR="#000000"><TABLE BORDER="0" WIDTH="100%" CELLSPACING="1" CELLPADDING="2"><TR><TD WIDTH="100%" BGCOLOR="<?php echo $CELLBG ?>"><TABLE BORDER="0" WIDTH="100%">

<TR><TD VALIGN="top"><B CLASS="tooltip" TITLE="<?php etooltip("groups-enabled-help")?>"><?php etranslate("Groups enabled")?>:</B></TD>
  <TD><INPUT TYPE="radio" NAME="admin_groups_enabled" VALUE="Y" <?php if ( $s["groups_enabled"] == "Y" ) echo "CHECKED";?>> <?php etranslate("Yes")?> <INPUT TYPE="radio" NAME="admin_groups_enabled" VALUE="N" <?php if ( $s["groups_enabled"] != "Y" ) echo "CHECKED";?>> <?php etranslate("No")?></TD></TR>

<TR><TD VALIGN="top"><B CLASS="tooltip" TITLE="<?php etooltip("user-sees-his-group-help")?>"><?php etranslate("User sees only his groups")?>:</B></TD>
  <TD><INPUT TYPE="radio" NAME="admin_user_sees_only_his_groups" VALUE="Y" <?php if ( $s["user_sees_only_his_groups"] == "Y" ) echo "CHECKED";?>> <?php etranslate("Yes")?> <INPUT TYPE="radio" NAME="admin_user_sees_only_his_groups" VALUE="N" <?php if ( $s["user_sees_only_his_groups"] != "Y" ) echo "CHECKED";?>> <?php etranslate("No")?></TD></TR>


</TABLE></TD></TR></TABLE></TD></TR></TABLE>



<H3><?php etranslate("Categories")?></H3>
<TABLE BORDER="0" CELLSPACING="0" CELLPADDING="0"><TR><TD BGCOLOR="#000000"><TABLE BORDER="0" WIDTH="100%" CELLSPACING="1" CELLPADDING="2"><TR><TD WIDTH="100%" BGCOLOR="<?php echo $CELLBG ?>"><TABLE BORDER="0" WIDTH="100%">

<TR><TD VALIGN="top"><B CLASS="tooltip" TITLE="<?php etooltip("categories-enabled-help")?>"><?php etranslate("Categories enabled")?>:</B></TD>
  <TD><INPUT TYPE="radio" NAME="admin_categories_enabled" VALUE="Y" <?php if ( $s["categories_enabled"] == "Y" ) echo "CHECKED";?>> <?php etranslate("Yes")?> <INPUT TYPE="radio" NAME="admin_categories_enabled" VALUE="N" <?php if ( $s["categories_enabled"] != "Y" ) echo "CHECKED";?>> <?php etranslate("No")?></TD></TR>

</TABLE></TD></TR></TABLE></TD></TR></TABLE>




<H3><?php etranslate("Email")?></H3>

<TABLE BORDER="0" CELLSPACING="0" CELLPADDING="0"><TR><TD BGCOLOR="#000000"><TABLE BORDER="0" WIDTH="100%" CELLSPACING="1" CELLPADDING="2"><TR><TD WIDTH="100%" BGCOLOR="<?php echo $CELLBG ?>"><TABLE BORDER="0" WIDTH="100%">

<TR><TD VALIGN="top"><B CLASS="tooltip" TITLE="<?php etooltip("email-enabled-help")?>"><?php etranslate("Email enabled")?>:</B></TD>
  <TD><INPUT TYPE="radio" NAME="admin_send_email" VALUE="Y" <?php if ( $s["send_email"] == "Y" ) echo "CHECKED";?>> <?php etranslate("Yes")?> <INPUT TYPE="radio" NAME="admin_send_email" VALUE="N" <?php if ( $s["send_email"] != "Y" ) echo "CHECKED";?>> <?php etranslate("No")?></TD></TR>

<TR><TD VALIGN="top">&nbsp;&nbsp;&nbsp;&nbsp;<B CLASS="tooltip" TITLE="<?php etooltip("email-default-sender")?>"><?php etranslate("Default sender address")?>:</B></TD>
  <TD><INPUT SIZE="30" NAME="admin_email_fallback_from" VALUE="<?php echo htmlspecialchars ( $email_fallback_from );?>" </TD></TR>


<TR><TD VALIGN="top" COLSPAN="2"><B><?php etranslate("Default user settings")?>:</B></TD></TR>

<TR><TD VALIGN="top">&nbsp;&nbsp;&nbsp;&nbsp;<B CLASS="tooltip" TITLE="<?php etooltip("email-event-reminders-help")?>"><?php etranslate("Event reminders")?>:</B></TD>
  <TD><INPUT TYPE="radio" NAME="admin_EMAIL_REMINDER" VALUE="Y" <?php if ( $s["EMAIL_REMINDER"] != "N" ) echo "CHECKED";?>> <?php etranslate("Yes")?> <INPUT TYPE="radio" NAME="admin_EMAIL_REMINDER" VALUE="N" <?php if ( $s["EMAIL_REMINDER"] == "N" ) echo "CHECKED";?>> <?php etranslate("No")?></TD></TR>

<TR><TD VALIGN="top">&nbsp;&nbsp;&nbsp;&nbsp;<B CLASS="tooltip" TITLE="<?php etooltip("email-event-added")?>"><?php etranslate("Events added to my calendar")?>:</B></TD>
  <TD><INPUT TYPE="radio" NAME="admin_EMAIL_EVENT_ADDED" VALUE="Y" <?php if ( $s["EMAIL_EVENT_ADDED"] != "N" ) echo "CHECKED";?>> <?php etranslate("Yes")?> <INPUT TYPE="radio" NAME="admin_EMAIL_EVENT_ADDED" VALUE="N" <?php if ( $s["EMAIL_EVENT_ADDED"] == "N" ) echo "CHECKED";?>> <?php etranslate("No")?></TD></TR>

<TR><TD VALIGN="top">&nbsp;&nbsp;&nbsp;&nbsp;<B CLASS="tooltip" TITLE="<?php etooltip("email-event-updated")?>"><?php etranslate("Events updated on my calendar")?>:</B></TD>
  <TD><INPUT TYPE="radio" NAME="admin_EMAIL_EVENT_UPDATED" VALUE="Y" <?php if ( $s["EMAIL_EVENT_UPDATED"] != "N" ) echo "CHECKED";?>> <?php etranslate("Yes")?> <INPUT TYPE="radio" NAME="admin_EMAIL_EVENT_UPDATED" VALUE="N" <?php if ( $s["EMAIL_EVENT_UPDATED"] == "N" ) echo "CHECKED";?>> <?php etranslate("No")?></TD></TR>

<TR><TD VALIGN="top">&nbsp;&nbsp;&nbsp;&nbsp;<B CLASS="tooltip" TITLE="<?php etooltip("email-event-deleted");?>"><?php etranslate("Events removed from my calendar")?>:</B></TD>
  <TD><INPUT TYPE="radio" NAME="admin_EMAIL_EVENT_DELETED" VALUE="Y" <?php if ( $s["EMAIL_EVENT_DELETED"] != "N" ) echo "CHECKED";?>> <?php etranslate("Yes")?> <INPUT TYPE="radio" NAME="admin_EMAIL_EVENT_DELETED" VALUE="N" <?php if ( $s["EMAIL_EVENT_DELETED"] == "N" ) echo "CHECKED";?>> <?php etranslate("No")?></TD></TR>

<TR><TD VALIGN="top">&nbsp;&nbsp;&nbsp;&nbsp;<B CLASS="tooltip" TITLE="<?php etooltip("email-event-rejected")?>"><?php etranslate("Event rejected by participant")?>:</B></TD>
  <TD><INPUT TYPE="radio" NAME="admin_EMAIL_EVENT_REJECTED" VALUE="Y" <?php if ( $s["EMAIL_EVENT_REJECTED"] != "N" ) echo "CHECKED";?>> <?php etranslate("Yes")?> <INPUT TYPE="radio" NAME="admin_EMAIL_EVENT_REJECTED" VALUE="N" <?php if ( $s["EMAIL_EVENT_REJECTED"] == "N" ) echo "CHECKED";?>> <?php etranslate("No")?></TD></TR>


</TABLE></TD></TR></TABLE></TD></TR></TABLE>


<H3><SPAN CLASS="tooltip" TITLE="<?php etooltip("colors-help")?>"><?php etranslate("Colors")?></SPAN></H3>

<TABLE BORDER="0" CELLSPACING="0" CELLPADDING="0"><TR><TD BGCOLOR="#000000"><TABLE BORDER="0" WIDTH="100%" CELLSPACING="1" CELLPADDING="2"><TR><TD WIDTH="100%" BGCOLOR="<?php echo $CELLBG ?>"><TABLE BORDER="0" WIDTH="100%">

<TR><TD VALIGN="top"><B><?php etranslate("Allow user to customize colors")?>:</B></TD>
  <TD COLSPAN="3"><INPUT TYPE="radio" NAME="admin_allow_color_customization" VALUE="Y" <?php if ( $s["allow_color_customization"] != "N" ) echo "CHECKED";?>> <?php etranslate("Yes")?> <INPUT TYPE="radio" NAME="admin_allow_color_customization" VALUE="N" <?php if ( $s["allow_color_customization"] == "N" ) echo "CHECKED";?>> <?php etranslate("No")?></TD></TR>


<TR><TD><B><?php etranslate("Document background")?>:</B></TD>
  <TD><INPUT NAME="admin_BGCOLOR" SIZE="8" MAXLENGTH="7" VALUE="<?php echo $s["BGCOLOR"]; ?>"></TD><TD BGCOLOR="<?php echo $s["BGCOLOR"]?>">&nbsp;</TD><TD> <INPUT TYPE="button" ONCLICK="selectColor('admin_BGCOLOR')" VALUE="<?php etranslate("Select")?>..."></TD></TR>

<TR><TD><B><?php etranslate("Document title")?>:</B></TD>
  <TD><INPUT NAME="admin_H2COLOR" SIZE="8" MAXLENGTH="7" VALUE="<?php echo $s["H2COLOR"]; ?>"> </TD><TD BGCOLOR="<?php echo $s["H2COLOR"]?>">&nbsp;</TD><TD> <INPUT TYPE="button" ONCLICK="selectColor('admin_H2COLOR')" VALUE="<?php etranslate("Select")?>..."></TD></TR>

<TR><TD><B><?php etranslate("Document text")?>:</B></TD>
  <TD><INPUT NAME="admin_TEXTCOLOR" SIZE="8" MAXLENGTH="7" VALUE="<?php echo $s["TEXTCOLOR"]; ?>"> </TD><TD BGCOLOR="<?php echo $s["TEXTCOLOR"]?>">&nbsp;</TD><TD> <INPUT TYPE="button" ONCLICK="selectColor('admin_TEXTCOLOR')" VALUE="<?php etranslate("Select")?>..."></TD></TR>

<TR><TD><B><?php etranslate("Table grid color")?>:</B></TD>
  <TD><INPUT NAME="admin_TABLEBG" SIZE="8" MAXLENGTH="7" VALUE="<?php echo $s["TABLEBG"]; ?>"> </TD><TD BGCOLOR="<?php echo $s["TABLEBG"]?>">&nbsp;</TD><TD><INPUT TYPE="button" ONCLICK="selectColor('admin_TABLEBG')" VALUE="<?php etranslate("Select")?>..."></TD></TR>

<TR><TD><B><?php etranslate("Table header background")?>:</B></TD>
  <TD><INPUT NAME="admin_THBG" SIZE="8" MAXLENGTH="7" VALUE="<?php echo $s["THBG"]; ?>"></TD><TD BGCOLOR="<?php echo $s["THBG"]?>">&nbsp;</TD><TD> <INPUT TYPE="button" ONCLICK="selectColor('admin_THBG')" VALUE="<?php etranslate("Select")?>..."></TD></TR>

	<TR><TD><B><?php etranslate("Table header text")?>:</B></TD>
	  <TD><INPUT NAME="admin_THFG" SIZE="8" MAXLENGTH="7" VALUE="<?php echo $s["THFG"]; ?>"></TD><TD BGCOLOR="<?php echo $s["THFG"]?>">&nbsp;</TD><TD> <INPUT TYPE="button" ONCLICK="selectColor('admin_THFG')" VALUE="<?php etranslate("Select")?>..."></TD></TR>

	<TR><TD><B><?php etranslate("Table cell background")?>:</B></TD>
	  <TD><INPUT NAME="admin_CELLBG" SIZE="8" MAXLENGTH="7" VALUE="<?php echo $s["CELLBG"]; ?>"></TD><TD BGCOLOR="<?php echo $s["CELLBG"]?>">&nbsp;</TD><TD> <INPUT TYPE="button" ONCLICK="selectColor('admin_CELLBG')" VALUE="<?php etranslate("Select")?>..."></TD></TR>

	<TR><TD><B><?php etranslate("Table cell background for current day")?>:</B></TD>
	  <TD><INPUT NAME="admin_TODAYCELLBG" SIZE="8" MAXLENGTH="7" VALUE="<?php echo $s["TODAYCELLBG"]; ?>"></TD><TD BGCOLOR="<?php echo $s["TODAYCELLBG"]?>">&nbsp;</TD><TD> <INPUT TYPE="button" ONCLICK="selectColor('admin_TODAYCELLBG')" VALUE="<?php etranslate("Select")?>..."></TD></TR>

	<TR><TD><B><?php etranslate("Table cell background for weekends")?>:</B></TD>
	  <TD><INPUT NAME="admin_WEEKENDBG" SIZE="8" MAXLENGTH="7" VALUE="<?php echo $s["WEEKENDBG"]; ?>"></TD><TD BGCOLOR="<?php echo $s["WEEKENDBG"]?>">&nbsp;</TD><TD> <INPUT TYPE="button" ONCLICK="selectColor('admin_WEEKENDBG')" VALUE="<?php etranslate("Select")?>..."></TD></TR>

	<TR><TD><B><?php etranslate("Event popup background")?>:</B></TD>
	  <TD><INPUT NAME="admin_POPUP_BG" SIZE="8" MAXLENGTH="7" VALUE="<?php echo $s["POPUP_BG"]; ?>"></TD><TD BGCOLOR="<?php echo $s["POPUP_BG"]?>">&nbsp;</TD><TD> <INPUT TYPE="button" ONCLICK="selectColor('admin_POPUP_BG')" VALUE="<?php etranslate("Select")?>..."></TD></TR>

<TR><TD><B><?php etranslate("Event popup text")?>:</B></TD>
  <TD><INPUT NAME="admin_POPUP_FG" SIZE="8" MAXLENGTH="7" VALUE="<?php echo $s["POPUP_FG"]; ?>"></TD><TD BGCOLOR="<?php echo $s["POPUP_FG"]?>">&nbsp;</TD><TD> <INPUT TYPE="button" ONCLICK="selectColor('admin_POPUP_FG')" VALUE="<?php etranslate("Select")?>..."></TD></TR>

</TABLE></TD></TR></TABLE></TD></TR></TABLE>


<BR><BR>
<TABLE BORDER=0><TR><TD>
<INPUT TYPE="submit" VALUE="<?php etranslate("Save")?>">
<SCRIPT LANGUAGE="JavaScript">
  document.writeln ( '<INPUT TYPE="button" VALUE="<?php etranslate("Help")?>..." ONCLICK="window.open ( \'help_admin.php\', \'cal_help\', \'dependent,menubar,scrollbars,height=400,width=400,innerHeight=420,outerWidth=420\');">' );
</SCRIPT>
</TD></TR></TABLE>


</FORM>

<?php } // if $error ?>

<?php include "includes/trailer.php"; ?>
</BODY>
</HTML>
