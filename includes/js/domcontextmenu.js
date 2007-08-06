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
//	DomContextMenu.create()  -  call anytime to initialise the Context Menu
//								This doesn't parse anything, it just prepares a <div> for the menu
//								And it adds Listeners to keyboard and mouse events


//  DomContextMenu.attach( node , {name , call , [ref] })
//
//								Adds a contextual menu item to any Dom-element


//  DomContextMenu.remove( node , ref )
//
//								Removes contextual menu item froms Dom-element


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
				if (( target != DomContextMenu.MenuContent)
						&& ( target.up('div') != DomContextMenu.MenuContent ))	DomContextMenu.hide();
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
		
		DomContextMenu.MenuContent.innerHTML = '';			// Clear Menu
		var cm;
		var target = Event.element(event);					// walk the DOM up from the target
		var targets = [];
		while (target) {
			targets.push(target);							// keep an array of the target objects for backreferencing
			if (cm = target.contextmenu) {					// add items to menu on the way
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
		node = $(node);	//  node can be an object or its id...
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
		return false
	},
	

	remove	: function (node,ref){
		node = $(node);	//  node can be an object or its id...
		if (!node) return 'Node does not exist';
		if (node.contextmenu){
			if (node.contextmenu[ref]) delete node.contextmenu[ref];
			else return 'Ref dos not exist';
		} else return 'Node has no context menu';
		return true;
	}
}


			