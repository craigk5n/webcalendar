function openHelp () {
 window.open ( 'help_index.php', 'cal_help','dependent,menubar,scrollbars,height=500,width=600,innerHeight=520,outerWidth=620' );
}

function openAbout () {
  var mX = (screen.width / 2) -123, mY = 200;
  var MyPosition = 'left=' + mX + ',top=' + mY + ',screenx=' + mX + ',screeny=' + mY;
  window.open ( 'about.php', 'cal_about','dependent,toolbar=0, height=300,width=245,innerHeight=310,outerWidth=255,location=0,' + MyPosition );
}

function addLoadHandler(handler)
{
    if (window.addEventListener)
    {
        window.addEventListener("load",handler,false);
    }
    else if (window.attachEvent)
    {
        window.attachEvent("onload",handler);
    }
    else if (window.onload)
    {
        var oldHandler = window.onload;
        window.onload = function piggyback()
        {
            oldHandler();
            handler();
        };
    }
    else
    {
        window.onload = handler;
    }
}

/* document.getElementsBySelector(selector)
   - returns an array of element objects from the current document
     matching the CSS selector. Selectors can contain element names,
     class names and ids and can be nested. For example:

       elements = document.getElementsBySelect('div#main p a.external')

     Will return an array of all 'a' elements with 'external' in their
     class attribute that are contained inside 'p' elements that are
     contained inside the 'div' element which has id="main"

   New in version 0.4: Support for CSS2 and CSS3 attribute selectors:
   See http://www.w3.org/TR/css3-selectors/#attribute-selectors

   Version 0.4 - Simon Willison, March 25th 2003
   -- Works in Phoenix 0.5, Mozilla 1.3, Opera 7, Internet Explorer 6, Internet Explorer 5 on Windows
   -- Opera 7 fails
*/

function getAllChildren(e) {
  // Returns all children of element. Workaround required for IE5/Windows. Ugh.
  return e.all ? e.all : e.getElementsByTagName('*');
}

document.getElementsBySelector = function(selector) {
  // Attempt to fail gracefully in lesser browsers
  if (!document.getElementsByTagName) {
    return new Array();
  }
  // Split selector in to tokens
  var tokens = selector.split(' ');
  var currentContext = new Array(document);
  for (var i = 0; i < tokens.length; i++) {
    token = tokens[i].replace(/^\s+/,'').replace(/\s+$/,'');
    if (token.indexOf('#') > -1) {
      // Token is an ID selector
      var bits = token.split('#');
      var tagName = bits[0];
      var id = bits[1];
      var element = document.getElementById(id);
      if (tagName && element.nodeName.toLowerCase() != tagName) {
        // tag with that ID not found, return false
        return new Array();
      }
      // Set currentContext to contain just this element
      currentContext = new Array(element);
      continue; // Skip to next token
    }
    if (token.indexOf('.') > -1) {
      // Token contains a class selector
      var bits = token.split('.');
      var tagName = bits[0];
      var className = bits[1];
      if (!tagName) {
        tagName = '*';
      }
      // Get elements matching tag, filter them for class selector
      var found = new Array;
      var foundCount = 0;
      for (var h = 0; h < currentContext.length; h++) {
        var elements;
        if (tagName == '*') {
            elements = getAllChildren(currentContext[h]);
        } else {
            elements = currentContext[h].getElementsByTagName(tagName);
        }
        for (var j = 0; j < elements.length; j++) {
          found[foundCount++] = elements[j];
        }
      }
      currentContext = new Array;
      var currentContextIndex = 0;
      for (var k = 0; k < found.length; k++) {
        if (found[k].className && found[k].className.match(new RegExp('\\b'+className+'\\b'))) {
          currentContext[currentContextIndex++] = found[k];
        }
      }
      continue; // Skip to next token
    }
    // Code to deal with attribute selectors
    if (token.match(/^(\w*)\[(\w+)([=~\|\^\$\*]?)=?"?([^\]"]*)"?\]$/)) {
      var tagName = RegExp.$1;
      var attrName = RegExp.$2;
      var attrOperator = RegExp.$3;
      var attrValue = RegExp.$4;
      if (!tagName) {
        tagName = '*';
      }
      // Grab all of the tagName elements within current context
      var found = new Array;
      var foundCount = 0;
      for (var h = 0; h < currentContext.length; h++) {
        var elements;
        if (tagName == '*') {
            elements = getAllChildren(currentContext[h]);
        } else {
            elements = currentContext[h].getElementsByTagName(tagName);
        }
        for (var j = 0; j < elements.length; j++) {
          found[foundCount++] = elements[j];
        }
      }
      currentContext = new Array;
      var currentContextIndex = 0;
      var checkFunction; // This function will be used to filter the elements
      switch (attrOperator) {
        case '=': // Equality
          checkFunction = function(e) { return (e.getAttribute(attrName) == attrValue); };
          break;
        case '~': // Match one of space separated words
          checkFunction = function(e) { return (e.getAttribute(attrName).match(new RegExp('\\b'+attrValue+'\\b'))); };
          break;
        case '|': // Match start with value followed by optional hyphen
          checkFunction = function(e) { return (e.getAttribute(attrName).match(new RegExp('^'+attrValue+'-?'))); };
          break;
        case '^': // Match starts with value
          checkFunction = function(e) { return (e.getAttribute(attrName).indexOf(attrValue) == 0); };
          break;
        case '$': // Match ends with value - fails with "Warning" in Opera 7
          checkFunction = function(e) { return (e.getAttribute(attrName).lastIndexOf(attrValue) == e.getAttribute(attrName).length - attrValue.length); };
          break;
        case '*': // Match ends with value
          checkFunction = function(e) { return (e.getAttribute(attrName).indexOf(attrValue) > -1); };
          break;
        default :
          // Just test for existence of attribute
          checkFunction = function(e) { return e.getAttribute(attrName); };
      }
      currentContext = new Array;
      var currentContextIndex = 0;
      for (var k = 0; k < found.length; k++) {
        if (checkFunction(found[k])) {
          currentContext[currentContextIndex++] = found[k];
        }
      }
      // alert('Attribute Selector: '+tagName+' '+attrName+' '+attrOperator+' '+attrValue);
      continue; // Skip to next token
    }
    // If we get here, token is JUST an element (not a class or ID selector)
    tagName = token;
    var found = new Array;
    var foundCount = 0;
    for (var h = 0; h < currentContext.length; h++) {
      var elements = currentContext[h].getElementsByTagName(tagName);
      for (var j = 0; j < elements.length; j++) {
        found[foundCount++] = elements[j];
      }
    }
    currentContext = found;
  }
  return currentContext;
}

/* That revolting regular expression explained
/^(\w+)\[(\w+)([=~\|\^\$\*]?)=?"?([^\]"]*)"?\]$/
  \---/  \---/\-------------/    \-------/
    |      |         |               |
    |      |         |           The value
    |      |    ~,|,^,$,* or =
    |   Attribute
   Tag
*/

function sortTasks( order, cat_id, ele ) {
  ele.style.cursor = 'wait';
  document.body.style.cursor = 'wait';
  var cat = '';
  if ( cat_id > -99 )
    cat = '&cat_id=' + cat_id;
  var url = 'ajax.php';
  var params = 'page=minitask&name=' + order + cat;
  var ajax = new Ajax.Request(url,
    {method: 'post',
    parameters: params,
    onComplete: showResponse});
}

function showResponse(originalRequest) {
  miniTask = document.getElementById('minitask');
  if (originalRequest.responseText) {
    text = originalRequest.responseText;
    miniTask.innerHTML = text;
  }
  document.body.style.cursor = 'default';
}

function altrows() {  
   if(!document.getElementsByTagName) return false;  
   var rows = $$('div tbody tr');      
   for (var i=0; i<rows .length; i++) { 
     if ( ! rows[i].hasClassName('ignore') ) {
       rows[i].onmouseover = function() { $(this).addClassName('alt');}  
       rows[i].onmouseout = function() { $(this).removeClassName('alt');} 
    }
   }  
} 

function altps() {  
   if(!document.getElementsByTagName) return false;  
   var rows = $$('div p');      
   for (var i=0; i<rows .length; i++) { 
     if ( ! rows[i].hasClassName('ignore') ) {
       rows[i].onmouseover = function() { $(this).addClassName('alt');}  
       rows[i].onmouseout = function() { $(this).removeClassName('alt');} 
    }
   }  
} 
function showFrame(foo,f,section) {
  document.getElementById(foo).style.display = "block";
  if (f) { setCookie(foo, "o", section); }
}

function hideFrame(foo,f,section) {
  if (document.getElementById(foo)) {
    document.getElementById(foo).style.display = "none";
    if (f) { deleteCookie(foo, section); }
  }
}