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
$sql = "SELECT cal_login, cal_lastname, cal_firstname, cal_is_admin " .
  "FROM webcal_user ORDER BY cal_lastname, cal_firstname, cal_login";
$res = dbi_query ( $sql );
if ( $res ) {
  while ( $row = dbi_fetch_row ( $res ) ) {
    echo "<LI><A HREF=\"edit_user.php?user=$row[0]\">";
    if ( strlen ( $row[1] ) ) {
      echo "$row[1]";
      if ( strlen ( $row[2] ) )
        echo ", $row[2]";
      echo " ($row[0])";
    } else {
      echo "$row[0]";
    }
    echo "</A>";
    if ( $row[3] == 'Y' )
      echo "<SUP>*</SUP>";
  }
  dbi_free_result ( $res );
}

?>
</UL>
<SUP>*</SUP> <?php etranslate("denotes administrative user")?>
<P>
<A HREF="edit_user.php"><?php etranslate("Add New User")?></A><BR>

<?php include "includes/trailer.inc"; ?>
</BODY>
</HTML>
