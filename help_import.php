<?php
include_once 'includes/init.php';
print_header('','','',true);
?>

<h2><font COLOR="<?php echo $H2COLOR;?>"><?php etranslate("Help")?>: <?php etranslate("Import")?></font></h2>

<h3><?php etranslate("Palm Desktop")?></h3>

<?php etranslate("This form will allow you to import entries from the Palm Desktop Datebook."); ?>
<br>
<?php etranslate("It should be located in your Palm directory in <tt>datebook/datebook.dat</tt> in a subdirectory named by your username.") ?>
<p>
<?php etranslate("The following entries will not be imported")?>:
<ul>
<li><?php etranslate("Entries older than the current date")?></li>
<li><?php etranslate("Entries created in the Palm Desktop that have not been HotSync'd")?></li>
</ul>
<p>
<?php etranslate("Anything imported from Palm will be overwritten during the next import (unless the event date has passed).") ?>
<?php etranslate("Therefore, updates should be made in the Palm Desktop.");?>

<h3><?php etranslate("vCal")?></h3>

<?php etranslate("This form will import vCalendar (.vcs) 1.0 events");?>.
<p>
<?php etranslate("The following formats have been tested");?>:
<ul>
<li><?php etranslate("Palm Desktop 4"); ?>
<li><?php etranslate("Lotus Organizer 6"); ?>
<li><?php etranslate("Microsoft Outlook 2002"); ?>
</ul>

<?php include_once "includes/help_trailer.php"; ?>


</BODY>
</HTML>
