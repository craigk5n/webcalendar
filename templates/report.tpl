{if $include_header}
  {include file="header.tpl"}
	
	<h2>{$report_name}</h2>
{/if}

{if $report_allow_nav}
<a class="nav" title="{'Previous'|translate}" href="report.php?report_id={$report_id}{$u_url}&amp;offset={$prev}">{'Previous'|translate}</a>
<a class="nav" title="{'Next'|translate}" href="report.php?report_id={$report_id}{$u_url}&amp;offset={$next}">{'Next'|translate}</a>
{/if}
<h2>{$manageStr}</h2>
  {$list}
  {$textStr}
{if $include_header}
  {include file="footer.tpl"}
{/if}

