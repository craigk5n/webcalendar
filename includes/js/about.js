// $Id$

linkFile('includes/js/chapman.js');

addLoadListener(function () {
    targeTo('_blank');

    attachEventListener(document.getElementById('ok'), 'click', window.close);

    start();
  });

function start() {
  startScroll('creds', (Modernizr.localstorage ? localStorage[data] : data));
}
