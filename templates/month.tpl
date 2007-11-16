	  {include file="header.tpl"}
    <table border="0" width="100%" cellpadding="1">
      <tr>
        <td id="printarea" valign="top" width="{if $p.DISPLAY_TASKS}80%{else}100%{/if}" rowspan="2">
        {if  ! $p.DISPLAY_TASKS && $p.DISPLAY_SM_MONTH }
          {small_month  dateYmd=$WC->prevYmd 
		    showyear=true showweeknum=true tid=prevmonth}
          {small_month dateYmd=$WC->nextYmd
		    showyear=true showweeknum=true tid=nextmonth}
        {/if}
          {include file="navigation.tpl"}
          {display_month thismonth=$WC->thismonth thisyear=$WC->thisyear}
        </td>
        <td valign="top" align="center">
        {if $p.DISPLAY_TASKS && ! $WC->friendly() }
          {small_month  dateYmd=$WC->prevYmd 
		    showyear=true showweeknum=false tid=prevmonth}<br />
          {small_month dateYmd=$WC->nextYmd
		    showyear=true showweeknum=false tid=nextmonth}<br />
		 {/if}
		 <div id="minitask"></div>
        </td>
      </tr>
    </table>
    {include file="footer.tpl"}
		