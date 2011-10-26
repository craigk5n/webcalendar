// $Id$

addLoadListener(function () {
  document.getElementById('grpiframe').style.display = 'none';

    var hrf = document.getElementsByTagName('a').getAttribute('href');
    
    for (var i = hrf.length - 1; i >= 0; i--) {
      if (substr(hrf[i], 0, 14) == 'group_edit.php') {
        attachEventListener(hrf[i], 'click', function () {
            document.getElementById('grpiframe').style.display = ('block'
               ? 'none' : 'block');
          });
      }
    }
    targeTo('grpiframe', 'tabscontent_groups');
  });
