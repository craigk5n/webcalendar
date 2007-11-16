{include file="header.tpl"}
<h2>{if $adding_report}__Add Report__{else}__Edit Report__{/if}</h2>


<form action="edit_report_handler.php" method="post" name="reportform">
{if ! $adding_report}
  <input type="hidden" name="report_id" value="{$report_id}" />
{/if}

<table>
 <tr>
   <td>
     <label for="rpt_name">__Report name__:</label></td>
	<td>
    <input type="text" name="report_name" id="rpt_name" size="40" maxlength="50"
    value="{$report_name}" />
  </td>
 </tr>
 <tr>
   <td>
	   <label for="rpt_user">__User__:</label></td>
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
		   <label>__Global__:</label></td>
		 <td>{print_radio variable='is_global' defIdx=$report_is_global}
     </td>
	</tr>
  <tr>
	  <td>
		   <label>__Include link in menu__:</label></td>
		<td>{print_radio variable='show_in_trailer' defIdx=$report_show_in_menu}
    </td>
	</tr>
{/if}

  <tr>
	  <td>
		  <label>__Include standard header/trailer__:</label></td>
		<td>{print_radio variable=include_header' defIdx=$report_include_header} 
    </td>
	</tr>
 
  <tr>
	  <td>
		  <label>__Include previous/next links__:</label></td>
		<td>{print_radio variable='allow_nav' defIdx=$report_allow_nav}
    </td>
	</tr>

  <tr>
	  <td>
		  <label>__Include empty dates__:</label></td>
		<td>{print_radio variable='include_empty' defIdx=$report_include_empty} 
    </td>
	</tr>

  <tr>
	  <td>
      <label for="rpt_time_range">__Date range__:</label></td>
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
      <label for="rpt_cat_id">__Category__:</label></td>
		<td>
      <select name="cat_id" id="rpt_cat_id">
        <option value="">__None__</option>
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
     <label>__Template variables__</label>
   </td>
 </tr>
 <tr>
   <td valign="top">
     <label>__Page template__:</label>
   </td>
   <td>
     <textarea rows="12" cols="60" name="page_template">{$page_template|htmlspecialchars:ENT_COMPAT:$charset}</textarea>
  </td><td class="alignT cursoradd odd" colspan="2">

  {foreach from=$page_options key=k item=v}
     <a onclick="addMe( 'page_template', '{$v}' )">{$v}</a><br />
  {/foreach}

 </td></tr>
 <tr>
   <td valign="top">
     <label>__Day template__:</label>
    </td>
    <td>
      <textarea rows="12" cols="60" name="day_template">{$day_template|htmlspecialchars:ENT_COMPAT:$charset}</textarea>
    </td>
    <td class="alignT cursoradd odd" colspan="2">

  {foreach from=$day_options key=k item=v}
     <a onclick="addMe( 'day_template', '{$v}' )">{$v}</a><br />
  {/foreach}
   </td>
 </tr>
 <tr>
   <td valign="top">
     <label>__Event template__:</label>
   </td>
   <td>
    <textarea rows="12" cols="60" name="event_template" id="event_template">{$event_template|htmlspecialchars:ENT_COMPAT:$charset}</textarea>
   </td>
   <td class="alignT cursoradd odd" width="150px">
  {foreach from=$event_options key=k item=v}
    <a onclick="addMe( 'event_template', '{$v}' )">{$v}</a><br />
  {/foreach}
   </td>
   <td class="alignT cursoradd odd">
  {if $extra_names}
    <label>'Site Extras__</label><br />
   {foreach from=$extra_names key=k item=v}
     <a onclick="addMe( 'event_template', '$&#123extra:{$v.name}&#125' )">$&#123extra:{$v.name}&#125</a><br />
   {/foreach}
  {/if}

 </td></tr>
 <tr><td colspan="4">
  <input type="submit" value="__Save__" />
  {if ! $adding_report}
  &nbsp;&nbsp;<input type="submit" name="delete" value="__Delete__"
  onclick="return confirm('{$confirmDeleteStr}');" />
  {/if}
 </td></tr>
</table>
</form>

{include file="footer.tpl"}

