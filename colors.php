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
<SCRIPT LANGUAGE="JavaScript">
function sendColor ( color ) {
  window.opener.document.prefform.<?php echo $color?>.value= color;
  window.close ();
}
</SCRIPT>
<?php include "includes/styles.inc"; ?>
</HEAD>
<BODY BGCOLOR="<?php echo $BGCOLOR;?>">
<CENTER>

<TABLE BORDER=2>
<TR>
<?php
$colors = array (
  "FFFFFF", "C0C0C0", "909090", "404040", "000000",
  "FF0000", "C00000", "A00000", "800000", "200000",
  "FF8080", "C08080", "A08080", "808080", "208080",
  "00FF00", "00C000", "00A000", "008000", "002000",
  "80FF80", "80C080", "80A080", "808080", "802080",
  "0000FF", "0000C0", "0000A0", "000080", "000020",
  "8080FF", "8080C0", "8080A0", "808080", "808020"
);
$i = 0;
for ( $r = 0; $r < 16; $r += 3 ) {
  for ( $g = 0; $g < 16; $g += 3 ) {
    for ( $b = 0; $b < 16; $b += 3 ) {
      if ( $i == 0 )
        echo "<TR>\n";
      else if ( $i % 16 == 0 )
        echo "</TR><TR>\n";
      $c = sprintf ( "%X0%X0%X0", $r, $g, $b );
      echo "<TD BGCOLOR=\"#" . $c .
        "\"><A HREF=\"javascript:sendColor('#" . $c .
        "')\"><IMG SRC=\"spacer.gif\" WIDTH=\"15\" HEIGHT=\"15\" BORDER=\"0\"></A></TD>\n";
      $i++;
    }
  }
}
$c = "FFFFFF";
  echo "<TD BGCOLOR=\"#" . $c .
    "\"><A HREF=\"javascript:sendColor('#" . $c .
    "')\"><IMG SRC=\"spacer.gif\" WIDTH=\"15\" HEIGHT=\"15\" BORDER=\"0\"></A></TD>\n";
echo "</TR>\n";
?>
</TABLE>

</CENTER>

</BODY>
</HTML>
