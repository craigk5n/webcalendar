<?php
/*
 * Page Description:
 *	Display a timebar view of a single day.
 *
 * Input Parameters:
 *	month (*) - specify the starting month of the timebar
 *	day (*) - specify the starting day of the timebar
 *	year (*) - specify the starting year of the timebar
 *	users (*) - csv of users to include
 *	(*) required field
	*
 * Security:
 *	Must have "allow view others" enabled ($allow_view_other) in
 *	  System Settings unless the user is an admin user ($is_admin).
 */

include_once 'includes/init.php';

// Don't allow users to use this feature if "allow view others" is
// disabled.
if ( $allow_view_other == "N" && ! $is_admin ) {
  // not allowed...
  exit;
}

// input args in URL
// users: list of comma-separated users
// form: name of form on parent page
// listid: element id of user selection object in form
//   ... to be used like form.elements[$listid]
if ( empty ( $users ) ) {
  echo "Program Error: No users specified!"; exit;
} else if ( empty ( $year ) ) {
  echo "Program Error: No year specified!"; exit;
} else if ( empty ( $month ) ) {
  echo "Program Error: No month specified!"; exit;
} else if ( empty ( $day ) ) {
  echo "Program Error: No day specified!"; exit;
}

print_header('', '', "onload=\"focus();\"");

$span = ($WORK_DAY_END_HOUR - $WORK_DAY_START_HOUR) * 3 + 1;
if (strlen($month) == 1) $month = '0'.$month;   // add leading zeros
if (strlen($day) == 1) $day = '0'.$day;         // add leading zeros
$date = $year.$month.$day;
$time = mktime(0,0,0,$month,$day,$year);
$wday = strftime ( "%w", $time );
$base_url = "?form=$form&amp;listid=$listid&amp;users=$users";
$prev_url = $base_url."&amp;year=".  strftime('%Y', $time - 86400)
                     ."&amp;month=". strftime('%m', $time - 86400)
                     ."&amp;day=".   strftime('%d', $time - 86400);
$next_url = $base_url."&amp;year=".  strftime('%Y', $time + 86400)
                     ."&amp;month=". strftime('%m', $time + 86400)
                     ."&amp;day=".   strftime('%d', $time + 86400);

$users = explode(",",$users);
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
      parentForm.ampm[0].checked = true;
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

<div style="border-width:0px; width:99%;">
<a title="<?php etranslate("Previous")?>" class="prev" href="<?php echo $prev_url ?>"><img src="leftarrow.gif" class="prevnext" alt="<?php etranslate("Previous")?>" /></a>
<a title="<?php etranslate("Next")?>" class="next" href="<?php echo $next_url ?>"><img src="rightarrow.gif" class="prevnext" alt="<?php etranslate("Next")?>" /></a>
<div class="title">
<span class="date"><?php 
  printf ( "%s, %s %d, %d", weekday_name ( $wday ), month_name ( $month - 1 ), $day, $year ); 
?></span><br />
</div></div>
<br />

<form action="availability.php" method="post">
<?php daily_matrix($date,$users); ?>
</form>

</body></html>