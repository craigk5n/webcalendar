
function definePreColor() {
  precol[parseInt(cursorPos) - 1] = curcol;
  setPreColors();
  setCursor(
    parseInt(cursorPos) + 1 > 16
     ? 1 : parseInt(cursorPos) + 1);
}

function fromhex(inval) {
  var out = 0;
  for (var a = inval.length - 1; a >= 0; a--) {
    out += Math.pow(16, inval.length - a - 1)
     * hexchars.indexOf(inval.charAt(a));
  }
  return out;
}

function getCookie(Name) {
  var search = Name + '=';

  if (document.cookie.length > 0) { // If there are any cookies.
    offset = document.cookie.indexOf(search)
      if (offset !== -1) { // if cookie exists
        offset += search.length
        // set index of beginning of value
        end = document.cookie.indexOf(';', offset)
          // set index of end of cookie value
          if (end === -1)
            end = document.cookie.length;

          return unescape(document.cookie.substring(offset, end));
      }
  }
}

function HSLtoRGB(h, s, l) {
  if (s === 0)
    return [l, l, l]// achromatic

    h = h * 360 / 255;
  s /= 255;
  l /= 255;

  if (l <= 0.5)
    rm2 = l + l * s;
  else
    rm2 = l + s - l * s;

  rm1 = 2.0 * l - rm2;
  return [
    ToRGB1(rm1, rm2, h + 120.0),
    ToRGB1(rm1, rm2, h),
    ToRGB1(rm1, rm2, h - 120.0)];
}

function preset(what) {
  setCol(precol[what - 1]);
  setCursor(what);
}

function RGBtoHSL(r, g, b) {
  min = Math.min(r, Math.min(g, b));
  max = Math.max(r, Math.max(g, b));
  // l
  l = Math.round((max + min) / 2);
  // achromatic ?
  if (max === min) {
    h = 160;
    s = 0;
  } else {
    // s
    s = Math.round(255 * (max - min) /
        (l < 128
           ? (max + min)
           : (510 - max - min)));

    // h
    if (r === max)
      h = (g - b);
    else if (g === max)
      h = 2 + (b - r);
    else
      h = 4 + (r - g);

    h /= (max - min);
    h *= 60;

    if (h < 0)
      h += 360;

    h = Math.round(h * 255 / 360);
  }
  return [h, s, l];
}

function setCol(value) {
  value = value.toUpperCase();

  if (value.length !== 6)
    value = curcol;

  for (var a = 0; a < 6; a++)
    if (hexchars.indexOf(value.charAt(a)) === -1) {
      value = curcol;
      break;
    }
  curcol = value;
  currgb = [
    fromhex(curcol.substring(0, 2)),
    fromhex(curcol.substring(2, 4)),
    fromhex(curcol.substring(4, 6))];
  curhsl = RGBtoHSL(currgb.join());
  update();
}

function setCookie(name, value, expire) {
  document.cookie = name + '=' + escape(value) +
    (expire === null ? '' : '; expires=' + expire.toGMTString());
}

function setCursor(what) {
  document.getElementById('preimg' + cursorPos).src = blankImg.src;
  cursorPos = what;
  document.getElementById('preimg' + cursorPos).src = cursorImg.src;
}

function setFromHTML() {
  inval = document.getElementById('htmlcolor').value.toUpperCase();
  if (inval.length !== 6) {
    setCol(curcol);
    return;
  }
  for (var a = 0; a < 6; a++) {
    if (hexchars.indexOf(inval.charAt(a)) === -1) {
      setCol(curcol);
      return;
    }
  }
  setCol(inval);
}

function setFromImage(event) {
  var
  x = event.offsetX,
  y = event.offsetY;

  if (x === undefined) {
    xd = yd = 0;
    lr = document.getElementById('colorpic');
    while (lr !== null) {
      xd += lr.offsetLeft;
      yd += lr.offsetTop;
      lr = lr.offsetParent;
    }
    x = event.pageX - xd;
    y = event.pageY - yd;
  }
  //prevent lockup if RGB = 000000 or FFFFFF
  if (curhsl[2] === 0 || curhsl[2] === 255)
    curhsl[2] = Math.round(255 - y * 255 / 191);

  setHSL(Math.round(x * 255 / 191),
    Math.round(255 - y * 255 / 191),
    curhsl[2]);
}

function setFromRGB() {
  r = document.getElementById('rgb_r').value;
  g = document.getElementById('rgb_g').value;
  b = document.getElementById('rgb_b').value;
  setRGB(r, g, b);
}

function setFromSlider(event) {
  yd = 0;
  lr = document.getElementById('slider');
  while (lr !== null) {
    yd += lr.offsetTop;
    lr = lr.offsetParent;
  }
  y = event.clientY - yd;
  setHSL(curhsl[0], curhsl[1], Math.round(255 - y * 255 / 191));
}

function setHSL(h, s, l) {
  if (
    h > 255 || h < 0 ||
    s > 255 || s < 0 ||
    l > 255 || l < 0) {
    h = curhsl[0];
    s = curhsl[1];
    l = curhsl[2];
  }
  curhsl = [h, s, l];
  currgb = HSLtoRGB(h, s, l);
  curcol = tohex(currgb[0]) + tohex(currgb[1]) + tohex(currgb[2]);
  update();
}

function setPreColors() {
  for (var a = 1; a < 17; a++) {
    document.getElementById('precell' + a).bgColor = '#' + precol[a - 1];
  }
}

function setRGB(r, g, b) {
  if (
    r > 255 || r < 0 ||
    g > 255 || g < 0 ||
    b > 255 || b < 0) {
    r = currbg[0];
    g = currgb[1];
    b = currgb[2];
  }
  currgb = [r, g, b];
  curcol = tohex(r) + tohex(g) + tohex(b);
  curhsl = RGBtoHSL(r, g, b);
  update();
}

function tohex(inval) {
  return hexchars.charAt(inval / 16) + hexchars.charAt(inval % 16);
}

function ToRGB1(rm1, rm2, rh) {
  if (rh > 360.0)
    rh -= 360.0;
  else if (rh < 0.0)
    rh += 360.0;

  if (rh < 60.0)
    rm1 = rm1 + (rm2 - rm1) * rh / 60.0;
  else if (rh < 180.0)
    rm1 = rm2;
  else if (rh < 240.0)
    rm1 = rm1 + (rm2 - rm1) * (240.0 - rh) / 60.0;

  return Math.round(rm1 * 255);
}

function transferColor() {
  var expires = today || new Date();

  expires.setTime(today.getTime() + 864000);
  setCookie('webcalendar_custom_colors', precol.join(','), expires);
  thisInput.value = '#' + document.getElementById('htmlcolor').value.toUpperCase();

  if (thisInput.onchange)
    // This updates the color swatch for this input. It relies on the
    // <input>s of the prefform having onkeyup = 'updateColor(this);'
    // as an attribute.
    thisInput.onchange();

  window.close();
}

function update() {
  document.getElementById('thecell').bgColor = '#' + curcol;
  document.getElementById('rgb_r').value = currgb[0];
  document.getElementById('rgb_g').value = currgb[1];
  document.getElementById('rgb_b').value = currgb[2];
  document.getElementById('htmlcolor').value = curcol;
  setCursor(cursorPos);

  // set the cross on the colorpic
  var
  cp = document.getElementById('colorpic'),
  cross = document.getElementById('cross').style;

  xd = yd = 0;
  lr = cp;
  while (lr !== null) {
    xd += lr.offsetLeft;
    yd += lr.offsetTop;
    lr = lr.offsetParent;
  }
  cross.top = (yd - 9 + 191 - 191 * curhsl[1] / 255) + 'px';
  cross.left = (xd - 9 + 191 * curhsl[0] / 255) + 'px';
  // update slider pointer
  var sa = document.getElementById('sliderarrow').style,
  sp = document.getElementById('slider');
  xd = yd = 0;
  lr = sp;
  while (lr !== null) {
    xd += lr.offsetLeft;
    yd += lr.offsetTop;
    lr = lr.offsetParent;
  }
  sa.top = (yd + 187 - 191 * curhsl[2] / 255) + 'px';
  sa.left = (xd + 14) + 'px'
  // update slider colors
  for (var i = 0; i < 192; i++) {
    rgb = HSLtoRGB(curhsl[0], curhsl[1], 255 - 255 * i / 191);
    document.getElementById('sc' + i).bgColor = '#' +
      tohex(rgb[0]) + tohex(rgb[1]) + tohex(rgb[2]);
  }
}

var
// 'new' Images without using hacked 'new'.
blankImg = cursorImg = document.createElement('img'),

// Color choices
colorList = [
  'FF0000', '400000', '800000', 'C00000', 'FF4040', 'FF8080', 'FFC0C0', '000000',
  'FFFF00', '404000', '808000', 'C0C000', 'FFFF40', 'FFFF80', 'FFFFC0', '202020',
  '00FF00', '004000', '008000', '00C000', '40FF40', '80FF80', 'C0FFC0', '404040',
  '00FFFF', '004040', '008080', '00C0C0', '40FFFF', '80FFFF', 'C0FFFF', '808080',
  '0000FF', '000040', '000080', '0000C0', '4040FF', '8080FF', 'C0C0FF', 'C0C0C0',
  'FF00FF', '400040', '800080', 'C000C0', 'FF40FF', 'FF80FF', 'FFC0FF', 'FFFFFF'],

ctr = 0,

// Predefined Colors Cursor
cursorPos = 1,

// Other Stuff
curcol = curhsl = oldcol = thisInput = '',
currgb = [],
hexchars = '0123456789ABCDEF',
nl = "\n",
precol = getCookie('webcalendar_custom_colors');

choice = document.getElementById('colorchoices'),
custom = document.getElementById('colorcustom'),
slide = document.getElementById('slider'),
choicehtml = `
            <table border="1">`,
customhtml = `
              <table border="1">
                <tr>`,
slidehtml = `
                  <table width="14">`;

blankImg.src = 'images/blank.gif';
cursorImg.src = 'images/cursor.gif';

// Predefined Colors
if (!precol)
  precol.array_fill('FFFFFF', 0, 15);

for (var i = 0; i < 192; i++) {
  slidehtml += `
                    <tr>
                      <td id="sc${i}" height="1" width="14"></td>
                    </tr>`;
}
slide.innerHTML = slidehtml + `
                  </table>`;

for (var i = 1; i < 7; i++) {
  choicehtml += `
              <tr>`;
  for (var j = 1; j < 9; j++) {
    choicehtml += `
                <td bgcolor="#${colorList[ctr]}"<img src="images/blank.gif" height="14" width="14" alt="" onclick="setCol('${colorList[ctr]}')">></a></td>`;
    ctr++;
  }
  choicehtml += `
              <tr>`;
}
choice.innerHTML = choicehtml + `
            </table>`;

for (var i = 1; i < 17; i++) {
  customhtml += `
                  <td id="precell${i}"` +
  ' bgcolor="#FFFFFF"><img src="images/blank.gif" id="preimg' + i +
  '" height="14" width="14" alt="" onclick="preset(' + i + ')"></td>';

  if (i === 8)
    customhtml += `
                </tr>
                <tr>`;
}
custom.innerHTML = customhtml + `
                </tr>
              </table>`;

thisInput = window.opener.document.getElementById(document.getElementById('colorcell').value);
oldcol = thisInput.value;

if (oldcol.substring(0, 1) === '#')
  oldcol = oldcol.substring(1);

curcol = oldcol;
setPreColors();
setCursor('1');
setCol(oldcol);
document.getElementById('theoldcell').bgColor = '#' + oldcol;
currgb = [
  fromhex(curcol.substring(0, 2)),
  fromhex(curcol.substring(2, 4)),
  fromhex(curcol.substring(4, 6))];
curhsl = RGBtoHSL(currgb.join());
update();
