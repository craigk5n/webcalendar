<script type="text/javascript">
<!-- <![CDATA[
function selectUsers () {
  url = "usersel.php?form=editviewform&listid=3&users=";
  // add currently selected users
  for ( i = 0, j = 0; i < document.forms[0].elements[3].length; i++ ) {
    if ( document.forms[0].elements[3].options[i].selected ) {
      if ( j != 0 )
	url += ",";
      j++;
      url += document.forms[0].elements[3].options[i].value;
    }
  }
  //alert ( "URL: " + url );
  // open window
  window.open ( url, "UserSelection",
    "width=500,height=500,resizable=yes,scrollbars=yes" );
}
//]]> -->
</script>
