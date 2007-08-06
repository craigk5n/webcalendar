{include file="header.tpl"}
<table align="center" border="0" width="250px">
  <tr>
    <th colspan="3">{'Categories'|translate}</th>
  </tr>
  <form action="" method="post" name="editCategories" onSubmit="sendCats (this)">
  <tr>
    <td valign="top">
      <select name="cats[]" size="10">
         <option disabled>{$availCatStr}</option>
       {foreach from=$catList key=k item=v}
         <option value="{$k}" >{$v.name}</option>
			{/foreach}
			</select>
    </td>
    <td valign="center"><input type="button" value=">>" onclick="selAdd ()"/>
		</td>
    <td align="center" valign="top">
      <select name="eventcats[]" size="9" multiple>
        <option disabled>{$entryCatStr}</option>
       {foreach from=$eventcats key=k item=v}
        <option value="{$k}" {$v.disabled}>{$v.name}</option>
       {/foreach}
      </select>
      <input type="button" value="{'Remove'|translate}" onclick="selRemove ()" />
     </td>
   </tr>
   <tr>
      <td valign="top" align="right">*{'Global Category'|translate}&nbsp;&nbsp;&nbsp;
			  <input type="button" value="{'OK'|translate}" onclick="sendCats ()" />
			</td>
      <td colspan="2" align="left">&nbsp;&nbsp;
			  <input type="button" value="{'Cancel'|translate}" onclick="window.close ()" /></td>
    </tr>
   </form>
  </table>
{include file="footer.tpl" include_nav_links=false}