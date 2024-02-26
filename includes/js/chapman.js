/* $Id: chapman.js,v 1.1 2013/01/30 10:02:28 bbannon Exp $ */

var dR, isDST, mqr, speed, step, tzOffSet, uri;

/*
 * All below copyright by Stephen Chapman at various times.
 * Modified by Bruce Bannon to combine files;
 * - includes/js/dateformat.js
 * - includes/js/geturl.js
 * - includes/js/v_h_scrolls.js
 * - (and others that were not yet part of WebCalendar)
 * consolidating duplicate code,
 * and incorporating WebCalendar translations.
 *
 * Note: I had links to Stephen's articles for each function.
 *       I've removed the broken ones.
 */
/**
 * Date Format Method
 *
 * When used in conjunction with "includes/js/translate.js.php"
 * we can get the translated values for:
 *  - Month and Weekday names,
 *  - AM/am/PM/pm,
 *  - numeric ordinals and
 *  - daylight saving time or standard.
 */
Date.prototype.getDayName = function (n) {
  var d;

  d = (n === 'D'
     ? (typeof week_names !== NULL
       ? week_names
       : ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'])
     : (typeof weekday_names !== NULL
       ? weekday_names
       : ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday']));

  return d[this.getDay()];
};
Date.prototype.getDOY = function () {
  var onejan;

  onejan = new Date(this.getFullYear(), 0, 1);

  if (onejan.getDST()) {
    onejan.addHours(1);
  }
  if (this.getDST()) {
    onejan.addHours(-1);
  }
  return Math.ceil((this - onejan + 1) / 86400000);
};
Date.prototype.getDST = function () {
  return this.getTimezoneOffset() < this.getStdTimezoneOffset();
};
Date.prototype.getISOWeek = function () {
  var onejan, wk;

  onejan = new Date(this.getISOYear(), 0, 1);
  wk = Math.ceil((((this - onejan) / 86400000) + onejan.getMDay() + 1) / 7);

  if (onejan.getMDay() > 3) {
    wk--;
  }
  return wk;
};
Date.prototype.getISOYear = function () {
  return new Date(this.getFullYear(), this.getMonth(), this.getDate() + 3 - this.getMDay()).getFullYear();
};
Date.prototype.getJulian = function () {
  return Math.floor((this / 86400000) - (this.getTimezoneOffset() / 1440) + 2440587.5);
};
Date.prototype.getMDay = function () {
  return (this.getDay() + 6) % 7;
};
Date.prototype.getMonthName = function (n) {
  var m;

  m = (n === 'M'
     ? (typeof monthshort_names !== NULL
       ? monthshort_names
       : ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'])
     : (typeof month_names !== NULL
       ? month_names
       : ['January', 'February', 'March', 'April', 'May_', 'June', 'July', 'August', 'September', 'October', 'November', 'December']));

  return m[this.getMonth()];
};
Date.prototype.getOrdinal = function () {
  var rt;

  // If I was sure that everyone used only 2 characters for these,
  // translate ( 'stndrdth' ) would work. Like this:
  // var x=(xlate['stndrdth']!=='undefined'?xlate['stndrdth']:'stndrdth');
  // case 3:return x.substr (4,2);
  switch (this.getDate()) {
  case 1:
  case 21:
  case 31:
    // translate ( 'st' ) as in 1st
    rt = (xlate['st'] !== NULL ? xlate['st'] : 'st');
    break;
  case 2:
  case 22:
    //  translate ( 'nd' ) as in 2nd
    rt = (xlate['nd'] !== NULL ? xlate['nd'] : 'nd');
    break;
  case 3:
  case 23:
    //  translate ( 'rd' ) as in 3rd
    rt = (xlate['rd'] !== NULL ? xlate['rd'] : 'rd');
    break;
  default:
    //  translate ( 'th' ) as in  4th
    rt = (xlate['th'] !== NULL ? xlate['th'] : 'th');
  }
  return rt;
};
Date.prototype.getStdTimezoneOffset = function () {
  var jan, jul;

  jan = new Date(this.getFullYear(), 0, 1);
  jul = new Date(this.getFullYear(), 6, 1);

  return Math.max(jan.getTimezoneOffset(), jul.getTimezoneOffset());
};
Date.prototype.getSwatch = function () {
  return Math.floor((((this.getUTCHours() + 1) % 24) + this.getUTCMinutes() / 60 + this.getUTCSeconds() / 3600) * 1000 / 24);
};
Date.prototype.getWeek = function () {
  var onejan;

  onejan = new Date(this.getFullYear(), 0, 1);

  return Math.ceil((((this - onejan) / 86400000) + onejan.getDay() + 1) / 7);
};
// Port of PHP date formatting.
Date.prototype.format = function (f) {
  var A, d, fmt, h, i, m, res, s, t, w, y;

  fmt = f.split('');
  res = '';
  for (i = 0; fmt[i]; i++) {
    switch (fmt[i]) {
    case '^': // Since backslash is already used by javasccript.
      res += fmt[++i];
      break;
    case 'A':
    case 'a':
      // Befoe or after noon?
      A = (fmt[i] === 'A');
      // If I was sure that everyone used only 2 characters for these,
      // translate ( 'AMPMampm' ) would work better. Like this:
      // var x=(xlate['AMPMampm']!=='undefined'?xlate['AMPMampm']:'AMPMampm');
      // res+=(this.getHours ()>11?x.substr ((A?2:6),2):x.substr ((A?0:4),2));
      res += (this.getHours() > 11
         ? (A // translate ( 'AM' ) translate ( 'am' )
           ? (xlate['AM'] !== NULL ? xlate['AM'] : 'AM')
           : (xlate['am'] !== NULL ? xlate['am'] : 'am'))
         : (A // translate ( 'PM' ) translate ( 'pm' )
           ? (xlate['PM'] !== NULL ? xlate['PM'] : 'PM')
           : (xlate['pm'] !== NULL ? xlate['pm'] : 'pm')));
      break;
    case 'B':
      res += this.getSwatch();
      break;
    case 'D':
    case 'l':
      res += this.getDayName(fmt[i]);
      break;
    case 'F':
    case 'M':
      res += this.getMonthName(fmt[i]);
      break;
    case 'G':
    case 'H':
    case 'g':
    case 'h':
      h = this.getHours();

      if (fmt[i]in Array('g', 'h')) {
        h %= 12;
        h = (h === 0 ? 12 : h);
      }
      res += (fmt[i]in Array('G', 'g') || h > 9 ? h : '0' + h);
      break;
    case 'I':
    case 'K':
      // Daylight Saving time or standard?
      t = (this.getDST());
      // If I was sure that everyone always used 3 characters for these,
      // translate ( 'DSTstd' ) would work like this:
      // var x=(xlate['DSTstd']!=='undefined'?xlate['DSTstd']:'DSTstd');
      // res+=(fmt[i]==='I'?t:x.substr ((t?0:3),3));
      res += (fmt[i] === 'I'
         ? t
         : (t
           ? (xlate['DST'] !== NULL ? xlate['DST'] : 'DST')
           : (xlate['std'] !== NULL ? xlate['std'] : 'std')));
      break;
    case 'J':
      res += this.getJulian();
      break;
    case 'L':
      res += (_daysInMonth(2, this.getFullYear()) === 29);
      break;
    case 'N':
      d = this.getDay();
      res += (d ? d : 7);
      break;
    case 'O':
    case 'P':
      m = this.getTimezoneOffset();
      s = (m < 0 ? '+' : '-');
      m = Math.abs(m);
      h = Math.floor(m / 60);
      m %= 60;
      res += s +
      (h > 9 ? h : '0' + h) +
      (fmt[i] === 'P' ? ':' : '') +
      (m > 9 ? m : '0' + m);
      break;
    case 'R':
      res += (1000 + this.getDOY()).toString().substr(1);
      break;
    case 'S':
      res += this.getOrdinal();
      break;
    case 'U':
      res += Math.floor(this.getTime() / 1000);
      break;
    case 'W':
      res += this.getISOWeek();
      break;
    case 'X':
    case 'x':
      w = this.getWeek();
      res += (fmt[i] === 'X' || w > 9 ? w : '0' + w);
      break;
    case 'Y':
    case 'y':
      y = this.getFullYear();
      res += (fmt[i] === 'Y' ? y : (10000 + y).toString().substr(3));
      break;
    case 'Z':
      res += this.getTimezoneOffset() * -60;
      break;
    case 'c':
      res += this.format('Y-m-d^TH:i:sP');
      break;
    case 'd':
    case 'j':
      d = this.getDate();
      res += (fmt[i] === 'j' || d > 9 ? d : '0' + d);
      break;
    case 'i':
      res += (100 + this.getMinutes()).toString().substr(1);
      break;
    case 'm':
    case 'n':
      m = this.getMonth() + 1;
      res += (fmt[i] === 'n' || m > 9 ? m : '0' + m);
      break;
    case 'o':
      res += this.getISOYear();
      break;
    case 'r':
      res += this.format('D, j M Y H:i:s P');
      break;
    case 's':
      res += (100 + this.getSeconds()).toString().substr(1);
      break;
    case 't':
      res += _daysInMonth(this.getMonth() + 1, this.getFullYear());
      break;
    case 'u':
      res += this.getMilliseconds() * 1000;
      break;
    case 'w':
      res += this.getDay();
      break;
    case 'z':
      res += this.getDOY() - 1;
      break;
    case 'e':
      /* Not sure if it would be useful...
      If PHP 5.1.0+ we could put users server TZ from "translate.js".php here. */
      // res+=userSvrTZ;break;
    case 'T':
      /* Not sure if this would be useful either...
      Use users server TZ abbreviation from "translate.js.php". */
      // res+=userSvrTZAbbr;break;
    default:
      res += fmt[i];
    }
  }
  return res;
}
/**
 * Number of days in month?
 */
function _daysInMonth(
  month, // int 1 to 12
  year //   int 4 digits
) {
  var dd;

  dd = new Date(year, month, 0);

  return dd.getDate();
}
// end Date Format Method
//===============================================

/**
 * Number of days between today and another date.
 */
Date.prototype.dayDiff = function (d2) {
  return Math.floor(Math.abs(this - d2) / 86400000);
}
/**
 * Number of weekday_names, ie: 'Sun', between today and another date.
 * (Holidays are not considered.)
 */
Date.prototype.weekDayDiff = function (d2) {
  var d, t, w1, w2;

  d = this.dayDiff(d2);
  t = d % 7;

  if (this < d2) {
    w1 = this.getDay();
    w2 = d2.getDay();
  } else {
    w2 = this.getDay();
    w1 = d2.getDay();
  }
  if (w1 > w2) {
    t -= 2;
  }
  if ((w1 === 0 && w2 === 6) || w1 === 6) {
    t--;
  }
  return Math.abs(Math.floor(d / 7) * 5 + t);
}
//===============================================

/**
 * Is this a valid date?
 */
function validDate(
  y, // int  4 digit year
  m, // int  month (1 to 12)
  d //  int  day (1 to 31)
) {
  if (y !== parseInt(y, 10) || m !== parseInt(m, 10) || d !== parseInt(d, 10)) {
    return false;
  }
  m--;

  var nd;

  nd = new Date(y, m, d);

  return (y === nd.getFullYear() && m === nd.getMonth() && d === nd.getDate() ? nd : false);
}
//===============================================

/**
 * How many Tuesdays (or whatever) in month?
 */
function weekdayMonth(
  y, // int  4 digit year
  m, // int  month (1 = january, 12 = december)
  w //  int  day of the week to count (0 = Sunday, 6 = Saturday)
) {
  var dd;

  dd = new Date(y, m, 0);

  return (dd.getDate() - (dd.getDay() - w + 7) % 7 > 28 ? 5 : 4);
}
//===============================================

/**
 * Current Page Reference
 */
function getURL(uri) {
  uri.dir = uri.dom = location.href.substr(0, location.href.lastIndexOf('\/'));

  if (uri.dom.substr(0, 7) === 'http:\/\/') {
    uri.dom = uri.dom.substr(7);
  }
  var pos;

  uri.path = '';
  pos = uri.dom.indexOf('\/');

  if (pos > -1) {
    uri.path = uri.dom.substr(pos + 1);
    uri.dom = uri.dom.substr(0, pos);
  }
  uri.page = location.href.substr(uri.dir.length + 1, location.href.length + 1);
  pos = uri.page.indexOf('?');

  if (pos > -1) {
    uri.args = uri.page.substr(pos + 1);
    uri.page = uri.page.substr(0, pos);
  }
  pos = uri.page.indexOf('#');

  if (pos > -1) {
    uri.page = uri.page.substr(0, pos);
  }
  uri.ext = '';
  pos = uri.page.indexOf('.');

  if (pos > -1) {
    uri.ext = uri.page.substr(pos + 1);
    uri.page = uri.page.substr(0, pos);
  }
  uri.file = uri.page;

  if (uri.ext !== '') {
    uri.file += '.' + uri.ext;
  }
  if (uri.file === '') {
    uri.page = 'index';
  }
  return uri;
}
//===============================================

/* This function needs to be seperate for each page that has scrollers.
function start () {
// Code each vertical scroller as follows:
// startScroll('id of scroller div', 'scroll content');
// As many as needed

// startScroll('creds', scrollcontent); // "about.php" example
// startScroll('twoscroll','<p>Yet another scroller!</p>');

// Start new mq () for each marquee.
// As many as needed.
// 'm1'..'m2' are the example ids of the div to scroll.

// new mq('m1');
// new mq('m2');
// mqRotate(mqr); // must come last
}
 */

/**
 * Vertical Scroller
 */
function objWidth(obj) {
  if (obj.offsetWidth) {
    return obj.offsetWidth;
  }
  return (obj.clip ? obj.clip.width : 0);
}
function objHeight(obj) {
  if (obj.offsetHeight) {
    return obj.offsetHeight;
  }
  return (obj.clip ? obj.clip.height : 0);
}
function scrF(i, sH, eH) {
  var x = parseInt(i.top, 10) + (dR ? step :  - step);

  if (dR && x > sH) {
    x =  - eH;
  } else if (x < 2 - eH) {
    x = sH;
  }
  i.top = x + 'px';
}
function startScroll(sN, txt) {
  var eH, sH, sTxt, sW, scr;

  scr = document.getElementById(sN);
  sW = objWidth(scr) - 6;
  sH = objHeight(scr);
  scr.innerHTML = '<div class="Vscroll" id="' + sN + 'in" style="width:' + sW + ';">' + txt + '<\/div>';
  sTxt = document.getElementById(sN + 'in');
  eH = objHeight(sTxt);
  sTxt.style.top = (dR ? -eH : sH) + 'px';
  sTxt.style.clip = 'rect( 0,' + sW + 'px,' + eH + 'px,0 )';
  setInterval(function () {
    scrF(sTxt.style, sH, eH, 2);
  }, 1000 / speed);
}
//===============================================

/**
 * Continuous Text Marquee
 * https://www.thoughtco.com/how-to-create-a-continuous-text-marquee-in-javascript-4071126
 */
function mq(id) {
  var fulwid, heit, i, maxw, mqo, txt, wid;

  this.mqo = document.getElementById(id);
  wid = objWidth(this.mqo.getElementsByTagName('span')[0]) + 5;
  fulwid = objWidth(this.mqo);
  txt = this.mqo.getElementsByTagName('span')[0].innerHTML;
  this.mqo.innerHTML = '';
  heit = this.mqo.style.height;
  this.mqo.onmouseout = function () {
    mqRotate(mqr);
  };
  this.mqo.onmouseover = function () {
    clearTimeout(mqr[0].TO);
  };
  this.mqo.ary = [];
  maxw = Math.ceil(fulwid / wid) + 1;
  for (i = 0; i < maxw; i++) {
    this.mqo.ary[i] = document.createElement('div');
    this.mqo.ary[i].innerHTML = txt;
    this.mqo.ary[i].style.position = 'absolute';
    this.mqo.ary[i].style.left = (wid * i) + 'px';
    this.mqo.ary[i].style.width = wid + 'px';
    this.mqo.ary[i].style.height = heit;
    this.mqo.appendChild(this.mqo.ary[i]);
  }
  mqr.push(this.mqo);
}
function mqRotate(mqr) {
  if (!mqr) {
    return;
  }
  var i, j, x, y, z;

  for (j = 0; mqr[j]; j++) {
    maxa = mqr[j].ary.length;
    for (i = 0; i < maxa; i++) {
      x = mqr[j].ary[i].style;
      x.left = (parseInt(x.left, 10) - 1) + 'px';
    }
    y = mqr[j].ary[0].style;

    if (parseInt(y.left, 10) + parseInt(y.width, 10) < 0) {
      z = mqr[j].ary.shift();
      z.style.left = (parseInt(z.style.left, 10) + parseInt(z.style.width, 10) * maxa) + 'px';
      mqr[j].ary.push(z);
    }
  }
  mqr[0].TO = setTimeout('mqRotate( mqr )', 10);
}

dR = false,
mqr = [],
speed = 15,
step = 2;
uri = getURL(this);

isDST = today.format('I'); //    Is user in daylight saving time?
tzOffSet = today.format('O'); // User offset from UTC.
