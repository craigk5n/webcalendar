 {include file="header.tpl"}
<h2>__Preferences__ {$prefTitle}
{generate_help_icon url='help_pref.php'}
</h2>

<form action="pref.php{$qryStr}" method="post" onsubmit="return valid_form(this);" name="prefform">
<input type="hidden" name="currenttab" id="currenttab" value="{$WC->getPOST ( 'currenttab' )}" />
{if $user}
  <input type="hidden" name="user" value="{$user}" />
{/if}
<input type="submit" value="__Save__" name="" />
&nbsp;&nbsp;&nbsp;

{if $nulist}
 <select onchange="location=this.options[this.selectedIndex].value;">
   <option {#selected#} {#disabled#} value="">__Modify Non User Calendar Preferences__</option>
{foreach from=$nulist key=k item=v}
   <option value="pref.php?user={$v.cal_login_id}">{$v.cal_fullname}</option>
{/foreach}
  </select>
{elseif $WC->isUser()}
{generate_href_button label='__Return to My Preferences__' attrib='onclick="location.href=\'pref.php\'"'} 
{/if}

<br/><br />

<!-- TABS -->
{print_tabs tabs=$tabs_ar}

<!-- TABS BODY -->
<div id="tabscontent">
 <!-- DETAILS -->
<div id="tabscontent_settings">
<fieldset>
 <legend>__Language__</legend>
<table width="100%">
<tr><td  class="tooltipselect" title="__language-help__">
 <label for="pref_language">__Language__:</label></td><td>
 <select name="pref_LANGUAGE" id="pref_lang">
   {foreach from=$languages key=k item=v}
   {if $k != 'Browser-defined' || $WC->isAdmin() || $WC->isNonuserAdmin()}
    <option value="{$v}" {if $v == $p.LANGUAGE}{#selected#}{/if}>{$k}</option>
    {/if}
  {/foreach}
 </select>__Your browser default language is@L2,R1__{$WC->browserLang()}
</td></tr>
</table>
</fieldset>
<fieldset>
 <legend>__Date and Time__</legend>
<table width="100%">
{if $can_set_timezone}
<tr><td class="tooltipselect" title="__tz-help@T__">
  <label for="pref_TIMEZONE">__Timezone Selection__:</label></td><td>
  {print_timezone_list prefix='pref_'}
</td></tr>
{/if}
<tr><td class="tooltipselect" title="__date-format-help@T__">
<label for="pref_DATE_FORMAT">__Date format__:</label></td><td>
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
  </select>&nbsp;__Small Task Date__
</td></tr>
  <tr><td class="tooltip" title="__display-week-starts-on@T__">
   __Week starts on__:</td><td>
   <select name="pref_WEEK_START" id="pref_WEEK_START">
   {section name=weekStart loop=7}
    <option value="{$smarty.section.weekStart.index}" {if $smarty.section.weekStart.index == $p.WEEK_START}{#selected#}{/if}>{weekday_name day=$smarty.section.weekStart.index}</option>
   {/section}
   </select>
  </td></tr>
  
  <tr><td class="tooltip" title="__display-weekend-starts-on@T__">
   __Weekend starts on__:</td><td>
   <select name="pref_WEEKEND_START" id="pref_WEEKEND_START">
   {section name=weekendStart loop=7}
    <option value="{$smarty.section.weekendStart.index}" {if $smarty.section.weekendStart.index == $p.WEEKEND_START}{#selected#}{/if}>{weekday_name day=$smarty.section.weekendStart.index}</option>
   {/section}
   </select>
  </td></tr>

 <tr><td class="tooltip" title="__time-format-help@T__">
  __Time format__:</td><td>
  {print_radio variable='TIME_FORMAT' vars=$time_format_array}
 </td></tr>
 <tr><td class="tooltip" title="__work-hours-help@T__">
  __Work hours__:</td><td>
  <label for="pref_WORK_DAY_START_HOUR">__From@R1__</label>
  <select name="pref_WORK_DAY_START_HOUR" id="pref_WORK_DAY_START_HOUR">
   {section name=workstart loop=24}
    <option value="{$smarty.section.workstart.index}" {if $smarty.section.workstart.index == $p.WORK_DAY_START_HOUR}{#selected#}{/if}>{$smarty.section.workstart.index|display_time:1}
     </option>
   {/section}
  </select>
  <label for="pref_WORK_DAY_END_HOUR">__to@L1,R1__</label>
  <select name="pref_WORK_DAY_END_HOUR" id="pref_WORK_DAY_END_HOUR">
   {section name=workend loop=24}
    <option value="{$smarty.section.workend.index}" {if $smarty.section.workend.index == $p.WORK_DAY_END_HOUR}{#selected#}{/if}>{$smarty.section.workend.index|display_time:1}
     </option>
   {/section}
  </select>
 </td></tr>
</table>
</fieldset>
<fieldset>
 <legend>__Appearance__</legend>
<table width="100%">
<tr><td class="tooltip" title="__preferred-view-help@T__">__Preferred view__:</td><td>
<select name="pref_STARTVIEW">
{foreach from=$choices key=k item=v}
  <option value="{$k}" {if $p.STARTVIEW == $k}{#selected#}{/if}>{$v}</option>
{/foreach}
{foreach from=$views key=k item=v}
  <option value="{$v.url}" {if $p.STARTVIEW == $k}{#selected#}{/if}>{$v.cal_name}</option>
{/foreach}
</select>
</td></tr>

<tr><td class="tooltipselect" title="__fonts-help@T__">
 <label for="pref_font">__Fonts__:</label></td><td>
 <input type="text" size="40" name="pref_FONTS" id="pref_font" value="{$p.FONTS|htmlspecialchars}" />
</td></tr>

<tr><td class="tooltip" title="__display-sm_month-help@T__">
 __Display small months__:</td><td>
 {print_radio variable='DISPLAY_SM_MONTH'}
</td></tr>

<tr><td class="tooltip" title="__display-weekends-help@T__">
 __Display weekends__:</td><td>
 {print_radio variable='DISPLAY_WEEKENDS'}
</td></tr>
 <tr><td class="tooltip" title="__display-long-daynames-help@T__">
  __Display long day names__:</td><td>
  {print_radio variable='DISPLAY_LONG_DAYS'}
 </td></tr>
<tr><td class="tooltip" title="__display-minutes-help@T__">
 __Display 00 minutes always__:</td><td>
 {print_radio variable='DISPLAY_MINUTES'}
</td></tr>
<tr><td class="tooltip" title="__display-end-times-help@T__">
 __Display end times on calendars__:</td><td>
 {print_radio variable='DISPLAY_END_TIMES'}
</td></tr>
<tr><td class="tooltip" title="__display-alldays-help@T__">
  __Display all days in month view__:</td><td>
  {print_radio variable='DISPLAY_ALL_DAYS_IN_MONTH'}
 </td></tr> 
<tr><td class="tooltip" title="__display-week-number-help@T__">
 __Display week number__:</td><td>
 {print_radio variable='DISPLAY_WEEKNUMBER'}
</td></tr>
<tr><td class="tooltip" title="__display-tasks-help@T__">
 __Display small task list__:</td><td>
 {print_radio variable='DISPLAY_TASKS'}
</td></tr>
<tr><td class="tooltip" title="__display-tasks-in-grid-help@T__">
 __Display tasks in Calendars__:</td><td>
 {print_radio variable='DISPLAY_TASKS_IN_GRID'}
</td></tr>

<tr><td class="tooltip" title="__lunar-help@T__">
 __Display Lunar Phases in month view__:</td><td>
 {print_radio variable='DISPLAY_MOON_PHASES'}
</td></tr>

</table>
</fieldset>
<fieldset>
 <legend>__Events__</legend>
<table width="100%">

<tr><td class="tooltip" title="__display-unapproved-help@T__">
 __Display unapproved__:</td><td>
 {print_radio variable='DISPLAY_UNAPPROVED'}
</td></tr>

<tr><td class="tooltip" title="__timed-evt-len-help@T__">
 __Specify timed event length by__:</td><td>
 {print_radio variable='TIMED_EVT_LEN' vars=$timed_evt_len_array}
</td></tr>

{if $categories}
<tr><td>
 <label for="pref_cat">__Default Category__:</label></td><td>
 <select name="pref_CATEGORY_VIEW" id="pref_cat">
  {foreach from=$categories key=k item=v}
   <option value={$k}{if $p.CATEGORY_VIEW== $k}{#selected#}{/if}>{$v.cat_name}</option>
  {/foreach}
 </select>
</td></tr>
{/if}
<tr><td class="tooltip" title="__crossday-help@T__">
 __Disable Cross-Day Events__:</td><td>
 {print_radio variable='DISABLE_CROSSDAY_EVENTS'}
</td></tr>
<tr><td class="tooltip" title="__display-desc-print-day-help@T__">
 __Display description in printer day view__:</td><td>
 {print_radio variable='DISPLAY_DESC_PRINT_DAY'}
</td></tr>

<tr><td class="tooltip" title="__entry-interval-help@T__">
 __Entry interval__:</td><td>
 <select name="pref_ENTRY_SLOTS">
  <option value="24" {if $p.ENTRY_SLOTS == '24'}{#selected#}{/if}>1 __hour__</option>
  <option value="48" {if $p.ENTRY_SLOTS == '48'}{#selected#}{/if}>30 {$minutesStr}</option>
  <option value="72" {if $p.ENTRY_SLOTS == '72'}{#selected#}{/if}>20 {$minutesStr}</option>
  <option value="96" {if $p.ENTRY_SLOTS == '96'}{#selected#}{/if}>15 {$minutesStr}</option>
  <option value="144" {if $p.ENTRY_SLOTS == '144'}{#selected#}{/if}>10 {$minutesStr}</option>
  <option value="288" {if $p.ENTRY_SLOTS == '288'}{#selected#}{/if}>5 {$minutesStr}</option>
  <option value="1440" {if $p.ENTRY_SLOTS == '1440'}{#selected#}{/if}>1 __minute__</option>
 </select>
</td></tr>
<tr><td class="tooltip" title="__time-interval-help@T__">
 __Time interval__:</td><td>
 <select name="pref_TIME_SLOTS">
  <option value="24" {if $p.TIME_SLOTS == '24'}{#selected#}{/if}>1 __hour__</option>
  <option value="48" {if $p.TIME_SLOTS == '48'}{#selected#}{/if}>30 {$minutesStr}</option>
  <option value="72" {if $p.TIME_SLOTS == '72'}{#selected#}{/if}>20 {$minutesStr}</option>
  <option value="96" {if $p.TIME_SLOTS == '96'}{#selected#}{/if}>15 {$minutesStr}</option>
  <option value="144" {if $p.TIME_SLOTS == '144'}{#selected#}{/if}>10 {$minutesStr}</option>
  <option value="288" {if $p.TIME_SLOTS == '288'}{#selected#}{/if}>5 {$minutesStr}</option>
  <option value="1440" {if $p.TIME_SLOTS == '1440'}{#selected#}{/if}>1 __minute__</option>
 </select>
</td></tr>
</table>
</fieldset>
<fieldset>
 <legend>__Miscellaneous__</legend>
<table width="100%">

<tr><td class="tooltip" title="__auto-refresh-help@T__">
 __Auto-refresh calendars__:</td><td>
 {print_radio variable='AUTO_REFRESH'}
</td></tr>

<tr><td class="tooltip" title="__auto-refresh-time-help@T__">
 __Auto-refresh time@L4__:</td><td>
 <input type="text" name="pref_AUTO_REFRESH_TIME" size="4" value="{$p.AUTO_REFRESH_TIME}" />{$minutesStr}
</td></tr>
</table>
</fieldset>
</div>
<!-- END SETTINGS -->

{if $themes}
<div id="tabscontent_themes">
<table width="100%">
<tr><td class="tooltip"  title="__theme-reload-help@T__"colspan="3">
__Page may need to be reloaded for new Theme to take effect__</td></tr>
<tr><td  class="tooltipselect" title="__themes-help@T__">
 <label for="pref_THEME">__Themes__:</label></td><td colspan="2">
 <select name="pref_THEME" id="pref_THEME">
   <option {#disabled#}>__AVAILABLE THEMES__</option>
   <option  value="none" {#selected#}>__None__</option>
 {foreach from=$themes key=k item=v}
   <option value="{$k}">{$v}</option>
 {/foreach}
 </select>&nbsp;&nbsp;&nbsp;
 <input type="button" name="preview" value="__Preview__" onclick="return showPreview()" />
</td></tr>
</table>
</div>
<!-- END THEMES -->
{/if}

{if $send_email}
<div id="tabscontent_email">
<table width="100%">
<tr><td class="tooltip">
 __Email format preference__:</td><td>
 {print_radio variable='EMAIL_HTML' vars=$email_format_array}
</td></tr>

<tr><td class="tooltip">
 __Event reminders__:</td><td>
 {print_radio variable='EMAIL_REMINDER'}
</td></tr>

<tr><td class="tooltip">
 __Events added to my calendar__:</td><td>
 {print_radio variable='EMAIL_EVENT_ADED'}
</td></tr>

<tr><td class="tooltip">
 __Events updated on my calendar__:</td><td>
 {print_radio variable='EMAIL_EVENT_UPDTED'}
</td></tr>

<tr><td class="tooltip">
 __Events removed from my calendar__:</td><td>
 {print_radio variable='EMAIL_EVENT_DELTED'}
</td></tr>

<tr><td class="tooltip">
 __Event rejected by participant__:</td><td>
 {print_radio variable='EMAIL_EVENT_REJCTED'}
</td></tr>

<tr><td class="tooltip">
 __Event that I create__:</td><td>
 {print_radio variable='EMAIL_EVENT_CRETE'}
</td></tr>
</table>
</div>
<!-- END EMAIL -->
{/if}

<div id="tabscontent_boss">
<table width="100%">
{if $send_email}
<tr><td class="tooltip">__Email me event notification__:</td><td>
 {print_radio variable='EMAIL_ASSISTANT_EVENTS'}
</td></tr>
{/if}
<tr><td class="tooltip">__I want to approve events__:</td><td>
 {print_radio variable='APPROVE_ASSISTANT_EVENT'}
</td></tr>

<tr><td class="tooltip" title="__display_byproxy-help@T__">
__Display if created by Assistant__:</td><td>
  {print_radio variable='DISPLAY_CREATED_BYPROXY'}
</td></tr>
</table>
</div>
<!-- END BOSS -->

{if $publish_enabled || $rss_enabled}
<div id="tabscontent_subscribe">
<table width="100%">
<tr><td class="tooltipselect" title="__allow-view-subscriptions-help@T__">
__Allow remote viewing of__:</td><td>
  <select name="pref_USER_REMOTE_ACCESS">
   <option value="0" {if $publish_access == '0'}
{#selected#}{/if}>__Public__ __entries__</option>
   <option value="1" {if $publish_access == '1'}
{#selected#}{/if}>__Public__ &amp; __Confidential__ __entries__</option>
   <option value="2" {if $publish_access == '2'}
{#selected#}{/if}>__All__ __entries__</option>  
  </select>  
  </td></tr>
  {if $publish_enabled}
<tr><td class="tooltipselect" title="__allow-remote-subscriptions-help@T__">__Allow remote subscriptions__:</td><td>
  {print_radio variable='USER_PUBLISH_ENABLED'}
</td></tr>
   {if $server_url}
<tr><td class="tooltipselect" title="__remote-subscriptions-url-help@T__">__URL@L4__:</td>
  <td>
    {$server_url|htmlspecialchars}publish.php/{$user}.ics
    <br />
    {$server_url|htmlspecialchars}publish.php?user={$user}
</td></tr>
    {/if}

<tr><td class="tooltipselect" title="__allow-remote-publishing-help@T__">__Allow remote publishing__:</td>
  <td>
  {print_radio variable='USER_PUBLISH_RW_ENABLED'}
</td></tr>
   {if $server_url}
<tr><td class="tooltipselect" title="__remote-publishing-url-help@T__">__URL@L4__:</td>
  <td>
    {$server_url|htmlspecialchars}icalclient.php
</td></tr>
    {/if}

  {/if}

  {if $rss_enabled}
<tr><td class="tooltipselect" title="__rss-enabled-help@T__">__Enable RSS feed__:</td>
  <td>
  {print_radio variable='USER_RSS_ENABLED'}
</td></tr>
    {if $server_url}
<tr><td class="tooltipselect" title="__rss-feed-url-help@T__">__URL@L4__:</td>
  <td>
    {$server_url|htmlspecialchars}rss.php?user={$user}
  </td></tr>
    {/if}
  {/if}

<tr><td class="tooltipselect" title="__freebusy-enabled-help@T__">__Enable FreeBusy publishing__:</td>
  <td>
  {print_radio variable='FREEBUSY_ENABLED'}
</td></tr>
  {if $server_url}
<tr><td class="tooltipselect" title="__freebusy-url-help@T__">__URL@L4__:</td>
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
 <tr><td class="tooltip" title="__custom-script-help@T__">
  __Custom script/stylesheet__:</td><td>
  {print_radio variable='CUSTOM_SCRIPT'}&nbsp;&nbsp;
  <input type="button" value="__Edit__..." onclick="{$openS}" name="" />
 </td></tr>
{/if}
{if $custom_header}
 <tr><td class="tooltip" title="__custom-header-help@T__">
  __Custom header__:</td><td>
  {print_radio variable='CUSTOM_HEADER'}&nbsp;&nbsp;
  <input type="button" value="__Edit__..." onclick="{$openH}" name="" />
 </td></tr>
{/if}
{if $custom_trailer}
 <tr><td class="tooltip" title="__custom-trailer-help@T__">
  __Custom trailer__:</td><td>
  {print_radio variable='CUSTOM_TRAILER'}&nbsp;&nbsp;
  <input type="button" value="__Edit__..." onclick="{$openT}" name="" />
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
 <legend>__Color options__</legend>
<table width="100%">
<tr class="ignore"><td  colspan="4"></td><td rowspan="17" valign="middle">
<!-- BEGIN EXAMPLE MONTH -->
<table class="demotable"><tr>
<td width="1%" rowspan="3">&nbsp;</td>
<td style="text-align:center; color:{$p.H2COLOR}; font-weight:bold;">
{$demoMonthDate}</td>
<td width="1%" rowspan="3">&nbsp;</td></tr>
<tr><td bgcolor="{$p.BGCOLOR}">
{$display_month}
</td></tr>
<tr><td>&nbsp;</td></tr>
</table>
<!-- END EXAMPLE MONTH -->
</td></tr>
 <tr><td>
 {html_color_input name='BGCOLOR' title="__Document background@T__"}
</td>
</tr>
<tr><td>
 {html_color_input name='H2COLOR' title="__Document title@T__"}
</td></tr>
<tr><td>
 {html_color_input name='TEXTCOLOR' title="__Document text@T__"}
</td></tr>
<tr><td>
 {html_color_input name='MYEVENTS' title="__My event text@T__"}
</td></tr>
<tr><td>
 {html_color_input name='TABLEBG' title="__Table grid@T__"}
</td></tr>
<tr><td>
 {html_color_input name='THBG' title="__Table header background@T__"}
</td></tr>
<tr><td>
 {html_color_input name='THFG' title="__Table header text@T__"}
</td></tr>
<tr><td>
 {html_color_input name='CELLBG' title="__Table cell background@T__"}
</td></tr>
<tr><td>
 {html_color_input name='TODAYCELLBG' title="__Table cell background__ __for current day@T__"}
</td></tr>
<tr><td>
 {html_color_input name='HASEVENTSBG' title="__Table cell background__ __for days with events@T__"}
</td></tr>
<tr><td>
  {html_color_input name='WEEKENDBG' title="__Table cell background__ __for weekends@T__"}
</td></tr>
<tr><td>
  {html_color_input name='OTHERMONTHBG' title="__Table cell background__ __for other month@T__"}
</td></tr>
<tr><td>
  {html_color_input name='WEEKNUMBER' title="__Week number@T__"}
</td></tr>
<tr><td>
 {html_color_input name='POPUP_BG' title="__Event popup background@T__"}
</td></tr>
<tr><td>
  {html_color_input name='POPUP_FG' title="__Event popup text@T__"}
</td></tr>
</table>
</fieldset>
</div>
<!-- END COLORS -->
{/if}

<!-- END TABS -->
<br /><br />
<div>
<input type="submit" value="__Save__" name="" />
<br/><br/>
</div>
</form>
{include file="footer.tpl"}
