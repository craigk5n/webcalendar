<?php /* $Id: views_edit.php,v 1.12.2.2 2007/08/06 02:28:27 cknudsen Exp $  */ ?>
function selectUsers () {
  var listid = 0;
  for ( i = 0; i < document.editviewform.elements.length; i++ ) {
    if ( document.editviewform.elements[i].name == "users[]" )
      listid = i;
  }
  url = "usersel.php?form=editviewform&listid=" + listid + "&users=";
  // add currently selected users
  for ( i = 0, j = 0; i < document.editviewform.elements[listid].length; i++ ) {
    if ( document.editviewform.elements[listid].options[i].selected ) {
      if ( j != 0 )
  url += ",";
      j++;
      url += document.editviewform.elements[listid].options[i].value;
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

