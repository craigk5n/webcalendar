<?php php_track_vars?>
<?php

include "includes/config.inc";
include "includes/php-dbi.inc";
include "includes/functions.inc";
include "includes/$user_inc";
include "includes/validate.inc";
include "includes/connect.inc";

load_user_preferences ();
load_user_layers ();

include "includes/translate.inc";

?>
<HTML>
<HEAD>
<TITLE><?php etranslate("Title")?></TITLE>
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
  if ( ! valid_color ( form.pref_BGCOLOR.value ) )
    err += "<?php etranslate("Invalid color for document background")?>.\n";
  if ( ! valid_color ( form.pref_H2COLOR.value ) )
    err += "<?php etranslate("Invalid color for document title")?>.\n";
  if ( ! valid_color ( form.pref_CELLBG.value ) )
    err += "<?php etranslate("Invalid color for table cell background")?>.\n";
  if ( ! valid_color ( form.pref_TODAYCELLBG.value ) )
    err += "<?php etranslate("Invalid color for table cell background for today")?>.\n";
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
<?php include "includes/styles.inc"; ?>
</HEAD>
<BODY BGCOLOR="<?php echo $BGCOLOR;?>">

<H2><FONT COLOR="<?php echo $H2COLOR;?>"><?php etranslate("Preferences")?></FONT></H2>

<FORM ACTION="pref_handler.php" METHOD="POST" ONSUBMIT="return valid_form(this);" NAME="prefform">


<H3><?php etranslate("Settings")?></H3>

<TABLE BORDER="0" CELLSPACING="0" CELLPADDING="0"><TR><TD BGCOLOR="#000000"><TABLE BORDER="0" WIDTH="100%" CELLSPACING="1" CELLPADDING="2"><TR><TD WIDTH="100%" BGCOLOR="<?php echo $CELLBG ?>"><TABLE BORDER="0" WIDTH="100%">
<TR><TD VALIGN="top"><B><?php etranslate("Language")?>:</B></TD>
<TD><SELECT NAME="pref_LANGUAGE">
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
<TR><TD><B><?php etranslate("Preferred view")?>:</B></TD>
<TD>
<SELECT NAME="pref_STARTVIEW">
<OPTION VALUE="day" <?php if ( $STARTVIEW == "day" ) echo "SELECTED";?> ><?php etranslate("Day")?>
<OPTION VALUE="week" <?php if ( $STARTVIEW == "week" ) echo "SELECTED";?> ><?php etranslate("Week")?>
<OPTION VALUE="month" <?php if ( $STARTVIEW == "month" ) echo "SELECTED";?> ><?php etranslate("Month")?>
<OPTION VALUE="year" <?php if ( $STARTVIEW == "year" ) echo "SELECTED";?> ><?php etranslate("Year")?>
</SELECT></TD></TR>
<TR><TD><B><?php etranslate("Time format")?>:</B></TD>
  <TD><INPUT TYPE="radio" NAME="pref_TIME_FORMAT" VALUE="12" <?php if ( $TIME_FORMAT == "12" ) echo "CHECKED";?>> <?php etranslate("12 hour")?> <INPUT TYPE="radio" NAME="pref_TIME_FORMAT" VALUE="24" <?php if ( $TIME_FORMAT != "12" ) echo "CHECKED";?>> <?php etranslate("24 hour")?></TD></TR>
<TR><TD><B><?php etranslate("Display unapproved")?>:</B></TD>
  <TD><INPUT TYPE="radio" NAME="pref_DISPLAY_UNAPPROVED" VALUE="Y" <?php if ( $DISPLAY_UNAPPROVED != "N" ) echo "CHECKED";?>> <?php etranslate("Yes")?> <INPUT TYPE="radio" NAME="pref_DISPLAY_UNAPPROVED" VALUE="N" <?php if ( $DISPLAY_UNAPPROVED == "N" ) echo "CHECKED";?>> <?php etranslate("No")?></TD></TR>
<!--
<TR><TD><B><?php etranslate("Display icons")?>:</B></TD>
  <TD><INPUT TYPE="radio" NAME="pref_DISPLAY_ICONS" VALUE="Y" <?php if ( $DISPLAY_ICONS != "N" ) echo "CHECKED";?>> <?php etranslate("Yes")?> <INPUT TYPE="radio" NAME="pref_DISPLAY_ICONS" VALUE="N" <?php if ( $DISPLAY_ICONS == "N" ) echo "CHECKED";?>> <?php etranslate("No")?></TD></TR>
-->
<TR><TD><B><?php etranslate("Display week number")?>:</B></TD>
  <TD><INPUT TYPE="radio" NAME="pref_DISPLAY_WEEKNUMBER" VALUE="Y" <?php if ( $DISPLAY_WEEKNUMBER != "N" ) echo "CHECKED";?>> <?php etranslate("Yes")?> <INPUT TYPE="radio" NAME="pref_DISPLAY_WEEKNUMBER" VALUE="N" <?php if ( $DISPLAY_WEEKNUMBER == "N" ) echo "CHECKED";?>> <?php etranslate("No")?></TD></TR>
<TR><TD><B><?php etranslate("Week starts on")?>:</B></TD>
  <TD><INPUT TYPE="radio" NAME="pref_WEEK_START" VALUE="0" <?php if ( $WEEK_START != "1" ) echo "CHECKED";?>> <?php etranslate("Sunday")?> <INPUT TYPE="radio" NAME="pref_WEEK_START" VALUE="1" <?php if ( $WEEK_START == "1" ) echo "CHECKED";?>> <?php etranslate("Monday")?></TD></TR>
<TR><TD><B><?php etranslate("Work hours")?>:</B></TD>
  <TD>
  <?php etranslate("From")?> <SELECT NAME="pref_WORK_DAY_START_HOUR">
  <?php
  for ( $i = 0; $i < 24; $i++ ) {
    echo "<OPTION VALUE=\"$i\" " .
      ( $i == $WORK_DAY_START_HOUR ? "SELECTED " : "" ) .
      "> " . display_time ( $i * 10000 );
  }
  ?>
  </SELECT> <?php etranslate("to")?>
  <SELECT NAME="pref_WORK_DAY_END_HOUR">
  <?php
  for ( $i = 0; $i < 24; $i++ ) {
    echo "<OPTION VALUE=\"$i\" " .
      ( $i == $WORK_DAY_END_HOUR ? "SELECTED " : "" ) .
      "> " . display_time ( $i * 10000 );
  }
  ?>
  </SELECT>
  </TD></TR>

</TABLE></TD></TR></TABLE></TD></TR></TABLE>

<H3><?php etranslate("Email")?></H3>

<TABLE BORDER="0" CELLSPACING="0" CELLPADDING="0"><TR><TD BGCOLOR="#000000"><TABLE BORDER="0" WIDTH="100%" CELLSPACING="1" CELLPADDING="2"><TR><TD WIDTH="100%" BGCOLOR="<?php echo $CELLBG ?>"><TABLE BORDER="0" WIDTH="100%">

<TR><TD VALIGN="top"><B><?php etranslate("Event reminders")?>:</B></TD>
  <TD><INPUT TYPE="radio" NAME="pref_EMAIL_REMINDER" VALUE="Y" <?php if ( $EMAIL_REMINDER != "N" ) echo "CHECKED";?>> <?php etranslate("Yes")?> <INPUT TYPE="radio" NAME="pref_EMAIL_REMINDER" VALUE="N" <?php if ( $EMAIL_REMINDER == "N" ) echo "CHECKED";?>> <?php etranslate("No")?></TD></TR>

<TR><TD VALIGN="top"><B><?php etranslate("Events added to my calendar")?>:</B></TD>
  <TD><INPUT TYPE="radio" NAME="pref_EMAIL_EVENT_ADDED" VALUE="Y" <?php if ( $EMAIL_EVENT_ADDED != "N" ) echo "CHECKED";?>> <?php etranslate("Yes")?> <INPUT TYPE="radio" NAME="pref_EMAIL_EVENT_ADDED" VALUE="N" <?php if ( $EMAIL_EVENT_ADDED == "N" ) echo "CHECKED";?>> <?php etranslate("No")?></TD></TR>

<TR><TD VALIGN="top"><B><?php etranslate("Events updated on my calendar")?>:</B></TD>
  <TD><INPUT TYPE="radio" NAME="pref_EMAIL_EVENT_UPDATED" VALUE="Y" <?php if ( $EMAIL_EVENT_UPDATED != "N" ) echo "CHECKED";?>> <?php etranslate("Yes")?> <INPUT TYPE="radio" NAME="pref_EMAIL_EVENT_UPDATED" VALUE="N" <?php if ( $EMAIL_EVENT_UPDATED == "N" ) echo "CHECKED";?>> <?php etranslate("No")?></TD></TR>

<TR><TD VALIGN="top"><B><?php etranslate("Events removed from my calendar")?>:</B></TD>
  <TD><INPUT TYPE="radio" NAME="pref_EMAIL_EVENT_DELETED" VALUE="Y" <?php if ( $EMAIL_EVENT_DELETED != "N" ) echo "CHECKED";?>> <?php etranslate("Yes")?> <INPUT TYPE="radio" NAME="pref_EMAIL_EVENT_DELETED" VALUE="N" <?php if ( $EMAIL_EVENT_DELETED == "N" ) echo "CHECKED";?>> <?php etranslate("No")?></TD></TR>

<TR><TD VALIGN="top"><B><?php etranslate("Event rejected by participant")?>:</B></TD>
  <TD><INPUT TYPE="radio" NAME="pref_EMAIL_EVENT_REJECTED" VALUE="Y" <?php if ( $EMAIL_EVENT_REJECTED != "N" ) echo "CHECKED";?>> <?php etranslate("Yes")?> <INPUT TYPE="radio" NAME="pref_EMAIL_EVENT_REJECTED" VALUE="N" <?php if ( $EMAIL_EVENT_REJECTED == "N" ) echo "CHECKED";?>> <?php etranslate("No")?></TD></TR>

</TABLE></TD></TR></TABLE></TD></TR></TABLE>

<?php if ( $allow_color_customization ) { ?>

<H3><?php etranslate("Colors")?></H3>

<TABLE BORDER="0" CELLSPACING="0" CELLPADDING="0"><TR><TD BGCOLOR="#000000"><TABLE BORDER="0" WIDTH="100%" CELLSPACING="1" CELLPADDING="2"><TR><TD WIDTH="100%" BGCOLOR="<?php echo $CELLBG ?>"><TABLE BORDER="0" WIDTH="100%">


<TR><TD><B><?php etranslate("Document background")?>:</B></TD>
  <TD><INPUT NAME="pref_BGCOLOR" SIZE=7 MAXLENGTH=7 VALUE="<?php echo $BGCOLOR; ?>"> <INPUT TYPE="button" ONCLICK="selectColor('pref_BGCOLOR')" VALUE="<?php etranslate("Select")?>..."></TD></TR>
<TR><TD><B><?php etranslate("Document title")?>:</B></TD>
  <TD><INPUT NAME="pref_H2COLOR" SIZE=7 MAXLENGTH=7 VALUE="<?php echo $H2COLOR; ?>"> <INPUT TYPE="button" ONCLICK="selectColor('pref_H2COLOR')" VALUE="<?php etranslate("Select")?>..."></TD></TR>
<TR><TD><B><?php etranslate("Table cell background")?>:</B></TD>
  <TD><INPUT NAME="pref_CELLBG" SIZE=7 MAXLENGTH=7 VALUE="<?php echo $CELLBG; ?>"> <INPUT TYPE="button" ONCLICK="selectColor('pref_CELLBG')" VALUE="<?php etranslate("Select")?>..."></TD></TR>
<TR><TD><B><?php etranslate("Table cell background for current day")?>:</B></TD>
  <TD><INPUT NAME="pref_TODAYCELLBG" SIZE=7 MAXLENGTH=7 VALUE="<?php echo $TODAYCELLBG; ?>"> <INPUT TYPE="button" ONCLICK="selectColor('pref_TODAYCELLBG')" VALUE="<?php etranslate("Select")?>..."></TD></TR>

</TABLE></TD></TR></TABLE></TD></TR></TABLE>

<?php } // if $allow_color_customization ?>

<BR><BR>
<TABLE BORDER=0><TR><TD>
<INPUT TYPE="submit" VALUE="<?php etranslate("Save Preferences")?>">
<SCRIPT LANGUAGE="JavaScript">
  document.writeln ( '<INPUT TYPE="button" VALUE="<?php etranslate("Help")?>..." ONCLICK="window.open ( \'help_pref.php\', \'cal_help\', \'dependent,menubar,scrollbars,height=400,width=400,innerHeight=420,outerWidth=420\');">' );
</SCRIPT>
</TD></TR></TABLE>


</FORM>

<?php include "includes/trailer.inc"; ?>
</BODY>
</HTML>
