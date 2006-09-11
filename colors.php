<?php
/* $Id$ */
include_once 'includes/init.php';
$color = getGetValue ( 'color' );
$INC = array("js/colors.php/false/$color");
print_header($INC,'','',true);

$colors = array('00', '33', '66', '99', 'CC', 'FF');
$grayscale = array('FFFFFF','DDDDDD','C0C0C0','969696','808080','646464','4B4B4B','242424','000000');
$green1 = array('FF', 'CC', '99');
$green2 = array('66', '33', '00');
$colorcnt = count($colors);
$green1cnt = count($green1);
$green2cnt = count($green2);
$graycnt = count($grayscale);
$rgb = '000000';
?>

<div class="aligncenter">
<table style="border-collapse: separate;border: none;background-color:#000000;border-spacing: 1px;">
<?php
// First green array 
for ($r=0; $r < $colorcnt; $r++){     //the red colors loop
  echo "<tr>\n"; 
  for ($g=0; $g < $green1cnt; $g++){   //the green colors loop
    for ($b=0; $b < $colorcnt; $b++){ //iterate through the six blue colors
      $rgb = $colors[$r].$green1[$g].$colors[$b];
      echo '<td style="background-color:#' . $rgb . 
        ';"><a href="javascript:sendColor(\'#' . $rgb .
        '\')"><img src="images/spacer.gif" class="color" alt="" /></a></td>' . "\n";
    } //End of b-blue innermost loop
  } //End of g-green loop
  echo "</tr>\n"; // close row 
} //End of r-red outermost loop

// second green array
for ($r=0; $r < $colorcnt; $r++){     //the red colors loop
  echo "<tr>\n"; 
  for ($g=0; $g < $green2cnt; $g++){   //the green colors loop
    for ($b=0; $b < $colorcnt; $b++){ //iterate through the six blue colors
      $rgb = $colors[$r].$green2[$g].$colors[$b];
      echo '<td style="background-color:#' . $rgb . 
        ';"><a href="javascript:sendColor(\'#' . $rgb .
        '\')"><img src="images/spacer.gif" class="color" alt="" /></a></td>' . "\n";
    } //End of b-blue innermost loop
  } //End of g-green loop
  echo "</tr>\n"; // close row 
} //End of r-red outermost loop

?>
</table>
<br />
<table style="border-collapse: separate;border: none;background-color:#000000;border-spacing: 1px;"><tr>
<?php
for ($gs=0; $gs < $graycnt; $gs++){     
  $rgb = $grayscale[$gs];
      echo '<td style="background-color:#' . $rgb . 
        ';"><a href="javascript:sendColor(\'#' . $rgb .
        '\')"><img src="images/spacer.gif" class="color" alt="" /></a></td>' . "\n";
}
 
?>
</tr></table>

</div>

<?php echo print_trailer ( false, false, true ); ?>

