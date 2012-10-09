// $Id$

var weeknum = /\bweeknumber\b/;

addLoadListener(function () {
  var links = document.getElementsByTagName('a');

  for (var i in links) {
    if (weeknum.test(i.className)) {
      if (typeof i.onmouseover != 'function') {
        i.onmouseover = function () {
          highlightAncestor(this, 'tr');
        };
      } else {
        var mouseover = i.onmouseover;
        i.onmouseover = function () {
          mouseover();
          highlightAncestor(i, 'tr');
        }
      }

      if (typeof i.onmouseout != 'function') {
        i.onmouseout = function () {
          unhighlightAncestor(this, 'tr');
        };
      } else {
        var mouseout = i.onmouseout;
        i.onmouseout = function () {
          mouseout();
          unhighlightAncestor(i, 'tr');
        }
      }
    }
  }
});

function highlightAncestor(ele, ancestorTag) {
  var ancestor = ele.parentNode;
  while (ancestor.tagName.toLowerCase() != ancestorTag) {
    ancestor = ancestor.parentNode;
  }
  if (ancestor.className.search(weeknum) == -1) {
    ancestor.className += ' highlight';
  }
}

function unhighlightAncestor(ele, ancestorTag) {
  var ancestor = ele.parentNode;
  while (ancestor.tagName.toLowerCase() != ancestorTag) {
    ancestor = ancestor.parentNode;
  }
  while (ancestor.className.search(weeknum) != -1) {
    ancestor.className = ancestor.className.replace(weeknum, '');
  }
}
