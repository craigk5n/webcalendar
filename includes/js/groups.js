// $Id$

addLoadListener(function () {
  toggleVisible('grpiframe', 'visible', 'none');
  toggleVisible('tabscontent_groups', 'visible', 'none');

  var hrf = document.getElementsByTagName('a').getAttribute('href');

  for (var i = hrf.length - 1; i >= 0; i--) {
    if (substr(hrf[i], 0, 14) == 'group_edit.php') {
      attachEventListener(hrf[i], 'click', function () {
        toggleVisible('grpiframe');
      });
    }
  }
  targeTo('grpiframe', 'tabscontent_groups');
});
