// $Id: assistant_edit.js,v 1.1 2008/12/15 10:42:45 bbannon Exp $

function selectUsers() {
  // Find id of user selection object.
  var
    delim  = url = '',
    dae    = document.assistanteditform.elements,
    listid = 0;

  for ( i = 0; i < dae.length; i++ ) {
    if ( dae[i].name == "users[]" ) {
      listid = i;
    }
  }

  // add currently selected users
  for ( i = 0; i < dae[listid].length; i++ ) {
    if ( dae[listid].options[i].selected ) {
      url += delim + dae[listid].options[i].value;
      delim = ',';
    }
  }
  // open window
  window.open( 'usersel.php?form=assistanteditform&listid=' + listid + '&users='
    + url, 'UserSelection', 'width=500,height=500,resizable=yes,scrollbars=yes' );
}
