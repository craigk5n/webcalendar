<?php
include_once 'includes/init.php';
print_header();
?>

<H2><FONT COLOR="<?php echo $H2COLOR;?>"><?php etranslate("Help")?>: <?php etranslate("Import")?></FONT></H2>

<H3><?php etranslate("Palm Desktop")?></H3>

<?php etranslate("This form will allow you to import entries from the Palm Desktop Datebook."); ?>
<br>
<?php etranslate("It should be located in your Palm directory in <tt>datebook/datebook.dat</tt> in a subdirectory named by your username.") ?>
<p>
<?php etranslate("The following entries will not be imported")?>:
<ul>
<li><?php etranslate("Entries older than the current date")?></li>
<li><?php etranslate("Entries creted in the Palm Desktop that have not been HotSync'd")?></li>
</ul>
<p>
<?php etranslate("Anything imported from Palm will be overwritten during the next import (unless the event date has passed).") ?>
<?php etranslate("Therefore, updates should be made in the Palm Desktop.");?>

<H3><?php etranslate("vCal")?></H3>

<?php etranslate("This form will import vCalendar (.cvs) 1.0 events");?>.
<p>
<?php etranslate("The following formats have been tested");?>:
<ul>
<li>Palm Desktop 4
<li>Lotus Organizer 6
<li>Microsoft Outlook 2002
</ul>

<?php include_once "includes/help_trailer.php"; ?>


</BODY>
</HTML>
