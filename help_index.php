<?php
include_once 'includes/init.php';
print_header();
?>

<H2><FONT COLOR="<?php echo $H2COLOR;?>"><?php etranslate("Help Index")?></FONT></H2>

<UL>
<LI><A HREF="help_edit_entry.php"><?php etranslate("Adding/Editing Calendar Entries")?></A>
<LI><A HREF="help_layers.php"><?php etranslate("Layers")?></A>
<LI><A HREF="help_pref.php"><?php etranslate("Preferences")?></A>
<?php if ( $is_admin ) { ?>
<LI><A HREF="help_admin.php"><?php etranslate("System Settings")?></A>
<?php } ?>
<LI><A HREF="help_bug.php"><?php etranslate("Report Bug")?></A>
</UL>

<?php include_once "includes/help_trailer.php"; ?>

</BODY>
</HTML>
