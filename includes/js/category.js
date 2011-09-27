// $Id$

linkFile('includes/js/visible.js');

addLoadListener(function () {
    attachEventListener(document.getElementById('searchIcon'), 'click',
      function () {
        window.open('icons.php', 'icons',
          'dependent,menubar=no,scrollbars=n0,height=300,width=400,'
           + 'outerHeight=320,outerWidth=420');
      });
    attachEventListener(document.getElementById('deleIcon'), 'click',
      function () { // translate( 'reallyDeleteEntry' )
        return confirm(xlate['reallyDeleteEntry']);
      });
  });
