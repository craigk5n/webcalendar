{include file="header.tpl"}
<table>
 <tr><td style="vertical-align:top;" colspan="3">
<h2>{if $user.login_id}__Edit User__{else}__Add User__{/if}</h2>
</td>
{if $user.login_id  && $chgPasswd}
<td rowspan="4" valign="top">
<table><tr>
<td class="alignT" colspan="2">
<h2>__Change Password__</h2>
 <form action="edit_user_handler.php" method="post" onsubmit="return valid_form2(this);" >
  <input type="hidden" name="formtype" value="setpassword" />
  {if $WC->isAdmin() }
  <input type="hidden" name="user" value="{$user.login_id}" />
  {/if}
</td></tr>
 <tr><td>
  <label for="newpass1">__New Password__:</label></td><td>
  <input name="upassword1" id="newpass1" type="password" size="15" />
 </td></tr>
 <tr><td>
  <label for="newpass2">__New Password__ (__again__):</label></td><td>
  <input name="upassword2" id="newpass2" type="password" size="15" />
 </td></tr>
 <tr><td colspan="2">
  {if $s.DEMO_MODE }
   <input type="button" value="__Set Password__" onclick="alert('__Disabled for demo@D__')" />
  {else}
   <input type="submit" value="__Set Password__" />
  {/if}
</form>
 </td></tr>
</table>
</td>
{/if}
  <form action="edit_user_handler.php" name="edituserform" id="edituserform" method="post" onsubmit="return valid_form(this);" >
  <input type="hidden" name="formtype" value="edituser" />
{if  ! $user.login_id }
  <input type="hidden" name="add" value="1" />
{else}
  <input type="hidden" name="user" value="{$user.login_id}" />
{/if}

</tr>
 <tr><td>
  <label for="username">__Username__:</label></td><td>
  <input type="text" name="username" id="username" size="25" onchange="check_name();" maxlength="25" value="{$user.login}" />
 </td>
 {if ! $user.login_id && $chgPasswd}
 <td rowspan="2">
   <table align="left">
  <tr><td>
  <label for="pass1">__Password__:</label></td>
	<td>
  <input name="upassword1" id="pass1" size="15" value="" type="password" />
 </td></tr>
 <tr><td>
  <label for="pass2">__Password__ (__again__):</label></td>
	<td>
  <input name="upassword2" id="pass2" size="15" value="" type="password" />
 </td></tr>
 </table>
  </td>
 {/if}
 </tr>
 <tr><td>
  <label for="ufirstname">__First Name__:</label></td><td>
  <input type="text" name="ufirstname" id="ufirstname" size="20" value="{$user.firstname}" />
 </td></tr>
 <tr><td>
  <label for="ulastname">__Last Name__:</label></td>
	<td colspan="3">
  <input type="text" name="ulastname" id="ulastname" size="20" value="{$user.lastname}" />
 </td></tr>
 <tr><td>
  <label for="uemail">__E-mail address__:</label></td>
	<td colspan="3">
  <input type="text" name="uemail" id="uemail" size="20" value="{$user.email}" onchange="check_uemail();" />
 </td></tr>
{if $s.EXTENDED_USER}
   <tr><td >
  <label for="uemail">__User Title__:</label></td>
	<td colspan="3">
  <input type="text" name="utitle" id="utitle" size="30" value="{$user.title}" />
 </td></tr>
 <tr><td>
  <label for="uemail">__Telephone__:</label></td>
	<td colspan="3">
  <input type="text" name="utelephone" id="utelephone" size="20" value="{$user.telephone}" />
 </td></tr>
 <tr><td>
  <label for="uemail">__Address__:</label></td>
	<td colspan="3">
  <textarea cols="30" rows="1" name="uaddress" id="uaddress">{$user.address}</textarea>
 </td></tr>
  <tr><td>
  <label for="ubirth">__Birthday__:</label></td>
	<td colspan="3">
  {date_selection prefix='ubirth' date=$user.birthday year_pre=80 blank=true}
 </td></tr>
{/if}
{if $WC->isAdmin() && ! $WC->isLogin() }
 <tr><td class="bold">
  __Admin__:</td>
	<td colspan="3">
  {print_radio variable=uis_admin defIdx=$user.is_admin}
 </td></tr>
 <tr><td class="bold">
  __Enabled__:</td>
	<td colspan="3">
  {print_radio variable=uenabled defIdx=$user.enabled}
 </td></tr>
 <tr><td class="bold">
  __Reset Login__:</td>
	<td colspan="3">
  {print_checkbox name='reset_login' value='N'}
 </td></tr>
{elseif $WC->isAdmin() }
  <input type="hidden" name="uis_admin" value="Y" />
  <input type="hidden" name="uenabled" value="Y" />
{/if}
 <tr><td colspan="4">
{if $s.DEMO_MODE }
   <input type="button" value="__Save__" onclick="alert('__Disabled for demo@D__')" />
   {if $WC->isAdmin() && $user}
    <input type="submit" name="Delete" value="__Delete__" onclick="alert('__Disabled for demo@D__');setIgnore()" />
	 {/if}
{else}
   <input type="submit" value="{if $user}__Save__{else}__Add__{/if}" />
  {if $WC->isAdmin() && $chgPasswd}
    <input type="submit" name="Delete" value="__Delete__" onclick="return confirm('__ruSureUser@D__');setIgnore()" />
  {/if}
{/if}
</form>

</td></tr></table>
{include file="footer.tpl" include_nav_links=false}