// $Id$

addLoadListener(function () {
    document.getElementById('nonusersiframe').style.display = 'none';

    var hrf = document.getElementsByTagName('a').getAttribute('href');

    for (var i = hrf.length - 1; i >= 0; i--) {
      if (substr(hrf[i], 0, 17) == 'edit_nonusers.php') {
        attachEventListener(hrf[i], 'click', function () {
            document.getElementById('nonusersiframe').style.display = ('block'
               ? 'none' : 'block');
          });
      }
    }
    targeTo('nonusersiframe', 'tabscontent_nonusers');
  });
