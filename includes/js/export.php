<script type="text/javascript">
<!-- <![CDATA[
function selectDate ( day, month, year ) {
  // get currently selected month/year
  monthobj = eval ( 'document.exportform.' + month );
  curmonth = monthobj.options[monthobj.selectedIndex].value;
  yearobj = eval ( 'document.exportform.' + year );
  curyear = yearobj.options[yearobj.selectedIndex].value;
  date = curyear;
  if ( curmonth < 10 )
    date += "0";
  date += curmonth;
  date += "01";
  url = "datesel.php?form=exportform&amp;day=" + day +
    "&amp;month=" + month + "&amp;year=" + year + "&amp;date=" + date;
  var colorWindow = window.open(url,"DateSelection","width=300,height=200,resizable=yes,scrollbars=yes");
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
tabs[1] = "import";
tabs[2] = "export";
//]]> -->
</script>
