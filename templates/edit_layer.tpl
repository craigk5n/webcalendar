   {include file="header.tpl"}
    <fieldset>
	 <legend>{if $layer.cal_layeruser}{'Edit Layer'|translate}{else}{'Add Layer'|translate}{/if}</legend>
    <form action="edit_layer.php" method="post" onsubmit="return valid_form (this);" name="prefform">
	<input type="hidden" name="do_layer_edit" value="1" />
	<input type="hidden" name="user" value="{$WC->userId()}"/>
    <table cellspacing="2" cellpadding="3">



  {if $userlist}
      <tr>
        <td class="aligntop"><label for="layeruser">{'Source'|translate}:</label></td>
        <td colspan="3">
          <select name="layeruser" id="layeruser" size="1">
				{foreach from=$userlist key=k item=v}
            <option value="{$v.cal_login_id}" {if $layers.cal_layeruser == $v.cal_login_id}{#selected#}{/if}>{$v.cal_fullname}</option>				
				{/foreach}
          </select>
        </td>
      </tr>
   {/if}

      <tr>
        <td>{html_color_input name='layercolor' title='Color'|translate val=$layer.cal_color}</td>
      </tr>
      <tr>
        <td class="bold">{'Duplicates'|translate}:</td>
        <td colspan="3"><label>
          <input type="checkbox" name="dups" value="Y" {if $layer.cal_dups == 'Y'}{#selected#}{/if}/>&nbsp;
           {'Show layer events that are the same as your own'|translate}?</label>
        </td>
      </tr>

{if $WC->isAdmin() && ! $layers.cal_layeruser}
      <tr>
        <td class="bold">{'Add to My Calendar'|translate}:</td>
        <td colspan="3">
          <input type="checkbox" name="is_mine" {#checked#} onclick="show_others();" />
		    </td>
      </tr>
      <tr id="others" style="visibility: hidden;">
        <td class="aligntop"><label for="cal_login">{'Add to Others'|translate}:</label>
				</td>
        <td colspan="3">
          <select name="cal_login[]" id="cal_login" size="10" multiple="multiple">
				{foreach from=$others key=k item=v}
				  {if $v.cal_login_id != $layer_user}
				 <option value="{$v.cal_login_id}">{$v.cal_fullname}</option>
				  {/if}
			  {/foreach}
          </select>
        </td>
     </tr>
{/if}

     <tr>
       <td colspan="4">
         <input type="submit" value="{'Save'|translate}" />
       </td>
     </tr>
   </table>
		{if $layer.cal_layeruser}
      <input type="hidden" name="id" value="{$WC->getId ()}" />
		{/if}
    </form>
  {include file="footer.tpl" include_nav_links=false}