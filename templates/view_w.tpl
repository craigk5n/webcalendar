{include file="header.tpl"}
<div style="width:99%;">
  <a title="{$Previous}" class="prev" href="view_w.php?vid={$vid}&amp;date={$prevdate}">
  <img src="images/leftarrow.gif" alt="{$Previous}" /></a>
  <a title="{$Next}" class="next" href="view_w.php?vid={$vid}&amp;date={$nextDate}">
  <img src="images/rightarrow.gif" alt="{$Next}" /></a>
  <div class="title">
    <span class="date">{$dateStr}</span><br />
    <span class="viewname">{$view_name}</span>
  </div>
</div><br />

{section loop=$viewusercnt start=0 step=6 name=tableloop}
{assign var=loop1 value=$smarty.section.tableloop.index}
{assign var=userloop value=$loop1+$users_per_table}
<table class="main" cellspacing="0" cellpadding="1">
  <tr>
    <th class="empty">&nbsp;</th>
  {section name=thloop loop=$viewuser start=$loop1 max=$userloop}
  {assign var=loop2 value=$smarty.section.thloop.index}	
    <th style="width:{$viewwidth.$loop1}%;">{$viewuser.$loop2.fullname}</th>
  {/section}
  </tr>

  {section loop=$viewdata.$loop1 name=dayloop}
	{assign var=loop3 value=$smarty.section.dayloop.index}
  <tr>
	  <th class="{$viewdata.$loop1.$loop3.class}">{$viewdata.$loop1.$loop3.weekday} {$viewdata.$loop1.$loop3.dated}</th>
  {section name=tdloop loop=$viewuser start=$loop1 max=$userloop}
	{assign var=loop4 value=$smarty.section.tdloop.index}
      <td id="td{$viewdata.$loop1.$loop3.dateYmd}{$viewuser.$loop4.login_id}" class="{$viewdata.$loop1.$loop3.class}" {if $smarty.section.dayloop.first}style="width:{$viewwidth.$loop1}%;"{/if}>&nbsp;</td>
   {/section}
  </tr>
  {/section}
</table>
<br />
<br />
{/section}
{include file="footer.tpl"}

