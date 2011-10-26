// $Id$

linkFile('includes/js/export_import.js');
linkFile('includes/js/visible.js');

addLoadListener(function () {
    toggle_import();

    attachEventListener(document.getElementById('importtype'), 'change',
      toggle_import);
    attachEventListener(document.getElementById('importform'), 'submit',
      function () {
        return checkExtension();
      });
    attachEventListener(document.getElementsByTagName('img'), 'click',
      function () {
        window.open('help_import.php', 'cal_help',
          'dependent,menubar,scrollbars,height=400,width=400');
      });
  });
