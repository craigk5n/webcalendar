
<BR CLEAR="all">
<HR CLEAR="all">
<FONT SIZE="-1">
<TABLE BORDER="0" WIDTH="100%" CELLPADDING="0" CELLSPACING="0">
<FORM ACTION="month.php" METHOD="GET" NAME="SelectMonth">
<?php if ( strlen ( $user ) && $user != $login )
        echo "<INPUT TYPE=\"hidden\" NAME=\"user\" VALUE=\"$user\">\n";
?>
<TR><TD ALIGN="left" VALIGN="top" WIDTH="33%"><FONT SIZE="-1">
<B><?php etranslate("Month")?>:</B>
<SELECT NAME="date" ONCHANGE="document.SelectMonth.submit()">
<?php
  if ( $thisyear && $thismonth ) {
    $m = $thismonth;
    $y = $thisyear;
  } else {
    $m = date ( "m" );
    $y = date ( "Y" );
  }
  $d_time = mktime ( 0, 0, 0, $m, 1, $y );
  $thisdate = date ( "Ymd", $d_time );
  $y--;
  for ( $i = 0; $i < 25; $i++ ) {
    $m++;
    if ( $m > 12 ) {
      $m = 1;
      $y++;
    }
    $d = mktime ( 0, 0, 0, $m, 1, $y );
    echo "<OPTION VALUE=\"" . date ( "Ymd", $d ) . "\"";
    if ( date ( "Ymd", $d ) == $thisdate )
      echo " SELECTED";
    echo ">";
    printf ( "%s %d", month_short_name ( $m - 1 ), $y );
  }
?>
</SELECT>
<INPUT TYPE="submit" VALUE="<?php etranslate("Go")?>">
</FONT></TD>
</FORM>
<FORM ACTION="week.php" METHOD="GET" NAME="SelectWeek">
<?php if ( strlen ( $user ) && $user != $login )
        echo "<INPUT TYPE=\"hidden\" NAME=\"user\" VALUE=\"$user\">\n";
?>
<TD ALIGN="center" VALIGN="top" WIDTH="33%"><FONT SIZE="-1">
<B><?php etranslate("Week")?>:</B>
<SELECT NAME="date" ONCHANGE="document.SelectWeek.submit()">
<?php
  if ( $thisyear && $thismonth ) {
    $m = $thismonth;
    $y = $thisyear;
  } else {
    $m = date ( "m" );
    $y = date ( "Y" );
  }
  if ( $thisday ) {
    $d = $thisday;
  } else {
    $d = date ( "d" );
  }
  $d_time = mktime ( 0, 0, 0, $m, $d, $y );
  $thisdate = date ( "Ymd", $d_time );
  $wday = date ( "w", $d_time );
  if ( $WEEK_START == 1 )
    $wkstart = mktime ( 0, 0, 0, $m, $d - ( $wday - 1 ), $y );
  else
    $wkstart = mktime ( 0, 0, 0, $m, $d - $wday, $y );
  for ( $i = -7; $i <= 7; $i++ ) {
    $twkstart = $wkstart + ( 3600 * 24 * 7 * $i );
    $twkend = $twkstart + ( 3600 * 24 * 6 );
    echo "<OPTION VALUE=\"" . date ( "Ymd", $twkstart ) . "\"";
    if ( date ( "Ymd", $twkstart ) <= $thisdate &&
      date ( "Ymd", $twkend ) >= $thisdate )
      echo " SELECTED";
    echo ">";
    printf ( "%s %d - %s %d",
      month_short_name ( date ( "m", $twkstart ) - 1 ), date ( "d", $twkstart ),
      month_short_name ( date ( "m", $twkend ) - 1 ), date ( "d", $twkend ) );
    echo "\n";
  }
?>
</SELECT>
<INPUT TYPE="submit" VALUE="<?php etranslate("Go")?>">
</FONT></TD>
</FORM>
<FORM ACTION="year.php" METHOD="GET" NAME="SelectYear">
<?php if ( strlen ( $user ) && $user != $login )
        echo "<INPUT TYPE=\"hidden\" NAME=\"user\" VALUE=\"$user\">\n";
?>
<TD ALIGN="right" VALIGN="top" WIDTH="33%"><FONT SIZE="-1">
<B><?php etranslate("Year")?>:</B>
<SELECT NAME="year" ONCHANGE="document.SelectYear.submit()">
<?php
  if ( $thisyear ) {
    $y = $thisyear;
  } else {
    $y = date ( "Y" );
  }
  for ( $i = $y - 4; $i < $y + 4; $i++ ) {
    echo "<OPTION VALUE=\"$i\"";
    if ( $i == $y )
      echo " SELECTED";
    echo ">$i\n";
  }
?>
</SELECT>
<INPUT TYPE="submit" VALUE="<?php etranslate("Go")?>">
</FONT></TD>
</FORM>
</TR>
</TABLE>
<BR>
<B><?php etranslate("Go to")?>:</B> 
<?php
  if ( ! strlen ( $single_user_login ) ) {
    if ( ! $use_http_auth )
      echo "<A CLASS=\"navlinks\" HREF=\"login.php\">" .
      translate("Login") . "/" . translate("Logout") . "</A>,\n";
    if ( strlen ( $user ) && $user != $login )
      echo "<A CLASS=\"navlinks\" HREF=\"$STARTVIEW.php\"><B>" .
        translate("Back to My Calendar") . "</B></A>,\n";
    else
      echo "<A CLASS=\"navlinks\" HREF=\"$STARTVIEW.php\"><B>" .
        translate("My Calendar") . "</B></A>,\n";
    echo "<A CLASS=\"navlinks\" HREF=\"select_user.php\">" .
      translate("Another User's Calendar") . "</A>\n";
  } else {
    echo "<A CLASS=\"navlinks\" HREF=\"$STARTVIEW.php\"><B>" .
      translate("My Calendar") . "</B></A>\n";
  }
  // only display some links if we're viewing are own calendar.
  if ( strlen ( $user ) == 0 || $user == $login ) {
    echo ", <A CLASS=\"navlinks\" HREF=\"search.php\">" .
      translate("Search") . "</A>";
    echo ", <A CLASS=\"navlinks\" HREF=\"export.php\">" .
      translate("Export") . "</A>";
    if ( ! $readonly ) {
      echo ", <A CLASS=\"navlinks\" HREF=\"edit_entry.php";
      if ( $thisyear ) {
        print "?year=$thisyear";
        if ( $thismonth )
          print "&month=$thismonth";
        if ( $thisday )
          print "&day=$thisday";
      }
      echo "\">" . translate("Add New Entry") . "</A>";
    }
  }
?>
<BR>
<B><?php etranslate("Admin")?>:</B>
<A CLASS="navlinks" HREF="pref.php"><?php etranslate("Preferences")?></A>
<?php
  if ( ! strlen ( $single_user_login ) && ( ! $readonly || $is_admin ) ) {

    echo ", <A CLASS=\"navlinks\" HREF=\"layers.php\">" .
      translate ("Edit Layers") . "</A>\n";

    if ( $LAYERS_STATUS == "N" )
      echo ", <A CLASS=\"navlinks\" HREF=\"layers_toggle.php?status=on\">" .
        translate ("Enable Layers") . "</A>\n";
    else
      echo ", <A CLASS=\"navlinks\" HREF=\"layers_toggle.php?status=off\">" .
        translate ("Disable Layers") . "</A>\n";

    if ( $is_admin ) {
      echo ", <A CLASS=\"navlinks\" HREF=\"users.php\">" .
        translate ("Users") . "</A>\n";
    } else {
      echo ", <A CLASS=\"navlinks\" HREF=\"edit_user.php\">" .
        translate ("Account") . "</A>\n";
    }
    if ( strlen ( $login ) ) {
      print "<BR><B>" . translate("Current User") . ":</B> ";
      if ( strlen ( $lastname ) )
        echo "$lastname, $firstname";
      else
        echo "$login";
      echo "<BR>";
    }
  }
?>
<BR>

</FONT>
<?php
dbi_close ( $c );
?>
