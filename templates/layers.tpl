   {include file="header.tpl"}
	 		<br />
	 {print_tabs tabs=$tabs_ar}
<!-- TABS BODY -->
  <div id="tabscontent">
    <h2>{$userStr}</h2>
		
		
{if ! $WC->isUser ()}
  <select onchange="location=this.options[this.selectedIndex].value;">
	  <option selected="selected" disabled="disabled" value="">{$nonUserStr}</option>
  {foreach from=$nulist key=k item=v}
    <option value="layers.php?user={$v.cal_login_id}">{$v.cal_fullname}</option>
	{/foreach}
	</select>
{else}  
  {generate_href_button label=$myLayerStr attrib='onclick="location.href=\'layers.php\'"'}
{/if}
		<br />
		{'Layers are currently'|translate}&nbsp;<strong>

 {if $layers_enabled}
    {'Enabled'|translate}</strong>&nbsp;
	  {generate_href_button label='Disable Layers'|translate attrib="onclick=\"location.href='layers.php?status=off$u_url'\""}
		<br />
    {generate_href_button label='Add layer'|translate attrib="onclick=\"window.frames['layeriframe'].location.href='edit_layer.php?$u_url';show('layeriframe');\""}
	  <br />
    <div>
	    <iframe name="layeriframe" id="layeriframe"></iframe>
		</div>
    {foreach from=$layers key=k item=v name=layers}
      <div class="layers" style="color:{$v.cal_color}">
       <h4>{$layerStr} {$smarty.foreach.layers.index+1} 
			 <input type="button" value="{'Edit'|translate}" onclick="window.frames['layeriframe'].location.href='edit_layer.php?eid={$v.cal_layerid}{$u_url}';show('layeriframe');" />
			 <input type="button" value="{'Delete'|translate}" onclick="location.href='layers.php?delete=1&amp;id={$v.cal_layerid}{$u_url}';return confirm('{$areYouSureStr}');" /></h4>
      <p><label>{'Source'|translate}: </label>{$v.cal_fullname}</p>
      <p><label>{'Color'|translate}: </label>{$v.cal_color}</p>
      <p><label>{'Duplicates'|translate}: </label>{if $v.cal_dups == 'N'}
        {'No'|translate}{else}{'Yes'|translate}{/if}</p>
    </div>
   {/foreach}
 {else}
    {'Disabled'|translate}</strong>&nbsp;
	  {generate_href_button label='Enable layers'|translate attrib="onclick=\"location.href='layers.php?status=on$u_url'\""}
 {/if}
  </div>
  {include file="footer.tpl"}
