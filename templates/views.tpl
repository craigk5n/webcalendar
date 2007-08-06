 {include file="header.tpl"}
 		<br />
 {print_tabs tabs=$tabs_ar}
<!-- TABS BODY -->
  <div id="tabscontent">
<!-- VIEWS -->
    <a name="tabviews"></a>
    <div id="tabscontent_views">
	    <input type="button" value="{'Add New View'|translate}" onclick="return window.frames['viewiframe'].location.href='edit_views.php'" />
	    <ul>

{foreach from=$views key=k item=v}
  {if $v.cal_is_global != 'Y' || $WC->isAdmin()}
        <li><a title="{$v.cal_name|htmlspecialchars}" href="edit_views.php?eid={$v.cal_view_id}" target="viewiframe" onclick="show('viewiframe');">{$v.cal_name|htmlspecialchars}</a>
    {if $v.cal_is_global == 'Y'}
		  {assign var=global_found value=true}
      &nbsp;<abbr title="{'Global'|translate}">*</abbr>
    {/if}
	{/if}
        </li>
{/foreach}


      </ul>
		{if $global_found}
		   <br />*&nbsp;{'Global'|translate}<br />
		{/if}
        <iframe name="viewiframe" id="viewiframe"></iframe>
      </div>
    </div>
{include file="footer.tpl"}	
