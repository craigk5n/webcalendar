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

<TABLE BORDER="0" WIDTH="100%">
<TR><TD ALIGN="left">
<?php if ( ! $friendly ) { ?>
<A HREF="view_d.php?id=<?php echo $id?>&date=<?php echo $prevdate?>"><IMG SRC="leftarrow.gif" WIDTH="36" HEIGHT="32" BORDER="0" ALT="<?php etranslate("Previous")?>"></A>
<?php } ?>
</TD>
<TD ALIGN="middle">
<FONT SIZE="+2" COLOR="<?php echo $H2COLOR?>">
<B>
<?php printf ( "%s, %s %d, %d", weekday_name ( $wday ), month_name ( $thismonth - 1 ), $thisday, $thisyear ); ?>
</B></FONT><BR>
<FONT COLOR="<?php echo $H2COLOR?>"><?php echo $view_name ?></FONT>
</TD>
<TD ALIGN="right">
<?php if ( ! $friendly ) { ?>
<A HREF="view_d.php?id=<?php echo $id?>&date=<?php echo $nextdate?>"><IMG SRC="rightarrow.gif" WIDTH="36" HEIGHT="32" BORDER="0" ALT="<?php etranslate("Next")?>"></A>
<?php } ?>
</TD></TR>
</TABLE>
<CENTER>

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

<BR>

<!-- Hidden form for booking events -->
<FORM ACTION="edit_entry.php" METHOD="POST" NAME="schedule">
<INPUT TYPE="hidden" NAME="date" VALUE="<?php echo $thisyear.$thismonth.$thisday;?>">
<INPUT TYPE="hidden" NAME="defusers" VALUE="<?php echo implode ( ",", $participants ); ?>">
<INPUT TYPE="hidden" NAME="hour" VALUE="">
<INPUT TYPE="hidden" NAME="minute" VALUE="">
</FORM>

</CENTER>

<?php if ( empty ( $friendly ) ) {
  echo "<p><a class=\"navlinks\" href=\"view_d.php?id=$id&";
  echo $u_url . "date=$nowYmd";
  echo $caturl;
  echo '&friendly=1" target="cal_printer_friendly" onmouseover="window.status=\'' .
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

</BODY>
</HTML>

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

<BR>
<TABLE BORDER="0" CELLSPACING="0" CELLPADDING="0"><TR><TD BGCOLOR="<?php echo $TABLEBG;?>">
<TABLE WIDTH="<?php echo $total_pix;?>" BORDER="0" CELLSPACING="0" CELLPADDING="0" COLS="<?php echo $cols;?>">
 <TR><TD HEIGHT="1" COLSPAN="<?php echo $cols;?>" BGCOLOR="black"><img src="pix.gif" HEIGHT="1" WIDTH="100%"></TD></TR>
 <TR>
   <TD WIDTH="<?php echo $participant_pix;?>" BGCOLOR="<?php echo $THBG;?>"><FONT COLOR="<?php echo $THFG;?>" SIZE="-1"><?php etranslate("Participants");?> </FONT></TD>

<?php
  $str = '';
  $MouseOut = "onMouseOut=\"window.status=''; this.style.backgroundColor='".$THBG."';\"";
  $CC = 1;
  for($i=$first_hour;$i<$last_hour;$i++) {
     for($j=0;$j<$interval;$j++) {
        $str .= '   <TD ALIGN="right" ID="C'.$CC.'" BGCOLOR="'.$THBG.'" WITDH="'.$cell_pix.'" CLASS="dailymatrix" ';
        switch($j) {
          case 0:
                  if($interval == 4) { $k = ($i<=9?'0':substr($i,0,1)); }
		  $str .= 'onMouseDown="schedule_event('.$i.','.($increment * $j).");\" onMouseOver=\"window.status='Schedule a ".$i.':'.($increment * $j<=9?'0':'').($increment * $j)." appointment.'; this.style.backgroundColor='#CCFFCC'; return true;\" ".$MouseOut.">";
                  $str .= $k."</TD>\n";
                  break;
          case 1:
                  if($interval == 4) { $k = ($i<=9?substr($i,0,1):substr($i,1,2)); }
		  $str .= 'onMouseDown="schedule_event('.$i.','.($increment * $j).");\" onMouseOver=\"window.status='Schedule a ".$i.':'.($increment * $j)." appointment.'; this.style.backgroundColor='#CCFFCC'; return true;\" ".$MouseOut.">";
                  $str .= $k."</TD>\n";
                  break;
          default:
		  $str .= 'onMouseDown="schedule_event('.$i.','.($increment * $j).");\" onMouseOver=\"window.status='Schedule a ".$i.':'.($increment * $j)." appointment.'; this.style.backgroundColor='#CCFFCC'; return true;\" ".$MouseOut.">";
                  $str .= "&nbsp;</TD>\n";
                  break;
        }
       $CC++;
     }
  }
  echo $str.
       " </TR>\n <TR><TD HEIGHT=\"1\" COLSPAN=\"$cols\" BGCOLOR=\"black\"><img src=\"pix.gif\" HEIGHT=\"1\" WIDTH=\"100%\"></TD></TR>\n";

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
    echo "<TR>\n <TD WIDTH=\"$participant_pix\" BGCOLOR=\"$CELLBG\"><FONT COLOR=\"$THFG\" SIZE=\"-1\">".$user_nospace."</FONT></TD>\n";
    $col = 1;

    for($j=$first_hour;$j<$last_hour;$j++) {
       for($k=0;$k<$interval;$k++) {
         $space = ($k == '0') ? '<img src="pix.gif" HEIGHT="12" WIDTH="1" align="middle">' : "&nbsp;";
	 $RC = $CELLBG;
         $TIME = mktime ( sprintf ( "%02d",$j), ($increment * $k), 0, $thismonth, $thisday, $thisyear );

         foreach ($all_events as $ET) {
          if (($TIME >= $ET['START']) && ($TIME < $ET['END'])) {
    	    if ($space == "&nbsp;") {
            $space="<a href=\"view_entry.php?id={$ET['ID']}\"><img src=\"pix.gif\" HEIGHT=\"8\" WIDTH=\"100%\" ALIGN=\"middle\" BORDER=0></a>";
            } else {
            $space="<a href=\"view_entry.php?id={$ET['ID']}\"><img src=\"pix.gif\" HEIGHT=\"12\" WIDTH=\"1\" align=\"middle\" BORDER=0><img src=\"pix.gif\" HEIGHT=\"8\" WIDTH=\"100%\" ALIGN=\"middle\" BORDER=0></a>";
            }
	    break;
	  }
	 }
         echo "   <TD ALIGN=\"left\" BGCOLOR=\"$RC\" VALIGN=\"MIDDLE\" WIDTH=\"$cell_pix\">$space</TD>\n";
         $col++;
       }
    }
    echo " </TR>\n <TR><TD HEIGHT=\"1\" COLSPAN=\"$cols\" BGCOLOR=\"black\"><img src=\"pix.gif\" HEIGHT=\"1\" WIDTH=\"100%\"></TD></TR>\n";
  } // End foreach participant
  echo "</TABLE></TD></TR></TABLE>\n";

} // end TimeMatrix function
?>
