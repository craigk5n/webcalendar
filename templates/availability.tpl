{include file="header.tpl"}
<div style="width:99%;">
  <a title="{$Previous}" class="prev" href="?users={$partStr}&amp;date={$prevDate}">
    <img src="images/leftarrow.gif" class="prevnext" alt="{$Previous}" /></a>
  <a title="{$Next}" class="next" href="?users={$partStr}&amp;date={$nextDate}">
    <img src="images/rightarrow.gif" class="prevnext" alt="{$Next}" /></a>
  <div class="title">
    <span class="date">{$WC->thisdate|date_to_str}</span><br />
  </div>
</div><br />
   {daily_matrix date=$WC->thisdate participants=$partArray}
{include file="footer.tpl" include_nav_links=false}