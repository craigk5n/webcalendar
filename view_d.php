<?php
//$start = microtime();

include_once 'includes/init.php';

// Don't allow users to use this feature if "allow view others" is
// disabled.
if ( $allow_view_other == "N" && ! $is_admin ) {
  // not allowed...
  do_redirect ( "$STARTVIEW.php" );
}

if ( empty ( $id ) ) {
  echo "Error: no id"; exit;
}

if ( empty ( $friendly ) )
  $friendly = 0;

// Find view name in $views[]
$view_name = "";
for ( $i = 0; $i < count ( $views ); $i++ ) {
  if ( $views[$i]['cal_view_id'] == $id ) {
    $view_name = $views[$i]['cal_name'];
  }
}

$INC = array ( 'js/view_d.php' );
print_header ( $INC );

set_today($date);
if (!$date) $date = $thisdate;

$wday = strftime ( "%w", mktime ( 2, 0, 0, $thismonth, $thisday, $thisyear ) );
$now = mktime ( 2, 0, 0, $thismonth, $thisday, $thisyear );
$nowYmd = date ( "Ymd", $now );

$next = mktime ( 2, 0, 0, $thismonth, $thisday + 1, $thisyear );
$nextyear = date ( "Y", $next );
$nextmonth = date ( "m", $next );
$nextday = date ( "d", $next );
$nextdate = sprintf ( "%04d%02d%02d", $nextyear, $nextmonth, $nextday );

$prev = mktime ( 2, 0, 0, $thismonth, $thisday - 1, $thisyear );
$prevyear = date ( "Y", $prev );
$prevmonth = date ( "m", $prev );
$prevday = date ( "d", $prev );
$prevdate = sprintf ( "%04d%02d%02d", $prevyear, $prevmonth, $prevday );

$thisdate = sprintf ( "%04d%02d%02d", $thisyear, $thismonth, $thisday );
?>

<table style="border-width:0px; width:100%;">
<tr><td style="text-align:left;">
<?php if ( ! $friendly ) { ?>
<a title="<?php etranslate("Previous")?>" href="view_d.php?id=<?php echo $id?>&amp;date=<?php echo $prevdate?>"><img src="leftarrow.gif" class="prevnext" alt="<?php etranslate("Previous")?>" /></a>
<?php } ?>
</td>
<td class="viewdtitle">
<span class="date">
<?php printf ( "%s, %s %d, %d", weekday_name ( $wday ), month_name ( $thismonth - 1 ), $thisday, $thisyear ); ?>
</span><br />
<span class="viewname">
<?php echo $view_name ?>
</span>
</td>
<td style="text-align:right;">
<?php if ( ! $friendly ) { ?>
<a title="<?php etranslate("Next")?>" href="view_d.php?id=<?php echo $id?>&amp;date=<?php echo $nextdate?>"><img src="rightarrow.gif" class="prevnext" alt="<?php etranslate("Next")?>" /></a>
<?php } ?>
</td></tr>
</table>
<center>

<?php
// get users in this view
$res = dbi_query (
  "SELECT cal_login FROM webcal_view_user WHERE cal_view_id = $id" );
$participants = array ();
if ( $res ) {
  while ( $row = dbi_fetch_row ( $res ) ) {
    $participants[] = $row[0];
  }
  dbi_free_result ( $res );
}
TimeMatrix($date,$participants);
?>

<br />

<!-- Hidden form for booking events -->
<form action="edit_entry.php" method="post" name="schedule">
<input type="hidden" name="date" value="<?php echo $thisyear.$thismonth.$thisday;?>" />
<input type="hidden" name="defusers" value="<?php echo implode ( ",", $participants ); ?>" />
<input type="hidden" name="hour" value="" />
<input type="hidden" name="minute" value="" />
</form>

</center>

<?php if ( empty ( $friendly ) ) {
  echo "<p><a class=\"navlinks\" href=\"view_d.php?id=$id&amp;";
  echo $u_url . "date=$nowYmd";
  echo $caturl;
  echo '&amp;friendly=1" target="cal_printer_friendly" onmouseover="window.status=\'' .
    translate("Generate printer-friendly version") .
    '\'">[' . translate("Printer Friendly") . ']</a>';
}
print_trailer ();
?>

<?php
//$end =  microtime();
//$start = explode(' ',$start);
//$end = explode(' ',$end);
//$total = $end[0]+trim($end[1]) - $start[0]-trim($start[1]);
//printf ("<p>seconds = %8.2f s</p>", $total);
?>

</body>
</html>

<?php
function TimeMatrix ($date,$participants) {
  global $CELLBG, $TODAYCELLBG, $THFG, $THBG, $TABLEBG;
  global $user_fullname,$nowYmd,$repeated_events,$events;
  global $thismonth, $thisday, $thisyear;

  $increment = 15;
  $interval = 4;
  $cell_pix = 6;
  $participant_pix = '120';
  //$interval = (int)(60 / $increment);
  $first_hour = $GLOBALS["WORK_DAY_START_HOUR"];
  $last_hour = $GLOBALS["WORK_DAY_END_HOUR"];
  $hours = $last_hour - $first_hour;
  $cols = (($hours * $interval) + 1);
  $total_pix = (int)((($cell_pix * $interval) * $hours) + $participant_pix);
?>

<br />
<table class="viewd" style="width:<?php echo $total_pix;?>;" cellspacing="0" cellpadding="0" cols="<?php echo $cols;?>">
 <tr><td style="height:1px; background-color:#000000;" colspan="<?php echo $cols;?>"><img src="pix.gif" style="height:1px; width:100%;" /></td></tr>
 <tr>
   <th class="row" style="width:<?php echo $participant_pix;?>;"><?php etranslate("Participants");?></th>

<?php
  $str = '';
  $MouseOut = "onmouseout=\"window.status=''; this.style.backgroundColor='".$CELLBG."';\"";
  $CC = 1;
  for($i=$first_hour;$i<$last_hour;$i++) {
     for($j=0;$j<$interval;$j++) {
        $str .= '   <td style="text-align:right; background-color:'.$CELLBG.'; width:'.$cell_pix.';" id="C'.$CC.'" class="dailymatrix" ';
        switch($j) {
          case 0:
                  if($interval == 4) { $k = ($i<=9?'0':substr($i,0,1)); }
		  $str .= 'onmousedown="schedule_event('.$i.','.($increment * $j).");\" onmouseover=\"window.status='Schedule a ".$i.':'.($increment * $j<=9?'0':'').($increment * $j)." appointment.'; this.style.backgroundColor='#CCFFCC'; return true;\" ".$MouseOut.">";
                  $str .= $k."</td>\n";
                  break;
          case 1:
                  if($interval == 4) { $k = ($i<=9?substr($i,0,1):substr($i,1,2)); }
		  $str .= 'onmousedown="schedule_event('.$i.','.($increment * $j).");\" onmouseover=\"window.status='Schedule a ".$i.':'.($increment * $j)." appointment.'; this.style.backgroundColor='#CCFFCC'; return true;\" ".$MouseOut.">";
                  $str .= $k."</td>\n";
                  break;
          default:
		  $str .= 'onmousedown="schedule_event('.$i.','.($increment * $j).");\" onmouseover=\"window.status='Schedule a ".$i.':'.($increment * $j)." appointment.'; this.style.backgroundColor='#CCFFCC'; return true;\" ".$MouseOut.">";
                  $str .= "&nbsp;</td>\n";
                  break;
        }
       $CC++;
     }
  }
  echo $str.
       " </tr>\n <tr><td style=\"height:1px; background-color:#000000;\" colspan=\"$cols\"><img src=\"pix.gif\" style=\"height:1px; width:100%;\" /></td></tr>\n";

  // Display each participant

  for($i=0;$i<count($participants);$i++) {
    user_load_variables ( $participants[$i], "user_" );

    /* Pre-Load the repeated events for quckier access */
    $repeated_events = read_repeated_events ( $participants[$i] );
    /* Pre-load the non-repeating events for quicker access */
    $events = read_events ( $participants[$i], $nowYmd, $nowYmd );

    // get all the repeating events for this date and store in array $rep
    $rep = get_repeating_entries ( $participants[$i], $nowYmd );
    // get all the non-repeating events for this date and store in $ev
    $ev = get_entries ( $participants[$i], $nowYmd );

    // combine into a single array for easy processing
    $ALL = array_merge($rep,$ev);
    $all_events = array();

    // exchange space for &nbsp; to keep from breaking
    $user_nospace = preg_replace('/\s/','&nbsp;',$user_fullname);

    foreach ($ALL as $E) {
      $E['cal_time'] = sprintf ( "%06d", $E['cal_time']);
      $Tmp['START'] = mktime ( substr($E['cal_time'], 0, 2 ), substr($E['cal_time'], 2, 2 ), 0, $thismonth, $thisday, $thisyear );
      $Tmp['END'] = $Tmp['START'] + ( $E['cal_duration'] * 60 );
      $Tmp['ID'] = $E['cal_id'];
      $all_events[] = $Tmp;
    }
    echo "<tr>\n <th class=\"row\" style=\"width:$participant_pix;\">".$user_nospace."</th>\n";
    $col = 1;

    for($j=$first_hour;$j<$last_hour;$j++) {
       for($k=0;$k<$interval;$k++) {
         $space = ($k == '0') ? '<img src="pix.gif" style=\"height:12px; width:1px; text-align:center;\" />' : "&nbsp;";
	 $RC = $CELLBG;
         $TIME = mktime ( sprintf ( "%02d",$j), ($increment * $k), 0, $thismonth, $thisday, $thisyear );

         foreach ($all_events as $ET) {
          if (($TIME >= $ET['START']) && ($TIME < $ET['END'])) {
    	    if ($space == "&nbsp;") {
            $space="<a href=\"view_entry.php?id={$ET['ID']}\"><img src=\"pix.gif\" style=\"height:8px; width:100%; text-align:center; border-width:0px;\" /></a>";
            } else {
            $space="<a href=\"view_entry.php?id={$ET['ID']}\"><img src=\"pix.gif\" style=\"height:12px; width:1px; text-align:center; border-width:0px;\" /><img src=\"pix.gif\" style=\"height:8px; width:100%; text-align:center; border-width:0px;\" /></a>";
            }
	    break;
	  }
	 }
         echo "   <td style=\"text-align:left; background-color:$RC; vertical-align:middle; width:$cell_pix;\">$space</td>\n";
         $col++;
       }
    }
    echo " </tr>\n <tr><td style=\"height:1px; background-color:#000000;\" colspan=\"$cols\"><img src=\"pix.gif\" style=\"height:1px; width:100%;\" /></td></tr>\n";
  } // End foreach participant
  echo "</table>\n";

} // end TimeMatrix function
?>