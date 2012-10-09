// $Id$

addLoadListener(function () {
  toggleVisible('grpiframe', 'visible', 'none');
  toggleVisible('tabscontent_groups', 'visible', 'none');

  for (var i in document.getElementsByTagName('a').getAttribute('href')) {
    if (substr(i, 0, 14) == 'group_edit.php') {
      attachEventListener(i, 'click', function () {
        toggleVisible('grpiframe');
      });
    }
  }
  targeTo('grpiframe', 'tabscontent_groups');
});
