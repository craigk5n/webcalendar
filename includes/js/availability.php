<?php
defined ( '_ISVALID' ) or die ( 'You cannot access this file directly!' );

$month = $arinc[3];
$day = $arinc[4];
$year = $arinc[5];
$parent_form = $arinc[6];
?>

function schedule_event(hours, minutes) {
  var year =<?php echo $year ?>;
  var month =<?php echo $month ?>;
  var day =<?php echo $day ?>;
  if (confirm("<?php etranslate ( 'Change the date and time of this entry?', true)?>")) {
    var parentForm = $('#<?php echo $parent_form;?>', window.parent.document);
    if ('<?php echo $parent_form ?>' == 'editentryform') {
      var tt = window.opener.$("#timetype");
      // Change to "Timed Event".
      // Calling change() will also invoke the change handler and make certain
      // input fields (hours, minutes) visible.
      tt.val('T').change();
//      //Make time controls visible on parent
//      window.opener.$('#timeentrystart').show();
//      if (window.opener.$('#duration_h')) {
//        window.opener.$('#timeentryduration').show();
//      } else {
//        $window.opener.$('#timeentryend').show();
//      }
    }
    window.opener.$('#entry_hour').val(hours);
    if (hours >  12) {
      if (window.opener.$('#entry_ampmP')) {
        window.opener.$('#entry_hour').val(hours - 12);
        window.opener.$('#entry_ampmP').prop("checked", true);
      }
    } else {
      if (hours == 12 && window.opener.$('#entry_ampmP')) {
        window.opener.$('#entry_ampmP').prop("checked", true);
      } else {
        if (window.opener.$('#entry_ampmA')) {
          window.opener.$('#entry_ampmA').prop("checked", true);
        }
      }
    }
    window.opener.$('#entry_minute').val(minutes);
    window.opener.$('#day').attr('selectedIndex', day - 1);
    window.opener.$('#month').attr('selectedIndex', month - 1);
    window.opener.$('#year').val(year).change();
    window.close();
  }
}
