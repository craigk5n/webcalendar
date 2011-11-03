// $Id$

// The following code is used to support the small popups that give the full
// description of an event when the user moves the mouse over it.
// Thanks to Klaus Knopper (www.knoppix.com) for this script.
// It has been modified to work with the existing WebCalendar
// architecture on 02/25/2005.
//
// 03/05/2005 Prevent popup from going off screen by setting maximum width,
// which is configurable.
//
// Bubblehelp infoboxes, (c) 2002 Klaus Knopper <infobox@knopper.net>
// You can copy/modify and distribute this code under the conditions
// of the GNU GENERAL PUBLIC LICENSE Version 2.

var kon,         // Are we using KDE Konqueror?
followMe = 1,    // allow popup to follow cursor...turn off for better performance
idiv = null,     // Pointer to infodiv container
maxwidth = 300,  // maximum width of popup window
popupH,          // height of popup
popupW,          // width of popup
px = 'px',       // position suffix with "px" in some cases
x, y, winW, winH,// Current help position and main window size
xoffset = 8,     // popup distance from cursor x coordinate
yoffset = 12;    // popup distance from cursor y coordinate

addLoadListener(function () {
    kon = (navigator.userAgent.indexOf('konqueror') > 0);
    idiv = null;
    winW = 800;
    winH = 600;
    x = y = 0;

    if (followMe) {
      document.onmousemove = mousemove;

    // Workaround for Konqueror bug: Fix browser confusion on resize.
    if (kon) {
      setTimeout('window.onresize = rebrowse', 2000);
    }

    var entries = document.getElementsBySelector('a.entry');

    entries = entries.concat(document.getElementsBySelector('a.layerentry'));
    entries = entries.concat(document.getElementsBySelector('a.unapprovedentry'));
    entries = entries.concat(document.getElementsBySelector('tr.task'));

    for (var i = 0, j = entries.length; i < j; i++) {
      entries[i].onmouseover = function (event) {
        showPopUp(event, 'eventinfo-' + this.id);
        return true;
      }
      entries[i].onmouseout = function () {
        hidePopUp('eventinfo-' + this.id);
        return true;
      }
    }
  }
});
function rebrowse() {
  window.location.reload();
}

function hidePopUp(name) {
  idiv.style.visibility = 'hidden';
  idiv = null;
}

function gettip(name) {
  return (document[name]
         ? document[name]
         : (document.getElementById(name)
           ? document.getElementById(name)
           : 0));
}

function showPopUp(evt, name) {
  if (idiv)
    hide(name);

  idiv = gettip(name);

  if (idiv) {
    scrollX = scrollY = 0;

    scrollX = (typeof window.pageXOffset == 'number'
       ? window.pageXOffset
       : (document.documentElement && document.documentElement.scrollLeft
         ? document.documentElement.scrollLeft
         : (document.body && document.body.scrollLeft
           ? document.body.scrollLeft
           : window.scrollX)));
    scrollY = (typeof window.pageYOffset == 'number'
       ? window.pageYOffset
       : (document.documentElement && document.documentElement.scrollTop
         ? document.documentElement.scrollTop
         : (document.body && document.body.scrollTop
           ? document.body.scrollTop
           : window.scrollY)));
    winW = (window.innerWidth
       ? window.innerWidth + window.pageXOffset - 16
       : document.body.offsetWidth - 20);
    winH = (window.innerHeight
       ? window.innerHeight
       : document.body.offsetHeight) + scrollY;

    popupW = idiv.offsetWidth;
    popupH = idiv.offsetHeight;

    showtip(evt);
  }
}

function recursive_resize(ele, width, height) {
  if (ele.nodeType != 1)
    return;

  if (width != null && ele.offsetWidth > width)
    ele.style.width = width + px;

  if (height != null && ele.offsetHeight > height)
    ele.style.height = height + px;

  for (var i = 0, j = ele.childNodes.length; i < j; i++) {
    recursive_resize(ele.childNodes[i],
      width - ele.childNodes[i].offsetLeft,
      height - ele.childNodes[i].offsetTop);
  }
}

function showtip(e) {
  e = (e ? e : window.event);

  if (idiv) {
    if (e) {
      x = (e.pageX
         ? e.pageX
         : (e.clientX
           ? e.clientX + scrollX
           : 0));
      y = (e.pageY
         ? e.pageY
         : (e.clientY
           ? e.clientY + scrollY
           : 0));
    } else {
      x = y = 0;
    }
    // Make sure we don't go off screen.
    recursive_resize(idiv, maxwidth);
    popupW = idiv.offsetWidth;
    popupH = idiv.offsetHeight;
    idiv.style.top = (y + popupH + yoffset > winH - yoffset
       ? (winH - popupH - yoffset < 0 ? 0 : winH - popupH - yoffset)
       : y + yoffset) + px;
    idiv.style.left = (x + popupW + xoffset > winW - xoffset
       ? x - popupW - xoffset : x + xoffset) + px;
    idiv.style.visibility = 'visible';
  }
}

function mousemove(e) {
  showtip(e);
}
