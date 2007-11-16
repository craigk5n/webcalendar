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
  var cat_ids = $('cat_id').value;
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
  url = "datesel.php?form=" + frm.id + "&fday=" + day +
    "&fmonth=" + month + "&fyear=" + year + "&date=" + date;
  var colorWindow = window.open(url,"DateSelection","width=300,height=180,"  + MyPosition);
}

function validateDate ( day, month, year ) {
   var d = $(day).selectedIndex;
   var vald = $(day).options[d].value;
   var m = $(month).selectedIndex;
   var valm = $(month).options[m].value;
   var y = $(year).selectedIndex;
   var valy = $(year).options[y].value;
   var c = new Date(valy,valm -1,vald);
   if ( c.getDate() != vald ) {
     alert ( invalidDate  );
     $(day).focus ();
     return false;
   }
	 return c;
 }
 
function showTab(name) {
 if (! document.getElementById) { return true; }
 for (var i=0; i<tabs.length; i++) {
  var tname = tabs[i];
  var tab = document.getElementById("tab_" + tname);
  //we might call without parameter, if so display tabfor div
  if (tab && !name) {
    if ( tab.className == "tabfor" ) name = tname;
  } else if (tab) {
   tab.className = (tname == name) ? "tabfor" : "tabbak";
  }
  var div = document.getElementById("tabscontent_" + tname);
  if (div) {
   div.style.display = (tname == name) ? "block" : "none";
  }
 }
 return false;
}


function toggle_datefields( name, ele) {
	$(name).showIf(!document.getElementById(ele).checked, false);
}

function getScrollingPosition()
{
 var position = [0, 0];

 if (typeof window.pageYOffset != 'undefined')
 {
   position = [
       window.pageXOffset,
       window.pageYOffset
   ];
 }

 else if (typeof document.documentElement.scrollTop
     != 'undefined' && document.documentElement.scrollTop > 0)
 {
   position = [
       document.documentElement.scrollLeft,
       document.documentElement.scrollTop
   ];
 }

 else if (typeof document.body.scrollTop != 'undefined')
 {
   position = [
       document.body.scrollLeft,
       document.body.scrollTop
   ];
 }

 return position;
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

function isNumeric(sText)
{
   //allow blank values. these will become 0
   if ( sText.length == 0 ) 
     return false;
	 var ret = true;
   var validChars = "0123456789";
   var Char;
   for (i = 0, slen = sText.length; i < slen; i++) 
   { 
      Char = sText.charAt(i); 
      if (validChars.indexOf(Char) == -1) 
      {
        ret = false;
      }
   }
   return ret;
}


function setPref(setting, value) {
  var url = 'ajax.php';
  var params = 'page=setPref&setting=' + setting + '&value=' + value;
 var ajax = new Ajax.Request(url,
    {method: 'post', 
    parameters: params, 
    onComplete: doNothing});
}

function doNothing () {
	 //do nothing
}

function getPref(setting, control) {
	if ( ! control ) control =1;
  var url = 'ajax.php';
  var params = 'page=getPref&setting=' + setting + '&control=' + control;
  var ajax = new Ajax.Request(url,
    {method: 'post', 
    parameters: params, 
    onComplete: setPhpVars});
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

function add_dblclick() {
	 if ( domInitialized ) return true;
   if(!document.getElementsByTagName) return false;  
   var rows = $$('td');
	 var rowslen = rows.length;
   for (var i=0; i<rowslen; i++) {
     if ( rows[i].id.length >= 10 && rows[i].id.substring(0,2) == 'td' ) {
       rows[i].style.cursor = 'pointer';
       rows[i].title = doubleClick;
       rows[i].ondblclick = function( event ) {
          dblclick ( this.id );
          return true;
       } 
     }     
   }
	 domInitialized = true;
}

function dblclick ( dblDate ) {
 dblDate = dblDate.substring(2);
 if ( dblDate.length == 12 ) {
   cal_time = "&cal_time=" + dblDate.substring ( 8 ) + '00';
 } else { 
   cal_time = '&allday=1';
 }
 var user = ( document.getElementById('user') ? 
        '&user=' + document.getElementById('user').value : '');
 var cat_id = document.getElementById('cat_id');
 var cat_val = ( cat_id && cat_id.value > 0 
  ? '&cat_id=' + cat_id.value : '');
 var url = 'edit_entry.php?date=' + dblDate.substring ( 0,8 ) 
   + user + cat_val + cal_time;
 window.location.href  = url;
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
//Extend Prototype Element Methods
Object.extend(Element.Methods, {

  hide: function(element, shrink) {
		shrink = typeof shrink == 'undefined' ? true : shrink;
		if ( shrink )
      $(element).style.display = 'none';
		$(element).style.visibility = 'hidden';
    return element;

  },

  show: function(element, shrink) {
		shrink = typeof shrink == 'undefined' ? true : shrink;
		if ( shrink )
      $(element).style.display = '';
		$(element).style.visibility = 'visible';
    return element;
  },
	
  showIf: function(element, iftest, shrink) {
		iftest = typeof iftest == 'undefined' ? true : iftest;
		if ( iftest )
      $(element).show(shrink);
		else 
		  $(element).hide(shrink);
  }
});
// Call this to reflect the new functions
Element.addMethods();

var domInitialized = false;


