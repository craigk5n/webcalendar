// $Id$
// Because I don't like half-way. If I'm going to move js to external files,
// then I'm going to move all I can figure out how to do.

addLoadListener(function () {
    attachEventListener(document.getElementById('delGrpEntry'), 'click', function () {
        return confirm(xlate['reallyDeleteEntry']); // translate( 'really delete entry' )
      });
  });
