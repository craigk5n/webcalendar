 {include file="header.tpl"}
<form action="edit_views_handler.php" method="post" name="editviewform">

{if $newview}
  <h2>{'Add View'|translate}</h2>
  <input type="hidden" name="add" value="1" />
{else}
  <h2>{'Edit View'|translate}</h2>
  <input type="hidden" name="eid" value="{$eid}" />
{/if}

<table>
  <tr>
	  <td>
      <label for="viewname">{'View Name'|translate}:</label></td>
		<td colspan="3">
      <input name="viewname" id="viewname" size="20" value="{$viewname|htmlspecialchars}" />
    </td>
  </tr>
  <tr>
	  <td>
      <label for="viewtype">{'View Type'|translate}:</label></td>
		<td colspan="3">
 <select name="viewtype" id="viewtype">
  <option value="D" {if $viewtype == 'D'}{#selected#}{/if}>{'Day'|translate}</option>
  <option value="E" {if $viewtype == 'E'}{#selected#}{/if}>{'Day by Time'|translate}</option>
  <option value="W" {if $viewtype == 'W'}{#selected#}{/if}>{'Week (Users horizontal)'|translate}</option>
  <option value="R" {if $viewtype == 'R'}{#selected#}{/if}>{'Week by Time'|translate}</option>
  <option value="V" {if $viewtype == 'V'}{#selected#}{/if}>{'Week (Users vertical)'|translate}</option>
  <option value="S" {if $viewtype == 'S'}{#selected#}{/if}>{'Week (Timebar)'|translate}</option>
  <option value="T" {if $viewtype == 'T'}{#selected#}{/if}>{'Month (Timebar)'|translate}</option>
  <option value="M" {if $viewtype == 'M'}{#selected#}{/if}>{'Month (side by side)'|translate}</option>
  <option value="L" {if $viewtype == 'L'}{#selected#}{/if}>{ 'Month (on same calendar)'|translate}</option>
      </select>&nbsp;
  </td></tr>

{if $WC->isAdmin()}
  <tr>
	  <td>
		  <label>{'Global'|translate}:</label></td>
		<td>{print_radio variable='is_global' defIdx=$viewisglobal sep='</td><td>'}
    </td>
	</tr>
{/if}


  <tr>
	  <td>
		  <label>{'Users'|translate}:</label></td>
	  <td>{print_radio variable='viewuserall' vars=$selectuserall
    onclick='usermode_handler()' defIdx=$all_users sep='</td><td>'}
    </td>
	</tr>

  <tr>
	  <td></td>
	  <td colspan="3">
      <div id="viewuserlist">
        <select name="users[]" id="viewusers" size="{$userSize}" multiple="multiple">
 {foreach from=$users key=k item=v}
          <option value="{$v.cal_login_id}" {$v.selected}>{$v.cal_fullname}</option>
{/foreach}
       </select>

{if $s.GROUPS_ENABLED}
       <input type="button" onclick="selectUsers()" value="{'Select'|translate}..." />
{/if}
      </div>
    </td>
	</tr>
  <tr>
	  <td colspan="4">
      <input type="submit" name="action" value="{if $newview}{'Add'|translate}{else}{'Save'|translate}{/if}" />

{if ! $newview}
      <input type="submit" name="delete" value="{'Delete'|translate}" onclick="return confirm('{$confirmStr}')" />
{/if}
</td></tr>
</table>

</form>
{include file="footer.tpl" include_nav_links=false}	