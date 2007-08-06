{include file="header.tpl"}
<h2>{'Unapproved Entries'|translate}</h2>
<form action="list_unapproved.php" name="listunapproved" method="post">
<table border="0">
 {foreach from=$users  key=user_id item=u_events name=u_loop}
  {assign var=count value=foreach.u_loop.index>1}
  <tr>
	  <td colspan="5"><h3>{$WC->getFullName($user_id)}</h3></td>
	</tr> 
	 {foreach from=$u_events key=k item=v name=ev_loop}      
  <tr {if $smarty.foreach.ev_loop.index%2==1}class="odd"{/if} >
    <td width="5%" align="right">
      <input type="checkbox" name="{$v.entryID}"  value="{$user_id}"/>
		</td>
    <td><a title="{'View this entry'|translate}" class="entry" id="{$v.popID}" href="view_entry.php?eid={$k}&amp;user={$user_id}">{$v.name|htmlspecialchars}</a>
         ({$v.date}):
		</td>
		<td align="center">
      <input type="image" src="images/check.gif" title="{'Approve/Confirm'|translate}" onclick="return do_confirm('approve','{$user_id}', '{$v.entryID}');" />
		</td>
    <td align="center">
      <input type="image" src="images/rejected.gif" title="{'Reject'|translate}" onclick="return do_confirm('reject','{$user_id}', '{$v.entryID}');" />
		</td>
   {if $can_delete}
    <td align="center">
      <input type="image" src="images/delete.png" title="{'Delete'|translate}" onclick="return do_confirm('delete','{$user_id}', '{$v.entryID}');" />
		</td>
   {/if}
  </tr>
   {/foreach}
 {if $count}
  <tr>
	  <td colspan="5" nowrap="nowrap">&nbsp;
      <img src="images/select.gif" border="0" alt="" />
      <label><a title="{'Check All'|translate}" onclick="check_all('{$user_id}');">{'Check All'|translate}</a>  /  
			<a  title="{'Uncheck All'|translate}" onclick="uncheck_all('{$user_id}');">{'Uncheck All'|translate}</a></label>&nbsp;&nbsp;
      <input  type="image" src="images/check.gif" title="{'Approve Selected'|translate}" onclick="return do_confirm('approveSelected','{$user_id}');" />
      &nbsp;
      <input  type="image" src="images/rejected.gif" title="{'Reject Selected'|translate}" onclick="return do_confirm('rejectSelected','{$user_id}');" />
      &nbsp;&nbsp;( {'Emails Will Not Be Sent'|translate} ) 
     </td>
	</tr>
 {else}
  {/if}
	{foreachelse}
	<tr>
	  <td colspan="5" class="nounapproved">{'No unapproved entries for'|translate}&nbsp;{$WC->getFullName($user_id)}
		</td>
	</tr>
  {/foreach}
</table>
<input type="hidden" name="process_action" value="" />
<input type="hidden" name="process_user" value="" />
</form>
{include file="footer.tpl"}