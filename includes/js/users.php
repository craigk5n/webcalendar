<script type="text/javascript">
<!-- <![CDATA[
function show(foo,f,section) {
	document.getElementById(foo).style.display = "block";
	if (f) { setCookie(foo, "o", section); }
}

function hide(foo,f, section) {
	if (document.getElementById(foo)) {
		document.getElementById(foo).style.display = "none";
		if (f) { deleteCookie(foo, section); }
	}
}

var tabs = new Array();
function showTab (name) {
	if (! document.getElementById) { return true; }
	for (var i=0; i<tabs.length; i++) {
		var tname = tabs[i];
		var tab = document.getElementById("tab_" + tname);
		if (tab && tab.setAttribute) {
			tab.setAttribute("class", (tname == name) ? "tabfor" : "tabbak");
		}
		var div = document.getElementById("tabscontent_" + tname);
		if (div) {
			div.style.display = (tname == name) ? "block" : "none";
		}
	}
	return false;
}
tabs[1] = "users";
tabs[2] = "groups";
//]]> -->
</script>
