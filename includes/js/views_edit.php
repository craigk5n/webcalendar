<script type="text/javascript">
<!-- <![CDATA[
function selectUsers () {
  url = "usersel.php?form=editviewform&listid=3&users=";
  // add currently selected users
  for ( i = 0, j = 0; i < document.editviewform.elements[3].length; i++ ) {
    if ( document.editviewform.elements[3].options[i].selected ) {
      if ( j != 0 )
	url += ",";
      j++;
      url += document.editviewform.elements[3].options[i].value;
    }
  }
  //alert ( "URL: " + url );
  // open window
  window.open ( url, "UserSelection",
    "width=500,height=500,resizable=yes,scrollbars=yes" );
}

function usermode_handler ()
{
  var show = ( document.editviewform.viewuserall[0].checked );
  if ( show ) {
    makeVisible ( "viewuserlist" );
  } else {
    makeInvisible ( "viewuserlist" );
  }
}

//]]> -->
</script>
