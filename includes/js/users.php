<script type="text/javascript">
<!-- <![CDATA[
function show(foo,f,section) {
	document.getElementById(foo).style.display = "block";
	if (f) { setCookie(foo, "o", section); }
}

function hide(foo,f,section) {
	if (document.getElementById(foo)) {
		document.getElementById(foo).style.display = "none";
		if (f) { deleteCookie(foo, section); }
	}
}

<?php //see the showTab function in includes/js.php for common code shared by all pages
	//using the tabbed GUI.
?>var tabs = new Array();
tabs[1] = "users";
tabs[2] = "groups";
tabs[3] = "nonusers";
//]]> -->
</script>
