// $Id$
// This file will usually get called in by print_header().
// Some duplicate functions moved here from "popups.js", "utils.js", and others.

var u = location.href,
d = (u.indexOf('install') >= 0 ? '../' : ''),
p = u.substring(u.lastIndexOf('\/') + 1);

p = p.substring(0, p.indexOf('.'));

// Link in the CSS/JS files for the specific page, if available.
// And other files that are going to see extensive use later,
// with HTML5 and CSS3 especially.
linkFile(d + 'includes/css/' + p + '.css', 'link');

var tmp = [
  'dateformat',
  'geturl',
// Not sure if we should call these from offsite or not.
// 'http://code.jquery.com/jquery-1.7.min.js'
// 'http://cdnjs.cloudflare.com/ajax/libs/modernizr/2.0.6/modernizr.min.js'
  'jquery',    // Complements "prototype.js" and works with
  'modernizr', // to make older browsers do HTML5/CSS3 things with minimal code from us.
  p,           // Loads the CSS/JS for the page that called this.
];
for (var i = tmp.length - 1; i >= 0; i--) {
  linkFile(d + 'includes/js/' + tmp[i] + '.js');
}
function addLoadListener(fn) {
  if (typeof window.addEventListener != 'undefined') {
    window.addEventListener('load', fn, false);
  } else if (typeof document.addEventListener != 'undefined') {
    document.addEventListener('load', fn, false);
  } else if (typeof window.attachEvent != 'undefined') {
    window.attachEvent('onload', fn);
  } else {
    var oldfn = window.onload;

    if (typeof window.onload != 'function') {
      window.onload = fn;
    } else {
      window.onload = function () {
        oldfn();
        fn();
      };
    }
  }
}
function attachEventListener(target, eventType, functionRef, capture) {
  if (typeof target.addEventListener != 'undefined') {
    target.addEventListener(eventType, functionRef, capture);
  } else if (typeof target.attachEvent != 'undefined') {
    target.attachEvent('on' + eventType, functionRef);
  } else {
    eventType = 'on' + eventType;

    if (typeof target[eventType] == 'function') {
      var oldListener = target[eventType];

      target[eventType] = function () {
        oldListener();

        return functionRef();
      }
    } else {
      target[eventType] = functionRef;
    }
  }
  return true;
}
function getElementsByAttribute(attribute, attributeValue) {
  var elementArray = matchedArray = [];

  elementArray = document.getElementsByTagName('*');

  for (var i = elementArray.length - 1; i >= 0; i--) {
    if (attribute == 'class') {
      var pattern = new RegExp('(^| )' + attributeValue + '( |$)');

      if (pattern.test(elementArray[i].className)) {
        matchedArray[matchedArray.length] = elementArray[i];
      }
    } else if (attribute == 'for') {
      if (elementArray[i].getAttribute('htmlFor')
         || elementArray[i].getAttribute('for')) {
        if (elementArray[i].htmlFor == attributeValue) {
          matchedArray[matchedArray.length] = elementArray[i];
        }
      }
    } else if (elementArray[i].getAttribute(attribute) == attributeValue) {
      matchedArray[matchedArray.length] = elementArray[i];
    }
  }
  return matchedArray;
}
if (typeof document.getElementsBySelector == 'undefined') {
  /* document.getElementsBySelector( selector )
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

  function getAllChildren(e) {
    return e.getElementsByTagName('*');
  }

  document.getElementsBySelector = function (selector) {
    // Attempt to fail gracefully in lesser browsers.
    if (!document.getElementsByTagName) {
      return new Array();
    }
    // Split selector in to tokens.
    var tokens = selector.split(' ');
    var currentContext = [document];

    for (var i = 0, l = tokens.length; i < l; i++) {
      token = tokens[i].replace(/^\s+/, '').replace(/\s+$/, '');

      if (token.indexOf('#') > -1) {
        // Token is an ID selector.
        var bits = token.split('#');
        var tagName = bits[0],
        id = bits[1],
        element = document.getElementById(id);

        if (tagName && element.nodeName.toLowerCase() != tagName) {
          // Tag with that ID not found, return false.
          return new Array();
        }
        // Set currentContext to contain just this element.
        currentContext = [element];
        continue; // Skip to next token.
      }
      if (token.indexOf('.') > -1) {
        // Token contains a class selector.
        var bits = token.split('.');
        var tagName = bits[0],
        className = bits[1];

        if (!tagName) {
          tagName = '*';
        }
        // Get elements matching tag, filter them for class selector.
        var found = [],
        foundCount = 0;

        for (var h = 0, m = currentContext.length; h < m; h++) {
          var elements;
          elements = (tagName == '*'
             ? getAllChildren(currentContext[h])
             : currentContext[h].getElementsByTagName(tagName));

          for (var j = 0, n = elements.length; j < n; j++) {
            found[foundCount++] = elements[j];
          }
        }
        currentContext = [];

        var currentContextIndex = 0;

        for (var k = 0, o = found.length; k < o; k++) {
          if (found[k].className && found[k].className.match(new RegExp('\\b' + className + '\\b')))
            currentContext[currentContextIndex++] = found[k];
        }
        continue; // Skip to next token.
      }
      // Code to deal with attribute selectors.
      if (token.match(/^(\w*)\[(\w+)([=~\|\^\$\*]?)=?"?([^\]"]*)"?\]$/)) {
        /* That revolting regular expression explained.
        /^(\w+)\[(\w+)([=~\|\^\$\*]?)=?"?([^\]"]*)"?\]$/
        \---/  \---/\-------------/    \-------/
        |      |         |               |
        |      |         |           The value
        |      |    ~,|,^,$,* or =
        |   Attribute
        Tag
         */
        var tagName = RegExp.$1,
        attrName = RegExp.$2,
        attrOperator = RegExp.$3,
        attrValue = RegExp.$4;

        if (!tagName) {
          tagName = '*';
        }
        // Grab all of the tagName elements within current context.
        var found = [],
        foundCount = 0;

        for (var h = 0, m = currentContext.length; h < m; h++) {
          var elements;

          elements = (tagName == '*'
             ? getAllChildren(currentContext[h])
             : currentContext[h].getElementsByTagName(tagName));

          for (var j = 0, n = elements.length; j < n; j++) {
            found[foundCount++] = elements[j];
          }
        }
        currentContext = [];

        var currentContextIndex = 0,
        checkFunction; // This function will be used to filter the elements.

        switch (attrOperator) {
        case '=': // Equality
          checkFunction = function (e) {
            return (e.getAttribute(attrName) == attrValue);
          };
          break;
        case '~': // Match one of space seperated words.
          checkFunction = function (e) {
            return (e.getAttribute(attrName).match(new RegExp('\\b' + attrValue + '\\b')));
          };
          break;
        case '|': // Match start with value followed by optional hyphen.
          checkFunction = function (e) {
            return (e.getAttribute(attrName).match(new RegExp('^' + attrValue + '-?')));
          };
          break;
        case '^': // Match starts with value.
          checkFunction = function (e) {
            return (e.getAttribute(attrName).indexOf(attrValue) == 0);
          };
          break;
        case '$': // Match ends with value - fails with "Warning" in Opera 7.
          checkFunction = function (e) {
            return (e.getAttribute(attrName).lastIndexOf(attrValue) == e.getAttribute(attrName).length - attrValue.length);
          };
          break;
        case '*': // Match ends with value.
          checkFunction = function (e) {
            return (e.getAttribute(attrName).indexOf(attrValue) > -1);
          };
          break;
        default: // Just test for existence of attribute.
          checkFunction = function (e) {
            return e.getAttribute(attrName);
          };
        }
        currentContext = [];

        var currentContextIndex = 0;

        for (var k = 0, o = found.length; k < o; k++) {
          if (checkFunction(found[k])) {
            currentContext[currentContextIndex++] = found[k];
          }
        }
        continue; // Skip to next token.
      }
      // If we get here, token is JUST an element (not a class or ID selector).
      tagName = token;

      var found = [],
      foundCount = 0;

      for (var h = 0, m = currentContext.length; h < m; h++) {
        var elements = currentContext[h].getElementsByTagName(tagName);

        for (var j = 0, n = elements.length; j < n; j++) {
          found[foundCount++] = elements[j];
        }
      }
      currentContext = found;
    }
    return currentContext;
  }
}
function linkFile(wher, what, desc, medi) {
  /**
  Link in an external file (CSS or JavaScript) from JavaScript.
  And, load it in the background while the main program continues on.

  params:
  wher = string: Where is the file? (required)
  what = string: 'script' for javascript (default) or 'link' for CSS.
  desc = string: The title of an alternative stylesheet.
  If alternative stylesheets are used,
  the default stylesheet should be titled as 'default'.
  medi = string: What media (screen, print, etc.).
   */

  if (typeof wher == 'undefined') {
    return;
  }
  if (typeof what == 'undefined' || what != 'link') {
    what = 'script';
  }
  if (typeof desc == 'undefined') {
    desc = false;
  }
  if (typeof medi == 'undefined') {
    medi = false;
  }

  var s = document.createElement(what);

  // To make IE < 9 play nice.
  // First, use setAttribute() with '' 2nd param,
  // then use DOM0 to set the real value.
  if (what == 'script') {
    s.setAttribute('src', '');
    s.src = wher;
  } else {
    s.setAttribute('href', '');
    s.setAttribute('rel', '');
    s.href = wher;
    s.rel = (desc && desc != 'default' ? 'alternative ' : '') + 'stylesheet';

    if (medi) {
      s.setAttribute('media', '');
      s.media = medi;
    } else if (desc) {
      s.setAttribute('title', '');
      s.title = desc;
    }
  }
  document.getElementsByTagName('head')[0].appendChild(s);
}
function rbetween(min, max) {
  return min + Math.floor(Math.random() * (max - min + 1));
}
function stopDefaultAction(event) {
  event.returnValue = false;

  if (typeof event.preventDefault != 'undefined') {
    event.preventDefault();
  }
  return true;
}
function targeTo(targ, aId) {
  /**
  Target Links to New Window.
   */

  if (!document.getElementsByTagName) {
    return;
  }
  var a,
  anchors;

  a = (typeof aId === 'undefined'
     ? document : document.getElementById(aId));
  anchors = a.getElementsByTagName('a');

  for (var i = anchors.length - 1; i >= 0; i--) {
    a = anchors[i];
    a.setAttribute('target', '');
    a.target = targ; // If '', turn target off.
  }
}
// Combining functions MakeVisible (x2), MakeInvisible, showFrame and hideFrame.
function toggleVisible(name, v, d, mom) {
  var ele;

  if (typeof mom != 'undefined')
    ele = window.opener.document.getElementById(name).style;
  else
    ele = document.getElementById(name).style;

  if (v == 'undefined') {
    v = (ele.display == 'visible' ? 'hidden' : 'visible');
  }

  ele.visibility = v;
  if (d) {
    ele.display = d;
  }
}
function wc_getCookie(Name) {
  var cookies = document.cookie.split(';');

  for (var i = cookies.length - 1; i >= 0; i--) {
    var crumbs = cookies[i].split('=');

    if (crumbs[0] == Name) {
      return crumbs[1];
    }
  }
  return false;
}
function wc_setCookie(name, value, expire) {
  var e = time() + (expire == null ? -10 : expire);
  document.cookie = name + '=' + escape(value) + '; expires=' + e.toGMTString();
}
