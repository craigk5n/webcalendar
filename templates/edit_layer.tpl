   {include file="header.tpl"}
    <fieldset>
	 <legend>{if $layer.cal_layeruser_id}__Edit Layer__{else}__Add Layer__{/if}</legend>
    <form action="edit_layer.php" method="post" onsubmit="return valid_form (this);" name="prefform">
	<input type="hidden" name="do_layer_edit" value="1" />
    <table cellspacing="2" cellpadding="3">


  {if $layer.cal_layeruser_id}
      <tr>
        <td class="alignT"><label for="layeruser">__Source__:</label></td>
        <td colspan="3">
          {$layer.cal_fullname}
					<input type="hidden" name="layeruser" value="{$layer.cal_layeruser_id}" />
					<input type="hidden" name="lid" value="{$layer.cal_layerid}" />
        </td>
      </tr>	
  {else if $userlist}
      <tr>
        <td class="alignT"><label for="layeruser">__Source__:</label></td>
        <td colspan="3">
          <select name="layeruser" id="layeruser" size="1">
				{foreach from=$userlist key=k item=v}
            <option value="{$v.cal_login_id}" >{$v.cal_fullname}</option>				
				{/foreach}
          </select>
        </td>
      </tr>
   {/if}

      <tr>
        <td>{html_color_input name='layercolor' title=__Color__  val=$layer.cal_color}</td>
      </tr>
      <tr>
        <td class="bold">__Duplicates__:</td>
        <td colspan="3"><label>
          <input type="checkbox" name="dups" value="Y" {if $layer.cal_dups == 'Y'}{#selected#}{/if}/>&nbsp;
           __Show layer events that are the same as your own__?</label>
        </td>
      </tr>

{if $WC->isAdmin() && ! $layer.cal_layeruser_id}
      <tr>
        <td class="bold">__Add to My Calendar__:</td>
        <td colspan="3">
          <input type="checkbox" name="is_mine" {#checked#} onclick="show_others();" />
		    </td>
      </tr>
      <tr id="others" style="visibility: hidden;">
        <td class="alignT"><label for="cal_login">__Add to Others__:</label>
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
         <input type="submit" value="__Save__" />
       </td>
     </tr>
   </table>
    </form>
  {include file="footer.tpl" include_nav_links=false}
