{include file="header.tpl"}
<h2>{if $nid}
      __Edit Remote Calendar__
    {else}
		  __Add Remote Calendar__
		{/if}</h2>
  <form action="edit_remotes_handler.php" method="post"  name="prefform" onsubmit="return valid_form(this);">
  <table cellspacing="0" cellpadding="2">
    <tr><td>
      <label for="calid">__Calendar ID__:</label></td>
      <td colspan="3">
			{if $nid}
			  {$rmt_login} 
				<input type="hidden" name="nid" id="nid" value="{$nid}" />
			{else}
			  <input type="text" name="nid" id="nid" size="20" maxlength="20" onchange="check_name();" /> __word characters only__
			{/if}</td>
		</tr>
    <tr><td>
      <label for="nfirstname">__Name__:</label></td><td colspan="3">
      <input type="text" name="rmt_name" id="rmt_name" size="20" maxlength="25" value="{$rmt_name}" /></td>
		</tr>
    <tr><td>
      <label for="nurl">__URL__:</label></td><td colspan="3">
      <input type="text" name="nurl" id="nurl" size="75" maxlength="255" value="{$rmt_url}" /></td>
	 </tr>

{if !$nid }
   <tr><td>
     <label for="nlayer">__Create Layer__:</label></td><td colspan="3">
     <input type="hidden" name="reload" id="reload" value="true" />
     <input type="checkbox" name="nlayer" id="nlayer"  value="Y"  onchange="toggle_layercolor();"/>__Required to View Remote Calendar__</td></tr>
   <tr id="nlayercolor" style="visibility:hidden" ><td>
    {html_color_input name='layercolor' title=__Color__ val=#000000}
   </td></tr>
{/if}
  </table>
  <input type="hidden" name="nadmin" id="nadmin" value="{$WC->loginId()}" />
  <input type="submit" name="action" value="{if $add}__Add__{else}__Save__{/if}" />

{if $nid}
  <input type="submit" name="delete" value="__Delete__" onclick="return confirm('__ruSureEntry@D__')" />
{/if}
  <input type="submit" name="reload" value="__Reload__" />
</form>
{include file="footer.tpl" include_nav_links=false}

