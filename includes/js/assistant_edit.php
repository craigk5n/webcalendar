<?php /* $Id: assistant_edit.php,v 1.10.2.2 2007/08/06 02:28:27 cknudsen Exp $  */ ?>
function selectUsers () {
  // find id of user selection object
  var listid = 0;
  for ( i = 0; i < document.assistanteditform.elements.length; i++ ) {
    if ( document.assistanteditform.elements[i].name == "users[]" )
      listid = i;
  }
  url = "usersel.php?form=assistanteditform&listid=" + listid + "&users=";
  // add currently selected users
  for ( i = 0, j = 0; i < document.assistanteditform.elements[listid].length; i++ ) {
    if ( document.assistanteditform.elements[listid].options[i].selected ) {
      if ( j != 0 )
        url += ",";
      j++;
      url += document.assistanteditform.elements[listid].options[i].value;
    }
  }
  //alert ( "URL: " + url );
  // open window
  window.open ( url, "UserSelection",
    "width=500,height=500,resizable=yes,scrollbars=yes" );
}

