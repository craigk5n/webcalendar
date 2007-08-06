    {include file="header.tpl"}
    <form action="edit_nonusers_handler.php" name="editnonuser" method="post" onsubmit="return valid_form (this);">
   {if $nidData.admin}
      <input type="hidden" name="old_admin" value="{$nidData.admin}" />
		{/if}
      <h2>{if $nid}{'Edit User'|translate}{else}{'Add User'|translate}{/if}</h2>
      <table>
        <tr>
          <td><label for="calid">{'Calendar ID'|translate}:</label></td>
          <td>
						 <input type="text" name="nid" id="nid" size="20" onchange="check_name ();" maxlength="20" value="{$nidData.login}"/> {'word characters only'|translate}
					</td>
        </tr>
        <tr>
          <td><label for="nfirstname">{'First Name'|translate}:</label></td>
          <td><input type="text" name="nfirstname" id="nfirstname" size="20"maxlength="25" value="{$nidData.firstname}" /></td>
        </tr>
       <tr>
          <td><label for="nlastname">{'Last Name'|translate}:</label></td>
          <td><input type="text" name="nlastname" id="nlastname" size="20"maxlength="25" value="{$nidData.lastname}" /></td>
        </tr>
        <tr>
          <td><label for="nadmin">{'Admin'|translate}:</label></td>
          <td>
            <select name="nadmin" id="nadmin">
    {foreach from=$userlist key=k item=v}
              <option value="{$v.cal_login_id}" {if $nidData.admin == $v.cal_login_id} selected="selected" {/if}>{$v.cal_fullname}</option>
		 {/foreach}
            </select>
          </td>
        </tr>

  {if ! $smarty.const._WC_HTTP_AUTH}
      <tr>
        <td valign="top"><label for="nispublic">
           {'Is public calendar'|translate}:</label></td>
        <td>&nbsp;&nbsp;
					{print_radio variable=nispublic defIdx=$nidData.is_public}
        </td>
     </tr>
	{/if}
     <tr>
       <td valign="top"><label for="nisselected">
           {'Is default participant'|translate}:</label></td>
          <td>&nbsp;&nbsp;
					{print_radio variable=nisselcted defIdx=$nidData.is_selected}
        </td>
     </tr>
     <tr>
       <td valign="top"><label for="nviewpart">
           {'Can see participants'|translate}:</label></td>
          <td>&nbsp;&nbsp;
					{print_radio variable=nviewpart defIdx=$nidData.view_part}
        </td>
     </tr>
	{if $nidData.login}
		 <tr>
       <td valign="top"><label for="nuc_url">
           {'Login URL'|translate}:</label></td>
		   <td><a href="{$nuc_url}" target="_top">{$nuc_url}</a>
			 </td>
		 </tr>
	{/if}
   </table>
	 <br />
      <input type="submit" name="action" value="{if $add}{'Add'|translate}{else}{'Save'|translate}{/if}" />
{if $nid}
  <input type="submit" name="delete" value="{'Delete'|translate}" onclick="return confirm('{$confirmStr}')" />
{/if}
    </form>
{include file="footer.tpl" include_nav_links=false}