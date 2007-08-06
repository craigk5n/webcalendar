    <table class="main" cellspacing="0" cellpadding="0" id="month_main">
      <tr class="header">
{if $display_weeknumber}
        <th class="empty"></th>
{/if}
{section name=thdata loop=$th}
        <th {$th[thdata].class}>{$th[thdata].name}</th>
{/section}
      </tr>
{section name=trdata loop=$tr}
      <tr>
{if $display_weeknumber}
        <td class="weekcell">
		  <a class="weekcell" title="{$tr[trdata].title}" href="{$tr[trdata].href}" >{$tr[trdata].weekStr}</a>
		</td>
{/if}
{section name=tddata loop=$td[trdata]}
        <td {$td[trdata][tddata].class}>{$td[trdata][tddata].data}<div id="{$td[trdata][tddata].id}">&nbsp;</div>
		</td>
{/section}
{/section}
      </tr>
    </table>
