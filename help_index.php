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

<?php include "includes/help_trailer.php"; ?>

</BODY>
</HTML>
