   {include file="header.tpl"}
	 	<br />
   {print_tabs tabs=$tabs_ar}
<!-- TABS BODY -->
  <div id="tabscontent">
{if $add_edit}
    <form action="category_handler.php" method="post" name="catform" enctype="multipart/form-data">
		 {if $eid}
		  <input name="eid" type="hidden" value="{$WC->getId()}" />
		 {/if}
      <table cellspacing="2" cellpadding="3">
        <tr>
          <td width="25%"><label for="catname">{'Category Name'|translate}</label>
					</td>
          <td colspan="3"><input type="text" name="catname" size="20" value="{$catname|htmlspecialchars}" onmouseout="if (this.value !='') this.form.action.disabled=false;"/>
					</td>
       </tr>
			{if $WC->isAdmin() && ! $WC->getId()}
       <tr>
          <td><label for="isglobal">{$globalStr}:</label></td>
          <td colspan="3">
					  {print_radio variable=isglobal defIdx=$isglobal}
          </td>
        </tr>
			{/if}
        <tr>
          <td>
					  {html_color_input name='catcolor' title='Color'|translate val=$catcolor}
          </td>
        </tr>
        <tr id="cat_icon" style="visibility:{$showIcon}">
          <td><label>{$catIconStr}:</label></td>
          <td colspan="3"><img src="{$catIcon}" name="urlpic" id="urlpic" alt="{$catIconStr}" />
					</td>
        </tr>
        <tr id="remove_icon" style="visibility:{$showIcon}">
          <td><label for="delIcon">{'Remove Icon'|translate}</label>
					</td>
          <td colspan="3">
					  <input type="checkbox" name="delIcon" value="Y" />
					</td>
        </tr>
			{if $doUploads}
        <tr>
          <td colspan="4">
            <label for="FileName">{'Add Icon to Category'|translate}</label>
						<br/>&nbsp;&nbsp;&nbsp;{'Upload'|translate}&nbsp;
						<span style="font-size:small;">{'gif 3kb max'|translate}</span>:
            <input type="file" name="FileName" id="fileupload" size="45" maxlength="50" value=""/>
          </td>
        </tr>
        </tr>
          <td colspan="4">
            <input type="hidden" name="urlname" size="50" />&nbsp;&nbsp;&nbsp;
            <input type="button" value="{'Search for existing icons'|translate}" onclick="window.open ('icons.php', 'icons','dependent,menubar=no,scrollbars=no,height=300,width=400,outerHeight=320,outerWidth=420');" />
          </td>
        </tr>
			{/if}
			  <tr>
          <td colspan="4">
            <input type="submit" name="action" 
						value="{if $add}{'Add'|translate}" disabled="disabled" />
							     {else}{'Save'|translate}" />
							     {/if}
           {if $eid}
            <input type="submit" name="delete" value="{'Delete'|translate}" onclick="return confirm('{$confirnStr}')" />
					 {/if}
          </td>
        </tr>
      </table>
    </form>
{else}
  {if $categories}
    <ul>
    {foreach from=$categories key=k item=v}
      {if $k > 0}
      <li>{if $WC->isLogin( $v.cat_owner ) || $WC->isAdmin()}
			      <a href="category.php?eid={$k}">{/if}<span style="color:{$v.cat_color};">{$v.cat_name}</span>
						{if $WC->isLogin( $v.cat_owner ) || $WC->isAdmin()}</a>{/if}
      {if ! $v.cat_owner}
        <sup>*</sup>
        {assign var='global_found' value=true}
      {/if}
      {if $showIcon == 'visible'}
        <img src="{$catIcon}" alt="{$catIconStr}" title="{$catIconStr}" />
			{/if}
			</li>
			{/if}
    {/foreach}
    </ul>
  {/if}
  {if $global_found}
	   <br /><br /><sup>*</sup>{$globalStr}
	{/if}
    <p>{generate_href_button label='Make New Category'|translate attrib='onclick="window.location.href=\'category.php?add=1\'"'}
   </p><br />
	{/if}
	 </div>
    {include file="footer.tpl"}