<?php
include_once 'includes/init.php';
send_no_cache_header ();

if (($user != $login) && $is_nonuser_admin)
  load_user_layers ($user);
else
  load_user_layers ();

function display_small_month ( $thismonth, $thisyear, $showyear ) {
  global $WEEK_START, $user, $login, $boldDays, $get_unapproved;

  if ( $user != $login && ! empty ( $user ) )
    $u_url = "&user=$user";
  else
    $u_url = "";

  echo "<table style=\"border-width:0px;\" cellpadding=\"1\" cellspacing=\"2\">";
  if ( $WEEK_START == "1" )
    $wkstart = get_monday_before ( $thisyear, $thismonth, 1 );
  else
    $wkstart = get_sunday_before ( $thisyear, $thismonth, 1 );

  $monthstart = mktime(2,0,0,$thismonth,1,$thisyear);
  $monthend = mktime(2,0,0,$thismonth + 1,0,$thisyear);
  echo "<tr><td colspan=\"7\" style=\"text-align:center;\">"
     . "<a href=\"month.php?year=$thisyear&month=$thismonth"
     . $u_url . "\" class=\"monthlink\">";
  echo month_name ( $thismonth - 1 ) .
    "</a></td></tr>";
  echo "<tr>";
  if ( $WEEK_START == 0 ) echo "<td><font size=\"-3\">" .
    weekday_short_name ( 0 ) . "</td>";
  for ( $i = 1; $i < 7; $i++ ) {
    echo "<td><font size=\"-3\">" .
      weekday_short_name ( $i ) . "</td>";
  }
  if ( $WEEK_START == 1 ) echo "<td><font size=\"-3\">" .
    weekday_short_name ( 0 ) . "</td>";
  for ($i = $wkstart; date("Ymd",$i) <= date ("Ymd",$monthend);
    $i += (24 * 3600 * 7) ) {
    echo "<tr>";
    for ($j = 0; $j < 7; $j++) {
      $date = $i + ($j * 24 * 3600);
      $dateYmd = date ( "Ymd", $date );
      $hasEvents = false;
      if ( $boldDays ) {
        $ev = get_entries ( $user, $dateYmd, $get_unapproved );
        if ( count ( $ev ) > 0 ) {
          $hasEvents = true;
        } else {
          $rep = get_repeating_entries ( $user, $dateYmd, $get_unapproved );
          if ( count ( $rep ) > 0 )
            $hasEvents = true;
        }
      }
      if ( $dateYmd >= date ("Ymd",$monthstart) &&
        $dateYmd <= date ("Ymd",$monthend) ) {
        echo "<td style=\"text-align:right;\"><a href=\"day.php?date=" .
          $dateYmd . $u_url .
          "\" class=\"dayofmonthyearview\">";
        echo "<font size=\"-1\">" .
          ( $hasEvents ? "<span style=\"font-weight:bold;\">" : "" ) .
          date ( "j", $date ) .
          ( $hasEvents ? "</span>" : "" ) .
          "</a></font></td>";
      } else
        echo "<td>&nbsp;</td>";
    }                 // end for $j
    echo "</tr>";
  }                         // end for $i
  echo "</table>";
}

if ( empty ( $year ) )
  $year = date("Y");

$thisyear = $year;
if ( $year != date ( "Y") )
  $thismonth = 1;

if ( $year > "1903" )
  $prevYear = $year - 1;
else
  $prevYear=$year;

$nextYear= $year + 1;

if ( $allow_view_other != "Y" && ! $is_admin )
  $user = "";

$boldDays = false;
if ( ! empty ( $bold_days_in_year ) && $bold_days_in_year == 'Y' ) {
  /* Pre-Load the repeated events for quckier access */
  $repeated_events = read_repeated_events (
    ( ! empty ( $user ) && strlen ( $user ) ) ? $user : $login, $cat_id );

  /* Pre-load the non-repeating events for quicker access */
  $events = read_events ( ( ! empty ( $user ) && strlen ( $user ) )
    ? $user : $login, $year . "0101", $year . "1231", $cat_id );
  $boldDays = true;
}

// Include unapproved events?
$get_unapproved = ( $DISPLAY_UNAPPROVED == 'Y' );
if ( $user == "__public__" )
  $get_unapproved = false;

print_header();
?>

<table style="width:100%;">
<tr>
<?php if ( empty ( $friendly ) ) { ?>
<td style="text-align:left;"><font size="-1">
<a href="year.php?year=<?php echo $prevYear; if ( ! empty ( $user ) ) echo "&user=$user";?>" class="monthlink"><img src="leftarrow.gif" style="width:36px; height:32px; border:0px;" alt="<?php etranslate("Previous")?>" /></A>
</font></td>
<?php } ?>
<td style="text-align:center; color: <?php echo $H2COLOR?>;">
<font size="+2"><b>
<?php echo $thisyear ?>
</b></font>
<font size="+1">
<?php
  if ( $single_user == "N" ) {
    echo "<br />\n";
    if ( ! empty ( $user ) ) {
      user_load_variables ( $user, "user_" );
      echo $user_fullname;
    } else
      echo $fullname;
    if ( $is_assistant )
      echo "<br /><b>-- " . translate("Assistant mode") . " --</b>";
  }
?>
</font></td>
<?php if ( empty ( $friendly ) ) {?>
<td style="text-align:right;">
<a href="year.php?year=<?php echo $nextYear; if ( ! empty ( $user ) ) echo "&user=$user";?>" class="monthlink"><img src="rightarrow.gif" style="width:36px; height:32px; border:0px;" alt="<?php etranslate("Next")?>" /></a>
</font></td>
<?php } ?>
</tr>
</table>

<div style="text-align:center;">
<table style="border-width:0px;" align="center" cellspacing="4" cellpadding="4">
<tr>
<td style="vertical-align:top;"><?php display_small_month(1,$year,False); ?></td>
<td style="vertical-align:top;"><?php display_small_month(2,$year,False); ?></td>
<td style="vertical-align:top;"><?php display_small_month(3,$year,False); ?></td>
<td style="vertical-align:top;"><?php display_small_month(4,$year,False); ?></td>
</tr>
<tr>
<td style="vertical-align:top;"><?php display_small_month(5,$year,False); ?></td>
<td style="vertical-align:top;"><?php display_small_month(6,$year,False); ?></td>
<td style="vertical-align:top;"><?php display_small_month(7,$year,False); ?></td>
<td style="vertical-align:top;"><?php display_small_month(8,$year,False); ?></td>
</tr>
<tr>
<td style="vertical-align:top;"><?php display_small_month(9,$year,False); ?></td>
<td style="vertical-align:top;"><?php display_small_month(10,$year,False); ?></td>
<td style="vertical-align:top;"><?php display_small_month(11,$year,False); ?></td>
<td style="vertical-align:top;"><?php display_small_month(12,$year,False); ?></td>
</tr>
</table>
</div>

<br /><br />

<?php if ( empty ( $friendly ) ) {

display_unapproved_events ( $login );

?>
<br /><br />
<a class="navlinks" href="year.php?<?php
  if ( $thisyear )
    echo "year=$thisyear&";
  if ( $user != $login && ! empty ( $user ) )
    echo "user=$user&";
?>friendly=1" target="cal_printer_friendly"
onmouseover="window.status = '<?php etranslate("Generate printer-friendly version")?>'">[<?php etranslate("Printer Friendly")?>]</a>

<?php }
print_trailer();
?>

</body>
</html>
