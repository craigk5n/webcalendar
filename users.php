<?php

include "includes/config.inc";
include "includes/php-dbi.inc";
include "includes/functions.inc";
include "includes/$user_inc";
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
<?php

if ( ! $is_admin ) {
  echo "<H2><FONT COLOR=\"$H2COLOR\">" . translate("Error") .
    "</FONT></H2>" . translate("You are not authorized") . ".\n";
  include "includes/trailer.inc";
  echo "</BODY></HTML>\n";
  exit;
}
?>


<H2><FONT COLOR="<?php echo $H2COLOR;?>"><?php etranslate("Users")?></FONT></H2>

<UL>
<?php
$userlist = user_get_users ();
for ( $i = 0; $i < count ( $userlist ); $i++ ) {
  echo "<LI><A HREF=\"edit_user.php?user=" . $userlist[$i]["cal_login"] .
    "\">";
  if ( strlen ( $userlist[$i]["cal_firstname"] ) &&
    strlen ( $userlist[$i]["cal_lastname"] ) )
    echo $userlist[$i]["cal_firstname"] . " " .
      $userlist[$i]["cal_lastname"];
  else
    echo $userlist[$i]["cal_login"];
  echo "</A>";
  if (  $userlist[$i]["cal_is_admin"] == 'Y' )
    echo "<SUP>*</SUP>";
}
?>
</UL>
<SUP>*</SUP> <?php etranslate("denotes administrative user")?>
<P>
<?php
  if ( $admin_can_add_user )
    echo "<A HREF=\"edit_user.php\">" . translate("Add New User") .
      "</A><BR>\n";
?>

<?php include "includes/trailer.inc"; ?>
</BODY>
</HTML>
