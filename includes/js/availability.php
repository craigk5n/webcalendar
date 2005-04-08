<?php
if ( empty ( $PHP_SELF ) && ! empty ( $_SERVER ) &&
  ! empty ( $_SERVER['PHP_SELF'] ) ) {
  $PHP_SELF = $_SERVER['PHP_SELF'];
}
if ( ! empty ( $PHP_SELF ) && preg_match ( "/\/includes\//", $PHP_SELF ) ) {
    die ( "You can't access this file directly!" );
}
global $month, $day, $year;
?>

<script type="text/javascript">
<!-- <![CDATA[
// detect browser
NS4 = (document.layers) ? 1 : 0;
IE4 = (document.all) ? 1 : 0;
// W3C stands for the W3C standard, implemented in Mozilla (and Netscape 6) and IE5
W3C = (document.getElementById) ? 1 : 0;	
//Function is similar to visible.php, but effects the parent
function makeVisible ( name ) {
  var ele;

  if ( W3C ) {
    ele = window.opener.document.getElementById(name);
  } else if ( NS4 ) {
    ele = window.opener.document.layers[name];
  } else { // IE4
    ele = window.opener.document.all[name];
  }

  if ( NS4 ) {
    ele.visibility = "show";
  } else {  // IE4 & W3C & Mozilla
    ele.style.visibility = "visible";
  }
}

function schedule_event(hours, minutes) {
  var year =<?php echo $year ?> ;
  var month =<?php echo $month ?> ;
  var day =<?php echo $day ?> ;
  if (confirm("<?php etranslate("Change the date and time of this entry?")?>")) {
    var parentForm = window.opener.document.editentryform;
    parentForm.timetype.selectedIndex = 1;
    //Make time controls visible on parent
    makeVisible ( "timeentrystart" );
    if ( parentForm.duration_h ) {
      makeVisible ( "timeentryduration" );
    } else {
      makeVisible ( "timeentryend" );
    }
    if ( hours >  12 ) {
      parentForm.hour.value = hours - 12;
      if ( parentForm.ampm ) {
        parentForm.ampm[1].checked = true;
      }
    } else {
      parentForm.hour.value = hours;
      if ( hours ==  12 &&  parentForm.ampm )  {
        parentForm.ampm[1].checked = true;
      } else {
        if ( parentForm.ampm ) {
          parentForm.ampm[0].checked = true;
        }
      }
    }
    parentForm.minute.value = minutes;
    parentForm.day.selectedIndex = day - 1;
    parentForm.month.selectedIndex = month - 1;
    for ( i = 0; i < parentForm.year.length; i++ ) {
      if ( parentForm.year.options[i].value == year ) {
        parentForm.year.selectedIndex = i;
      }
    }
    window.close ();
  }
}
//]]> -->
</script>