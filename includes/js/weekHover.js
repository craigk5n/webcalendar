function highlightAncestor(ele, ancestorTag) {
  var ancestor = ele.parentNode;
  while (ancestor.tagName.toLowerCase() != ancestorTag.toLowerCase()) {
    ancestor = ancestor.parentNode;
  }
  if (ancestor.className.search(/\bhighlight\b/) == -1) {
    ancestor.className += " highlight";
  }
}

function unhighlightAncestor(ele, ancestorTag) {
  var ancestor = ele.parentNode;
  while (ancestor.tagName.toLowerCase() != ancestorTag.toLowerCase()) {
    ancestor = ancestor.parentNode;
  }
  while (ancestor.className.search(/\bhighlight\b/) != -1) {
    ancestor.className = ancestor.className.replace(/\bhighlight\b/, "");
  }
}

function addLoadEvent(func) {
  var oldonload = window.onload;
  if (typeof window.onload != 'function') {
    window.onload = func;
  } else {
    window.onload = function() {
      oldonload();
      func();
    }
  }
}

function setupHovers() {
  var links = document.getElementsByTagName('A');
  var weeknum = /\bweeknumber\b/;

  for (var i = 0; i < links.length; i++) {
    if (weeknum.test(links[i].className)) {
      if (typeof links[i].onmouseover != 'function') {
        links[i].onmouseover = function() { highlightAncestor(this, 'TR'); };
      } else {
        var mouseover = links[i].onmouseover;
        links[i].onmouseover = function() {
          mouseover();
          highlightAncestor(links[i], 'TR');
        }
      }

      if (typeof links[i].onmouseout != 'function') {
        links[i].onmouseout = function() { unhighlightAncestor(this, 'TR'); };
      } else {
        var mouseout = links[i].onmouseout;
        links[i].onmouseout = function() {
          mouseout();
          highlightAncestor(links[i], 'TR');
        }
      }
    }
  }
}

addLoadEvent(setupHovers);
