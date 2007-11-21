 {include file="header.tpl"}
<h2>__View Entry__
{$eType_label}{generate_help_icon url='help_edit_entry.php'}
<br />&nbsp;&nbsp;{$navFullname}
{$navAdmin}
{$navAssistant}</h2>

<table width="100%">
  <tr>
    <td class="alignT bold" width="10%">__Description__:</td>
    <td>{$description}</td>
  </tr>
	
{if $location}
  <tr>
     <td class="alignT bold">__Location__:</td>
     <td>{$location}</td>
	</tr>
{/if}

{if $url}
  <tr>
    <td class="alignT bold">__URL__:</td>
    <td>{$url}</td>
  <tr>
{/if}

{if $status}
  <tr>
    <td class="alignT bold">__Status__:</td>
    <td>{$status}</td>
  </tr>
{/if}
  <tr>
    <td class="alignT bold">{if $eType == 'task'}__Start Date__{else}__Date__{/if}:</td>
    <td>{$display_date|date_to_str}</td>
	</tr>
{if $eType == 'task'}
  {if $itemTime >= 0 }
  <tr>
    <td class="alignT bold">__Start Time__:</td>
    <td>{$itemDate|display_time:2}</td>
  </tr>
	{/if}
  <tr>
    <td class="alignT bold">__Due Date__:</td>
     <td>{$itemDueDate|date_to_str}</td>
  </tr>

	{if $itemCompleted}
  <tr>
    <td class="alignT bold">__Completed__:</td>
    <td>{$itemCompleted|date_to_str}</td>
  </tr>
	{/if}
{else if $timeStr}
  <tr>
    <td class="alignT bold">__Time__:</td>
    <td>{$timeStr}</td>
   </tr>
{/if}
{if $event_repeats}
  <tr>
    <td class="alignT bold">__Repeat Type__:</td>
    <td>{$recurrenceStr}</td>
  </tr>
{/if}
{if $durationStr}
  <tr>
    <td class="alignT bold">__Duration__:</td>
    <td>{$durationStr}</td>
  </tr>
{/if}

{if $itemPriority}
  <tr>
    <td class="alignT bold">__Priority__:</td>
    <td>{$itemPriority}</td>
  </tr>
{/if}		
			
{if $itemAccess}
  <tr>
    <td class="alignT bold">__Access__:</td>
    <td>{$itemAccess}</td>
  </tr>
{/if}

{if $itemCategory}			
  <tr>
    <td class="alignT bold">__Category__:</td>
    <td>{$itemCategory}</td>
  </tr>
{/if}


{if $createby_fullname}
  <tr>
    <td class="alignT bold">__Created by__:</td>
    <td>
    {if $can_email}
      <a href="mailto:{$email_addr}?subject={$subject}">{$createby_fullname}</a>
		{else}
      {$createby_fullname}
		{/if}{$proxy_fullname}</td>
  </tr>
{/if}

  <tr>
    <td class="alignT bold">__Updated__:</td>
    <td>{$itemModDate}</td>
  </tr>

{if $reminder}
  <tr>
    <td class="alignT bold">__Send Reminder__:</td>
    <td>{$reminder}</td>
  </tr>
{/if}

<!--Add site Extras Here-->
{foreach from=$site_extras key=k item=v}
  {if $v[5] & $smarty.const.EXTRA_DISPLAY_VIEW}
	{assign var=v0 value=$v.0}
  <tr>
    <td class="alignT bold">{$v[1]}:</td>
    <td>
    {if $v[2] == $smarty.const.EXTRA_URL}
      {if $extras.$v0.cal_data}<a href="{$extras.$v0.cal_data}" 
			{if $v[3]}target="{$v[3]}"{/if}>{$extras.$v0.cal_data}</a>{/if}
    {elseif $v[2] == $smarty.const.EXTRA_EMAIL}
      {if $extras.$v0.cal_data}<a href="mailto:{$extras.$v0.cal_data}?subject={$subject}">{$extras.$v[0].cal_data}</a>{/if}
    {elseif $v[2] == $smarty.const.EXTRA_DATE}
      {if $extras.$v0.cal_date > 0}{$extras.$v0.cal_date|date_to_str}{/if}
    {elseif $v[2] == $smarty.const.EXTRA_TEXT || $v[2] == $smarty.const.EXTRA_MULTILINETEXT}
      {$extras.$v0.cal_data|nl2br}
    {elseif $v[2] == $smarty.const.EXTRA_USER || $v[2] == $smarty.const.EXTRA_SELECTLIST 
      || $v[2] == $smarty.const.EXTRA_CHECKBOX}
      {$extras.$v0.cal_data}
    {elseif $v[2] == $smarty.const.EXTRA_RADIO}
      {$v[3].$extras.$v0.cal_data}
		{/if}

    </td>
  </tr>
  {/if}
{/foreach}

{if $show_participants}
  <tr>
    <td class="alignT bold">__Participants__:</td>
    <td>

  {if $eType == 'task'}
    <table border="1" width="80%" cellspacing="0" cellpadding="1">
      <th align="center">__Participants__</th>
      <th align="center" colspan="2">__Percentage Complete__</th>
     {foreach from=$percentage key=k item=v}
      <tr>
        <td width="30%">
        {if $v.email}
          <a href="mailto:{$v.email}?subject={$subject}">&nbsp;{$v.fullname}</a>
				{else}
          {$v.fullname}
				{/if}
        </td>
        <td width="5%" align="center">{$v.percentage}%</td>
        <td width="65%"><img src="images/pix.gif" width="{$v.percentage}%" height="10">
          <img src="images/spacer.gif" width="{$v.spacer}" height="10"></td>
      </tr>
     {/foreach}
    </table>
  {else}
	  {foreach from=$approved key=k item=v}
      {if $v.email}
			  <a href="mailto:{$v.email}?subject={$subject}">{$v.fullname}</a>
      {else}
        {$v.fullname}
      {/if}
			<br />
		{/foreach}
  {/if}
	
  {if $ext_users}
	  {foreach from=$ext_users key=k item=v}
       {$k} ( {$externUserStr} )<br />
    {/foreach}
	{/if}
  {if $waiting} 
	 {foreach from=$waiting key=k item=v}
      {if $v.email}
        <a href="mailto:{$v.email}?subject={$subject}">{$v.fullname}</a>
      {else}
        {$v.fullname}
      {/if}
      (?)<br />
    {/foreach}
	{/if}	
	{if $rejected }
		{foreach from=$rejected key=k item=v}
      <strike>
			 {if $v.email}
        <a href="mailto:{$v.email}?subject={$subject}">{$v.fullname}</a>
			 {else}
        {$v.fullname}
			 {/if}
			</strike> (__Rejected__)<br />
    {/foreach}
  {/if}
   </td>
  </tr>
{/if} 


{if $eType == 'task'}
  {if $canUpdatePercentage}
  <tr>
    <td class="alignT bold">
      <form action="view_entry.php?eid={$eid}" method="post" name="setpercentage">
        <input type="hidden" name="others_complete" value="{$others_complete}" />__Update Task Percentage__
		</td>
    <td>
        <select name="upercent" id="task_percent">
		{section loop=110 step=10 name=task_percent}
		  {assign var=index value=$smarty.section.task_percent.index}
          <option value="{$index}" {if $login_percentage == $index}{#seclected#}{/if}>{$index}</option>
    {/section}
        </select>&nbsp;
        <input type="submit" value="__Update__" />
      </form>
    </td>
  <tr>
  {/if}
{/if}

{if $attachmentsEnabled}
  <tr>
    <td class="alignT bold">__Attachments__:</td>
    <td>
  {if $attList}
	 {foreach from=$attList key=k item=v}
     {$v.Summary}
     {if $can_edit}
      [<a href="docdel.php?blid={$v.id}" onclick="return confirm('__ruSureAttach@D__);">__Delete__</a>]
			{/if}
			<br />
    {/foreach}
  {else}
     __None__<br />
	{/if}
    </td>
  </tr>
{/if}

{if $commentsEnabled}
  <tr>
    <td class="alignT bold">__Comments__:</td>
    <td>
   {if $comment_text}
      <input id="showbutton" type="button" value="__Show__" onclick="showComments();" />
      <input id="hidebutton" type="button" value="__Hide__" onclick="hideComments();" /><br />
      <div id="comtext">{$comment_text}</div>
  {else}
     __None__<br />
	{/if}
<script language="JavaScript" type="text/javascript">
<!-- <![CDATA[
function showComments () {ldelim}
  var x = document.getElementById ( "comtext" )
  if ( x )
    x.style.display = "block";
  x = document.getElementById ( "showbutton" )
  if ( x )
    x.style.display = "none";
  x = document.getElementById ( "hidebutton" )
  if ( x )
    x.style.display = "block";
{rdelim}
function hideComments () {ldelim}
  var x = document.getElementById ( "comtext" )
  if ( x )
    x.style.display = "none";
  x = document.getElementById ( "showbutton" )
  if ( x )
    x.style.display = "block";
  x = document.getElementById ( "hidebutton" )
  if ( x )
    x.style.display = "none";
{rdelim}
hideComments ();
//]]> -->
</script>
   </td>
 </tr>
{/if}

</table><br />
<ul class="nav">

{if $can_approve}
  <li><a title="__Approve/Confirm entry__" class="nav" href="approve_entry.php?eid={$eid}&amp;{$u_url}&amp;type=E" onclick="return confirm('__Approve this entry?@D__');">__Approve/Confirm entry__</a>
	</li>
  <li>
	  <a title="__Reject entry__" class="nav" href="reject_entry.php?eid={$eid}&amp;{$u_url}&amp;type=E" onclick="return confirm('__Reject this entry?@D__');">__Reject entry__</a>
	</li>
{/if}

{if $can_add_attach}
  <li>
	  <a title="__Add Attachment__" class="nav" href="docadd.php?type=A&amp;eid={$eid}&amp;{$u_url}">__Add Attachment__</a>
	</li>
{/if}

{if $can_add_comment}
  <li>
	  <a title="__Add Comment__" class="nav" href="docadd.php?type=C&amp;eid={$eid}&amp;{$u_url}">__Add Comment__</a>
	</li>
{/if}


{if $setCategory}
  <li>
	  <a title="__Set category__" class="nav" href="set_entry_cat.php?eid={$eid}{$rdate}">__Set category__</a>
	</li>
{/if}


{if $can_edit}
  {if $event_repeats}
  <li><a title="__editAllDates__" class="nav" href="edit_entry.php?eid={$eid}{$u_url}">__editAllDates__</a>
	</li>

  <li><a title="__editThisDate__" class="nav" href="edit_entry.php?eid={$eid}{$u_url}{$rdate}&amp;override=1">__editThisDate__</a>
	</li>

  <li>
	  <a title="__deleteAllDates__" class="nav" href="del_entry.php?eid={$eid}{$u_url}&amp;override=1" onclick="return confirm('__ruSureEntry@D__ __delEntryAllUser@D__');">__deleteAllDates__</a>
	</li>

   <li>
	   <a title="__deleteOnly__" class="nav" href="del_entry.php?eid={$eid}{$u_url}{$rdate}&amp;override=1" onclick="return confirm('__ruSureEntry@D__ __delEntryAllUser@D__');">__deleteOnly__</a>
	</li>
  {else}
  <li>
	  <a title="__Edit entry__" class="nav" href="edit_entry.php?eid={$eid}{$u_url}">__Edit entry__</a>
	</li>
  <li>
	  <a title="__Delete entry__" class="nav" href="del_entry.php?eid={$eid}{$u_url}{$rdate}" onclick="return confirm('__ruSureEntry@D____This will delete this entry for all users.@D__');">__Delete entry__{$otherUserStr}</a>
	</li>
	{/if}
  <li>
	  <a title="__Copy entry__" class="nav" href="edit_entry.php?eid={$eid}{$u_url}&amp;copy=1">__Copy entry__</a>
	</li>
{else if $delFromCalStr}
  <li>
	  <a title="__Delete entry__" class="nav" href="del_entry.php?eid={$eid}{$u_url}{$rdate}" onclick="return confirm('{$confirmAreYouSureStr}');">__Delete entry__{$fromBoss}</a>
	</li>
  <li>
	  <a title="__Copy entry__" class="nav" href="edit_entry.php?eid={$eid}&amp;copy=1">__Copy entry__</a>
	</li>
{/if}

{if $addToMyCal}
  <li>
	  <a title="__Add to My Calendar__" class="nav" href="add_entry.php?eid={$eid}" onclick="return confirm('{$confirmAddtoMineStr}');">__Add to My Calendar__</a>
	</li>
{/if}

{if $emailAll}
  <li>
	  <a title="__Email all participants__" class="nav" href="mailto:{$allmails}?subject={$subject}">__Email all participants__</a>
	</li>
{/if}

{if $can_show_log}
  <li>
	{if $show_log}
	  <a title="__Hide activity log__" class="nav" href="view_entry.php?eid={$eid}">__Hide activity log__</a>
	{else}
	  <a title="__Show activity log__" class="nav" href="view_entry.php?eid={$eid}&amp;log=1">__Show activity log__</a>
	{/if}	
  </li>
{/if}
 </ul>
 
{if $can_show_log && $show_log}
  {include file="activity_log.tpl"}
{/if}

{if $allowExport}
    <form method="post" name="exportform" action="export.php">
      <label for="exformat">__Export this entry to__:&nbsp;</label>
      {generate_export_select}
      <input type="hidden" name="eid" value="{$eid}" />
		{if $user}
		  <input type="hidden" name="user" value="{$user}" />
		{/if}
      <input type="submit" value="__Export__" />
    </form>
{/if}
{include file="footer.tpl"}