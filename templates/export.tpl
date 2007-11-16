{include file="header.tpl"}
<h2>__Export__</h2>
    <form action="export.php" method="post" name="exportform" id="exportform">
      <table>
        <tr>
          <td><label for="exformat">__Export format__:</label></td>
          <td>{generate_export_select jsaction='toggel_catfilter'}
          </td>
        </tr>
{if $users }
       <tr>
         <td class="alignT"><label for="caluser">__Calendar__:</label>
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
          <td><label for="cat_filter">__Categories__:</label></td>
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
            <label for="include_layers">__Include all layers__</label>
          </td>
        </tr>
{/if}
        <tr>
          <td>&nbsp;</td>
          <td>
            <input type="checkbox" name="include_deleted" id="include_deleted" value="y" />
            <label for="include_deleted">__Include deleted entries__</label>
          </td>
        </tr>
        <tr>
          <td>&nbsp;</td>
          <td>
            <input type="checkbox" name="use_all_dates" id="exportall" value="y" onchange="toggle_datefields ( 'dateArea', 'exportall');"/>
            <label for="exportall">__Export all dates__</label>
          </td>
        </tr>
        <tr>
          <td colspan="2">
            <table id="dateArea">
              <tr>
                <td><label>__Start date__:</label></td>
                <td>{date_selection prefix='from'}</td>
              </tr>
              <tr>
                <td><label>__End date__:</label></td>
                <td>{date_selection prefix='end'}</td>
              </tr>
              <tr>
                <td><label>__Modified since__:</label></td>
                <td>{date_selection prefix='mod' date=$moddate}</td>
              </tr>
            </table>
          </td>
        </tr>
        <tr>
          <td colspan="2"><input type="submit" value="__Export__" /></td>
        </tr>
      </table>
    </form>
{include file="footer.tpl"}
