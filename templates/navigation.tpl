  <div class="topnav" > 
  {if $navArrows && !$WC->friendly ()}
    <a title="{$Next}" class="next" href="{$navName}.php?{$u_url}date={$nextYmd}{$caturl}">
	  <img src="images/rightarrow.gif" alt="{$Next}" /></a>
    <a title="{$Previous}" class="prev" href="{$navName}.php?{$u_url}date={$prevYmd}{$caturl}">
	  <img src="images/leftarrow.gif" alt="{$Previous}" /></a>
  {/if}
    <div class="title">
      <span class="date">

	  {if $navName == day }
		{$WC->thisdate|date_to_str }
	  {elseif $navName == week }
		{$navStart|date_to_str:'':false }
		 &nbsp;-&nbsp;
		 {$navEnd|date_to_str:'':false}
		 {if $p.DISPLAY_WEEKNUMBER }
		   (__Week__&nbsp;{$WC->_startdate|date_format:'%W'})
		 {/if}
	  {elseif $navName == month || $navName == view_l }
	   {$spacer} {$WC->thisdate|date_to_str:DATE_FORMAT_MY:false:false}
	   {/if}
     </span>
	   <span class="user">
       <br />{$navFullname}<br />
       {$navAdmin}
       {$navAssistant}
	   </span>
		 <div align="center">
	 {include file="category_menu.tpl"}	
	   </div>  
    </div>
   </div>
