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

if ( ! $is_admin )
  $user = $login;

?>
<HTML>
<HEAD>
<TITLE><?php etranslate($application_name)?></TITLE>
<?php include "includes/styles.php"; ?>
</HEAD>
<BODY BGCOLOR="<?php echo $BGCOLOR;?>" CLASS="defaulttext">

<H2><FONT COLOR="<?php echo $H2COLOR;?>"><?php etranslate("Views")?></FONT></H2>

<UL>
<?php
for ( $i = 0; $i < count ( $views ); $i++ ) {
  echo "<LI><A HREF=\"views_edit.php?id=" . $views[$i]["cal_view_id"] .
    "\">" . $views[$i]["cal_name"] . "</A> ";
}
?>
</UL>
<P>
<?php
  echo "<A HREF=\"views_edit.php\">" . translate("Add New View") .
    "</A><BR>\n";
?>

<?php include "includes/trailer.php"; ?>
</BODY>
</HTML>
