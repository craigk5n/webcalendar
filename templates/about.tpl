{include file="header.tpl"}
<div align="left" style="margin-left:4px; position:absolute; bottom:0" >
{if ! $credits}
  <a title="{$smarty.const.PROGRAM_NAME}" href="{$smarty.const.PROGRAM_URL}" target="_blank">
      <h2 style="margin:0">__Title__</h2>
      <p>__version__ {$smarty.const._WEBCAL_PROGRAM_VERSION}</p>
      <p>{$smarty.const.PROGRAM_DATE}</p></a>
      <p>&nbsp;</p>
      <p>__Webcalendar is a PHP application used...__</p>
{else}
	<script language="javascript1.2" type="text/javascript">
        var
          scrollW="235px",
          scrollH="250px",
          copyS=scrollS=1,
          pauseS=0,
          scrollcontent="{$data}",
          actualH='',
          cross_scroll;

        function populate(){ldelim}
          cross_scroll=document.getElementById("scroller");
          cross_scroll.innerHTML=scrollcontent;
          actualH=cross_scroll.offsetHeight;
          lefttime=setInterval("scrollMe ()",30);
        {rdelim}

        window.onload=populate;

        function scrollMe() {ldelim}
          if (parseInt (cross_scroll.style.top)>(actualH* (-1)+8))
            cross_scroll.style.top=parseInt(cross_scroll.style.top)-copyS+"px";
          else
            cross_scroll.style.top=parseInt(scrollH)+8+"px";
        {rdelim}

        with (document) {ldelim}
          write('<div style="position:relative; width:'+scrollW+'; height: '
            + scrollH +'; overflow:hidden;" onMouseover="copyS=pauseS" '
            + 'onMouseout="copyS=scrollS"><div id="scroller"></div></div>');
        {rdelim}
      </script>
{/if}
<hr />
<div align="center" style="margin:10px;">
  <form action="about.php" name="aboutform" method="post">
    <input type="submit" name={if $credits}"About" value="__About__{else}
	"Credits" value="__Credits__{/if}" />&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
    <input type="button" name="ok" value="__Ok__" onclick="window.close ()" />
  </form>
</div>
</div>
{include file="footer.tpl" include_nav_links=false}


