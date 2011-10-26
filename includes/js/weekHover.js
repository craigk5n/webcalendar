// $Id$

var weeknum = /\bweeknumber\b/;

addLoadListener(function () {
    var links = document.getElementsByTagName('a');

    for (var i = links.length - 1; i >= 0; i--) {
      if (weeknum.test(links[i].className)) {
        if (typeof links[i].onmouseover != 'function') {
          links[i].onmouseover = function () {
            highlightAncestor(this, 'tr');
          };
        } else {
          var mouseover = links[i].onmouseover;
          links[i].onmouseover = function () {
            mouseover();
            highlightAncestor(links[i], 'tr');
          }
        }

        if (typeof links[i].onmouseout != 'function') {
          links[i].onmouseout = function () {
            unhighlightAncestor(this, 'tr');
          };
        } else {
          var mouseout = links[i].onmouseout;
          links[i].onmouseout = function () {
            mouseout();
            unhighlightAncestor(links[i], 'tr');
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
