<script type="text/javascript">
<!-- <![CDATA[
// The following code is used to support the small popups that
// give the full description of an event when the user move the
// mouse over it.
// Thanks to Klaus Knopper (www.knoppix.com) for this script.
// It has been modified to work with the existing WebCalendar
// architecture on 02/25/2005
//
// 03/05/2005 Prevent popup from going off screen by setting
// maximum width, which is cnfigurable
//
// Bubblehelp infoboxes, (C) 2002 Klaus Knopper <infobox@knopper.net>
// You can copy/modify and distribute this code under the conditions
// of the GNU GENERAL PUBLIC LICENSE Version 2.
//
var ns4            // Are we using Netscape4?
var ie4            // Are we using Internet Explorer Version 4?
var ie5            // Are we using Internet Explorer Version 5 and up?
var kon            // Are we using KDE Konqueror?
var x,y,winW,winH  // Current help position and main window size
var idiv=null      // Pointer to infodiv container
var px="px"        // position suffix with "px" in some cases
var popupW         // width of popup
var popupH         // height of popup
var xoffset = 8    // popup distance from cursor x coordinate
var yoffset = 12   // popup distance from cursor y coordinate
var followMe = 1   // allow popup to follow cursor...turn off for better performance
var maxwidth = 300 // maximum width of popup window

function nsfix(){setTimeout("window.onresize = rebrowse", 2000);}

function rebrowse(){window.location.reload();}

function infoinit(){
  ns4=(document.layers)?true:false, ie4=(document.all)?true:false;
  ie5=((ie4)&&((navigator.userAgent.indexOf('MSIE 5')>0)||(navigator.userAgent.indexOf('MSIE 6')>0)))?true:false;
  kon=(navigator.userAgent.indexOf('konqueror')>0)?true:false;
  x=0;y=0;winW=800;winH=600;
  idiv=null;
  if (followMe) {
    document.onmousemove = mousemove;
    if(ns4&&document.captureEvents) document.captureEvents(Event.MOUSEMOVE);
  }
  // Workaround for just another netscape bug: Fix browser confusion on resize
  // obviously conqueror has a similar problem :-(
  if(ns4||kon){ nsfix() }
  if(ns4) { px=""; }
}

function hide(name){
  idiv.visibility=ns4?"hide":"hidden";
  idiv=null;
}

function gettip(name){return (document.layers&&document.layers[name])?document.layers[name]:(document.all&&document.all[name]&&document.all[name].style)?document.all[name].style:document[name]?document[name]:(document.getElementById(name)?document.getElementById(name).style:0);}

function show(evt, name){
  if(idiv) hide(name);
  idiv=gettip(name);
  if(idiv){
   scrollX =0; scrollY=0;
   winW=(window.innerWidth)? window.innerWidth+window.pageXOffset-16:document.body.offsetWidth-20;
   winH=(window.innerHeight)?window.innerHeight+window.pageYOffset  :document.body.offsetHeight;
   scrollX=(typeof window.pageXOffset == "number")? window.pageXOffset:(document.documentElement && document.documentElement.scrollLeft)?document.documentElement.scrollLeft:(document.body && document.body.scrollLeft)?document.body.scrollLeft:window.scrollX;
   scrollY=(typeof window.pageYOffset == "number")? window.pageYOffset:(document.documentElement && document.documentElement.scrollTop)?document.documentElement.scrollTop:(document.body && document.body.scrollTop)?document.body.scrollTop:window.scrollY;
   popupW = document.getElementById(name).offsetWidth;
   popupH = document.getElementById(name).offsetHeight;   

   showtip(evt);
  }
}

function showtip(e){
  e = e? e: window.event;
  if(idiv) {
    if(e)   {
      x=e.pageX?e.pageX:e.clientX?e.clientX + scrollX:0; 
      y=e.pageY?e.pageY:e.clientY?e.clientY + scrollY:0;
    }
    else {
      x=0; y=0;
    }
    // MAke sure we don't go off screen
    if ( popupW > maxwidth ) { 
      popupW = maxwidth;
      idiv.width = maxwidth + px;
    }  
    idiv.left=(((x + popupW + xoffset)>winW)?x - popupW - xoffset:x + xoffset)+px;
    if ((popupH + yoffset)>winH) {
      idiv.top= yoffset + px;
    } else {
      idiv.top=(((y + popupH + yoffset)>winH)?winH - popupH - yoffset:y + yoffset)+px;
    }
    idiv.visibility=ns4?"show":"visible";
    }
}

function mousemove(e){
  showtip(e);
}
// Initialize after loading the page
window.onload=infoinit;
//]]> -->
</script>