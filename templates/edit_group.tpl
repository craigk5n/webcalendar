 {include file="header.tpl"}
<form action="edit_group_handler.php" method="post">
  <h2>
	{if $newgroup}{'Add Group'|translate}</h2>
   <input type="hidden" name="add" value="1">
   {else}{'Edit Group'|translate}</h2>
   <input type="hidden" name="id" value="{$gid}">
	 {/if}
   <table>
     <tr>
       <td class="bold"><label for="groupname">{'Group name'|translate}:</label></td>
       <td><input type="text" name="groupname" id="groupname" size="20" value="{$groupname|htmlspecialchars}" /></td>
     </tr>
	{if ! $newgroup}
     <tr>
       <td class="aligntop bold">{'Updated'|translate}:</td>
       <td>{$groupupdated|date_to_str}</td>
     </tr>
     <tr>
       <td class="aligntop bold">{'Created by'|translate}:</td>
       <td>{$groupowner}</td>
     </tr>
	{/if}
     <tr>
       <td class="aligntop bold"><label for="users">{'Users'|translate}:</label></td>
       <td>
         <select name="users[]" id="users" size="10" multiple="multiple">
      {foreach from=$userlist  key=k item=v}
           <option value="{$v.cal_login_id}">{$v.cal_fullname}</option>
      {/foreach}
        </select>
       </td>
       <td></td>
			 <td>
         <select name="group[]" id="group" size="10" multiple="multiple">
      {foreach from=$grouplist  key=k item=v}
           <option value="{$v.cid}">{$v.fullname}</option>
      {/foreach}
        </select>
       </td>
     </tr>
     <tr>
       <td colspan="2" class="aligncenter"><br />
         <input type="submit" name="action" value="{if $newgroup}{'Add'|translate}{else}{'Save'|translate}{/if}" />
      {if ! $newgroup}
         <input type="submit" name="delete" value="{'Delete'|translate}" onclick="return confirm('{$deleteConfirm}')" />
	    {/if}
       </td>
     </tr>
   </table>
 </form>
{include file="footer.tpl" include_nav_links=false}
