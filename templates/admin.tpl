 {include file="header.tpl"}
<h2>__System Settings__ {generate_help_icon url='help_admin.php'}</h2>

<form action="admin.php" method="post" onsubmit="return valid_form(this);" name="prefform">
<input type="hidden" name="currenttab" id="currenttab" value="{$currenttab}" />
<input type="submit" value="__Save__" name="" />
<br/><br/>

<!-- TABS -->
{print_tabs tabs=$tabs_ar}

<!-- TABS BODY -->
<div id="tabscontent">
 <!-- DETAILS -->
 <div id="tabscontent_settings">
<fieldset>
 <legend>__System options__</legend>
 <table width="100%">
 <tr><td class="tooltip" title="__app-name-help@T__">
  <label for="admin_APPLICATION_NAME">__Application Name__:</label></td><td>
  <input type="text" size="40" name="admin_APPLICATION_NAME" id="admin_APPLICATION_NAME" value="{$s.APPLICATION_NAME|htmlspecialchars}" />
  {if $s.APPLICATION_NAME == 'Title'}
    __Translated Name@L2,R1__(__Title__)
  {/if}
 </td></tr>
 <tr><td class="tooltip" title="__server-url-help@T__">
  <label for="admin_SERVER_URL">__Server URL__:</label></td><td>
  <input type="text" size="40" name="admin_SERVER_URL" id="admin_SERVER_URL" value="{$s.SERVER_URL|htmlspecialchars}" />
 </td></tr>
 <tr><td class="tooltip" title="__home-url-help@T__">
  <label for="admin_HOME_LINK">__Home URL__:</label></td><td>
  <input type="text" size="40" name="admin_HOME_LINK" id="admin_HOME_LINK" value="{if $s.HOME_LINK}{$s.HOME_LINK|htmlspecialchars}{/if}" />
 </td></tr>
 <tr><td class="tooltipselect" title="__language-help@T__">
  <label for="admin_LANGUAGE">__Language__:</label></td><td>
  <select name="admin_LANGUAGE" id="admin_LANGUAGE">
  {foreach from=$languages key=k item=v}
    <option value="{$v}" {if $v == $s.LANGUAGE}{#selected#}{/if}>{$k}</option>
  {/foreach}
  </select>__Your browser default language is@L2,R1__{$WC->browserLang()}
 </td></tr>
<tr><td><label>
 __Allow user to use themes__:</label></td><td colspan="3">
 {print_checkbox name='_ALLOW_USER_THEMES'}
</td></tr> 
 <tr><td  class="tooltip" title="__themes-help@T__">
 <label for="admin_THEME">__Themes__:</label></td><td>
 <!--always use 'none' as default so we don't overwrite manual settings-->
 <select name="admin_THEME" id="admin_THEME">
   <option {#disabled#}>__AVAILABLE THEMES__</option>
   <option  value="none" {#selected#}>__None__</option>
 {foreach from=$themes key=k item=v}
   <option value="{$k}">{$v}</option>
 {/foreach}
 </select>&nbsp;&nbsp;&nbsp;
 <input type="button" name="preview" value="__Preview__" onclick="return showPreview()" />
 </td></tr> 
 </table>
</fieldset>

<fieldset>
 <legend>__Site customization__</legend>
 <table width="100%">
 <tr><td class="tooltip" title="__custom-script-help@T__">
  __Custom script/stylesheet__:</td><td>
  {print_checkbox name='CUSTOM_SCRIPT'}&nbsp;&nbsp;
  <input type="button" value="__Edit__..." onclick="{$openS}" name="" />
 </td></tr>
 <tr><td class="tooltip" title="__custom-header-help@T__">
  __Custom header__:</td><td>
  {print_checkbox name='CUSTOM_HEADER'}&nbsp;&nbsp;
  <input type="button" value="__Edit__..." onclick="{$openH}" name="" />
 </td></tr>
 <tr><td class="tooltip" title="__custom-trailer-help@T__">
  __Custom trailer__:</td><td>
  {print_checkbox name='CUSTOM_TRAILER'}&nbsp;&nbsp;
  <input type="button" value="__Edit__..." onclick="{$openT}" name="" />
 </td></tr>

 <tr><td class="tooltip" title="__enable-external-header-help@T__">
  __Allow external file for header/script/trailer__:</td><td>
  {print_checkbox name='_ALLOW_EXTERNAL_HEADER'}
 </td></tr>

<tr><td class="tooltip" title="__enable-user-header-help__"><label>
 __Allow user to override header/trailer__:</label></td><td colspan="3">
 {print_checkbox name='_ALLOW_USER_HEADER'}
</td></tr>
 </table>
</fieldset>


<fieldset>
 <legend>__Date and Time__</legend>
 <table width="100%">
  {if $can_set_timezone}
 <tr><td class="tooltipselect" title="__tz-help@T__">
  <label>__Server Timezone Selection__:</label></td><td>
 {print_timezone_list prefix='admin_'}
</td></tr>
 {/if}
 <tr><td class="tooltip" title="__display-general-use-gmt-help@T__">
  __Display Common Use Date/Times as GMT__:</td><td>
  {print_checkbox name='GENERAL_USE_GMT'}
 </td></tr>
 <tr><td class="tooltipselect" title="__date-format-help@T__">
  __Date format__:</td><td>
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
  </select>__Small Task Date@L1__
 </td></tr>

  <tr><td class="tooltip" title="__display-week-starts-on@T__">
   __Week starts on__:</td><td>
   <select name="admin_WEEK_START" id="admin_WEEK_START">
   {section name=weekStart loop=7}
    <option value="{$smarty.section.weekStart.index}" {if $smarty.section.weekStart.index == $s.WEEK_START}{#selected#}{/if}>{weekday_name day=$smarty.section.weekStart.index}</option>
   {/section}
   </select>
  </td></tr>
  
  <tr><td class="tooltip" title="__display-weekend-starts-on@T__">
   __Weekend starts on__:</td><td>
   <select name="admin_WEEKEND_START" id="admin_WEEKEND_START">
   {section name=weekendStart loop=7}
    <option value="{$smarty.section.weekendStart.index}" {if $smarty.section.weekendStart.index == $s.WEEK_START}{#selected#}{/if}>{weekday_name day=$smarty.section.weekendStart.index}</option>
   {/section}
   </select>
  </td></tr>

 <tr><td class="tooltip" title="__time-format-help@T__">
  __Time format__:</td><td>
  {print_radio variable='TIME_FORMAT' vars=$time_format_array}
 </td></tr>
 <tr><td class="tooltip" title="__timed-evt-len-help@T__">
  __Specify timed event length by__:</td><td>
  {print_radio variable='TIMED_EVT_LEN' vars=$timed_evt_len_array}
 </td></tr>
 <tr><td class="tooltip" title="__work-hours-help@T__">
  __Work hours__:</td><td>
  <label for="admin_WORK_DAY_START_HOUR">__From@R1__</label>
  <select name="admin_WORK_DAY_START_HOUR" id="admin_WORK_DAY_START_HOUR">
   {section name=workstart loop=24}
    <option value="{$smarty.section.workstart.index}" {if $smarty.section.workstart.index == $s.WORK_DAY_START_HOUR}{#selected#}{/if}>{$smarty.section.workstart.index|display_time:1}
     </option>
   {/section}
  </select>
  <label for="admin_WORK_DAY_END_HOUR">__to@L1,R1__</label>
  <select name="admin_WORK_DAY_END_HOUR" id="admin_WORK_DAY_END_HOUR">
   {section name=workend loop=24}
    <option value="{$smarty.section.workend.index}" {if $smarty.section.workend.index == $s.WORK_DAY_END_HOUR}{#selected#}{/if}>{$smarty.section.workend.index|display_time:1}
     </option>
   {/section}
  </select>
 </td></tr>
</table> 
</fieldset>
<fieldset>
 <legend>__Appearance__</legend>
 <table width="100%">
 <tr><td class="tooltip" title="__preferred-view-help@T__">
<label for="admin_STARTVIEW">__Preferred view__:</label></td><td>
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
 __Date Selectors position__:</label></td><td colspan="3">
 {print_radio variable='MENU_DATE_TOP' vars=$top_bottom_array}
 </td></tr>  
 <tr><td class="tooltip" title="__fonts-help@T__">
  <label for="admin_FONTS">__Fonts__:</label></td><td>
  <input type="text" size="40" name="admin_FONTS" id="admin_FONTS" value="{$s.FONTS|htmlspecialchars}" />
 </td></tr>
 <tr><td class="tooltip" title="__display-sm_month-help@T__">
  __Display small months__:</td><td>
  {print_checkbox name='DISPLAY_SM_MONTH'}
 </td></tr>
 <tr><td class="tooltip" title="__display-weekends-help@T__">
  __Display weekends__:</td><td>
  {print_checkbox name='DISPLAY_WEEKENDS'}
 </td></tr>
 <tr><td class="tooltip" title="__display-long-daynames-help@T__">
  __Display long day names__:</td><td>
  {print_checkbox name='DISPLAY_LONG_DAYS'}
 </td></tr>
 <tr><td class="tooltip" title="__display-alldays-help@T__">
  __Display all days in month view__:</td><td>
  {print_checkbox name='DISPLAY_ALL_DAYS_IN_MONTH'}
 </td></tr>
  <tr><td class="tooltip" title="__display-week-number-help@T__">
  __Display week number__:</td><td>
  {print_checkbox name='DISPLAY_WEEKNUMBER'}
 </td></tr>

 <tr><td class="tooltip" title="__display-desc-print-day-help@T__">
  __Display description in printer day view__:</td><td>
  {print_checkbox name='DISPLAY_DESC_PRINT_DAY'}
 </td></tr>

 <tr><td class="tooltip" title="__yearly-shows-events-help@T__">
  __Display events in bold__:</td><td>
  {print_checkbox name='BOLD_DAYS_IN_YEAR'}
 </td></tr>

<tr><td class="tooltip" title="__display-minutes-help@T__">
 __Display 00 minutes always__:</td><td>
 {print_checkbox name='DISPLAY_MINUTES'}
</td></tr>

<tr><td class="tooltip" title="__display-end-times-help@T__">
 __Display end times on calendars__:</td><td>
 {print_checkbox name='DISPLAY_END_TIMES'}
</td></tr>

  <tr><td class="tooltip" title="__allow-view-add-help@T__">
  __Include add event link in views__:</td><td>
  {print_checkbox name='ADD_LINK_IN_VIEWS'}
 </td></tr>

<tr><td class="tooltip" title="__lunar-help@T__">
  __Display Lunar Phases in month view__:</td><td>
  {print_checkbox name='DISPLAY_MOON_PHASES'}
 </td></tr>
</table> 
</fieldset>
<fieldset>
<legend>__Popups__</legend>
<table width="100%">
 <tr><td class="tooltip" title="__enable-popups-help@T__">
  __Enable Pop-Ups__:</td><td>
  {print_checkbox name='ENABLE_POPUPS' onchange='popup_handler()'}
 </td></tr>
 <tbody id="popup">
 <tr><td class="tooltip" title="__popup-includes-siteextras-help@T__">
  __Display Site Extras in popup__:</td><td>
  {print_checkbox name='SITE_EXTRAS_IN_POPUP'}
 </td></tr>
 <tr><td class="tooltip" title="__popup-includes-participants-help@T__">
  __Display Participants in popup__:</td><td>
  {print_checkbox name='PARTICIPANTS_IN_POPUP'}
 </td></tr>
 </tbody>
</table>
</fieldset>
</div>
<!-- END SETTINGS -->

<!-- BEGIN EVENTS -->
<div id="tabscontent_events">
<table width="100%">
 <tr><td class="tooltip" title="__require-approvals-help@T__">
  __Require event approvals__:</td><td>
  {print_checkbox name='REQUIRE_APPROVALS'}
 </td></tr>
 <tr><td class="tooltip" title="__display-unapproved-help@T__">
  __Display unapproved@L4__:</td><td>
  {print_checkbox name='DISPLAY_UNAPPROVED'}
 </td></tr>
 <tr><td class="tooltip" title="__conflict-check-help@T__">
  __Check for event conflicts__:</td><td>
    {print_checkbox name='CHECK_CONFLICTS'}
 </td></tr>
 <tr><td class="tooltip" title="__conflict-months-help@T__">
  __Conflict checking months@L4__:</td><td>
  <input type="text" size="3" name="admin_CONFLICT_REPEAT_MONTHS" value="{$s.CONFLICT_REPEAT_MONTHS|htmlspecialchars}" />
 </td></tr>
 <tr><td class="tooltip" title="__conflict-check-override-help@T__">
  __Allow users to override conflicts@L4__:</td><td>
  {print_checkbox name='_ALLOW_CONFLICT_OVERRIDE'}
 </td></tr>
 <tr><td class="tooltip" title="__limit-appts-help@T__">
  __Limit number of timed events per day__:</td><td>
  {print_checkbox name='_LIMIT_APPTS'}
 </td></tr>
 <tr><td class="tooltip" title="__limit-appts-number-help@T__">
  __Maximum timed events per day@L4__:</td><td>
  <input type="text" size="3" name="admin__LIMIT_APPTS_NUMBER" value="{$s._LIMIT_APPTS_NUMBER|htmlspecialchars}" />
 </td></tr>
 <tr><td class="tooltip" title="__summary_length-help@T__">
  __Brief Description Length__:</td><td>
  <input type="text" size="3" name="admin_SUMMARY_LENGTH" value="{$s.SUMMARY_LENGTH}" />
 </td></tr>
 <tr><td class="tooltip" title="__crossday-help@T__">
  __Enable Cross-Day Events__:</td><td>
  {print_checkbox name='_ENABLE_CROSSDAY_EVENTS'}
 </td></tr>
  <tr><td class="tooltip" title="__enable-location-field-help@T__">
  __Enable Location field__:</td><td>
  {print_checkbox name='_ENABLE_LOCATION_FIELD'}
 </td></tr>
  <tr><td class="tooltip" title="__enable-url-field-help@T__">
  __Enable URL field__:</td><td>
  {print_checkbox name='_ENABLE_URL_FIELD'}
 </td></tr>
 <tr><td class="tooltip" title="__enable-priority-field-help@T__">
  __Enable Priority field__:</td><td>
  {print_checkbox name='_ENABLE_PRIORITY_FIELD'}
 </td></tr>
 <tr><td class="tooltip" title="__enable-access-field-help@T__">
  __Enable Access field__:</td><td>
  {print_checkbox name='_ENABLE_ACCESS_FIELD'}
 </td></tr>
 <tr><td class="tooltip" title="__enable-participants-field-help@T__">
  __Enable Participants field__:</td><td>
  {print_checkbox name='_ENABLE_PARTICIPANTS_FIELD'}
 </td></tr>
 <tr><td class="tooltip" title="__enable-repeating-field-help@T__">
  __Enable Repeating field__:</td><td>
  {print_checkbox name='_ENABLE_REPEATING_FIELD'}
 </td></tr>
 <tr><td class="tooltip" title="__allow-html-description-help@T__">
  __Allow HTML in Description__:</td><td>
  {print_checkbox name='_ALLOW_HTML_DESCRIPTION'}
 </td></tr>
 <!-- BEGIN ATTACHMENTS/COMMENTS -->

 <tr><td class="tooltip" title="__allow-attachment-help@T__">
  __Allow file attachments to events__:</td><td>
  {print_checkbox name='_ALLOW_ATTACH' onchange='attach_handler()'}
  <span id="attach">
  <br/><strong>__Note__:</strong>
  __Admin and owner can always add attachments if enabled__<br/>
   {print_checkbox name='_ALLOW_ATTACH_PART' label="__Participant__"}
   {print_checkbox name='_ALLOW_ATTACH_ANY' label="__Anyone__"}
  </span>
 </td></tr>

 <tr><td class="tooltip" title="__allow-comments-help@T__">
  __Allow comments to events__:</td><td>
  {print_checkbox name='_ALLOW_COMMENTS' onchange='comment_handler()'}
  <br/>
  <span id="comment">
  <br/><strong>Note:</strong>
  __Admin and owner can always add comments if enabled__<br/>
  {print_checkbox name='_ALLOW_COMMENTS_PART' label="__Participant__"}
  {print_checkbox name='_ALLOW_COMMENTS_ANY' label="__Anyone__"}
  </span>
 </td></tr>

 <!-- END ATTACHMENTS/COMMENTS --> 
</table>
</div>

<!-- BEGIN GROUPS -->
<div id="tabscontent_groups">
<table width="100%">
 <tr><td class="tooltip" title="__groups-enabled-help@T__">
  __Groups enabled__:</td><td>
  {print_checkbox name='_ENABLE_GROUPS'}
 </td></tr>
 <tr><td class="tooltip" title="__user-sees-his-group-help@T__">
  __User sees only his groups__:</td><td>
  {print_checkbox name='_USER_SEES_ONLY_HIS_GROUPS'}
 </td></tr>
</table>
</div>

<!-- BEGIN USER SETTINGS-->
<div id="tabscontent_users">
<table width="100%">
 <tr><td class="tooltip" title="__cleartext-passwords-help@T__">
  __Allow cleartext passwords__:</td><td>
  {print_checkbox name='_CLEARTEXT_PASSWORDS'}
 </td></tr>
 <tr><td class="tooltip" title="__expire-password-help@T__">
  __Password expiration__:</td><td>
  <input type="text" size="3" name="admin__EXPIRE_PASSWORDS" value="{$s._EXPIRE_PASSWORDS}" />
 </td></tr>
 <tr><td class="tooltip" title="__remember-last-login-help@T__">
  __Remember last login__:</td><td>
  {print_checkbox name='REMEMBER_LAST_LOGIN'}
 </td></tr>
 <tr><td class="tooltip" title="__allow-view-other-help@T__">
  __Allow viewing other user&#39;s calendars__:</td><td>
  {print_checkbox name='_ALLOW_VIEW_OTHER'}
 </td></tr>
 <tr><td class="tooltip" title="__user_sort-help@T__">
  <label for="admin_USER_SORT_ORDER">__User Sort Order__:</label></td><td>
  <select name="admin_USER_SORT_ORDER" id="admin_USER_SORT_ORDER">
   <option value="cal_lastname, cal_firstname"
    {if $s.USER_SORT_ORDER == "cal_lastname, cal_firstname"}
     {#selected#}
    {/if}>__Lastname, Firstname__</option>
   <option value="cal_firstname, cal_lastname" 
   {if $s.USER_SORT_ORDER == "cal_firstname, cal_lastname" } 
    {#selected#}
    {/if}>__Firstname, Lastname__</option>
  </select>
 </td></tr>
 <tr><td class="tooltip" title="__nonuser-enabled-help@T__">
  __Nonuser enabled__:</td><td>
  {print_checkbox name='_ENABLE_NONUSERS'}
 </td></tr>
 <tr><td class="tooltip" title="__nonuser-list-help@T__">
  __Nonuser list__:</td><td>
  {print_radio variable='_NONUSER_AT_TOP' vars=$top_bottom_array}
</td></tr>
 <tr><td class="tooltip" title="___EXTENDED_USER-help@T__">
  __Allow extended user settings__:</td><td>
  {print_checkbox name='_EXTENDED_USER'}
 </td></tr>
<!-- BEGIN EXT PARTICIPANTS -->

 <tr><td class="tooltip" title="__allow-external-users-help@T__">
  __Allow external users__:</td><td>
  {print_checkbox name='_ALLOW_EXTERNAL_USERS' onchange='eu_handler()'}
 </td></tr>
 <tbody id="eu">
 <tr><td class="tooltip" title="__external-can-receive-notification-help@T__">
  __External users can receive email notifications@L4__:</td><td>
  {print_checkbox name='_EXTERNAL_NOTIFICATIONS'}
 </td></tr>
 <tr><td class="tooltip" title="__external-can-receive-reminder-help@T__">
  __External users can receive email reminders@L4__:</td><td>
  {print_checkbox name='_EXTERNAL_REMINDERS'}
 </td></tr>
 </tbody>
  <!-- BEGIN SELF REGISTRATION -->

 <tr><td class="tooltip" title="__allow-self-registration-help@T__">
  __Allow self-registration__:</td><td>
  {print_checkbox name='_ALLOW_SELF_REGISTRATION' onchange='sr_handler()'}
 </td></tr>
 <tbody id="sr">
 <tr><td class="tooltip" title="__use-blacklist-help@T__">
  __Restrict self-registration to blacklist@L4__:</td><td>
  {print_checkbox name='_SELF_REGISTRATION_BLACKLIST' onchange='sr_handler()'}
 </td></tr>
 <tr><td class="tooltip" title="__allow-self-registration-full-help@T__">
  __Use self-registration email notifications@L4__:</td><td>
  {print_checkbox name='_SELF_REGISTRATION_FULL' onchange='sr_handler()'}
 </td></tr>
 </tbody>
<!-- TODO add account aging feature -->
</table>
</div>

<!-- BEGIN REPORTS -->
<div id="tabscontent_other">
<table width="100%">
<tr><td class="tooltip" title="__reports-enabled-help@T__">
 __Reports enabled__:</td><td>
 {print_checkbox name='_ENABLE_REPORTS'}
</td></tr>


<!-- BEGIN CATEGORIES -->

 <tr><td class="tooltip" title="__categories-enabled-help@T__">
  __Categories enabled__:</td><td>
  {print_checkbox name='_ENABLE_CATEGORIES'}
 </td></tr>

 <tr><td class="tooltip" title="__icon_upload-enabled-help@T__">
  __Category Icon Upload enabled__:</td><td>
  {print_checkbox name='_ENABLE_ICON_UPLOADS'}&nbsp;{$icons_dir_notice}
 </td></tr>
 
<!-- Display Task Preferences -->
 <tr><td class="tooltip" title="__display-tasks-help@T__">
  __Display small task list__:</td><td>
  {print_checkbox name='DISPLAY_TASKS'}
 </td></tr>
 <tr><td class="tooltip" title="__display-tasks-in-grid-help@T__">
  __Display tasks in Calendars__:</td><td>
  {print_checkbox name='DISPLAY_TASKS_IN_GRID'}
 </td></tr>
<!-- BEGIN PUBLISHING -->

<tr><td class="tooltip" title="__subscriptions-enabled-help@T__">
 __Allow remote subscriptions__:</td><td>
 {print_checkbox name='_ENABLE_PUBLISH'}
</td></tr>
{if $allow_url_fopen}
<tr><td class="tooltip" title="__remotes-enabled-help@T__">
 __Allow remote calendars__:</td><td>
 {print_checkbox name='_ENABLE_REMOTES'}
</td></tr>
{/if}
<tr><td class="tooltip" title="__rss-enabled-help@T__">
 __Enable RSS feed__:</td><td>
 {print_checkbox name='_ENABLE_RSS'}
</td></tr>
<tr><td class="tooltip" title="__rss-default-user-help@T__">
 __RSS default user__:</td><td>
  <select name="admin__RSS_DEFAULT_USER"> 
 {foreach from=$userlist key=k item=v}
    <option value="{$v.cal_login_id}" {if  $v.cal_login_id == 
      $s._RSS_DEFAULT_USER}{#selected#}{/if}>{$v.cal_fullname}</option>
 {/foreach}
  </select>
</td></tr>
<tr><td class="tooltip" title="__publish-default-user-help@T__">
 __Publish default user__:</td><td>
  <select name="admin__PUBLISH_DEFAULT_USER"> 
 {foreach from=$userlist key=k item=v}
    <option value="{$v.cal_login_id}" {if  $v.cal_login_id == 
      $s._PUBLISH_DEFAULT_USER}{#selected#}{/if}>{$v.cal_fullname}</option>
 {/foreach}
  </select>
</td></tr>
<tr><td class="tooltip" title="__minical-default-user-help@T__">
 __Minical default user__:</td><td>
  <select name="admin__MINICAL_DEFAULT_USER"> 
 {foreach from=$userlist key=k item=v}
    <option value="{$v.cal_login_id}" {if  $v.cal_login_id == 
      $s._MINICAL_DEFAULT_USER}{#selected#}{/if}>{$v.cal_fullname}</option>
 {/foreach}
  </select>
</td></tr>

</table>
</div>

<!-- BEGIN EMAIL -->
<div id="tabscontent_email">
<table width="100%">
<tr><td class="tooltip" title="__email-enabled-help@T__">
 __Email enabled__:</td><td>
 {print_checkbox name='_SEND_EMAIL' onchange='email_handler()'}
</td></tr>
<tbody id="em">
<tr><td class="tooltip" title="__email-default-sender@T__">
 __Default sender address@L4__:</td><td>
 <input type="text" size="30" name="admin__EMAIL_FALLBACK_FROM" value="{$s._EMAIL_FALLBACK_FROM}" />
</td></tr>

<tr><td class="tooltip" title="__email-mailer@T__">
__Email Mailer__:</td><td>
 <select name="admin__EMAIL_MAILER"  onchange="email_handler()">
   <option value="smtp" {if $s._EMAIL_MAILER == 'smtp'}
     {#selected#}{/if}>SMTP</option>
   <option value="mail" {if $s._EMAIL_MAILER == 'mail'}
     {#selected#}{/if}>PHP mail</option>
   <option value="sendmail" {if $s._EMAIL_MAILER == 'sendmail'}
     {#selected#}{/if}>sendmail</option>
  </select>   
</td></tr>
<tbody id="em_smtp">
<tr><td class="tooltip" title="__email-smtp-host@T__">
__SMTP Host name(s)__:</td><td>
 <input type="text" size="50" name="admin__SMTP_HOST" value="{$s._SMTP_HOST}" />
</td></tr>
<tr><td class="tooltip" title="__email-smtp-port@T__">
__SMTP Port Number__:</td><td>
 <input type="text" size="4" name="admin__SMTP_PORT" value="{$s._SMTP_PORT}" />
</td></tr>

<tr><td class="tooltip" title="__email-smtp-auth@T__">
 __SMTP Authentication__:</td><td>
 {print_checkbox name='_SMTP_AUTH' onchange='email_handler()'}
</td></tr>
<tbody id="em_auth">
<tr><td class="tooltip" title="__email-smtp-username@T__">
 __SMTP Username@L4__:</td><td>
 <input type="text" size="30" name="admin__SMTP_USERNAME" value="{$s._SMTP_USERNAME}" />
</td></tr>

<tr><td class="tooltip" title="__email-smtp-password@T__">
 __SMTP Password@L4__:</td><td>
 <input type="text" size="30" name="admin__SMTP_PASSWORD" value="{$s._SMTP_PASSWORD}" />
</td></tr>
</tbody>
</tbody>
<tr><td colspan="2" class="bold">
 __Default user settings__:
</td></tr>
<tr><td class="tooltip" title="__email-event-reminders-help@T__">
 __Event reminders@L4__:</td><td>
 {print_checkbox name='EMAIL_REMINDER'}
</td></tr>
<tr><td class="tooltip" title="__email-event-added@T__">
 __Events added to my calendar@L4__:</td><td>
 {print_checkbox name='EMAIL_EVENT_ADED'}
</td></tr>
<tr><td class="tooltip" title="__email-event-updated@T__">
 __Events updated on my calendar@L4__:</td><td>
 {print_checkbox name='EMAIL_EVENT_UPDTED'}
</td></tr>
<tr><td class="tooltip" title="__email-event-deleted@T__">
 __Events removed from my calendar@L4__:</td><td>
 {print_checkbox name='EMAIL_EVENT_DELTED'}
</td></tr>
<tr><td class="tooltip" title="__email-event-rejected@T__">
 __Event rejected by participant@L4__:</td><td>
 {print_checkbox name='EMAIL_EVENT_REJCTED'}
</td></tr>
<tr><td class="tooltip" title="__email-event-create@T__">
 __Event that I create@L4__:</td><td>
 {print_checkbox name='EMAIL_EVENT_CRETE'}
</td></tr>
</tbody>
</table>
</div>

<!-- BEGIN COLORS -->
<div id="tabscontent_colors">
<fieldset>
 <legend>__Color options__</legend>
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
 __Allow user to customize colors__:</label></td><td colspan="3">
 {print_checkbox name='_ALLOW_COLOR_CUSTOMIZATION'}
</td></tr>
<tr><td class="tooltip" title="__gradient-colors@T__">
  <label>__Enable gradient images for background colors__:</label></td>
 <td colspan="3">
{if $enable_gradients}
  {print_checkbox name='ENABLE_GRADIENTS'}
{else}
   __Not available__
{/if}
</td></tr>
<tr><td>
 {html_color_input name='BGCOLOR' title="__Document background__"}
</td>
</tr>
<tr><td>
 {html_color_input name='H2COLOR' title="__Document title__"}
</td></tr>
<tr><td>
 {html_color_input name='TEXTCOLOR' title="__Document text__"}
</td></tr>
<tr><td>
 {html_color_input name='MYEVENTS' title="__My event text__"}
</td></tr>
<tr><td>
 {html_color_input name='TABLEBG' title="__Table grid__"}
</td></tr>
<tr><td>
 {html_color_input name='THBG' title="__Table header background__"}
</td></tr>
<tr><td>
 {html_color_input name='THFG' title="__Table header text__"}
</td></tr>
<tr><td>
 {html_color_input name='CELLBG' title="__Table cell background__"}
</td></tr>
<tr><td>
 {html_color_input name='TODAYCELLBG' title="__Table cell background__ __for current day__"}
</td></tr>
<tr><td>
 {html_color_input name='HASEVENTSBG' title="__Table cell background__ __for days with events__"}
</td></tr>
<tr><td>
  {html_color_input name='WEEKENDBG' title="__Table cell background__ __for weekends__"}
</td></tr>
<tr><td>
  {html_color_input name='OTHERMONTHBG' title="__Table cell background__ __for other month__"}
</td></tr>
<tr><td>
  {html_color_input name='WEEKNUMBER' title="__Week number__"}
</td></tr>
<tr><td>
 {html_color_input name='POPUP_BG' title="__Event popup background__"}
</td></tr>
<tr><td>
  {html_color_input name='POPUP_FG' title="__Event popup text__"}
</td></tr>
</table>
</fieldset>
<fieldset>
 <legend>__Background Image options__</legend>
<table width="100%">
 <tr><td class="tooltip" title="__bgimage-help@T__">
  <label for="admin_BGIMAGE">__Background Image__:</label></td><td>
  <input type="text" size="75" name="admin_BGIMAGE" id="admin_BGIMAGE" value="{$s.BGIMAGE|htmlspecialchars}" />
 </td></tr>
 <tr><td class="tooltip" title="__bgrepeat-help@T__">
  <label for="admin_BGREPEAT">__Background Repeat__:</label></td><td>
  <input type="text" size="30" name="admin_BGREPEAT" id="admin_BGREPEAT" value="{$s.BGREPEAT}" />
 </td></tr>
</table>
</fieldset>
</div>
</div>

<br /><br />
<div>
 <input type="submit" value="__Save__" name="" />
</div>
</form>
{include file="footer.tpl"}


