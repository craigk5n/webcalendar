{if $include_header}
  {include file="header.tpl"}
	
	<h2>{$report_name}</h2>
{/if}

{if $report_allow_nav}
<a class="nav" title="{$Previous}" href="report.php?report_id={$report_id}{$u_url}&amp;offset={$prev}">{$Previous}</a>
<a class="nav" title="{$Next}" href="report.php?report_id={$report_id}{$u_url}&amp;offset={$next}">{$Next}</a>
{/if}
<h2>{$manageStr}</h2>
  {$list}
  {$textStr}
{if $include_header}
  {include file="footer.tpl"}
{/if}

