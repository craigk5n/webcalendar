<script type="text/javascript">
<!-- <![CDATA[
function selectUsers () {
  // find id of user selection object
  var listid = 0;
  for ( i = 0; i < document.searchformentry.elements.length; i++ ) {
    if ( document.searchformentry.elements[i].name == "users[]" )
      listid = i;
  }
  url = "usersel.php?form=searchformentry&listid=" + listid + "&users=";
  // add currently selected users
  for ( i = 0, j = 0; i < document.searchformentry.elements[listid].length; i++ ) {
    if ( document.searchformentry.elements[listid].options[i].selected ) {
      if ( j != 0 )
	url += ",";
      j++;
      url += document.searchformentry.elements[listid].options[i].value;
    }
  }
  //alert ( "URL: " + url );
  // open window
  window.open ( url, "UserSelection",
    "width=500,height=500,resizable=yes,scrollbars=yes" );
}

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
//]]> -->
</script>
