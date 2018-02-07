<?php
/************************************************************************
 * Date selection via DHTML.  We use PHP to provide the translations and
 * a few user preferences (date format, language).  Otherwise, this could
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
 * The CSS ids will be the datename parameter with '_fmt' and '_YMD'
 * appended.
 *
 * Requires:
 *	prototype.js, scriptaculous.js
 *	(Both will be included by print_header unless you override with
 *	the disableAJAX parameter.)
 ************************************************************************/

$ldays_per_month = $days_per_month = [0, 31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31];
$ldays_per_month[2] = 29;

?>

// Month names
var months = [
  <?php
    for ( $i = 0; $i < 12; $i++ ) {
      if ( $i ) echo ", ";
      echo "'" . month_name ( $i ) . "'";
    }
  ?>];
var shortMonths = [
  <?php
    for ( $i = 0; $i < 12; $i++ ) {
      if ( $i ) echo ", ";
      echo "'" . month_name ( $i, 'M' ) . "'";
    }
  ?>];
var weekdays = [
  <?php
    for ( $i = 0; $i < 7; $i++ ) {
      if ( $i ) echo ", ";
      echo "'" . weekday_name ( $i, 'l' ) . "'";
    }
  ?>];
var shortWeekdays = [
  <?php
    for ( $i = 0; $i < 7; $i++ ) {
      if ( $i ) echo ", ";
      echo "'" . weekday_name ( $i, 'D' ) . "'";
    }
  ?>];

// Handle the user clicking somewhere else on the page than the
// date selection box.  This will cancel the date selection.
function handleBackgroundClick ()
{
  // We make it disappear instantly rather than fade just cause it
  // provides quicker feedback in case it was an accident.
  $('dateselOverlay').setStyle ( { display: "none" } );
}

// Bring up the date selection dialog
// The current date setting will be pulled from the
// "xxxxx_YMD".value attribute, where xxxxx is the value of datename.
function datesel_SelectDate ( event, datename )
{
  // Look for the datesel div tag.  If not found, then add it
  // programatically.
  var o = $ ('dateselDiv');
  var curYMD = $(datename + '_YMD').value;
  if ( ! o ) {
    // Add new div tag
    var divElement = document.createElement("div");
    divElement.name = 'dateselOverlay';
    divElement.id = 'dateselOverlay';
    divElement.onclick = handleBackgroundClick;
    datesel_AddElementToBody ( divElement );

    var div2 = document.createElement("div");
    div2.name = 'dateselDiv';
    div2.id = 'dateselDiv';
    divElement.appendChild ( div2 );
    o = div2;
  } else {
    $('dateselOverlay').setStyle ( { display: "block" } );
  }

  // Pull the current date from the YMDId object
  var YMDId = datename + '_YMD';
  if ( ! $(YMDId) ) {
    alert ( "No such object for YMD date '" + YMDId + "'" );
    return;
  }
  var ymd = $(YMDId).value;
  var y = ymd.substr ( 0, 4 );
  var m = ymd.substr ( 4, 2 );
  var d = ymd.substr ( 6, 2 );
  if ( m.substring ( 0, 1 ) == '0' )
    m = m.substring ( 1 );
  if ( d.substring ( 0, 1 ) == '0' )
    d = d.substring ( 1 );

  // Update table contents
  datesel_UpdateDisplay ( o, datename, y, m, d, curYMD );

  // Move date popup to just below where the user clicked the mouse.
  var xpos = event.clientX - 100;
  var ypos = event.clientY + 15;
  var style = { position: "absolute", left: xpos + "px", top: ypos + "px", width: "200px", height: "200px", display: "block" };
  o.setStyle(style);
}

function datesel_goto ( event, datename, year, month, day, curYMD )
{
  datesel_UpdateDisplay ( $('dateselDiv'), datename, year, month, day, curYMD );
  Event.stop ( event );  
}

// Handle the user selecting a date.
// Update the calling HTML elements to reflect the new date
// and close/hide the date selection.
function datesel_DateSelected ( event, datename, year, month, day )
{
  var fmtEle = datename + '_fmt';
  var ymdEle = datename + '_YMD';

  // turn selected date green as the table fades away
  var style = { background: "#00ff00" };
  $('dom_' + day ).setStyle ( style );

  var ymdVal = "" + year + ( month < 10 ? "0" : "" ) + month +
        ( day < 10 ? "0": "" ) + day;
  $(ymdEle).value = ymdVal;
  var fmtDate = datesel_FormatDate ( ymdVal, true );
  $(fmtEle).innerHTML = fmtDate;
  var o = $(fmtEle);

  // Hide date selection table
  //$('dateselDiv').style.display = 'none';
  Effect.Fade('dateselOverlay', { duration: 0.5 });
}

function datesel_Cancel ()
{
  Effect.Fade('dateselOverlay', { duration: 0.5 });
}

function datesel_UpdateDisplay ( div, datename, year, month, day, curYMD )
{
  var daysPerMonth = [ <?php echo implode ( ", ", $days_per_month ); ?> ];
  var leapDaysPerMonth = [ <?php echo implode ( ", ", $ldays_per_month ); ?> ];

  year = parseInt(year);
  month = parseInt(month)
  day = parseInt(day);

  // Also parse the currently selected date
  curYMD = "" + curYMD;
  var cury = curYMD.substr ( 0, 4 );
  var curm = curYMD.substr ( 4, 2 );
  var curd = curYMD.substr ( 6, 2 );
  if ( curm.substring ( 0, 1 ) == '0' )
    curm = curm.substring ( 1 );
  if ( curd.substring ( 0, 1 ) == '0' )
    curd = curd.substring ( 1 );

  var nextYear;
  var nextMonth = month + 1;
  if ( nextMonth > 12 ) {
    nextMonth = 1;
    nextYear = year + 1;
  } else {
    nextYear = year;
  }
  var nextDay = day;

  var prevYear;
  var prevMonth = month - 1;
  if ( prevMonth == 0 ) {
    prevMonth = 12;
    prevYear = year - 1;
  } else {
    prevYear = year;
  }
  var prevDay = day;

  var ret = 
    '<div style="width: 100%">' +
    '<span class="clickable" onclick="datesel_Cancel()"/><img id="cancelImage" src="images/cancel.png" alt="x" /></span></div>' +
    '<table class="dateselTable">' +
    '<tr><td colspan="7" id="dateselMonthName">' +
    '<img src="images/combo-prev.png" class="alignleft clickable" ' +
    'onclick="datesel_goto(event,' + "'" + datename + "'" + ',' + prevYear + ',' +
    prevMonth + ',' + prevDay + ',' + curYMD + ')" />' + months[month-1] + ' ' + year +
    '<img src="images/combo-next.png" class="alignright clickable" ' +
    'onclick="datesel_goto(event,' + "'" + datename + "'" + ',' + nextYear + ',' +
    nextMonth + ',' + nextDay + ',' + curYMD + ')" />' + '</td></tr>';

  ret += '<tr>';
  for ( var w = 0; w < 7; w++ ) {
    ret += '<td class="wdayname">' + shortWeekdays[w] + '</td>';
  }
  ret += '</tr>';

  var d = new Date();
  var today = new Date();
  d.setYear ( year );
  d.setMonth ( month - 1 );
  d.setDate ( 1 );

  var wday = d.getDay();
  var startDay = 1 - wday;
  var daysThisMonth = ( year % 4 == 0 ) ? leapDaysPerMonth[month] :
    daysPerMonth[month];

  for ( var i = startDay, j = 0; i <= daysThisMonth || j % 7 != 0; i++, j++ ) 
{
    if ( j % 7 == 0 ) ret += "<tr>";
    if ( i < 1 ) {
      ret += "<td class=\"othermonth\">&nbsp;</td>\n";
    } else if ( i > daysThisMonth ) {
      ret += "<td class=\"othermonth\">&nbsp;</td>\n";
    } else {
      var key = "" + year + ( month < 10 ? "0" : "" ) + month +
        ( i < 10 ? "0": "" ) + i;
      var cl = 'clickable fakebutton';
      if ( year == cury && month == curm && i == curd )
        cl += ' selected';
      else if ( year == today.getYear () + 1900 &&
        month == ( today.getMonth() + 1 ) &&
        i == today.getDate () )
        cl += ' today';
      ret += "<td id=\"dom_" + i + "\" class=\"" + cl +
        "\" onclick=\"datesel_DateSelected(event,'" +
        datename + "'," + year + "," + month + "," + i +
        ")\">" + i + "</td>";
    }
    if ( j % 7 == 6 ) ret += "</tr>\n";
  }
  ret += "</table>\n";
  $(div).innerHTML = ret;
}

// This function mimics the date_to_str PHP function found in
// includes/functions.php.
function datesel_FormatDate ( dateStr, showWeekday )
{
  var fmt = '<?php echo $DATE_FORMAT;?>';

  var y = dateStr.substr ( 0, 4 );
  var m = dateStr.substr ( 4, 2 );
  var d = dateStr.substr ( 6, 2 );

  var ret = fmt;
  ret = ret.replace ( /__dd__/, d );
  ret = ret.replace ( /__j__/, d );
  ret = ret.replace ( /__mm__/, m );
  ret = ret.replace ( /__mon__/, shortMonths[m-1] );
  ret = ret.replace ( /__month__/, months[m-1] );
  ret = ret.replace ( /__n__/, m );
  ret = ret.replace ( /__yy__/, y % 100 );
  ret = ret.replace ( /__yyyy__/, y );

  var w = '';
  if ( showWeekday ) {
    var myD = new Date();
    myD.setYear ( y );
    myD.setMonth ( m - 1 );
    myD.setDate ( d );
    wday = myD.getDay();
    w = shortWeekdays[wday] + ', ';
  }

  return w + ret;
}

// Add a new HTML element as the last element of the body tag.
function datesel_AddElementToBody(el) {
  eval("document.getElementsByTagName('body')[0].appendChild(el)");
}

// Update date selection object (generated from the PHP datesel_Print
// function) to have a different date.
// This is useful if a div tag is re-used and each
// use has a different date setting.
function datsesel_UpdateCurrentDate ( datename, newYMD )
{
  var id = 'dateselIcon_' + datename;
  $(id).click = new function () {
    datesel_SelectDate(event, datename, newYMD);
  }
}

// end of datesel
