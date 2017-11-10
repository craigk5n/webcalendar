<?php // $Id: colors.php,v 1.37 2009/11/22 16:47:44 bbannon Exp $
include_once 'includes/init.php';
$color = getGetValue ( 'color' );
if ( empty ( $color ) )
  exit;

$addcustomStr = translate ( 'Add Custom' );
$basicStr = translate ( 'Basic Colors' );
$cancelStr = translate ( 'Cancel' );
$currentStr = translate ( 'Current Color' );
$customStr = translate ( 'Custom Colors' );
$oldStr = translate ( 'Old Color' );
$okStr = '&nbsp;&nbsp;&nbsp;' . translate ( 'OK' ). '&nbsp;&nbsp;&nbsp;';

print_header( '',
  '<script src="includes/js/colors.js"></script>',
  'onload="fillhtml(); setInit();"', true, false, true );

/*
  HTML Color Editor v1.2 (c) 2000 by Sebastian Weber <webersebastian@yahoo.de>
  Modified by Ray Jones for inclusion into WebCalendar.
  NOTE: In-line CSS styles must remain in this file for proper operation
*/

echo <<<EOT
    <form action="colors.php" name="colorpicker">
      <input id="colorcell" type="hidden" value="{$color}">
      <table cellspacing="2" class="aligncenter">
        <tr>
          <td colspan="3"><img src="images/blank.gif" alt="" style="height:1px;"></td>
        </tr>
        <tr>
          <td class="aligncenter">{$basicStr}</td>
<!-- COLORS PICTURE -->
          <td class="aligncenter" rowspan="5" style="width:220px;"><img src="images/colors.jpg" id="colorpic" alt="" style="height:192px; width:192px;" onclick="setFromImage(event);"></td>
<!-- ***** SLIDER **** -->
          <td rowspan="5">
            <table width="24"
              onclick="setFromSlider(event);">
              <tr>
                <td id="slider"></td>
              </tr>
            </table>
          </td>
        </tr>
        <tr>
<!--  BASIC COLORS PALETTE  -->
          <td class="aligncenter" id="colorchoices"></td>
        </tr>
        <tr>
          <td class="aligncenter">{$customStr}</td>
        </tr>
        <tr>
<!--  Custom Colors  -->
          <td class="aligncenter" id="colorcustom"></td>
        </tr>
        <tr>
          <td class="aligncenter"><input type="button" value="{$addcustomStr}"
            onclick="definePreColor()"></td>
        </tr>
        <tr>
          <td class="aligntop" colspan="3">
            <table cellpadding="2">
              <tr class="aligncenter">
                <td colspan="2" height="30" class="alignbottom">{$currentStr}</td>
                <td class="alignbottom">{$oldStr}</td>
              </tr>
              <tr>
<!-- RGB INPUT -->
                <td class="boxtop boxbottom boxleft aligntop alignright">
                  R: <input id="rgb_r" type="text" size="3" maxlength="3"
                    value="255" onchange="setFromRGB()"><br>
                  G: <input id="rgb_g" type="text" size="3" maxlength="3"
                    value="255" onchange="setFromRGB()"><br>
                  B: <input id="rgb_b" type="text" size="3" maxlength="3"
                    value="255" onchange="setFromRGB()"><br>
                  HTML: <input id="htmlcolor" type="text" size="6" maxlength="6"
                    value="FFFFFF" onchange="setFromHTML()">
                </td>
                <td class="boxtop boxright boxbottom" width="120">
          <table id="thecell" bgcolor="#FFFFFF" class="aligncenter"
            border="1">
                    <tr>
                      <td><img src="images/blank.gif" width="55" height="53"
                        alt=""></td>
                    </tr>
                  </table>
                </td>
                <td class="alignmiddle aligncenter boxtop boxright boxbottom">
<!--  Display New Color  -->
        <table id="theoldcell" bgcolor="#FFFFFF" border="1">
                    <tr>
                      <td><img src="images/blank.gif" alt="" style="width:55px; height:53px;"></td>
                    </tr>
                  </table>
                </td>
              </tr>
            </table>
          </td>
        </tr>
        <tr>
          <td colspan="3" class="aligncenter" height="30">
            <input type="button"
              value="&nbsp;&nbsp;&nbsp;{$okStr}&nbsp;&nbsp;&nbsp;"
              onclick="transferColor(); window.close()">&nbsp;&nbsp;&nbsp;
            <input type="button" value="{$cancelStr}" onclick="window.close()">
          </td>
        </tr>
      </table>
    </form>
    <img src="images/cross.gif" id="cross" alt="" style="position:absolute; left:0; top:0">
    <img src="images/arrow.gif" id="sliderarrow" alt="" style="position:absolute; left:0; top:0">
  </body>
</html>
EOT;

?>
