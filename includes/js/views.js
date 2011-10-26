// $Id$

linkFile('includes/js/popups.js');
linkFile('includes/js/visible.js');
linkFile('includes/js/weekHover.js');

addLoadListener(function () {
    document.getElementById('viewiframe').style.display = 'none';

    var hrf = document.getElementsByTagName('a').getAttribute('href');

    for (var i = hrf.length - 1; i >= 0; i--) {
      if (substr(hrf[i], 0, 14) == 'views_edit.php') {
        attachEventListener(hrf[i], 'click', function () {
            document.getElementById('viewiframe').style.display = ('block'
               ? 'none' : 'block');
          });
      }
    }
    targeTo('viewiframe', 'tabscontent_views');

    attachEventListener(document.getElementById('tab_views'), 'click',
      function () {
        return showTab('views');
      });
  });
