// $Id: ie5.js,v 1.1 2008/12/14 23:37:46 bbannon Exp $

// Array.push & Array.splice got left out of IE 5.
Array.prototype.push = function() {
  for ( var i = 0; i < arguments.length; i++ ) {
    this[this.length] = arguments[i];
  }
  return arguments[i - 1];
};

Array.prototype.splice = function( a, b ) {
  var tmp = [];
  for ( var i = a + b; i < this.length; i++ ) {
    tmp[tmp.length] = this[i];
  }

  var rem = [];
  for ( i = a; i < a + b; i++ ) {
    rem[rem.length] = this[i];
  }

  this.length = a;

  for ( i = 2; i < arguments.length; i++ ) {
    this[this.length] = arguments[i];
  }

  for ( i = 0; i < tmp.length; i++ ) {
    this[this.length] = tmp[i];
  }

  return rem;
};
