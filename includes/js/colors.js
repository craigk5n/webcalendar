 /* $Id$  */

initPhpVars( 'colors' );

  precol= new Array (16);
// Colr choices
  var colorList = new Array ('0','ff0000','400000','800000','c00000','ff4040','ff8080','ffc0c0','000000','ffff00','404000','808000','c0c000','ffff40','ffff80','ffffc0','202020','00ff00','004000','008000','00c000','40ff40','80ff80',',c0ffc0','404040','00ffff','004040','008080','00c0c0','40ffff','80ffff','c0ffff','808080','0000ff','000040','000080','0000c0','4040ff','8080ff','c0c0ff','c0c0c0','ff00ff','400040','800080','c000c0','ff40ff','ff80ff','ffc0ff','ffffff');
// Predefined Colors Cursor
  cursorImg=new Image ();
  cursorImg.src="images/cursor.gif";
  blankImg=new Image ();
  blankImg.src="images/blank.gif";
  cursorPos=1;
// Other Stuff
  hexchars="0123456789ABCDEF";
  var oldcol;
  var curcol;
  var thisInput;
  var currgb;
  var curhsl;

function setInit () {
	//make sure AJAX is finished first
   if ( typeof( window[ 'CUSTOM_COLORS' ] ) == "undefined") {
     setTimeout ( "setInit()", 100 );
	 return false;
   }
  precol=CUSTOM_COLORS.split(",",16);
  thisInput = window.opener.$($('colorcell').value);
  oldcol=thisInput.value;
  if (oldcol.substr (0,1 ) == '#' )
    oldcol = oldcol.substr (1,6);
  curcol=oldcol;
  setPreColors ();
  setCursor('1');
  setCol(oldcol);
  $('theoldcell').bgColor= '#' + oldcol;
  currgb=[fromhex(curcol.substr (0,2)), fromhex(curcol.substr (2,2)), fromhex(curcol.substr (4,2))];
  curhsl=RGBtoHSL(currgb[0],currgb[1],currgb[2]);
  update ();
}

function fillhtml () {
  var slidehtml = choicehtml = customhtml ='';
  var ctr=0;
  slidehtml += '<table cellspacing="0" cellpadding="0" width="14">';
  for (i=0;i<192;i++) {
    slidehtml += '<tr><td id="sc'+(i+1)+ '" height="1" width="14"></td></tr>';
  }
  slidehtml += '</table>';
  $('slider').innerHTML = slidehtml;

  choicehtml += '<table border="1" cellpadding="0" cellspacing="0">';
  for ( i=1; i<7; i++ ) {
    choicehtml += '<tr height="14">';
    for (j=1; j<9; j++ ) {
      ctr++;
        choicehtml += '<td height="14" width="14" bgcolor="#'+ colorList[ctr]
        +'" onclick="setCol( \'' +colorList[ctr] +'\' )">'
        + '<img src="images/blank.gif" width="14" height="14" border="0" alt=""/></a></td>';
    }
    choicehtml += '</tr>';
  }
  choicehtml += '</table>';
  $('colorchoices').innerHTML = choicehtml;

  customhtml += '<table border="1" cellpadding="0" cellspacing="0"><tr>';
  for ( i=1; i<17; i++) {
    customhtml += '<td id="precell' + i
       + '" bgcolor="#ffffff" onclick="preset( '+ i
       + ' )"><img src="images/blank.gif" width="14" '
       + 'id="preimg' + i + '" height="14" border="0" alt="" /></td>';
    if ( i == 8 ) customhtml += '</tr><tr>';
  }
  customhtml += '</tr></table>';
  $('colorcustom').innerHTML = customhtml;
}

function transferColor () {
  //setPref ( 'CUSTOM_COLORS' ,precol.join(",") );
  thisInput.value = '#' + $('htmlcolor').value.toUpperCase ();
  if (thisInput.onchange) {
  // This updates the color swatch for this color input. It relies on the
  // <input>s of the prefform having onkeyup="updateColor(this);" as an
  // attribute
    thisInput.onchange ();
   }
  window.close ();
}

function fromhex(inval) {
  out=0;
  for (a=inval.length-1;a>=0;a--)
    out+=Math.pow(16,inval.length-a-1)*hexchars.indexOf(inval.charAt(a));
  return out;
}

function tohex(inval) {
  out=hexchars.charAt(inval/16);
  out+=hexchars.charAt(inval%16);
  return out;
}

function setPreColors () {
  for (a=1;a<=16;a++) {
    $('precell'+a).bgColor= '#'+ precol[a-1];
  }
}

function definePreColor () {
  precol[parseInt(cursorPos)-1]=curcol;
  setPreColors ();
  setCursor(parseInt(cursorPos)+1>16?1:parseInt(cursorPos)+1);
}

function preset (what) {
  setCol(precol[what-1]);
  setCursor(what);
}

function setCursor(what) {
  $('preimg'+cursorPos).src=blankImg.src;
  cursorPos=what;
  $('preimg'+cursorPos).src=cursorImg.src;
}

function update () {
  $('thecell').bgColor= '#' + curcol;
  $('rgb_r').value=currgb[0];
  $('rgb_g').value=currgb[1];
  $('rgb_b').value=currgb[2];
  $('htmlcolor').value=curcol;
  setCursor(cursorPos);

  // set the cross on the colorpic
  xd=0;yd=0;lr=$('colorpic');
  while (lr!=null) {xd+=lr.offsetLeft; yd+=lr.offsetTop; lr=lr.offsetParent;}
  $('cross').style.top=(yd-9+191-191*curhsl[1]/255)+"px";
  $('cross').style.left=(xd-9+191*curhsl[0]/255)+"px";
  // update slider pointer
  xd=0;yd=0;lr=$('slider');
  while (lr!=null) {xd+=lr.offsetLeft; yd+=lr.offsetTop; lr=lr.offsetParent;}
  $('sliderarrow').style.top=(yd+187-191*curhsl[2]/255)+"px";
  $('sliderarrow').style.left=(xd+14)+"px"
  // update slider colors
  for (i=0;i<192;i++) {
    rgb=HSLtoRGB(curhsl[0],curhsl[1],255-255*i/191);
    $('sc'+(i+1)).bgColor= '#' + tohex(rgb[0])+tohex(rgb[1])+tohex(rgb[2]);
  }
}

function HSLtoRGB (h,s,l) {
  if (s == 0) return [l,l,l] // achromatic
  h=h*360/255;s/=255;l/=255;
  if (l <= 0.5) rm2 = l + l * s;
  else rm2 = l + s - l * s;
  rm1 = 2.0 * l - rm2;
  return [ToRGB1(rm1, rm2, h + 120.0),ToRGB1(rm1, rm2, h),ToRGB1(rm1, rm2, h - 120.0)];
}

function ToRGB1(rm1,rm2,rh) {
  if      (rh > 360.0) rh -= 360.0;
  else if (rh <   0.0) rh += 360.0;
  if      (rh <  60.0) rm1 = rm1 + (rm2 - rm1) * rh / 60.0;
  else if (rh < 180.0) rm1 = rm2;
  else if (rh < 240.0) rm1 = rm1 + (rm2 - rm1) * (240.0 - rh) / 60.0;
  return Math.round ( rm1 * 255);
}

function RGBtoHSL (r,g,b) {
  min = Math.min (r,Math.min (g,b));
  max = Math.max(r,Math.max(g,b));
  // l
  l = Math.round ( (max+min)/2);
  // achromatic ?
  if (max==min) {h=160;s=0;}
  else {
  // s
    if (l<128) s=Math.round ( 255*(max-min)/(max+min));
    else s=Math.round ( 255*(max-min)/(510-max-min));
  // h
    if (r==max)  h=(g-b)/(max-min);
    else if (g==max) h=2+(b-r)/(max-min);
    else h=4+(r-g)/(max-min);
    h*=60;
    if (h<0) h+=360;
    h=Math.round ( h*255/360);
  }
  return [h,s,l];
}

function setCol(value) {
  value=value.toUpperCase ();
  if (value.length!=6) value=curcol;
  for (a=0;a<6;a++)
    if (hexchars.indexOf(value.charAt(a))==-1) {
      value=curcol;break;
    }
  curcol=value;
  currgb=[fromhex(curcol.substr (0, 2 )), fromhex(curcol.substr (2,2)), fromhex(curcol.substr (4,2))];
  curhsl=RGBtoHSL(currgb[0],currgb[1],currgb[2]);
  update ();
}

function setRGB(r,g,b) {
  if (r>255||r<0||g>255||g<0||g>255||g<0) {r=currbg[0];g=currgb[1];b=currgb[2];}
  currgb=[r,g,b];
  curcol=tohex(r)+tohex(g)+tohex(b);
  curhsl=RGBtoHSL(r,g,b);
  update ();
}

function setHSL (h,s,l) {
  if (h>255||h<0||s>255||s<0||l>255||l<0) {s=curhsl[0];s=curhsl[1];l=curhsl[2];}
  curhsl=[h,s,l];
  currgb=HSLtoRGB(h,s,l);
  curcol=tohex(currgb[0])+tohex(currgb[1])+tohex(currgb[2]);
  update ();
}

function setFromRGB () {
  setRGB($('rgb_r').value,$('rgb_g').value,$('rgb_b').value);
}

function setFromHTML () {
  inval=$('htmlcolor').value.toUpperCase ();
  if (inval.length!=6) {setCol(curcol);return;}
  for (a=0;a<6;a++)
    if (hexchars.indexOf(inval.charAt(a))==-1) {
      setCol(curcol);return;
    }
  setCol(inval);
}

function setFromImage (event) {
  var x=event.offsetX;
  var y=event.offsetY;
  if (x == undefined) {
    xd=0;yd=0;lr=$('colorpic');
    while (lr!=null) {xd+=lr.offsetLeft; yd+=lr.offsetTop; lr=lr.offsetParent;}
    x=event.pageX-xd;
    y=event.pageY-yd;
  }
  //prevent lockup if RGB = 000000 or FFFFFF
  if ( curhsl[2] == 0 || curhsl[2] == 255 ) curhsl[2] = Math.round ( 255-y*255/191);
  setHSL (Math.round ( x*255/191),Math.round ( 255-y*255/191),curhsl[2]);
}

function setFromSlider (event) {
  yd=0;lr=$('slider');
  while (lr!=null) {yd+=lr.offsetTop; lr=lr.offsetParent;}
  y=event.clientY-yd;
  setHSL (curhsl[0],curhsl[1],Math.round ( 255-y*255/191));
}