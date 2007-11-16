    <table class="main" cellspacing="0" cellpadding="0" id="month_main">
      <tr class="header">
{if $p.DISPLAY_WEEKNUMBER}
        <th class="empty"></th>
{/if}
{section name=thdata loop=$th}
        <th {$th[thdata].class}>{$th[thdata].name}</th>
{/section}
      </tr>
{section name=trdata loop=$tr}
      <tr>
  {if $p.DISPLAY_WEEKNUMBER}
        <td class="weekcell">
		  <a class="weekcell" title="{$tr[trdata].title}" href="{$tr[trdata].href}" >{$tr[trdata].weekStr}</a>
		</td>
  {/if}
  {section name=tddata loop=$td[trdata]}
        <td id="td{$td[trdata][tddata].id}" {$td[trdata][tddata].class}>{$td[trdata][tddata].data}<div id="dv{$td[trdata][tddata].id}" >&nbsp;</div>
		</td>
  {/section}
	 </tr>
{/section}
  </table>
