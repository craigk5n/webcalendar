<?php
	include_once 'includes/init.php';
	print_header('','','',true);
?>

<h2><?php etranslate("Help")?>: <?php etranslate("Import")?></h2>

<h3><?php etranslate("Palm Desktop")?></h3>
	<?php etranslate("This form will allow you to import entries from the Palm Desktop Datebook."); ?><br />
	<?php etranslate("It should be located in your Palm directory in <tt>datebook/datebook.dat</tt> in a subdirectory named by your username.") ?><br /><br />

<?php etranslate("The following entries will not be imported")?>:
	<ul>
		<li><?php etranslate("Entries older than the current date")?></li>
		<li><?php etranslate("Entries created in the Palm Desktop that have not been HotSync'd")?></li>
	</ul>
	<br /><br />

	<?php etranslate("Anything imported from Palm will be overwritten during the next import (unless the event date has passed).") ?>
	<?php etranslate("Therefore, updates should be made in the Palm Desktop.");?>

<h3><?php etranslate("vCal")?></h3>
	<?php etranslate("This form will import vCalendar (.vcs) 1.0 events");?>.<br /><br />

<?php etranslate("The following formats have been tested");?>:
	<ul>
		<li><?php etranslate("Palm Desktop 4"); ?></li>
		<li><?php etranslate("Lotus Organizer 6"); ?></li>
		<li><?php etranslate("Microsoft Outlook 2002"); ?></li>
	</ul>

<h3><?php etranslate("iCalendar")?></h3>
	<p><?php etranslate("This form will import iCalendar (.ics) events");?>.
	<?php etranslate("Enabling <b>Overwrite Prior Import</b> will cause events imported previously that used the same UID as an event from the new import file to be marked as deleted.  This should allow an updated iCalendar file to be imported without creating duplicates."); ?></p>

<?php include_once "includes/help_trailer.php"; ?>
</body>
</html>
