 {include file="header.tpl"}
<form action="edit_group_handler.php" name="editgroup" method="post" onsubmit="selAll();">
  <h2>
	{if $newgroup}__Add Group__</h2>
   <input type="hidden" name="add" value="1">
   {else}__Edit Group__</h2>
   <input type="hidden" name="gid" value="{$gid}">
	 {/if}
   <table>
     <tr>
       <td class="bold">
			   <label for="groupname">__Group name__:</label></td>
       <td colspan="2">
			   <input type="text" name="groupname" id="groupname" size="20" value="{$groupname|htmlspecialchars}" /></td>
     </tr>
	{if ! $newgroup}
     <tr>
       <td class="alignT bold">__Updated__:</td>
       <td colspan="2">{$groupupdated|date_to_str}</td>
     </tr>
     <tr>
       <td class="alignT bold">__Created by__:</td>
       <td colspan="2">{$groupowner}</td>
     </tr>
	{/if}
	   <tr>
		   <td colspan="3">&nbsp;</td>
		 </tr>
     <tr>
       <td class="alignT alignC bold boxL boxT boxR">
         <label for="users">__Available__&nbsp;__Users__</label>
       </td>
			 <td>&nbsp;</td>
       <td class="alignT alignC bold boxL boxT boxR">
         <label for="users">__Group Members__</label>
       </td>
     </tr>
     <tr>
       <td valign="top" class="boxL boxR boxB">
         <select name="users[]" id="users" size="10" class="fixed" multiple="multiple">
      {foreach from=$userlist  key=k item=v}
           <option value="{$v.cal_login_id}">{$v.cal_fullname}</option>
      {/foreach}
        </select>
       </td>
       <td valign="middle"><input type="button" value=">>" onclick="selAdd ()"/>
		   </td>
			 <td align="center"  valign="top"  class="boxL boxR boxB">
         <select name="group[]" id="group" size="10" class="fixed" multiple="multiple">
      {foreach from=$grouplist  key=k item=v}
           <option value="{$k}">{$v}</option>
      {/foreach}
        </select>
				<br />
				<input type="button" value="__Remove__" onclick="selRemove()" />
       </td>
     </tr>
     <tr>
       <td colspan="3" class="alignC"><br />
         <input type="submit" name="action" value="{if $newgroup}__Add__{else}__Save__{/if}" />
      {if ! $newgroup}
         <input type="submit" name="delete" value="__Delete__" onclick="return confirm('__ruSureGroup@D__')" />
	    {/if}
       </td>
     </tr>
   </table>
 </form>
{include file="footer.tpl" include_nav_links=false}
