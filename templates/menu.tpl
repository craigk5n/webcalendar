<table width="100%" class="Menubar" cellspacing="0" cellpadding="0">
  <tr>
    <td class="Menubackgr"><div id="myMenuID"></div></td>
	{if $menu_date_top}
    <td class="Menubackgr" style="text-align:right">
		  {date_selectors}
     </td>
	{/if}
    <td class="Menubackgr Menu" align="right" style="padding-right:10px">
  {if $logout}
      <a class="menuhref" title="{'Logout'|translate}" href="{$WC->_logout_url}">{'Logout'|translate}:</a>&nbsp;<label>{$menuName}</label>
	{else}
      <a class="menuhref" title="{'Login'|translate}" href="{$WC->_login_url}">{'Login'|translate}</a>
	{/if}
    </td>
  </tr>
</table>
