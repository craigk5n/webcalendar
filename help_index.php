<?php
	include_once 'includes/init.php';
	print_header('','','',true);
?>

<h2><?php etranslate("Help Index")?></h2>
<ul>
	<li><a title="<?php etranslate("Adding/Editing Calendar Entries")?>" href="help_edit_entry.php"><?php etranslate("Adding/Editing Calendar Entries")?></a></li>
	<li><a title="<?php etranslate("Layers")?>" href="help_layers.php"><?php etranslate("Layers")?></a></li>
	<li><a title="<?php etranslate("Import")?>" href="help_import.php"><?php etranslate("Import")?></a></li>
	<li><a title="<?php etranslate("Preferences")?>" href="help_pref.php"><?php etranslate("Preferences")?></a></li>
	<?php if ( $is_admin ) { ?>
		<li><a title="<?php etranslate("System Settings")?>" href="help_admin.php"><?php etranslate("System Settings")?></a></li>
	<?php } ?>
	<li><a title="<?php etranslate("Report Bug")?>" href="help_bug.php"><?php etranslate("Report Bug")?></a></li>
</ul>

<?php include_once "includes/help_trailer.php"; ?>
</body>
</html>
