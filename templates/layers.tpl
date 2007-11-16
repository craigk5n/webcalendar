   {include file="header.tpl"}
	 		<br />
	 {print_tabs tabs=$tabs_ar}
<!-- TABS BODY -->
  <div id="tabscontent">
    <h2>{$userStr}</h2>
		
		
{if ! $WC->isUser ()}
  <select onchange="location=this.options[this.selectedIndex].value;">
	  <option {#selected#} {#disabled#} value="">__Modify Non User Calendar Layer__</option>
  {foreach from=$nulist key=k item=v}
    <option value="layers.php?user={$v.cal_login_id}">{$v.cal_fullname}</option>
	{/foreach}
	</select>
{else}  
  {generate_href_button label='__Return to My Layer__' attrib='onclick="location.href=\'layers.php\'"'}
{/if}
		<br />
		__Layers are currently__&nbsp;<strong>
 
 {if $layers_enabled}
    __Enabled__</strong>&nbsp;<br />
	  {generate_href_button label='__Disable Layers__'   attrib="onclick=\"location.href='layers.php?status=off$u_url'\""}
		<br />
    {generate_href_button label='__Add layer__' attrib="onclick=\"window.frames['layeriframe'].location.href='edit_layer.php?$u_url';show('layeriframe');\""}
	  <br />
    <div>
	    <iframe name="layeriframe" id="layeriframe"></iframe>
		</div>
    {foreach from=$layers key=k item=v name=layers}
      <div class="layers" style="color:{$v.cal_color}">
       <h4>__Layer__ {$smarty.foreach.layers.index+1} 
			 <input type="button" value="__Edit__" onclick="window.frames['layeriframe'].location.href='edit_layer.php?lid={$v.cal_layerid}';show('layeriframe');" />
			 <input type="button" value="__Delete__" onclick="location.href='layers.php?delete=1&amp;lid={$v.cal_layerid}{$u_url}';return confirm('__ruSureLayer@D__');" /></h4>
      <p><label>__Source__: </label>{$v.cal_fullname}</p>
      <p><label>__Color__: </label>{$v.cal_color}</p>
      <p><label>__Duplicates__: </label>{if $v.cal_dups == 'N'}
        __No__{else}__Yes__{/if}</p>
    </div>
   {/foreach}
 {else}
    __Disabled__</strong>&nbsp;<br />
	  {generate_href_button label='__Enable layers__' attrib="onclick=\"location.href='layers.php?status=on$u_url'\""}
 {/if}
  </div>
  {include file="footer.tpl"}
