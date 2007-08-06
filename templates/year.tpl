 {include file="header.tpl"}
 {include file="navigation.tpl"}
 
  <div align="center">
  <table id="monthgrid">
    <tr>
      <td>{$monthArray[1]}</td>
      <td>{$monthArray[2]}</td>
      <td>{$monthArray[3]}</td>
	{if $p.SCREEN_WIDTH < 1024}</tr><tr>{/if}
      <td>{$monthArray[4]}</td>
	{if $p.SCREEN_WIDTH >= 1024}</tr><tr>{/if}
      <td>{$monthArray[5]}</td>
      <td>{$monthArray[6]}</td>
	{if $p.SCREEN_WIDTH < 1024}</tr><tr>{/if}
      <td>{$monthArray[7]}</td>
      <td>{$monthArray[8]}</td>
	{if $p.SCREEN_WIDTH >= 1024}</tr><tr>{/if}
      <td>{$monthArray[9]}</td>
	{if $p.SCREEN_WIDTH < 1024}</tr><tr>{/if}
      <td>{$monthArray[10]}</td>
      <td>{$monthArray[11]}</td>
      <td>{$monthArray[12]}</td>
    </tr>
   </table>
  </div>
  <br />
{include file="footer.tpl"}	
