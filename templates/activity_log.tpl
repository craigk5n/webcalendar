{if $activity_log}
 {include file="header.tpl"}
{/if}
<h2>{if $system}__System Log__{else}__Activity Log__{/if}</h2>
  <table class="embactlog">
    <tr>
      <th class="usr">__Action__ __User__</th>
      <th class="cal">__Calendar__ __Owner__</th>
      <th class="scheduled">__Date__/__Time__</th>
		{if ! $sys && ! $eid}
      <th class="dsc">__Event__</th>
		{/if}
      <th class="action">__Action__</th>
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
  <a title="{$Previous}&nbsp;{$PAGE_SIZE}&nbsp;__Events__" class="prev" href="{$prev_URL}">{$Previous}&nbsp;{$PAGE_SIZE}&nbsp;__Events__</a>
 {/if}

 {if {$next_URL}
  <a title="{$Next}&nbsp;{$PAGE_SIZE}&nbsp;__Events__" class="next" href="{$next_URL}">{$Next}&nbsp;{$PAGE_SIZE}&nbsp;__Events__</a>
 {/if}

</div>
{if $activity_log}
  {include file="footer.tpl"}
{/if}