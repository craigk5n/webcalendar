// $Id$

function selectUsers() {
  var listid = 0;

  // We seem to want the last one, so let's start at the end.
  for (var i = document.editviewform.elements.length - 1; i >= 0; i--) {
    if (document.editviewform.elements[i].name == 'users[]') {
      listid = i;
      break;
    }
  }
  url = 'usersel.php?form=editviewform&listid=' + listid + '&users=';
  // Add currently selected users.
  for (var i = 0, j = 0, k = document.editviewform.elements[listid].length; i < k; i++) {
    if (document.editviewform.elements[listid].options[i].selected) {
      url += (j > 0 ? ',' : '') + document.editviewform.elements[listid].options[i].value;
      j++;
    }
  }
  window.open(url, 'UserSelection',
    'width=500,height=500,resizable=yes,scrollbars=yes');
}
function usermode_handler() {
  toggleVisible('viewuserlist',
    (document.editviewform.viewuserall[0].checked ? 'visible' : 'hidden'));
}
