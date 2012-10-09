// $Id$

addLoadListener(function () {
  toggleComments();

  attachEventListener(document.getElementById('tglComms'), 'click', toggleComments);

  for (var i in document.getElementsByTagName('a').getAttribute('href')) {
    if (substr(i, 0, 13) == 'add_entry.php') {
      attachEventListener(i, 'click', function () {
        // translate ( 'will add entry to your cal' )
        return confirm(xlate['addCalEntry']);
      });
    }
    if (substr(i, 0, 17) == 'approve_entry.php') {
      attachEventListener(i, 'click', function () {
        // translate ( 'Approve this entry?' )
        return confirm(xlate['approveEntry']);
      });
    }
    if (substr(i, 0, 13) == 'del_entry.php') {
      attachEventListener(i, 'click', function () {
        var tmp = wc_getCookie('del_entry');

        tmp = (tmp == '' ? '' : "\n\n" + tmp);

        wc_setCookie('del_entry', '', 0);
        // translate ( 'really delete entry' )
        return confirm(xlate['reallyDeleteEntry'] + tmp);
      });
    }
    if (substr(i, 0, 10) == 'docdel.php') {
      attachEventListener(i, 'click', function () {
        return confirm(xlate['reallyDeleteEntry']);
      });
    }
    if (substr(i, 0, 16) == 'reject_entry.php') {
      attachEventListener(i, 'click', function () {
        // translate ( 'Reject this entry?' )
        return confirm(xlate['rejThisEntry']);
      });
    }
  }
});
function toggleComments() {
  var com = document.getElementById('comtext');

  if (com) {
    var vis = com.style.display;

    com.style.display = (vis == 'block' ? 'none' : 'block');
    document.getElementById('tglComms').value = (vis == 'block'
      // translate ( 'Hide' ) translate ( 'Show' )
       ? xlate['Show'] : xlate['Hide']);
  }
}
