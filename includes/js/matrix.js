/* $Id$  */ 

initPhpVars( 'matrix' );

var isAvailabilty = false;
var month, day, year;

function initAvail( m, d, y) {
	month = m;
	day = d;
	year = y;
	isAvailabilty = true;
}

function matrixMagic() {  
   if(!document.getElementsByTagName) return false; 
	 //make sure AJAX is finished first
   if ( typeof( window[ 'schedStr' ] ) == "undefined") {
     setTimeout ( "matrixMagic()", 10 );
	 return false;
   }
	 var rows = $$('td');  
	 var img = $$('td a img'); 
   for (var i=0; i<rows .length; i++) { 
	   if ( rows[i].hasClassName('dailymatrix') ||
		   rows[i].hasClassName('matrixappts')) {
			 		if ( timeFormat == 12 ) { 
			      rowtime = rows[i].id.substring(1,3) % 12;
				    if ( rowtime == 0 ) rowtime = 12;
			    } else {
			      rowtime = rows[i].id.substring(1,3);
			    }
			    if ( isAvailabilty == false ) {
			      rows[i].onmousedown = function( event ) {
				      document.schedule.cal_time.value = this.id.substring(1,5) + '00';
				      document.schedule.submit ();
				      return true;
			      } 
			      rows[i].title = schedStr + rowtime + ':' + rows[i].id.substring(3,5);
					} else {
            rows[i].onmousedown = function( event ) {
				      change_Event (  this.id.substring(1,3) ,this.id.substring(3,5) );
				      return true;
			      } 						
						rows[i].title = chgStr + rowtime + ':' + rows[i].id.substring(3,5);
					}
		 }		 
   }
   for (var i=0; i<img .length; i++) { 
	   if ( img[i].parentNode.hasClassName('matrix') ) {
			 img[i].title = img[i].alt = viewStr;			 
		 }
		 
   }
}
//we currently only get here from the participants tab of edit_entry.php
function change_Event ( hours, minutes ) {
  if (confirm( chgConfirmStr )) {
		var parent = window.opener.document;
    var parentForm = parent.forms['editentryform'];
      parentForm.timetype.selectedIndex = 1;
      //Make time controls visible on parent
			parent.getElementById('timeentrystart').style.visibility = "visible";
      //makeVisible ( timeentrystart, false );
      if ( parentForm.duration_h ) {
				parent.getElementById('timeentryduration').style.visibility = "visible";
       // makeVisible ( timeentryduration, false );
      } else {
				parent.getElementById('timeentryend').style.visibility = "visible";
       // makeVisible ( timeentryend, false);
      }
    parentForm.entry_hour.value = hours;
    if ( hours >  12 ) {
      if ( parentForm.entry_ampmP ) {
        parentForm.entry_hour.value = hours - 12;
        parentForm.entry_ampmP.checked = true;
      }
    } else {
      if ( hours ==  12 &&  parentForm.entry_ampmP )  {
        parentForm.entry_ampmP.checked = true;
      } else {
        if ( parentForm.entry_ampmA ) {
          parentForm.entry_ampmA.checked = true;
        }
      }
    }
    if   ( minutes <= 9 ) minutes = '0' + minutes; 
		if   ( minutes == '000' ) minutes = '0';
    parentForm.entry_minute.value=minutes; 
    parentForm.entry_day.selectedIndex = parseInt ( day ) -1;
    for ( i = 0; i < parentForm.entry_month.length; i++ ) {
      if ( parentForm.entry_month.options[i].value == parseInt ( month ) ) {
        parentForm.entry_month.selectedIndex = i;
      }
    }
    for ( i = 0; i < parentForm.entry_year.length; i++ ) {
      if ( parentForm.entry_year.options[i].value == year ) {
        parentForm.entry_year.selectedIndex = i;
      }
    }
    window.close ();
  }
}



