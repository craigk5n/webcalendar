//This is used by JSCookMenu
var cm =
{
    // main menu display attributes
    //
    // Note.  When the menu bar is horizontal,
    // mainFolderLeft and mainFolderRight are
    // put in <span></span>.  When the menu
    // bar is vertical, they would be put in
    // a separate TD cell.

    // HTML code to the left of the folder item
    mainFolderLeft: '&nbsp;',
    // HTML code to the right of the folder item
    mainFolderRight: '&nbsp;',
    // HTML code to the left of the regular item
    mainItemLeft: '&nbsp;',
    // HTML code to the right of the regular item
    mainItemRight: '&nbsp;',

    // sub menu display attributes

    // 0, HTML code to the left of the folder item
    folderLeft: '<img alt="" src="images/icons/spacer.gif">',
    // 1, HTML code to the right of the folder item
    folderRight: '<img alt="" src="images/icons/arrow.gif">',
    // 2, HTML code to the left of the regular item
    itemLeft: '<img alt="" src="images/icons/spacer.gif">',
    // 3, HTML code to the right of the regular item
    itemRight: '<img alt="" src="images/icons/blank.gif">',
    // 4, cell spacing for main menu
    mainSpacing: 0,
    // 5, cell spacing for sub menus
    subSpacing: 0,
    // 6, auto dispear time for submenus in milli-seconds
    delay: 500
};

// for horizontal menu split
if (typeof _cmNoAction != "undefined") {
  var cmHSplit = [_cmNoAction, '<td class="MenuItemLeft"></td><td colspan="2"><div class="MenuSplit"></div></td>'];
  var cmMainHSplit = [_cmNoAction, '<td class="MainItemLeft"></td><td colspan="2"><div class="MenuSplit"></div></td>'];
  var cmMainVSplit = [_cmNoAction, '&nbsp;'];
}

function openHelp ( page ) {
 var page_url = 'help_index.php';
 if ( page )
   page_url = page + '?thispage=' + page;
 window.open ( page_url, 'cal_help','dependent,menubar,scrollbars,height=500,width=600,innerHeight=520,outerWidth=620' );
}
     
function openAbout () {
  var mX = (screen.width / 2) -123, mY = 200;
  var MyPosition = 'left=' + mX + ',top=' + mY + ',screenx=' + mX + ',screeny=' + mY;
  window.open ( 'about.php', 'cal_about','dependent,toolbar=0, height=300,width=245,innerHeight=310,outerWidth=255,location=0,' + MyPosition );
}

function setTab( tab ) {
  document.forms['prefform'].currenttab.value = tab;
  showTab(tab);
  return false;
}

function editCats (  evt, form ) {
  if (document.getElementById) {
    mX = evt.clientX   -160;
    mY = evt.clientY  + 150;
  }
  else {
    mX = evt.pageX  -160;
    mY = evt.pageY + 150;
  }
  var MyPosition = 'scrollbars=no,toolbar=no,left=' + mX + ',top=' + mY + ',screenx=' + mX + ',screeny=' + mY;
  var cat_ids = document.getElementById('cat_id').value;
  url = "catsel.php?form=" + form;
	if ( cat_ids )
	url += "&cats=" + cat_ids; 
  if ( typeof user != "undefined" && user ) {
    url += "&user=" + user;
 }
  var catWindow = window.open(url,"EditCat","width=440,height=250,"  + MyPosition);
}

function selectDate ( day, month, year, current, evt, frm ) {
  // get currently selected day/month/year
  monthobj = eval( 'document.' + frm.id + '.' + month);
  curmonth = monthobj.options[monthobj.selectedIndex].value;
  yearobj = eval( 'document.' + frm.id + '.' + year );
  curyear = yearobj.options[yearobj.selectedIndex].value;
  date = curyear;
  evt = evt? evt: window.event;
  var scrollingPosition = getScrollingPosition();

  if (typeof evt.pageX != "undefined" &&
     typeof evt.x != "undefined")
 {
   mX = evt.pageX + 40;
   mY = self.screen.availHeight - evt.pageY;
 }
 else
 {
   mX = evt.clientX + scrollingPosition[0] + 40;
   mY = evt.clientY + scrollingPosition[1];
 }
//alert ( mX + ' ' + mY );
  var MyPosition = 'scrollbars=no,toolbar=no,screenx=' + mX + ',screeny=' + mY + ',left=' + mX + ',top=' + mY ;
  if ( curmonth < 10 )
    date += "0";
  date += curmonth;
  date += "01";
  url = "datesel.php?form=" + form.id + "&fday=" + day +
    "&fmonth=" + month + "&fyear=" + year + "&date=" + date;
  var colorWindow = window.open(url,"DateSelection","width=300,height=180,"  + MyPosition);
}

function selectColor ( color, evt ) {
  url = "colors.php?color=" + color;
  if (document.getElementById) {
    mX = evt.clientX   + 40;
  }
  else {
    mX = evt.pageX + 40;
  }
  var mY = 100;
  var MyOptions = 'width=390,height=380,scrollbars=0,left=' + mX + ',top=' + mY + ',screenx=' + mX + ',screeny=' + mY;
  var colorWindow = window.open(url,"ColorSelection","width=390,height=365," + MyOptions );
}

function valid_color ( str ) {
 var validColor = /^#[0-9a-fA-F]{3}$|^#[0-9a-fA-F]{6}$/;

 return validColor.test ( str );
}

// Updates the background-color of a table cell
// Parameters:
//    input - element containing the new color value
//    target - id of sample
function updateColor ( input, target ) {
 // The cell to be updated
 var colorCell = document.getElementById(target);
 // The new color
 var color = input.value;

 if (!valid_color ( color ) ) {
   // Color specified is invalid; use black instead
  colorCell.style.backgroundColor = "#000000";
  input.select();
  input.focus(); 
  alert ( invalidColor );
 } else {
  colorCell.style.backgroundColor = color;
 }
}

        
function addLoadHandler(handler)
{
    if(window.addEventListener)
    {
        window.addEventListener("load",handler,false);
    }
    else if(window.attachEvent)
    {
        window.attachEvent("onload",handler);
    }
    else if(window.onload)
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

function isNumeric(sText)
{
   //allow blank values. these will become 0
   if ( sText.length == 0 ) 
     return sText;
   var validChars = "0123456789";
   var Char;
   for (i = 0; i < sText.length && sText != 99; i++) 
   { 
      Char = sText.charAt(i); 
      if (validChars.indexOf(Char) == -1) 
      {
        sText = 99;
      }
   }
   return sText;
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
        case '~': // Match one of space seperated words 
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

//----------------------------------------------------------------------------
//
// DomContextMenu v0.6, 2007-01-19
//
//----------------------------------------------------------------------------
//
// Copyright (c) 2007 Michael Egger, me@anyma.ch
// http://www.anyma.ch/
// 
// Permission is hereby granted, free of charge, to any person obtaining
// a copy of this software and associated documentation files (the
// "Software"), to deal in the Software without restriction, including
// without limitation the rights to use, copy, modify, merge, publish,
// distribute, sublicense, and/or sell copies of the Software, and to
// permit persons to whom the Software is furnished to do so, subject to
// the following conditions:
// 
// The above copyright notice and this permission notice shall be
// included in all copies or substantial portions of the Software.
//
// THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
// EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF
// MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
// NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE
// LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION
// OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION
// WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
//
//----------------------------------------------------------------------------

// FUNCTION REFERENCE
//
//    DomContextMenu.create()  -  call anytime to initialise the Context Menu
//                                This doesn't parse anything, it just prepares a <div> for the menu
//                                And it adds Listeners to keyboard and mouse events


//  DomContextMenu.attach( node , {name , call , [ref] })
//
//                                Adds a contextual menu item to any Dom-element


//  DomContextMenu.remove( node , ref )
//
//                                Removes contextual menu item froms Dom-element


var DomContextMenu = {
 
    create : function() {
        // create our context menu div
        
        DomContextMenu.Menu = document.createElement('div');
        DomContextMenu.Menu.id = 'DomContextMenu_div';
        DomContextMenu.MenuContent = document.createElement('div');
        DomContextMenu.MenuContent.id = 'DomContextMenuContent_div';

        document.body.appendChild(DomContextMenu.Menu);
        DomContextMenu.Menu.appendChild(DomContextMenu.MenuContent);

        // Add Prototype Sugar
        Element.extend(DomContextMenu.Menu);
        
        DomContextMenu.hide();
        
        // Add Event Listeners
        Event.observe(DomContextMenu.Menu, 'mouseup', function(event){
                DomContextMenu.hide();
            });
            
        Event.observe(document, 'mousedown', function(event){
          var target = Event.element(event);
          if (( target != DomContextMenu.MenuContent) )
					  DomContextMenu.hide();
          });
                    
        Event.observe(document, 'keypress', function(e){
                if (e.keyCode == Event.KEY_ESC) DomContextMenu.hide();
            });

    },
    
    show : function() {
        DomContextMenu.Menu.show();
    },
    hide : function() {
        DomContextMenu.Menu.hide();
    },
    
    prepare :function (event) {                                
                                          // set position
        DomContextMenu.Menu.setStyle({
            left:Event.pointerX(event) +'px',
            top:Event.pointerY(event) +'px'
        });
        
        DomContextMenu.MenuContent.innerHTML = ''; // Clear Menu
        var cm;
        var target = Event.element(event); // walk the DOM up from the target
        var targets = [];
        while (target) {
            targets.push(target);  // keep an array of the target objects for backreferencing
            if (cm = target.contextmenu) { // add items to menu on the way
                    var ul = document.createElement('ul');
                    $H(cm).values().each(function(item){
                            var d = true;                    
                            if (item.className) {
                                d = targets.find(function(s){return s.className.split(' ').include(item.className)});
                            }
                            if (item.tagName) {
                                d = targets.find(function(s){return (s.tagName.toLowerCase() == item.tagName.toLowerCase())});
                            }
                            if (d) {
                                var li = document.createElement('li');
                                li.innerHTML = item.name;
                                li.onmouseup = item.call.bind(target,$(d));
                                ul.appendChild(li);
                            }
                        });
                    DomContextMenu.MenuContent.appendChild(ul);
                }
            target = target.parentNode;
        }
    },
    
    attach : function (node,options){
        node = $(node);    //  node can be an object or its id...
        if (!node) return false;
        
        if (!options.name) return false;
        // build reference from item text if not given
        if (options.ref == null) options.ref = options.name.replace(/\W/g,'');
        
        // create empty object if there's nothing
        if (node.contextmenu == null) node.contextmenu = {};
        
        // store new info (or update if ref exists already
        if (node.contextmenu[options.ref] = { name:options.name , call:options.call }) {        
            // add more options
            if (options.className) node.contextmenu[options.ref].className = options.className;
            if (options.tagName) node.contextmenu[options.ref].tagName = options.tagName;
            return true;

        }
        return false;
    },
    

    remove    : function (node,ref){
        node = $(node);    //  node can be an object or its id...
        if (!node) return 'Node does not exist';
        if (node.contextmenu){
            if (node.contextmenu[ref]) delete node.contextmenu[ref];
            else return 'Ref dos not exist';
        } else return 'Node has no context menu';
        return true;
    }
}
//end DomContextMenu

function contextKiller (id) {
  Event.observe(document.getElementById(id),
  'contextmenu', function(event){DomContextMenu.prepare(event);
  DomContextMenu.show();
  Event.stop(event);});
}

function contextNew (id, type, text) {
	var evid = 'ev' + id;
	if ( type == 'view' ) {
		title = '<img src="images/icons/view_detailed.png">&nbsp;' + text;
		func = 'location.replace("view_entry.php?eid=' + id + '")';
	}else 	if ( type == 'edit' ) {
		title = '<img src="images/icons/edit2.png">&nbsp;' + text;		
		func = 'location.replace("edit_entry.php?eid=' + id + '")';		
	}else 	if ( type == 'approve' ) {
		title =  '<img src="images/check.gif">&nbsp;' + text;		
    func = 'contextUpdate(' + id + ',"approve")';
	}else 	if ( type == 'delete' ) {
		title = '<img src="images/delete.png">&nbsp;' + text;	
    func = 'contextUpdate(' + id + ',"delete")';		
	}	
  DomContextMenu.attach(evid,{name: title,
  call:function(){
      eval (func )
      } 
  });
}

function contextUpdate (id, type) {
  var url = 'ajax_update.php';
  var params = 'page=update&id=' + id + '&type=' + type;
  var ajax = new Ajax.Request(url,
    {method: 'post', 
    parameters: params, 
    onComplete: contextRewrite});
}

function contextRewrite(originalRequest) {
  miniTask = document.getElementById('minitask');  
  if (originalRequest.responseText) {
    text = originalRequest.responseText;
    miniTask.innerHTML = text;
  }
  document.body.style.cursor = 'default';
}



function sortTasks(order, cat_id, ele) {
  if ( ele ) 
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
function initPhpVars(filename) {
  var url = 'ajax.php';
  var params = 'page=initPHP&filename=' + filename;
  var ajax = new Ajax.Request(url,
    {method: 'post', 
    parameters: params, 
    onComplete: setPhpVars});
}
function setPhpVars(originalRequest) {
  if (originalRequest.responseText) {
    text = originalRequest.responseText;
    eval ( text );
  }
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

function lookupName( list ){
	var partid = document.getElementById(list);
	var selectid = 0;
	var lookupid = document.getElementById('hint');
  
  var x =  stringLength(lookupid.value);
  for ( i = 0; i < partid.length; i++ ) {
  	str = partid.options[i].text;
    if ( stringToLowercase(str.substring(0,x)) == 
		  stringToLowercase(lookupid.value )){
      selectid = i;
	  i = partid.length;
	 }
  }

	partid.selectedIndex = selectid;
	partid.options[selectid].selected = true;
}


function selAdd(sourceid, targetid){
  var source = document.getElementById(sourceid);
  var target = document.getElementById(targetid);	 
  var isUnique = true;

  with (source)
    {
       for (i = 0; i < length; i++) {
         if(options[i].selected) {
           with (options[i]) {
             for ( j=0; j < target.length;j++ ) {
               if (target.options[j].value == value )
                 isUnique = false;
             }
             if (isUnique)
               target.options[target.length]  = new Option( text, value );    
             options[i].selected = false;
           } //end with options
         }
       } // end for loop
    } // end with source
}

function selRemove(targetid){
  var target = document.getElementById(targetid);
  with (target)
    {
      for (i = 0; i < length; i++)
         {
           if(options[i].selected){
             options[i] = null;
           } 
      } // end for loop
    }
}

function stringLength(inputString)
{
  return inputString.length;
}
function stringToLowercase(inputString)
{
  return inputString.toLowerCase();
}


