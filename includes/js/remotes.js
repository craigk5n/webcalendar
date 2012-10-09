// $Id$

addLoadListener(function () {
  toggleVisible('remotesiframe', 'visible', 'none');
  toggleVisible('tabscontent_remotes', 'visible', 'none');

  for (var i in document.getElementsByTagName('a').getAttribute('href')) {
    if (substr(i, 0, 16) == 'edit_remotes.php') {
      attachEventListener(i, 'click', function () {
        toggleVisible('remotesiframe', 'visible', 'block');
      });
    }
  }
  targeTo('remotesiframe', 'tabscontent_remotes');
});
