/* $Id$  */ 

initPhpVars( 'calendar' );
var myarray = '';
function onLoad () {
	  add_dblclick();
  //make sure AJAX is finished first
   if ( typeof( window[ 'DISPLAY_TASKS' ] ) == "undefined") {
     setTimeout ( "onLoad()", 10 );
   return false;
   }
  if  ( DISPLAY_TASKS ) 
    sortTasks( 0, cat_id );  
}

function initEntries( user, caltype, date, cat_id ) {
  var url = 'ajax_entries.php';
	var catId = '';
  if ( cat_id )
    catId = '&cat_id=' + cat_id;
  var params = 'page=initEntries&user=' + user + '&caltype=' + caltype + '&date=' + date + catId;
  var ajax = new Ajax.Request(url,
    {method: 'post', 
    parameters: params, 
    onComplete: setEntries});
}
    
function setEntries(originalRequest ) {
  waitForDom();
  if (originalRequest.responseText) {
		var dvinfo = originalRequest.responseText.evalJSON();
    //dvinfo = eval( '(' + originalRequest.responseText + ')' );
    //Create event entries
		var dvinner;
    var eDiv;
		var evAr = new Array();
		if (dvinfo.tip)
		dvinfo.tip.each(function(tips) {
			tip = '';
		 //Parse Popups
			//Note: the text below has already been translated via ajax.php
			if ( tips.puser )     
				tip += '<dt>User:</dt><dd>' + tips.puser + '</dd>';
			tip += '<dl><dt>Time:</dt><dd>' + tips.ptime + '</dd>';
			if ( tips.ploc )     
				tip += '<dt>Location:</dt><dd>' + tips.ploc + '</dd>';
			if ( tips.psum )     
				tip += '<dt>Summary:</dt><dd>' + tips.psum + '</dd>';
			if ( tips.ppart )     
				tip += '<dt>Participants:</dt><dd>' + tips.ppart + '</dd>';
			if ( tips.pdesc ) 
				tip += '<dt>Description:</dt><dd>' + tips.pdesc + '</dd>';
			if ( tips.prem )
				tip += '<dt>Reminder:</dt><dd>' + tips.prem + ' </dd>';
			if ( tips.pse )
				tip += '<dt>SiteExtras:</dt><dd>' + tips.pse + ' </dd>';
			tip += '</dl>';
			evAr[tips.tid] = tip;
		});
		//alert ( dvinfo.tip[0].ptime );
    dvinfo.caldata.each(function(dvdata) {
      dvinner = '';
      dvid = 'dv' + dvdata.dv;
			//alert ( dvdata.ev);
			if ( dvdata.ev[0].eid == 'blank' ) {
        $(dvid).update();	
			}else{
        dvdata.ev.each(function(evt) {
					eid = evt.eid.split('-')[0];
					date = evt.date ? evt.date : dvdata.dv;
					time = evt.time ? evt.time : '';
					//alert ( eid);
					view = (evt.type ? viewTaskStr : viewEventStr);
					img = (evt.type ? 'task' : 'event');
					dvinner += '<div id="ev'+ evt.eid +'"><a  title="' + view 
						+ '" class="' + evt.cl + '" href="view_entry.php?eid=' 
						+ eid + '&amp;date=' + date + '&amp;user=' + evt.user
						+ '"><img src="images/' + img + '.gif" class="bullet" alt="' 
						+ view + '" width="5" height="7" />' + time 
						+ evt.sum + '</a></div>';
			   });
				$(dvid).update(dvinner);
				//Loop through again now that all DIVs are created
        dvdata.ev.each(function(evt) {
					eid = evt.eid.split('-')[0];
					//Create Context Menus  
					contextKiller (evt.eid);                   
					contextNew (evt.eid, evt.cm);
					//Create Popups
					if ( evAr[eid] ) 
						new Tip( $('ev' + evt.eid), evAr[eid]);
			   });
			}
    });
  }
}

function waitForDom () {
	if ( ! domInitialized )  {
    var t = setTimeout ( "waitForDom()", 10 ); 
 }
  return false;
}

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
        //return if already exists
        if ( $('DomContextMenu_div') )
          return  true;
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
        if (( target != DomContextMenu.MenuContent)
            && ( target.up('div') != DomContextMenu.MenuContent ))  DomContextMenu.hide();
      });
      
    Event.observe(document, 'contextmenu', function(event){
        DomContextMenu.prepare(event);
        DomContextMenu.show();
        Event.stop(event);   // prevent Browsers own context menu from showing up
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
  Event.observe($('ev'+id),
  'contextmenu', function(event){DomContextMenu.prepare(event);
  DomContextMenu.show();
  Event.stop(event);});
  if  ( ! DomContextMenu.Menu )
    DomContextMenu.create();
}

function contextNew (id, type) {
  var evid = 'ev' + id;
  var eid = id.split('-')[0];
  if ( type.include('V') ) {
    title = '<img src="images/icons/view_detailed.png"/>&nbsp;' + viewEventStr;
    DomContextMenu.attach(evid,{name: title,
  call:function(){
    window.location.href = 'view_entry.php?eid=' + eid;
      } 
  }); 
  }
  if ( type.include('W') ) {
    title = '<img src="images/icons/todo.png"/>&nbsp;' + viewTaskStr;
    DomContextMenu.attach(evid,{name: title,
  call:function(){
    window.location.href = 'view_entry.php?eid=' + eid;
      } 
  }); 
  }
  if ( type.include('A') ) {
    title =  '<img src="images/check.gif" />&nbsp;' + approveStr;  
    DomContextMenu.attach(evid,{name: title,
  call:function(){
    contextUpdate( id, 'A');
      } 
  });  
  }
  if ( type.include('R') ) {
    title =  '<img src="images/rejected.gif" />&nbsp;' + rejectStr;  
    DomContextMenu.attach(evid,{name: title,
  call:function(){
    contextUpdate( id, 'R');
      } 
  });  
  }
  if ( type.include('E') ) {
    title = '<img src="images/icons/edit2.png" />&nbsp;' + editEventStr; 
    DomContextMenu.attach(evid,{name: title,
  call:function(){
    window.location.href = 'edit_entry.php?eid=' + eid;
      } 
  });
  }
  if ( type.include('T') ) {
    title = '<img src="images/icons/edit2.png" />&nbsp;' + editTaskStr; 
    DomContextMenu.attach(evid,{name: title,
  call:function(){
    window.location.href = 'edit_entry.php?eid=' + eid;
      } 
  });
  }
  if ( type.include('D') ) {
    title = '<img src="images/delete.png" />&nbsp;' + deleteStr;  
    DomContextMenu.attach(evid,{name: title,
  call:function(){
    contextUpdate( id, 'D');
      } 
  });    
  }
  if ( isNumeric(type) ) {
    title = '<img src="images/icons/view_detailed.png"/>&nbsp;' + viewEventStr;
    DomContextMenu.attach(evid,{name: title,
  call:function(){
    window.location.href = 'view_entry.php?eid=' + eid;
      }
  });
    title = '<img src="images/icons/edit2.png" />&nbsp;' + editAllDates; 
    DomContextMenu.attach(evid,{name: title,
  call:function(){
    window.location.href = 'edit_entry.php?eid=' + eid;
      } 
  });
    title = '<img src="images/icons/edit2.png" />&nbsp;' + editThisDate; 
    DomContextMenu.attach(evid,{name: title,
  call:function(){
    window.location.href = 'edit_entry.php?eid=' + eid 
      + '&date=' + type + '&override=1';
      } 
  });
    title = '<img src="images/delete.png" />&nbsp;' + deleteAllDates; 
    DomContextMenu.attach(evid,{name: title,
  call:function(){
    contextUpdate( id, 'DA');
      }  
  });
    title = '<img src="images/delete.png" />&nbsp;' + deleteOnly; 
    DomContextMenu.attach(evid,{name: title,
  call:function(){
    contextUpdate( id, 'DO');
      } 
  });
  }
}

function contextUpdate (id, type) {
  var myId = document.getElementById('ev' + id);
  var parId = myId.parentNode.id.substring(2);
  var eid = id.split('-')[0];
  var url = 'ajax_entries.php';
  var params = 'page=update&eid=' + eid + '&type=' + type + '&date=' + parId;
  var ajax = new Ajax.Request(url,
    {method: 'post', 
    parameters: params, 
    onComplete: setEntries});
}

function sortTasks(order, cat_id, ele) {
  if ( ele ) 
    ele.style.cursor = 'wait';
  document.body.style.cursor = 'wait';
  var cat = '';
  if ( cat_id )
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




