<script type="text/javascript">
<!-- <![CDATA[
function selectUsers () {
  // find id of user selection object
  var listid = 0;
  for ( i = 0; i < document.forms[0].elements.length; i++ ) {
    if ( document.forms[0].elements[i].name == "users[]" )
      listid = i;
  }
  url = "usersel.php?form=editentryform&listid=" + listid + "&users=";
  // add currently selected users
  for ( i = 0, j = 0; i < document.forms[0].elements[listid].length; i++ ) {
    if ( document.forms[0].elements[listid].options[i].selected ) {
      if ( j != 0 )
        url += ",";
      j++;
      url += document.forms[0].elements[listid].options[i].value;
    }
  }
  //alert ( "URL: " + url );
  // open window
  window.open ( url, "UserSelection",
    "width=500,height=500,resizable=yes,scrollbars=yes" );
}
//]]> -->
</script>