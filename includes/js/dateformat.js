// $Id$

// Date Format Method
// copyright Stephen Chapman, 20th November 2007, 19 January 2011
// http://javascript.about.com/library/bldateformat.htm
// Permission to use this JavaScript on your web page is granted
// provided that all of the code below in this script (including these
// comments) is used without any alteration.

// When used in conjunction with "includes/js/translate.js.php"
// we can get the translated Month and Weekday names.

Date.prototype.getMDay = function () {
  return (this.getDay() + 6) % 7;
};
Date.prototype.getISOYear = function () {
  var thu = new Date(this.getFullYear(), this.getMonth(), this.getDate() + 3 - this.getMDay());
  return thu.getFullYear();
};
Date.prototype.getISOWeek = function () {
  var onejan = new Date(this.getISOYear(), 0, 1);
  var wk = Math.ceil((((this - onejan) / 86400000) + onejan.getMDay() + 1) / 7);
  if (onejan.getMDay() > 3)
    wk--;
  return wk;
};
Date.prototype.getJulian = function () {
  return Math.floor((this / 86400000) - (this.getTimezoneOffset() / 1440) + 2440587.5);
};
Date.prototype.getMonthName = function () {
  var m = (typeof months != 'undefined' ? months : ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December']);
  return m[this.getMonth()];
};
Date.prototype.getMonthShort = function () {
  var m = (typeof shortMonths != 'undefined' ? shortMonths : ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec']);
  return m[this.getMonth()];
};
Date.prototype.getDayName = function () {
  var d = (typeof weekdays != 'undefined' ? weekdays : ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday']);
  return d[this.getDay()];
};
Date.prototype.getDayShort = function () {
  var d = (typeof shortWeekdays != 'undefined' ? shortWeekdays : ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat']);
  return d[this.getDay()];
};
Date.prototype.getOrdinal = function () {
  var d = this.getDate();
  switch (d) {
  case 1:
  case 21:
  case 31:
    return 'st';
  case 2:
  case 22:
    return 'nd';
  case 3:
  case 23:
    return 'rd';
  default:
    return 'th';
  }
};
Date.prototype.getDOY = function () {
  var onejan = new Date(this.getFullYear(), 0, 1);
  if (onejan.getDST())
    onejan.addHours(1);
  if (this.getDST())
    onejan.addHours(-1);
  return Math.ceil((this - onejan + 1) / 86400000);
};
Date.prototype.getWeek = function () {
  var onejan = new Date(this.getFullYear(), 0, 1);
  return Math.ceil((((this - onejan) / 86400000) + onejan.getDay() + 1) / 7);
};
Date.prototype.getStdTimezoneOffset = function () {
  var jan = new Date(this.getFullYear(), 0, 1);
  var jul = new Date(this.getFullYear(), 6, 1);
  return Math.max(jan.getTimezoneOffset(), jul.getTimezoneOffset());
};
Date.prototype.getDST = function () {
  return this.getTimezoneOffset() < this.getStdTimezoneOffset();
};
Date.prototype.getSwatch = function () {
  return Math.floor((((this.getUTCHours() + 1) % 24) +
      this.getUTCMinutes() / 60 + this.getUTCSeconds() / 3600) * 1000 / 24);
};
function _daysInMonth(month, year) {
  var dd = new Date(year, month, 0);
  return dd.getDate();
};
Date.prototype.format = function (f) {
  var fmt = f.split('');
  var res = '';
  for (var i = 0; fmt[i]; i++) {
    switch (fmt[i]) {
    case '^':
      res += fmt[++i];
      break;
    case 'd':
      var d = this.getDate();
      res += (d > 9 ? d : '0' + d);
      break;
    case 'D':
      res += this.getDayShort();
      break;
    case 'j':
      res += this.getDate();
      break;
    case 'l':
      res += this.getDayName();
      break;
    case 'S':
      res += this.getOrdinal();
      break;
    case 'w':
      res += this.getDay();
      break;
    case 'z':
      res += this.getDOY() - 1;
      break;
    case 'R':
      var dy = this.getDOY();
      if (dy < 9)
        dy = '0' + dy;
      res += (dy > 99 ? dy : '0' + dy);
      break;
    case 'F':
      res += this.getMonthName();
      break;
    case 'm':
      var m = this.getMonth() + 1;
      res += (m > 9 ? m : '0' + m);
      break;
    case 'M':
      res += this.getMonthShort();
      break;
    case 'n':
      res += (this.getMonth() + 1);
      break;
    case 't':
      res += _daysInMonth(this.getMonth() + 1, this.getFullYear());
      break;
    case 'L':
      res += (_daysInMonth(2, this.getFullYear()) == 29 ? 1 : 0);
      break;
    case 'Y':
      res += this.getFullYear();
      break;
    case 'y':
      var y = this.getFullYear().toString().substr(3);
      res += (y > 9 ? y : '0' + y);
      break;
    case 'a':
      res += (this.getHours() > 11 ? 'pm' : 'am');
      break;
    case 'A':
      res += (this.getHours() > 11 ? 'PM' : 'AM');
      break;
    case 'g':
      var h = this.getHours() % 12;
      res += (h == 0 ? 12 : h);
      break;
    case 'G':
      res += this.getHours();
      break;
    case 'h':
      var h = this.getHours() % 12;
      res += (h == 0 ? 12 : (h > 9 ? h : '0' + h));
      break;
    case 'H':
      var h = this.getHours();
      res += (h > 9 ? h : '0' + h);
      break;
    case 'i':
      var m = this.getMinutes();
      res += (m > 9 ? m : '0' + m);
      break;
    case 's':
      var s = this.getSeconds();
      res += (s > 9 ? s : '0' + s);
      break;
    case 'O':
    case 'P':
      var m = this.getTimezoneOffset();
      var s = (m < 0 ? '+' : '-');
      m = Math.abs(m);
      var h = Math.floor(m / 60);
      m = m % 60;
      res += s + (h > 9 ? h : '0' + h) + (fmt[i] == 'P' ? ':' : '') + (m > 9 ? m : '0' + m);
      break;
    case 'U':
      res += Math.floor(this.getTime() / 1000);
      break;
    case 'I':
      res += (this.getDST() ? 1 : 0);
      break;
    case 'K':
      res += (this.getDST() ? 'DST' : 'Std');
      break;
    case 'c':
      res += this.format('Y-m-d^TH:i:sP');
      break;
    case 'r':
      res += this.format('D, j M Y H:i:s P');
      break;
    case 'Z':
      var tz = this.getTimezoneOffset() * -60;
      res += tz;
      break;
    case 'W':
      res += this.getISOWeek();
      break;
    case 'X':
      res += this.getWeek();
      break;
    case 'x':
      var w = this.getWeek();
      res += (w > 9 ? w : '0' + w);
      break;
    case 'B':
      res += this.getSwatch();
      break;
    case 'N':
      var d = this.getDay();
      res += (d ? d : 7);
      break;
    case 'u':
      res += this.getMilliseconds() * 1000;
      break;
    case 'o':
      res += this.getISOYear();
      break;
    case 'J':
      res += this.getJulian();
      break;
    case 'e':
    case 'T':
      break;
    default:
      res += fmt[i];
    }
  }
  return res;
}
