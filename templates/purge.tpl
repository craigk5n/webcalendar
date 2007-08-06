{include file="header.tpl"}
<table>
  <tr>
	  <td style="vertical-align:top;" colspan="2">
    <h2>{'Delete Events'|translate} {$preview}</h2>
    </td>
	</tr>
{if $do_purge}
  <tr>
	  <td>
      <h3>{$preview} {'Purging events for'|translate} {$user}...</h3>
    </td>
	</tr>
	<tr>
    <td>
  {if $tables}
	 {$preview} {'Records deleted from'|translate} 
	   <ul>
  {foreach from=$tables key=k item=v}
	    <li>{$v.name}: {$v.num}</li>
	 {/foreach}
	   </ul>
  {else}
	 {'None'|translate}
	{/if}
		</td>
  </tr>
  <tr>
    <td>
    <h3>...{'Finished'|translate}</h3>

  <input type="button" value="{'Back'|translate}" onclick="history.back()" />
  {if $purgeDebug}
    <div style="border: 1px solid #000;background-color: #fff;"><tt>{$sqlLog}</tt></div>
  {/if}
	</td>
 </tr>
{else}
 <tr>
   <form action="purge.php" method="post" name="purgeform" id="purgeform">
   <td><label for="user">{'User'|translate}:</label></td>
   <td>
     <select name="user">
  {foreach from=$userlist key=k item=v}
	      <option value="{$k}" {$v.selected}>{$v.fullname}</option>
	{/foreach}
	      <option value="ALL">{'All Users'|translate}</option>
     </select>
   </td>
 </tr>
 <tr>
   <td><label for="purge_all">{'Delete <b>ALL</b> events for this user'|translate}: </label></td>
   <td valign="bottom">
     <input type="checkbox" name="purge_all" value="Y" id="purge_all" onchange="toggle_datefields( 'dateArea', this );" />
   </td>
 </tr>
 <tr id="dateArea">
   <td><label>{'Delete all events before'|translate}:</label></td>
	 <td>{date_selection prefix='end_'}
   </td>
 </tr>
 <tr>
   <td><label for="purge_deleted">{'Purge deleted only'|translate}:</label>
	 </td>
   <td valign="bottom">
     <input type="checkbox" name="purge_deleted" value="Y"  />
   </td>
 </tr>
 <tr>
   <td><label for="preview">{'Preview delete'|translate}:</label></td>
   <td valign="bottom">
     <input type="checkbox" name="preview" value="Y" checked="checked" />
   </td>
 </tr>
 <tr>
   <td colspan="2">
     <input type="submit" name="delete" value="{'Delete'|translate}" onclick="return confirm('{'Are you sure you want to delete events for'|translate:true} ' + document.purgeform.user.options[document.purgeform.user.selectedIndex].text + '?')" />
   </td>
 </tr>
  </form>
{/if}
</table>
{include file="footer.tpl"}
