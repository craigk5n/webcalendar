<?php
/*
 * $Id
 *
 * The functions in this file can be used to make elements visible or
 * hidden on the page.
 */
?>
<script type="text/javascript">
<!-- <![CDATA[
// detect browser
NS4 = (document.layers) ? 1 : 0;
IE4 = (document.all) ? 1 : 0;
// W3C stands for the W3C standard, implemented in Mozilla (and Netscape 6) and IE5
W3C = (document.getElementById) ? 1 : 0;	

function makeVisible ( name ) {
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
  }
}

function makeInvisible ( name ) {
  if (W3C) {
    document.getElementById(name).style.visibility = "hidden";
  } else if (NS4) {
    document.layers[name].visibility = "hide";
  } else {
    document.all[name].style.visibility = "hidden";
  }
}

function showTab (name) {
	if (! document.getElementById) { return true; }
	for (var i=0; i<tabs.length; i++) {
		var tname = tabs[i];
		var tab = document.getElementById("tab_" + tname);
		if (tab) {
			tab.className = (tname == name) ? "tabfor" : "tabbak";
		}
		var div = document.getElementById("tabscontent_" + tname);
		if (div) {
			div.style.display = (tname == name) ? "block" : "none";
		}
	}
	return false;
}
//]]> -->
</script>
