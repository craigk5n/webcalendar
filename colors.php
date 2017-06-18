<?php
/* $Id: colors.php,v 1.33.2.2 2007/08/06 02:28:29 cknudsen Exp $ */
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
print_header ( array ( 'js/colors.php/true' ), '',
  'onload="fillhtml(); setInit();"', true, false, true );

/*
  HTML Color Editor v1.2 (c) 2000 by Sebastian Weber <webersebastian@yahoo.de>
  Modified by Ray Jones for inclusion into WebCalendar.
  NOTE: In-line CSS styles must remain in this file for proper operation
*/

echo <<<EOT
    <form action="colors.php" name="colorpicker">
      <input type="hidden" id="colorcell" value="{$color}" />
      <table cellspacing="2" cellpadding="0" align="center">
        <tr>
          <td colspan="3">
            <img height="1" src="images/blank.gif" border="0" alt="" /></td>
        </tr>
        <tr>
          <td align="center">{$basicStr}</td>
<!-- COLORS PICTURE -->
          <td rowspan="5" width="220" align="center">
            <img id="colorpic" height="192" width="192" src="images/colors.jpg"
              onclick="setFromImage(event);" alt="" /></td>
<!-- ***** SLIDER **** -->
          <td rowspan="5">
            <table cellspacing="0" cellpadding="0" width="24"
              onclick="setFromSlider(event);">
              <tr>
                <td id="slider"></td>
              </tr>
            </table>
          </td>
        </tr>
        <tr>
<!--  BASIC COLORS PALETTE  -->
          <td align="center" id="colorchoices"></td>
        </tr>
        <tr>
          <td align="center">{$customStr}</td>
        </tr>
        <tr>
<!--  Custom Colors  -->
          <td align="center" id="colorcustom"></td>
        </tr>
        <tr>
          <td align="center"><input type="button" value="{$addcustomStr}"
            onclick="definePreColor()" /></td>
        </tr>
        <tr>
          <td valign="top" colspan="3">
            <table cellpadding="2" cellspacing="0" width="100%">
              <tr align="center">
                <td colspan="2" height="30" valign="bottom">{$currentStr}</td>
                <td valign="bottom">{$oldStr}</td>
              </tr>
              <tr>
<!-- RGB INPUT -->
                <td class="boxtop boxleft boxbottom" valign="top" align="right">
                  R: <input id="rgb_r" type="text" size="3" maxlength="3"
                    value="255" onchange="setFromRGB()" /><br />
                  G: <input id="rgb_g" type="text" size="3" maxlength="3"
                    value="255" onchange="setFromRGB()" /><br />
                  B: <input id="rgb_b" type="text" size="3" maxlength="3"
                    value="255" onchange="setFromRGB()" /><br />
                  HTML: <input id="htmlcolor" type="text" size="6" maxlength="6"
                    value="FFFFFF" onchange="setFromHTML()" />
                </td>
                <td class="boxtop boxright boxbottom" width="120">
          <table id="thecell" bgcolor="#ffffff" align="center"
border="1" cellspacing="0" cellpadding="0">
                    <tr>
                      <td><img src="images/blank.gif" width="55" height="53" border="0" alt="" /></td>
                    </tr>
                  </table>
                </td>
                <td valign="middle" align="center" class="boxtop boxright
                  boxbottom">
<!--  Display New Color  -->
        <table id="theoldcell" bgcolor="#ffffff" border="1" cellspacing="0" cellpadding="0">
                    <tr>
            <td><img src="images/blank.gif" width="55" height="53" border="0" alt="" /></td>
                    </tr>
                  </table>
                </td>
              </tr>
            </table>
          </td>
        </tr>
        <tr>
          <td colspan="3" align="center" height="30">
            <input type="button"
              value="&nbsp;&nbsp;&nbsp;{$okStr}&nbsp;&nbsp;&nbsp;"
              onclick="transferColor(); window.close()"
              />&nbsp;&nbsp;&nbsp;<input type="button"
              value="{$cancelStr}" onclick="window.close()" />
          </td>
        </tr>
      </table>
    </form>
<img id="cross" src="images/cross.gif" alt="" style="position:absolute; left:0px; top:0px" />
<img id="sliderarrow" src="images/arrow.gif" alt="" style="position:absolute; left:0px; top:0px" />
  </body>
</html>
EOT;

?>
