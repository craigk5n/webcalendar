{include file="header.tpl"}
<h2>{if $nid}
      {'Edit Remote Calendar'|translate}
    {else}
		  {'Add Remote Calendar'|translate}
		{/if}</h2>
  <form action="edit_remotes_handler.php" method="post"  name="prefform" onsubmit="return valid_form(this);">
  <table cellspacing="0" cellpadding="2">
    <tr><td>
      <label for="calid">{'Calendar ID'|translate}:</label></td>
      <td colspan="3">
			{if $nid}
			  {$nid} <input type="hidden" name="nid" id="nid" value="$nid" />
			{else}
			  <input type="text" name="nid" id="nid" size="20" maxlength="20" onchange="check_name();" /> {'word characters only'|translate}
			{/if}</td>
		</tr>
    <tr><td>
      <label for="nfirstname">{'Name'|translate}:</label></td><td colspan="3">
      <input type="text" name="rmt_name" id="rmt_name" size="20" maxlength="25" value="{$rmt_name}" /></td>
		</tr>
    <tr><td>
      <label for="nurl">{'URL'|translate}:</label></td><td colspan="3">
      <input type="text" name="nurl" id="nurl" size="75" maxlength="255" value="{$rmt_url}" /></td>
	 </tr>

{if !$nid }
   <tr><td>
     <label for="nlayer">{'Create Layer'|translate}:</label></td><td colspan="3">
     <input type="hidden" name="reload" id="reload" value="true" />
     <input type="checkbox" name="nlayer" id="nlayer"  value="Y"  onchange="toggle_layercolor();"/>{'Required to View Remote Calendar'|translate}</td></tr>
   <tr id="nlayercolor" style="visibility:hidden" ><td>
    {html_color_input name='layercolor' title='Color'|translate val=#000000}
   </td></tr>
{/if}
  </table>
  <input type="hidden" name="nadmin" id="nadmin" value="{$WC->loginId()}" />
  <input type="submit" name="action" value="{if $add}{'Add'|translate}{else}{'Save'|translate}{/if}" />

{if $nid}
  <input type="submit" name="delete" value="{'Delete'|translate}" onclick="return confirm('{$confirmStr}')" />
{/if}
  <input type="submit" name="reload" value="{'Reload'|translate}" />
</form>
{include file="footer.tpl" include_nav_links=false}

