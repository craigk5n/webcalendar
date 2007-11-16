{include file="header.tpl"}
<div style="width:99%;">
   <a title="{$Previous}" class="prev" href"view_m.php?vid={$vid}&amp;date={$prevdate}">
    <img src="images/leftarrow.gif" alt="{$Previous}" /></a>
   <a title="{$Next}" class="next" href="view_m.php?vid={$vid}&amp;date={$nextDate}">
     <img src="images/rightarrow.gif" alt="{$Next}" /></a>
   <div class="title">
     <span class="date">{$WC->thisdate|date_to_str}</span><br />
     <span class="viewname">{$view_name}</span>
   </div>
</div><br />

<br /><br />

<table class="main">
  <tr>
	 <th class="empty">&nbsp;</th>
  {foreach from=$viewusers key=k item=v}
   <th style="width:{$v.tdw}%;">{$v.fullname}</th>
  {/foreach}
  </tr>
  {foreach from=$weekloop key=k item=v}
  <tr>
	  <th $class>{$v.weekday}&nbsp;{$v.date}</th>
   {foreach from=$viewusers key=l item=w}
     <td {$w.class} style="width:{$v.tdw}%;">
    {if $s.ADD_LINK_IN_VIEWS}
      {add_icon date=$v.dateYmd user=$v}
    {/if}
    </td>
   {/foreach}
  </tr>
  {/foreach}
  </table>

{include file="footer.tpl"}

