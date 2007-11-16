 {include file="header.tpl"}
<form action="edit_views_handler.php" method="post" name="editviewform">

{if $newview}
  <h2>__Add View__</h2>
  <input type="hidden" name="add" value="1" />
{else}
  <h2>__Edit View__</h2>
  <input type="hidden" name="vid" value="{$vid}" />
{/if}

<table>
  <tr>
	  <td>
      <label for="viewname">__View Name__:</label></td>
		<td colspan="3">
      <input name="viewname" id="viewname" size="20" value="{$viewname|htmlspecialchars}" />
    </td>
  </tr>
  <tr>
	  <td>
      <label for="viewtype">__View Type__:</label></td>
		<td colspan="3">
 <select name="viewtype" id="viewtype">
  <option value="D" {if $viewtype == 'D'}{#selected#}{/if}>__Day__</option>
  <option value="E" {if $viewtype == 'E'}{#selected#}{/if}>__Day by Time__</option>
  <option value="W" {if $viewtype == 'W'}{#selected#}{/if}>__Week__ (__Users horizontal__)</option>
  <option value="R" {if $viewtype == 'R'}{#selected#}{/if}>__Week by Time__</option>
  <option value="V" {if $viewtype == 'V'}{#selected#}{/if}>__Week__ (__Users vertical__)</option>
  <option value="S" {if $viewtype == 'S'}{#selected#}{/if}>__Week__ (__Timebar__)</option>
  <option value="T" {if $viewtype == 'T'}{#selected#}{/if}>__Month__ (__Timebar__)</option>
  <option value="M" {if $viewtype == 'M'}{#selected#}{/if}>__Month__ (__side by side__)</option>
  <option value="L" {if $viewtype == 'L'}{#selected#}{/if}>__Month__ (__on same calendar__)</option>
      </select>&nbsp;
  </td></tr>

{if $WC->isAdmin()}
  <tr>
	  <td>
		  <label>__Global__:</label></td>
		<td>{print_radio variable='is_global' defIdx=$viewisglobal sep='</td><td>'}
    </td>
	</tr>
{/if}


  <tr>
	  <td>
		  <label>__Users__:</label></td>
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
       <input type="button" onclick="selectUsers()" value="__Select__..." />
{/if}
      </div>
    </td>
	</tr>
  <tr>
	  <td colspan="4">
      <input type="submit" name="action" value="{if $newview}__Add__{else}__Save__{/if}" />

{if ! $newview}
      <input type="submit" name="delete" value="__Delete__" onclick="return confirm('__ruSureView@D__')" />
{/if}
</td></tr>
</table>

</form>
{include file="footer.tpl" include_nav_links=false}	