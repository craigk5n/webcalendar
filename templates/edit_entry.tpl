 {include file="header.tpl"}
<h2>{if $eid}__Edit Entry__{else}__Add Entry__{/if}
  {$eType}{generate_help_icon url='help_edit_entry.php'} <br />
  &nbsp;&nbsp;{$navFullname}
  {$navAdmin}
  {$navAssistant}</h2>
<form action="edit_entry_handler.php" method="post" name="editentryform" id="editentryform">
  <input type="hidden" name="eType" value="{$eType}" />
  {if  ! $copy}
  <input type="hidden" name="eid" value="{$eid}" />
  {/if}
  <input type="hidden" name="entry_changed" id="entry_changed" value="" />
  {if $override}
  <input type="hidden" name="override" value="1" />
  <input type="hidden" name="override_date" value="{$cal_date}" />
  {/if}
  
  {if $WC->isNonuserAdmin() || $WC->userId()}
  <input type="hidden" name="user" value="{$user}" />
  {/if}
  
  {if $parent}
  <input type="hidden" name="parent" value="{$parent}" />
  {/if}
  <!-- TABS -->
  {print_tabs tabs=$tabs_ar}
  <!-- TABS BODY -->
  <div id="tabscontent">
    <!-- DETAILS -->
    <a name="tabdetails"></a>
    <div id="tabscontent_details">
      <fieldset>
      <legend>__Details__</legend>
      <table border="0">
        <tr>
          <td style="width:14%;" class="tooltip" title="__brief-description-help@T__">
            <label for="entry_brief">__Brief Description__:</label></td>
          <td colspan="4"><input type="text" name="entry_brief" id="entry_brief" size="25" value="{$name|htmlspecialchars}" /></td>
        </tr>
        <tr>
          <td class="tooltip alignT" title="__full-description-help@T__"><label for="entry_full">__Full Description__:</label></td>
          <td   colspan="4"><textarea name="entry_full" id="entry_full" {$textareasize}>{$description|htmlspecialchars}</textarea></td>
        </tr>
          {if ! $s.DISABLE_ACCESS_FIELD || ! $s.DISABLE_PRIORITY_FIELD}
        <tr> {if ! $s.DISABLE_ACCESS_FIELD}
          <td class="tooltip" title="__access-help@T__"><label for="access">__Access__:</label></td>
          <td><select name="access" id="access">
              <option value="P" {if $access == 'P'}
        {#selected#}{/if} >__Public__</option>
              <option value="R" {if $access == 'R'}
        {#selected#}{/if}>__Private__</option>
              <option value="C" {if $access == 'C'}
        {#selected#}{/if}>__Confidential__</option>
            </select>
          </td>
          {/if}
          {if ! $s.DISABLE_PRIORITY_FIELD}
          <td class="tooltip" title="__priority-help@T__" {if ! $s.DISABLE_ACCESS_FIELD}align="right"{/if}><label for="priority">__Priority__:&nbsp;</label></td>
          <td><select name="priority" id="priority">
      {foreach from=$priority key=k item=v}
              <option  value="{$k}" {$v.selected}>{$v.display}</option>
      {/foreach}
            </select>
          </td>
          {else}
          <td colspan="2"></td>
          {/if} </tr>
        {/if}
        {if $categories}
        <tr>
          <td class="tooltip" title="__category-help@T__">
            <label>__Category__:<br /></label>
          </td>
          <td colspan="4">
            <input type="text" readonly="readonly" name="catnames" id="catnames" value="{$catNames}"  size="30" onclick="editCats(event, 'editentryform')"/>
            <input type="button" value="__Edit__" onclick="editCats(event, 'editentryform')" />
            <input type="hidden" name="cat_id"  id="cat_id" value="{$catList}" />
          </td>
        </tr>
        {/if}
        
        {if $eType eq 'task'}
        <tr id="completed">
          <td class="tooltip" title="__completed-help@T__">
            <label for="task_percent">__Date Completed__:&nbsp;</label></td>
          <td colspan="4"><input type="text" name="completed_date" id="completed_date" value="{$completed|date_to_str:datepicker}" onclick="lcs(this, event)" /></td>
        </tr>
        <tr>
          <td class="tooltip boxL boxB boxT" valign="top" title="__percent-help@T__">
            <label for="task_percent">__Percent Complete__:&nbsp;</label></td>
          <td class="boxT boxB" width="10%" valign="top">
            <select name="task_percent" id="task_percent" onchange="completed_handler()">
    {section name=tpercent loop=110 step=10} 
              <option value="{$smarty.section.tpercent.index}" {if $task_percent == $smarty.section.tpercent.index}{#selected#}{/if}>{$smarty.section.tpercent.index}</option>
    {/section}
            </select>
          </td>
          <td  colspan="2" class="boxR boxT boxB">
            {if $overall_percent}
            <table width="80%" border="0" cellpadding="2" cellspacing="5">
              <tr>
                <td colspan="2"><label>__All Percentages__</label></td>
              </tr>
              {foreach from=$overall_percent key=k item=v name=opercent}
              <tr {if $smarty.foreach.opercent.index%2==1}class="odd"{/if}>
                <td width="50%">{$v.fullname}</td>
                <td>{$v.percent}</td>
              </tr>
              {/foreach}
            </table>
          </td>
        <input type="hidden" name="others_complete" id="others_complete" value="{$others_complete}" />
        {/if}
        </tr>
      
      {/if}
      {if  ! $s.DISABLE_LOCATION_FIELD}
        <tr>
          <td class="tooltip" title="__location-help@T__"><label for="location">__Location__:</label></td>
          <td colspan="4"><input type="text" name="location" id="location" size="55" value="{$location|htmlspecialchars}" />
          </td>
        </tr>
        {/if}
        {if ! $s.DISABLE_URL_FIELD}
        <tr>
          <td class="tooltip" title="__url-help@T__"><label for="entry_url">__URL__:</label></td>
          <td colspan="4"><input type="text" name="entry_url" id="entry_url" size="100" 
   value="{$cal_url|htmlspecialchars}" />
          </td>
        </tr>
        {/if}
        <tr>
          <td class="tooltip" title="__date-help@T__">
            <label>{if $eType == 'task'}__Start Date__{else}__Date__{/if}:</label>
          </td>
          <td colspan="4"><input type="text" name="entry_date" value="{$cal_date|date_to_str:datepicker}" onclick="lcs(this, event)" /></td>
        </tr>
        {if $eType != 'task'}
        <tr>
          <td>&nbsp;</td>
          <td colspan="4"><select name="timetype" id="timetype" onchange="timetype_handler()">
              <option value="U" {if $isUntimed}{#selected#}{/if}>__Untimed event__</option>
              <option value="T" {if $isTimed}{#selected#}{/if}>__Timed event__</option>
              <option value="A" {if $allDay}{#selected#}{/if}>__All day event__</option>
            </select>
          </td>
        </tr>
        {if $TZ_notice}
        <tr id="timezonenotice">
          <td class="tooltip" title="__Time entered here is based on your Timezone__"> __Timezone Offset__:</td>
          <td colspan="4">{$TZ_notice}</td>
        </tr>
        {/if}
        <tr id="timeentrystart" style="visibility:hidden;">
          <td class="tooltip" title="__time-help@T__"><label>__Time__:</label></td>
          <td colspan="4">{time_selection prefix='entry_'  time=$cal_time}
            
            {if $p.TIMED_EVT_LEN != 'E'} </td>
        </tr>
        <tr id="timeentryduration" style="visibility:hidden;">
          <td class="tooltip" title="__duration-help@T__"><label>__Duration__:&nbsp;</label>
          </td>
          <td colspan="4"><input type="text" name="duration_h" id="duration_h" size="2" maxlength="2" value="{if ! $allDay}{$dur_h}{/if}" />
            :
            <input type="text" name="duration_m" id="duration_m" size="2" maxlength="2" value="{if ! $allDay}{$dur_m}{/if}" />
            &nbsp;(
            <label for="duration_h"> __hours__</label>
            :
            <label for="duration_m"> __minutes__</label>
            ) </td>
        </tr>
        {else} <span id="timeentryend" class="tooltip" title="__end-time-help@T__">&nbsp;-&nbsp;{time_selection prefix='end_' time=$end_time}</span>
        </td>
        
        </tr>
        
        {/if}
        {else}
        <tr>
          <td class="tooltip" title="__time-help@T__">
            <label>__Start Time__:</label></td>
          <td colspan="4">{time_selection prefix='entry_' time=$cal_time} </td>
        </tr>
        <tr>
          <td class="tooltip" title="__date-help@T__">
            <label>__Due Date__:</label></td>
          <td colspan="4"><input type="text" name="due_date" id="due_date"value="{$due_date|date_to_str:datepicker}" onclick="lcs(this, event)" /></td>
        </tr>
        <tr>
          <td class="tooltip" title="__time-help@T__">
            <label>__Due Time__:</label></td>
          <td colspan="4">{time_selection prefix='due_' time=$due_time}</td>
        </tr>
        {/if}
      </table>
      </fieldset>
      <!-- Site Extras -->
      {if $site_extras}
      <div>
        <fieldset>
        <legend>__Site Extras__</legend>
        <table>
          {foreach from=$site_extras key=k item=v}
          {assign var=v0 value=$v.0}
          <tr>
            <td class="alignT bold">{if $v[2] == $smarty.const.EXTRA_MULTILINETEXT}
              <br />
              {/if}{$v[1]}:</td>
            <td> {if $v[2] == $smarty.const.EXTRA_URL}
              <input type="text" size="50" name="{$v[0]}" value="{$v[0].cal_data|htmlspecialchars}" />
              {elseif $v[2] == $smarty.const.EXTRA_EMAIL}
              <input type="text" size="30" name="{$v[0]}" 
          value="{$v[0]}" />
              {elseif $v[2] == $smarty.const.EXTRA_DATE}
              {if $extras.$v0.cal_date}
              <input type="text" name="{$v[0]}" id="{$v[0]}" value="{$extras.$v0.cal_date|date_to_str:datepicker}" onclick="lcs(this, event)" />
              {else}
              <input type="text" name="{$v[0]}" id="{$v[0]}"value="{$cal_date|date_to_str:datepicker}" onclick="lcs(this, event)" />
              {/if}
              {elseif $v[2] == $smarty.const.EXTRA_TEXT}
              <input type="text" size="$v[3]" name="$v[0]}" 
       value="$v[0]|htmlspecialchars}" />
              {elseif $v[2] == $smarty.const.EXTRA_MULTILINETEXT}
              <textarea rows="$v[4]" cols="$v[3]" 
      name="{$v[0]}">{$v[0]|htmlspecialchars}</textarea>
              {elseif $v[2] == $smarty.const.EXTRA_USER}
              <select name="{$v[0]}">
                <option value="">__None__</option>
                
    {foreach from=$userlist key=uk item=uv}
      
                <option value="{$uv.cal_login}" 
      {if $uv.cal_login == $v[0]}{#selected#}{/if}>{$uv.cal_fullname}</option>
                
    {/foreach}
    
              </select>
              {elseif $v[2] == $smarty.const.EXTRA_SELECTLIST}
              <select name="{$v[0]}{$v.isMultiple}" {$v.multiselect}>
                
     {foreach name=arglcnt from=$v[3]cnt key=ak item=av}
        
                <option value="{$av[3]}" 
          {if $av[4] === 0 && 
            $av[3] == $v[0]}{#selected#}
          {elseif $av[4] gt 0 && in_array ( $av[3], $extraSelectArr )}
            {#selected#}
          {elseif $smarty.foreach.arglcnt.index == 0}
            {#selected#}
          {/if}>
                {$av[3]}
                </option>
                
      {/foreach}
     
              </select>
              {elseif $v[2] == $smarty.const.EXTRA_RADIO}  
              {print_radio variable=$v[0]  vars=$v[3] 
                defIdx=$v[0]}
              {elseif $v[2] == $smarty.const.EXTRA_CHECKBOX}
              {print_checkbox  variable=$v[0] vars=$v[3] defIdx=$v[0]}
              {/if} </td>
          </tr>
          {/foreach}
        </table>
        </fieldset>
      </div>
      {/if}
      <!--end site-specific extra fields-->
    </div>
    <!-- PARTICIPANTS -->
    {if $show_participants} <a name="tabparticipants"></a>
    <div id="tabscontent_participants">
      <fieldset>
      <legend>__Participants__</legend>
      <table>
        <tr title="__avail_participants-help@T__">
          <td class="tooltip alignT" rowspan="3">
            <label>__Available__<br />__Participants__:</label>
          </td>
          <td class="boxL boxT">&nbsp;</td>
          <td colspan="5"class="boxT boxR">__Find Name__
            <input type="text" size="20" name="lookup" id="lookup" onkeyup="lookupName()" />
          </td>
        </tr>
        <tr>
          <td align="right" valign="top" class="boxL">
            <label>__Users__</label>
          </td>
          <td rowspan="2" valign="top" width="160px" class="boxB">
            <select class="fixed" name="participants[]" id="entry_part" size="{$size}" multiple="multiple">
          {foreach from=$myuserlist key=k item=v}
              <option value="{$v.cal_login_id}">{$v.cal_fullname}</option>
          {/foreach}
            </select>
        </td>
        <td align="right" valign="top">
          <label>__Resources__</label>
        </td>
        <td rowspan="2"  class="boxB">
          <select class="fixed" name="nonuserPart[]" id="res_part" size="{$size}" multiple="multiple">
        {foreach from=$nonuserlist key=k item=v}
            <option value="{$v.cal_login_id}">{$v.cal_fullname}</option>
        {/foreach}
          </select>
          </td>  
          <td rowspan="2" align="right" valign="top" class="boxB">
        {if $grouplist}
           &nbsp;&nbsp;<label>__Groups__</label>
        {/if}
          </td>
          <td rowspan="2" valign="top"  class="boxB boxR">
        {if $grouplist}          
            <select class="fixed" name="groups" id="groups" size="{$size}" onclick="addGroup()">
        {foreach from=$grouplist key=k item=v}
              <option value="{$v.cal_group_id}">{$v.cal_name}</option>
        {/foreach}
            </select>
        {/if}
          </td>      
        </tr>
        <tr>
          <td align="right" valign="bottom" class="boxB boxL">
            <input name="movert" type="button" value="__Add__" onclick="selAdd( this );" />
          </td>
          <td align="right" valign="bottom" class="boxB">
            <input name="moveit" type="button" value="__Add__" onclick="selResource( this );" />
          </td>
        </tr>    
        <tr>
          <td colspan="7">&nbsp;</td>
        </tr>
        <tr title="__participants-help@T__">
          <td class="tooltip alignT" rowspan="2">
            <label>__Selected__<br />__Participants__:</label>
          </td>
          <td align="left" valign="bottom" class="boxT boxL boxB">
            <input name="movelt" type="button" value="__Remove__" onclick="selRemove( this );" />
          </td>
          <td class="boxT boxB" valign="bottom">
            <select class="fixed" name="selectedPart[]" id="sel_part" size="7" multiple="multiple">
        {foreach from=$participants key=k item=v}
              <option value="{$v.cal_login_id}">{$v.cal_fullname} {$v.status}</option>
        {/foreach}
            </select>
          </td>
          <td class="boxT boxR boxB" valign="bottom">
            <input type="button" onclick="showSchedule()" value="__Availability__..." />
          </td>
          <td colspan="3"></td>
        </tr>

        {if $s.ALLOW_EXTERNAL_USERS}
        <tr title="__external-participants-help@T__">
          <td class="tooltip alignT"><label for="entry_extpart">__External Participants__:</label></td>
          <td><textarea name="externalparticipants" id="entry_extpart" rows="5" cols="40">
      {$external_users}</textarea></td>
        </tr>
        {/if}
      </table>
      </fieldset>
    </div>
    {/if}
    <!-- REPEATING INFO -->
    {if ! $s.DISABLE_REPEATING_FIELD} <a name="tabpete"></a>
    <div id="tabscontent_pete">
      <fieldset>
      <legend>__Repeat__</legend>
      <table border="0" cellspacing="0" cellpadding="3">
        <tr>
          <td class="tooltip" title="__repeat-type-help@T__"><label for="rpt_type">__Type__:</label></td>
          <td colspan="2"><select name="rpt_type" id="rpt_type" onchange="rpttype_handler();rpttype_weekly()">
              <option value="none" 
   {if $rpt_type == 'none'}{#selected#}{/if}>__None__</option>
              <option value="daily"
   {if $rpt_type == 'daily'}{#selected#}{/if}>__Daily__</option>
              <option value="weekly"
   {if $rpt_type =='weekly'}{#selected#}{/if}>__Weekly__</option>
              <option value="monthlyByDay"
   {if $rpt_type =='monthlyByDay'}{#selected#}{/if}>__Monthly__ (__by day__)</option>
              <option value="monthlyByDate"
   {if $rpt_type =='monthlyByDate'}{#selected#}{/if}>__Monthly__ (__by date__)</option>
              <option value="monthlyBySetPos"
   {if $rpt_type == 'monthlyBySetPos'}{#selected#}{/if}>__Monthly__ (__by position__)</option>
              <option value="yearly"
   {if $rpt_type == 'yearly'}{#selected#}{/if}>__Yearly__</option>
              <option value="manual"
   {if $rpt_type == 'manual'}{#selected#}{/if}>__Manual__</option>
            </select>
            &nbsp;&nbsp;&nbsp;
            <label id="rpt_mode_lbl">
            <input type="checkbox" name="rpt_mode"  id="rpt_mode" 
  value="Y" onclick="rpttype_handler()" {if $expert_mode}{#checked#}{/if}/>
            __Expert Mode__</label>
          </td>
        </tr>
        <tr id="rptenddate1" style="visibility:hidden;">
          <td class="tooltip boxA" title="__repeat-end-date-help@T__" rowspan="3">
            <label>__Ending__:</label></td>
          <td colspan="2" class="boxT boxR">
            <input type="radio" name="rpt_end_use" id="rpt_untilf" value="f" {if !  $rpt_end  && ! $rpt_count}{#checked#}{/if} onclick="toggle_until()" />
            <label for="rpt_untilf">__Forever__</label>
          </td>
        </tr>
        <tr id="rptenddate2" style="visibility:hidden;">
          <td><input type="radio" name="rpt_end_use" id="rpt_untilu" value="u" {if $rpt_end}{#checked#}{/if} onclick="toggle_until()" />
            &nbsp;
            <label for="rpt_untilu">__Use end date__</label>
          </td>
          <td class="boxR"><span class="end_day_selection" id="rpt_end_day_select">
          <input type="text" name="rpt_end_date" id="rpt_end_date" value="{$rpt_end_date|date_to_str:datepicker}" onclick="lcs(this, event)" /></span> <br />
            {time_selection prefix='rpt_'  time=$rpt_end_time}</td>
        </tr>
        <tr id="rptenddate3" style="visibility:hidden;">
          <td class="boxB"><input type="radio" name="rpt_end_use" id="rpt_untilc" value="c" {if $rpt_count}{#checked#}{/if} onclick="toggle_until()" />
            &nbsp;
            <label for="rpt_untilc">__Number of times__</label>
          </td>
          <td class="boxR boxB"><input type="text" name="rpt_count" id="rpt_count" size="4" maxlength="4" value="{$rpt_count}" />
          </td>
        </tr>
        <tr id="rptfreq" style="visibility:hidden;" title="__repeat-frequency-help@T__">
          <td class="tooltip">
            <label>__Frequency__:</label>
          </td>
          <td colspan="2"><input type="text" name="rpt_freq" id="rpt_freq" size="4" maxlength="4" value="{$rpt_freq}" />
            &nbsp;&nbsp;&nbsp;&nbsp;
            <label for="weekdays_only">
            <input type="checkbox" name="weekdays_only" id="weekdays_only" value="Y"
  {if $weekdays_only}{#checked#}{/if} />
            __Weekdays Only__</label>
            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; <span id="rptwkst">
            <select name="wkst" id="wkst">
              
   {foreach from=$WC->byday_names key=k item=v}
    
              <option value="{$k}" {if $wkst == $k}{#selected#}{/if}>{$v}</option>
              
   {/foreach}
   
            </select>
            &nbsp;&nbsp;
            <label for="rptwkst" >__Week Start__</label>
            </span> </td>
        </tr>
        <tr id="rptbydayextended" style="visibility:hidden;" title="__repeat-bydayextended-help@T__">
          <td class="tooltip boxA"><label>__ByDay__:</label></td>
          <td colspan="2" class="boxT boxR boxB">
            <input type="hidden" name="bydayList" id="bydayList" value="{$bydayStr}" />
            <input type="hidden" name="bymonthdayList" id="bymonthdayList"value="{$bymonthdayStr}" />
            <input type="hidden" name="bysetposList" id="bysetposList"value="{$bysetposStr}" />
            <table class="byxxx" cellpadding="2" cellspacing="2" border="1">
              <tr>
                <td></td>
                {foreach from=$WC->weekday_names key=k item=v}
                <th width="50"> <label >{$v}</label>
                </th>
                {/foreach} </tr>
              <tr>
                <th>&nbsp;__All__&nbsp;</th>
                {foreach from=$bydayAll key=k item=v}
                <td><input type="checkbox" name="bydayAll[]" id="{$k}" value="{$k}" {$v} />
                </td>
                {/foreach} </tr>
              <tr id="rptbydayln" style="visibility:hidden;"> {section loop=6 name=loop_ctr start=1}
                {assign var='loopcnt' value=$smarty.section.loop_ctr.index}
                {assign var='loopend' value=$loopcnt-6}
                <th><label>{$loopcnt}/{$loopend}</label></th>
                {section loop=7 name=loop_days}
                {assign var='loop_day' value=$smarty.section.loop_days.index}
                <td> {assign var="looploc" value=$loopcnt$loop_day}
                  <input type="button" class="tristate" name="byday" id="{$byday.$looploc.id}" value="{$byday.$looploc.value}" onclick="toggle_byday(this)" />
                </td>
                {/section} </tr>
              {if $loopcnt lt 5}
              <tr id="rptbydayln{$loopcnt}" style="visibility:hidden;"> {/if}
                {/section}
            </table></td>
        </tr>
        <tr id="rptbymonth" style="visibility:hidden;" title="__repeat-month-help@T__">
          <td class="tooltip boxA"><label> __ByMonth__:&nbsp;</label></td>
          <td colspan="2"  class="boxT boxR boxB"><table cellpadding="5" cellspacing="0">
              <tr> {foreach from=$bymonth key=k item=v name=bymonth}
                <td><label>
                  <input type="checkbox" name="bymonth[]" value="$k" 
           {$v.checked}/>
                  &nbsp;{$v.date}</label>
                </td>
                {if $smarty.foreach.bymonth.index == 5} </tr>
              <tr> {/if}
                {/foreach} </tr>
            </table></td>
        </tr>
        <tr  id="rptbysetpos" style="visibility:hidden;" title="__repeat-bysetpos-help@T__">
          <td class="tooltip boxA" id="BySetPoslabel"><label> __BySetPos__:&nbsp;</label></td>
          <td colspan="2" class="boxT boxR boxB"><table  class="byxxx" cellpadding="2" cellspacing="0" border="1" >
              <tr>
                <td></td>
                {section loop=10 name=rpt_bysetpos_lbl}
                <th width="37"><label >{$smarty.section.rpt_bysetpos_lbl.index}</label></th>
                {/section} </tr>
              <tr> {foreach from=$bysetpos key=k item=v}
                {if $k == 1 || $k == 11 || $k == 21}
                <th><label>&nbsp;{$k}-{$k+9}&nbsp;</label></th>
                {/if}
                {if $k == 31}
                <th><label>31</label></th>
                {/if}
                <td><input class="tristate" type="button" name="bysetpos" id="{$v.id}" value="{$v.value}" onclick="toggle_bysetpos(this)" />
                </td>
                {if $k%10 == 0} </tr>
              <tr> {/if}
                {/foreach} </tr>
            </table></td>
        </tr>
        <tr  id="rptbymonthdayextended" style="visibility:hidden;" title="__repeat-bymonthdayextended-help@T__">
          <td class="tooltip boxA" id="ByMonthDaylabel"><label> __ByMonthDay__:&nbsp;</label></td>
          <td colspan="2" class="boxT boxR boxB"><table class="byxxx" cellpadding="2" cellspacing="0" border="1" >
              <tr>
                <td></td>
                {section loop=11 name=rpt_bymonthday_lbl start=1}
                <th width="37"><label >{$smarty.section.rpt_bymonthday_lbl.index}</label></th>
                {/section} </tr>
              <tr> {foreach from=$bymonthday key=k item=v}
                {if $k == 1 || $k == 11 || $k == 21}
                <th><label>&nbsp;{$k}-{$k+9}&nbsp;</label></th>
                {/if}
                {if $k == 31}
                <th><label>31</label></th>
                {/if}
                <td><input class="tristate" type="button" name="bymonthday" id="{$v.id}" value="{$v.value}"
            onclick="toggle_bymonthday(this)" />
                </td>
                {if $k%10 == 0} </tr>
              <tr> {/if}
                {/foreach} </tr>
            </table></td>
        </tr>
        <tr id="rptbyweekno" style="visibility:hidden;" title="__repeat-byweekno-help@T__">
          <td class="tooltip">__ByWeekNo__:</td>
          <td colspan="2"><input type="text" name="byweekno" id="byweekno" size="50" maxlength="100" value="{$byweekno}" />
          </td>
        </tr>
        <tr id="rptbyyearday" style="visibility:hidden;" title="__repeat-byyearday-help@T__">
          <td class="tooltip">__ByYearDay__:</td>
          <td colspan="2"><input type="text" name="byyearday" id="byyearday" size="50" maxlength="100" value="{$byyearday}" />
          </td>
        </tr>
        <tr id="rptexceptions" style="visibility:visible;"  title="__repeat-exceptions-help@T__">
          <td class="tooltip boxA"><label>__Exclusions__<br />
            __Inclusions__:</label></td>
          <td colspan="2" class="boxT boxR boxB"><table border="0" width="250">
              <tr >
                <td colspan="2"><input type="text" name="except_date" id="except_date"value="{$cal_date|date_to_str:datepicker}" onclick="lcs(this, event)" /></td>
              </tr>
              <tr>
                <td align="right" valign="top" width="100">
                  <select name="exceptions[]" id="select_exceptions" multiple="multiple" style="visibility:{if $exceptions || $inclusions}visible{else}hidden{/if}" size="4" >            
         {section loop=$exceptions name=exception}
                    <option value="{$exceptions[exception]}">{$exceptions[exception]}</option>
         {/section} 
                    <option value="" {#disabled#}>&nbsp;</option>
                  </select>
                </td>
                <td valign="top">
                  <input  type="button" align="left" name="addException"  value="__Add Exception__" onclick="add_exception(0)" />
                  <br />
                  <input type="button" align="left" name="addInclusion"  value="__Add Inclusion__" onclick="add_exception(1)" />
                  <br />
                  <input type="button" align="left" name="delSelected"  value="__Delete Selected__" onclick="del_selected()" />
                </td>
              </tr>
            </table></td>
        </tr>
      </table>
      </fieldset>
    </div>
    <!-- End tabscontent_pete -->
    {/if}
    <!-- REMINDER INFO -->
    {if ! $s.DISABLE_REMINDER_FIELD} <a name="tabreminder"></a>
    <div id="tabscontent_reminder">
      <fieldset>
      <legend>__Reminders__</legend>
      <table border="0" cellspacing="0" cellpadding="3">
        <thead>
          <tr>
            <td class="tooltip"><label>__Send Reminder__:</label></td>
            <td colspan="3"><input type="hidden" name="rem_action" value="{$reminder.action}" />
              <input type="hidden" name="rem_last_sent" value="{$reminder.last_sent}" />
              <input type="hidden" name="rem_times_sent" value="{$reminder.times_sent}" />
              <label>
              <input type="radio" name="reminder" id="reminderYes" value="1" {if $rem_status}{#checked#}{/if} onclick="toggle_reminders()" />
              __Yes__</label>
              &nbsp;
              <label>
              <input type="radio" name="reminder" id="reminderNo" value="0" {if ! $rem_status}{#checked#}{/if} onclick="toggle_reminders()" />
              __No__</label></td>
          </tr>
        </thead>
        <tbody id="reminder_when">
          <tr>
            <td class="tooltip boxA" rowspan="6"><label>__When__:</label></td>
            <td class="boxT" width="20%"><label>
              <input type="radio" name="rem_when" id="rem_when_date" value="Y" {if $rem_use_date}{#checked#}{/if}  onclick="toggle_rem_when()" />
              __Use Date/Time__&nbsp;</label>
            </td>
            <td class="boxT boxR" nowrap="nowrap" colspan="2"><input type="text" name="reminder_date" id="reminder_date" value="{$reminder.date|date_to_str:datepicker}" onclick="lcs(this, event)" /></td>
          </tr>
          <tr>
            <td>&nbsp;</td>
            <td class="boxR"  colspan="2" nowrap="nowrap"> {time_selection prefix='reminder_' time=$reminder['time']} </td>
          </tr>
          <tr>
            <td class="boxR"  height="20" colspan="3">&nbsp;</td>
          </tr>
          <tr>
            <td><label>
              <input type="radio" name="rem_when" id="rem_when_offset" value="N" {if ! $rem_use_date}{#checked#}{/if} onclick="toggle_rem_when()" />
              __Use Offset__&nbsp;</label>
            </td>
            <td class="boxR" nowrap="nowrap" colspan="2"><label>
              <input type="text" size="2" id="rem_days" name="rem_days"
        value="{$rem_days}" />
              __days__</label>
              <label>
              <input type="text" size="2" id="rem_hours" name="rem_hours"
        value="{$rem_hours}" />
              __hours__</label>
              &nbsp;
              <label>
              <input type="text" size="2" id="rem_minutes" name="rem_minutes"
        value="{$rem_minutes}" />
              __minutes__</label>
            </td>
          </tr>
          <tr>
            <td>&nbsp;</td>
            <td><label>
              <input type="radio" name="rem_before" id="rem_beforeY" value="Y" 
        {if $rem_before}{#checked#}{/if}/>
              __Before__</label>
              &nbsp;</td>
            <td class="boxR"><label>
              <input type="radio" name="rem_before" id="rem_beforeN" value="N"
        {if ! $rem_before}{#checked#}{/if}/>
              __After__</label></td>
          </tr>
          <tr>
            <td class="boxB">&nbsp;</td>
            <td class="boxB"><label>
              <input type="radio" name="rem_related" id="rem_relatedS" value="S"
        {if $rem_related}{#checked#}{/if}/>
              __Start__</label>
              &nbsp;</td>
            <td  class="boxB boxR"><label>
              <input type="radio" name="rem_related" id="rem_relatedE" value="E"
        {if ! $rem_related}{#checked#}{/if}/>
              __End/Due__</label></td>
          </tr>
        </tbody>
        <tbody  id="reminder_repeat">
          <tr>
            <td class="tooltip boxA" rowspan="2"><label>__Repeat__:</label></td>
            <td class=" boxT">&nbsp;&nbsp;&nbsp;
              <label>__Times__</label></td>
            <td class="boxR boxT" colspan="2">
              <input type="text" size="2" id="rem_rep_count" name="rem_rep_count"
       value="{$rem_rep_count}" onchange="toggle_rem_rep();" /></td>
          </tr>
          <tr id="rem_repeats">
            <td class="boxB">&nbsp;&nbsp;&nbsp;
              <label>__Every__</label></td>
            <td class="boxR boxB" colspan="2"><label>
              <input type="text" size="2" id="rem_rep_days" name="rem_rep_days"
       value="{$rem_rep_days}" />
              __days__</label>
              <input type="text" size="2" id="rem_rep_hours" name="rem_rep_hours"
       value="{$rem_rep_hours}" />
              <label>__hours__</label>
              &nbsp;
              <input type="text" size="2" id="rem_rep_minutes" name="rem_rep_minutes"
       value="{$rem_rep_minutes}" />
              <label>__minutes__</label>
            </td>
          </tr>
        </tbody>
      </table>
      </fieldset>
    </div >
    <!-- End tabscontent_reminder -->
    {/if} </div>
  <!-- End tabscontent -->
  {$captcha}
  <table>
    <tr>
      <td><input type="button" value="__Save__" onclick="return validate_and_submit()" />
      </td>
      {if $eid}
      <td>
<input type="button" value="__Delete Entry__" onclick="return confirm('__ruSureEntry@D__');" /> 
{/if} </td>
    </tr>
  </table>
  {if $use_fckeditor}
<script type="text/javascript" src="includes/FCKeditor-2.0/fckeditor.js"></script>
<script type="text/javascript">
   var myFCKeditor = new FCKeditor( 'description' );
   myFCKeditor.BasePath = 'includes/FCKeditor-2.0/';
   myFCKeditor.ToolbarSet = 'Medium';
   myFCKeditor.Config['SkinPath'] = './skins/office2003/';
   myFCKeditor.ReplaceTextarea();
</script>
  {/if}
</form>
{include file="footer.tpl"}