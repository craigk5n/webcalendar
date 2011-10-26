// $Id$

linkFile('includes/js/export_import.js');
linkFile('includes/js/visible.js');

addLoadListener(function () {
    attachEventListener(document.getElementById('exportall'), 'click',
      function () {
        toggle_datefields('dateArea', this);
      });
    attachEventListener(document.getElementById('exformat'), 'change',
      toggel_catfilter);
  });
