// $Id$

// detect browser
NS4 = (document.layers ? 1 : 0);
IE4 = (document.all ? 1 : 0);
// W3C stands for the W3C standard, implemented in Mozilla (and Netscape 6) and IE5
W3C = (document.getElementById ? 1 : 0);

addLoadListener('focus');

/*
 * Similar to function in "js/visible.js" but, effects the parent.
 */
function makeVisible(name) {
  var ele;

  if (W3C) {
    ele = window.opener.document.getElementById(name);
  } else if (NS4) {
    ele = window.opener.document.layers[name];
  } else { // IE4
    ele = window.opener.document.all[name];
  }

  if (NS4) {
    ele.visibility = 'show';
  } else { // IE4 & W3C & Mozilla
    ele.style.visibility = 'visible';
  }
}
function schedule_event(hours, minutes) {
  var year = wc_getCookie('year');
  var month = wc_getCookie('month');
  var day = wc_getCookie('day');
  var frm = wc_getCookie('frm');

  if (confirm(xlate['changeEntryDatetime'])) {
    // translate( 'Change entry date/time' )
    var parentForm = window.opener.document.forms[frm];

    if (frm == 'editentryform') {
      parentForm.timetype.selectedIndex = 1;
      // Make time controls visible on parent.
      makeVisible('timeentrystart');

      if (parentForm.duration_h) {
        makeVisible('timeentryduration');
      } else {
        makeVisible('timeentryend');
      }
    }
    parentForm.entry_hour.value = hours;
    if (hours > 12) {
      if (parentForm.entry_ampmP) {
        parentForm.entry_hour.value = hours - 12;
        parentForm.entry_ampmP.checked = true;
      }
    } else {
      if (hours == 12 && parentForm.entry_ampmP) {
        parentForm.entry_ampmP.checked = true;
      } else {
        if (parentForm.entry_ampmA) {
          parentForm.entry_ampmA.checked = true;
        }
      }
    }
    if (minutes <= 9)
      minutes = '0' + minutes;

    parentForm.entry_minute.value = minutes;
    parentForm.day.selectedIndex = day - 1;
    parentForm.month.selectedIndex = month - 1;

    for (var i = 0; i < parentForm.year.length; i++) {
      if (parentForm.year.options[i].value == year) {
        parentForm.year.selectedIndex = i;
      }
    }
    window.close();
  }
}
