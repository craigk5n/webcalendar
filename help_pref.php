<?php php_track_vars?>
<?php
include "includes/config.inc";
include "includes/php-dbi.inc";
include "includes/functions.inc";
include "includes/validate.inc";
include "includes/connect.inc";

load_user_preferences ();
load_user_layers ();

include "includes/translate.inc";

?>
<HTML>
<HEAD>
<TITLE><?php etranslate("Title")?></TITLE>
<?php include "includes/styles.inc"; ?>
</HEAD>
<BODY BGCOLOR="<?php echo $BGCOLOR; ?>">

<H2><FONT COLOR="<?php echo $H2COLOR;?>"><?php etranslate("Help")?>: <?php etranslate("Preferences")?></FONT></H2>

<TABLE BORDER=0>

<TR><TD COLSPAN=2><H2><?php etranslate("Settings")?></H2></TD></TR>
<TR><TD VALIGN="top"><B><?php etranslate("Language")?>:</B></TD>
  <TD><?php etranslate("language-help")?></TD></TR>
<TR><TD VALIGN="top"><B><?php etranslate("Preferred view")?>:</B></TD>
  <TD><?php etranslate("preferred-view-help")?></TD></TR>
<TR><TD VALIGN="top"><B><?php etranslate("Time format")?>:</B></TD>
  <TD><?php etranslate("time-format-help")?></TD></TR>
<TR><TD VALIGN="top"><B><?php etranslate("Display unapproved")?>:</B></TD>
  <TD><?php etranslate("display-unapproved-help")?></TD></TR>
<!--
<TR><TD VALIGN="top"><B><?php etranslate("Display icons")?>:</B></TD>
  <TD><?php etranslate("display-icons-help")?></TD></TR>
-->
<TR><TD VALIGN="top"><B><?php etranslate("Display week number")?>:</B></TD>
  <TD><?php etranslate("display-week-number-help")?></TD></TR>
<TR><TD VALIGN="top"><B><?php etranslate("Week starts on")?>:</B></TD>
  <TD><?php etranslate("display-week-starts-on")?></TD></TR>
<TR><TD VALIGN="top"><B><?php etranslate("Work hours")?>:</B></TD>
  <TD><?php etranslate("work-hours-help")?>
      </TD></TR>

</TABLE>
<P>

<?php if ( $allow_color_customization ) { ?>
<TABLE BORDER=0>
<TR><TD COLSPAN=2><H2><?php etranslate("Colors")?></H2>
<?php etranslate("colors-help")?>
</TD></TR>
</TABLE>
<?php } // if $allow_color_customization ?>

<?php include "includes/help_trailer.inc"; ?>

</BODY>
</HTML>
