 {include file="header.tpl"}
<script language="javascript" type="text/javascript">
  var user = "{$real_user}";
</script>
<h2>{if $eid}{'Edit Entry'|translate}{else}{'Add Entry'|translate}{/if}
  {$eType|translate}{generate_help_icon url='help_edit_entry.php'} <br />
  &nbsp;&nbsp;{$navFullname}
  {$navAdmin}
  {$navAssistant}</h2>
<form action="edit_entry_handler.php" method="post" name="editentryform" id="editentryform">
  <input type="hidden" name="eType" value="{$eType}" />
  {if  ! $copy}
  <input type="hidden" name="eid" value="{$eid}" />
  {/if}
  <input type="hidden" name="entry_changed" value="" />
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
      <legend>{'Details'|translate}</legend>
      <table border="0">
        <tr>
          <td style="width:14%;" class="tooltip" title="{'brief-description-help'|tooltip}"><label for="entry_brief">{'Brief Description'|translate}:</label></td>
          <td colspan="4"><input type="text" name="name" id="entry_brief" size="25" value="{$name|htmlspecialchars}" /></td>
        </tr>
        <tr>
          <td class="tooltip aligntop" title="{'full-description-help'|tooltip}"><label for="entry_full">{'Full Description'|translate}:</label></td>
          <td   colspan="4"><textarea name="description" id="entry_full" {$textareasize}>{$description|htmlspecialchars}</textarea></td>
          {if ! $s.DISABLE_ACCESS_FIELD || ! $s.DISABLE_PRIORITY_FIELD}
        <tr> {if ! $s.DISABLE_ACCESS_FIELD}
          <td class="tooltip" title="{'access-help'|tooltip}"><label for="entry_access">{'Access'|translate}:</label></td>
          <td><select name="access" id="entry_access">
              <option value="P" {if $access == 'P'}
        {#selected#}{/if} >{'Public'|translate}</option>
              <option value="R" {if $access == 'R'}
        {#selected#}{/if}>{'Private'|translate}</option>
              <option value="C" {if $access == 'C'}
        {#selected#}{/if}>{'Confidential'|translate}</option>
            </select>
          </td>
          {/if}
          {if ! $s.DISABLE_PRIORITY_FIELD}
          <td class="tooltip" title="{'priority-help'|tooltip}" {if ! $s.DISABLE_ACCESS_FIELD}align="right"{/if}><label for="entry_prio">{'Priority'|translate}:&nbsp;</label></td>
          <td><select name="priority" id="entry_prio">
              
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
          <td class="tooltip" title="{'category-help'|tooltip}"><label for="entry_categories">{'Category'|translate}:<br />
            </label>
          </td>
          <td colspan="4">
					  <input  readonly="readonly" type="text" name="catnames" id="entry_categories" value="{$catNames}"  size="30" onclick="editCats(event, 'editentryform')"/>
            <input type="button" value="{'Edit'|translate}" onclick="editCats(event, 'editentryform')" />
            <input  type="hidden" name="cat_id"  id="cat_id" value="{$catList}" />
          </td>
        </tr>
        {/if}
        
        {if $eType eq 'task'}
        <tr id="completed">
          <td class="tooltip" title="{'completed-help'|tooltip}"><label for="task_percent">{'Date Completed'|translate}:&nbsp;</label></td>
          <td colspan="4">{date_selection prefix='completed_' date=$completed}</td>
        </tr>
        <tr>
          <td class="tooltip" title="{'percent-help'|tooltip}"><label for="task_percent">{'Percent Complete'|translate}:&nbsp;</label></td>
          <td colspan="4"><select name="percent" id="task_percent" onchange="completed_handler()">
              
    {section name=tpercent loop=100 step=10} 
      
              <option value="{$smarty.section.tpercent.index}" {if $task_percent == $smarty.section.tpercent.index}{#selected#}{/if}>{$smarty.section.tpercent.index}</option>
              
    {/section}
    
            </select></td>
        </tr>
        {if $overall_percent}
        <tr>
          <td colspan="4"><table width="100%" border="0" cellpadding="2" cellspacing="5">
              <tr>
                <td colspan="2">{'All Percentages'|translate}</td>
              </tr>
              {foreach from=$overall_percent key=k item=v}
              <tr>
                <td>{$v.fullname}</td>
                <td>{$v.percent}</td>
              </tr>
              {/foreach}
            </table></td>
        </tr>
        <input type="hidden" name="others_complete" value="{$others_complete}" />
        {/if}
        {/if}
        {if  ! $s.DISABLE_LOCATION_FIELD}
        <tr>
          <td class="tooltip" title="{'location-help'|tooltip}"><label for="entry_location">{'Location'|translate}:</label></td>
          <td colspan="4"><input type="text" name="location" id="entry_location" size="55" value="{$location|htmlspecialchars}" />
          </td>
        </tr>
        {/if}
        {if ! $s.DISABLE_URL_FIELD}
        <tr>
          <td class="tooltip" title="{'url-help'|tooltip}"><label for="entry_url">{'URL'|translate}:</label></td>
          <td colspan="4"><input type="text" name="entry_url" id="entry_url" size="100" 
   value="{$cal_url|htmlspecialchars}" />
          </td>
        </tr>
        {/if}
        <tr>
          <td class="tooltip" title="{'date-help'|tooltip}"><label> {if $eType == 'task'}{'Start Date'|translate}{else}{'Date'|translate}{/if}:</label>
          </td>
          <td colspan="4">{date_selection date=$cal_date} </td>
        </tr>
        {if $eType != 'task'}
        <tr>
          <td>&nbsp;</td>
          <td colspan="4"><select name="timetype" onchange="timetype_handler()">
              <option value="U" {if $isUntimed}{#selected#}{/if}>{'Untimed event'|translate}</option>
              <option value="T" {if $isTimed}{#selected#}{/if}>{'Timed event'|translate}</option>
              <option value="A" {if $allday}{#selected#}{/if}>{'All day event'|translate}</option>
            </select>
          </td>
        </tr>
        {if $TZ_notice}
        <tr id="timezonenotice">
          <td class="tooltip" title="{'Time entered here is based on your Timezone'|tooltip}"> {'Timezone Offset'|translate}:</td>
          <td colspan="4">{$TZ_notice}</td>
        </tr>
        {/if}
        <tr id="timeentrystart" style="visibility:hidden;">
          <td class="tooltip" title="{'time-help'|tooltip}"><label> {'Time'|translate}:</label></td>
          <td colspan="4">{time_selection prefix='entry_'  time=$cal_time}
            
            {if $p.TIMED_EVT_LEN != 'E'} </td>
        </tr>
        <tr id="timeentryduration" style="visibility:hidden;">
          <td class="tooltip" title="{'duration-help'|tooltip}"><label>{'Duration'|translate}:&nbsp;</label>
          </td>
          <td colspan="4"><input type="text" name="duration_h" id="duration_h" size="2" maxlength="2" value="{if ! $allday}{$dur_h}{/if}" />
            :
            <input type="text" name="duration_m" id="duration_m" size="2" maxlength="2" value="{if ! $allday}{$dur_m}{/if}" />
            &nbsp;(
            <label for="duration_h"> {'hours'|translate}</label>
            :
            <label for="duration_m"> {'minutes'|translate}</label>
            ) </td>
        </tr>
        {else} <span id="timeentryend" class="tooltip" title="{'end-time-help'|tooltip}">&nbsp;-&nbsp;{time_selection prefix='end_' time=$end_time}</span>
        </td>
        
        </tr>
        
        {/if}
        {else}
        <tr>
          <td class="tooltip" title="{'time-help'|tooltip}"> {'Start Time'|translate}:</td>
          <td colspan="4">{time_selection prefix='entry_' time=$cal_time} </td>
        </tr>
        <tr>
          <td class="tooltip" title="{'date-help'|tooltip}">{'Due Date'|translate}:</td>
          <td colspan="4">{date_selection prefix='due_' date=$due_date}</td>
        </tr>
        <tr>
          <td class="tooltip" title="{'time-help'|tooltip}">{'Due Time'|translate}:</td>
          <td colspan="4">{time_selection prefix='due_' time=$due_time}</td>
        </tr>
        {/if}
      </table>
      </fieldset>
      <!-- Site Extras -->
      {if  $site_extracnt}
      <div>
        <fieldset>
        <legend>{'Site Extras'|translate}</legend>
        <table>
          {foreach from=$site_extras key=k item=v}
          <tr>
            <td class="aligntop bold"> {if $v.extra_type == $smarty.const.EXTRA_MULTILINETEXT} <br />
              {/if}{$v.extra_descr|translate}:</td>
            <td> {if $v.extra_type == $smarty.const.EXTRA_URL}
              <input type="text" size="50" name="{$v.extra_name}" value="{$v.extra_name.cal_data|htmlspecialchars}" />
              {elseif $v.extra_type == $smarty.const.EXTRA_EMAIL}
              <input type="text" size="30" name="{$v.extra_name}" 
          value="{$extras[$v.extra_name.cal_data]}" />
              {elseif $v.extra_type == $smarty.const.EXTRA_DATE}
              {if $extras[$v.extra_name.cal_date]}
              {date_selection prefix=$v.extra_name date=$extras[$v.extra_name.cal_date]}
              {else}
              {date_selection prefix=$v.extra_name date=$cal_date}
              {/if}
              {elseif $v.extra_type == $smarty.const.EXTRA_TEXT}
              <input type="text" size="$v.extra_arg1" name="$v.extra_name}" 
       value="$extras[$v.extra_name.cal_data']|htmlspecialchars}" />
              {elseif $v.extra_type == $smarty.const.EXTRA_MULTILINETEXT}
              <textarea rows="$v.extra_arg2" cols="$v.extra_arg1" 
      name="{$v.extra_name}">{$extras[$v.extra_name.cal_data]|htmlspecialchars}</textarea>
              {elseif $v.extra_type == $smarty.const.EXTRA_USER}
              <select name="{$v.extra_name}">
                <option value="">{'None'|translate}</option>
                
    {foreach from=$userlist key=uk item=uv}
      
                <option value="{$uv.cal_login}" 
      {if $uv.cal_login == $extras[$extra_name.cal_data]}{#selected#}{/if}>{$uv.cal_fullname}</option>
                
    {/foreach}
    
              </select>
              {elseif $v.extra_type == $smarty.const.EXTRA_SELECTLIST}
              <select name="{$v.extra_name}{$v.isMultiple}" {$v.multiselect}>
                
     {foreach name=arglcnt from=$v.extra_arg1cnt key=ak item=av}
        
                <option value="{$av.extra_arg1}" 
          {if $av.extra_arg2 === 0 && 
            $av.extra_arg1 == $extras[$v.extra_name.cal_data]}{#selected#}
          {elseif $av.extra_arg2 gt 0 && in_array ( $av.extra_arg1, $extraSelectArr )}
            {#selected#}
          {elseif $smarty.foreach.arglcnt.index == 0}
            {#selected#}
          {/if}>
                {$av.extra_arg1}
                </option>
                
      {/foreach}
     
              </select>
              {elseif $v.extra_type == $smarty.const.EXTRA_RADIO}  
              {print_radio variable=$v.extra_name  vars=$v.extra_arg1 defIdx=$v.defIdx}
              {elseif $v.extra_type == $smarty.const.EXTRA_CHECKBOX}
              {print_checkbox  variable=$v.extra_name vars=$v.extra_arg1 defIdx=$defIdx}
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
      <legend>{'Participants'|translate}</legend>
      <table>
        <tr title="{'participants-help'|tooltip}">
          <td class="tooltipselect"><label for="entry_part">{'Participants'|translate}:</label></td>
          <td><select name="participants[]" id="entry_part" size="{$size}" multiple="multiple">
              
  {foreach from=$userlist key=k item=v}
    
              <option value="{$v.cal_login_id}" {$v.selected}>{$v.cal_fullname}</option>
              
  {/foreach}
   
            </select>
            {if $s.GROUPS_ENABLED}
            <input type="button" onclick="selectUsers()" value="{'Select'|translate}..." />
            {/if}
            <input type="button" onclick="showSchedule()" value="{'Availability'|translate}..." />
          </td>
        </tr>
        {if $s.ALLOW_EXTERNAL_USERS}
        <tr title="{'external-participants-help'|tooltip}">
          <td class="tooltip aligntop"><label for="entry_extpart">{'External Participants'|translate}:</label></td>
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
      <legend>{'Repeat'|translate}</legend>
      <table border="0" cellspacing="0" cellpadding="3">
        <tr>
          <td class="tooltip" title="{'repeat-type-help'|tooltip}"><label for="rpttype">{'Type'|translate}:</label></td>
          <td colspan="2"><select name="rpt_type" id="rpttype" onchange="rpttype_handler();rpttype_weekly()">
              <option value="none" 
   {if $rpt_type == 'none'}{#selected#}{/if}>{'None'|translate}</option>
              <option value="daily"
   {if $rpt_type == 'daily'}{#selected#}{/if}>{'Daily'|translate}</option>
              <option value="weekly"
   {if $rpt_type =='weekly'}{#selected#}{/if}>{'Weekly'|translate}</option>
              <option value="monthlyByDay"
   {if $rpt_type =='monthlyByDay'}{#selected#}{/if}>{$monthlyStr}({'by day'|translate})</option>
              <option value="monthlyByDate"
   {if $rpt_type =='monthlyByDate'}{#selected#}{/if}>{$monthlyStr}({'by date'|translate})</option>
              <option value="monthlyBySetPos"
   {if $rpt_type == 'monthlyBySetPos'}{#selected#}{/if}>{$monthlyStr}({'by position'|translate})</option>
              <option value="yearly"
   {if $rpt_type   == 'yearly'}{#selected#}{/if}>{'Yearly'|translate}</option>
              <option value="manual"
   {if $rpt_type == 'manual'}{#selected#}{/if}>{'Manual'|translate}</option>
            </select>
            &nbsp;&nbsp;&nbsp;
            <label id ="rpt_mode">
            <input type="checkbox" name="rptmode"  id="rptmode" 
  value="y" onclick="rpttype_handler()" {if $expert_mode}{#checked#}{/if}/>
            {'Expert Mode'|translate}</label>
          </td>
        </tr>
        <tr id="rptenddate1" style="visibility:hidden;">
          <td class="tooltip boxall" title="{'repeat-end-date-help'|tooltip}" rowspan="3"><label for="rpt_day">{'Ending'|translate}:</label></td>
          <td colspan="2" class="boxtop boxright"><input  type="radio" name="rpt_end_use" id="rpt_untilf" value="f" {if !  $rpt_end  && ! $rpt_count}{#checked#}{/if} onclick="toggle_until()" />
            <label for="rpt_untilf">{'Forever'|translate}</label>
          </td>
        </tr>
        <tr id="rptenddate2" style="visibility:hidden;">
          <td><input  type="radio" name="rpt_end_use" id="rpt_untilu" value="u" {if $rpt_end}{#checked#}{/if} onclick="toggle_until()" />
            &nbsp;
            <label for="rpt_untilu">{'Use end date'|translate}</label>
          </td>
          <td class="boxright"><span class="end_day_selection" id="rpt_end_day_select">{date_selection prefix='rpt_' date=$rpt_end_date}</span> <br />
            {time_selection prefix='rpt_'  time=$rpt_end_time}</td>
        </tr>
        <tr id="rptenddate3" style="visibility:hidden;">
          <td class="boxbottom"><input type="radio" name="rpt_end_use" id="rpt_untilc" value="c" {if $rpt_count}{#checked#}{/if} onclick="toggle_until()" />
            &nbsp;
            <label for="rpt_untilc">{'Number of times'|translate}</label>
          </td>
          <td class="boxright boxbottom"><input type="text" name="rpt_count" id="rpt_count" size="4" maxlength="4" value="{$rpt_count}" />
          </td>
        </tr>
        <tr id="rptfreq" style="visibility:hidden;" title="{'repeat-frequency-help'|tooltip}">
          <td class="tooltip"><label for="entry_freq">{'Frequency'|translate}:</label></td>
          <td colspan="2"><input type="text" name="rpt_freq" id="entry_freq" size="4" maxlength="4" value="{$rpt_freq}" />
            &nbsp;&nbsp;&nbsp;&nbsp;
            <label id="weekdays_only">
            <input  type="checkbox" name="weekdays_only" value="y"
  {if $weekdays_only}{#checked#}{/if} />
            {'Weekdays Only'|translate}</label>
            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; <span id="rptwkst">
            <select   name="wkst">
              
   {foreach from=$WC->byday_names key=k item=v}
    
              <option value="{$k}" {if $wkst == $k}{#selected#}{/if}>{$v|translate}</option>
              
   {/foreach}
   
            </select>
            &nbsp;&nbsp;
            <label for="rptwkst" >{'Week Start'|translate}</label>
            </span> </td>
        </tr>
        <tr id="rptbydayextended" style="visibility:hidden;" title="{'repeat-bydayextended-help'|tooltip}">
          <td class="tooltip boxall"><label>{'ByDay'|translate}:</label></td>
          <td colspan="2" class="boxtop boxright boxbottom"><input type="hidden" name="bydayList" value="{$bydayStr}" />
            <input type="hidden" name="bymonthdayList" value="{$bymonthdayStr}" />
            <input type="hidden" name="bysetposList" value="{$bysetposStr}" />
            <table class="byxxx" cellpadding="2" cellspacing="2" border="1">
              <tr>
                <td></td>
                {foreach from=$WC->weekday_names key=k item=v}
                <th width="50px"> <label >{$v|translate}</label>
                </th>
                {/foreach} </tr>
              <tr>
                <th>&nbsp;{'All'|translate}&nbsp;</th>
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
                  <input  class="tristate" type="button" name="byday" id="{$byday.$looploc.id}" value="{$byday.$looploc.value}" onclick="toggle_byday(this)" />
                </td>
                {/section} </tr>
              {if $loopcnt lt 6}
              <tr id="rptbydayln{$loopcnt}" style="visibility:hidden;"> {/if}
                {/section}
            </table></td>
        </tr>
        <tr id="rptbymonth" style="visibility:hidden;" title="{'repeat-month-help'|tooltip}">
          <td class="tooltip boxall"><label> {'ByMonth'|translate}:&nbsp;</label></td>
          <td colspan="2"  class="boxtop boxright boxbottom"><table cellpadding="5" cellspacing="0">
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
        <tr  id="rptbysetpos" style="visibility:hidden;" title="{'repeat-bysetpos-help'|tooltip}">
          <td class="tooltip boxall" id="BySetPoslabel"><label> {'BySetPos'|translate}:&nbsp;</label></td>
          <td colspan="2" class="boxtop boxright boxbottom"><table  class="byxxx" cellpadding="2" cellspacing="0" border="1" >
              <tr>
                <td></td>
                {section loop=10 name=rpt_bysetpos_lbl}
                <th width="37px"><label >{$smarty.section.rpt_bysetpos_lbl.index}</label></th>
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
        <tr  id="rptbymonthdayextended" style="visibility:hidden;" title="{'repeat-bymonthdayextended-help'|tooltip}">
          <td class="tooltip boxall" id="ByMonthDaylabel"><label> {'ByMonthDay'|translate}:&nbsp;</label></td>
          <td colspan="2" class="boxtop boxright boxbottom"><table class="byxxx" cellpadding="2" cellspacing="0" border="1" >
              <tr>
                <td></td>
                {section loop=11 name=rpt_bymonthday_lbl start=1}
                <th width="37px"><label >{$smarty.section.rpt_bymonthday_lbl.index}</label></th>
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
        <tr id="rptbyweekno" style="visibility:hidden;" title="{'repeat-byweekno-help'|tooltip}">
          <td class="tooltip">{'ByWeekNo'|translate}:</td>
          <td colspan="2"><input type="text" name="byweekno" id="byweekno" size="50" maxlength="100" value="{$byweekno}" />
          </td>
        </tr>
        <tr id="rptbyyearday" style="visibility:hidden;" title="{'repeat-byyearday-help'|tooltip}">
          <td class="tooltip">{'ByYearDay'|translate}:</td>
          <td colspan="2"><input type="text" name="byyearday" id="byyearday" size="50" maxlength="100" value="{$byyearday}" />
          </td>
        </tr>
        <tr id="rptexceptions" style="visibility:visible;"  title="{'repeat-exceptions-help'|tooltip}">
          <td class="tooltip boxall"><label>{'Exclusions'|translate}<br />
            {'Inclusions'|translate}:</label></td>
          <td colspan="2" class="boxtop boxright boxbottom"><table border="0" width="250px">
              <tr >
                <td colspan="2">{date_selection prefix='except_' date=$rpt_end_date} </td>
              </tr>
              <tr>
                <td align="right" valign="top" width="100"><select id="select_exceptions"  name="exceptions[]"  multiple="multiple" style="visibility:{if $exceptions || $inclusions}visible{else}hidden{/if}" size="4" >
                    
         {section loop=$exceptions name=exception}
           
                    <option value="{$exceptions[exception]}">{$exceptions[exception]}</option>
                    
         {/section} 
         
                  </select>
                </td>
                <td valign="top"><input  align="left" type="button" name="addException"  value="{'Add Exception'|translate}" onclick="add_exception(0)" />
                  <br />
                  <input  align="left" type="button" name="addInclusion"  value="{'Add Inclusion'|translate}" onclick="add_exception(1)" />
                  <br />
                  <input  align="left" type="button" name="delSelected"  value="{'Delete Selected'|translate}" onclick="del_selected()" />
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
      <legend>{'Reminders'|translate}</legend>
      <table border="0" cellspacing="0" cellpadding="3">
        <thead>
          <tr>
            <td class="tooltip"><label>{'Send Reminder'|translate}:</label></td>
            <td colspan="3"><input type="hidden" name="rem_action" value="{$reminder.action}" />
              <input type="hidden" name="rem_last_sent" value="{$reminder.last_sent}" />
              <input type="hidden" name="rem_times_sent" value="{$reminder.times_sent}" />
              <label>
              <input type="radio" name="reminder" id="reminderYes" value="1" {if $rem_status}{#checked#}{/if} onclick="toggle_reminders()" />
              {'Yes'|translate}</label>
              &nbsp;
              <label>
              <input type="radio" name="reminder" id="reminderNo" value="0" {if ! $rem_status}{#checked#}{/if} onclick="toggle_reminders()" />
              {'No'|translate}</label></td>
          </tr>
        </thead>
        <tbody id="reminder_when">
          <tr>
            <td class="tooltip boxall" rowspan="6"><label>{'When'|translate}:</label></td>
            <td class="boxtop" width="20%"><label>
              <input  type="radio" name="rem_when" id="rem_when_date" value="Y" {if $rem_use_date}{#checked#}{/if}  onclick="toggle_rem_when()" />
              {'Use Date/Time'|translate}&nbsp;</label>
            </td>
            <td class="boxtop boxright" nowrap="nowrap" colspan="2"> {date_selection prefix='reminder_' date=$reminder['date']} </td>
          </tr>
          <tr>
            <td>&nbsp;</td>
            <td class="boxright"  colspan="2" nowrap="nowrap"> {time_selection prefix='reminder_' time=$reminder['time']} </td>
          </tr>
          <tr>
            <td class="boxright"  height="20px" colspan="3">&nbsp;</td>
          </tr>
          <tr>
            <td><label>
              <input  type="radio" name="rem_when" id="rem_when_offset" value="N" {if ! $rem_use_date}{#checked#}{/if} onclick="toggle_rem_when()" />
              {'Use Offset'|translate}&nbsp;</label>
            </td>
            <td class="boxright" nowrap="nowrap" colspan="2"><label>
              <input type="text" size="2" name="rem_days"
        value="{$rem_days}" />
              {'days'|translate}</label>
              <label>
              <input type="text" size="2" name="rem_hours"
        value="{$rem_hours}" />
              {'hours'|translate}</label>
              &nbsp;
              <label>
              <input type="text" size="2" name="rem_minutes"
        value="{$rem_minutes}" />
              {'minutes'|translate}</label>
            </td>
          </tr>
          <tr>
            <td>&nbsp;</td>
            <td><label>
              <input type="radio" name="rem_before" id="rem_beforeY" value="Y" 
        {if $rem_before}{#checked#}{/if}/>
              {'Before'|translate}</label>
              &nbsp;</td>
            <td class="boxright"><label>
              <input type="radio" name="rem_before" id="rem_beforeN" value="N"
        {if ! $rem_before}{#checked#}{/if}/>
              {'After'|translate}</label></td>
          </tr>
          <tr>
            <td class="boxbottom">&nbsp;</td>
            <td class="boxbottom"><label>
              <input type="radio" name="rem_related" id="rem_relatedS" value="S"
        {if $rem_related}{#checked#}{/if}/>
              {'Start'|translate}</label>
              &nbsp;</td>
            <td  class="boxbottom boxright"><label>
              <input type="radio" name="rem_related" id="rem_relatedE" value="E"
        {if ! $rem_related}{#checked#}{/if}/>
              {'End/Due'|translate}</label></td>
          </tr>
        </tbody>
        <tbody  id="reminder_repeat">
          <tr>
            <td class="tooltip boxall" rowspan="2"><label>{'Repeat'|translate}:</label></td>
            <td class=" boxtop">&nbsp;&nbsp;&nbsp;
              <label>{'Times'|translate}</label></td>
            <td class="boxright boxtop" colspan="2"><input type="text" size="2" name="rem_rep_count"
       value="{$rem_rep_count}" onchange="toggle_rem_rep();" /></td>
          </tr>
          <tr id="rem_repeats">
            <td class="boxbottom">&nbsp;&nbsp;&nbsp;
              <label>{'Every'|translate}</label></td>
            <td class="boxright boxbottom" colspan="2"><label>
              <input type="text" size="2" name="rem_rep_days"
       value="{$rem_rep_days}" />
              {'days'|translate}</label>
              <input type="text" size="2" name="rem_rep_hours"
       value="{$rem_rep_hours}" />
              <label>{'hours'|translate}</label>
              &nbsp;
              <input type="text" size="2" name="rem_rep_minutes"
       value="{$rem_rep_minutes}" />
              <label>{'minutes'|translate}</label>
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
      <td><input type="button" value="{'Save'|translate}" onclick="return validate_and_submit()" />
      </td>
    </tr>
  </table>
  <input type="hidden" name="participant_list" value="" />
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
{if $eid}
<input type="button" value="{'Delete Entry'|translate}" onclick="return confirm('{$confirmStr}');" /> 
{/if}
{include file="footer.tpl"}