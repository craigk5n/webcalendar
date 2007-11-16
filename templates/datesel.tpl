{include file="header.tpl"}
<div align="center">
	<table class="alignC" width="100%">
		<tr>
			<td align="center" valign="middle">
				<table class="alignC">
					<tr>
						<td><a title="{$Previous}" class="prev" {$prevdate}>
							<img src="images/leftarrowsmall.gif"
								 alt="{$Previous}" /></a></td>
						<th colspan="5">&nbsp;{$monthStr}&nbsp;{$thisyear}&nbsp;</th>
						<td><a title="{$Next}"class="next" {$nextDate}>
							<img src="images/rightarrowsmall.gif"
								 alt="{$Next}" /></a></td>
					</tr>
					<tr class="day">
				{foreach from=$wkdays item=v}			
					  <td>{$v}</td>
        {/foreach}
          </tr>
			{foreach from=$mweeks item=mv}				
          <tr>
				{foreach from=$mdays key=k item=v} 
            <td {$v.class}>{$v.display}</td>
        {/foreach}
         </tr>
      {/foreach}

       </table>
     </td>
   </tr>
 </table>
</div>
  <script language="javascript" type="text/javascript">
  <!-- <![CDATA[
  function sendDate ( date ) {ldelim}
    year = date.substring ( 0, 4 );
    month = date.substring ( 4, 6 );
    day = date.substring ( 6, 8 );
    sday = window.opener.document.{$form}.{$fday};
    smonth = window.opener.document.{$form}.{$fmonth};
    syear = window.opener.document.{$form}.{$fyear};
    sday.selectedIndex = day - 1;
    smonth.selectedIndex = month - 1;
    for ( i = 0; i < syear.length; i++ ) {ldelim}
      if ( syear.options[i].value == year ) {ldelim}
        syear.selectedIndex = i;
      {rdelim}
    {rdelim}
    window.close ();
  {rdelim}
  //]]> -->
  </script>
{include file="footer.tpl" include_nav_links=false}