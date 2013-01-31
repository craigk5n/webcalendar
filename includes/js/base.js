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

for (var i in Array(
    'dateformat',
    'geturl',
    // 'http://code.jquery.com/jquery-1.7.min.js'
    // 'http://cdnjs.cloudflare.com/ajax/libs/modernizr/2.0.6/modernizr.min.js'
    p, // Loads the CSS/JS for the page that called this.
  )) {
  linkFile(d + 'includes/js/' + i + '.js');
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
// Remove whitespace, comments and other "junk" nodes from the DOM.
// http://jspro.com/raw-javascript/removing-useless-nodes-from-the-dom/#.UL2IF65sFTg
function clean(node) {
  for (var n = 0; node.childNodes[n]; n++) {
    var child = node.childNodes[n];
    if (child.nodeType === 8 || (child.nodeType === 3 && !/\S/.test(child.nodeValue))) {
      node.removeChild(child);
      n--;
    } else if (child.nodeType === 1) {
      clean(child);
    }
  }
}
// Javascript version of PHP's "empty()".
// Kinds of data considered to be empty:
//  - undefined or null
//  - a zero-length string
//  - an array with no members
//  - an object with no enumerable properties
// Booleans and numbers are NEVER empty, irrespective of their value.
// http://jspro.com/raw-javascript/testing-for-empty-values/#.UKx_6a5sFTg
function empty(data) {
  if (typeof(data) == 'number' || typeof(data) == 'boolean') {
    return false;
  }
  if (typeof(data) == 'undefined' || data === null) {
    return true;
  }
  if (typeof(data.length) != 'undefined') {
    return data.length == 0;
  }
  var count = 0;
  for (var i in data) {
    if (data.hasOwnProperty(i)) {
      count++;
    }
  }
  return count == 0;
}
function getElementsByAttribute(attribute, attributeValue) {
  var elementArray = matchedArray = [];
  
  elementArray = document.getElementsByTagName('*');
  
  for (var i in elementArray) {
    if (attribute == 'class') {
      var pattern = new RegExp('(^| )' + attributeValue + '( |$)');
      
      if (pattern.test(i.className)) {
        matchedArray[matchedArray.length] = i;
      }
    } else if (attribute == 'for') {
      if (i.getAttribute('htmlFor') || i.getAttribute('for')) {
        if (i.htmlFor == attributeValue) {
          matchedArray[matchedArray.length] = i;
        }
      }
    } else if (i.getAttribute(attribute) == attributeValue) {
      matchedArray[matchedArray.length] = i;
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
    
    for (var i in tokens) {
      token = i.replace(/^\s+/, '').replace(/\s+$/, '');
      
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
        
        for (var h in currentContext) {
          var elements;
          elements = (tagName == '*'
             ? getAllChildren(h) : h.getElementsByTagName(tagName));
          
          for (var j in elements) {
            found[foundCount++] = j;
          }
        }
        currentContext = [];
        
        var currentContextIndex = 0;
        
        for (var k in found) {
          if (k.className && k.className.match(new RegExp('\\b' + className + '\\b')))
            currentContext[currentContextIndex++] = k;
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
        
        for (var h in currentContext) {
          var elements;
          
          elements = (tagName == '*'
             ? getAllChildren(h) : h.getElementsByTagName(tagName));
          
          for (var j in elements) {
            found[foundCount++] = j;
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
        
        for (var k in found) {
          if (checkFunction(k)) {
            currentContext[currentContextIndex++] = k;
          }
        }
        continue; // Skip to next token.
      }
      // If we get here, token is JUST an element (not a class or ID selector).
      tagName = token;
      
      var found = [],
      foundCount = 0;
      
      for (var h in currentContext) {
        var elements = h.getElementsByTagName(tagName);
        
        for (var j in elements) {
          found[foundCount++] = j;
        }
      }
      currentContext = found;
    }
    return currentContext;
  }
}
// Compare 2 integers.
function intcmp(int1, int2) {
  return (int1 == int2 ? 0 : (int1 < int2 ? -1 : 1))
}
/**
Link in an external file (CSS or JavaScript) from JavaScript.
And, load it in the background while the main program continues on.
 */
function linkFile(
  wher, // string: Where is the file? (required)
  what, // string: 'script' for javascript (default) or 'link' for CSS.
  desc, // string: The title of an alternative stylesheet.
  // If alternative stylesheets are used, the default stylesheet should be titled 'default'.
  medi // string: What media (screen, print, etc.).
) {
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
  
  // To make IE < 9 play nice...
  // First, use setAttribute() with '' 2nd param,
  // then use DOM0 to set the real value.
  if (what == 'script') {
    s.setAttribute('src', '');
    s.src = wher;
    document.getElementsByTagName('body')[0].appendChild(s);
  } else {
    s.setAttribute('href', '');
    s.setAttribute('rel', '');
    s.href = wher;
    s.rel = (desc && desc != 'default' ? 'alternative' : '') + ' stylesheet';
    
    if (medi) {
      s.setAttribute('media', '');
      s.media = medi;
    } else if (desc) {
      s.setAttribute('title', '');
      s.title = desc;
    }
    document.getElementsByTagName('head')[0].appendChild(s);
  }
}
// Has page finished loading?
// If yes, run function.
function loaded(i, f) {
  if (jq('#' + i) != null) {
    f();
  } else if (!pageLoaded) {
    setTimeout('loaded(\'' + i + '\',' + f + ')', 100);
  }
}
// A Utility Function for Padding Strings and Numbers
// http://jspro.com/raw-javascript/a-utility-function-fo-padding-strings-and-numbers/#.UKxyTa5sFTg
function pad(input, length, padding) {
  while ((input = input.toString()).length + (padding = padding.toString()).length < length) {
    padding += padding;
  }
  return padding.substr(0, length - input.length) + input;
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
// Emulate the C strcmp function
// -1 if string1 comes first, 1 if string2 comes first, 0 if equal
function strcmp(string1, string2) {
  // Handle null values first
  if (string1 == null && string2 == null) {
    return 0;
  }
  if (string1 == null) {
    return -1;
  } else if (string2 == null) {
    return 1;
  }
  // Compare non-null values
  var str1 = string1.toLowerCase();
  var str2 = string2.toLowerCase();
  
  if (str1 == str2) {
    return 0;
  }
  for (var i = 0; str1[i] && str2[i]; i++) {
    if (str1.charAt(i) < str2.charAt(i)) {
      return -1;
    } else if (str1.charAt(i) > str2.charAt(i)) {
      return 1;
    }
  }
  if (str1.length < str2.length) {
    return -1;
  } else if (str1.length > str2.length) {
    return 1;
  }
  // Shouldn't ever reach here...
  alert('strcmp bug! string1= "' + str1 + '", string2= "' + str2 + '"');
}
/**
 * Target Links to New Window.
 */
function targeTo(
  targ, // string  target=
  aId // string  anchor ID (optional - default to all anchors)
) {
  if (!document.getElementsByTagName) {
    return;
  }
  var a,
  anchors;
  
  a = (typeof aId === 'undefined' ? document : document.getElementById(aId));
  anchors = a.getElementsByTagName('a');
  
  for (var a in anchors) {
    a.setAttribute('target', '');
    a.target = targ; // If'', turn target off.
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
/*
Javascript < 1.8.1 and IE < 9 have no "trim()" functions,
and javascript 1.8.1+ trimLeft(), trinRight() and trim() only handles whitespace.
So, I "borrowed" these from
http://jspro.com/raw-javascript/trimming-strings-in-javascript/#.UKxuUq5sFTg
 */
// Remove charlist from beginning of String.
// Default is whitespace.
String.prototype.Ltrim = function (charlist) {
  if (charlist === undefined) {
    charlist = '\\s';
  }
  return this.replace(new RegExp('^[' + charlist + '] + '), '');
};
// Remove charlist from end of String.
// Default is whitespace.
String.prototype.Rtrim = function (charlist) {
  if (charlist === undefined) {
    charlist = '\\s';
  }
  return this.replace(new RegExp('[' + charlist + '] + $'), '');
};
String.prototype.Btrim = function (charlist) {
  return this.Ltrim(charlist).Rtrim(charlist);
};
// end trim()s
function wc_getCookie(Name) {
  var cookies = document.cookie.split(';');
  
  for (var i in cookies) {
    var crumbs = i.split('=');
    
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
