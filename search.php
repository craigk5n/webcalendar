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
<BODY BGCOLOR="<?php echo $BGCOLOR;?>">

<H2><FONT COLOR="<?php echo $H2COLOR;?>"><?php etranslate("Search")?></FONT></H2>

<FORM ACTION="search_handler.php" METHOD="POST">

<B><?php etranslate("Keywords")?>:</B>
<INPUT NAME="keywords" SIZE=30>
<INPUT TYPE="submit" VALUE="<?php etranslate("Search")?>">
</FORM>

<?php include "includes/trailer.inc"; ?>
</BODY>
</HTML>
