{include file="header.tpl"}
<h2>__User Access Control__{$userData.fullname}</h2>
{if $WC->isAdmin() }
<form action="access.php" method="post" name="SelectUser">
  <select name="guser" onchange="document.SelectUser.submit()">
  {foreach from=$userlist key=k item=v}
    <option value="{$v.value}" {$v.selected}>{$v.display}</option>
  {/foreach}
    </select>
  <input type="submit" value="__Go__" />
</form>
{/if}

{if $access_functions}
<div class="boxA" style="margin-top: 5px; padding: 5px;">
  <form action="access.php" method="post" name="accessform">
    <input type="hidden" name="auser" value="{$guser}" />
    <input type="hidden" name="guser" value="{$guser}" />
    <table border="0" cellpadding="10">
      <tr>
        <td valign="top" style="padding:5px">
     {foreach from=$access_functions key=k item=v}
          <label><input type="checkbox" name="access_{$k}" value="Y" id="access_{$k}" {$v.checked} />&nbsp;{$v.desc}</label><br />
      {if $v.closeTD}
         </td>
         <td valign="top" style="padding:5px">
      {/if}
     {/foreach}
        </td>
      </tr>
    </table>
    <input type="submit" value="__Undo__"/>
    <input type="submit" name="submit" value="__Save__" />
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
    <input type="submit" value="__Go__" />
  {/if} 
  </form>
{/if}

{if $otheruser}
  {if $s._ALLOW_VIEW_OTHER}
  <form action="access.php" method="post" name="EditOther">
    <input type="hidden" name="guser" value="{$guser}" />
    <input type="hidden" name="otheruser" value="{$otheruser}"/><br />
    <table cellpadding="5" cellspacing="0" border="0">
    <tbody>
      <tr>
        <th class="boxL boxT boxB" width="25%">{$otheruser_fullname}</th>
        <th class="boxT boxB" width="15%">__Type__</th>
        <th width="15%" colspan="3" class="boxT boxB">__View__</th>
        <th width="15%" colspan="3" class="boxT boxB">__Edit__</th>
        <th width="15%" colspan="3" class="boxT boxR boxB">__Approve/Reject__</th>
      </tr>

    {section name=grid loop=5 start=1}
      {assign var=index value=$smarty.section.grid.index}
      {if $index !=3}
       <tr>
         <td class="boxL leftpadded {if $index > 3}boxB{/if}">
         {if $index == 1}
          <input type="checkbox" value="Y" name="invite" 
         {if $op.invite == 'Y'}{#checked#}{/if} /> __Can Invite__
         {elseif $index == 2}
          <input type="checkbox" value="Y" name="email" 
         {if $op.email == 'Y'}{#checked#}{/if} /> __Can Email__
         {else}
          <input type="checkbox" value="Y" name="time" 
         {if $op.time == 'Y'}{#checked#}{/if} /> __Can See Time Only__
        {/if}
        </td>
        <td align="center" class="boxL {if $index > 3}boxB{/if}">{$access_type.$index}</td>
        <td align="center" class="boxL pub {if $index > 3}boxB{/if}">
          <input type="checkbox" value="{$index}" name="v_{$index}" {if $op.view & $index}{#checked#}{/if} />
        </td>
        <td class="conf {if $index > 3}boxB{/if}">
          <input type="checkbox" value="{$index*8}" name="v_{$index*8}" {if $op.view & $index*8}{#checked#}{/if} />
        </td>
        <td class="priv {if $index > 3}boxB{/if}">
         <input type="checkbox" value="{$index*64}" name="v_{$index*64}" {if $op.view & $index*64}{#checked#}{/if} />
        </td>
        <td align="center" class="boxL pub  {if $index > 3}boxB{/if}">
          <input type="checkbox" value="{$index}" name="e_{$index}" {if $op.edit & $index}{#checked#}{/if} />
        </td>
        <td class="conf {if $index > 3}boxB{/if}">
          <input type="checkbox" value="{$index*8}" name="e_{$index*8}" {if $op.edit & $index*8}{#checked#}{/if} />
        </td>
        <td class="priv {if $index > 3}boxB{/if}">
         <input type="checkbox" value="{$index*64}" name="e_{$index*64}" {if $op.edit & $index*64}{#checked#}{/if} />
        </td>
        <td align="center" class="boxL pub  {if $index > 3}boxB{/if}">
          <input type="checkbox" value="{$index}" name="a_{$index}" {if $op.approve & $index}{#checked#}{/if} />
        </td>
        <td class="conf {if $index > 3}boxB{/if}">
          <input type="checkbox" value="{$index*8}" name="a_{$index*8}" {if $op.approve & $index*8}{#checked#}{/if} />
        </td>
        <td class="priv {if $index > 3}boxB{/if}">
         <input type="checkbox" value="{$index*64}" name="a_{$index*64}" {if $op.approve & $index*64}{#checked#}{/if} />
        </td>
      </tr>
      {/if}
    {/section}
      <tr>
        <td colspan="2" class="boxL alignR">
        {if ! $otheruserSpecial}
          <input type="checkbox" value="Y" name="assistant" {if $op.assistant == 'Y'}{#checked#}{/if} />
          <input type="button" value="__Assistant__" onclick="selectAll('{$asstWeight}');" />&nbsp;&nbsp;
        {/if}
          <input type="button" value="__Select All__" onclick="selectAll(256,256,256,0);" />&nbsp;&nbsp;
          <input type="button" value="__Clear All__" onclick="selectAll(0,0,0,0);" />
        </td>
        <td colspan="9" class="boxR">
          <table border="0" align="center" cellpadding="5" cellspacing="2">
            <tr>
              <td class="pub">__Public__</td>
              <td class="conf">__Confidential__</td>
              <td class="priv">__Private__</td>
            </tr>
          </table>
        </td>
      </tr>
  {/if}

      <tr>
        <td colspan="11" class="boxL boxB boxR">
          <input type="submit" value="__Undo__"/>
          <input type="submit" name="submit" value="__Save__" />
        </td>
      </tr>
    </tbody>
  </table>
</form>
{/if}
    <script language="javascript" type="text/javascript">
<!-- <![CDATA[
      function selectAll ( view, edit, approve, assistant ) {ldelim}
        if ( view == 0 )
          document.EditOther.time.checked = false;

        document.EditOther.email.checked =
        document.EditOther.invite.checked = ( view != 0 )
        if ( document.EditOther.assistant )
          document.EditOther.assistant.checked = ( assistant == 1 )
        
        for ( i = 1; i <= 256; ) {ldelim}
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
        {rdelim}
      {rdelim}
      function enableAll ( on ) {ldelim}
        for ( i = 1; i <= 256; ) {ldelim}
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
        {rdelim}
      {rdelim}
//]]> -->
    </script>
{include file="footer.tpl"}

