 {include file="header.tpl"}
<h2>{'System Settings'|translate} {generate_help_icon url='help_admin.php'}</h2>

<form action="admin.php" method="post" onsubmit="return valid_form(this);" name="prefform">
<input type="hidden" name="currenttab" id="currenttab" value="{$currenttab}" />
<input type="submit" value="{'Save'|translate}" name="" />
<br/><br/>

<!-- TABS -->
{print_tabs tabs=$tabs_ar}

<!-- TABS BODY -->
<div id="tabscontent">
 <!-- DETAILS -->
 <div id="tabscontent_settings">
<fieldset>
 <legend>{'System options'|translate}</legend>
 <table width="100%">
 <tr><td class="tooltip" title="{'app-name-help'|tooltip}">
  <label for="admin_APPLICATION_NAME">{'Application Name'|translate}:</label></td><td>
  <input type="text" size="40" name="admin_APPLICATION_NAME" id="admin_APPLICATION_NAME" value="{$s.APPLICATION_NAME|htmlspecialchars}" />&nbsp;&nbsp;
  {if $s.APPLICATION_NAME == 'Title'}
    {'Translated Name'|translate}({'Title'|translate})
	{/if}
 </td></tr>
 <tr><td class="tooltip" title="{'server-url-help'|tooltip}">
  <label for="admin_SERVER_URL">{'Server URL'|translate}:</label></td><td>
  <input type="text" size="40" name="admin_SERVER_URL" id="admin_SERVER_URL" value="{$s.SERVER_URL|htmlspecialchars}" />
 </td></tr>
 <tr><td class="tooltip" title="{'home-url-help'|tooltip}">
  <label for="admin_HOME_LINK">{'Home URL'|translate}:</label></td><td>
  <input type="text" size="40" name="admin_HOME_LINK" id="admin_HOME_LINK" value="{if $s.HOME_LINK}{$s.HOME_LINK|htmlspecialchars}{/if}" />
 </td></tr>
 <tr><td class="tooltipselect" title="{'language-help'|tooltip}">
  <label for="admin_LANGUAGE">{'Language'|translate}:</label></td><td>
  <select name="admin_LANGUAGE" id="admin_LANGUAGE">
	{foreach from=$languages key=k item=v}
    <option value="{$v}" {if $v == $s.LANGUAGE}{#selected#}{/if}>{$k}</option>
	{/foreach}
  </select>&nbsp;&nbsp;{'Your browser default language is'|translate} {$WC->browserLang()}
 </td></tr>
<tr><td><label>
 {'Allow user to use themes'|translate}:</label></td><td colspan="3">
 {print_radio variable='ALLOW_USER_THEMES'}
</td></tr> 
 <tr><td  class="tooltip" title="{'themes-help'|tooltip}">
 <label for="admin_THEME">{'Themes'|translate}:</label></td><td>
 <!--always use 'none' as default so we don't overwrite manual settings-->
 <select name="admin_THEME" id="admin_THEME">
   <option disabled="disabled">{'AVAILABLE THEMES'|translate}</option>
   <option  value="none" selected="selected">{'None'|translate}</option>
 {foreach from=$themes key=k item=v}
   <option value="{$v.1}">{$v.0}</option>
 {/foreach}
 </select>&nbsp;&nbsp;&nbsp;
 <input type="button" name="preview" value="{'Preview'|translate}" onclick="return showPreview()" />
 </td></tr> 
 </table>
</fieldset>

<fieldset>
 <legend>{'Site customization'|translate}</legend>
 <table width="100%">
 <tr><td class="tooltip" title="{'custom-script-help'|tooltip}">
  {'Custom script/stylesheet'|translate}:</td><td>
  {print_radio variable='CUSTOM_SCRIPT'}&nbsp;&nbsp;
  <input type="button" value="{'Edit'|translate}..." onclick="{$openS}" name="" />
 </td></tr>
 <tr><td class="tooltip" title="{'custom-header-help'|tooltip}">
  {'Custom header'|translate}:</td><td>
  {print_radio variable='CUSTOM_HEADER'}&nbsp;&nbsp;
  <input type="button" value="{'Edit'|translate}..." onclick="{$openH}" name="" />
 </td></tr>
 <tr><td class="tooltip" title="{'custom-trailer-help'|tooltip}">
  {'Custom trailer'|translate}:</td><td>
  {print_radio variable='CUSTOM_TRAILER'}&nbsp;&nbsp;
  <input type="button" value="{'Edit'|translate}..." onclick="{$openT}" name="" />
 </td></tr>

 <tr><td class="tooltip" title="{'enable-external-header-help'|tooltip}">
  {'Allow external file for header/script/trailer'|translate}:</td><td>
  {print_radio variable='ALLOW_EXTERNAL_HEADER'}
 </td></tr>

<tr><td class="tooltip" title="{'enable-user-header-help'|tooltip}"><label>
 {'Allow user to override header/trailer'|translate}:</label></td><td colspan="3">
 {print_radio variable='ALLOW_USER_HEADER'}
</td></tr>
 </table>
</fieldset>


<fieldset>
 <legend>{'Date and Time'|translate}</legend>
 <table width="100%">
  {if $can_set_timezone}
 <tr><td class="tooltipselect" title="{'tz-help'|tooltip}">
  <label for="admin_SERVER_TIMEZONE">{'Server Timezone Selection'|translate}:</label></td><td>
 {print_timezone_list prefix='admin_'}
</td></tr>
 {/if}
 <tr><td class="tooltip" title="{'display-general-use-gmt-help'|tooltip}">
  {'Display Common Use Date/Times as GMT'|translate}:</td><td>
  {print_radio variable='GENERAL_USE_GMT'}
 </td></tr>
 <tr><td class="tooltipselect" title="{'date-format-help'|tooltip}">
  {'Date format'|translate}:</td><td>
  <select name="admin_DATE_FORMAT">
  {foreach from=$datestyles key=k item=v}
    <option value="{$k}" {if $s.DATE_FORMAT == $v}{#selected#}{/if}>{$v}</option>
	{/foreach}
  </select>&nbsp;{$choices.month} {$choices.day} {$choices.year}<br />

  <select name="admin_DATE_FORMAT_MY">
  {foreach from=$datestyles_my key=k item=v}
    <option value="{$k}" {if $s.DATE_FORMAT == $v}{#selected#}{/if}>{$v}</option>
	{/foreach}
  </select>&nbsp;{$choices.month} {$choices.year}<br />

  <select name="admin_DATE_FORMAT_MD">
  {foreach from=$datestyles_md key=k item=v}
    <option value="{$k}" {if $s.DATE_FORMAT == $v}{#selected#}{/if}>{$v}</option>
	{/foreach}
  </select>&nbsp;{$choices.month} {$choices.day}<br />

  <select name="admin_DATE_FORMAT_TASK">
  {foreach from=$datestyles_task key=k item=v}
    <option value="{$k}" {if $s.DATE_FORMAT == $v}{#selected#}{/if}>{$v}</option>
{/foreach}
  </select>&nbsp;{'Small Task Date'|translate}
 </td></tr>

	<tr><td class="tooltip" title="{'display-week-starts-on'|tooltip}">
	 {'Week starts on'|translate}:</td><td>
	 <select name="admin_WEEK_START" id="admin_WEEK_START">
	 {section name=weekStart loop=7}
		<option value="{$smarty.section.weekStart.index}" {if $smarty.section.weekStart.index == $s.WEEK_START}{#selected#}{/if}>{weekday_name day=$smarty.section.weekStart.index}</option>
	 {/section}
	 </select>
	</td></tr>
	
	<tr><td class="tooltip" title="{'display-weekend-starts-on'|tooltip}">
	 {'Weekend starts on'|translate}:</td><td>
	 <select name="admin_WEEKEND_START" id="admin_WEEKEND_START">
	 {section name=weekendStart loop=7}
		<option value="{$smarty.section.weekStart.index}" {if $smarty.section.weekendStart.index == $s.WEEK_START}{#selected#}{/if}>{weekday_name day=$smarty.section.weekendStart.index}</option>
	 {/section}
	 </select>
	</td></tr>

 <tr><td class="tooltip" title="{'time-format-help'|tooltip}">
  {'Time format'|translate}:</td><td>
  {print_radio variable='TIME_FORMAT' vars=$time_format_array}
 </td></tr>
 <tr><td class="tooltip" title="{'timed-evt-len-help'|tooltip}">
  {'Specify timed event length by'|translate}:</td><td>
  {print_radio variable='TIMED_EVT_LEN' vars=$timed_evt_len_array}
 </td></tr>
 <tr><td class="tooltip" title="{'work-hours-help'|tooltip}">
  {'Work hours'|translate}:</td><td>
  <label for="admin_WORK_DAY_START_HOUR">{'From'|translate}&nbsp;</label>
  <select name="admin_WORK_DAY_START_HOUR" id="admin_WORK_DAY_START_HOUR">
	 {section name=workstart loop=24}
    <option value="{$smarty.section.workstart.index}" {if $smarty.section.workstart.index == $s.WORK_DAY_START_HOUR}{#selected#}{/if}>{$smarty.section.workstart.index*3600|display_time:1}
		 </option>
	 {/section}
  </select>&nbsp;
  <label for="admin_WORK_DAY_END_HOUR">{'to'|translate}&nbsp;</label>
  <select name="admin_WORK_DAY_END_HOUR" id="admin_WORK_DAY_END_HOUR">
	 {section name=workend loop=24}
    <option value="{$smarty.section.workend.index}" {if $smarty.section.workend.index == $s.WORK_DAY_END_HOUR}{#selected#}{/if}>{$smarty.section.workend.index*3600|display_time:1}
		 </option>
	 {/section}
  </select>
 </td></tr>
</table> 
</fieldset>
<fieldset>
 <legend>{'Appearance'|translate}</legend>
 <table width="100%">
 <tr><td class="tooltip" title="{'preferred-view-help'|tooltip}">
<label for="admin_STARTVIEW">{'Preferred view'|translate}:</label></td><td>
<select name="admin_STARTVIEW" id="admin_STARTVIEW">
{foreach from=$choices key=k item=v}
  <option value="{$k}" {if $s.STARTVIEW == $k}{#selected#}{/if}>{$v}</option>
{/foreach}
{foreach from=$views key=k item=v}
  <option value="{$v.url}" {if $s.STARTVIEW == $k}{#selected#}{/if}>{$v.cal_name}</option>
{/foreach}
</select>
 </td></tr>
 <tr><td><label>
 {'Date Selectors position'|translate}:</label></td><td colspan="3">
 {print_radio variable='MENU_DATE_TOP' vars=$top_bottom_array}
 </td></tr>  
 <tr><td class="tooltip" title="{'fonts-help'|tooltip}">
  <label for="admin_FONTS">{'Fonts'|translate}:</label></td><td>
  <input type="text" size="40" name="admin_FONTS" id="admin_FONTS" value="{$s.FONTS|htmlspecialchars}" />
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
 <tr><td class="tooltip" title="{'display-alldays-help'|tooltip}">
  {'Display all days in month view'|translate}:</td><td>
  {print_radio variable='DISPLAY_ALL_DAYS_IN_MONTH'}
 </td></tr>
  <tr><td class="tooltip" title="{'display-week-number-help'|tooltip}">
  {'Display week number'|translate}:</td><td>
  {print_radio variable='DISPLAY_WEEKNUMBER'}
 </td></tr>

 <tr><td class="tooltip" title="{'display-desc-print-day-help'|tooltip}">
  {'Display description in printer day view'|translate}:</td><td>
  {print_radio variable='DISPLAY_DESC_PRINT_DAY'}
 </td></tr>

 <tr><td class="tooltip" title="{'yearly-shows-events-help'|tooltip}">
  {'Display days with events in bold in month and year views'|translate}:</td><td>
  {print_radio variable='BOLD_DAYS_IN_YEAR'}
 </td></tr>

<tr><td class="tooltip" title="{'display-minutes-help'|tooltip}">
 {'Display 00 minutes always'|translate}:</td><td>
 {print_radio variable='DISPLAY_MINUTES'}
</td></tr>

<tr><td class="tooltip" title="{'display-end-times-help'|tooltip}">
 {'Display end times on calendars'|translate}:</td><td>
 {print_radio variable='DISPLAY_END_TIMES'}
</td></tr>

  <tr><td class="tooltip" title="{'allow-view-add-help'|tooltip}">
  {'Include add event link in views'|translate}:</td><td>
  {print_radio variable='ADD_LINK_IN_VIEWS'}
 </td></tr>

<tr><td class="tooltip" title="{'lunar-help'|tooltip}">
  {'Display Lunar Phases in month view'|translate}:</td><td>
  {print_radio variable='DISPLAY_MOON_PHASES'}
 </td></tr>
</table> 
</fieldset>
<fieldset>
 <legend>{'Restrictions'|translate}</legend>
 <table width="100%">
 <tr><td class="tooltip" title="{'allow-view-other-help'|tooltip}">
  {'Allow viewing other user&#39;s calendars'|translate}:</td><td>
  {print_radio variable='ALLOW_VIEW_OTHER'}
 </td></tr>
 <tr><td class="tooltip" title="{'require-approvals-help'|tooltip}">
  {'Require event approvals'|translate}:</td><td>
  {print_radio variable='REQUIRE_APPROVALS'}
 </td></tr>
 <tr><td class="tooltip" title="{'display-unapproved-help'|tooltip}">
  &nbsp;&nbsp;&nbsp;&nbsp;{'Display unapproved'|translate}:</td><td>
  {print_radio variable='DISPLAY_UNAPPROVED'}
 </td></tr>
 <tr><td class="tooltip" title="{'conflict-check-help'|tooltip}">
  {'Check for event conflicts'|translate}:</td><td>
    {print_radio variable='CHECK_CONFLICTS'}
 </td></tr>
 <tr><td class="tooltip" title="{'conflict-months-help'|tooltip}">
  &nbsp;&nbsp;&nbsp;&nbsp;{'Conflict checking months'|translate}:</td><td>
  <input type="text" size="3" name="admin_CONFLICT_REPEAT_MONTHS" value="{$s.CONFLICT_REPEAT_MONTHS|htmlspecialchars}" />
 </td></tr>
 <tr><td class="tooltip" title="{'conflict-check-override-help'|tooltip}">
  &nbsp;&nbsp;&nbsp;&nbsp;{'Allow users to override conflicts'|translate}:</td><td>
  {print_radio variable='ALLOW_CONFLICT_OVERRIDE'}
 </td></tr>
 <tr><td class="tooltip" title="{'limit-appts-help'|tooltip}">
  {'Limit number of timed events per day'|translate}:</td><td>
  {print_radio variable='LIMIT_APPTS'}
 </td></tr>
 <tr><td class="tooltip" title="{'limit-appts-number-help'|tooltip}">
  &nbsp;&nbsp;&nbsp;&nbsp;{'Maximum timed events per day'|translate}:</td><td>
  <input type="text" size="3" name="admin_LIMIT_APPTS_NUMBER" value="{$s.LIMIT_APPTS_NUMBER|htmlspecialchars}" />
 </td></tr>
 <tr><td class="tooltip" title="{'crossday-help'|tooltip}">
  {'Disable Cross-Day Events'|translate}:</td><td>
  {print_radio variable='DISABLE_CROSSDAY_EVENTS'}
 </td></tr>
 </table>
</fieldset>
<fieldset>
 <legend>{'Events'|translate}</legend>
 <table width="100%">
  <tr><td class="tooltip" title="{'disable-location-field-help'|tooltip}">
  {'Disable Location field'|translate}:</td><td>
  {print_radio variable='DISABLE_LOCATION_FIELD'}
 </td></tr>
  <tr><td class="tooltip" title="{'disable-url-field-help'|tooltip}">
  {'Disable URL field'|translate}:</td><td>
  {print_radio variable='DISABLE_URL_FIELD'}
 </td></tr>
 <tr><td class="tooltip" title="{'disable-priority-field-help'|tooltip}">
  {'Disable Priority field'|translate}:</td><td>
  {print_radio variable='DISABLE_PRIORITY_FIELD'}
 </td></tr>
 <tr><td class="tooltip" title="{'disable-access-field-help'|tooltip}">
  {'Disable Access field'|translate}:</td><td>
  {print_radio variable='DISABLE_ACCESS_FIELD'}
 </td></tr>
 <tr><td class="tooltip" title="{'disable-participants-field-help'|tooltip}">
  {'Disable Participants field'|translate}:</td><td>
  {print_radio variable='DISABLE_PARTICIPANTS_FIELD'}
 </td></tr>
 <tr><td class="tooltip" title="{'disable-repeating-field-help'|tooltip}">
  {'Disable Repeating field'|translate}:</td><td>
  {print_radio variable='DISABLE_REPEATING_FIELD'}
 </td></tr>
 <tr><td class="tooltip" title="{'allow-html-description-help'|tooltip}">
  {'Allow HTML in Description'|translate}:</td><td>
  {print_radio variable='ALLOW_HTML_DESCRIPTION'}
 </td></tr>
</table>
</fieldset>
<fieldset>
<legend>{'Popups'|translate}</legend>
<table width="100%">
 <tr><td class="tooltip" title="{'disable-popups-help'|tooltip}">
  {'Disable Pop-Ups'|translate}:</td><td>
  {print_radio variable='DISABLE_POPUPS' onclick='popup_handler()'}
 </td></tr>
 <tbody id="popup">
 <tr><td class="tooltip" title="{'popup-includes-siteextras-help'|tooltip}">
  {'Display Site Extras in popup'|translate}:</td><td>
  {print_radio variable='SITE_EXTRAS_IN_POPUP'}
 </td></tr>
 <tr><td class="tooltip" title="{'popup-includes-participants-help'|tooltip}">
  {'Display Participants in popup'|translate}:</td><td>
  {print_radio variable='PARTICIPANTS_IN_POPUP'}
 </td></tr>
 </tbody>
</table>
</fieldset>
<fieldset>
 <legend>{'Miscellaneous'|translate}</legend>
 <table width="100%">
 <tr><td class="tooltip" title="{'remember-last-login-help'|tooltip}">
  {'Remember last login'|translate}:</td><td>
  {print_radio variable='REMEMBER_LAST_LOGIN'}
 </td></tr>
<tr><td class="tooltip" title="{'summary_length-help'|tooltip}">
  {'Brief Description Length'|translate}:</td><td>
  <input type="text" size="3" name="admin_SUMMARY_LENGTH" value="{$s.SUMMARY_LENGTH}" />
 </td></tr>
 <tr><td class="tooltip" title="{'user_sort-help'|tooltip}">
  <label for="admin_USER_SORT_ORDER">{'User Sort Order'|translate}:</label></td><td>
  <select name="admin_USER_SORT_ORDER" id="admin_USER_SORT_ORDER">
   <option value="cal_lastname, cal_firstname"
	  {if $s.USER_SORT_ORDER == "cal_lastname, cal_firstname"}
		 {#selected#}
		{/if}>{'Lastname, Firstname'|translate}</option>
   <option value="cal_firstname, cal_lastname" 
	 {if $s.USER_SORT_ORDER == "cal_firstname, cal_lastname" } 
    {#selected#}
		{/if}>{'Firstname, Lastname'|translate}</option>
  </select>
 </td></tr>
</table>
</fieldset>
</div>
<!-- END SETTINGS -->

<!-- BEGIN GROUPS -->
<div id="tabscontent_groups">
<table width="100%">
 <tr><td class="tooltip" title="{'groups-enabled-help'|tooltip}">
  {'Groups enabled'|translate}:</td><td>
  {print_radio variable='GROUPS_ENABLED'}
 </td></tr>
 <tr><td class="tooltip" title="{'user-sees-his-group-help'|tooltip}">
  {'User sees only his groups'|translate}:</td><td>
  {print_radio variable='USER_SEES_ONLY_HIS_GROUPS'}
 </td></tr>
</table>
</div>

<!-- BEGIN NONUSER -->
<div id="tabscontent_nonuser">
<table width="100%">
 <tr><td class="tooltip" title="{'nonuser-enabled-help'|tooltip}">
  {'Nonuser enabled'|translate}:</td><td>
  {print_radio variable='NONUSER_ENABLED'}
 </td></tr>
 <tr><td class="tooltip" title="{'nonuser-list-help'|tooltip}">
  {'Nonuser list'|translate}:</td><td>
  {print_radio variable='NONUSER_AT_TOP' vars=$top_bottom_array}
</td></tr>
</table>
</div>

<!-- BEGIN REPORTS -->
<div id="tabscontent_other">
<table width="100%">
<tr><td class="tooltip" title="{'reports-enabled-help'|tooltip}">
 {'Reports enabled'|translate}:</td><td>
 {print_radio variable='REPORTS_ENABLED'}
</td></tr>


<!-- BEGIN PUBLISHING -->

<tr><td class="tooltip" title="{'subscriptions-enabled-help'|tooltip}">
 {'Allow remote subscriptions'|translate}:</td><td>
 {print_radio variable='PUBLISH_ENABLED'}
</td></tr>
{if $allow_url_fopen}
<tr><td class="tooltip" title="{'remotes-enabled-help'|tooltip}">
 {'Allow remote calendars'|translate}:</td><td>
 {print_radio variable='REMOTES_ENABLED'}
</td></tr>
{/if}
<tr><td class="tooltip" title="{'rss-enabled-help'|tooltip}">
 {'Enable RSS feed'|translate}:</td><td>
 {print_radio variable='RSS_ENABLED'}
</td></tr>


<!-- BEGIN CATEGORIES -->

 <tr><td class="tooltip" title="{'categories-enabled-help'|tooltip}">
  {'Categories enabled'|translate}:</td><td>
  {print_radio variable='CATEGORIES_ENABLED'}
 </td></tr>

 <tr><td class="tooltip" title="{'icon_upload-enabled-help'|tooltip}">
  {'Category Icon Upload enabled'|translate}:</td><td>
  {print_radio variable='ENABLE_ICON_UPLOADS'}&nbsp;{$icons_dir_notice}
 </td></tr>
 
<!-- Display Task Preferences -->
 <tr><td class="tooltip" title="{'display-tasks-help'|tooltip}">
  {'Display small task list'|translate}:</td><td>
  {print_radio variable='DISPLAY_TASKS'}
 </td></tr>
 <tr><td class="tooltip" title="{'display-tasks-in-grid-help'|tooltip}">
  {'Display tasks in Calendars'|translate}:</td><td>
  {print_radio variable='DISPLAY_TASKS_IN_GRID'}
 </td></tr>

<!-- BEGIN EXT PARTICIPANTS -->

 <tr><td class="tooltip" title="{'allow-external-users-help'|tooltip}">
  {'Allow external users'|translate}:</td><td>
  {print_radio variable='ALLOW_EXTERNAL_USERS' onclick='eu_handler()'}
 </td></tr>
 <tbody id="eu">
 <tr><td class="tooltip" title="{'external-can-receive-notification-help'|tooltip}">
  &nbsp;&nbsp;&nbsp;&nbsp;{'External users can receive email notifications'|translate}:</td><td>
  {print_radio variable='EXTERNAL_NOTIFICATIONS'}
 </td></tr>
 <tr><td class="tooltip" title="{'external-can-receive-reminder-help'|tooltip}">
  &nbsp;&nbsp;&nbsp;&nbsp;{'External users can receive email reminders'|translate}:</td><td>
  {print_radio variable='EXTERNAL_REMINDERS'}
 </td></tr>
 </tbody>
 <!-- BEGIN SELF REGISTRATION -->

 <tr><td class="tooltip" title="{'allow-self-registration-help'|tooltip}">
  {'Allow self-registration'|translate}:</td><td>
  {print_radio variable='ALLOW_SELF_REGISTRATION' onclick='sr_handler()'}
 </td></tr>
 <tbody id="sr">
 <tr><td class="tooltip" title="{'use-blacklist-help'|tooltip}">
  &nbsp;&nbsp;&nbsp;&nbsp;{'Restrict self-registration to blacklist'|translate}:</td><td>
  {print_radio variable='SELF_REGISTRATION_BLACKLIST' onclick='sr_handler()'}
 </td></tr>
 <tr><td class="tooltip" title="{'allow-self-registration-full-help'|tooltip}">
  &nbsp;&nbsp;&nbsp;&nbsp;{'Use self-registration email notifications'|translate}:</td><td>
  {print_radio variable='SELF_REGISTRATION_FULL' onclick='sr_handler()'}
 </td></tr>
 </tbody>
<!-- TODO add account aging feature -->


 <!-- BEGIN ATTACHMENTS/COMMENTS -->

 <tr><td class="tooltip" title="{'allow-attachment-help'|tooltip}">
  {'Allow file attachments to events'|translate}:</td><td>
  {print_radio variable='ALLOW_ATTACH' onclick='attach_handler()'}
  <span id="attach">
  <br/><strong>{'Note'|translate}:</strong>
  {'Admin and owner can always add attachments if enabled'|translate}><br/>
   {print_checkbox name='ALLOW_ATTACH_PART' label='Participant'|translate}
   {print_checkbox name='ALLOW_ATTACH_ANY' label='Anyone'|translate}
  </span>
 </td></tr>

 <tr><td class="tooltip" title="{'allow-comments-help'|tooltip}">
  {'Allow comments to events'|translate}:</td><td>
  {print_radio variable='ALLOW_COMMENTS' onclick='comment_handler()'}
  <br/>
  <span id="comment">
  <br/><strong>Note:</strong>
  {'Admin and owner can always add comments if enabled'|translate}<br/>
  {print_checkbox name='ALLOW_COMMENTS_PART' label='Participant'|translate}
  {print_checkbox name='ALLOW_COMMENTS_ANY' label='Anyone'|translate}
  </span>
 </td></tr>

 <!-- END ATTACHMENTS/COMMENTS -->

</table>
</div>

<!-- BEGIN EMAIL -->
<div id="tabscontent_email">
<table width="100%">
<tr><td class="tooltip" title="{'email-enabled-help'|tooltip}">
 {'Email enabled'|translate}:</td><td>
 {print_radio variable='SEND_EMAIL' onclick='email_handler()'}
</td></tr>
<tbody id="em">
<tr><td class="tooltip" title="{'email-default-sender'|tooltip}">
 &nbsp;&nbsp;&nbsp;&nbsp;{'Default sender address'|translate}:</td><td>
 <input type="text" size="30" name="admin_EMAIL_FALLBACK_FROM" value="{$s.EMAIL_FALLBACK_FROM}" />
</td></tr>

<tr><td class="tooltip" title="{'email-mailer'|tooltip}">
{'Email Mailer'|translate}:</td><td>
 <select name="admin_EMAIL_MAILER"  onchange="email_handler()">
   <option value="smtp" {if $s.EMAIL_MAILER == 'smtp'}
	   {#selected#}{/if}>SMTP</option>
   <option value="mail" {if $s.EMAIL_MAILER == 'mail'}
	   {#selected#}{/if}>PHP mail</option>
   <option value="sendmail" {if $s.EMAIL_MAILER == 'sendmail'}
	   {#selected#}{/if}>sendmail</option>
  </select>   
</td></tr>
<tbody id="em_smtp">
<tr><td class="tooltip" title="{'email-smtp-host'|tooltip}">
{'SMTP Host name(s)'|translate}:</td><td>
 <input type="text" size="50" name="admin_SMTP_HOST" value="{$s.SMTP_HOST}" />
</td></tr>
<tr><td class="tooltip" title="{'email-smtp-port'|tooltip}">
{'SMTP Port Number'|translate}:</td><td>
 <input type="text" size="4" name="admin_SMTP_PORT" value="{$s.SMTP_PORT}" />
</td></tr>

<tr><td class="tooltip" title="{'email-smtp-auth'|tooltip}">
 {'SMTP Authentication'|translate}:</td><td>
 {print_radio variable='SMTP_AUTH' onclick='email_handler()'}
</td></tr>
<tbody id="em_auth">
<tr><td class="tooltip" title="{'email-smtp-username'|tooltip}">
 &nbsp;&nbsp;&nbsp;&nbsp;{'SMTP Username'|translate}:</td><td>
 <input type="text" size="30" name="admin_SMTP_USERNAME" value="{$s.SMTP_USERNAME}" />
</td></tr>

<tr><td class="tooltip" title="{'email-smtp-password'|tooltip}">
 &nbsp;&nbsp;&nbsp;&nbsp;{'SMTP Password'|translate}:</td><td>
 <input type="text" size="30" name="admin_SMTP_PASSWORD" value="{$s.SMTP_PASSWORD}" />
</td></tr>
</tbody>
</tbody>
<tr><td colspan="2" class="bold">
 {'Default user settings'|translate}:
</td></tr>
<tr><td class="tooltip" title="{'email-event-reminders-help'|tooltip}">
 &nbsp;&nbsp;&nbsp;&nbsp;{'Event reminders'|translate}:</td><td>
 {print_radio variable='EMAIL_REMINDER'}
</td></tr>
<tr><td class="tooltip" title="{'email-event-added'|tooltip}">
 &nbsp;&nbsp;&nbsp;&nbsp;{'Events added to my calendar'|translate}:</td><td>
 {print_radio variable='EMAIL_EVENT_ADED'}
</td></tr>
<tr><td class="tooltip" title="{'email-event-updated'|tooltip}">
 &nbsp;&nbsp;&nbsp;&nbsp;{'Events updated on my calendar'|translate}:</td><td>
 {print_radio variable='EMAIL_EVENT_UPDTED'}
</td></tr>
<tr><td class="tooltip" title="{'email-event-deleted'|tooltip}">
 &nbsp;&nbsp;&nbsp;&nbsp;{'Events removed from my calendar'|translate}:</td><td>
 {print_radio variable='EMAIL_EVENT_DELTED'}
</td></tr>
<tr><td class="tooltip" title="{'email-event-rejected'|tooltip}">
 &nbsp;&nbsp;&nbsp;&nbsp;{'Event rejected by participant'|translate}:</td><td>
 {print_radio variable='EMAIL_EVENT_REJCTED'}
</td></tr>
<tr><td class="tooltip" title="{'email-event-create'|tooltip}">
 &nbsp;&nbsp;&nbsp;&nbsp;{'Event that I create'|translate}:</td><td>
 {print_radio variable='EMAIL_EVENT_CRETE'}
</td></tr>
</tbody>
</table>
</div>

<!-- BEGIN COLORS -->
<div id="tabscontent_colors">
<fieldset>
 <legend>{'Color options'|translate}</legend>
<table width="100%">
<tr class="ignore" ><td  colspan="4"></td><td rowspan="17" valign="middle">
<!-- BEGIN EXAMPLE MONTH -->
<table class="demotable"><tr class="ignore">
<td width="1%" rowspan="3">&nbsp;</td>
<td style="text-align:center; color:{$s.H2COLOR}; font-weight:bold;">
{$WC->thisdate|date_to_str:DATE_FORMAT_MY:false:false}</td>
<td width="1%" rowspan="3">&nbsp;</td></tr>
<tr class="ignore"><td bgcolor="{$s.BGCOLOR}">
{display_month}
</td></tr>
<tr><td>&nbsp;</td></tr>
</table>
<!-- END EXAMPLE MONTH -->
</td></tr>
<tr><td width="30%"><label>
 {'Allow user to customize colors'|translate}:</label></td><td colspan="3">
 {print_radio variable='ALLOW_COLOR_CUSTOMIZATION'}
</td></tr>
<tr><td class="tooltip" title="{'gradient-colors'|tooltip}"><label>
 {'Enable gradient images for background colors'|translate}:</label></td>
 <td colspan="3">
{if $enable_gradients}
  {print_radio variable='ENABLE_GRADIENTS'}
{else}
   {'Not available'|translate}
{/if}
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
<fieldset>
 <legend>{'Background Image options'|translate}</legend>
<table width="100%">
 <tr><td class="tooltip" title="{'bgimage-help'|tooltip}">
  <label for="admin_BGIMAGE">{'Background Image'|translate}:</label></td><td>
  <input type="text" size="75" name="admin_BGIMAGE" id="admin_BGIMAGE" value="{$s.BGIMAGE|htmlspecialchars}" />
 </td></tr>
 <tr><td class="tooltip" title="{'bgrepeat-help'|tooltip}">
  <label for="admin_BGREPEAT">{'Background Repeat'|translate}:</label></td><td>
  <input type="text" size="30" name="admin_BGREPEAT" id="admin_BGREPEAT" value="{$s.BGREPEAT}" />
 </td></tr>
</table>
</fieldset>
</div>
</div>

<br /><br />
<div>
 <input type="submit" value="{'Save'|translate}" name="" />
</div>
</form>
{include file="footer.tpl"}


