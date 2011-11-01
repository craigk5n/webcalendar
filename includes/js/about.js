// $Id$

linkFile('includes/js/v_h_scrolls.js');

addLoadListener(function () {
    targeTo('_blank');

    attachEventListener(document.getElementById('ok'), 'click', window.close);
  });

function start() {
  startScroll('creds', (Modernizr.localstorage ? localStorage[data] : data));
}
 