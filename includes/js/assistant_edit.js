// $Id$
jq(document).ready(function () {
  jq(':button').click(function () {
    // Find id of user selection object.
    var delim = url = '',
    dae = document.assistanteditform.elements,
    listid = 0;
    // We seem to want the last one, so let's start at the end.
    for (var i = dae.length - 1; i >= 0; i--) {
      if (dae[i].name == 'users[]') {
        listid = i;
        break;
      }
    }
    // add currently selected users
    for (var i = 0; dae[listid][i]; i++) {
      if (dae[listid].options[i].selected) {
        url += delim + dae[listid].options[i].value;
        delim = ',';
      }
    }
    window.open('usersel.php?form=assistanteditform&listid=' + listid + '&users=' + url,
      'UserSelection', 'width=500,height=500,resizable=yes,scrollbars=yes');
  });
});
// end assistant_edit.js
