{include file="header.tpl"}
<h2>{if $adding_report}{'Add Report'|translate}{else}{'Edit Report'|translate}{/if}</h2>


<form action="edit_report_handler.php" method="post" name="reportform">
{if ! $adding_report}
  <input type="hidden" name="report_id" value="{$report_id}" />
{/if}

<table>
 <tr>
   <td>
     <label for="rpt_name">{'Report name'|translate}:</label></td>
	<td>
    <input type="text" name="report_name" id="rpt_name" size="40" maxlength="50"
    value="{$report_name}" />
  </td>
 </tr>
 <tr>
   <td>
	   <label for="rpt_user">{'User'|translate}:</label></td>
   <td>
	   <select name="report_user" id="rpt_user" size="1">
		{foreach from=$users key=k item=v}
		  <option value="{$k}" {$v.selected}>{$v.cal_fullname}</option>
		{/foreach}
     </select>
   </td>
  </tr>

{if $WC->isAdmin() }
   <tr>
	   <td>
		   <label>{'Global'|translate}:</label></td>
		 <td>{print_radio variable='is_global' defIdx=$report_is_global}
     </td>
	</tr>
  <tr>
	  <td>
		   <label>{'Include link in menu'|translate}:</label></td>
		<td>{print_radio variable='show_in_trailer' defIdx=$report_show_in_menu}
    </td>
	</tr>
{/if}

  <tr>
	  <td>
		  <label>{'Include standard header/trailer'|translate}:</label></td>
		<td>{print_radio variable=include_header' defIdx=$report_include_header} 
    </td>
	</tr>
 
  <tr>
	  <td>
		  <label>{'Include previous/next links'|translate}:</label></td>
		<td>{print_radio variable='allow_nav' defIdx=$report_allow_nav}
    </td>
	</tr>

  <tr>
	  <td>
		  <label>{'Include empty dates'|translate}:</label></td>
		<td>{print_radio variable='include_empty' defIdx=$report_include_empty} 
    </td>
	</tr>

  <tr>
	  <td>
      <label for="rpt_time_range">{'Date range'|translate}:</label></td>
		<td>
      <select name="time_range" id="rpt_time_range">
    {foreach from=$rpt_ranges key=k item=v}
        <option value="{$k}" {$v.selected}>{$v.desc}</option>
		{/foreach}
      </select>
    </td>
	</tr>
{if $sCATEGORIES_ENABLED}
  <tr>
	  <td>
      <label for="rpt_cat_id">{'Category'|translate}:</label></td>
		<td>
      <select name="cat_id" id="rpt_cat_id">
        <option value="">{'None'|translate}</option>
			{foreach from=$categories key=k item=v}
        <option value="{$k}" {$v.selected}>{$v.cat_name}</option>
      {/foreach}
      </select>
    </td>
	</tr>
{/if}
</table>

<table>
 <tr>
   <td>&nbsp;</td>
   <td>&nbsp;</td>
   <td colspan="2" class="odd">
     <label>{'Template variables'|translate}</label>
   </td>
 </tr>
 <tr>
   <td valign="top">
     <label>{'Page template'|translate}:</label>
   </td>
   <td>
     <textarea rows="12" cols="60" name="page_template">{$page_template|htmlspecialchars:ENT_COMPAT:$charset}</textarea>
  </td><td class="aligntop cursoradd odd" colspan="2">

  {foreach from=$page_options key=k item=v}
     <a onclick="addMe( 'page_template', '{$v}' )">{$v}</a><br />
  {/foreach}

 </td></tr>
 <tr>
   <td valign="top">
     <label>{'Day template'|translate}:</label>
    </td>
    <td>
      <textarea rows="12" cols="60" name="day_template">{$day_template|htmlspecialchars:ENT_COMPAT:$charset}</textarea>
    </td>
    <td class="aligntop cursoradd odd" colspan="2">

  {foreach from=$day_options key=k item=v}
     <a onclick="addMe( 'day_template', '{$v}' )">{$v}</a><br />
  {/foreach}
   </td>
 </tr>
 <tr>
   <td valign="top">
     <label>{'Event template'|translate}:</label>
   </td>
   <td>
    <textarea rows="12" cols="60" name="event_template" id="event_template">{$event_template|htmlspecialchars:ENT_COMPAT:$charset}</textarea>
   </td>
   <td class="aligntop cursoradd odd" width="150px">
  {foreach from=$event_options key=k item=v}
    <a onclick="addMe( 'event_template', '{$v}' )">{$v}</a><br />
  {/foreach}
   </td>
   <td class="aligntop cursoradd odd">
  {if $extra_names}
    <label>'Site Extras'|translate}</label><br />
   {foreach from=$extra_names key=k item=v}
     <a onclick="addMe( 'event_template', '$&#123extra:{$v.name}&#125' )">$&#123extra:{$v.name}&#125</a><br />
   {/foreach}
  {/if}

 </td></tr>
 <tr><td colspan="4">
  <input type="submit" value="{'Save'|translate}" />
  {if ! $adding_report}
  &nbsp;&nbsp;<input type="submit" name="delete" value="{'Delete'|translate}"
  onclick="return confirm('{$confirmDeleteStr}');" />
  {/if}
 </td></tr>
</table>
</form>

{include file="footer.tpl"}

