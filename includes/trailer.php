
<BR CLEAR="all">
<HR CLEAR="all">
<FONT SIZE="-1">
<TABLE BORDER="0" WIDTH="100%" CELLPADDING="0" CELLSPACING="0">
<FORM ACTION="month.php" METHOD="GET" NAME="SelectMonth">
<?php
  if ( ! empty ( $user ) && $user != $login )
    echo "<INPUT TYPE=\"hidden\" NAME=\"user\" VALUE=\"$user\">\n";
  if ( ! empty ( $cat_id ) && $categories_enabled == "Y"
    && ( ! $user || $user == $login ) )
    echo "<INPUT TYPE=\"hidden\" NAME=\"cat_id\" VALUE=\"$cat_id\">\n";
?>
<TR><TD ALIGN="left" VALIGN="top" WIDTH="33%"><FONT SIZE="-1">
<B><?php etranslate("Month")?>:</B>
<SELECT NAME="date" ONCHANGE="document.SelectMonth.submit()">
<?php
  if ( ! empty ( $thisyear ) && ! empty ( $thismonth ) ) {
    $m = $thismonth;
    $y = $thisyear;
  } else {
    $m = date ( "m" );
    $y = date ( "Y" );
  }
  $d_time = mktime ( 3, 0, 0, $m, 1, $y );
  $thisdate = date ( "Ymd", $d_time );
  $y--;
  for ( $i = 0; $i < 25; $i++ ) {
    $m++;
    if ( $m > 12 ) {
      $m = 1;
      $y++;
    }
    $d = mktime ( 3, 0, 0, $m, 1, $y );
    echo "<OPTION VALUE=\"" . date ( "Ymd", $d ) . "\"";
    if ( date ( "Ymd", $d ) == $thisdate )
      echo " SELECTED";
    echo ">";
    echo date_to_str ( date ( "Ymd", $d ), $DATE_FORMAT_MY, false, true );
  }
?>
</SELECT>
<INPUT TYPE="submit" VALUE="<?php etranslate("Go")?>">
</FONT></TD>
</FORM>
<FORM ACTION="week.php" METHOD="GET" NAME="SelectWeek">
<?php
  if ( ! empty ( $user ) && $user != $login )
    echo "<INPUT TYPE=\"hidden\" NAME=\"user\" VALUE=\"$user\">\n";
  if ( ! empty ( $cat_id ) && $categories_enabled == "Y"
    && ( ! $user || $user == $login ) )
    echo "<INPUT TYPE=\"hidden\" NAME=\"cat_id\" VALUE=\"$cat_id\">\n";
?>
<TD ALIGN="center" VALIGN="top" WIDTH="33%"><FONT SIZE="-1">
<B><?php etranslate("Week")?>:</B>
<SELECT NAME="date" ONCHANGE="document.SelectWeek.submit()">
<?php
  if ( ! empty ( $thisyear ) && ! empty ( $thismonth ) ) {
    $m = $thismonth;
    $y = $thisyear;
  } else {
    $m = date ( "m" );
    $y = date ( "Y" );
  }
  if ( ! empty ( $thisday ) ) {
    $d = $thisday;
  } else {
    $d = date ( "d" );
  }
  $d_time = mktime ( 3, 0, 0, $m, $d, $y );
  $thisdate = date ( "Ymd", $d_time );
  $wday = date ( "w", $d_time );
  if ( $WEEK_START == 1 )
    $wkstart = mktime ( 3, 0, 0, $m, $d - ( $wday - 1 ), $y );
  else
    $wkstart = mktime ( 3, 0, 0, $m, $d - $wday, $y );
  for ( $i = -7; $i <= 7; $i++ ) {
    $twkstart = $wkstart + ( 3600 * 24 * 7 * $i );
    $twkend = $twkstart + ( 3600 * 24 * 6 );
    echo "<OPTION VALUE=\"" . date ( "Ymd", $twkstart ) . "\"";
    if ( date ( "Ymd", $twkstart ) <= $thisdate &&
      date ( "Ymd", $twkend ) >= $thisdate )
      echo " SELECTED";
    echo ">";
    printf ( "%s - %s",
      date_to_str ( date ( "Ymd", $twkstart ), $DATE_FORMAT_MD, false, true ),
      date_to_str ( date ( "Ymd", $twkend ), $DATE_FORMAT_MD, false, true ) );
    echo "\n";
  }
?>
</SELECT>
<INPUT TYPE="submit" VALUE="<?php etranslate("Go")?>">
</FONT></TD>
</FORM>
<FORM ACTION="year.php" METHOD="GET" NAME="SelectYear">
<?php
  if ( ! empty ( $user ) && $user != $login )
    echo "<INPUT TYPE=\"hidden\" NAME=\"user\" VALUE=\"$user\">\n";
  if ( ! empty ( $cat_id ) && $categories_enabled == "Y"
    && ( ! $user || $user == $login ) )
    echo "<INPUT TYPE=\"hidden\" NAME=\"cat_id\" VALUE=\"$cat_id\">\n";
?>
<TD ALIGN="right" VALIGN="top" WIDTH="33%"><FONT SIZE="-1">
<B><?php etranslate("Year")?>:</B>
<SELECT NAME="year" ONCHANGE="document.SelectYear.submit()">
<?php
  if ( ! empty ( $thisyear ) ) {
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
  $can_add = ( $readonly == "N" || $is_admin == "Y" );
  if ( $public_access == "Y" && $public_access_can_add != "Y" &&
    $login == "__public__" )
    $can_add = false;

  if ( strlen ( get_last_view() ) )
    $mycal = get_last_view ();
  else
    $mycal = "$STARTVIEW.php";
  if ( $single_user != "Y" ) {
    if ( ! empty ( $user ) && $user != $login )
      echo "<A CLASS=\"navlinks\" HREF=\"$mycal\"><B>" .
        translate("Back to My Calendar") . "</B></A>";
    else
      echo "<A CLASS=\"navlinks\" HREF=\"$mycal\"><B>" .
        translate("My Calendar") . "</B></A>";
    if ( ! $use_http_auth )
      echo " | <A CLASS=\"navlinks\" HREF=\"login.php\">" .
        translate("Login") . "/" . translate("Logout") . "</A>";
    if ( $login != "__public__" && $readonly == "N" &&
      ( $require_approvals == "Y" || $public_access == "Y" ) )
      echo " | <A CLASS=\"navlinks\" HREF=\"list_unapproved.php\">" .
        translate("Unapproved Events") . "</A>";
    if ( $login == "__public__" && $public_access_others != "Y" ) {
      // don't allow them to see other people's calendar
    } else if ( $allow_view_other == "Y" || $is_admin )
      echo " | <A CLASS=\"navlinks\" HREF=\"select_user.php\">" .
        translate("Another User's Calendar") . "</A>";
  } else {
    echo "<A CLASS=\"navlinks\" HREF=\"$mycal\"><B>" .
      translate("My Calendar") . "</B></A>";
  }
  // only display some links if we're viewing our own calendar.
  if ( empty ( $user ) || $user == $login ) {
    echo " | <A CLASS=\"navlinks\" HREF=\"search.php\">" .
      translate("Search") . "</A>";
    echo " | <A CLASS=\"navlinks\" HREF=\"export.php\">" .
      translate("Export") . "</A>";
    if ( $categories_enabled == "Y" && $login != "__public__"
      && $readonly != "Y" )
      echo " | <A CLASS=\"navlinks\" HREF=\"category.php\">" .
      translate ("Categories") . "</A>\n";
    if ( $can_add ) {
      echo " | <A CLASS=\"navlinks\" HREF=\"edit_entry.php";
      if ( ! empty ( $thisyear ) ) {
        print "?year=$thisyear";
        if ( ! empty ( $thismonth ) )
          print "&month=$thismonth";
        if ( ! empty ( $thisday ) )
          print "&day=$thisday";
      }
      echo "\">" . translate("Add New Entry") . "</A>";
    }
  }
  echo " | <A CLASS=\"navlinks\" HREF=\"#\" ONCLICK=\"window.open ( 'help_index.php', 'cal_help', 'dependent,menubar,scrollbars,height=400,width=400,innerHeight=420,outerWidth=420' );\" " .
    "ONMOUSEOVER=\"window.status='" . translate("Help") . "'\">" .
    translate("Help") . "</A>";
?>
<BR>
<?php if ( ( $login != "__public__" ) &&
         ( $allow_view_other != "N" || $is_admin ) ) { ?>
<B><?php etranslate("Views")?>:</B>
<?php
  for ( $i = 0; $i < count ( $views ); $i++ ) {
    if ( $i > 0 )
      echo " | ";
    echo "<A CLASS=\"navlinks\" HREF=\"";
    if ( $views[$i]['cal_view_type'] == 'W' )
      echo "view_w.php";
    else if ( $views[$i]['cal_view_type'] == 'M' )
      echo "view_m.php";
    else
      echo "view_m.php"; // add day view here when it's implemented
    echo "?id=" . $views[$i]['cal_view_id'];
    if ( ! empty ( $thisdate ) )
      echo "&date=$thisdate";
    echo "\">" . $views[$i]['cal_name'] . "</A>";
  }
  if ( $readonly != "Y" ) {
    if ( count ( $views ) > 0 )
      echo " | ";
    echo "<A CLASS=\"navlinks\" HREF=\"views.php\">" .
      translate("Manage Views") . "</A>";
  }
?>
<BR>
<?php } // if ( $login != "__public__" ) ?>
<?php
  if ( $single_user != "Y" && $readonly != "Y" && $login != "__public__" ) {
    echo "<B>" . translate("Admin") . ":</B>\n";
    if ( $is_admin )
      echo "<A CLASS=\"navlinks\" HREF=\"admin.php\">" . translate("System Settings") . "</A> |\n";
    echo "<A CLASS=\"navlinks\" HREF=\"pref.php\">" . translate("Preferences") . "</A>\n";

    if ( $allow_view_other == "Y" || $is_admin ) {
      echo " | <A CLASS=\"navlinks\" HREF=\"layers.php\">" .
        translate ("Edit Layers") . "</A>\n";

      if ( $LAYERS_STATUS == "N" || $LAYERS_STATUS == "" )
        echo " | <A CLASS=\"navlinks\" HREF=\"layers_toggle.php?status=on\">" .
          translate ("Enable Layers") . "</A>\n";
      else
        echo " | <A CLASS=\"navlinks\" HREF=\"layers_toggle.php?status=off\">" .
          translate ("Disable Layers") . "</A>\n";
    }

    if ( $is_admin ) {
      echo " | <A CLASS=\"navlinks\" HREF=\"users.php\">" .
        translate ("Users") . "</A>\n";
      if ( $groups_enabled == "Y" )
        echo " | <A CLASS=\"navlinks\" HREF=\"groups.php\">" .
          translate ("Groups") . "</A>\n";
      echo " | <A CLASS=\"navlinks\" HREF=\"activity_log.php\">" .
        translate ("Activity Log") . "</A>\n";
    } else {
      echo " | <A CLASS=\"navlinks\" HREF=\"edit_user.php\">" .
        translate ("Account") . "</A>\n";
    }
    if ( strlen ( $login ) && $login != "__public__" ) {
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
