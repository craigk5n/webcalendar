<?php
/* $Id: popups.php,v 1.29.2.2 2007/08/06 02:28:27 cknudsen Exp $ */
?>
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

var
  ns4,              // Are we using Netscape4?
  ie4,              // Are we using Internet Explorer Version 4?
  ie5,              // Are we using Internet Explorer Version 5 and up?
  kon,              // Are we using KDE Konqueror?
  followMe = 1,     // allow popup to follow cursor...turn off for better performance
  idiv = null,      // Pointer to infodiv container
  maxwidth = 300,   // maximum width of popup window
  popupH,           // height of popup
  popupW,           // width of popup
  px = 'px',        // position suffix with "px" in some cases
  x, y, winW, winH, // Current help position and main window size
  xoffset = 8,      // popup distance from cursor x coordinate
  yoffset = 12;     // popup distance from cursor y coordinate

function nsfix () {
  setTimeout ( 'window.onresize = rebrowse', 2000 );
}

if ( typeof document.getElementsBySelector == 'undefined' ) {
  /* document.getElementsBySelector ( selector )
     - returns an array of element objects from the current document
       matching the CSS selector. Selectors can contain element names,
       class names and ids and can be nested. For example:

         elements = document.getElementsBySelect('div#main p a.external')

       Will return an array of all 'a' elements with 'external' in their
       class attribute that are contained inside 'p' elements that are
       contained inside the 'div' element which has id="main".

     New in version 0.4: Support for CSS2 and CSS3 attribute selectors:
     See http://www.w3.org/TR/css3-selectors/#attribute-selectors

     Version 0.4 - Simon Willison, March 25th 2003
     -- Works in Phoenix 0.5, Mozilla 1.3, Opera 7, Internet Explorer 6,
        Internet Explorer 5 on Windows.
     -- Opera 7 fails.
  */

  function getAllChildren ( e ) {
    // Returns all children of element. Workaround required for IE5/Windows. Ugh.
    return ( e.all ? e.all : e.getElementsByTagName ( '*' ) );
  }

  document.getElementsBySelector = function ( selector ) {
    // Attempt to fail gracefully in lesser browsers.
    if ( ! document.getElementsByTagName )
      return new Array ();

    // Split selector in to tokens.
    var
      tokens = selector.split ( ' ' ),
      currentContext = new Array ( document );
    for ( var i = 0; i < tokens.length; i++ ) {
      token = tokens[i].replace ( /^\s+/,'' ).replace ( /\s+$/,'' );
      if ( token.indexOf ( '#' ) > -1 ) {
        // Token is an ID selector.
        var
          bits = token.split ( '#' ),
          tagName = bits[0],
          id = bits[1],
          element = document.getElementById ( id );
        if ( tagName && element.nodeName.toLowerCase () != tagName ) {
          // Tag with that ID not found, return false.
          return new Array ();
        }
        // Set currentContext to contain just this element.
        currentContext = new Array ( element );
        continue; // Skip to next token.
      }
      if ( token.indexOf ( '.' ) > -1 ) {
      // Token contains a class selector.
        var
          bits = token.split ( '.' ),
          tagName = bits[0],
          className = bits[1];
        if ( ! tagName )
          tagName = '*';

        // Get elements matching tag, filter them for class selector.
        var
          found = new Array,
          foundCount = 0;
        for ( var h = 0; h < currentContext.length; h++ ) {
          var elements;
          elements = ( tagName == '*'
            ? getAllChildren ( currentContext[h] )
            : currentContext[h].getElementsByTagName ( tagName ) );

          for ( var j = 0; j < elements.length; j++ ) {
            found[foundCount++] = elements[j];
          }
        }
        currentContext = new Array;
        var currentContextIndex = 0;
        for ( var k = 0; k < found.length; k++ ) {
          if ( found[k].className && found[k].className.match ( new RegExp ( '\\b'+className+'\\b' ) ) )
            currentContext[currentContextIndex++] = found[k];
        }
        continue; // Skip to next token.
      }
      // Code to deal with attribute selectors.
      if ( token.match ( /^(\w*)\[(\w+)([=~\|\^\$\*]?)=?"?([^\]"]*)"?\]$/ ) ) {
        var
          tagName = RegExp.$1,
          attrName = RegExp.$2,
          attrOperator = RegExp.$3,
          attrValue = RegExp.$4;
        if ( ! tagName )
          tagName = '*';

        // Grab all of the tagName elements within current context.
        var
          found = new Array,
          foundCount = 0;
        for ( var h = 0; h < currentContext.length; h++ ) {
          var elements;
            elements = ( tagName == '*'
              ? getAllChildren(currentContext[h])
              : currentContext[h].getElementsByTagName ( tagName ) );

          for ( var j = 0; j < elements.length; j++ ) {
            found[foundCount++] = elements[j];
          }
        }
        currentContext = new Array;
        var
          currentContextIndex = 0,
          checkFunction; // This function will be used to filter the elements.
        switch ( attrOperator ) {
          case '=': // Equality
            checkFunction = function ( e ) {
              return ( e.getAttribute ( attrName ) == attrValue );
            };
            break;
          case '~': // Match one of space seperated words.
            checkFunction = function ( e ) {
              return ( e.getAttribute ( attrName ).match ( new RegExp ( '\\b'+attrValue+'\\b' ) ) );
            };
            break;
          case '|': // Match start with value followed by optional hyphen.
            checkFunction = function ( e ) {
              return ( e.getAttribute ( attrName ).match ( new RegExp ( '^'+attrValue+'-?' ) ) );
            };
            break;
          case '^': // Match starts with value.
            checkFunction = function ( e ) {
              return ( e.getAttribute(attrName ).indexOf ( attrValue ) == 0);
            };
            break;
          case '$': // Match ends with value - fails with "Warning" in Opera 7.
            checkFunction = function ( e ) {
              return ( e.getAttribute ( attrName ).lastIndexOf ( attrValue ) == e.getAttribute ( attrName ).length - attrValue.length );
            };
            break;
          case '*': // Match ends with value.
            checkFunction = function ( e ) {
              return ( e.getAttribute ( attrName ).indexOf ( attrValue ) > -1);
            };
            break;
          default: // Just test for existence of attribute.
            checkFunction = function ( e ) {
              return e.getAttribute ( attrName );
            };
        }
        currentContext = new Array;
        var currentContextIndex = 0;
        for ( var k = 0; k < found.length; k++ ) {
          if ( checkFunction ( found[k] ) )
            currentContext[currentContextIndex++] = found[k];
        }
        continue; // Skip to next token.
      }
      // If we get here, token is JUST an element (not a class or ID selector).
      tagName = token;
      var
        found = new Array,
        foundCount = 0;
      for ( var h = 0; h < currentContext.length; h++ ) {
        var elements = currentContext[h].getElementsByTagName ( tagName );
        for ( var j = 0; j < elements.length; j++ ) {
          found[foundCount++] = elements[j];
        }
      }
      currentContext = found;
    }
    return currentContext;
  }

  /* That revolting regular expression explained.
  /^(\w+)\[(\w+)([=~\|\^\$\*]?)=?"?([^\]"]*)"?\]$/
    \---/  \---/\-------------/    \-------/
      |      |         |               |
      |      |         |           The value
      |      |    ~,|,^,$,* or =
      |   Attribute
     Tag
  */
}

function rebrowse () {
  window.location.reload ();
}

function infoinit () {
  ns4 = ( document.layers );
  ie4 = ( document.all );
  ie5 = ( ie4 && (
    navigator.userAgent.indexOf ( 'MSIE 5' ) > 0 ||
    navigator.userAgent.indexOf ( 'MSIE 6' ) > 0 ||
    navigator.userAgent.indexOf ( 'MSIE 7' ) > 0 ) );
  kon = ( navigator.userAgent.indexOf ( 'konqueror' ) > 0 );
  idiv = null;
  winW = 800;
  winH = 600;
  x = y = 0;
  if ( followMe ) {
    document.onmousemove = mousemove;
    if ( ns4 && document.captureEvents )
      document.captureEvents ( Event.MOUSEMOVE );
  }
  // Workaround for another Netscape bug: Fix browser confusion on resize.
  // Obviously Konqueror has a similar problem. :-(
  if ( ns4 || kon )
    nsfix ();

  if ( ns4 )
    px = '';

  var entries = document.getElementsBySelector ( 'a.entry' );
  entries = entries.concat ( document.getElementsBySelector ( 'a.layerentry' ) );
  entries = entries.concat ( document.getElementsBySelector ( 'a.unapprovedentry' ) );
  entries = entries.concat ( document.getElementsBySelector ( 'tr.task' ) );
  for ( var i = 0; i < entries.length; i++ ) {
    entries[i].onmouseover = function ( event ) {
      showPopUp ( event, 'eventinfo-' + this.id );
      return true;
    }
    entries[i].onmouseout = function () {
      hidePopUp ( 'eventinfo-' + this.id );
      return true;
    }
  }
}

function hidePopUp ( name ) {
  idiv.style.visibility = ( ns4 ? 'hide' : 'hidden' );
  idiv = null;
}

function gettip ( name ) {
  return ( document.layers && document.layers[name]
    ? document.layers[name]
    : ( document.all && document.all[name]
      ? document.all[name]
      : ( document[name]
        ? document[name]
        : ( document.getElementById ( name )
          ? document.getElementById ( name )
          : 0 ) ) ) );
}

function showPopUp ( evt, name ) {
  if ( idiv )
    hide ( name );

  idiv = gettip ( name );
  if ( idiv ) {
    scrollX = scrollY = 0;

    scrollX = ( typeof window.pageXOffset == 'number'
      ? window.pageXOffset
      : ( document.documentElement && document.documentElement.scrollLeft
        ? document.documentElement.scrollLeft
        : ( document.body && document.body.scrollLeft
          ? document.body.scrollLeft
          : window.scrollX ) ) );
    scrollY = ( typeof window.pageYOffset == 'number'
      ? window.pageYOffset
      : ( document.documentElement && document.documentElement.scrollTop
        ? document.documentElement.scrollTop
        : ( document.body && document.body.scrollTop
          ? document.body.scrollTop
          : window.scrollY ) ) );
    winW = ( window.innerWidth
      ? window.innerWidth + window.pageXOffset - 16
      : document.body.offsetWidth - 20 );
    winH = ( window.innerHeight
      ? window.innerHeight
      : ( ie5
        ? 500
        : document.body.offsetHeight ) ) + scrollY;

    popupW = idiv.offsetWidth;
    popupH = idiv.offsetHeight;

    showtip ( evt );
  }
}

function recursive_resize ( ele, width, height ) {
  if ( ele.nodeType != 1 )
    return;

  if ( width != null && ele.offsetWidth > width )
    ele.style.width = width + px;

  if ( height != null && ele.offsetHeight > height )
    ele.style.height = height + px;

  for ( var i = 0; i < ele.childNodes.length; i++ ) {
    recursive_resize ( ele.childNodes[i],
      width - ele.childNodes[i].offsetLeft,
      height - ele.childNodes[i].offsetTop );
  }
}

function showtip ( e ) {
  e = ( e ? e : window.event );
  if ( idiv ) {
    if ( e ) {
      x = ( e.pageX
        ? e.pageX
        : ( e.clientX
          ? e.clientX + scrollX
          : 0 ) );
      y = ( e.pageY
        ? e.pageY
        : ( e.clientY
          ? e.clientY + scrollY
          : 0 ) );
    } else {
      x = y = 0;
    }
    // Make sure we don't go off screen.
    recursive_resize ( idiv, maxwidth );
    popupW = idiv.offsetWidth;
    popupH = idiv.offsetHeight;
    idiv.style.top = ( y + popupH + yoffset > winH - yoffset
      ? ( winH - popupH - yoffset < 0 ? 0 : winH - popupH - yoffset )
      : y + yoffset ) + px ;
    idiv.style.left = ( x + popupW + xoffset > winW - xoffset
      ? x - popupW - xoffset : x + xoffset ) + px;
    idiv.style.visibility = ( ns4 ? 'show' : 'visible' );
  }
}

function mousemove ( e ) {
  showtip ( e );
}
// Initialize after loading the page.
if ( typeof addLoadHandler == 'undefined' ) {
  function addLoadHandler ( handler )  {
    if ( window.addEventListener ) {
      window.addEventListener ( 'load',handler,false);
    } else
    if ( window.attachEvent ) {
      window.attachEvent ( 'onload',handler );
    } else
    if ( window.onload ) {
      var oldHandler = window.onload;
      window.onload = function piggyback () {
        oldHandler ();
        handler ();
      };
    } else {
      window.onload = handler;
    }
  }
}

addLoadHandler ( infoinit );
