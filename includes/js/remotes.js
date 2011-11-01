// $Id$

addLoadListener(function () {
  toggleVisible('remotesiframe', 'visible', 'none');
  toggleVisible('tabscontent_remotes', 'visible', 'none');

  var hrf = document.getElementsByTagName('a').getAttribute('href');

  for (var i = hrf.length - 1; i >= 0; i--) {
    if (substr(hrf[i], 0, 16) == 'edit_remotes.php') {
      attachEventListener(hrf[i], 'click', function () {
        toggleVisible('remotesiframe', 'visible', 'block');
      });
    }
  }
  targeTo('remotesiframe', 'tabscontent_remotes');
});
