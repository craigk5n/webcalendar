 {include file="header.tpl"}
<h2>{'Preferences'|translate} {$prefTitle}
{generate_help_icon url='help_pref.php'}
</h2>

<form action="pref.php{$qryStr}" method="post" onsubmit="return valid_form(this);" name="prefform">
<input type="hidden" name="currenttab" id="currenttab" value="{$WC->getPOST ( 'currenttab' )}" />
{if $user}
  <input type="hidden" name="user" value="{$user}" />
{/if}
<input type="submit" value="{'Save'|translate}" name="" />
&nbsp;&nbsp;&nbsp;

{if $nulist}
 <select onchange="location=this.options[this.selectedIndex].value;">
   <option {#selected#} disabled="disabled" value="">{$nonUserStr}</option>
{foreach from=$nulist key=k item=v}
   <option value="pref.php?user={$v.cal_login_id}">{$v.cal_fullname}</option>
{/foreach}
  </select>
{elseif $WC->isUser()}
{generate_href_button label=$myPrefStr attrib='onclick="location.href=\'pref.php\'"'} 
{/if}

<br/><br />

<!-- TABS -->
{print_tabs tabs=$tabs_ar}

<!-- TABS BODY -->
<div id="tabscontent">
 <!-- DETAILS -->
<div id="tabscontent_settings">
<fieldset>
 <legend>{'Language'|translate}</legend>
<table width="100%">
<tr><td  class="tooltipselect" title="{'language-help'|translate}">
 <label for="pref_language">{'Language'|translate}:</label></td><td>
 <select name="pref_LANGUAGE" id="pref_lang">
 	{foreach from=$languages key=k item=v}
	 {if $k != 'Browser-defined' || $WC->isAdmin() || $WC->isNonuserAdmin()}
    <option value="{$v}" {if $v == $p.LANGUAGE}{#selected#}{/if}>{$k}</option>
		{/if}
	{/foreach}
 </select>&nbsp;&nbsp;{'Your browser default language is'|translate} {$WC->browserLang()}
</td></tr>
</table>
</fieldset>
<fieldset>
 <legend>{'Date and Time'|translate}</legend>
<table width="100%">
{if $can_set_timezone}
<tr><td class="tooltipselect" title="{'tz-help'|tooltip}">
  <label for="pref_TIMEZONE">{'Timezone Selection'|translate}:</label></td><td>
  {print_timezone_list prefix='pref_'}
</td></tr>
{/if}
<tr><td class="tooltipselect" title="{'date-format-help'|tooltip}">
<label for="pref_DATE_FORMAT">{'Date format'|translate}:</label></td><td>
 <select name="pref_DATE_FORMAT">
  {foreach from=$datestyles key=k item=v}
    <option value="{$k}" {if $p.DATE_FORMAT == $v}{#selected#}{/if}>{$v}</option>
	{/foreach}
 </select>&nbsp;{$choices.month} {$choices.day} {$choices.year}<br />
 <select name="pref_DATE_FORMAT_MY">
  {foreach from=$datestyles_my key=k item=v}
    <option value="{$k}" {if $p.DATE_FORMAT == $v}{#selected#}{/if}>{$v}</option>
	{/foreach}
  </select>&nbsp;{$choices.month} {$choices.year}<br />

  <select name="pref_DATE_FORMAT_MD">
  {foreach from=$datestyles_md key=k item=v}
    <option value="{$k}" {if $p.DATE_FORMAT == $v}{#selected#}{/if}>{$v}</option>
	{/foreach}
  </select>&nbsp;{$choices.month} {$choices.day}<br />

  <select name="pref_DATE_FORMAT_TASK">
  {foreach from=$datestyles_task key=k item=v}
    <option value="{$k}" {if $p.DATE_FORMAT == $v}{#selected#}{/if}>{$v}</option>
{/foreach}
  </select>&nbsp;{'Small Task Date'|translate}
</td></tr>
	<tr><td class="tooltip" title="{'display-week-starts-on'|tooltip}">
	 {'Week starts on'|translate}:</td><td>
	 <select name="pref_WEEK_START" id="pref_WEEK_START">
	 {section name=weekStart loop=7}
		<option value="{$smarty.section.weekStart.index}" {if $smarty.section.weekStart.index == $p.WEEK_START}{#selected#}{/if}>{weekday_name day=$smarty.section.weekStart.index}</option>
	 {/section}
	 </select>
	</td></tr>
	
	<tr><td class="tooltip" title="{'display-weekend-starts-on'|tooltip}">
	 {'Weekend starts on'|translate}:</td><td>
	 <select name="pref_WEEKEND_START" id="pref_WEEKEND_START">
	 {section name=weekendStart loop=7}
		<option value="{$smarty.section.weekendStart.index}" {if $smarty.section.weekendStart.index == $p.WEEKEND_START}{#selected#}{/if}>{weekday_name day=$smarty.section.weekendStart.index}</option>
	 {/section}
	 </select>
	</td></tr>

 <tr><td class="tooltip" title="{'time-format-help'|tooltip}">
  {'Time format'|translate}:</td><td>
  {print_radio variable='TIME_FORMAT' vars=$time_format_array}
 </td></tr>
 <tr><td class="tooltip" title="{'work-hours-help'|tooltip}">
  {'Work hours'|translate}:</td><td>
  <label for="pref_WORK_DAY_START_HOUR">{'From'|translate}&nbsp;</label>
  <select name="pref_WORK_DAY_START_HOUR" id="pref_WORK_DAY_START_HOUR">
	 {section name=workstart loop=24}
    <option value="{$smarty.section.workstart.index}" {if $smarty.section.workstart.index == $p.WORK_DAY_START_HOUR}{#selected#}{/if}>{$smarty.section.workstart.index*3600|display_time:1}
		 </option>
	 {/section}
  </select>&nbsp;
  <label for="pref_WORK_DAY_END_HOUR">{'to'|translate}&nbsp;</label>
  <select name="pref_WORK_DAY_END_HOUR" id="pref_WORK_DAY_END_HOUR">
	 {section name=workend loop=24}
    <option value="{$smarty.section.workend.index}" {if $smarty.section.workend.index == $p.WORK_DAY_END_HOUR}{#selected#}{/if}>{$smarty.section.workend.index*3600|display_time:1}
		 </option>
	 {/section}
  </select>
 </td></tr>
</table>
</fieldset>
<fieldset>
 <legend>{'Appearance'|translate}</legend>
<table width="100%">
<tr><td class="tooltip" title="{'preferred-view-help'|tooltip}">{'Preferred view'|translate}:</td><td>
<select name="pref_STARTVIEW">
{foreach from=$choices key=k item=v}
  <option value="{$k}" {if $p.STARTVIEW == $k}{#selected#}{/if}>{$v}</option>
{/foreach}
{foreach from=$views key=k item=v}
  <option value="{$v.url}" {if $p.STARTVIEW == $k}{#selected#}{/if}>{$v.cal_name}</option>
{/foreach}
</select>
</td></tr>

<tr><td class="tooltipselect" title="{'fonts-help'|tooltip}">
 <label for="pref_font">{'Fonts'|translate}:</label></td><td>
 <input type="text" size="40" name="pref_FONTS" id="pref_font" value="{$p.FONTS|htmlspecialchars}" />
</td></tr>

<tr><td class="tooltip" title="{'display-sm_month-help'|tooltip}">
 {'Display small months'|translate}:</td><td>
 {print_radio variable='DISPLAY_SM_MONTH'}
</td></tr>

<tr><td class="tooltip" title="{'display-weekends-help'|tooltip}">
 {'Display weekends'|translate}:</td><td>
 {print_radio variable='DISPLAY_WEEKENDS'}
</td></tr>
 <tr><td class="tooltip" title="{'display-long-daynames-help'|tooltip}">
  {'Display long day names'|translate}:</td><td>
  {print_radio variable='DISPLAY_LONG_DAYS'}
 </td></tr>
<tr><td class="tooltip" title="{'display-minutes-help'|tooltip}">
 {'Display 00 minutes always'|translate}:</td><td>
 {print_radio variable='DISPLAY_MINUTES'}
</td></tr>
<tr><td class="tooltip" title="{'display-end-times-help'|tooltip}">
 {'Display end times on calendars'|translate}:</td><td>
 {print_radio variable='DISPLAY_END_TIMES'}
</td></tr>
<tr><td class="tooltip" title="{'display-alldays-help'|tooltip}">
  {'Display all days in month view'|translate}:</td><td>
  {print_radio variable='DISPLAY_ALL_DAYS_IN_MONTH'}
 </td></tr> 
<tr><td class="tooltip" title="{'display-week-number-help'|tooltip}">
 {'Display week number'|translate}:</td><td>
 {print_radio variable='DISPLAY_WEEKNUMBER'}
</td></tr>
<tr><td class="tooltip" title="{'display-tasks-help'|tooltip}">
 {'Display small task list'|translate}:</td><td>
 {print_radio variable='DISPLAY_TASKS'}
</td></tr>
<tr><td class="tooltip" title="{'display-tasks-in-grid-help'|tooltip}">
 {'Display tasks in Calendars'|translate}:</td><td>
 {print_radio variable='DISPLAY_TASKS_IN_GRID'}
</td></tr>

<tr><td class="tooltip" title="{'lunar-help'|tooltip}">
 {'Display Lunar Phases in month view'|translate}:</td><td>
 {print_radio variable='DISPLAY_MOON_PHASES'}
</td></tr>

</table>
</fieldset>
<fieldset>
 <legend>{'Events'|translate}</legend>
<table width="100%">

<tr><td class="tooltip" title="{'display-unapproved-help'|tooltip}">
 {'Display unapproved'|translate}:</td><td>
 {print_radio variable='DISPLAY_UNAPPROVED'}
</td></tr>

<tr><td class="tooltip" title="{'timed-evt-len-help'|tooltip}">
 {'Specify timed event length by'|translate}:</td><td>
 {print_radio variable='TIMED_EVT_LEN' vars=$timed_evt_len_array}
</td></tr>

{if $categories}
<tr><td>
 <label for="pref_cat">{'Default Category'|translate}:</label></td><td>
 <select name="pref_CATEGORY_VIEW" id="pref_cat">
  {foreach from=$categories key=k item=v}
   <option value={$k}{if $p.CATEGORY_VIEW== $k}{#selected#}{/if}>{$v.cat_name}</option>
	{/foreach}
 </select>
</td></tr>
{/if}
<tr><td class="tooltip" title="{'crossday-help'|tooltip}">
 {'Disable Cross-Day Events'|translate}:</td><td>
 {print_radio variable='DISABLE_CROSSDAY_EVENTS'}
</td></tr>
<tr><td class="tooltip" title="{'display-desc-print-day-help'|tooltip}">
 {'Display description in printer day view'|translate}:</td><td>
 {print_radio variable='DISPLAY_DESC_PRINT_DAY'}
</td></tr>

<tr><td class="tooltip" title="{'entry-interval-help'|tooltip}">
 {'Entry interval'|translate}:</td><td>
 <select name="pref_ENTRY_SLOTS">
  <option value="24" {if $p.ENTRY_SLOTS == '24'}{#selected#}{/if}>1 {'hour'|translate}</option>
  <option value="48" {if $p.ENTRY_SLOTS == '48'}{#selected#}{/if}>30 {$minutesStr}</option>
  <option value="72" {if $p.ENTRY_SLOTS == '72'}{#selected#}{/if}>20 {$minutesStr}</option>
  <option value="96" {if $p.ENTRY_SLOTS == '96'}{#selected#}{/if}>15 {$minutesStr}</option>
  <option value="144" {if $p.ENTRY_SLOTS == '144'}{#selected#}{/if}>10 {$minutesStr}</option>
  <option value="288" {if $p.ENTRY_SLOTS == '288'}{#selected#}{/if}>5 {$minutesStr}</option>
  <option value="1440" {if $p.ENTRY_SLOTS == '1440'}{#selected#}{/if}>1 {'minute'|translate}</option>
 </select>
</td></tr>
<tr><td class="tooltip" title="{'time-interval-help'|tooltip}">
 {'Time interval'|translate}:</td><td>
 <select name="pref_TIME_SLOTS">
  <option value="24" {if $p.TIME_SLOTS == '24'}{#selected#}{/if}>1 {'hour'|translate}</option>
  <option value="48" {if $p.TIME_SLOTS == '48'}{#selected#}{/if}>30 {$minutesStr}</option>
  <option value="72" {if $p.TIME_SLOTS == '72'}{#selected#}{/if}>20 {$minutesStr}</option>
  <option value="96" {if $p.TIME_SLOTS == '96'}{#selected#}{/if}>15 {$minutesStr}</option>
  <option value="144" {if $p.TIME_SLOTS == '144'}{#selected#}{/if}>10 {$minutesStr}</option>
  <option value="288" {if $p.TIME_SLOTS == '288'}{#selected#}{/if}>5 {$minutesStr}</option>
  <option value="1440" {if $p.TIME_SLOTS == '1440'}{#selected#}{/if}>1 {'minute'|translate}</option>
 </select>
</td></tr>
</table>
</fieldset>
<fieldset>
 <legend>{'Miscellaneous'|translate}</legend>
<table width="100%">

<tr><td class="tooltip" title="{'auto-refresh-help'|tooltip}">
 {'Auto-refresh calendars'|translate}:</td><td>
 {print_radio variable='AUTO_REFRESH'}
</td></tr>

<tr><td class="tooltip" title="{'auto-refresh-time-help'|tooltip}">
 &nbsp;&nbsp;&nbsp;&nbsp;{'Auto-refresh time'|translate}:</td><td>
 <input type="text" name="pref_AUTO_REFRESH_TIME" size="4" value="{$p.AUTO_REFRESH_TIME}" />{$minutesStr}
</td></tr>
</table>
</fieldset>
</div>
<!-- END SETTINGS -->

{if $themes}
<div id="tabscontent_themes">
<table width="100%">
<tr><td class="tooltip"  title="{'theme-reload-help'|tooltip}"colspan="3">{'Page may need to be reloaded for new Theme to take effect'|translate}</td></tr>
<tr><td  class="tooltipselect" title="{'themes-help'|tooltip}">
 <label for="pref_THEME">{'Themes'|translate}:</label></td><td>
 <select name="pref_THEME" id="pref_THEME">
   <option disabled="disabled">{'AVAILABLE THEMES'|translate}</option>
   <option  value="none" selected="selected">{'None'|translate}</option>
 {foreach from=$themes key=k item=v}
   <option value="{$v.1}">{$v.0}</option>
 {/foreach}
 </select></td><td>
 <input type="button" name="preview" value="{'Preview'|translate}" onclick="return showPreview()" />
</td></tr>
</table>
</div>
<!-- END THEMES -->
{/if}

{if $send_email}
<div id="tabscontent_email">
<table width="100%">
<tr><td class="tooltip">
 {'Email format preference'|translate}:</td><td>
 {print_radio variable='EMAIL_HTML' vars=$email_format_array}
</td></tr>

<tr><td class="tooltip">
 {'Event reminders'|translate}:</td><td>
 {print_radio variable='EMAIL_REMINDER'}
</td></tr>

<tr><td class="tooltip">
 {'Events added to my calendar'|translate}:</td><td>
 {print_radio variable='EMAIL_EVENT_ADED'}
</td></tr>

<tr><td class="tooltip">
 {'Events updated on my calendar'|translate}:</td><td>
 {print_radio variable='EMAIL_EVENT_UPDTED'}
</td></tr>

<tr><td class="tooltip">
 {'Events removed from my calendar'|translate}:</td><td>
 {print_radio variable='EMAIL_EVENT_DELTED'}
</td></tr>

<tr><td class="tooltip">
 {'Event rejected by participant'|translate}:</td><td>
 {print_radio variable='EMAIL_EVENT_REJCTED'}
</td></tr>

<tr><td class="tooltip">
 {'Event that I create'|translate}:</td><td>
 {print_radio variable='EMAIL_EVENT_CRETE'}
</td></tr>
</table>
</div>
<!-- END EMAIL -->
{/if}

<div id="tabscontent_boss">
<table width="100%">
{if $send_email}
<tr><td class="tooltip">{'Email me event notification'|translate}:</td><td>
 {print_radio variable='EMAIL_ASSISTANT_EVENTS'}
</td></tr>
{/if}
<tr><td class="tooltip">{'I want to approve events'|translate}:</td><td>
 {print_radio variable='APPROVE_ASSISTANT_EVENT'}
</td></tr>

<tr><td class="tooltip" title="{'display_byproxy-help'|tooltip}">{'Display if created by Assistant'|translate}:</td><td>
  {print_radio variable='DISPLAY_CREATED_BYPROXY'}
</td></tr>
</table>
</div>
<!-- END BOSS -->

{if $publish_enabled || $rss_enabled}
<div id="tabscontent_subscribe">
<table width="100%">
<tr><td class="tooltipselect" title="{'allow-view-subscriptions-help'|tooltip}">{'Allow remote viewing of'|translate}:</td><td>
  <select name="pref_USER_REMOTE_ACCESS">
   <option value="0" {if $publish_access == '0'}
{#selected#}{/if}>{'Public'|translate} {'entries'|translate}</option>
   <option value="1" {if $publish_access == '1'}
{#selected#}{/if}>{'Public'|translate} &amp; {'Confidential'|translate} {'entries'|translate}</option>
   <option value="2" {if $publish_access == '2'}
{#selected#}{/if}>{'All'|translate} {'entries'|translate}</option>  
  </select>  
  </td></tr>
  {if $publish_enabled}
<tr><td class="tooltipselect" title="{'allow-remote-subscriptions-help'|tooltip}">{'Allow remote subscriptions'|translate}:</td><td>
  {print_radio variable='USER_PUBLISH_ENABLED'}
</td></tr>
   {if $server_url}
<tr><td class="tooltipselect" title="{'remote-subscriptions-url-help'|tooltip}">&nbsp;&nbsp;&nbsp;&nbsp;{'URL'|translate}:</td>
  <td>
    {$server_url|htmlspecialchars}publish.php/{$user}.ics
    <br />
    {$server_url|htmlspecialchars}publish.php?user={$user}
</td></tr>
    {/if}

<tr><td class="tooltipselect" title="{'allow-remote-publishing-help'|tooltip}">{'Allow remote publishing'|translate}:</td>
  <td>
  {print_radio variable='USER_PUBLISH_RW_ENABLED'}
</td></tr>
   {if $server_url}
<tr><td class="tooltipselect" title="{'remote-publishing-url-help'|tooltip}">&nbsp;&nbsp;&nbsp;&nbsp;{'URL'|translate}:</td>
  <td>
    {$server_url|htmlspecialchars}icalclient.php
</td></tr>
    {/if}

  {/if}

  {if $rss_enabled}
<tr><td class="tooltipselect" title="{'rss-enabled-help'|tooltip}">{'Enable RSS feed'|translate}:</td>
  <td>
  {print_radio variable='USER_RSS_ENABLED'}
</td></tr>
    {if $server_url}
<tr><td class="tooltipselect" title="{'rss-feed-url-help'|tooltip}">&nbsp;&nbsp;&nbsp;&nbsp;{'URL'|translate}:</td>
  <td>
    {$server_url|htmlspecialchars}rss.php?user={$user}
  </td></tr>
    {/if}
  {/if}

<tr><td class="tooltipselect" title="{'freebusy-enabled-help'|tooltip}">{'Enable FreeBusy publishing'|translate}:</td>
  <td>
  {print_radio variable='FREEBUSY_ENABLED'}
</td></tr>
  {if $server_url}
<tr><td class="tooltipselect" title="{'freebusy-url-help'|tooltip}">&nbsp;&nbsp;&nbsp;&nbsp;{'URL'|translate}:</td>
  <td>
    {$server_url|htmlspecialchars}freebusy.php/{$user}.ifb
    <br />
    {$server_url|htmlspecialchars}freebusy.php?user={$user}
  </td></tr>
  {/if}
</table>
</div>
<!-- END SUBSCRIBE -->
{/if}

{if $allow_user_header}
<div id="tabscontent_header">
<table  width="100%">
{if $custom_script}
 <tr><td class="tooltip" title="{'custom-script-help'|tooltip}">
  {'Custom script/stylesheet'|translate}:</td><td>
  {print_radio variable='CUSTOM_SCRIPT'}&nbsp;&nbsp;
  <input type="button" value="{'Edit'|translate}..." onclick="{$openS}" name="" />
 </td></tr>
{/if}
{if $custom_header}
 <tr><td class="tooltip" title="{'custom-header-help'|tooltip}">
  {'Custom header'|translate}:</td><td>
  {print_radio variable='CUSTOM_HEADER'}&nbsp;&nbsp;
  <input type="button" value="{'Edit'|translate}..." onclick="{$openH}" name="" />
 </td></tr>
{/if}
{if $custom_trailer}
 <tr><td class="tooltip" title="{'custom-trailer-help'|tooltip}">
  {'Custom trailer'|translate}:</td><td>
  {print_radio variable='CUSTOM_TRAILER'}&nbsp;&nbsp;
  <input type="button" value="{'Edit'|translate}..." onclick="{$openT}" name="" />
 </td></tr>
{/if}
</table>
</div>
<!-- END HEADER -->
{/if}

<!-- BEGIN COLORS -->

{if $allow_color_customization}
<div id="tabscontent_colors">
<fieldset>
 <legend>{'Color options'|translate}</legend>
<table width="100%">
<tr><td  colspan="4"></td><td rowspan="17" valign="middle">
<!-- BEGIN EXAMPLE MONTH -->
<table class="demotable"><tr>
<td width="1%" rowspan="3">&nbsp;</td>
<td style="text-align:center; color:{$p.H2COLOR}; font-weight:bold;">
{$demoMonthDate}</td>
<td width="1%" rowspan="3">&nbsp;</td></tr>
<tr><td bgcolor="{$p.BGCOLOR}">
{display_month}
</td></tr>
<tr><td>&nbsp;</td></tr>
</table>
<!-- END EXAMPLE MONTH -->
</td></tr>
 <tr><td>
 {html_color_input name='BGCOLOR' title='Document background'|translate}
</td>
</tr>
<tr><td>
 {html_color_input name='H2COLOR' title='Document title'|translate}
</td></tr>
<tr><td>
 {html_color_input name='TEXTCOLOR' title='Document text'|translate}
</td></tr>
<tr><td>
 {html_color_input name='MYEVENTS' title='My event text'|translate}
</td></tr>
<tr><td>
 {html_color_input name='TABLEBG' title='Table grid'|translate}
</td></tr>
<tr><td>
 {html_color_input name='THBG' title='Table header background'|translate}
</td></tr>
<tr><td>
 {html_color_input name='THFG' title='Table header text'|translate}
</td></tr>
<tr><td>
 {html_color_input name='CELLBG' title='Table cell background'|translate}
</td></tr>
<tr><td>
 {html_color_input name='TODAYCELLBG' title='Table cell background for current day'|translate}
</td></tr>
<tr><td>
 {html_color_input name='HASEVENTSBG' title='Table cell background for days with events'|translate}
</td></tr>
<tr><td>
  {html_color_input name='WEEKENDBG' title='Table cell background for weekends'|translate}
</td></tr>
<tr><td>
  {html_color_input name='OTHERMONTHBG' title='Table cell background for other month'|translate}
</td></tr>
<tr><td>
  {html_color_input name='WEEKNUMBER' title='Week number'|translate}
</td></tr>
<tr><td>
 {html_color_input name='POPUP_BG' title='Event popup background'|translate}
</td></tr>
<tr><td>
  {html_color_input name='POPUP_FG' title='Event popup text'|translate}
</td></tr>
</table>
</fieldset>
</div>
<!-- END COLORS -->
{/if}

<!-- END TABS -->
<br /><br />
<div>
<input type="submit" value="{'Save'|translate}" name="" />
<br/><br/>
</div>
</form>
{include file="footer.tpl"}
