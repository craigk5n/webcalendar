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
<BODY BGCOLOR="<?php echo $BGCOLOR;?>" CLASS="defaulttext">
<?php

if ( ! $is_admin ) {
  echo "<H2><FONT COLOR=\"$H2COLOR\">" . translate("Error") .
    "</FONT></H2>" . translate("You are not authorized") . ".\n";
  include "includes/trailer.php";
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
  echo $userlist[$i]['cal_fullname'];
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

<?php include "includes/trailer.php"; ?>
</BODY>
</HTML>
