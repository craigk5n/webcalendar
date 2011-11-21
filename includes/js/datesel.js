/************************************************************************
 * $Id$
 *
 * Date selection via DHTML. We use PHP to provide the translations and
 * a few user preferences (date format, language). Otherwise, this could
 * be just javascript... in which case we could have re-used any number of
 * open source alternatives 8-)
 *
 * There is a corresponding datesel_Print function in functions.php that
 * will generate the necessary HTML.
 *
 * This will create two HTML elements:
 * - a form element of type hidden that will hold the date value in
 *   YYYYMMDD format
 * - a span element that will display the nicely formatted date to the user
 * The CSS ids will be the datename parameter with '_fmt' and '_YMD' appended.
 *
 * Requires:
 *  prototype.js, scriptaculous.js
 *  (Both will be included by print_header
 *    unless you override with the disableAJAX parameter.)
 ************************************************************************/

 function datesel_Cancel() {
  Effect.Fade('dateselOverlay', {
    duration: 0.5
  });
}
// Handle the user selecting a date.
// Update the calling HTML elements to reflect the new date
// and close/hide the date selection.
function datesel_DateSelected(event, datename, year, month, day) {
  var fmtEle = datename + '_fmt',
  ymdEle = datename + '_YMD'

    // Turn selected date green as the table fades away.
    $('dom_' + day).setStyle({
      background: '#00ff00'
    });

  var ymdVal = year + (month > 9 ? '' : '0') + month + (day > 9 ? '' : '0') + day;

  $(ymdEle).value = ymdVal;

  var fmtDate = datesel_FormatDate(ymdVal, true);

  $(fmtEle).innerHTML = fmtDate;

  var o = $(fmtEle);

  // Hide date selection table
  Effect.Fade('dateselOverlay', {
    duration: 0.5
  });
}

// This function mimics the date_to_str PHP function found in
// "includes/functions.php".
function datesel_FormatDate(dateStr, showWeekday) {
  var date = new Date(dateStr);

  var ret = (showWeekday ? 'D, ' : '') + dateFmt.replace(/__dd__/, 'd');
  ret = ret.replace(/__j__/, 'j');
  ret = ret.replace(/__mm__/, 'm');
  ret = ret.replace(/__mon__/, 'M');
  ret = ret.replace(/__month__/, 'F');
  ret = ret.replace(/__n__/, 'n');
  ret = ret.replace(/__yy__/, 'y');

  return date.format(ret.replace(/__yyyy__/, 'Y'));
}

function datesel_goto(event, datename, year, month, day, curYMD) {
  datesel_UpdateDisplay($('dateselDiv'), datename, year, month, day, curYMD);
  Event.stop(event);
}

// Bring up the date selection dialog.
// The current date setting will be pulled from the
// "xxxxx_YMD".value attribute, where xxxxx is the value of datename.
function datesel_SelectDate(event, datename) {
  // Look for the datesel div tag. If not found, then add it programatically.
  var o = $('dateselDiv');
  var curYMD = $(datename + '_YMD').value;

  if (!o) {
    // Add new div tag
    var div2 = divElement = document.createElement('div');

    divElement.setAttribute('id', '');
    divElement.setAttribute('name', '');
    divElement.setAttribute('onclick', '');
    divElement.id = 'dateselOverlay';
    divElement.name = 'dateselOverlay';
    divElement.onclick = handleBackgroundClick;
    document.getElementsByTagName('body')[0].appendChild(divElement)

    div2.setAttribute('id', '');
    div2.setAttribute('name', '');
    div2.id = 'dateselDiv';
    div2.name = 'dateselDiv';
    document.getElementsByTagName('body')[0].appendChild(div2);
    o = div2;
  } else {
    $('dateselOverlay').setStyle({
      display: 'block'
    });
  }

  // Pull the current date from the YMDId object
  var YMDId = datename + '_YMD';

  if (!$(YMDId)) {
    // translate('No such object for YMD XXX' )
    alert(xlate['noYMDXXX'].replace(/XXX/, YMDId));
    return;
  }
  var ymd = $(YMDId).value;

  var y = ymd.substr(0, 4);
  var m = ymd.substr(4, 2);
  var d = ymd.substr(6, 2);

  if (m.substring(0, 1) == '0')
    m = m.substring(1);

  if (d.substring(0, 1) == '0')
    d = d.substring(1);

  // Update table contents
  datesel_UpdateDisplay(o, datename, y, m, d, curYMD);

  // Move date popup to just below where the user clicked the mouse.
  var xpos = event.clientX - 100,
  ypos = event.clientY + 15;

  o.setStyle({
    position: 'absolute',
    left: xpos + 'px',
    top: ypos + 'px',
    width: '200px',
    height: '200px',
    display: 'block'
  });
}

// Update date selection object (generated from the PHP datesel_Print
// function) to have a different date.
// This is useful if a div tag is re-used and each
// use has a different date setting.
function datsesel_UpdateCurrentDate(datename, newYMD) {
  var id = 'dateselIcon_' + datename;

  $(id).click = new function () {
    datesel_SelectDate(event, datename, newYMD);
  }
}

// Handle the user clicking somewhere other than the date selection box.
// This will cancel the date selection.
function handleBackgroundClick() {
  // We make it disappear instantly rather than fade just cause it
  // provides quicker feedback in case it was an accident.
  $('dateselOverlay').setStyle({
    display: 'none'
  });
}
function sendDate(date) {
  var d = wc_getCookie('fday');
  var m = wc_getCookie('fmonth');
  var y = wc_getCookie('fyear');
  var f = wc_getCookie('fform');
  var year = date.substring(0, 4);
  var wodf = window.opener.document.f,
  sy = wodf.y;

  wodf.d.selectedIndex = date.substring(6, 8) - 1;
  wodf.m.selectedIndex = date.substring(4, 6) - 1;

  for (var i = sy.length - 1; i >= 0; i--) {
    if (sy.options[i].value == year) {
      sy.selectedIndex = i;
    }
  }
  window.close();
}
function datesel_UpdateDisplay(div, datename, year, month, day, curYMD) {
  year = parseInt(year);
  month = parseInt(month);
  day = parseInt(day);

  // Also parse the currently selected date
  curYMD = '' + curYMD;

  var cury = curYMD.substr(0, 4);
  var curm = curYMD.substr(4, 2);
  var curd = curYMD.substr(6, 2);

  if (curm.substring(0, 1) == '0')
    curm = curm.substring(1);

  if (curd.substring(0, 1) == '0')
    curd = curd.substring(1);

  var nextYear,
  nextMonth = month + 1;

  if (nextMonth > 12) {
    nextMonth = 1;
    nextYear = year + 1;
  } else {
    nextYear = year;
  }
  var nextDay = day,
  prevYear,
  prevMonth = month - 1;

  if (prevMonth == 0) {
    prevMonth = 12;
    prevYear = year - 1;
  } else {
    prevYear = year;
  }
  var prevDay = day,
  nl = "\n",
  ret = '<div><img src="images/cancel.png" id="cancelImage" alt="x"></div>' + nl
     + '<table summary="date selection">' + nl + '<tr>' + nl
     + '<td colspan="7" id="dateselMonthName"><img src="images/combo-prev.png">'
     + months[month - 1] + ' ' + year + '<img src="images/combo-next.png></td>' + nl
     + '</tr>' + nl + '<tr class="wdayname">';

  for (var w = 0; w < 7; w++) {
    ret += nl + '<td>' + shortWeekdays[w] + '</td>';
  }
  ret += nl + '</tr>';

  var d = today = new Date();

  d.setYear(year);
  d.setMonth(month - 1);
  d.setDate(1);

  var daysThisMonth = _daysInMonth(month, year);
  var wday = d.getDay();
  var startDay = 1 - wday;

  for (i = startDay, j = 0; i <= daysThisMonth || j % 7 != 0; i++, j++) {
    if (j % 7 == 0)
      ret += nl + '<tr>';

    if (i < 1 || i > daysThisMonth) {
      ret += nl + '<td class="othermonth">&nbsp;</td>';
    } else {
      var key = year + (month > 9 ? '' : '0') + month + (i > 9 ? '' : '0') + i;
      var cl = 'clickable fakebutton';

      if (year == cury && month == curm && i == curd)
        cl += ' selected';
      else if (year == today.getYear() + 1900 && month == (today.getMonth() + 1)
         && i == today.getDate())
        cl += ' today';

      ret += nl + '<td id="dom_' + i + '" class="' + cl + '">' + i + '</td>';
    }
    if (j % 7 == 6)
      ret += nl + '</tr>';
  }
  $(div).innerHTML = ret + nl + '</table>';

  attachEventListener(document.getElementById('cancelImage'), 'click', function () {
    Effect.Fade('dateselOverlay', {
      duration: 0.5
    });
  });

  var imgs = document.getElementById('dateselMonthName').getElementsByTagName('img');
  attachEventListener(imgs[0], 'click', function () {
    datesel_goto(event, 'datename', prevYear, prevMonth, prevDay, curYMD);
  });
  attachEventListener(imgs[1], 'click', function () {
    datesel_goto(event, 'datename', nextYear, nextMonth, nextDay, curYMD);
  });
  for (var i = daysThisMonth - 1; i >= 0; i--) {
    attachEventListener(document.getElementById('dom_' + i), 'click', function () {
      datesel_DateSelected(event, 'datename', year, month, i)
    });
  }
}

// end of datesel.js
