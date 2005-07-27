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
// maximum width, which is configurable
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

  var entries = document.getElementsBySelector("a.entry");
  entries = entries.concat(document.getElementsBySelector("a.layerentry"));
  entries = entries.concat(document.getElementsBySelector("a.unapprovedentry"));

  for (var i = 0; i < entries.length; i++) {
    entries[i].onmouseover = function(event) {
   show(event, "eventinfo-" + this.id);
   window.status = "<?php etranslate("View this entry"); ?>";
   return true;
  }
    entries[i].onmouseout = function() {
      hide("eventinfo-" + this.id);
   window.status = "";
   return true;
    }
  }

}

function hide(name){
  idiv.style.visibility=ns4?"hide":"hidden";
  idiv=null;
}

function gettip(name) {
  return (document.layers&&document.layers[name])?document.layers[name]:(document.all && document.all[name])?document.all[name]:document[name]?document[name]:(document.getElementById(name)?document.getElementById(name):0);
}

function show(evt, name){
  if(idiv) hide(name);
  idiv=gettip(name);
  if(idiv){
   scrollX =0; scrollY=0;
   winW=(window.innerWidth)? window.innerWidth+window.pageXOffset-16:document.body.offsetWidth-20;
   winH=(window.innerHeight)?window.innerHeight+window.pageYOffset  :document.body.offsetHeight;
   scrollX=(typeof window.pageXOffset == "number")? window.pageXOffset:(document.documentElement && document.documentElement.scrollLeft)?document.documentElement.scrollLeft:(document.body && document.body.scrollLeft)?document.body.scrollLeft:window.scrollX;
   scrollY=(typeof window.pageYOffset == "number")? window.pageYOffset:(document.documentElement && document.documentElement.scrollTop)?document.documentElement.scrollTop:(document.body && document.body.scrollTop)?document.body.scrollTop:window.scrollY;
   popupW = idiv.offsetWidth;
   popupH = idiv.offsetHeight;   

   showtip(evt);
  }
}

function recursive_resize(ele, width, height) {
  if (ele.nodeType != 1) {
    return;
  }

  if (width != null && ele.offsetWidth > width) {
    ele.style.width = width + px;
  }

  if (height != null && ele.offsetHeight > height) {
    ele.style.height = height + px;
  }

  for (var i = 0; i < ele.childNodes.length; i++) {
    recursive_resize(ele.childNodes[i],
                     width - ele.childNodes[i].offsetLeft,
                     height - ele.childNodes[i].offsetTop);
  }
}

function showtip(e){
  e = e? e: window.event;
  if(idiv) {
    if(e)   {
      x=e.pageX?e.pageX:e.clientX?e.clientX + scrollX:0; 
      y=e.pageY?e.pageY:e.clientY?e.clientY + scrollY:0;
    } else {
      x=0; y=0;
    }

    // Make sure we don't go off screen
    recursive_resize(idiv, maxwidth);
    popupW = idiv.offsetWidth;
    popupH = idiv.offsetHeight;

    if (x + popupW + xoffset > winW - xoffset) {
      idiv.style.left = (winW - popupW - xoffset) + px;
    } else {
      idiv.style.left = (x + xoffset) + px;
    }

    if (y + popupH + yoffset > winH - yoffset) {
      if (winH - popupH - yoffset < 0) {
        idiv.style.top = 0 + px;
      } else {
        idiv.style.top = (winH - popupH - yoffset) + px;
      }
    } else {
      idiv.style.top = (y + yoffset) + px;
    }

    idiv.style.visibility=ns4?"show":"visible";
  }
}

function mousemove(e){
  showtip(e);
}
// Initialize after loading the page
addLoadHandler(infoinit);
//]]> -->
</script>
