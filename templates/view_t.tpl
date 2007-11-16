{include file="header.tpl"}
<div style="width:99%;">
  <a title="__Previous__" class="prev" href="view_t.php?timeb={$timeb}&amp;vid={$vid}&amp;date={$prevdate}">
	   <img src="images/leftarrow.gif" alt="__Previous__" /></a>
  <a title="__Next__" class="next" href="view_t.php?timeb={$timeb}&amp;vid={$vid}&amp;date={$nextdate}">
	   <img src="images/rightarrow.gif" alt="__Next__" /></a>
  <div class="title">
    <span class="date">{$dateStr}</span>
		<br />
    <span class="viewname">{$view_name}</span>
  </div>
</div>
<br /><br />
  
<table class="main">
{foreach from=$wkloop key=k item=v}
  <tr class="{$v.trclass}"><th class="{$v.thclass}">{$v.thdate}</th>\n";
   <td class="timebar"> {$timeBarHeader}
   {print_date_entries_timebar date=$dateYmd user=$WC->loginId())
  </tr>
{/foreach}
</table>
{include file="footer.tpl"}



