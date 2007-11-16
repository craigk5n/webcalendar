


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
