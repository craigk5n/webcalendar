<?php
include_once 'includes/init.php';
$INC = array('js/colors.php');
print_header($INC,'','',true);
?>

<div style="text-align:center;">

<table style="border-width:2px;">
<tr>
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
        echo "<tr>\n";
      else if ( $i % 16 == 0 )
        echo "</tr><tr>\n";
      $c = sprintf ( "%X0%X0%X0", $r, $g, $b );
      echo "<td style=\"background-color:#" . $c . ";\"><a href=\"javascript:sendColor('#" . $c .
        "')\"><img src=\"spacer.gif\" style=\"border-width:0px;\" width=\"15\" height=\"15\" /></a></td>\n";
      $i++;
    }
  }
}
$c = "FFFFFF";
  echo "<td style=\"background-color:#" . $c . ";\"><a href=\"javascript:sendColor('#" . $c .
    "')\"><img src=\"spacer.gif\" style=\"border-width:0px; width:15px; height:15px;\" /></a></td>\n";
echo "</tr>\n";
?>
</table>
</div>

<?php print_trailer ( false, false, true ); ?>
</body>
</html>
