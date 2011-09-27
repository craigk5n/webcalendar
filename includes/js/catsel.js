// $Id$

addLoadListener(function () {
    attachEventListener(document.getElementById('editCategories'), 'submit',
      function () {
        sendCats(this);
      });
    attachEventListener(document.getElementById('selAdd'), 'click', selAdd);
    attachEventListener(document.getElementById('selRem'), 'click', selRemove);
    attachEventListener(document.getElementById('sendCat'), 'click', sendCats);
    attachEventListener(document.getElementById('canCat'), 'click', window.close);
  });
function sendCats(cats) {
  var frm = wc_getCookie('frm');
  var dfe = document.forms[0].elements,
  eventid = 0,
  parentid = parenttext = '',
  woda = window.opener.document.frm;

  // We seem to be looking for the last one. So let's start at the end.
  for (var i = dfe.length - 1; i >= 0; i--) {
    if (dfe[i].name == 'eventcats[]') {
      eventid = i;
      break;
    }
  }
  for (var i = 1, j = dfe[eventid].length; i < j; i++) {
    dfe[eventid].options[i].selected = 1;
    parentid += ',' + parseInt(dfe[eventid].options[i].value);
    parenttext += ', ' + dfe[eventid].options[i].text;
  }
  parentid = parentid.substr(1);
  parenttext = parenttext.substr(1);

  woda.cat_id.value = parentid;
  woda.catnames.value = parenttext;

  window.close();
}

function updateList(ele) {
  document.editCategories.elements['categoryNames'].value += ele.name;
}

function selAdd(btn) {
  // find id of cat selection object
  var catid = eventid = 0,
  dfe = document.forms[0].elements;

  for (var i = 0, j = dfe.length; i < j; i++) {
    if (dfe[i].name == 'cats[]') {
      catid = i;
    }
    if (dfe[i].name == 'eventcats[]') {
      eventid = i;
    }
  }
  var evlist = dfe[eventid],
  isUnique = true;

  // "with" is one of the most inefficient resource hogs in javascript.
  // But, I haven't figured out how to remove them. Yet. :( bb
  with (document.forms[0]) {
    with (dfe[catid]) {
      for (var i = 0, j = length; i < j; i++) {
        if (options[i].selected) {
          with (options[i]) {
            for (var k = 0, l = evlist.length; k < l; j++) {
              if (evlist.options[k].value == value) {
                isUnique = false;
                break; // We only need one.
              }
            }
            if (isUnique) {
              evlist.options[evlist.length] = new Option(text, value);
            }
            options[i].selected = false;
          } //end with options
        }
      } // end for loop
    } // end with islist1
  } // end with document
}

function selRemove(btn) {
  // find id of event cat object
  var dfe = document.forms[0].elements,
  eventid = 0;

  // We seem to be looking for the last one. So let's start at the end.
  for (var i = dfe.length - 1; i >= 0; i--) {
    if (dfe[i].name == 'eventcats[]') {
      eventid = i;
      break;
    }
  }
  with (document.forms[0]) {
    with (dfe[eventid]) {
      for (var i = length - 1; i >= 0; i--) {
        if (options[i].selected) {
          options[i] = null;
        }
      } // end for loop
    }
  } // end with document
}
