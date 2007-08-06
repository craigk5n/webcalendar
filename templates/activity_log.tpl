{if $activity_log}
 {include file="header.tpl"}
{/if}
<h2>{if $system}{'System Log'|translate}{else}{'Activity Log'|translate}{/if}</h2>
  <table class="embactlog">
    <tr>
      <th class="usr">{'Action'|translate} {'User'}</th>
      <th class="cal">{'Calendar'|translate} {'Owner'|translate}</th>
      <th class="scheduled">{'Date'|translate}/{'Time'|translate}</th>
		{if ! $sys && ! $eid}
      <th class="dsc">{'Event'|translate}</th>
		{/if}
      <th class="action">{'Action'|translate}</th>
    </tr>
  {foreach from=$log_data key=k item=v name=log_loop}
    <tr {if $smarty.foreach.log_loop.index%2==1}class="odd"{/if}>
      <td>{$v.actionFullname}</td>
      <td>{$v.ownerFullname}</td>
      <td>{$v.l_date|date_to_str}&nbsp;{$v.l_date|display_time:$use_GMT}</td>
      <td>
		{if ! $sys && ! $eid}<a title="{$v.l_ename|htmlspecialchars}" href="view_entry.php?eid={$v.l_eid}">{$v.l_ename|htmlspecialchars}</a></td>
			<td>
		{/if}
			  {$v.log_data}
			</td>
    </tr>
   {/foreach}
  </table>
		
			
<div class="navigation">
 {if $prev_URL}
  <a title="{'Previous'|translate}&nbsp;{$PAGE_SIZE}&nbsp;{'Events'|translate}" class="prev" href="{$prev_URL}">{'Previous'|translate}&nbsp;{$PAGE_SIZE}&nbsp;{'Events'|translate}</a>
 {/if}

 {if $next_URL}
  <a title="{'Next'|translate}&nbsp;{$PAGE_SIZE}&nbsp;{'Events'|translate}" class="next" href="{$next_URL}">{'Next'|translate}&nbsp;{$PAGE_SIZE}&nbsp;{'Events'|translate}</a>
 {/if}

</div>
{if $activity_log}
  {include file="footer.tpl"}
{/if}