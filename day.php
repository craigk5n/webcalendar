<?php php_track_vars?>
<?php

include "includes/config.inc";
include "includes/php-dbi.inc";
include "includes/functions.inc";
include "includes/$user_inc";
include "includes/validate.inc";
include "includes/connect.inc";

load_user_preferences ();
load_user_layers ();

include "includes/translate.inc";

if ( ! $allow_view_other && ! $is_admin )
  $user = "";

$view = "day";

if ( strlen ( $user ) ) {
  $u_url = "user=$user&";
  user_load_variables ( $user, "user_" );
} else {
  $u_url = "";
  $user_fullname = $fullname;
}
?>
<HTML>
<HEAD>
<TITLE><?php etranslate("Title")?></TITLE>
<?php include "includes/styles.inc"; ?>
<?php include "includes/js.inc"; ?>
</HEAD>
<BODY BGCOLOR=<?php echo "\"$BGCOLOR\"";?>>

<?php
if ( strlen ( $date ) > 0 ) {
  $thisyear = substr ( $date, 0, 4 );
  $thismonth = substr ( $date, 4, 2 );
  $thisday = substr ( $date, 6, 2 );
} else {
  if ( $month == 0 )
    $thismonth = date("m");
  else
    $thismonth = $month;
  if ( $year == 0 )
    $thisyear = date("Y");
  else
    $thisyear = $year;
  if ( $day == 0 )
    $thisday = date("d");
  else
    $thisday = $day;
}
$wday = strftime ( "%w", mktime ( 2, 0, 0, $thismonth, $thisday, $thisyear ) );

$now = mktime ( 2, 0, 0, $thismonth, $thisday, $thisyear );
$nowYmd = date ( "Ymd", $now );

$next = mktime ( 2, 0, 0, $thismonth, $thisday + 1, $thisyear );
$nextyear = date ( "Y", $next );
$nextmonth = date ( "m", $next );
$nextday = date ( "d", $next );
$month_ago = date ( "Ymd", mktime ( 2, 0, 0, $thismonth - 1, $thisday, $thisyear ) );

$prev = mktime ( 2, 0, 0, $thismonth, $day - 1, $thisyear );
$prevyear = date ( "Y", $prev );
$prevmonth = date ( "m", $prev );
$prevday = date ( "d", $prev );
$month_ahead = date ( "Ymd", mktime ( 2, 0, 0, $thismonth + 1, $thisday, $thisyear ) );

/* Pre-Load the repeated events for quckier access */
$repeated_events = read_repeated_events ( strlen ( $user ) ? $user : $login );

/* Pre-load the non-repeating events for quicker access */
$events = read_events ( strlen ( $user ) ? $user : $login, $nowYmd, $nowYmd );

?>

<TABLE BORDER="0" WIDTH="100%">
<TR><TD VALIGN="top" WIDTH="70%"><TR><TD>
<TABLE BORDER="0" WIDTH="100%">
<TR>
<TD ALIGN="middle"><FONT SIZE="+2" COLOR="<?php echo $H2COLOR;?>"><B>
<?php
  printf ( "%s, %s %d, %d", weekday_name ( $wday ),
    month_name ( $thismonth - 1 ), $thisday, $thisyear );
?>
</B></FONT>
<FONT SIZE="+1" COLOR="<?php echo $H2COLOR;?>">
<?php
  // display current calendar's user (if not in single user)
  if ( ! $single_user ) {
    echo "<BR>";
    echo $user_fullname;
  }
?>
</FONT>
</TD>
</TR>
</TABLE>

<TABLE BORDER="0" WIDTH="100%" CELLSPACING="0" CELLPADDING="0">
<TR><TD BGCOLOR="<?php echo $TABLEBG?>">
<TABLE BORDER="0" WIDTH="100%" CELLSPACING="1" CELLPADDING="2">

<?php

print_day_at_a_glance ( date ( "Ymd", $now ),
  strlen ( $user ) > 0 ? $user : $login, $friendly );

?>
</TR>

</TABLE>
</TD></TR></TABLE>
</TD>
<TD VALIGN="top">
<?php if ( ! $friendly ) { ?>
<DIV ALIGN="right">
<TABLE BORDER="0" CELLSPACING="0" CELLPADDING="0">
<TR><TD BGCOLOR="<?php echo $TABLEBG?>">
<TABLE BORDER="0" WIDTH="100%" CELLSPACING="1" CELLPADDING="2">
<TR><TH COLSPAN="7" BGCOLOR="<?php echo $THBG?>"><FONT SIZE="+4" COLOR="<?php echo $THFG?>"><?php echo $thisday?></FONT></TH></TR>
<TR>
<TD ALIGN="left" BGCOLOR="<?php echo $THBG?>"><A HREF="day.php?<?php echo $u_url; ?>date=<?php echo $month_ago?>" CLASS="monthlink">&lt;</A></TD>
<TH COLSPAN="5" BGCOLOR="<?php echo $THBG?>"><FONT COLOR="<?php echo $THFG?>"><?php echo month_name ( $thismonth - 1 ) . " $thisyear"?></FONT></TH>
<TD ALIGN="right" BGCOLOR="<?php echo $THBG?>"><A HREF="day.php?<?php echo $u_url; ?>date=<?php echo $month_ahead?>" CLASS="monthlink">&gt;</A></TD>
</TR>
<?php
echo "<TR>";
if ( $WEEK_START == 0 ) echo "<TD BGCOLOR=\"$CELLBG\"><FONT SIZE=\"-3\">" .
  weekday_short_name ( 0 ) . "</TD>";
for ( $i = 1; $i < 7; $i++ ) {
  echo "<TD BGCOLOR=\"$CELLBG\"><FONT SIZE=\"-3\">" .
    weekday_short_name ( $i ) . "</TD>";
}
if ( $WEEK_START == 1 ) echo "<TD BGCOLOR=\"$CELLBG\"><FONT SIZE=\"-3\">" .
  weekday_short_name ( 0 ) . "</TD>";
echo "</TR>\n";
// generate values for first day and last day of month
$monthstart = mktime ( 2, 0, 0, $thismonth, 1, $thisyear );
$monthend = mktime ( 2, 0, 0, $thismonth + 1, 0, $thisyear );
if ( $WEEK_START == "1" )
  $wkstart = get_monday_before ( $thisyear, $thismonth, 1 );
else
  $wkstart = get_sunday_before ( $thisyear, $thismonth, 1 );
$wkend = $wkstart + ( 3600 * 24 * 7 );

for ( $i = $wkstart; date ( "Ymd", $i ) <= date ( "Ymd", $monthend );
  $i += ( 24 * 3600 * 7 ) ) {
  for ( $i = $wkstart; date ( "Ymd", $i ) <= date ( "Ymd", $monthend );
    $i += ( 24 * 3600 * 7 ) ) {
    echo "<TR ALIGN=\"center\">\n";
    for ( $j = 0; $j < 7; $j++ ) {
      $date = $i + ( $j * 24 * 3600 );
      if ( date ( "Ymd", $date ) >= date ( "Ymd", $monthstart ) &&
        date ( "Ymd", $date ) <= date ( "Ymd", $monthend ) ) {
        if ( date ( "Ymd", $date ) == date ( "Ymd", $now ) )
          echo "<TD BGCOLOR=\"$TODAYCELLBG\">";
        else
          echo "<TD BGCOLOR=\"$CELLBG\">";
        echo "<FONT SIZE=\"-2\">";
        echo "<A HREF=\"day.php?";
        echo $u_url;
        echo "date=" . date ( "Ymd", $date ) . "\" CLASS=\"monthlink\">" .
         date ( "d", $date ) .
         "</A></FONT></TD>\n";
      } else {
        print "<TD BGCOLOR=\"$CELLBG\">&nbsp;</TD>\n";
      }
    }
    echo "</TR>\n";
  }
}
?>
</TABLE>
</TD></TR></TABLE>
</DIV>
<?php } ?>
</TD></TR></TABLE>

<P>

<?php echo $eventinfo; ?>

<?php if ( ! $friendly ) {

  display_unapproved_events ( $login );

?>

<P>
<A HREF="day.php?<?php
  echo $u_url;
  if ( $thisyear ) {
    echo "year=$thisyear&month=$thismonth&day=$thisday&";
  }
?>friendly=1" TARGET="cal_printer_friendly"
onMouseOver="window.status = '<?php etranslate("Generate printer-friendly version")?>'">[<?php etranslate("Printer Friendly")?>]</A>

<?php include "includes/trailer.inc"; ?>

<?php } ?>

</BODY>
</HTML>
