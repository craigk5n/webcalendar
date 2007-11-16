{include file="header.tpl"}
<h2>__Administrative Tools__</h2>
<table border="1" class="admin">
  <tr>
{foreach from=$names key=k item=v name=links}
{assign var=index value=$smarty.foreach.links.index}
{if $index>0 && $index%$columns==0}</tr><tr>{/if}
  <td><a href="{$v.link}">{$v.name}</a></td>
{/foreach}
{section loop=$columns-$index%$columns-1 name=spacers}
  <td>&nbsp;</td>
{/section}  
  </tr >
</table>
{include file="footer.tpl"}

