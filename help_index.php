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

<H2><FONT COLOR="<?php echo $H2COLOR;?>"><?php etranslate("Help Index")?></FONT></H2>

<UL>
<LI><A HREF="help_edit_entry.php"><?php etranslate("Adding/Editing Calendar Entries")?></A>
<LI><A HREF="help_pref.php"><?php etranslate("Preferences")?></A>
</UL>

<?php include "includes/help_trailer.inc"; ?>

</BODY>
</HTML>
