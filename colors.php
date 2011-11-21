<?php // $Id$
include_once 'includes/init.php';
$color = getGetValue ( 'color' );
if ( empty ( $color ) )
  exit;

$addcustomStr= translate( 'Add Custom' );
$basicStr    = translate( 'Basic Colors' );
$cancelStr   = translate( 'Cancel' );
$currentStr  = translate( 'Current Color' );
$customStr   = translate( 'Custom Colors' );
$oldStr      = translate( 'Old Color' );

$choicehtml = $customhtml = $slidehtml = '';

for ( $i = 1; $i < 193; $i++ ) {
  $slidehtml .= '
                    <tr><td id="sc' . $i . '"></td></tr>';
  if ( $i < 17 ) {
    $customhtml .= '
                <td id="precell' . $i
     . '"><img src="images/blank.gif" id="preimg' . $i . '" alt=""></td>';
    if ( $i == 8 ) {
      $customhtml .= '
              </tr>
              <tr>';
    }
    if ( $i < 7 ) {
      $choicehtml .= '
              <tr>';
      for ( $j = 1; $j < 9; $j++ ) {
        $choicehtml .= '
                <td><img src="images/blank.gif" alt=""></a></td>';
      }
      $choicehtml .= '
              </tr>';

    }
  }
}
print_header( '', '', '', true, false, true );

/*
  HTML Color Editor v1.2 (c) 2000 by Sebastian Weber <webersebastian@yahoo.de>
  Modified by Ray Jones for inclusion into WebCalendar.
*/

echo <<<EOT
    <form action="colors.php" name="colorpicker">
      <input type="hidden" id="colorcell" value="{$color}">
      <table cellspacing="2" summary="">
        <tr><td colspan="3"><img src="images/blank.gif" alt=""></td></tr>
        <tr>
          <td align="center">{$basicStr}</td>
<!-- COLORS PICTURE -->
          <td rowspan="5"><img src="images/colors.jpg" id="colorpic" alt=""></td>
<!-- ***** SLIDER **** -->
          <td rowspan="5">
            <table id="setSlide" summary="slide area">
              <tr>
                <td id="slider">
                  <table summary="slide pointer">{$slidehtml}
                  </table>
                </td>
              </tr>
            </table>
          </td>
        </tr>
        <tr>
<!--  BASIC COLORS PALETTE  -->
          <td id="colorchoices">
            <table summary="choose a color">{$choicehtml}
            </table>
          </td>
        </tr>
        <tr>
          <td align="center">{$customStr}</td>
        </tr>
        <tr>
<!--  Custom Colors  -->
          <td id="colorcustom">
            <table summary="custom colors">
              <tr>{$customhtml}
              </tr>
            </table>
          </td>
        </tr>
        <tr>
          <td><input type="button" value="{$addcustomStr}"
//            onclick="definePreColor()"
></td>
        </tr>
        <tr>
          <td colspan="3" valign="top">
            <table cellpadding="2" summary="">
              <tr align="center">
                <td colspan="2" height="30" valign="bottom">{$currentStr}</td>
                <td valign="bottom">{$oldStr}</td>
              </tr>
              <tr>
<!-- RGB INPUT -->
                <td class="boxtop boxbottom boxleft" align="right" valign="top">
                  R: <input type="text" id="rgb_r" size="3" maxlength="3" value="255"><br>
                  G: <input type="text" id="rgb_g" size="3" maxlength="3" value="255"><br>
                  B: <input type="text" id="rgb_b" size="3" maxlength="3" value="255"><br>
                  HTML: <input type="text" id="htmlcolor" size="6" maxlength="6" value="FFFFFF">
                </td>
                <td id="thecell" class="boxtop boxright boxbottom">
                  <img src="images/blank.gif" alt=""></td>
<!--  Display New Color  -->
                <td id="theoldcell" class="boxtop boxright boxbottom">
                  <img src="images/blank.gif" alt=""></td>
              </tr>
            </table>
          </td>
        </tr>
        <tr>
          <td colspan="3" align="center" height="30">
            <input type="button" value="{$okStr}"
//              onclick="transferColor(); window.close()"
><input type="button" value="{$cancelStr}"
//              onclick="window.close()"
>
          </td>
        </tr>
      </table>
    </form>
    <img src="images/cross.gif" id="cross" alt="">
    <img src="images/arrow.gif" id="sliderarrow" alt="">
  </body>
</html>
EOT;

?>
