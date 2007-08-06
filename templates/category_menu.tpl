{if $s.CATEGORIES_ENABLED}
  <form action="{$smarty.server.SCRIPT_NAME}" method="get" name="selectcat" class="categories">
    {if $WC->todayYmd }
      <input type="hidden" name="{if $smarty.server.SCRIPT_NAME != 'year.php'}date{else}year{/if}" value="{$WC->todayYmd|date_format:'Ymd'}" />
	{/if}
    {if $WC->isUser() }
      <input type="hidden" name="user" value="{$user}" />
	{/if}
	{if !$WC->friendly ()}
     {'Category'|translate}
      <select name="cat_id" onchange="document.selectcat.submit ()">
       {foreach from=$categories key=k item=v}
          <option value="{$k}" {if $k == $cat_id}selected="selected"{/if}>{$v.cat_name}</option>
       {/foreach}
     </select>
	{/if}
   </form>
   {if $WC->catId() > 0}
    <span id="cat">{'Category'|translate}:{$categories[0].cat_name}</span>
	{/if}
{/if}