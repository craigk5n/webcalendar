// $Id$

addLoadListener('focus');

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
      toggleVisible('timeentrystart', 'visible', '', true);
      toggleVisible('timeentry' + (parentForm.duration_h ? 'duration' : 'end'), 'visible', '', true; );
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

    for (var i = 0; parentForm.year[i]; i++) {
      if (parentForm.year.options[i].value == year) {
        parentForm.year.selectedIndex = i;
      }
    }
    window.close();
  }
}
