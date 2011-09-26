// $Id$

function selectUsers() {
  // Find id of user selection object.
  var delim = url = '',
  dae = document.assistanteditform.elements,
  listid = 0;

  // We seem to want the last one, so let's start at the end.
  for (var i = dae.length; i >= 0; i--) {
    if (dae[i].name == 'users[]') {
      listid = i;
      break;
    }
  }

  // add currently selected users
  for (var i = 0, j = dae[listid].length; i < j; i++) {
    if (dae[listid].options[i].selected) {
      url += delim + dae[listid].options[i].value;
      delim = ',';
    }
  }
  window.open('usersel.php?form=assistanteditform&listid=' + listid + '&users='
     + url, 'UserSelection', 'width=500,height=500,resizable=yes,scrollbars=yes');
}
