<?php

/*
 * $Id$
 *
 * Page Description:
 *	This page will display the month "view" with all users's events
 *	on the same calendar.  (The other month "view" displays each user
 *	calendar in a separate column, side-by-side.)  This view gives you
 *	the same effect as enabling layers, but with layers you can only
 *	have one configuration of users.
 *
 * Input Parameters:
 *	id (*) - specify view id in webcal_view table
 *	date - specify the starting date of the view.
 *	  If not specified, current date will be used.
 *	friendly - if set to 1, then page does not include links or
 *	  trailer navigation.
 *	(*) required field
 *
 * Security:
 *	Must have "allow view others" enabled ($allow_view_other) in
 *	  System Settings unless the user is an admin user ($is_admin).
 *	Must be owner of the view.
 *
 */


include_once 'includes/init.php';

$error = "";

if ( $allow_view_other == "N" && ! $is_admin ) {
  // not allowed...
  do_redirect ( "$STARTVIEW.php" );
}
if ( empty ( $id ) ) {
  do_redirect ( "views.php" );
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

// If view_name not found, then the specified view id does not
// belong to current user. 
if ( empty ( $view_name ) ) {
  $error = translate ( "You are not authorized" );
}

$INC = array('js/popups.php');
print_header($INC);

set_today($date);

$next = mktime ( 3, 0, 0, $thismonth + 1, 1, $thisyear );
$nextyear = date ( "Y", $next );
$nextmonth = date ( "m", $next );
$nextdate = sprintf ( "%04d%02d01", $nextyear, $nextmonth );

$prev = mktime ( 3, 0, 0, $thismonth - 1, 1, $thisyear );
$prevyear = date ( "Y", $prev );
$prevmonth = date ( "m", $prev );
$prevdate = sprintf ( "%04d%02d01", $prevyear, $prevmonth );

$startdate = sprintf ( "%04d%02d01", $thisyear, $thismonth );
$enddate = sprintf ( "%04d%02d31", $thisyear, $thismonth );
$monthstart = mktime ( 3, 0, 0, $thismonth, 1, $thisyear );
$monthend = mktime ( 3, 0, 0, $thismonth + 1, 0, $thisyear );

// get users in this view
$res = dbi_query (
  "SELECT cal_login FROM webcal_view_user WHERE cal_view_id = $id" );
$viewusers = array ();
if ( $res ) {
  while ( $row = dbi_fetch_row ( $res ) ) {
    $viewusers[] = $row[0]; 
  }
  dbi_free_result ( $res );
} else {
  $error = translate ( "Database error" ) . ": " . dbi_error ();
}
if ( count ( $viewusers ) == 0 ) {
  // no need to translate the following since it should not happen
  // unless the db gets screwed up.
  $error = "No users for this view";
}

if ( ! empty ( $error ) ) {
  echo "<h2><font color=\"$H2COLOR\">" . translate ( "Error" ) .
    "</font></h2>\n" . $error;
  include_once "includes/trailer.php";
  exit;
}

$e_save = array ();
$re_save = array ();
for ( $i = 0; $i < count ( $viewusers ); $i++ ) {
  /* Pre-Load the repeated events for quckier access */
  $repeated_events = read_repeated_events ( $viewusers[$i] ); 
  $re_save = array_merge($re_save, $repeated_events);
  /* Pre-load the non-repeating events for quicker access */
  $events = read_events ( $viewusers[$i], $startdate, $enddate );
  $e_save = array_merge($e_save, $events);
} 
$events = array ();
$repeated_events = array ();

for ( $i = 0; $i < count ( $e_save ); $i++ ) {
  $should_add = 1;
  for ( $j = 0; $j < count ( $events ) && $should_add; $j++ ) {
    if ( $e_save[$i]['cal_id'] == $events[$j]['cal_id'] ) {
      $should_add = 0;
    }
  }
  if ( $should_add ) {
    array_push ( $events, $e_save[$i] );
  }
}

for ( $i = 0; $i < count ( $re_save ); $i++ ) {
  $should_add = 1;
  for ( $j = 0; $j < count ( $repeated_events ) && $should_add; $j++ ) {
    if ( $re_save[$i]['cal_id'] == $repeated_events[$j]['cal_id'] ) {
      $should_add = 0;
    }
  }
  if ( $should_add ) {
    array_push ( $repeated_events, $re_save[$i] );
  }
}
?>

<TABLE BORDER="0" WIDTH="100%">
<TR>
<?php
if ( ! $friendly ) {
  echo '<TD ALIGN="left"><TABLE BORDER=0>';
  if ( $WEEK_START == "1" )
    $wkstart = get_monday_before ( $prevyear, $prevmonth, 1 );
  else
    $wkstart = get_sunday_before ( $prevyear, $prevmonth, 1 );
  $monthstart = mktime ( 3, 0, 0, $prevmonth, 1, $prevyear );
  $monthend = mktime ( 3, 0, 0, $prevmonth + 1, 0, $prevyear );
  echo "<TR><TD COLSPAN=7 ALIGN=\"middle\"><FONT SIZE=\"-1\">" .
    "<A HREF=\"view_l.php?id=$id&date=$prevdate\" CLASS=\"monthlink\">" .
    date_to_str ( sprintf ( "%04d%02d01", $prevyear, $prevmonth ),
    $DATE_FORMAT_MY, false, false ) .
    "</A></FONT></TD></TR>\n";
  echo "<TR>";
  if ( $WEEK_START == 0 ) echo "<TD><FONT SIZE=\"-2\">" .
    weekday_short_name ( 0 ) . "</TD>";
  for ( $i = 1; $i < 7; $i++ ) {
    echo "<TD><FONT SIZE=\"-2\">" .
      weekday_short_name ( $i ) . "</TD>";
  }
  if ( $WEEK_START == 1 ) echo "<TD><FONT SIZE=\"-2\">" .
    weekday_short_name ( 0 ) . "</TD>";
  echo "</TR>\n";
  for ( $i = $wkstart; date ( "Ymd", $i ) <= date ( "Ymd", $monthend );
    $i += ( 24 * 3600 * 7 ) ) {
    print "<TR>\n";
    for ( $j = 0; $j < 7; $j++ ) {
      $date = $i + ( $j * 24 * 3600 );
      if ( date ( "Ymd", $date ) >= date ( "Ymd", $monthstart ) &&
        date ( "Ymd", $date ) <= date ( "Ymd", $monthend ) ) {
        print "<TD><FONT SIZE=\"-2\">" . date ( "d", $date ) . "</FONT></TD>\n";
      } else {
        print "<TD></TD>\n";
      }
    }
    print "</TR>\n";
  }
  echo "</TABLE></TD>\n";
}

?>
<TD ALIGN="middle">
<FONT SIZE="+2" COLOR="<?php echo $H2COLOR?>">
<B>
<?php
  echo date_to_str ( sprintf ( "%04d%02d01", $thisyear, $thismonth ),
    $DATE_FORMAT_MY, false, false );
?>
</B></FONT>
<FONT COLOR="<?php echo $H2COLOR?>" SIZE="+1">
<?php
    echo "<BR>\n";
    echo $view_name;
?>
</FONT></TD>
<?php
if ( ! $friendly ) {
  echo '<TD ALIGN="right"><TABLE BORDER=0>';
  if ( $WEEK_START == "1" )
    $wkstart = get_monday_before ( $nextyear, $nextmonth, 1 );
  else
    $wkstart = get_sunday_before ( $nextyear, $nextmonth, 1 );
  $monthstart = mktime ( 3, 0, 0, $nextmonth, 1, $nextyear );
  $monthend = mktime ( 3, 0, 0, $nextmonth + 1, 0, $nextyear );
  echo "<TR><TD COLSPAN=7 ALIGN=\"middle\"><FONT SIZE=\"-1\">" .
    "<A HREF=\"view_l.php?id=$id&date=$nextdate\" CLASS=\"monthlink\">" .
    date_to_str ( sprintf ( "%04d%02d01", $nextyear, $nextmonth ),
    $DATE_FORMAT_MY, false, false ) .
    "</A></FONT></TD></TR>\n";
  echo "<TR>";
  if ( $WEEK_START == 0 ) echo "<TD><FONT SIZE=\"-2\">" .
    weekday_short_name ( 0 ) . "</TD>";
  for ( $i = 1; $i < 7; $i++ ) {
    echo "<TD><FONT SIZE=\"-2\">" .
      weekday_short_name ( $i ) . "</TD>";
  }
  if ( $WEEK_START == 1 ) echo "<TD><FONT SIZE=\"-2\">" .
    weekday_short_name ( 0 ) . "</TD>";
  echo "</TR>\n";
  for ( $i = $wkstart; date ( "Ymd", $i ) <= date ( "Ymd", $monthend );
    $i += ( 24 * 3600 * 7 ) ) {
    print "<TR>\n";
    for ( $j = 0; $j < 7; $j++ ) {
      $date = $i + ( $j * 24 * 3600 );
      if ( date ( "Ymd", $date ) >= date ( "Ymd", $monthstart ) &&
        date ( "Ymd", $date ) <= date ( "Ymd", $monthend ) ) {
        print "<TD><FONT SIZE=\"-2\">" . date ( "d", $date ) . "</FONT></TD>\n";
      } else {
        print "<TD></TD>\n";
      }
    }
    print "</TR>\n";
  }
  echo "</TABLE></TD>\n";
}

?>
</TR>
</TABLE>

<?php if ( empty ( $friendly ) || ! $friendly ) { ?>
<TABLE BORDER="0" WIDTH="100%" CELLSPACING="0" CELLPADDING="0">
<TR><TD BGCOLOR="<?php echo $TABLEBG?>">
<TABLE BORDER="0" WIDTH="100%" CELLSPACING="1" CELLPADDING="2">
<?php } else { ?> 
<TABLE BORDER="1" WIDTH="100%" CELLSPACING="0" CELLPADDING="0">
<?php } ?>

<TR>
<?php if ( $WEEK_START == 0 ) { ?>
<TH WIDTH="14%" CLASS="tableheader" BGCOLOR="<?php echo $THBG?>"><FONT COLOR="<?php echo $THFG?>"><?php etranslate("Sun")?></FONT></TH>
<?php } ?>
<TH WIDTH="14%" CLASS="tableheader" BGCOLOR="<?php echo $THBG?>"><FONT COLOR="<?php echo $THFG?>"><?php etranslate("Mon")?></FONT></TH>
<TH WIDTH="14%" CLASS="tableheader" BGCOLOR="<?php echo $THBG?>"><FONT COLOR="<?php echo $THFG?>"><?php etranslate("Tue")?></FONT></TH>
<TH WIDTH="14%" CLASS="tableheader" BGCOLOR="<?php echo $THBG?>"><FONT COLOR="<?php echo $THFG?>"><?php etranslate("Wed")?></FONT></TH>
<TH WIDTH="14%" CLASS="tableheader" BGCOLOR="<?php echo $THBG?>"><FONT COLOR="<?php echo $THFG?>"><?php etranslate("Thu")?></FONT></TH>
<TH WIDTH="14%" CLASS="tableheader" BGCOLOR="<?php echo $THBG?>"><FONT COLOR="<?php echo $THFG?>"><?php etranslate("Fri")?></FONT></TH>
<TH WIDTH="14%" CLASS="tableheader" BGCOLOR="<?php echo $THBG?>"><FONT COLOR="<?php echo $THFG?>"><?php etranslate("Sat")?></FONT></TH>
<?php if ( $WEEK_START == 1 ) { ?>
<TH WIDTH="14%" CLASS="tableheader" BGCOLOR="<?php echo $THBG?>"><FONT COLOR="<?php echo $THFG?>"><?php etranslate("Sun")?></FONT></TH>
<?php } ?>
</TR>


<?php

// We add 2 hours on to the time so that the switch to DST doesn't
// throw us off.  So, all our dates are 2AM for that day.
//$sun = get_sunday_before ( $thisyear, $thismonth, 1 );
if ( $WEEK_START == 1 )
  $wkstart = get_monday_before ( $thisyear, $thismonth, 1 );
else
  $wkstart = get_sunday_before ( $thisyear, $thismonth, 1 );
// generate values for first day and last day of month
$monthstart = mktime ( 3, 0, 0, $thismonth, 1, $thisyear );
$monthend = mktime ( 3, 0, 0, $thismonth + 1, 0, $thisyear );

// debugging
//echo "<P>sun = " . date ( "D, m-d-Y", $sun ) . "<BR>";
//echo "<P>monthstart = " . date ( "D, m-d-Y", $monthstart ) . "<BR>";
//echo "<P>monthend = " . date ( "D, m-d-Y", $monthend ) . "<BR>";

// NOTE: if you make HTML changes to this table, make the same changes
// to the example table in pref.php.
for ( $i = $wkstart; date ( "Ymd", $i ) <= date ( "Ymd", $monthend );
  $i += ( 24 * 3600 * 7 ) ) {
  print "<TR>\n";
  for ( $j = 0; $j < 7; $j++ ) {
    $date = $i + ( $j * 24 * 3600 );
    if ( date ( "Ymd", $date ) >= date ( "Ymd", $monthstart ) &&
      date ( "Ymd", $date ) <= date ( "Ymd", $monthend ) ) {
      $thiswday = date ( "w", $date );
      $is_weekend = ( $thiswday == 0 || $thiswday == 6 );
      if ( empty ( $WEEKENDBG ) ) $is_weekend = false;
      $class = $is_weekend ? "tablecellweekend" : "tablecell";
      $color = $is_weekend ? $WEEKENDBG : $CELLBG;
      if ( empty ( $color ) )
        $color = "#C0C0C0";
      print "<TD VALIGN=\"top\" HEIGHT=75 ID=\"$class\" "; 
      if ( date ( "Ymd", $date ) == date ( "Ymd", $today ) )
        echo "BGCOLOR=\"$TODAYCELLBG\">";
      else
        echo "BGCOLOR=\"$color\">";
      //echo date ( "D, m-d-Y H:i:s", $date ) . "<BR>";
      print_date_entries ( date ( "Ymd", $date ),
        ( ! empty ( $user ) ) ? $user : $login,
        $friendly, false );
      print "</TD>\n";
    } else {
      print "<TD VALIGN=\"top\" HEIGHT=75 ID=\"tablecell\" BGCOLOR=\"$CELLBG\">&nbsp;</TD>\n";
    }
  }
  print "</TR>\n";
}

?>

<?php if ( empty ( $friendly ) || ! $friendly ) { ?>
</TABLE>
</TD></TR></TABLE>
<?php } else { ?>
</TABLE>
<?php } ?>

<P>

<?php if ( empty ( $friendly ) ) echo $eventinfo; ?>

<?php if ( ! $friendly ) {
  display_unapproved_events ( ( $is_assistant || $is_nonuser_admin ? $user : $login ) );
?>

<P>
<A CLASS="navlinks" HREF="view_l.php?id=<?php echo $id?>&<?php
  if ( $thisyear ) {
    echo "year=$thisyear&month=$thismonth&";
  }
  if ( ! empty ( $user ) ) echo "user=$user&";
  if ( ! empty ( $cat_id ) ) echo "cat_id=$cat_id&";
?>friendly=1" TARGET="cal_printer_friendly"
onMouseOver="window.status = '<?php etranslate("Generate printer-friendly version")?>'">[<?php etranslate("Printer Friendly")?>]</A>

<?php include_once "includes/trailer.php"; ?>

<?php } else {
        dbi_close ( $c );
      }
?>

</BODY>
</HTML>
