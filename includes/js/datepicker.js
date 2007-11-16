/* --- Swazz Javascript Calendar ---
/* --- v 1.0 3rd November 2006
By Oliver Bryant
http://calendar.swazz.org */


//returned array is in form (m,d,y)
function parseDate ( date ) {
  formatParts=DATE_FORMAT.split('__');
  formatParts.pop();
  formatParts.shift();
  //get separators, then remove them
  sep1 = formatParts[1];
  sep2 = formatParts[3];
  formatParts.splice(3,1);
  formatParts.splice(1,1);
  dtarr = new Array();  
  dateArr = date.split ( sep1 );
  //we may be using different separatora
  if ( dateArr.length == 2 ) {
    date2 = dateArr[1];
    dateArr2 = date2.split ( sep2 );
    dateArr[1] = dateArr2[0];
    dateArr[2] = dateArr2[1];    
  }
  
  for(var k=0;k<formatParts.length;k++) {  
    if ( formatParts[k].substring(0,1) == 'm' )
      dtarr[0] = dateArr[k] -1; //change to javascript month numbering 0-11
    if ( formatParts[k].substring(0,1) == 'd' )
      dtarr[1] = dateArr[k];
    if ( formatParts[k] == 'yyyy' )
      dtarr[2] = dateArr[k];
    if ( formatParts[k] == 'yy' )
      dtarr[2] = (dateArr[k] < 30 ? '20' : '19') + dateArr[k] ;    
  }  
  //alert ( formatParts + '|' + dateArr + '|' + dtarr );
  return  dtarr; 
}

function formatDate ( date ) {
  //simple is mask is default value
//  if ( DATE_FORMAT == '__mm__/__dd__/__yyyy__' )
//   return date;
  dtarr = new Array();
  formatParts=DATE_FORMAT.split('__');
  formatParts.pop();
  formatParts.shift();
  //separators
  sep1 = formatParts[1];
  sep2 = formatParts[3]; 
  formatParts.splice(3,1);
  formatParts.splice(1,1);
  dateArr = date.split ( '/' );
  
  for(var k=0;k<formatParts.length;k++) {  
    if ( formatParts[k] == 'm' )
      dtarr[k] = parseInt(dateArr[0]-(-1)); //change to normal month numbering 1-12
    if ( formatParts[k] == 'mm' )
      dtarr[k] = lPad(dateArr[0]-(-1));
    if ( formatParts[k] == 'd' )
      dtarr[k] = dateArr[1];
    if ( formatParts[k] == 'dd' )
      dtarr[k] = lPad(dateArr[1]);
    if ( formatParts[k] == 'yyyy' )
      dtarr[k] = dateArr[2];
    if ( formatParts[k] == 'yy' )
      dtarr[k] = dateArr[2].substring(2,3);    
  }
  dtrtn = dtarr[0] + sep1 + dtarr[1] + sep2 + dtarr[2];
  return dtrtn;
}

function lPad(n, totalDigits)  { 
  if ( !totalDigits ) totalDigits = 2; 
  n = n.toString(); 
  var pd = ''; 
  if (totalDigits > n.length) { 
	for (i=0; i < (totalDigits-n.length); i++) { 
      pd += '0'; 
	} 
  } 
  return pd + n; 
}

function checkClick(e) {
    e?evt=e:evt=event;
    CSE=evt.target?evt.target:evt.srcElement;
    if ($('fc'))
      if (!isChild(CSE,$('fc')))
        $('fc').hide();
}

function isChild(s,d) {
  while(s) {
    if (s==d) 
      return true;
    s=s.parentNode;
  }
  return false;
}

function Left(obj)
{
    var curleft = 0;
    if (obj.offsetParent)
    {
        while (obj.offsetParent)
        {
            curleft += obj.offsetLeft
            obj = obj.offsetParent;
        }
    }
    else if (obj.x)
        curleft += obj.x;
    return curleft;
}

function Top(obj)
{
    var curtop = 0;
    if (obj.offsetParent)
    {
        while (obj.offsetParent)
        {
            curtop += obj.offsetTop
            obj = obj.offsetParent;
        }
    }
    else if (obj.y)
        curtop += obj.y;
    return curtop;
}

document.write('<table id="fc" cellpadding=2>');
document.write('<tr><td class="arrow" onclick="csubm()"><img src="images/arrowleftmonth.gif"></td><td colspan="5" id="mns"></td><td align="right" class="arrow" onclick="caddm()"><img src="images/arrowrightmonth.gif"></td></tr>');
document.write('<tr id="dn"></tr>');    
for(var kk=1;kk<=6;kk++) {
    document.write('<tr>');
    for(var tt=1;tt<=7;tt++) {
        num=7 * (kk-1) - (-tt);
        document.write('<td id="v' + num + '">&nbsp;</td>');
    }
    document.write('</tr>');
}
document.write('</table>');

document.all?document.attachEvent('onclick',checkClick):document.addEventListener('click',checkClick,false);
                
$('fc').hide();

// Calendar script
dtarr = new Array ();
var now = new Date;
var ccm=now.getMonth();
var ccy=now.getFullYear();

var updobj;
function lcs(ielem, evt ) {    
    ielem.select();
    evt.cancelBubble=true;
    updobj=ielem;
    if  ( Top(ielem) + $('fc').getDimensions().height > parent.innerHeight - 25  )
       $('fc').style.top = Top(ielem) - $('fc').getDimensions().height +"px";
    else 
       $('fc').style.top = Top(ielem)+ielem.offsetHeight +"px";
    $('fc').style.left = Left(ielem) +"px";
    $('fc').show();
    
    curdtarr = parseDate(ielem.value);

    if (curdtarr.length==3) {
      ccm=curdtarr[0];
      ccy=curdtarr[2];
      prepcalendar(curdtarr[0],curdtarr[1],curdtarr[2]);
    }    
}

var mnn=new Array('31','28','31','30','31','30','31','31','30','31','30','31');
var mnl=new Array('31','29','31','30','31','30','31','31','30','31','30','31');
var calvalarr=new Array(42);

// day selected
function prepcalendar(cm,cd,cy) {
	td=new Date();
	td.setDate(1);
	td.setFullYear(cy);
	td.setMonth(cm);
	ccd=td.getDay();
    if (cd=='' && cm==curdtarr[0] && cy==curdtarr[2] ) cd = curdtarr[1];
    $('dn').innerHTML=dn;    
    $('mns').innerHTML=mn[cm]+ ' ' + cy;
    marr=((cy%4)==0)?mnl:mnn;
    for(var d=1;d<=42;d++) {
      if ((d >= (ccd -(-1))) && (d<=ccd-(-marr[cm]))) { 
            $('v'+ d).className = ((cd!='')&&((d-ccd) == cd)) ? 'current' : 'day';            
            $('v'+ d).innerHTML=d-ccd;    
            calvalarr['v' + d]=  cm + '/' + (d-ccd) + '/' + cy;
            $('v'+ d).observe('click', function( event ){
              var target = Event.element(event).id;
              updobj.value=formatDate(calvalarr[target]);
              $('fc').hide();
            });            
        }
        else {
            $('v'+parseInt(d)).innerHTML = '&nbsp;';
            $('v'+parseInt(d)).className = 'other';
            }
    }
}

function caddm() {
    marr=((ccy%4)==0)?mnl:mnn;
    
    ccm+=1;
    if (ccm>=12) {
        ccm=0;
        ccy++;
    }
    prepcalendar(ccm, '', ccy);
}

function csubm() {
    marr=((ccy%4)==0)?mnl:mnn;
    
    ccm-=1;
    if (ccm<0) {
        ccm=11;
        ccy--;
    }
    prepcalendar(ccm, '', ccy);
}
