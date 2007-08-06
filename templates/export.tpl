{include file="header.tpl"}
<h2>{'Export'|translate}</h2>
    <form action="export.php" method="post" name="exportform" id="exportform">
      <table>
        <tr>
          <td><label for="exformat">{'Export format'|translate}:</label></td>
          <td>{$exportSelectStr}
          </td>
        </tr>
{if $users }
       <tr>
         <td class="aligntop"><label for="caluser">{'Calendar'|translate}:</label>
	      </td>
	        <td>
	          <select name="calUser" id="caluser" size="{$size}">
		     {foreach from=$users key=k item=v}
              <option value="{$k}" {$v.selected}>{$v.fullname}</option>
		     {/foreach}
           </select>
	       </td>
       </tr>
{/if}
{if $categories}
        <tr id="catfilter">
          <td><label for="cat_filter">{'Categories'|translate}:</label></td>
          <td>
            <select name="cat_filter" id="cat_filter">
            {foreach from=$categories key=k item=v}
             <option value="{$k}">{$v.cat_name}</option>
            {/foreach}
            </select>
          </td>
        </tr>
{/if}
{if $p.LAYERS_STATUS}
        <tr>
          <td>&nbsp;</td>
          <td>
            <input type="checkbox" name="include_layers" id="include_layers" value="y" />
            <label for="include_layers">{'Include all layers'|translate}</label>
          </td>
        </tr>
{/if}
        <tr>
          <td>&nbsp;</td>
          <td>
            <input type="checkbox" name="include_deleted" id="include_deleted" value="y" />
            <label for="include_deleted">{'Include deleted entries'|translate}</label>
          </td>
        </tr>
        <tr>
          <td>&nbsp;</td>
          <td>
            <input type="checkbox" name="use_all_dates" id="exportall" value="y" onchange="toggle_datefields ( 'dateArea', this );"/>
            <label for="exportall">{'Export all dates'|translate}</label>
          </td>
        </tr>
        <tr>
          <td colspan="2">
            <table id="dateArea">
              <tr>
                <td><label>{'Start date'|translate}:</label></td>
                <td>{date_selection prefix='from'}</td>
              </tr>
              <tr>
                <td><label>{'End date'|translate}:</label></td>
                <td>{date_selection prefix='end'}</td>
              </tr>
              <tr>
                <td><label>{'Modified since'|translate}:</label></td>
                <td>{date_selection prefix='mod' date=$moddate}</td>
              </tr>
            </table>
          </td>
        </tr>
        <tr>
          <td colspan="2"><input type="submit" value="{'Export'|translate}" /></td>
        </tr>
      </table>
    </form>
{include file="footer.tpl"}
