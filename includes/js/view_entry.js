// $Id$

addLoadListener(function () {
    toggleComments();

    attachEventListener(document.getElementById('tglComms'), 'click',
      toggleComments);

    var hrf = document.getElementsByTagName('a').getAttribute('href');

    for (var i = hrf.length - 1; i >= 0; i--) {
      if (substr(hrf[i], 0, 13) == 'add_entry.php') {
        attachEventListener(hrf[i], 'click', function () {
            // translate( 'will add entry to your cal' )
            return confirm(xlate['addCalEntry']);
          });
      }
      if (substr(hrf[i], 0, 17) == 'approve_entry.php') {
        attachEventListener(hrf[i], 'click', function () {
            // translate( 'Approve this entry?' )
            return confirm(xlate['approveEntry']);
          });
      }
      if (substr(hrf[i], 0, 13) == 'del_entry.php') {
        attachEventListener(hrf[i], 'click', function () {
            var tmp = wc_getCookie('del_entry');

            tmp = (tmp == '' ? '' : "\n\n" + tmp);

            wc_setCookie('del_entry', '', 0);
            // translate( 'really delete entry' )
            return confirm(xlate['reallyDeleteEntry'] + tmp);
          });
      }
      if (substr(hrf[i], 0, 10) == 'docdel.php') {
        attachEventListener(hrf[i], 'click', function () {
            return confirm(xlate['reallyDeleteEntry']);
          });
      }
      if (substr(hrf[i], 0, 16) == 'reject_entry.php') {
        attachEventListener(hrf[i], 'click', function () {
            // translate( 'Reject this entry?' )
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
      // translate( 'Hide' ) translate( 'Show' )
       ? xlate['Show'] : xlate['Hide']);
  }
}
