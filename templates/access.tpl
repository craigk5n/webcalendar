{include file="header.tpl"}
<h2>{'User Access Control'|translate}{$userData.fullname}</h2>
{if $WC->isAdmin() }
<form action="access.php" method="post" name="SelectUser">
  <select name="guser" onchange="document.SelectUser.submit()">
	{foreach from=$userlist key=k item=v}
    <option value="{$v.value}" {$v.selected}>{$v.display}</option>
	{/foreach}
    </select>
  <input type="submit" value="{'Go'|translate}" />
</form>
{/if}

{if $access_functions}
<div class="boxall" style="margin-top: 5px; padding: 5px;">
  <form action="access.php" method="post" name="accessform">
    <input type="hidden" name="auser" value="{$guser}" />
    <input type="hidden" name="guser" value="{$guser}" />
    <table border="0" cellpadding="10">
      <tr>
        <td valign="top" style="padding:5px">
        {$access_functions}
        </td>
      </tr>
    </table>
    <input type="submit" value="{'Undo'|translate}"/>
    <input type="submit" name="submit" value="{'Save'|translate}" />
  </form>
</div>
{/if}
 
{if $otheruserList}
<h2 style="margin-bottom: 2px;">{$pagetitle}</h2>
  <form action="access.php" method="post" name="SelectOther">
	{if $otheruserList}
    <input type="hidden" name="guser" value="{$guser}" />
    <select name="otheruser" onchange="document.SelectOther.submit()">
		{foreach from=$otheruserList key=k item=v}
      <option value="{$v.value}" {$v.selected}>{$v.display}</option>
    {/foreach}
    </select> 
    <input type="submit" value="{'Go'|translate}" />
	{/if} 
  </form>
{/if}

{if $otheruser}
  {if $s.ALLOW_VIEW_OTHER}
  <form action="access.php" method="post" name="EditOther">
    <input type="hidden" name="guser" value="{$guser}" />
    <input type="hidden" name="otheruser" value="{$otheruser}"/><br />
    <table cellpadding="5" cellspacing="0" border="0">
    <tbody>
      <tr>
        <th class="boxleft boxtop boxbottom" width="25%">{$otheruser_fullname}
				</th>
        <th class="boxtop boxbottom" width="15%">{'Type'|translate}
				</th>
        <th width="15%" colspan="3" class="boxtop boxbottom">{'View'|translate}
				</th>
        <th width="15%" colspan="3" class="boxtop boxbottom">{'Edit'|translate}
				</th>
        <th width="15%" colspan="3" class="boxtop boxright boxbottom">{'Approve/Reject'|translate}
				</th>
      </tr>

    {section name=grid loop=5 start=1}
		  {assign var=index value=$smarty.section.grid.index}
      {if $index !=3}
       <tr>
         <td class="boxleft leftpadded {if $index > 3}boxbottom{/if}">
				 {if $index == 1}
				  <input type="checkbox" value="Y" name="invite" 
         {if $op.invite == 'Y'}{#checked#}{/if} /> {'Can Invite'|translate}
				 {elseif $index == 2}
				  <input type="checkbox" value="Y" name="email" 
         {if $op.email == 'Y'}{#checked#}{/if} /> {'Can Email'|translate}
         {else}
				  <input type="checkbox" value="Y" name="time" 
         {if $op.time == 'Y'}{#checked#}{/if} /> {'Can See Time Only'|translate}
        {/if}
        </td>
        <td align="center" class="boxleft {if $index > 3}boxbottom{/if}">{$access_type.$index}</td>
        <td align="center" class="boxleft pub {if $index > 3}boxbottom{/if}">
          <input type="checkbox" value="{$index}" name="v_{$index}" {if $op.view & $index}{#checked#}{/if} />
				</td>
        <td class="conf {if $index > 3}boxbottom{/if}">
				  <input type="checkbox" value="{$index*8}" name="v_{$index*8}" {if $op.view & $index*8}{#checked#}{/if} />
				</td>
        <td class="priv {if $index > 3}boxbottom{/if}">
				 <input type="checkbox" value="{$index*64}" name="v_{$index*64}" {if $op.view & $index*64}{#checked#}{/if} />
				</td>
        <td align="center" class="boxleft pub  {if $index > 3}boxbottom{/if}">
          <input type="checkbox" value="{$index}" name="e_{$index}" {if $op.edit & $index}{#checked#}{/if} />
				</td>
        <td class="conf {if $index > 3}boxbottom{/if}">
				  <input type="checkbox" value="{$index*8}" name="e_{$index*8}" {if $op.edit & $index*8}{#checked#}{/if} />
				</td>
        <td class="priv {if $index > 3}boxbottom{/if}">
				 <input type="checkbox" value="{$index*64}" name="e_{$index*64}" {if $op.edit & $index*64}{#checked#}{/if} />
				</td>
        <td align="center" class="boxleft pub  {if $index > 3}boxbottom{/if}">
          <input type="checkbox" value="{$index}" name="a_{$index}" {if $op.approve & $index}{#checked#}{/if} />
				</td>
        <td class="conf {if $index > 3}boxbottom{/if}">
				  <input type="checkbox" value="{$index*8}" name="a_{$index*8}" {if $op.approve & $index*8}{#checked#}{/if} />
				</td>
        <td class="priv {if $index > 3}boxbottom{/if}">
				 <input type="checkbox" value="{$index*64}" name="a_{$index*64}" {if $op.approve & $index*64}{#checked#}{/if} />
        </td>
      </tr>
			{/if}
    {/section}
      <tr>
        <td colspan="2" class="boxleft alignright">
        {if ! $otheruserSpecial}
	        <input type="checkbox" value="Y" name="assistant" {if $op.assistant == 'Y'}{#checked#}{/if} />
          <input type="button" value="{'Assistant'|translate}" onclick="selectAll('{$asstWeight}');" />&nbsp;&nbsp;
				{/if}
          <input type="button" value="{'Select All'|translate}" onclick="selectAll(256,256,256,0);" />&nbsp;&nbsp;
          <input type="button" value="{'Clear All'|translate}" onclick="selectAll(0,0,0,0);" />
        </td>
        <td colspan="9" class="boxright">
          <table border="0" align="center" cellpadding="5" cellspacing="2">
            <tr>
              <td class="pub">{'Public'|translate}</td>
              <td class="conf">{'Confidential'|translate}</td>
              <td class="priv">{'Private'|translate}</td>
            </tr>
          </table>
        </td>
      </tr>
  {/if}

      <tr>
        <td colspan="11" class="boxleft boxbottom boxright">
          <input type="submit" value="{'Undo'|translate}"/>
          <input type="submit" name="submit" value="{'Save'|translate}" />
        </td>
      </tr>
    </tbody>
  </table>
</form>
{/if}
{literal}
    <script language="javascript" type="text/javascript">
<!-- <![CDATA[
      function selectAll ( view, edit, approve, assistant ) {
        if ( view == 0 )
          document.EditOther.time.checked = false;

        document.EditOther.email.checked =
        document.EditOther.invite.checked = ( view != 0 )
        if ( document.EditOther.assistant )
          document.EditOther.assistant.checked = ( assistant == 1 )
				
        for ( i = 1; i <= 256; ) {
          var
            aname = 'a_' + i,
            ename = 'e_' + i,
            vname = 'v_' + i;

          document.forms['EditOther'].elements[vname].checked = (i <= view);

          if (document.forms['EditOther'].elements[ename])
            document.forms['EditOther'].elements[ename].checked = (i <= edit);

          if (document.forms['EditOther'].elements[aname])
            document.forms['EditOther'].elements[aname].checked = (i <= approve);

          i = parseInt(i+i);
        }
      }
      function enableAll ( on ) {
        for ( i = 1; i <= 256; ) {
          var
            aname = 'a_' + i,
            ename = 'e_' + i,
            vname = 'v_' + i;

          document.forms['EditOther'].elements[vname].disabled = on;

          if (document.forms['EditOther'].elements[ename])
            document.forms['EditOther'].elements[ename].disabled = on;

          if (document.forms['EditOther'].elements[aname])
            document.forms['EditOther'].elements[aname].disabled = on;

          i = parseInt(i+i);
        }
      }
//]]> -->
    </script>
{/literal}
{include file="footer.tpl"}

