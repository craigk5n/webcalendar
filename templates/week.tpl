  {include file="header.tpl"}
  <table width="100%" cellpadding="1">
    <tr>
      <td id="printarea" style="vertical-align:top; width:{$tableWidth};" >
      {include file="navigation.tpl"}
      </td>
      {$filler}
    </tr>
    <tr>
      <td>
        <table class="main">
          <tr>
            <th class="empty">&nbsp;</th>
            {$headerStr}
          </tr>
          {$untimedStr}
          {$eventsStr}
        </table>
      </td>
      {if $p.DISPLAY_TASKS || $p.DISPLAY_SM_MONTH }
        <td id="minicolumn" rowspan="2" valign="top">
          <!-- START MINICAL -->
          <div class="minicontainer">
            {if $p.DISPLAY_SM_MONTH}
             <div class="minicalcontainer">
               {small_month  dateYmd=$WC->thisdate 
		    showyear=true showweeknum=true tid=thismonth}
             </div>
            {/if}
            <div id="minitask"></div>
		  </div>
        </td>
      {/if}
    </tr>
  </table> 
 {include file="footer.tpl"}
