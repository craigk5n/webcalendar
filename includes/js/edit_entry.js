// $Id$

var bydayAr = bymonthdayAr = bysetposAr = [];

linkFile('includes/js/visible.js');
// Add Modal Dialog javascript/CSS
linkFile('includes/js/modalbox/modalbox.css','link','','screen' );
linkFile('includes/tabcontent/tabcontent.css','link');
linkFile('includes/js/modalbox/modalbox.js');
linkFile('includes/js/scriptaculous/scriptaculous.js?load=builder,effects');
linkFile('includes/tabcontent/tabcontent.js');

addLoadListener(function () {
    if (!document.editentryform)
      return false;

    // Define these variables here so they are valid.
    form = document.editentryform;
    elements = document.editentryform.elements;
    elementlength = document.editentryform.elements.length;

    // Initialize byxxxAr Objects.
    if (form.bydayList) {
      bydayList = form.bydayList.value;

      if (bydayList.search(/,/) > -1) {
        bydayList = bydayList.split(',');

        for (var key in bydayList) {
          if (key == isNumeric(key))
            bydayAr[bydayList[key]] = bydayList[key];
        }
      } else if (bydayList.length > 0) {
        bydayAr[bydayList] = bydayList;
      }
    }

    if (form.bymonthdayList) {
      bymonthdayList = form.bymonthdayList.value;

      if (bymonthdayList.search(/,/) > -1) {
        bymonthdayList = bymonthdayList.split(',');

        for (var key in bymonthdayList) {
          if (key == isNumeric(key))
            bymonthdayAr[bymonthdayList[key]] = bymonthdayList[key];
        }
      } else if (bymonthdayList.length > 0) {
        bymonthdayAr[bymonthdayList] = bymonthdayList;
      }
    }

    if (form.bysetposList) {
      bysetposList = form.bysetposList.value;

      if (bysetposList.search(/,/) > -1) {
        bysetposList = bysetposList.split(',');

        for (var key in bysetposList) {
          if (key == isNumeric(key))
            bysetposAr[bysetposList[key]] = bysetposList[key];
        }
      } else if (bysetposList.length > 0) {
        bysetposAr[bysetposList] = bysetposList;
      }
    }

    completed_handler();
    rpttype_handler();
    timetype_handler();
    toggle_rem_rep();
    toggle_reminders();
    toggle_until();
  });

function displayInValid(myvar) {
  alert(xlate['inputTimeOfDay']); // translate( 'must enter valid time' )
  myvar.select();
  myvar.focus();
}

function isNumeric(sText) {
  // Allow blank values. these will become 0.
  if (sText.length == 0)
    return sText;

  var validChars = '0123456789',
  Char;

  for (var i = sText.length - 1; i >= 0 && sText != 99; i++) {
    Char = sText.charAt(i);

    if (validChars.indexOf(Char) == -1) {
      sText = 99;
    }
  }
  return sText;
}

function completed_handler() {
  if (form.percent) {
    // elements['dateselIcon_completed'].disabled =
    elements['completed_year'].disabled =
      elements['completed_month'].disabled =
      elements['completed_day'].disabled =
      (form.percent.selectedIndex != 10 || form.others_complete.value != 'yes');
  }
}

function selAdd(btn) {
  with (form) {
    with (form.entry_part) {
      for (var i = 0; i < length; i++) {
        if (options[i].selected) {
          with (options[i]) {
            if (is_unique(value)) {
              form.sel_part.options[form.sel_part.length] = new Option(text, value);
            }
            options[i].selected = false;
          } //end with options
        }
      } // end for loop
    } // end with islist1
  } // end with document
}

function is_unique(val) {
  unique = true;
  var sel = form.sel_part;

  for (var j = 0; j < sel.length; j++) {
    if (sel.options[j].value == val)
      unique = false;
  }
  return unique;
}

function selResource(btn) {
  with (form) {
    with (form.res_part) {
      for (var r = 0; r < length; r++) {
        if (options[r].selected) {
          with (options[r]) {
            if (is_unique(value)) {
              form.sel_part.options[form.sel_part.length] = new Option(text, value);
            }
            options[r].selected = false;
          } //end with options
        }
      } // end for loop
    }
  } // end with document
}
function selRemove(btn) {
  with (form) {
    with (form.sel_part) {
      for (var i = 0; i < length; i++) {
        if (options[i].selected) {
          options[i] = null;
        }
      } // end for loop
    }
  } // end with document
}

function lookupName() {
  var selectid = -1,
  x = form.lookup.value.length;
  var lower = form.lookup.value.toLowerCase();

  form.entry_part.selectedIndex =
    form.res_part.selectedIndex = -1;

  if (form.groups)
    form.groups.selectedIndex = -1;

  //check userlist
  for (var i = 0; i < form.entry_part.length; i++) {
    str = form.entry_part.options[i].text;

    if (str.substring(0, x).toLowerCase() == lower) {
      selectid = i;
      i = form.entry_part.length;
    }
  }
  if (selectid > -1) {
    form.entry_part.selectedIndex = selectid;
    return true;
  }
  //check resource list
  for (var i = 0; i < form.res_part.length; i++) {
    str = form.res_part.options[i].text;

    if (str.substring(0, x).toLowerCase() == lower) {
      selectid = i;
      i = form.res_part.length;
    }
  }
  if (selectid > -1) {
    form.res_part.selectedIndex = selectid;
    return true;
  }
  //check groups if enabled
  if (form.groups) {
    for (var i = 0; i < form.groups.length; i++) {
      str = form.groups.options[i].text;

      if (str.substring(0, x).toLowerCase() == lower) {
        selectid = i;
        i = form.groups.length;
      }
    }
    if (selectid > -1) {
      form.groups.selectedIndex = selectid;
      return true;
    }
  }
}
