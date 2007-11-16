{if $s.CATEGORIES_ENABLED}
  <form action="{$smarty.server.SCRIPT_NAME}" method="get" name="selectcat">
    {if $WC->todayYmd }
      <input type="hidden" name="{if $smarty.server.SCRIPT_NAME != 'year.php'}date{else}year{/if}" value="{$WC->todayYmd}" />
	{/if}
    {if $WC->isUser() }
      <input type="hidden" name="user" value="{$user}" />
	{/if}
	{if !$WC->friendly ()}
		<div class="multiselect_container">
     <div>__Category__</div>
     <select name="cat_id" id="cat_id"  >
       {foreach from=$categories key=k item=v}
          <option value="{$k}" {if $k == $cat_id}{#selected#}{/if}>{$v.cat_name}</option>
       {/foreach}
     </select>
		 </div>
	{/if}
   </form>
   {if $WC->catId() > 0}
    <span id="cat">__Category__:{$categories[0].cat_name}</span>
	{/if}
{/if}