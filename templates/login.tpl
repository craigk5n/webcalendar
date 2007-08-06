   {include file="header.tpl"}

{if ! $logout}
{literal}
<script type="text/javascript">
function valid_form ( form ) {
  if ( form.login.value.length == 0 || form.password.value.length == 0 ) {
    alert ( '{/literal}{'You must enter a login and password'|translate:true}{literal}' );
    return false;
  }
  return true;
}
function myOnLoad() {
  document.login_form.login.focus();
{/literal}
  {if $login}
	  document.login_form.login.select();
	{/if}
  {if $error}
     alert ( '{$error}' );
  {/if}
{literal}
}
</script>
{/literal}
{/if}

<h2>{$appStr}</h2>


{if $error}
  <span style="color:#FF0000; font-weight:bold;">{'Error'|translate}: {$error}</span>
{/if}
 <br />
{if $logout}
  <p>{'You have been logged out'|translate}</p>
  <br /><br />
  <a href="login.php{if $return_path}?return_path={$return_path|htmlentities}{/if}" 
	class="nav">{'Login'|translate}</a>
	<br /><br /><br />


{else}

<form name="login_form" id="login" action="login.php" method="post" 
  onsubmit="return valid_form(this)">

{if $return_path}
  <input type="hidden" name="return_path" value="{$return_path|htmlentities}" />
{/if}

<table align="center" cellspacing="10" cellpadding="10">
<tr><td rowspan="2">
 <img src="images/login.gif" alt="Login" /></td><td align="right">
 <label for="user">{'Username'|translate}:</label></td><td>
 <input name="login" id="user" size="15" maxlength="25" value="{$last_login}" tabindex="1" />
</td></tr>
<tr><td class="alignright">
 <label for="password">{'Password'|translate}:</label></td><td>
 <input name="password" id="password" type="password" size="15" 
   maxlength="30" tabindex="2" />
</td></tr>
<tr><td colspan="3" style="font-size: 10px;">
 <input type="checkbox" name="remember" id="remember" tabindex="3" 
   value="yes" {if $remember_last_login}{#checked#}{/if}/>
	 <label for="remember">&nbsp;{'Save login via cookies so I don&#39;t have to login next time'|translate}</label>
</td></tr>
<tr><td colspan="4" class="aligncenter">
 <input type="submit" value="{'Login'|translate}" tabindex="4" />
</td></tr>
</table>
</form>

{foreach from=$nuclist key=k item=v}
  <a class="nav" href="nulogin.php?login={$k}">{'Access'|translate} {$v} {'calendar'}translate}</a><br />
{/foreach}

{if $s.DEMO_MODE}
  <b>Demo login: user = "demo", password = "demo"</b><br />
{/if}
<br /><br />

{if $valid_ip}
  <b><a href="register.php">{'Not yet registered? Register here!'|translate}</a></b><br />
{/if}
{/if}
<span class="cookies">{'cookies-note'|translate}</span><br />
<hr />
<br />
<a href="{$smarty.const.PROGRAM_URL}" id="programname">{$smarty.const.PROGRAM_NAME}</a>

    {include file="footer.tpl" include_nav_links=false}