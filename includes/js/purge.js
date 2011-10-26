// $Id$

linkFile('includes/js/visible.js');

addLoadListener(function () {
    attachEventListener(document.getElementById('backBtn'), 'click', history.back);
  });
