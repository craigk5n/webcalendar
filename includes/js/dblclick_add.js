// $Id$
// Function that will send user to the add event page.  This is typically
// invoked as the ondblclick event handler.
function dblclick_add ( date, name, hour, minute )
{
  minute = ( typeof(minute) != 'undefined' ) ? minute : 0;
  if ( hour ) {
    time = "&hour=" + hour + "&minute=" + minute;
  } else {
    time = "&duration=1440";
  }
  var url = 'edit_entry.php?date=' + date
    + '&defusers=' + name + time;
  window.location.href = url;
}
