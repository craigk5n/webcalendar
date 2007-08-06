{include file="header.tpl"}
<h2>{'Error'|translate}</h2>
{if $not_auth}
 {'You are not authorized'|translate}
{else}
  {'The following error occurred'|translate} : 
  {if $errorStr}
    <blockquote>{$errorStr}</blockquote>
  {else}
    <blockquote>{'Unknown Error'|translate}</blockquote>
  {/if}
{/if}
{include file="footer.tpl"}
