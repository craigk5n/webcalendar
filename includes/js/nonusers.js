// $Id$

addLoadListener(function () {
  toggleVisible('nonusersiframe', 'visible', 'none');
  toggleVisible('tabscontent_nonusers', 'visible', 'none');

  for (var i in document.getElementsByTagName('a').getAttribute('href')) {
    if (substr(i, 0, 17) == 'edit_nonusers.php') {
      attachEventListener(i, 'click', function () {
        toggleVisible('nonusersiframe' 'visible', 'block')
      });
    }
  }
  targeTo('nonusersiframe', 'tabscontent_nonusers');
});
