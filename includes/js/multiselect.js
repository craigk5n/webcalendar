/**

MultiSelect v1.0
(c) Arc90, Inc.

http://www.arc90.com
http://lab.arc90.com

Licensed under : Creative Commons Attribution 2.5 http://creativecommons.org/licenses/by/2.5/

USAGE:
set JS vars in script with your choices:
a$.NO_SELECTION	= 'No selection';
a$.SELECTED		= 'Options selected';
a$.SelectAllMin	= 6;
a$.WhenToUse	= 'class';

for SELECT apply class="multiselect" if a$.WhenToUse == 'class' otherwise if if a$.WhenToUse == 'multiple' any select with multiple set will become a multiselect

*/

var a$ = {}; // arc90 namespace functions
a$.c = 0;
a$.openSelect = null;

a$.NO_SELECTION	= 'No selection';
a$.SELECTED		= 'Options selected';
a$.SELECT_ALL	= 'Select All';
a$.SelectAllMin	= 6;
a$.WhenToUse	= 'class'; // class: based on class multiselect existing | multiple: based on multiple attributte exists | all: both single and multiple
a$.msSeparator	= '|';

a$.appName = navigator.appVersion.toLowerCase();
a$.isIE = document.all && a$.appName.indexOf('msie') >= 0;
a$.isSafari = a$.appName.indexOf('safari') >= 0;
a$.msBodyTimer = null;

a$.multiSelectCreate = function(o) {
// can be called directly with the id or object passed in as the first argument
//  or if a$.WhenToUse is set to class or multiple
	var S = null;

	if (o != null)
		S = [a$.isString(o)? a$.e(o): o];
	else
		S = document.getElementsByTagName('select');

	for (var i = 0, l = 1; i < l; i++) { //S.length
		var s = S[i];
		if (s != null && ((a$.WhenToUse == 'class' &&  s.className.indexOf('multiselect') >= 0) || (a$.WhenToUse == 'multiple' && s.multiple) || (a$.WhenToUse == 'all'))) {
			var title = s.title, id = s.id, name = s.name;
			var div = a$.newNode('div', 'a9multiselect-'+ id, 'a9multiselect');
			var span = a$.newNode('div', 'a9multiselect-'+ id +'-title', 'title');
			span.setAttribute('title', title);
			var expcol = a$.newNode('div', 'a9multiselect-click-'+ id, 'expcol-click', '', span, div);
			var ul = a$.newNode('ul');
			if (a$.isIE)
				ul.style.width = '20em';
			var expbody = a$.newNode('div', 'a9multiselect-body-'+ id, 'expcol-body', '', ul);
			expbody.style.display = 'none';
			
			// Timer Events to auto-close the drop-down when not being used
			a$.newEvent(div, 'mouseout', function(event) { a$.msBodyTimer = setTimeout('a$.closeSelect("'+ id +'")', 1500); });
			a$.newEvent(div, 'mouseover', function(event) { clearTimeout(a$.msBodyTimer); a$.msBodyTimer = null; });
			a$.newEvent(expbody, 'mouseout', function(event) { a$.msBodyTimer = setTimeout('a$.closeSelect("'+ id +'")', 1500); });
			a$.newEvent(expbody, 'mouseover', function(event) { clearTimeout(a$.msBodyTimer); a$.msBodyTimer = null; });
			a$.newEvent(ul, 'mouseout', function(event) { clearTimeout(a$.msBodyTimer); a$.msBodyTimer = null; });
			a$.newEvent(ul, 'mouseover', function(event) { clearTimeout(a$.msBodyTimer); a$.msBodyTimer = null; });

			if (a$.isIE)
				var hidden = a$.newNode('<input type="hidden" name="'+ name +'" title="'+ title +'" />', name, '', '', null, div);
			else {
				var hidden = a$.newNode('input', name, '', '', null, div);
				hidden.setAttribute('type', 'hidden');
				hidden.setAttribute('name', name);
				hidden.setAttribute('title', title);
			}

			// insert select all option
			var m = s.options.length;
			if (s.multiple && m >= a$.SelectAllMin) {
				var alli = a$.newNode('li', 'a9selectall-'+ id, 'a9selectall', '', null, ul);
				if (a$.isIE) {
					var allbx = a$.newNode('<input type="checkbox" name="a$-'+ a$.c +'" id="a$-'+ a$.c +'" alt="'+ id +'" />', 'a$-'+ (a$.c++), '', '', null, alli);
					var allbl = a$.newNode('<label for="'+ allbx.id +'" />', '', '', a$.SELECT_ALL, null, alli);
				} else {
					var allbx = a$.newNode('input', 'a$-'+ a$.c++, '', '', null, alli);
					allbx.setAttribute('type', 'checkbox');
					allbx.setAttribute('alt', id);	
					var allbl = a$.newNode('label', '', '', a$.SELECT_ALL, null, alli);
					allbl.setAttribute('for', allbx.id);
				}

				// call to function to get every checkbox under 'a9multiselect-'+ id a$.T('input', a$.e('a9multiselect-'+ id))
				eval("a$.newEvent(allbx, 'click', function () { a$.selectAll(a$.e('"+ allbx.id +"')); a$.chk(a$.e('"+ allbx.id +"')); });");
			}
			var sel = 0;
			for (var j = 0; j < m; j++) {
				var value = s.options[j].value, text = s.options[j].text;
				var li = a$.newNode('li', 'a9-li-'+ a$.c, '', '', null, ul);
				
				var d = a$.newNode('div', '', '', '', null, li);
				var chkType = s.multiple? 'checkbox': 'radio';
				if (a$.isIE) {
					var checked = '', onclick = '';
					if (s.options[j].selected == true) {
						checked = ' checked="checked"';
						 // needed to allow checked entries to be imeadiately activated, but won't work when actually clicked
						onclick = " onclick=\"a$.multiSelect(this, '"+ value +"', 'a9multiselect-"+ id +"');\"";
						sel++;
					}
					var chkbx = a$.newNode('<input title="'+ s.options[j].text +'" name="a9multiselect-options-'+ id +'" alt="'+ id +'" type="'+ chkType +'"'+ checked + onclick +' value="'+ value +'" />', 'a$-'+ a$.c++, '_a9checkbox', '', null, li);
				} else {
					var chkbx = a$.newNode('input', 'a$-'+ a$.c++, '_a9checkbox', '', null, li);
					chkbx.setAttribute('type', chkType);
					chkbx.setAttribute('value', value);
					chkbx.setAttribute('alt', id);
					chkbx.setAttribute('title', s.options[j].text);
					chkbx.setAttribute('name', 'a9multiselect-options-'+ id);
					if (s.options[j].selected == true) {
						chkbx.checked = true;
						 // needed to allow checked entries to be imeadiately activated, but won't work when actually clicked
						chkbx.onclick = "a$.multiSelect(this, '"+ value +"', 'a9multiselect-"+ id +"');";
						sel++;
					}
				}

				a$.newEvent(chkbx, 'click', function(event) {
					a$.cancelbubble(event); // cancel so li event doesn't get activated
					if (a$.isIE) // IE has trouble with 'this' being used here
						var t = a$.e(document.activeElement.id);
					else
						var t = this;
					a$.multiSelect(t, t.value, 'a9multiselect-'+ t.alt);
					a$.chk(t);
					// uncheck the select all
					allbx = a$.t('input', a$.e('a9selectall-'+ t.alt));
					if (allbx) a$.chk(allbx, (allbx.checked = false));
				});

				a$.newEvent(li, 'click', function() {
					var t = a$.e('a$-'+ this.id.slice('a9-li-'.length));
					t.checked = !t.checked;
					a$.multiSelect(t, t.title, 'a9multiselect-'+ t.alt);
					a$.chk(t);
				});

				if (a$.isIE)
					var label = a$.newNode('<label onclick="a$.cancelbubble(event);" for="'+ chkbx.id +'" />', '', '', text, null, li);
				else {
					var label = a$.newNode('label', '', '', text, null, li);
					label.setAttribute('for', chkbx.id);
					a$.newEvent(label, 'click', function(event) { a$.cancelbubble(event); }); // cancel so li event doesn't get activated
				}

				// Hide Radio Buttons for Firefox
				if (chkType == 'radio' && !a$.isIE) {
					chkbx.style.visibility = 'hidden';
					label.style.marginLeft = '-18px';
				}
			}
			if (sel == m && allbx != null)
				allbx.setAttribute('checked', true);
			else if (sel == 0)
				span.innerHTML = a$.NO_SELECTION;

			var bs = a$.node_before(s);
			//alert(bs.parentNode.tagName);
			bs.appendChild(div);
			bs.appendChild(expbody);
			// check the className of s to look for fieldwidth- and valuewidth-
			// if a value is specified without format it's default is pixels
			// options are value: dynamic, 30, 30px, 30em, etc...
			// dynamic will only have a min-width value set
			// if valuewidth is missing, then it's min-width is set to fieldswidths (either default or specified using a$.getStyle)
			
			var fieldwidth = s.className.toLowerCase().indexOf('fieldwidth-');
			var valuewidth = s.className.toLowerCase().indexOf('valuewidth-');
			if (fieldwidth >= 0) {
				var q = s.className.slice(fieldwidth);
				fieldwidth = (q.slice(0, q.indexOf(' ') < 0? q.length: q.indexOf(' '))).slice('fieldwidth-'.length);
				fieldwidth = parseFloat(fieldwidth) == fieldwidth? fieldwidth+'px': fieldwidth;
			} else fieldwidth = '';
			if (valuewidth >= 0) {
				var q = s.className.slice(valuewidth);
				valuewidth = (q.slice(0, q.indexOf(' ') < 0? q.length: q.indexOf(' '))).slice('valuewidth-'.length);
				valuewidth = parseFloat(valuewidth) == valuewidth? valuewidth+'px': valuewidth;
			} else valuewidth = '';
			
			if (fieldwidth != 'dynamic') {
				expcol.style.width = fieldwidth;
				div.style.width = fieldwidth;
			}
			if (valuewidth != 'dynamic')
				expbody.style.width = valuewidth;
			expbody.style.minWidth = a$.getStyle(expcol, 'width');

			if (a$.isIE || a$.isSafari)
				expbody.style.marginTop = '-1.4em';

			// remove original select
			s.parentNode.removeChild(s);

			// when done perform prep functions
			a$.expcol(div);
			a$.multiSelectPrep(div);
		}
	}
}

a$.selectAll = function(o) {
	var I = a$.T('input', a$.e('a9multiselect-body-'+ o.getAttribute('alt')));
	for (var i = 0, m = I.length; i < m; i++) {
		var c = I[i];
		if (c.type == 'checkbox' && c.className == '_a9checkbox') {
			c.checked = o.checked;
			a$.multiSelect(c.id, c.value, 'a9multiselect-'+ c.getAttribute('alt'));
			a$.chk(c);
		}
	}
}

a$.multiSelect = function(chk, value, parent) {
	var pid = parent.slice('a9multiselect-'.length);
	var to = a$.e(pid);

	chk = a$.isString(chk)? a$.e(chk): chk;
	if (chk.checked) {
		chk.type == 'checkbox'? to.value += a$.msSeparator + value: to.value = value;
	} else
		eval("to.value = to.value.replace(/"+ value +"/g, '');");
	var title = a$.e(parent+'-title');

	// cleans up clogged pipes
	to.value = to.value.replace(/\|{3}/g, a$.msSeparator);
	to.value = to.value.replace(/\|{2}/g, a$.msSeparator);
	to.value = to.value.replace(/^\|(.*)/g, '$1');
	to.value = to.value.replace(/(.*)\|$/g, '$1');

	var cbs = a$.T('input', a$.e('a9multiselect-body-'+ pid)), x = '', v = a$.NO_SELECTION;
	var vals = '', c = 0;
	for (var i = 0, l = cbs.length; i < l; i++)
		if (cbs[i].className == '_a9checkbox' && cbs[i].checked) {
			vals += cbs[i].title +' | ';
			c++;
			if (x == 0) {
				v = cbs[i].title;
			} else {
				v = (x+1) +' '+ a$.SELECTED;
			}
			x++;
		}

	vals = c > 1? vals.slice(0, vals.length-3): v;
	title.innerHTML = v;
	t = a$.e(pid).title;
	title.title =  t == ''? vals: t +' : '+ vals;
}

a$.multiSelectPrep = function(parent) {
	if (parent == null) parent = document;
	var pid = parent.id.slice('a9multiselect-'.length);
	var P = a$.T('input', a$.e('a9multiselect-body-'+ pid));
	var toObj = a$.e(parent.id.slice('a9multiselect-'.length));
	var to = toObj.value;
	var newto = '';
	for (var i = 0, l = P.length; i < l; i++) {
		if (P[i].type != null && P[i].className == '_a9checkbox') {
			a$.chk(P[i], false);

			if (P[i].checked == true) {
				a$.chk(P[i]);
				// autoselect and populate the value for default checked items
				var val = P[i].value;
				a$.multiSelect(P[i], val, parent.id);
			}
		}
	}

	if (to != '') { // remove any duplicates when reloading with firefox
		to = to.split(a$.msSeparator).sort();
		for (var i = 1, l = to.length; i < l; i++)
			if (to[i] == to[i-1])
				to[i-1] = null;
		to = to.toString().replace(/,,/g, ',').replace(/,/g, a$.msSeparator);
		toObj.value = to.indexOf(a$.msSeparator) == 0? to.slice(1): to.length > 1 && to.lastIndexOf(a$.msSeparator) == to.length-1? to.slice(0, to.length-1): to;
	}
}

a$.chk = function(c, force) {
	var n = a$.node_after(c);
	if (n != null && n.style) {
		if ((force != null && force) || c.checked) {
			n.style.fontWeight = 'bold';
			if (c.type == 'radio') {
				var R = c.form[c.name];
				for (var i = 0, l = R.length; i < l; i++) {
					var r = R[i];
					if (r.id != c.id)
						a$.node_after(r).style.fontWeight = 'normal';
				}
				a$.expcolclick('a9multiselect-click-'+ c.alt);
			}
		} else {
			n.style.fontWeight = 'normal';
		}
	}
}

a$.closeSelect = function(id) {
	clearTimeout(a$.msBodyTimer);
	a$.msBodyTimer = null;

	var obj = a$.e('a9multiselect-body-'+ id);
	var vis = a$.getStyle(obj, 'display');
	if (vis == 'block') {
		//obj.style.display = 'none';
		a$.expcolclick(a$.e('a9multiselect-click-'+ id));
	}
}

a$.is_ignorable = function(nod) {
  return (nod.nodeType == 8) || // A comment node
         ((nod.nodeType == 3) && !(/[^\t\n\r ]/.test(nod.data))); // a text node, all ws
}

a$.node_before = function(sib) {
	if (a$.isString(sib))
		sib = a$.e(sib);
	while ((sib = sib.previousSibling)) {
		if (!a$.is_ignorable(sib)) return sib;
	}
	return null;
}

a$.node_after = function(sib) {
	while (sib != null && (sib = sib.nextSibling)) {
		if (!a$.is_ignorable(sib)) return sib;
	}
	return null;
}

a$.expcol = function(parent) {
	var x = a$.T("div", parent);
	for (var i = 0, l = x.length; i < l; i++)
		if (x[i].className.indexOf("-click") >= 0) x[i].onclick = a$.expcolclick;
}

a$.expcolclick = function(o, force) {
	var c = null;
	if (a$.isIE)
		var t = this.id? this: a$.isString(o)? a$.e(o): o;
	else
		var t = this.toString().toLowerCase().indexOf('element') >= 0? this: a$.isString(o)? a$.e(o): o;

	c = a$.e('a9multiselect-body-'+ t.id.slice('a9multiselect-click-'.length));
	c.style.position = 'absolute';

	if (c != null && c.style && c.style.display != "block") {
		if (t.className.indexOf("-open") > 0) return;
		t.className = t.className +"-open";
		c.style.display = "block";
		if (force == null || force == false) {
			if (a$.openSelect && a$.openSelect.id != t.id)
				a$.expcolclick(a$.openSelect, true);
			a$.openSelect = t;
		}
	} else if (c != null && c.style) {
		t.className = t.className.substr(0, t.className.length-5);
		c.style.display = "none";
		if (force == null || force == false) {
			a$.openSelect = null;
		}
	}
}

a$.isString = function(o) { return (typeof(o) == "string"); }

/*
	tp: type (eg 'div')
	id: id
	cs: class OR style (if a : exists it is a style (color: pink; display: block;), not a class)
	tx: text to display inside the node
	cd: any child node with which to place inside
	p:  parent node to attach to
*/
a$.newNode = function(tp, id, cs, tx, cd, p) {
	var node = document.createElement(tp);
	if (tx != null && tx != '')
		node.appendChild(document.createTextNode(tx));
	if (id != null && id != '')
		node.id = id;
	if (cs != null && cs != '' && cs.indexOf(':') < 0)
		node.className = cs;
// inline styles removed to limit code to this specific task
//	else if (cs != null && cs != '' && cs.indexOf(':') > 0)
//		a$.setStyles(node, cs);
	if (cd != null)
		node.appendChild(cd);
	if (p != null && p != '')
		(a$.isString(p)? a$.e(p): p).appendChild(node);
	return node;
}

// specific element via id
a$.e = function(id, source) {
	if (source != null)
		return source.getElementById(id);
	return document.getElementById(id);
}

// all elements with tag
a$.T = function(tag, source) {
	if (source != null)
		return source.getElementsByTagName(tag);
	return document.getElementsByTagName(tag);
}

// the first element with tag
a$.t = function(tag, source) {
	if (source != null)
		var T = source.getElementsByTagName(tag);	
	else T = document.getElementsByTagName(tag);
	if (T.length > 0)
		return T[0];
}

// all elements with class
a$.C = function(classname, source) {
	if (source != null)
		return source.getElementsByClassName(classname);
	return document.getElementsByClassName(classname);
}

a$.getStyle = function(obj, styleIE, styleMoz) {
	if (styleMoz == null) styleMoz = styleIE;
	if (a$.isString(obj)) obj = a$.e(obj);
	var s = '';
	if (window.getComputedStyle)
		s = document.defaultView.getComputedStyle(obj, null).getPropertyValue(styleMoz);
	else if (obj.currentStyle)
		s = obj.currentStyle[styleIE];
	if (s == 'auto')
		switch (styleIE) {
		case 'top':		return obj.offsetTop;		break;
		case 'left':	return obj.offsetLeft;		break;
		case 'width':	return obj.offsetWidth;		break;
		case 'height':	return obj.offsetHeight;	break;
		}
	else
		return s;
}

a$.newEvent = function(e, meth, func, cap) {
	if (a$.isString(e))	e = a$.e(e);

	if (e.addEventListener){
		e.addEventListener(meth, func, cap);
    	return true;
	}	else if (e.attachEvent)
		return e.attachEvent("on"+ meth, func);
	return false;
}

// Start things off
a$.newEvent(window, 'load', function () {
	var x = a$.T('select');
	for (var i = 0, l = x.length; i < l; i++) {
		a$.multiSelectCreate(x[i]);
	} 
});

a$.cancelbubble = function(e) {
	if (a$.isIE) e = event;
	if (e) e.cancelBubble = true;
}

function noop() {
	return null;	
}