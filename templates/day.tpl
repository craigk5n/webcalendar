   {include file="header.tpl"}
   <table width="100%" cellpadding="1">
      <tr>
        <td class="alignT" width="80%">
          {include file="navigation.tpl"}
        </td>
        <td>&nbsp;</td>
      </tr>
      <tr>
        <td>
          {day_glance date=$WC->thisdate user=$WC->userLoginId()}
        </td>
        <td class="alignT" rowspan="2">
          <!-- START MINICAL -->
          <div class="minicalcontainer alignC">
            {small_month dateYmd=$WC->thisdate showyear=true}
          </div><br />
          <div id="minitask"></div>
        </td>
      </tr>
    </table>
    {include file="footer.tpl"}
