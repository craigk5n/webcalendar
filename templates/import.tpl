   {include file="header.tpl"}
<h2>{if $handler}__Import Results__{else}__Import__{/if} {generate_help_icon url='help_import.php'}</h2>

{if ! $upload_enabled}
<p>__Disabled__ (php.ini file_uploads)</p>
{elseif ! $handler}
<form action="import.php" method="post" name="importform"  enctype="multipart/form-data" onsubmit="return checkExtension()">
<table>
<tr><td>
 <label for="importtype">__Import format__:</label></td><td>
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
 <label>__Exclude private records__:</label></td><td>
 <label><input type="radio" name="exc_private" value="1" checked="checked" />__Yes__</label> 
 <label><input type="radio" name="exc_private" value="0" />__No__</label>
  </td>
</tr>
<!-- /PALM -->
<!-- Not valid for Outlook CSV as it doesn't generate UID for import tracking -->
<tr id="ivcal">
  <td>
 <label>__Overwrite Prior Import__:</label></td><td>
 <label><input type="radio" name="overwrite" value="Y" checked="checked" />&nbsp;__Yes__</label> 
 <label><input type="radio" name="overwrite" value="N" />&nbsp;__No__</label>
  </td>
</tr>
<!-- /IVCAL -->
<tr id="outlookcsv">
  <td colspan="2">
 <label>__Repeated items are imported separately__</label></td><td>
  </td>
</tr>
<tr class="browse"><td>
 <label for="fileupload">__Upload file__:</label></td><td>
 <input type="file" name="FileName" id="fileupload" size="45" maxlength="50" />
  </td>
</tr>
{if $users }
<tr>
  <td class="alignT"><label for="caluser">__Calendar__:</label>
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
<br /><input type="submit" value="__Import__" />
</form>
{else}
  {if $data && ! $errormsg }
	  {$importmsg}
    __Events successfully imported__: {$count_suc}<br />
    __Events from prior import marked as deleted__: {$numDeleted}<br />
    {if  $s.CHECK_CONFLICTS }
      __Conflicting events__: {$count_con}<br />
		{/if}
    __Errors__: {$error_num}<br /><br />
  {else if $errormsg }
    <br /><br />
    <b>__Error__:</b>{$errormsg}<br />
  {/if}
{/if}
    {include file="footer.tpl"}

