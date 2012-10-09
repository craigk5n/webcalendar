// $Id$

linkFile('includes/js/popups.js');
linkFile('includes/js/visible.js');
linkFile('includes/js/weekHover.js');

addLoadListener(function () {
  document.getElementById('viewiframe').style.display = 'none';

  for (var i in document.getElementsByTagName('a').getAttribute('href')) {
    if (substr(i, 0, 14) == 'views_edit.php') {
      attachEventListener(i, 'click', function () {
        document.getElementById('viewiframe').style.display = ('block'
           ? 'none' : 'block');
      });
    }
  }
  targeTo('viewiframe', 'tabscontent_views');

  attachEventListener(document.getElementById('tab_views'), 'click', function () {
    return showTab('views');
  });
});
