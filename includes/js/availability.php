<?php
if ( empty ( $PHP_SELF ) && ! empty ( $_SERVER ) &&
  ! empty ( $_SERVER['PHP_SELF'] ) ) {
  $PHP_SELF = $_SERVER['PHP_SELF'];
}
if ( ! empty ( $PHP_SELF ) && preg_match ( "/\/includes\//", $PHP_SELF ) ) {
    die ( "You can't access this file directly!" );
}
?>

<script type="text/javascript">
<!-- <![CDATA[
function schedule_event(hours, minutes) {
  var year =<?php echo $year ?> ;
  var month =<?php echo $month ?> ;
  var day =<?php echo $day ?> ;
  if (confirm('Change the date and time of this entry?')) {
    var parentForm = window.opener.document.editentryform;
    parentForm.timetype.selectedIndex = 1;
    if ( hours >  12 ) {
      parentForm.hour.value = hours - 12;
      parentForm.ampm[1].checked = true;
    } else {
      parentForm.hour.value = hours;
      if ( hours ==  12 ) {
        parentForm.ampm[1].checked = true;
      } else {
        parentForm.ampm[0].checked = true;
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