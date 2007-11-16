{include file="header.tpl"}
 <div class="viewnav">
     {if ! $WC->friendly() }
      <a title="{$Previous}" class="prev"
        href="view_d.php?vid={$vid}&amp;date={$prevYmd}">
        <img src="images/leftarrow.gif" class="prevnext"
          alt="{$Previous}" /></a>
      <a title="{$Next}" class="next"
        href="view_d.php?vid={$vid}&amp;date={$nextYmd}">
        <img src="images/rightarrow.gif" class="prevnext"
          alt="{$Next}" /></a>
			{/if}
      <div class="title">
        <span class="date">{$WC->thisdate|date_to_str}</span><br />
        <span class="viewname">{$view_name}</span>
      </div>
    </div>
    {daily_matrix date=$WC->thisdate participants=$partArray}

    <!-- Hidden form for booking events -->
    <form action="edit_entry.php" method="post" name="schedule">
      <input type="hidden" name="date" value="{$thisyear}{$thismonth}{$thisday}" />
      <input type="hidden" name="defusers" value="{$partStr}" />
      <input type="hidden" name="cal_time" value="" />
    </form>
{include file="footer.tpl"}
