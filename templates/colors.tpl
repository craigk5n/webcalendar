   {include file="header.tpl"}
    <form action="colors.php" name="colorpicker">
      <input type="hidden" id="colorcell" value="{$color}" />
      <table cellspacing="2" cellpadding="0" align="center">
        <tr>
          <td colspan="3">
            <img height="1" src="images/blank.gif" border="0" alt="" /></td>
        </tr>
        <tr>
          <td align="center">{'Basic Colors'|translate}</td>
<!-- COLORS PICTURE -->
          <td rowspan="5" width="220" align="center">
            <img id="colorpic" height="192" width="192" src="images/colors.jpg"
              onclick="setFromImage(event);" alt="" /></td>
<!-- ***** SLIDER **** -->
          <td rowspan="5">
            <table id="sliderholder" cellspacing="0" cellpadding="0" width="24px" onclick="setFromSlider(event);">
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
          <td align="center">{'Custom Colors'|translate}</td>
        </tr>
        <tr>
<!--  Custom Colors  -->
          <td align="center" id="colorcustom"></td>
        </tr>
        <tr>
          <td align="center">
					  <input type="button" value="{'Add Custom'|translate}"
            onclick="definePreColor()" /></td>
        </tr>
        <tr>
          <td valign="top" colspan="3">
            <table cellpadding="2" cellspacing="0" width="100%">
              <tr align="center">
                <td colspan="2" height="30" valign="bottom">{'Current Color'|translate}</td>
                <td valign="bottom">{'Old Color'|translate}</td>
              </tr>
              <tr>
<!-- RGB INPUT -->
                <td class="boxtop boxleft boxbottom" valign="top" align="right">
                  R: <input id="rgb_r" type="text" size="3" maxlength="3"
                    value="255" onchange="setFromRGB ()" /><br />
                  G: <input id="rgb_g" type="text" size="3" maxlength="3"
                    value="255" onchange="setFromRGB ()" /><br />
                  B: <input id="rgb_b" type="text" size="3" maxlength="3"
                    value="255" onchange="setFromRGB ()" /><br />
                  HTML: <input id="htmlcolor" type="text" size="6" maxlength="6"
                    value="FFFFFF" onchange="setFromHTML ()" />
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
        <table  id="theoldcell" bgcolor="#ffffff" border="1" cellspacing="0" cellpadding="0">
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
              value="&nbsp;&nbsp;&nbsp;{'OK'|translate}&nbsp;&nbsp;&nbsp;"
              onclick="transferColor(); window.close()"
              />&nbsp;&nbsp;&nbsp;<input type="button"
              value="{'Cancel'|translate}" onclick="window.close()" />
          </td>
        </tr>
      </table>
    </form>
<img id="cross" src="images/cross.gif" alt="" style="position:absolute; left:0px; top:0px" />
<img id="sliderarrow" src="images/arrow.gif" alt="" style="position:absolute; left:0px; top:0px" />

    {include file="footer.tpl" include_nav_links=false}
