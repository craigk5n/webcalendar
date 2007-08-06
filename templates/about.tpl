{include file="header.tpl"}
<div align="left" style="margin-left:4px; position:absolute; bottom:0" >
{if ! $credits}
  <a title="{$smarty.const.PROGRAM_NAME}" href="{$smarty.const.PROGRAM_URL}" target="_blank">
      <h2 style="margin:0">{'Title'|translate}</h2>
      <p>{'version'|translate} {$smarty.const.PROGRAM_VERSION}</p>
      <p>{$smarty.const.PROGRAM_DATE}</p></a>
      <p>&nbsp;</p>
      <p>{'Webcalendar is a PHP application used...'|translate}</p>
{else}
 {literal}
	<script language="javascript1.2" type="text/javascript">
        var
          scrollW="235px",
          scrollH="250px",
          copyS=scrollS=1,
          pauseS=0,
          scrollcontent="{/literal}{$data}{literal}",
          actualH='',
          cross_scroll;

        function populate(){
          cross_scroll=document.getElementById("scroller");
          cross_scroll.innerHTML=scrollcontent;
          actualH=cross_scroll.offsetHeight;
          lefttime=setInterval("scrollMe ()",30);
        }

        window.onload=populate;

        function scrollMe(){
          if (parseInt (cross_scroll.style.top)>(actualH* (-1)+8))
            cross_scroll.style.top=parseInt(cross_scroll.style.top)-copyS+"px";
          else
            cross_scroll.style.top=parseInt(scrollH)+8+"px";
        }

        with (document){
          write('<div style="position:relative; width:'+scrollW+'; height: '
            + scrollH +'; overflow:hidden;" onMouseover="copyS=pauseS" '
            + 'onMouseout="copyS=scrollS"><div id="scroller"></div></div>');
        }
      </script>
{/literal}
{/if}
<hr />
<div align="center" style="margin:10px;">
  <form action="about.php" name="aboutform" method="post">
    <input type="submit" name={if $credits}"About" value="{'About'|translate}{else}
	"Credits" value="{'Credits'|translate}{/if}" />&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
    <input type="button" name="ok" value="{'OK'|translate}" onclick="window.close ()" />
  </form>
</div>
</div>
{include file="footer.tpl" include_nav_links=false}


