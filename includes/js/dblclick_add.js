
// Function that will send user to the add event page.
// This is typically invoked as the ondblclick event handler.
function dblclick_add( date, name, hour, minute ) {
  window.location.href = 'edit_entry.php?date=' + date + '&defusers=' + name
    + ( hour ? '&hour=' + hour + '&minute='
      + ( typeof( minute ) != 'undefined' ? minute : 0 ) : '' );
}
