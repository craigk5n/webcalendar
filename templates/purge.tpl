{include file="header.tpl"}
<table>
  <tr>
	  <td style="vertical-align:top;" colspan="2">
    <h2>__Delete Events__ {$preview}</h2>
    </td>
	</tr>
{if $do_purge}
  <tr>
	  <td>
      <h3>{$preview} __Purging events for__ {$user}...</h3>
    </td>
	</tr>
	<tr>
    <td>
  {if $tables}
	 {$preview} __Records deleted from__ 
	   <ul>
  {foreach from=$tables key=k item=v}
	    <li>{$v.name}: {$v.num}</li>
	 {/foreach}
	   </ul>
  {else}
	 __None__
	{/if}
		</td>
  </tr>
  <tr>
    <td>
    <h3>...__Finished__</h3>

  <input type="button" value="__Back__" onclick="history.back()" />
  {if $purgeDebug}
    <div style="border: 1px solid #000;background-color: #fff;"><tt>{$sqlLog}</tt></div>
  {/if}
	</td>
 </tr>
{else}
 <tr>
   <form action="purge.php" method="post" name="purgeform" id="purgeform">
   <td><label for="user">__User__:</label></td>
   <td>
     <select name="user">
  {foreach from=$userlist key=k item=v}
	      <option value="{$k}" {$v.selected}>{$v.fullname}</option>
	{/foreach}
	      <option value="ALL">__All Users__</option>
     </select>
   </td>
 </tr>
 <tr>
   <td><label for="purge_all">__Delete <b>ALL</b> events for this user__: </label></td>
   <td valign="bottom">
     <input type="checkbox" name="purge_all" value="Y" id="purge_all" onchange="toggle_datefields( 'dateArea', 'purge_all' );" />
   </td>
 </tr>
 <tr id="dateArea">
   <td><label>__Delete all events before__:</label></td>
	 <td>{date_selection prefix='end_'}
   </td>
 </tr>
 <tr>
   <td><label for="purge_deleted">__Purge deleted only__:</label>
	 </td>
   <td valign="bottom">
     <input type="checkbox" name="purge_deleted" value="Y"  />
   </td>
 </tr>
 <tr>
   <td><label for="preview">__Preview delete__:</label></td>
   <td valign="bottom">
     <input type="checkbox" name="preview" value="Y" checked="checked" />
   </td>
 </tr>
 <tr>
   <td colspan="2">
     <input type="submit" name="delete" value="__Delete__" onclick="return confirm('__Are you sure you want to delete events for@D__ ' + document.purgeform.user.options[document.purgeform.user.selectedIndex].text + '?')" />
   </td>
 </tr>
  </form>
{/if}
</table>
{include file="footer.tpl"}
