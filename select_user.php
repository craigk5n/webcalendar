<?php php_track_vars?>
<?php

include "includes/config.inc";
include "includes/php-dbi.inc";
include "includes/functions.inc";
include "includes/user.inc";
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

<H2><FONT COLOR="<?php echo $H2COLOR;?>"><?php etranslate("View Another User's Calendar")?></FONT></H2>

<UL>
<?php
$userlist = user_get_users ();
for ( $i = 0; $i < count ( $userlist ); $i++ ) {
  echo "<LI><A HREF=\"$STARTVIEW.php?user=" . $userlist[$i]['cal_login'] .
    "\">" . $userlist[$i]['cal_fullname'] . "</A>";
}

?>
</UL>
<P>

<?php include "includes/trailer.inc"; ?>
</BODY>
</HTML>
