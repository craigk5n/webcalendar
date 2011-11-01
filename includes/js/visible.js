// $Id$

function showTab(name) {
  if (!document.getElementById) {
    return true;
  }
  var div,
  tab,
  tname;

  for (var i = tabs.length - 1; i >= 0; i--) {
    tname = tabs[i];
    tab = document.getElementById('tab_' + tname);
    // We might call without parameter, if so display tabfor div.
    if (tab && !name) {
      if (tab.className == 'tabfor') {
        name = tname;
      }
    } else if (tab) {
      tab.className = (tname == name ? 'tabfor' : 'tabbak');
    }
    div = document.getElementById('tabscontent_' + tname);
    if (div) {
      div.style.display = (tname == name ? 'block' : 'none');
    }
  }
  return false;
}
function visByClass(classname, state) {
  var inc = 0,
  alltags = document.getElementsByTagName('*');

  for (var i = alltags.length - 1; i >= 0; i--) {
    var str = alltags[i].className;
    if (str && str.match(classname)) {
      alltags[i].style.display = (state == 'hide' ? 'none' : '');
    }
  }
}
function getScrollingPosition() {
  var position = [0, 0];

  if (typeof window.pageYOffset != 'undefined') {
    position = [
      window.pageXOffset,
      window.pageYOffset
    ];
  } else if (typeof document.documentElement.scrollTop != 'undefined'
     && document.documentElement.scrollTop > 0) {
    position = [
      document.documentElement.scrollLeft,
      document.documentElement.scrollTop
    ];
  } else if (typeof document.body.scrollTop != 'undefined') {
    position = [
      document.body.scrollLeft,
      document.body.scrollTop
    ];
  }
  return position;
}
//These common function are here because all the files that use them
//also use visibility functions.
function selectDate(day, month, year, current, evt, form) {
  // Get currently selected day/month/year.
  monthobj = eval('document.' + form.id + '.' + month);
  curmonth = monthobj.options[monthobj.selectedIndex].value;
  yearobj = eval('document.' + form.id + '.' + year);
  evt = (evt ? evt : window.event);

  var scrollingPosition = getScrollingPosition();

  if (typeof evt.pageX != 'undefined' && typeof evt.x != 'undefined') {
    mX = evt.pageX;
    mY = self.screen.availHeight - evt.pageY;
  } else {
    mX = evt.clientX + scrollingPosition[0];
    mY = evt.clientY + scrollingPosition[1];
  }
  mX += 40;
  var colorWindow =
    window.open('datesel.php?form=' + form.id + '&fday=' + day
       + '&fmonth=' + month + '&fyear=' + year + '&date='
       + yearobj.options[yearobj.selectedIndex].value
       + (curmonth < 10 ? '0' : '') + curmonth + '01', 'DateSelection',
      'width=300,height=180,scrollbars=no,toolbar=no,screenx=' + mX
       + ',screeny=' + mY + ',left=' + mX + ',top=' + mY);
}
function selectColor(color, evt) {
  var mX = (document.getElementById ? evt.clientX : evt.pageX) + 40;
  var colorWindow = window.open('colors.php?color=' + color, 'ColorSelection',
       + 'width=390,height=365,scrollbars=0,left=' + mX
       + ',top=100,screenx=' + mX + ',screeny=100');
}
function valid_color(str) {
  return /^#[0-9a-fA-F]{3}$|^#[0-9a-fA-F]{6}$/.test(str);
}
// Updates the background-color of a table cell
// Parameters:
//   input - element containing the new color value
//   target - id of sample
function updateColor(input, target) {
  var // The cell to be updated.
  colorCell = document.getElementById(target).style.backgroundColor,
  color = input.value; // The new color.

  if (!valid_color(color)) {
    // Color specified is invalid; use black instead.
    colorCell = '#000000';
    input.select();
    input.focus();
    alert(xlate['invalidColor']); // translate( 'Invalid Color' )
  } else {
    colorCell = color;
  }
}
function toggle_datefields(name, ele) {
  toggleVisible(name, (document.getElementById(ele.id).checked ? 'hidden' : 'visible'));
}
function callEdit() {
  editwin = window.open('edit_entry.php', 'edit_entry',
      'width=600,height=500,resizable=yes,scrollbars=no');
}
