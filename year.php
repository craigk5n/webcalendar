<?php
include_once 'includes/init.php';
send_no_cache_header ();

if (($user != $login) && $is_nonuser_admin)
  load_user_layers ($user);
else
  load_user_layers ();

function display_small_month ( $thismonth, $thisyear, $showyear ) {
  global $WEEK_START, $user, $login, $boldDays, $get_unapproved, $friendly;

  if ( $user != $login && ! empty ( $user ) )
    $u_url = "&amp;user=$user";
  else
    $u_url = "";

  echo "<table class=\"yearviewminical\" cellpadding=\"1\" cellspacing=\"2\">\n";
  if ( $WEEK_START == "1" )
    $wkstart = get_monday_before ( $thisyear, $thismonth, 1 );
  else
    $wkstart = get_sunday_before ( $thisyear, $thismonth, 1 );

  $monthstart = mktime(2,0,0,$thismonth,1,$thisyear);
  $monthend = mktime(2,0,0,$thismonth + 1,0,$thisyear);
  echo "<tr><td colspan=\"7\" class=\"month\">";
  if ( empty ( $friendly ) ) {
     echo "<a href=\"month.php?year=$thisyear&amp;month=$thismonth"
       . $u_url . "\">";
  }
  echo month_name ( $thismonth - 1 ) .
    ( empty ( $friendly ) ? "</a>" : "" ) . "</td></tr>\n";
  echo "<tr class=\"day\">";
  if ( $WEEK_START == 0 ) echo "<th>" .
    weekday_short_name ( 0 ) . "</th>\n";
  for ( $i = 1; $i < 7; $i++ ) {
    echo "<th>" .
      weekday_short_name ( $i ) . "</th>\n";
  }
  if ( $WEEK_START == 1 ) echo "<th>" .
    weekday_short_name ( 0 ) . "</th>\n";
  echo "</tr>\n";
  for ($i = $wkstart; date("Ymd",$i) <= date ("Ymd",$monthend);
    $i += (24 * 3600 * 7) ) {
    echo "<tr class=\"numdate\">";
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
        echo "<td";
        echo ( $hasEvents ? " class=\"highlight\">" : ">" );
        if ( empty ( $friendly ) )
	  echo "<a href=\"day.php?date=" .  $dateYmd . $u_url .  "\">";
	echo date ( "j", $date );
        if ( empty ( $friendly ) )
          echo "</a>";
        echo "</td>\n";
      } else {
        echo "<td>&nbsp;</td>\n";
      }
    }                 // end for $j
    echo "</tr>\n";
  }                         // end for $i
  echo "</table>\n";
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
<td style="text-align:left;">
<a title="<?php etranslate("Previous")?>" href="year.php?year=<?php echo $prevYear; if ( ! empty ( $user ) ) echo "&amp;user=$user";?>"><img src="leftarrow.gif" class="prevnext" alt="<?php etranslate("Previous")?>" /></a>
</td>
<?php } ?>
<td class="yearviewtitle">
	<span class="date"><?php echo $thisyear ?></span>
<span class="user">
<?php
  if ( $single_user == "N" ) {
    echo "<br />\n";
    if ( ! empty ( $user ) ) {
      user_load_variables ( $user, "user_" );
      echo $user_fullname;
    } else
      echo $fullname;
    if ( $is_assistant )
      echo "<br /><span style=\"font-weight:bold;\">-- " . translate("Assistant mode") . " --</span>";
  }
?>
</span></td>
<?php if ( empty ( $friendly ) ) {?>
<td style="text-align:right;">
<a title="<?php etranslate("Next")?>" href="year.php?year=<?php echo $nextYear; if ( ! empty ( $user ) ) echo "&amp;user=$user";?>"><img src="rightarrow.gif" class="prevnext" alt="<?php etranslate("Next")?>" /></a>
</td>
<?php } ?>
</tr>
</table>

<div align="center">
<table class="yearview" cellspacing="1" cellpadding="2">
<tr>
<td><?php display_small_month(1,$year,False); ?></td>
<td><?php display_small_month(2,$year,False); ?></td>
<td><?php display_small_month(3,$year,False); ?></td>
<td><?php display_small_month(4,$year,False); ?></td>
</tr>
<tr>
<td><?php display_small_month(5,$year,False); ?></td>
<td><?php display_small_month(6,$year,False); ?></td>
<td><?php display_small_month(7,$year,False); ?></td>
<td><?php display_small_month(8,$year,False); ?></td>
</tr>
<tr>
<td><?php display_small_month(9,$year,False); ?></td>
<td><?php display_small_month(10,$year,False); ?></td>
<td><?php display_small_month(11,$year,False); ?></td>
<td><?php display_small_month(12,$year,False); ?></td>
</tr>
</table>
</div>

<br />
<?php if ( empty ( $friendly ) ) {

display_unapproved_events ( $login );

?>
<br />
<a class="navlinks" href="year.php?<?php
  if ( $thisyear )
    echo "year=$thisyear&amp;";
  if ( $user != $login && ! empty ( $user ) )
    echo "user=$user&amp;";
?>friendly=1" target="cal_printer_friendly"
onmouseover="window.status = '<?php etranslate("Generate printer-friendly version")?>'">[<?php etranslate("Printer Friendly")?>]</a>

<?php }
print_trailer();
?>

</body>
</html>
