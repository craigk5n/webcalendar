<?php
include_once 'includes/init.php';
$INC = array('js/colors.php');
print_header($INC,'','',true);

$colors = array("00", "33", "66", "99", "CC", "FF");
$grayscale = array("FFFFFF","DDDDDD","C0C0C0","969696","808080","646464","4B4B4B","242424","000000");
$green1 = array("FF", "CC", "99");
$green2 = array("66", "33", "00");

?>

<div style="text-align:center;">
<table style="border-collapse: separate;border: none;background-color:#000000;border-spacing: 1px;">
<?php
// First green array 
for ($r=0; $r<count($colors); $r++){     //the red colors loop
  echo "<tr>\n"; 
  for ($g=0; $g<count($green1); $g++){   //the green colors loop
    for ($b=0; $b<count($colors); $b++){ //iterate through the six blue colors
      $c = $colors[$r].$green1[$g].$colors[$b];
      echo "<td style=\"background-color:#" . $c . ";\"><a href=\"javascript:sendColor('#" . $c .
        "')\"><img src=\"spacer.gif\" class=\"color\" alt=\"\" /></a></td>\n";
    } //End of b-blue innermost loop
  } //End of g-green loop
  echo "</tr>\n"; // close row 
} //End of r-red outermost loop

// second green array
for ($r=0; $r<count($colors); $r++){     //the red colors loop
  echo "<tr>\n"; 
  for ($g=0; $g<count($green2); $g++){   //the green colors loop
    for ($b=0; $b<count($colors); $b++){ //iterate through the six blue colors
      $c = $colors[$r].$green2[$g].$colors[$b];
      echo "<td style=\"background-color:#" . $c . ";\"><a href=\"javascript:sendColor('#" . $c .
        "')\"><img src=\"spacer.gif\" class=\"color\" alt=\"\" /></a></td>\n";
    } //End of b-blue innermost loop
  } //End of g-green loop
  echo "</tr>\n"; // close row 
} //End of r-red outermost loop
?>
</table>
<br />
<table style="border-collapse: separate;border: none;background-color:#000000;border-spacing: 1px;"><tr>
<?php
for ($gs=0; $gs<count($grayscale); $gs++){     
  $c = $grayscale[$gs];
  echo "<td style=\"background-color:#" . $c . ";\"><a href=\"javascript:sendColor('#" . $c .
       "')\"><img src=\"spacer.gif\" class=\"color\" alt=\"\" /></a></td>\n";
} 
?>
</tr></table>

</div>

<?php print_trailer ( false, false, true ); ?>
</body>
</html>
