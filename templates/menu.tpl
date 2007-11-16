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
      <a class="menuhref" title="__Logout__" href="{$WC->_logout_url}">__Logout__:</a>&nbsp;<label>{$menuName}</label>
	{else}
      <a class="menuhref" title="__Login__" href="{$WC->_login_url}">__Login__</a>
	{/if}
    </td>
  </tr>
</table>
