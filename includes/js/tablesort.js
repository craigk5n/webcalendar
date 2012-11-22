// $Id$

// Sort any column of any table with an id, as long as th is inside a thead.
// From the book "The Art and Science of Javascript",
// http://sitepoint.com/books/jsdesign1
function TableSort(id) {
  this.tbl = document.getElementById(id);
  this.lastSortedTh = null;
  if ( this.tbl && this.tbl.nodeName == 'TABLE') {
    var headings = this.tbl.tHead.rows[0].cells;
    for (var i = 0; headings[i]; i++) {
      if ( headings[i].className.match(/asc|dsc/)) {
        this.lastSortedTh = headings[i];
      }
    }
    this.makeSortable();
  }
}
TableSort.prototype.makeSortable = function () {
  var headings = this.tbl.tHead.rows[0].cells;
  for (var i = 0; headings[i]; i++) {
    headings[i].cIdx = i;
    var a = document.createElement('a');

    a.href = '#';
    a.innerHTML = headings[i].innerHTML;
    a.onclick = function (that) {
      return function () {
        that.sortCol(this);
        return false;
      }
    }
    (this);
    headings[i].innerHTML = '';
    headings[i].appendChild(a);
  }
}
TableSort.prototype.sortCol = function (el) {
  /*
   * Get cell data for column that is to be sorted from HTML table
   */
  var aIdx = nIdx = 0,
  alpha = numeric = [],
  rows = this.tbl.rows,
  th = el.parentNode,
  cellIndex = th.cIdx;

  for (var i = 1; rows[i]; i++) {
    var cell = rows[i].cells[cellIndex],
    content = (cell.textContent ? cell.textContent : cell.innerText);
    /*
     * Split data into two separate arrays, one for numeric content and one for
     * everything else (alphabetic). Store both the actual data that will be used
     * for comparison by the sort algorithm (thus the need to parseFloat() the
     * numeric data) as well as a reference to the element's parent row.
     * The row reference will be used after the new order of content is determined
     * in order to actually reorder the HTML table's rows.
     */
    var num = content.replace(/(\$|\,|\s)/g, '');
    if ( parseFloat(num) == num) {
      numeric[nIdx++] = {
        value : Number(num),
        row : rows[i]
      }
    } else {
      alpha[aIdx++] = {
        value : content,
        row : rows[i]
      }
    }
  }
  /*
   * Sort according to direction (ascending or descending)
   */
  var col = [],
  top,
  bottom;
  if ( th.className.match('asc')) {
    top = bubbleSort(alpha, -1);
    bottom = bubbleSort(numeric, -1);
    th.className = th.className.replace(/asc/, 'dsc');
  } else {
    top = bubbleSort(numeric, 1);
    bottom = bubbleSort(alpha, 1);
    if ( th.className.match('dsc')) {
      th.className = th.className.replace(/dsc/, 'asc');
    } else {
      th.className += 'asc';
    }
  }
  /*
   * Clear asc/dsc class names from the last sorted column's th
   * if it isnt the same as the one that was just clicked
   */
  if ( this.lastSortedTh && th != this.lastSortedTh) {
    this.lastSortedTh.className = this.lastSortedTh.className.replace(/dsc|asc/g, '');
  }
  this.lastSortedTh = th;
  /*
   *  Reorder HTML table based on new order of data found in the col array
   */
  col = top.concat(bottom);
  var tBody = this.tbl.tBodies[0];
  for (var i = 0; col[i]; i++) {
    tBody.appendChild(col[i].row);
  }
}
function bubbleSort(arr, dir) {
  // Pre-calculate directional information
  var start,
  end;
  if ( dir === 1) {
    start = 0;
    end = arr.length;
  } else if ( dir === -1) {
    start = arr.length - 1;
    end = -1;
  }
  // Bubble sort: http://en.wikipedia.org/wiki/Bubble_sort
  var unsorted = true;
  while (unsorted) {
    unsorted = false;
    for (var i = start; i != end; i = i + dir) {
      if ( arr[i + dir] && arr[i].value > arr[i + dir].value) {
        var a = arr[i],
        b = arr[i + dir],
        c = a;

        arr[i] = b;
        arr[i + dir] = c;
        unsorted = true;
      }
    }
  }
  return arr;
}
