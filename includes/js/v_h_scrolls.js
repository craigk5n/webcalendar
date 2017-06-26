// $Id: v_h_scrolls.js,v 1.4 2010/02/21 08:27:49 bbannon Exp $

/* This function needs to be seperate for each page that has scrollers.
function start() {
  // Code each vertical scroller as follows:
  // startScroll( 'id of scroller div', 'scroll content' );
  // As many as needed

  startScroll( 'creds', scrollcontent ); // about.php example
  //startScroll('twoscroll','<p>Yet another scroller!</p>');

  // Start new mq() for each marquee.
  // As many as needed.
  // 'm1'..'m2' are the example ids of the div to scroll.

  // new mq( 'm1' );
  // new mq( 'm2' );
  // mqRotate( mqr ); // must come last
}
*/
var dR = false;
var mqr = [];
var speed = 15;
var step = 2;

window.onload = start;

// Vertical Scroller Javascript
// copyright 24th September 2005, by Stephen Chapman
// permission to use this Javascript on your web page is granted
// provided that all of the code below (as well as these
// comments) is used without any alteration
function objWidth( obj ) {
  if ( obj.offsetWidth )
    return obj.offsetWidth;

  if ( obj.clip )
    return obj.clip.width;

  return 0;
}
function objHeight( obj ) {
  if ( obj.offsetHeight )
    return obj.offsetHeight;

  if ( obj.clip )
    return obj.clip.height;

  return 0;
}
function scrF( i, sH, eH ) {
  var x = parseInt( i.top,10 ) + ( dR ? step : - step );

  if ( dR && x > sH )
    x =- eH;
  else if ( x < 2 - eH )
    x = sH;

  i.top = x + 'px';
}
function startScroll( sN, txt ) {
  var scr = document.getElementById( sN );
  var sW = objWidth( scr ) - 6;
  var sH = objHeight( scr );
  scr.innerHTML = '<div class="Vscroll" id="' + sN + 'in" style="width:' + sW + ';">' + txt + '<\/div>';
  var sTxt = document.getElementById( sN + 'in' );
  var eH = objHeight( sTxt );
  sTxt.style.top = ( dR ? - eH : sH ) + 'px';
  sTxt.style.clip = 'rect( 0,' + sW + 'px,' + eH + 'px,0 )';
  setInterval( function() {
    scrF( sTxt.style, sH, eH, 2 );
  }, 1000 / speed );
}

// Continuous Text Marquee
// copyright 30th September 2009 by Stephen Chapman
// http://javascript.about.com
// permission to use this Javascript on your web page is granted
// provided that all of the code below in this script (including these
// comments) is used without any alteration
function mq( id ) {
  this.mqo = document.getElementById( id );
  var wid = objWidth( this.mqo.getElementsByTagName( 'span' )[0] ) + 5;
  var fulwid = objWidth( this.mqo );
  var txt = this.mqo.getElementsByTagName( 'span' )[0].innerHTML;
  this.mqo.innerHTML = '';
  var heit = this.mqo.style.height;
  this.mqo.onmouseout = function() {
    mqRotate( mqr );
  };
  this.mqo.onmouseover = function() {
    clearTimeout( mqr[0].TO );
  };
  this.mqo.ary = [];
  var maxw = Math.ceil( fulwid / wid ) + 1;
  for ( var i = 0; i < maxw; i++ ) {
    this.mqo.ary[i] = document.createElement( 'div' );
    this.mqo.ary[i].innerHTML = txt;
    this.mqo.ary[i].style.position = 'absolute';
    this.mqo.ary[i].style.left = ( wid * i ) + 'px';
    this.mqo.ary[i].style.width = wid + 'px';
    this.mqo.ary[i].style.height = heit;
    this.mqo.appendChild( this.mqo.ary[i] );
  }
  mqr.push( this.mqo );
}
function mqRotate( mqr ) {
  if ( ! mqr )
    return;

  for ( var j = mqr.length - 1; j > - 1; j-- ) {
    maxa = mqr[j].ary.length;
    for ( var i = 0; i < maxa; i++ ) {
      var x = mqr[j].ary[i].style;
      x.left = ( parseInt( x.left, 10 ) - 1 ) + 'px';
    }
    var y = mqr[j].ary[0].style;
    if ( parseInt( y.left, 10 ) + parseInt( y.width, 10 ) < 0 ) {
      var z = mqr[j].ary.shift();
      z.style.left = ( parseInt( z.style.left, 10 )
        + parseInt( z.style.width, 10 ) * maxa ) + 'px';
      mqr[j].ary.push( z );
    }
  }
  mqr[0].TO = setTimeout( 'mqRotate( mqr )', 10 );
}
