<?php
include_once 'includes/init.php';
print_header('','','',true);
?>

<h2><?php etranslate("Help")?>: <?php etranslate("Layers")?></h2>

<table style="border-width:0px;">
	<tr><td colspan="2">
		<?php etranslate("Layers are useful for displaying other users' events in your own calendar.  You can specifiy the user and the color the events will be displayed in.")?>
	</td></tr>
	<tr><td colspan="2">&nbsp;</td></tr>
	<tr><td class="help">
		<?php etranslate("Add/Edit/Delete")?>:</td><td>
		<?php etranslate("Clicking the Edit Layers link in the admin section at the bottom of the page will allow you to add/edit/delete layers.")?>
	</td></tr>
	<tr><td class="help">
		<?php etranslate("Source")?>:</td><td>
		<?php etranslate("Specifies the user that you would like to see displayed in your calendar.")?>
	</td></tr>
	<tr><td class="help">
		<?php etranslate("Colors")?>:</td><td>
		<?php etranslate("The text color of the new layer that will be displayed in your calendar.")?>
	</td></tr>
	<tr><td class="help">
		<?php etranslate("Duplicates")?>:</td><td>
		<?php etranslate("If checked, events that are duplicates of your events will be shown.")?>
	</td></tr>
	<tr><td class="help">
		<?php etranslate("Disabling")?>:</td><td>
		<?php etranslate("Press the Disable Layers link in the admin section at the bottom of the page to turn off layers.")?>
	</td></tr>
	<tr><td class="help">
		<?php etranslate("Enabling")?>:</td><td>
		<?php etranslate("Press the Enable Layers link in the admin section at the bottom of the page to turn on layers.")?>
	</td></tr>
</table>
<br /><br />

<?php if ( $allow_color_customization ) { ?>
	<h3><?php etranslate("Colors")?></h3>
	<?php etranslate("colors-help")?>
	<br /><br />
<?php } // if $allow_color_customization ?>

<?php include_once "includes/help_trailer.php"; ?>
</body>
</html>
