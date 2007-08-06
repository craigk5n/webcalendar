   {include file="header.tpl"}
<h2>{if $handler}{'Import Results'|translate}{else}{'Import'|translate}{/if} {generate_help_icon url='help_import.php'}</h2>

{if ! $upload_enabled}
<p>{'Disabled'|translate} (php.ini file_uploads)</p>
{elseif ! $handler}
<form action="import.php" method="post" name="importform"  enctype="multipart/form-data" onsubmit="return checkExtension()">
<table>
<tr><td>
 <label for="importtype">{'Import format'|translate}:</label></td><td>
  <select name="ImportType" id="importtype" onchange="toggle_import()">
   <option value="ICAL">iCal</option>
   <option value="PALMDESKTOP">Palm Desktop</option>
   <option value="VCAL">vCal</option>
      <option value="OUTLOOKCSV">Outlook (CSV)</option>
  </select>
</td></tr>
<!-- Valid only for Palm Desktop import -->
<tr id="palm">
  <td>
 <label>{'Exclude private records'|translate}:</label></td><td>
 <label><input type="radio" name="exc_private" value="1" checked="checked" />{'Yes'|translate}</label> 
 <label><input type="radio" name="exc_private" value="0" />{'No'|translate}</label>
  </td>
</tr>
<!-- /PALM -->
<!-- Not valid for Outlook CSV as it doesn't generate UID for import tracking -->
<tr id="ivcal">
  <td>
 <label>{'Overwrite Prior Import'|translate}:</label></td><td>
 <label><input type="radio" name="overwrite" value="Y" checked="checked" />&nbsp;{'Yes'|translate}</label> 
 <label><input type="radio" name="overwrite" value="N" />&nbsp;{'No'|translate}</label>
  </td>
</tr>
<!-- /IVCAL -->
<tr id="outlookcsv">
  <td colspan="2">
 <label>{'Repeated items are imported separately. Prior imports are not overwritten.'|translate}</label></td><td>
  </td>
</tr>
<tr class="browse"><td>
 <label for="fileupload">{'Upload file'|translate}:</label></td><td>
 <input type="file" name="FileName" id="fileupload" size="45" maxlength="50" />
  </td>
</tr>
{if $users }
<tr>
  <td class="aligntop"><label for="caluser">{'Calendar'|translate}:</label>
	</td>
	<td>
	  <select name="calUser" id="caluser" size="{$size}">
		{foreach from=$users key=k item=v}
       <option value="{$k}" {$v.selected}>{$v.fullname}</option>
		{/foreach}
    </select>
	</td>
</tr>
{/if}
</table>
<br /><input type="submit" value="{'Import'|translate}" />
</form>
{else}
  {if $data && ! $errormsg }
	  {$importmsg}
    {'Events successfully imported'|translate}: {$count_suc}<br />
    {'Events from prior import marked as deleted'|translate}: {$numDeleted}<br />
    {if  $s.CHECK_CONFLICTS }
      {'Conflicting events'|translate}: {$count_con}<br />
		{/if}
    {'Errors'|translate}: {$error_num}<br /><br />
  {else if $errormsg }
    <br /><br />
    <b>{'Error'|translate}:</b>{$errormsg}<br />
  {/if}
{/if}
    {include file="footer.tpl"}

