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

// Double-tap support for Android Chrome (intercepts dblclick for zoom).
// Only enabled on Android; iOS Safari fires dblclick correctly.
(function () {
  if (!/Android/i.test(navigator.userAgent)) return;

  var lastTap = 0;
  var lastTarget = null;
  var TAP_DELAY = 400; // ms

  document.addEventListener('touchend', function (e) {
    // Only handle single-finger taps.
    if (e.changedTouches.length !== 1) return;

    // Walk up from the touch target to find the nearest element with ondblclick.
    var el = e.target;
    while (el && !el.getAttribute('ondblclick')) {
      el = el.parentElement;
    }
    if (!el) return;

    var now = Date.now();
    if (lastTarget === el && (now - lastTap) < TAP_DELAY) {
      e.preventDefault();
      // Fire the ondblclick handler.
      el.ondblclick();
      lastTap = 0;
      lastTarget = null;
    } else {
      lastTap = now;
      lastTarget = el;
    }
  }, { passive: false });
})();
