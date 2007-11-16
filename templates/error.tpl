{include file="header.tpl"}
<h2>__Error__</h2>
{if $not_auth}
 __You are not authorized__
{else}
  __The following error occurred__ : 
  {if $errorStr}
    <blockquote>{$errorStr}</blockquote>
  {else}
    <blockquote>__Unknown Error__</blockquote>
  {/if}
{/if}
{include file="footer.tpl"}
