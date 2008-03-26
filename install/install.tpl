<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head><title>__WebCalendar Setup Wizard__</title>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<script type="text/javascript" src="../includes/js/prototype.js"></script>
<script type="text/javascript" src="install.js"></script>
<link rel="stylesheet" type="text/css" href="install.css" />
<link rel="shortcut icon" href="/favicon.ico" type="image/x-icon" />
</head>
<body {$direction} {$BodyX}>
<div class="title" >&nbsp;
    <img src="../images/icons/k5n.png" width="80" height="32"/>
    <span>__WebCalendar Setup@L2__</span>
</div>
<div class="install">
  <div class="menu">
		  <table width="90%" align="center" cellpadding="0" cellspacing="0">
    {foreach from=$menu key=k item=v name=menu}
		{assign var=page value=$smarty.foreach.menu.index+1}
      <tr {if $v.active}class="active"{/if}><td width="18px">{if $v.complete}
       <img src="../images/recommended.gif">{/if}</td>
			 <td><a href="index.php?page={$page}">{$v.title}</a>
			 </td></tr>
    {/foreach}
	    </table>
  </div>
	<div class="menu" >     
		<label>__Setup Progress__</label>
    <div class="progress">
		<img src="../images/progress.png" title="{$progress}%" width="{$progress}%" height="18px"></div>
  </div>
</div>
<div class="main box">
		  <table width="100%">
        <tr><td><h3>{$main.title}</h3></td></tr>
        <tr><td><h4>{$main.text}</h4></td></tr>
			  <tr><td class="grid">
						<form name="{$main.formname}" action="index.php" method="post">
							<input type="hidden" name="page" value="{$page}" />
						{foreach from=$fields key=k item=v}
						<p>{$v.text}</p>
						{if $v.type != 'select'}
							<input type="{$v.type}" name="{$k}" value="{$v.value}" size="{$v.size}"/>
						{ else }
							<select type="select" name="{$k}" >
							{foreach from=$v.options key=ok item=ov}
								<option value="{$ok}" {if
									$ov.selected}{#selected#}{/if}>{$ov}</option>
							{/foreach}
							</select>
						{/if}
						{/foreach}
						 <div><input style="color:#4F3AAA"type="submit" name="submit" value="__Continue__" /></div>
					 </form>
				</td></tr>
			</table>
</div>
</body>
</html>