<?php /* $Id$ */ ?>
<script type="text/javascript">
<!-- <![CDATA[
function selectDate ( day, month, year, current, evt ) {
  // get currently selected month/year
  monthobj = eval ( 'document.exportform.' + month );
  curmonth = monthobj.options[monthobj.selectedIndex].value;
  yearobj = eval ( 'document.exportform.' + year );
  curyear = yearobj.options[yearobj.selectedIndex].value;
  date = curyear;
  if (document.getElementById) {
    mX = evt.clientX   + 40;
    mY = evt.clientY  + 120;
  }
  else {
    mX = evt.pageX + 40;
    mY = evt.pageY +130;
  }
 var MyPosition = 'scrollbars=no,toolbar=no,left=' + mX + ',top=' + mY + ',screenx=' + mX + ',screeny=' + mY ; 
  if ( curmonth < 10 )
    date += "0";
  date += curmonth;
  date += "01";
  url = "datesel.php?form=exportform&fday=" + day +
    "&fmonth=" + month + "&fyear=" + year + "&date=" + date;
  var colorWindow = window.open(url,"DateSelection","width=300,height=200," + MyPosition);
}

function toggle_import() {
    var i = document.importform.ImportType.selectedIndex;
  if ( i == 1 ) { //Palm
      makeVisible ( "palm" );
   } else {
      makeInvisible ( "palm" ); 
   }
  if ( i == 3 ) {// Outlook CSV
      makeInvisible ( "ivcal" );
      makeVisible ( "outlookcsv" ); 
   } else {
      makeVisible ( "ivcal" ); 
      makeInvisible ( "outlookcsv" );
   }
}
//]]> -->
</script>
