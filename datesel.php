<?php php_track_vars?>
<?php

include "includes/config.inc";
include "includes/php-dbi.inc";
include "includes/functions.inc";
include "includes/validate.inc";
include "includes/connect.inc";

load_user_preferences ();
load_user_layers ();

include "includes/translate.inc";

?>
<HTML>
<HEAD>
<TITLE><?php etranslate("Title")?></TITLE>
<SCRIPT LANGUAGE="JavaScript">
function sendDate ( date ) {
  year = date.substring ( 0, 4 );
  month = date.substring ( 4, 6 );
  day = date.substring ( 6, 8 );
  window.opener.document.<?php echo $form?>.<?php echo $day?>.selectedIndex = day - 1;
  window.opener.document.<?php echo $form?>.<?php echo $month?>.selectedIndex = month - 1;
  for ( i = 0; i < window.opener.document.<?php echo $form?>.<?php echo $year?>.length; i++ ) {
    if ( window.opener.document.<?php echo $form?>.<?php echo $year?>.options[i].value == year ) {
      window.opener.document.<?php echo $form?>.<?php echo $year?>.selectedIndex = i;
    }
  }
  window.close ();
}
</SCRIPT>
<?php include "includes/styles.inc"; ?>
</HEAD>
<BODY BGCOLOR="<?php echo $BGCOLOR;?>">
<CENTER>

<?php
if ( strlen ( $date ) > 0 ) {
  $thisyear = substr ( $date, 0, 4 );
  $thismonth = substr ( $date, 4, 2 );
} else {
  $thismonth = date("m");
  $thisyear = date("Y");
}

$next = mktime ( 2, 0, 0, $thismonth + 1, 1, $thisyear );
$nextyear = date ( "Y", $next );
$nextmonth = date ( "m", $next );
$nextdate = date ( "Ym", $next ) . "01";

$prev = mktime ( 2, 0, 0, $thismonth - 1, 1, $thisyear );
$prevyear = date ( "Y", $prev );
$prevmonth = date ( "m", $prev );
$prevdate = date ( "Ym", $prev ) . "01";

?>

<TABLE BORDER=0>
<TR>
<TD><A HREF="datesel.php?form=<?php echo $form?>&day=<?php echo $day?>&month=<?php echo $month?>&year=<?php echo $year?>&date=<?php echo $prevdate?>">&lt;</A></TD>
<TH COLSPAN="5"><?php echo month_name ( $thismonth - 1 ) . " " . $thisyear;?></TH>
<TD><A HREF="datesel.php?form=<?php echo $form?>&day=<?php echo $day?>&month=<?php echo $month?>&year=<?php echo $year?>&date=<?php echo $nextdate?>">&gt;</A></TD>
</TR>
<?php
echo "<TR>";
if ( $WEEK_START == 0 ) echo "<TD><FONT SIZE=\"-1\">" .
  weekday_short_name ( 0 ) . "</TD>";
for ( $i = 1; $i < 7; $i++ ) {
  echo "<TD><FONT SIZE=\"-1\">" .
    weekday_short_name ( $i ) . "</TD>";
}
if ( $WEEK_START == 1 ) echo "<TD><FONT SIZE=\"-1\">" .
  weekday_short_name ( 0 ) . "</TD>";
echo "</TR>\n";
if ( $WEEK_START == "1" )
  $wkstart = get_monday_before ( $thisyear, $thismonth, 1 );
else
  $wkstart = get_sunday_before ( $thisyear, $thismonth, 1 );
$monthstart = mktime ( 0, 0, 0, $thismonth, 1, $thisyear );
$monthend = mktime ( 0, 0, 0, $thismonth + 1, 0, $thisyear );
for ( $i = $wkstart; date ( "Ymd", $i ) <= date ( "Ymd", $monthend );
  $i += ( 24 * 3600 * 7 ) ) {
  echo "<TR>\n";
  for ( $j = 0; $j < 7; $j++ ) {
    $date = $i + ( $j * 24 * 3600 );
    if ( date ( "Ymd", $date ) >= date ( "Ymd", $monthstart ) &&
      date ( "Ymd", $date ) <= date ( "Ymd", $monthend ) ) {
      echo "<TD><A HREF=\"javascript:sendDate('" .
        date ( "Ymd", $date ) . "')\">" .
        date ( "d", $date ) . "</A></TD>";
    } else {
      echo "<TD></TD>\n";
    }
  }
  echo "</TR>\n";
}
?>
</TABLE>

</CENTER>

</BODY>
</HTML>
