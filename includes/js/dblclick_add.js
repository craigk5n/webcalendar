/**
 * @description Send user to the add event page. Typically invoked as the ondblclick event handler.
 * @author Craig Knudsen
 * @date 2023-10-05
 * @param {int} date
 * @param {string} name
 * @param {int} hour
 * @param {int} minute
 */
function dblclick_add ( date, name, hour, minute ) {
  window.location.href = 'edit_entry.php?date=' + date + '&defusers=' + name
    + ( hour ? '&hour=' + hour + '&minute='
      + ( typeof ( minute ) !== 'undefined' ? minute : 0 ) : '' );
}
