   {include file="header.tpl"}

{if ! $logout}
<script type="text/javascript">
function valid_form ( form ) {ldelim}
  if ( form.login.value.length == 0 || form.password.value.length == 0 ) {ldelim}
    alert ( "__You must enter a login and password@D__" );
    return false;
  {rdelim}
  return true;
{rdelim}
function myOnLoad() {ldelim}
  document.login_form.login.focus();
  {if $login}
	  document.login_form.login.select();
	{/if}
  {if $error}
     alert ( '{$error}' );
  {/if}
{rdelim}
</script>
{/if}

<h2>{$appStr}</h2>


{if $error}
  <span style="color:#FF0000; font-weight:bold;">__Error__: {$error}</span>
{/if}
 <br />
{if $logout}
  <p>__You have been logged out__</p>
  <br /><br />
  <a href="login.php{if $return_path}?return_path={$return_path|htmlentities}{/if}" 
	class="nav">__Login__</a>
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
 <label for="user">__Username__:</label></td><td>
 <input name="login" id="user" size="15" maxlength="25" value="{$last_login}" tabindex="1" />
</td></tr>
<tr><td class="alignR">
 <label for="password">__Password__:</label></td><td>
 <input name="password" id="password" type="password" size="15" 
   maxlength="30" tabindex="2" />
</td></tr>
<tr><td colspan="3" style="font-size: 10px;">
 <input type="checkbox" name="remember" id="remember" tabindex="3" 
   value="yes" {if $remember_last_login}{#checked#}{/if}/>
	 <label for="remember">&nbsp;__Save login via cookies__</label>
</td></tr>
<tr><td colspan="4" class="alignC">
 <input type="submit" value="__Login__" tabindex="4" />
</td></tr>
</table>
</form>

{foreach from=$nuclist key=k item=v}
  <a class="nav" href="nulogin.php?login={$v.user}">__Access@R1__{$v.fullname}__calendar@L1__</a><br />
{/foreach}

{if $s.DEMO_MODE}
  <b>Demo login: user = "demo", password = "demo"</b><br />
{/if}
<br /><br />

{if $valid_ip}
  <b><a href="register.php">__Not registered?__</a></b><br />
{/if}
{/if}
<span class="cookies">__cookies-note__</span><br />
<hr />
<br />
<a href="{$smarty.const.PROGRAM_URL}" id="programname">{$smarty.const.PROGRAM_NAME}</a>

    {include file="footer.tpl" include_nav_links=false}