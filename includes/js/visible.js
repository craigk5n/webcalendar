
// detect browser
NS4 = (document.layers) ? 1 : 0;
IE4 = (document.all) ? 1 : 0;
// W3C stands for the W3C standard, implemented in Mozilla (and Netscape 6) and IE5
W3C = (document.getElementById) ? 1 : 0; 

function makeVisible( name, hide ) {
 //alert (name);
 var ele;
  if ( W3C ) {
    ele = document.getElementById(name);
  } else if ( NS4 ) {
    ele = document.layers[name];
  } else { // IE4
    ele = document.all[name];
  }

  if ( NS4 ) {
    ele.visibility = "show";
  } else {  // IE4 & W3C & Mozilla
    ele.style.visibility = "visible";
    if ( hide )
     ele.style.display = "";
  }
}

function makeInvisible( name, hide ) {
  //alert (name);
 if (W3C) {
    document.getElementById(name).style.visibility = "hidden";
    if ( hide )
      document.getElementById(name).style.display = "none";
  } else if (NS4) {
    document.layers[name].visibility = "hide";
  } else {
    document.all[name].style.visibility = "hidden";
    if ( hide )
      document.all[name].style.display = "none";
  }
}



function show(foo,f,section) {
	document.getElementById(f).style.display = "block";
}

function hide(foo,f,section) {
	if (document.getElementById(f)) {
		document.getElementById(f).style.display = "none";
	}
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

function visByClass(classname, state){
 var inc=0;
 var alltags=document.all? document.all : document.getElementsByTagName("*");
 for (i=0; i<alltags.length; i++){
 var str=alltags[i].className;
   if ( str && str.match(classname) )
     if ( state=='hide')
       alltags[i].style.display = "none";
     else
       alltags[i].style.display = "";     
 }
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

function toggle_datefields( name, ele ) {
  var enabled = document.getElementById(ele.id).checked;
  if ( enabled ) { 
      makeInvisible ( name );
  } else {
      makeVisible ( name ); 
  }
}

function callEdit() {
  var features = 'width=600,height=500,resizable=yes,scrollbars=no';
  var url = "edit_entry.php";
  editwin = window.open( url, "edit_entry", features ); 
}
