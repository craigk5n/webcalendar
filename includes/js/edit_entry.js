// $Id$

var bydayAr = bymonthdayAr = bysetposAr = [];

// See the showTab function in "includes/js/visible.js"
// for common code shared by all pages using the tabbed GUI.
var sch_win,
tabs = [
  'details',
  'participants',
  'pete',
  'reminder'
];

linkFile('includes/js/visible.js');
// Add Modal Dialog javascript/CSS
linkFile('includes/js/modalbox/modalbox.css', 'link', '', 'screen');
linkFile('includes/tabcontent/tabcontent.css', 'link');
linkFile('includes/js/modalbox/modalbox.js');
linkFile('includes/js/scriptaculous/scriptaculous.js?load=builder,effects');
linkFile('includes/tabcontent/tabcontent.js');

addLoadListener(function () {
  if (!document.editentryform)
    return false;

  for (var i = tabs.length - 1; i > 1; i++ ){
    toggleVisible(tabs[i], 'visible', 'none');
  }

  // Define these variables here so they are valid.
  form = document.editentryform;
  elements = form.elements;
  elementlength = elements.length;

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
function add_exception(which) {
  var sign = '-';

  if (which) {
    sign = '+';
  }
  var ymd = $('except_YMD').value;
  var y = ymd.substr(0, 4);
  var m = ymd.substr(4, 2);

  if (m.substr(0, 1) == '0')
    m = m.substr = (1, 1);

  var d = ymd.substr(6, 2);

  if (d.substr(0, 1) == '0')
    d = d.substr = (1, 1);

  var c = new Date(parseInt(y), parseInt(m) - 1, parseInt(d));

  if (c.getDate() != d) {
    alert(xlate['invalidDate']); // translate( 'Invalid Date' )
    return false;
  }
  var exceptDate = ymd,
  isUnique = true;

  // Test to see if this date is already in the list.
  with (form) {
    with (elements['exceptions[]']) {
      for (var i = length - 1; i >= 0; i--) {
        if (options[i].text == '-' + exceptDate || options[i].text == '+' + exceptDate) {
          isUnique = false;
          break; // Only need one.
        }
      }
    }
  }
  if (isUnique) {
    elements['exceptions[]'].options[elements['exceptions[]'].length] = new Option(sign + exceptDate, sign + exceptDate);
    toggleVisible('select_exceptions', 'visible');
    toggleVisible('select_exceptions_not', 'hidden');
  }
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
function del_selected() {
  with (form) {
    with (elements['exceptions[]']) {
      for (var i = length - 1; i >= 0; i--) {
        if (options[i].selected) {
          options[i] = null;
        }
      } // end for loop
      if (!length) {
        toggleVisible('select_exceptions', 'hidden');
        toggleVisible('select_exceptions_not', 'visible');
      }
    }
  } // end with document
}
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
function is_unique(val) {
  unique = true;
  var sel = form.sel_part;

  for (var i = sel.length - 1; i >= 0; i--) {
    if (sel.options[i].value == val) {
      unique = false;
      break;
    }
  }
  return unique;
}
function lookupName() {
  var selectid = -1,
  x = form.lookup.value.length;
  var lower = form.lookup.value.toLowerCase();

  form.entry_part.selectedIndex =
    form.res_part.selectedIndex = -1;

  if (form.groups)
    form.groups.selectedIndex = -1;

  // Check userlist.
  for (var i = 0, j = form.entry_part.length; i < j; i++) {
    if (form.entry_part.options[i].text.substring(0, x).toLowerCase() == lower) {
      selectid = i;
      break;
    }
  }
  if (selectid > -1) {
    form.entry_part.selectedIndex = selectid;
    return true;
  }
  // Check resource list.
  for (var i = 0, j = form.res_part.length; i < j; i++) {
    if (form.res_part.options[i].text.substring(0, x).toLowerCase() == lower) {
      selectid = i;
      break;
    }
  }
  if (selectid > -1) {
    form.res_part.selectedIndex = selectid;
    return true;
  }
  // Check groups if enabled.
  if (form.groups) {
    for (var i = 0, j = form.groups.length; i < j; i++) {
      if (form.groups.options[i].text.substring(0, x).toLowerCase() == lower) {
        selectid = i;
        break;
      }
    }
    if (selectid > -1) {
      form.groups.selectedIndex = selectid;
      return true;
    }
  }
}
function rpttype_handler() {
  //Repeat Tab disabled
  if (!form.rpttype) {
    return;
  }
  var expert = (document.getElementById('rptmode').checked);
  var i = form.rpttype.selectedIndex,
  val = form.rpttype.options[i].text;

  //i == 0 none
  //i == 1 daily
  //i == 2 weekly
  //i == 3,4,5 monthlyByDay, monthlyByDate, monthlyBySetPos
  //i == 6 yearly
  //i == 7 manual  Use only Exclusions/Inclusions
  //Turn all off initially
  for (var i in Array(
      'rpt_mode',
      'rptwkst',
      'weekdays_only',
      )) {
    toggleVisible(i, 'hidden');
  }
  for (var i in Array(
      'rptbydayextended',
      'rptbydayln',
      'rptbydayln1',
      'rptbydayln2',
      'rptbydayln3',
      'rptbydayln4',
      'rptbymonth',
      'rptbymonthdayextended',
      'rptbysetpos',
      'rptbyweekno',
      'rptbyyearday',
      // 'rptday',
      'rptenddate1',
      'rptenddate2',
      'rptenddate3',
      'rptexceptions',
      'rptfreq',
      // 'select_exceptions_not',
    )) {
    toggleVisible(i, 'hidden', 'none');
  }

  if (i > 0 && i < 7) {
    // always on
    for (var i in Array(
        'rptenddate1',
        'rptenddate2',
        'rptenddate3',
        'rptexceptions',
        'rptfreq',
        )) {
      toggleVisible(i, 'visible', 'block');
    }
    toggleVisible('rpt_mode', 'visible');

    if (i == 1) { // daily
      toggleVisible('weekdays_only', 'visible');
    }

    if (i == 2) { // weekly
      toggleVisible('rptbydayextended', 'visible', 'block');

      if (expert) {
        toggleVisible('rptwkst', 'visible');
      }
    }
    if (i == 3) { // monthly (by day)
      if (expert) {
        for (var i in Array(
            'rptbydayln',
            'rptbydayln1',
            'rptbydayln2',
            'rptbydayln3',
            'rptbydayln4',
            )) {
          toggleVisible(i, 'visible', 'block');
        }
        toggleVisible('rptwkst', 'visible');
      }
    }
    if (i == 4) { // monthly (by date)
      if (expert) {
        toggleVisible('rptbydayextended', 'visible', 'block');
        toggleVisible('rptbymonthdayextended', 'visible', 'block');
      }
    }
    if (i == 5) { // monthly (by position)
      toggleVisible('rptbysetpos', 'visible', 'block');
    }
    if (i == 6) { // yearly
      if (expert) {
        for (var i in Array(
        'rptbydayln',
        'rptbydayln1',
        'rptbydayln2',
        'rptbydayln3',
        'rptbydayln4',
        'rptbymonthdayextended',
        'rptbyweekno',
        'rptbyyearday',
            )) {
          toggleVisible(i, 'visible', 'block');
        }
        toggleVisible('rptwkst', 'visible');
      }
        toggleVisible('rptwkst', 'visible');
      }
    }
    if (expert) {
      toggleVisible('rptbydayextended', 'visible', 'block');
      toggleVisible('rptbymonth', 'visible', 'block');
      toggleVisible('weekdays_only', 'hidden');
    }
  }
  if (i == 7) {
    toggleVisible('rptexceptions', 'visible', 'block');
  }
}
function rpttype_weekly() {
  if (form.rpttype.options[form.rpttype.selectedIndex].text == 'Weekly') {
    //Get Event Date values
    var c = new Date(form.year.options[form.year.selectedIndex].value,
        form.month.options[form.month.selectedIndex].value - 1,
        form.day.options[form.day.selectedIndex].value);

    elements[bydayLabels[c.getDay()]].checked = true;
  }
}
// Set the state (selected or unselected) if a single user in the list of users.
function selectByLogin(login) {
  // Check Users.
  var list = document.editentryform.entry_part;

  for (var i = list.options.length - 1; i >= 0; i--) {
    if (list.options[i].value == login) {
      list.options[i].selected = true;
      return true;
    }
  }
  // Check Resources.
  var list = document.editentryform.res_part;

  for (var i = list.options.length - 1; i >= 0; i--) {
    if (list.options[i].value == login) {
      list.options[i].selected = true;
      return true;
    }
  }
}
function selAdd(btn) {
  with (form) {
    with (form.entry_part) {
      for (var i = length - 1; i >= 0; i--) {
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
function selRemove(btn) {
  with (form) {
    with (form.sel_part) {
      for (var i = length - 1; i >= 0; i--) {
        if (options[i].selected) {
          options[i] = null;
        }
      } // end for loop
    }
  } // end with document
}
function selResource(btn) {
  with (form) {
    with (form.res_part) {
      for (var i = length - 1; i >= 0; i--) {
        if (options[i].selected) {
          with (options[i]) {
            if (is_unique(value)) {
              form.sel_part.options[form.sel_part.length] = new Option(text, value);
            }
            options[i].selected = false;
          } //end with options
        }
      } // end for loop
    }
  } // end with document
}
// Show Availability for the first selection.
function showSchedule() {
  var cols = workEndHour - workStartHour,
  delim = users = '',
  userlist = form.elements['selectedPart[]'],
  w = 760,
  h = 180;

  for (var i = 0; j = userlist.length; i < j; i++) {
    users += delim + userlist.options[i].value;
    delim = ',';
    h += 18;
  }
  if (users == '') {
    alert(xlate['addParticipant']); // translate( 'Please add a participant' )
    return false;
  }
  var mX = 100,
  mY = 200
    url = 'availability.php?users=' + users
     + '&form=' + 'editentryform'
     + '&year=' + form.year.value
     + '&month=' + form.month.value
     + '&day=' + form.day.options[form.day.selectedIndex].text;

  if (sch_win != null && !sch_win.closed) {
    h = h + 30;
    sch_win.location.replace(url);
    sch_win.resizeTo(w, h);
  } else {
    sch_win = window.open(url, 'showSchedule', 'left=' + mX + ',top=' + mY
         + ',screenx=' + mX + ',screeny=' + mY + ',width=' + w + ',height=' + h
         + ',resizable=yes,scrollbars=yes');
  }
}
// This function is called when the event type combo box is changed.
// If the user selects "untimed event" or "all day event",
// the times & duration fields are hidden.
// If they change their mind & switch it back,
// the original values are restored for them.
function timetype_handler() {
  if (!form.timetype)
    return true;

  var i = form.timetype.selectedIndex,
  val = form.timetype.options[i].text;

  if (i != 1) {
    toggleVisible('timeentrystart', (i != 1
         ? 'hidden' // Untimed/All Day
         : 'visible')); // Timed Event

    if (form.timezonenotice) {
      toggleVisible('timezonenotice', (i != 1 ? 'hidden' : 'visible'));
    }

    toggleVisible('timeentry' + (form.duration_h ? 'duration' : 'end'),
      (i != 1 ? 'hidden' : 'visible'));

    if (form.rpttype) {
      toggleVisible('rpt_until_time_date', (i != 1 ? 'hidden' : 'visible'),
        (i != 1 ? 'none' : 'block'));
    }
  }
}
function toggle_byday(ele) {
  var bydaytext = bydayTrans[ele.id.substr(2, 1)],
   bydayVal = bydayLabels[ele.id.substr(2, 1)],
   tmp = '';

  if (ele.value.length > 4) {
    // blank
    ele.value = ele.id.substr(1, 1) + bydaytext;
    tmp = ele.id.substr(1, 1) + bydayVal;
  } else if (ele.value == ele.id.substr(1, 1) + bydaytext) {
    // positive value
    ele.value = (parseInt(ele.id.substr(1, 1)) - 6) + bydaytext;
    tmp = (parseInt(ele.id.substr(1, 1)) - 6) + bydayVal;
  } else if (ele.value == (parseInt(ele.id.substr(1, 1)) - 6) + bydaytext) {
    // negative value
    ele.value = '        ';
    tmp = '';
  }
  bydayAr[ele.id.substr(1)] = tmp;
}

function toggle_bymonthday(ele) {
  var tmp = '';
  if (ele.value.length > 3) {
    // blank
    ele.value = tmp = ele.id.substr(10);
  } else if (ele.value == ele.id.substr(10)) {
    // positive value
    ele.value = tmp = parseInt(ele.id.substr(10)) - 32;
  } else if (ele.value == (parseInt(ele.id.substr(10)) - 32)) {
    // negative value
    ele.value = '     ';
    tmp = '';
  }
  bymonthdayAr[ele.id.substr(10)] = tmp;
}
function toggle_bysetpos(ele) {
  var tmp = '';

  if (ele.value.length > 3) {
    // blank
    ele.value = tmp = ele.id.substr(8);
  } else if (ele.value == ele.id.substr(8)) {
    // positive value
    ele.value = tmp = parseInt(ele.id.substr(8)) - 32;
  } else if (ele.value == (parseInt(ele.id.substr(8)) - 32)) {
    // negative value
    ele.value = '    ';
    tmp = '';
  }
  bysetposAr[ele.id.substr(8)] = tmp;
}
function toggle_rem_rep() {
  elements['rem_rep_days'].disabled =
    elements['rem_rep_hours'].disabled =
    elements['rem_rep_minutes'].disabled =
    (elements['rem_rep_count'].value == 0);
}
function toggle_rem_when() {
  //Reminder Tab disabled
  if (!form.rem_when) {
    return;
  }
  if (elements['reminder_ampmA']) {
    if (elements['rem_when_date'].checked == true) {
      document.getElementById('reminder_ampmA').disabled =
        document.getElementById('reminder_ampmP').disabled = false;
    } else {
      document.getElementById('reminder_ampmA').disabled =
        document.getElementById('reminder_ampmP').disabled = 'disabled';
    }
  }
  elements['rem_days'].disabled =
    elements['rem_hours'].disabled =
    elements['rem_minutes'].disabled =
    elements['rem_beforeY'].disabled =
    elements['rem_relatedS'].disabled =
    elements['rem_beforeN'].disabled =
    elements['rem_relatedE'].disabled = elements['rem_when_date'].checked;

  elements['reminder_year'].disabled =
    elements['reminder_month'].disabled =
    elements['reminder_day'].disabled =
    elements['reminder_hour'].disabled =
    elements['reminder_minute'].disabled = (elements['rem_when_date'].checked != true);
}
function toggle_reminders() {
  // Reminder Tab disabled
  if (!form.rem_when) {
    return;
  }
  toggle_rem_when();
  toggleVisible('reminder_repeat', 'hidden', 'none');
  toggleVisible('reminder_when', 'hidden', 'none');

  if (elements['reminderYes'].checked == true) {
    toggleVisible('reminder_repeat', 'visible', 'block');
    toggleVisible('reminder_when', 'visible', 'block');
  }
}
function toggle_until() {
  //Repeat Tab disabled
  if (!form.rpttype) {
    return;
  }
  // use date
  elements['rpt_year'].disabled =
    elements['rpt_month'].disabled =
    elements['rpt_day'].disabled =
    elements['rpt_hour'].disabled =
    elements['rpt_minute'].disabled =
    (form.rpt_untilu.checked != true);

  // use count
  elements['rpt_count'].disabled = (form.rpt_untilc.checked != true);

  if (elements['rpt_ampmA']) {
    if (form.rpt_untilu.checked) { // use until date
      document.getElementById('rpt_ampmA').disabled =
        document.getElementById('rpt_ampmP').disabled = false;
    } else {
      document.getElementById('rpt_ampmA').disabled =
        document.getElementById('rpt_ampmP').disabled = 'disabled';
    }
  }
}
// Do a little form verifying.
function validate_and_submit() {
  if (form.name.value == '') {
    form.name.select();

    if (evtEditTabs)
      showTab('details');

    form.name.focus();
    // translate( 'must enter Brief Description' )
    alert(xlate['inputBriefDescipt']);
    return false;
  }
  if (form.timetype && form.timetype.selectedIndex == 1) {
    h = parseInt(isNumeric(form.entry_hour.value));
    m = parseInt(isNumeric(form.entry_minute.value));

    // Ask for confirmation for time of day
    // if it is before the user's preference for work hours.
    if (h < $WORK_DAY_START_HOUR + (timeFmt == '12' ? ' && form.entry_ampmA.checked' : '')) {
      if (!confirm(xlate['timeB4WorkHours']))
        // translate( 'time before work hours' )
        return false;
    }
  }
  // Was there really a change?
  changed = false;
  for (var i = form.elements.length - 1; i >= 0; i--) {
    field = form.elements[i];
    switch (field.type) {
    case 'radio':
    case 'checkbox':
      if (field.checked != field.defaultChecked)
        changed = true;

      break;
    case 'text':
    case 'textarea':
      if (field.value != field.defaultValue)
        changed = true;

      break;
    case 'select-one':
      //Don't register a percentage change
      if (form.elements[i].name == 'percent')
        break;

      // case 'select-multiple':
      for (var j = field.length - 1; j >= 0; j--) {
        if (field.options[j].selected != field.options[j].defaultSelected)
          changed = true;
      }
      break;
    }
  }
  if (changed) {
    form.entry_changed.value = 'yes';
  }

  // Add code to make HTMLArea code stick in TEXTAREA.
  if (typeof editor != 'undefined')
    editor._textArea.value = editor.getHTML();

  // Check if Event date is valid.
  var bydayStr = bymonthdayStr = bysetposStr = '',
  vald = form.day.options[form.day.selectedIndex].value;

  var c = new Date(form.year.options[form.year.selectedIndex].value,
      form.month.options[form.month.selectedIndex].value - 1,
      vald);

  if (c.getDate() != vald) {
    alert(xlate['invalidEvtDate']); // translate( 'Invalid Event Date' )
    form.day.focus();
    return false;
  }
  // Repeat Tab enabled, Select them all.
  if (form.rpttype) {
    for (var i = elements['exceptions[]'].length - 1; i >= 0; i--) {
      elements['exceptions[]'].options[i].selected = true;
    }
  }
  // Set byxxxList values for submission.
  for (bydayKey in bydayAr) {
    if (bydayKey == isNumeric(bydayKey))
      bydayStr = bydayStr + ',' + bydayAr[bydayKey];
  }
  if (bydayStr.length > 0)
    elements['bydayList'].value = bydayStr.substr(1);

  // Set bymonthday values for submission.
  for (bymonthdayKey in bymonthdayAr) {
    if (bymonthdayKey == isNumeric(bymonthdayKey))
      bymonthdayStr = bymonthdayStr + ',' + bymonthdayAr[bymonthdayKey];
  }
  if (bymonthdayStr.length > 0)
    elements['bymonthdayList'].value = bymonthdayStr.substr(1);

  // Set bysetpos values for submission.
  for (bysetposKey in bysetposAr) {
    if (bysetposKey == isNumeric(bysetposKey))
      bysetposStr = bysetposStr + ',' + bysetposAr[bysetposKey];
  }
  if (bysetposStr.length > 0)
    elements['bysetposList'].value = bysetposStr.substr(1);

  // Select allusers in selectedPart.
  if (form.elements['selectedPart[]']) {
    var userlist = form.elements['selectedPart[]'];

    for (var i = userlist.length - 1; i >= 0; i--) {
      userlist.options[i].selected = true;
    }
  }
  form.submit();
  return true;
}
