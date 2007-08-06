 {include file="header.tpl"}
<h2>{'View Entry'|translate}
{$eType_label}{generate_help_icon url='help_edit_entry.php'}
<br />&nbsp;&nbsp;{$navFullname}
{$navAdmin}
{$navAssistant}</h2>

<table width="100%">
  <tr>
    <td class="aligntop bold" width="10%">{'Description'|translate}:</td>
    <td>{$description}</td>
  </tr>
	
{if $location}
  <tr>
     <td class="aligntop bold">{'Location'|translate}:</td>
     <td>{$location}</td>
	</tr>
{/if}

{if $url}
  <tr>
    <td class="aligntop bold">{'URL'|translate}:</td>
    <td>{$url}</td>
  <tr>
{/if}

{if $status}
  <tr>
    <td class="aligntop bold">{'Status'|translate}:</td>
    <td>{$status}</td>
  </tr>
{/if}
  <tr>
    <td class="aligntop bold">{if $eType == 'task'}{'Start Date'|translate}{else}{'Date'|translate}{/if}:</td>
    <td>{$display_date|date_to_str}</td>
	</tr>
{if $eType == 'task'}
  {if $itemTime >= 0 }
  <tr>
    <td class="aligntop bold">{'Start Time'|translate}:</td>
    <td>{$itemDate|display_time:2}</td>
  </tr>
	{/if}
  <tr>
    <td class="aligntop bold">{'Due Date'|translate}:</td>
     <td>{$itemDueDate|date_to_str}</td>
  </tr>

	{if $itemCompleted}
  <tr>
    <td class="aligntop bold">{'Completed'|translate}:</td>
    <td>{$itemCompleted|date_to_str}</td>
  </tr>
	{/if}
{else if $timeStr}
  <tr>
    <td class="aligntop bold">{'Time'|translate}:</td>
    <td>{$timeStr}</td>
   </tr>
{/if}
{if $event_repeats}
  <tr>
    <td class="aligntop bold">{'Repeat Type'|translate}:</td>
    <td>{$recurrenceStr}</td>
  </tr>
{/if}
{if $durationStr}
  <tr>
    <td class="aligntop bold">{'Duration'|translate}:</td>
    <td>{$durationStr}</td>
  </tr>
{/if}

{if $itemPriority}
  <tr>
    <td class="aligntop bold">{'Priority'|translate}:</td>
    <td>{$itemPriority}</td>
  </tr>
{/if}		
			
{if $itemAccess}
  <tr>
    <td class="aligntop bold">{'Access'|translate}:</td>
    <td>{$itemAccess}</td>
  </tr>
{/if}

{if $itemCategory}			
  <tr>
    <td class="aligntop bold">{'Category'|translate}:</td>
    <td>{$itemCategory}</td>
  </tr>
{/if}


{if $createby_fullname}
  <tr>
    <td class="aligntop bold">{'Created by'|translate}:</td>
    <td>
    {if $can_email}
      <a href="mailto:{$email_addr}?subject={$subject}">{$pubAccStr}</a>
		{else}
      {$pubAccStr}
		{/if}{$proxy_fullname}</td>
  </tr>
{/if}

  <tr>
    <td class="aligntop bold">{'Updated'|translate}:</td>
    <td>{$itemModDate}</td>
  </tr>

{if $reminder}
  <tr>
    <td class="aligntop bold">{'Send Reminder'|translate}:</td>
    <td>{$reminder}</td>
  </tr>
{/if}

<!--Add site Extras Here-->

{if $show_participants}
  <tr>
    <td class="aligntop bold">{'Participants'|translate}:</td>
    <td>

  {if $eType == 'task'}
    <table border="1" width="80%" cellspacing="0" cellpadding="1">
      <th align="center">{'Participants'|translate}</th>
      <th align="center" colspan="2">{'Percentage Complete'|translate}</th>
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
			</strike> ({'Rejected'|translate})<br />
    {/foreach}
  {/if}
   </td>
  </tr>
{/if} 


{if $eType == 'task'}
  {if $canUpdatePercentage}
  <tr>
    <td class="aligntop bold">
      <form action="view_entry.php?eid={$eid}" method="post" name="setpercentage">
        <input type="hidden" name="others_complete" value="{$others_complete}" />{'Update Task Percentage'|translate}
		</td>
    <td>
        <select name="upercent" id="task_percent">
		{section loop=100 step=10 name=task_percent}
		  {assign var=index value=$smarty.section.task_percent.index}
          <option value="{$index}" {if $login_percentage == $index}{#seclected#}{/if}>{$index}</option>
    {/section}
        </select>&nbsp;
        <input type="submit" value="{'Update'|translate}" />
      </form>
    </td>
  <tr>
  {/if}
{/if}

{if $attachmentsEnabled}
  <tr>
    <td class="aligntop bold">{'Attachments'|translate}:</td>
    <td>
  {if $attList}
	 {foreach from=$attList key=k item=v}
     {$v.Summary}
     {if $can_edit}
      [<a href="docdel.php?blid={$v.id}" onclick="return confirm('{$areYouSureStr});">{'Delete'}translate}</a>]
			{/if}
			<br />
    {/foreach}
  {else}
     {'None'|translate}<br />
	{/if}
    </td>
  </tr>
{/if}

{if $commentsEnabled}
  <tr>
    <td class="aligntop bold">{'Comments'|translate}:</td>
    <td>
   {if $comment_text}
      <input id="showbutton" type="button" value="{'Show'|translate}" onclick="showComments();" />
      <input id="hidebutton" type="button" value="{'Hide'|translate}" onclick="hideComments();" /><br />
      <div id="comtext">{$comment_text}</div>
  {else}
     {'None'|translate}<br />
	{/if}
{literal}
<script language="JavaScript" type="text/javascript">
<!-- <![CDATA[
function showComments () {
  var x = document.getElementById ( "comtext" )
  if ( x ) {
    x.style.display = "block";
  }
  x = document.getElementById ( "showbutton" )
  if ( x ) {
    x.style.display = "none";
  }
  x = document.getElementById ( "hidebutton" )
  if ( x ) {
    x.style.display = "block";
  }
}
function hideComments () {
  var x = document.getElementById ( "comtext" )
  if ( x ) {
    x.style.display = "none";
  }
  x = document.getElementById ( "showbutton" )
  if ( x ) {
    x.style.display = "block";
  }
  x = document.getElementById ( "hidebutton" )
  if ( x ) {
    x.style.display = "none";
  }
}
hideComments ();
//]]> -->
</script>
{/literal}
   </td>
 </tr>
{/if}

</table><br />
<ul class="nav">

{if $can_approve}
  <li><a title="{'Approve/Confirm entry'|translate}" class="nav" href="approve_entry.php?eid={$eid}&amp;{$u_url}&amp;type=E" onclick="return confirm('{'Approve this entry?'|translate:true}');">{'Approve/Confirm entry'|translate}</a>
	</li>
  <li>
	  <a title="{'Reject entry'|translate}" class="nav" href="reject_entry.php?eid={$eid}&amp;{$u_url}&amp;type=E" onclick="return confirm('{'Reject this entry?'|translate:true}');">{'Reject entry'|translate}</a>
	</li>
{/if}

{if $can_add_attach}
  <li>
	  <a title="{'Add Attachment'|translate}" class="nav" href="docadd.php?type=A&amp;eid={$eid}&amp;{$u_url}">{'Add Attachment'|translate}</a>
	</li>
{/if}

{if $can_add_comment}
  <li>
	  <a title="{'Add Comment'|translate}" class="nav" href="docadd.php?type=C&amp;eid={$eid}&amp;{$u_url}">{'Add Comment'|translate}</a>
	</li>
{/if}


{if $setCategory}
  <li>
	  <a title="{'Set category'|translate}" class="nav" href="set_entry_cat.php?eid={$eid}{$rdate}">{'Set category'|translate}</a>
	</li>
{/if}


{if $can_edit}
  {if $event_repeats}
  <li><a title="{$editAllDatesStr}" class="nav" href="edit_entry.php?eid={$eid}{$u_url}">{$editAllDatesStr}</a>
	</li>

  <li><a title="{$editThisDateStr}" class="nav" href="edit_entry.php?eid={$eid}{$u_url}{$rdate}&amp;override=1">{$editThisDateStr}</a>
	</li>

  <li>
	  <a title="{$deleteAllDatesStr}" class="nav" href="del_entry.php?eid={$eid}{$u_url}&amp;override=1" onclick="return confirm('{$areYouSureStr}{$deleteAllStr}');">{$deleteAllDatesStr}</a>
	</li>

   <li>
	   <a title="{$deleteOnlyStr}" class="nav" href="del_entry.php?eid={$eid}{$u_url}{$rdate}&amp;override=1" onclick="return confirm('{$areYouSureStr}{$deleteAllStr}');">{$deleteOnlyStr}</a>
	</li>
  {else}
  <li>
	  <a title="{'Edit entry'|translate}" class="nav" href="edit_entry.php?eid={$eid}{$u_url}">{'Edit entry'|translate}</a>
	</li>
  <li>
	  <a title="{'Delete entry'|translate}" class="nav" href="del_entry.php?eid={$eid}{$u_url}{$rdate}" onclick="return confirm('{$areYouSureStr}{'This will delete this entry for all users.'|translate:true}');">{'Delete entry'|translate}{$otherUserStr}</a>
	</li>
	{/if}
  <li>
	  <a title="{$copyStr}" class="nav" href="edit_entry.php?eid={$eid}{$u_url}&amp;copy=1">{$copyStr}</a>
	</li>
{else if $delFromCalStr}
  <li>
	  <a title="{$deleteEntryStr}" class="nav" href="del_entry.php?eid={$eid}{$u_url}{$rdate}" onclick="return confirm('{$confirmAreYouSureStr}');">{$deleteEntryStr}{$fromBoss}</a>
	</li>
  <li>
	  <a title="{$copyStr}" class="nav" href="edit_entry.php?eid={$eid}&amp;copy=1">{$copyStr}</a>
	</li>
{/if}

{if $addToMineStr}
  <li>
	  <a title="{$addToMineStr}" class="nav" href="add_entry.php?eid={$eid}" onclick="return confirm('{$confirmAddtoMineStr}');">{$addToMineStr}</a>
	</li>
{/if}

{if $emailAllStr}
  <li>
	  <a title="{$emailAllStr}" class="nav" href="mailto:{$allmails}?subject={$subject}">{$emailAllStr}</a>
	</li>
{/if}

{if $can_show_log}
  <li>
	{if $show_log}
	  <a title="{'Hide activity log'|translate}" class="nav" href="view_entry.php?eid={$eid}">{'Hide activity log'|translate}</a>
	{else}
	  <a title="{'Show activity log'|translate}" class="nav" href="view_entry.php?eid={$eid}&amp;log=1">{'Show activity log'|translate}</a>
	{/if}	
  </li>
{/if}
 </ul>
 
{if $can_show_log && $show_log}
  {include file="activity_log.tpl"}
{/if}

{if $allowExport}
    <form method="post" name="exportform" action="export.php">
      <label for="exformat">{'Export this entry to'|translate}:&nbsp;</label>
      {$exportSelectStr}
      <input type="hidden" name="eid" value="{$eid}" />
		{if $user}
		  <input type="hidden" name="user" value="{$user}" />
		{/if}
      <input type="submit" value="{'Export'|translate}" />
    </form>
{/if}
{include file="footer.tpl"}