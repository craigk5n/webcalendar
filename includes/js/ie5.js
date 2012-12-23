// $Id$

// Array.push & Array.splice got left out of IE 5.
Array.prototype.push = function () {
  for (var i = 0; arguments[i]; i++) {
    this[this.length] = arguments[i];
  }
  return arguments[i - 1];
};
Array.prototype.splice = function (a, b) {
  var tmp = [];
  for (var i = a + b; this[i]; i++) {
    tmp[tmp.length] = this[i];
  }
  var rem = [];
  for (i = a; i < a + b; i++) {
    rem[rem.length] = this[i];
  }
  this.length = a;

  for (i = 2; arguments[i]; i++) {
    this[this.length] = arguments[i];
  }
  for (i = 0; tmp[i]; i++) {
    this[this.length] = tmp[i];
  }
  return rem;
};
