    {include file="header.tpl"}
    <form action="edit_nonusers_handler.php" name="editnonuser" method="post" onsubmit="return valid_form (this);">
   {if $nidData.admin}
      <input type="hidden" name="old_admin" value="{$nidData.admin}" />
		{/if}
      <h2>{if $nid}__Edit User__{else}__Add User__{/if}</h2>
      <table>
        <tr>
          <td><label for="calid">__Calendar ID__:</label></td>
          <td>
						 <input type="text" name="nid" id="nid" size="20" onchange="check_name ();" maxlength="20" value="{$nidData.login}"/> __word characters only__
					</td>
        </tr>
        <tr>
          <td><label for="nfirstname">__First Name__:</label></td>
          <td><input type="text" name="nfirstname" id="nfirstname" size="20"maxlength="25" value="{$nidData.firstname}" /></td>
        </tr>
       <tr>
          <td><label for="nlastname">__Last Name__:</label></td>
          <td><input type="text" name="nlastname" id="nlastname" size="20"maxlength="25" value="{$nidData.lastname}" /></td>
        </tr>
        <tr>
          <td><label for="nadmin">__Admin__:</label></td>
          <td>
            <select name="nadmin" id="nadmin">
    {foreach from=$userlist key=k item=v}
              <option value="{$v.cal_login_id}" {if $nidData.admin == $v.cal_login_id} {#selected#} {/if}>{$v.cal_fullname}</option>
		 {/foreach}
            </select>
          </td>
        </tr>

  {if ! $smarty.const._WC_HTTP_AUTH}
      <tr>
        <td valign="top"><label for="nispublic">
           __Is public calendar__:</label></td>
        <td>&nbsp;&nbsp;
					{print_radio variable=nispublic defIdx=$nidData.is_public}
        </td>
     </tr>
	{/if}
     <tr>
       <td valign="top"><label for="nisselected">
           __Is default participant__:</label></td>
          <td>&nbsp;&nbsp;
					{print_radio variable=nisselcted defIdx=$nidData.is_selected}
        </td>
     </tr>
     <tr>
       <td valign="top"><label for="nviewpart">
           __Can see participants__:</label></td>
          <td>&nbsp;&nbsp;
					{print_radio variable=nviewpart defIdx=$nidData.view_part}
        </td>
     </tr>
	{if $nidData.login}
		 <tr>
       <td valign="top"><label for="nuc_url">
           __Login URL__:</label></td>
		   <td><a href="{$nuc_url}" target="_top">{$nuc_url}</a>
			 </td>
		 </tr>
	{/if}
   </table>
	 <br />
      <input type="submit" name="action" value="{if $add}__Add__{else}__Save__{/if}" />
{if $nid}
  <input type="submit" name="delete" value="__Delete__" onclick="return confirm('__ruSureEntry@D__')" />
{/if}
    </form>
{include file="footer.tpl" include_nav_links=false}