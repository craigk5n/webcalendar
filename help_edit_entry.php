<?php
include "includes/config.php";
include "includes/php-dbi.php";
include "includes/functions.php";
include "includes/$user_inc";
include "includes/validate.php";
include "includes/connect.php";

load_global_settings ();
load_user_preferences ();
load_user_layers ();

include "includes/translate.php";

?>
<HTML>
<HEAD>
<TITLE><?php etranslate($application_name)?></TITLE>
<?php include "includes/styles.php"; ?>
</HEAD>
<BODY BGCOLOR="<?php echo $BGCOLOR; ?>" CLASS="defaulttext">

<H2><FONT COLOR="<?php echo $H2COLOR;?>"><?php etranslate("Help")?>: <?php etranslate("Adding/Editing Calendar Entries")?></FONT></H2>

<TABLE BORDER=0>
<TR>
<TD VALIGN="top"><B><?php etranslate("Brief Description")?>:</B></TD>
  <TD><?php etranslate("brief-description-help")?></TD></TR>
<TD VALIGN="top"><B><?php etranslate("Full Description")?>:</B></TD>
  <TD><?php etranslate("full-description-help")?></TD></TR>
<TD VALIGN="top"><B><?php etranslate("Date")?>:</B></TD>
  <TD><?php etranslate("date-help")?></TD></TR>
<TD VALIGN="top"><B><?php etranslate("Time")?>:</B></TD>
  <TD><?php etranslate("time-help")?></TD></TR>
<TD VALIGN="top"><B><?php etranslate("Duration")?>:</B></TD>
  <TD><?php etranslate("duration-help")?></TD></TR>

<?php if ( $disable_priority_field != "Y" ) { ?>
<TD VALIGN="top"><B><?php etranslate("Priority")?>:</B></TD>
  <TD><?php etranslate("priority-help")?></TD></TR>
<?php } ?>

<?php if ( $disable_access_field != "Y" ) { ?>
<TD VALIGN="top"><B><?php etranslate("Access")?>:</B></TD>
  <TD><?php etranslate("access-help")?></TD></TR>
<?php } ?>

<?php
$show_participants = ( $disable_participants_field != "Y" );
if ( $is_admin )
  $show_participants = true;
if ( $single_user == "N" && $show_participants ) { ?>
<TD VALIGN="top"><B><?php etranslate("Participants")?>:</B></TD>
  <TD><?php etranslate("participants-help")?></TD></TR>
<?php } ?>


<?php if ( $disable_repeating_field != "Y" ) { ?>
<TD VALIGN="top"><B><?php etranslate("Repeat Type")?>:</B></TD>
  <TD><?php etranslate("repeat-type-help")?></TD></TR>
<TD VALIGN="top"><B><?php etranslate("Repeat End Date")?>:</B></TD>
  <TD><?php etranslate("repeat-end-date-help")?></TD></TR>
<TD VALIGN="top"><B><?php etranslate("Repeat Day")?>:</B></TD>
  <TD><?php etranslate("repeat-day-help")?></TD></TR>
<TD VALIGN="top"><B><?php etranslate("Frequency")?>:</B></TD>
  <TD><?php etranslate("repeat-frequency-help")?></TD></TR>
<?php } ?>

</TABLE>

<?php include "includes/help_trailer.php"; ?>

</BODY>
</HTML>
