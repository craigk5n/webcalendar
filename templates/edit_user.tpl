{include file="header.tpl"}
<table border="0">
 <tr><td style="vertical-align:top; width:50%;" colspan="2">
<h2>{if $user.login_id}{'Edit User'|translate}{else}{'Add User'|translate}{/if}</h2>
</td>
<td rowspan="4">
{if $user.login_id  && $chgPasswd}
<table><tr>
<td class="aligntop" colspan="2">
<h2>{'Change Password'|translate}</h2>
 <form action="edit_user_handler.php" method="post"  onsubmit="return valid_form2(this);" >
  <input type="hidden" name="formtype" value="setpassword" />
  {if $WC->isAdmin() }
  <input type="hidden" name="user" value="{$user.login_id}" />
  {/if}
</td></tr>
 <tr><td>
  <label for="newpass1">{'New Password'|translate}:</label></td><td>
  <input name="upassword1" id="newpass1" type="password" size="15" />
 </td></tr>
 <tr><td>
  <label for="newpass2">{'New Password'|translate} ({'again'|translate}):</label></td><td>
  <input name="upassword2" id="newpass2" type="password" size="15" />
 </td></tr>
 <tr><td colspan="2">
  {if $s.DEMO_MODE }
   <input type="button" value="{'Set Password'|translate}" onclick="alert('{'Disabled for demo'|translate:true}')" />
  {else}
   <input type="submit" value="{'Set Password'|translate}" />
  {/if}
</form>
 </td></tr>
</table>
{/if}
  <form action="edit_user_handler.php" name="edituser" method="post" onsubmit="return valid_form(this);" >
  <input type="hidden" name="formtype" value="edituser" />
{if  ! $user.login_id }
  <input type="hidden" name="add" value="1" />
{else}
  <input type="hidden" name="user" value="{$user.login_id}" />
{/if}
</td>
</tr>
 <tr><td>
  <label for="username">{'Username'|translate}:</label></td><td>
  <input type="text" name="username" id="username" size="25" onchange="check_name();" maxlength="25" value="{$user.login}" />
 </td></tr>
 <tr><td>
  <label for="ufirstname">{'First Name'|translate}:</label></td><td>
  <input type="text" name="ufirstname" id="ufirstname" size="20" value="{$user.firstname}" />
 </td></tr>
 <tr><td>
  <label for="ulastname">{'Last Name'|translate}:</label></td><td>
  <input type="text" name="ulastname" id="ulastname" size="20" value="{$user.lastname}" />
 </td></tr>
 <tr><td>
  <label for="uemail">{'E-mail address'|translate}:</label></td>
	<td colspan="2">
  <input type="text" name="uemail" id="uemail" size="20" value="{$user.email}" onchange="check_uemail();" />
 </td></tr>
{if $s.EXTENDED_USER}
   <tr><td >
  <label for="uemail">{'User Title'|translate}:</label></td>
	<td colspan="2">
  <input type="text" name="utitle" id="utitle" size="20" value="{$user.title}" />
 </td></tr>
 <tr><td>
  <label for="uemail">{'Telephone'|translate}:</label></td>
	<td colspan="2">
  <input type="text" name="utelephone" id="utelephone" size="20" value="{$user.telephone}" />
 </td></tr>
 <tr><td>
  <label for="uemail">{'Address'|translate}:</label></td>
	<td colspan="2">
  <textarea cols="30" rows="1"  name="uaddress" id="uaddress">{$user.adress}</textarea>
 </td></tr>
  <tr><td>
  <label for="uemail">{'Birthday'|translate}:</label></td>
	<td colspan="2">
  {date_selection prefix='birthday' date=$user.birthday}
 </td></tr>
{/if}
{if ! $user.login_id && $chgPasswd}
 <tr><td>
  <label for="pass1">{'Password'|translate}:</label></td>
	<td colspan="2">
  <input name="upassword1" id="pass1" size="15" value="" type="password" />
 </td></tr>
 <tr><td>
  <label for="pass2">{'Password'|translate} ({'again'|translate}):</label></td>
	<td colspan="2">
  <input name="upassword2" id="pass2" size="15" value="" type="password" />
 </td></tr>
{/if}
{if $WC->isAdmin() && ! $WC->isLogin() }
 <tr><td class="bold">
  {'Admin'|translate}:</td>
	<td colspan="2">
  {print_radio variable=uis_admin defIdx=$user.is_admin}
 </td></tr>
 <tr><td class="bold">
  {'Enabled'|translate}:</td>
	<td colspan="2">
  {print_radio variable=u_enabled defIdx=$user.enabled}
 </td></tr>
{elseif $WC->isAdmin() }
  <input type="hidden" name="uis_admin" value="Y" />
  <input type="hidden" name="u_enabled" value="Y" />
{/if}
 <tr><td colspan="3">
{if $s.DEMO_MODE }
   <input type="button" value="{'Save'|translate}" onclick="alert('{'Disabled for demo'|translate:true}')" />
   {if $WC->isAdmin() && $user}
    <input type="submit" name="Delete" value="{'Delete'|translate}" onclick="alert('{'Disabled for demo'|translate:true}');setIgnore()" />
	 {/if}
{else}
   <input type="submit" value="{if $user}{'Save'|translate}{else}{'Add'|translate}{/if}" />
  {if $WC->isAdmin() && $chgPasswd}
    <input type="submit" name="Delete" value="{'Delete'|translate}" onclick="return confirm('{$deleteConfirm}');setIgnore()" />
  {/if}
{/if}
</form>

</td></tr></table>
{include file="footer.tpl" include_nav_links=false}