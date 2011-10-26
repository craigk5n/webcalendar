// $Id$

// See the showTab function in includes/js/visible.js
// for common code shared by all pages using the tabbed GUI.
var tabs = ['', 'users', 'groups', 'nonusers', 'remotes'];

linkFile('includes/js/visible.js');

if (wc_getCookie('grps')) {
  linkFile('includes/css/groups.css','link');
  linkFile('includes/js/groups.js');
}
if (wc_getCookie('nucs')) {
  linkFile('includes/css/nonusers.css','link');
  linkFile('includes/js/nonusers.js');
}
if (wc_getCookie('rems')) {
  linkFile('includes/css/remotes.css','link');
  linkFile('includes/js/remotes.js');
}
addLoadListener(function () {
    showTab(wc_getCookie('currenttab'));

    for (var i in[groups, nonusers, remotes, users]) {
      attachEventListener(document.getElementById('tab_' + i), 'click',
        function () {
          return showTab(i);
        });
    }

    var hrf = document.getElementsByTagName('a').getAttribute('href');

    for (var i = hrf.length - 1; i >= 0; i--) {
      if (substr(hrf[i], 0, 14) == 'edit_user.php') {
        attachEventListener(hrf[i], 'click', function () {
            document.getElementById('useriframe').style.display = ('block'
               ? 'none' : 'block');
          });
      }
    }
    targeTo('useriframe', 'tabscontent_users');

  });
