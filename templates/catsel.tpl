{include file="header.tpl"}
<form action="" method="post" name="editCategories" onsubmit="sendCats (this)">
<table align="center" border="0" width="250px">
  <tr>
    <th colspan="3">__Categories__</th>
  </tr>
  <tr>
    <td valign="top">
      <select name="cats[]" id="cats" size="10">
         <option {#disabled#}>__AVAILABLE CATEGORIES@P30__</option>
       {foreach from=$catList key=k item=v}
         <option value="{$k}" >{$v.name}</option>
			{/foreach}
			</select>
    </td>
    <td valign="middle"><input type="button" value=">>" onclick="selAdd ()"/>
		</td>
    <td align="center" valign="top">
      <select name="eventcats[]" id="eventcats" size="10" multiple="multiple">
        <option {#disabled#}>__ENTRY CATEGORIES@P30__</option>
       {foreach from=$eventList key=k item=v}
        <option value="{$k}" {$v.disabled}>{$v.name}</option>
       {/foreach}
      </select>
      <input type="button" value="__Up__" onclick="moveUp()" />
      <input type="button" value="__Down__" onclick="moveDown()" />
      <input type="button" value="__Remove__" onclick="selRemove()" />
     </td>
   </tr>
   <tr>
      <td valign="top" align="right">*__Global Category__&nbsp;&nbsp;&nbsp;
			  <input type="button" value="__Ok__" onclick="sendCats ()" />
			</td>
      <td colspan="2" align="left">&nbsp;&nbsp;
			  <input type="button" value="__Cancel__" onclick="window.close ()" /></td>
    </tr>
  </table>
</form>
{include file="footer.tpl" include_nav_links=false}