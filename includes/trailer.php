<?php
// NOTE: This file is included within the print_trailer function found
// in includes/init.php.  If you add a global variable somewhere in this
// file, be sure to declare it global in the print_trialer function.
?>

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
  else if ( ! empty ( $STARTVIEW ) )
    $mycal = "$STARTVIEW.php";
  else
    $mycal = "index.php";
  if ( $single_user != "Y" ) {
    if ( ! empty ( $user ) && $user != $login )
      echo "<A CLASS=\"navlinks\" HREF=\"$mycal\"><B>" .
        translate("Back to My Calendar") . "</B></A>";
    else
      echo "<A CLASS=\"navlinks\" HREF=\"$mycal\"><B>" .
        translate("My Calendar") . "</B></A>";
    if ( $login != '__public__' )
      echo " | <A CLASS=\"navlinks\" HREF=\"adminhome.php\"><B>" .
        translate("Admin") . "</B></A>";
    if ( ! $use_http_auth ) {
      if ( empty ( $login_return_path ) )
        $login_url = "login.php";
      else
        $login_url = "login.php?return_path=$login_return_path";
      echo " | <A CLASS=\"navlinks\" HREF=\"$login_url\">" .
        translate("Login") . "/" . translate("Logout") . "</A>";
    }
    if ( $login != "__public__" && $readonly == "N" &&
      ( $require_approvals == "Y" || $public_access == "Y" ) ) {
	$url = 'list_unapproved.php';
        if ($is_nonuser_admin) $url .= "?user=$user";
	echo " | <A CLASS=\"navlinks\" HREF=\"$url\">" .
        translate("Unapproved Events") . "</A>";
    }
    if ( $login == "__public__" && $public_access_others != "Y" ) {
      // don't allow them to see other people's calendar
    } else if ( $allow_view_other == "Y" || $is_admin )
      echo " | <A CLASS=\"navlinks\" HREF=\"select_user.php\">" .
        translate("Another User's Calendar") . "</A>";
  } else {
    echo "<A CLASS=\"navlinks\" HREF=\"$mycal\"><B>" .
      translate("My Calendar") . "</B></A>";
    echo " | <A CLASS=\"navlinks\" HREF=\"adminhome.php\"><B>" .
      translate("Admin") . "</B></A>";
  }
  // only display some links if we're viewing our own calendar.
  if ( empty ( $user ) || $user == $login ) {
    echo " | <A CLASS=\"navlinks\" HREF=\"search.php\">" .
      translate("Search") . "</A>";
    echo " | <A CLASS=\"navlinks\" HREF=\"export.php\">" .
      translate("Export") . "</A>";
    echo " | <A CLASS=\"navlinks\" HREF=\"import.php\">" .
      translate("Import") . "</A>";
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
  if ( $login != '__public__' ) {
    echo " | <A CLASS=\"navlinks\" HREF=\"#\" ONCLICK=\"window.open ( 'help_index.php', 'cal_help', 'dependent,menubar,scrollbars,height=400,width=400,innerHeight=420,outerWidth=420' );\" " .
      "ONMOUSEOVER=\"window.status='" . translate("Help") . "'\">" .
      translate("Help") . "</A>";
  }
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
    elseif ( $views[$i]['cal_view_type'] == 'D' )
      echo "view_d.php";
    elseif ( $views[$i]['cal_view_type'] == 'V' )
      echo "view_v.php";
    elseif ( $views[$i]['cal_view_type'] == 'T' )
      echo "view_t.php";
    elseif ( $views[$i]['cal_view_type'] == 'M' )
      echo "view_m.php";
    elseif ( $views[$i]['cal_view_type'] == 'L' )
      echo "view_l.php";
    else
      echo "view_m.php";
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

<?php if ( ! empty ( $reports_enabled ) && $reports_enabled == 'Y' ) { ?>
<b><?php etranslate("Reports")?>:</b>
<?php
$res = dbi_query ( "SELECT cal_report_name, cal_report_id " .
  "FROM webcal_report " .
  "WHERE cal_login = '$login' OR " .
  "( cal_is_global = 'Y' AND cal_show_in_trailer = 'Y' ) " .
  "ORDER by cal_report_id" );
$found_report = false;
if ( ! empty ( $user ) && $user != $login ) {
  $u_url = "&user=$user";
} else {
  $u_url = "";
}
if ( $res ) {
  while ( $row = dbi_fetch_row ( $res ) ) {
    if ( $found_report )
      echo " | ";
    echo "<a href=\"report.php?report_id=$row[1]$u_url\" class=\"navlinks\">" .
      htmlentities ( $row[0] ) . "</a>";
    $found_report = true;
  }
  dbi_free_result ( $res );
}
if ( $login != "__public__" ) {
  if ( $found_report )
    echo " | ";
  echo "<a href=\"report.php\" class=\"navlinks\">" .
    translate("Manage Reports") . "</a>\n";
}
?>
<br>
<?php } ?>

<?php
  if ( strlen ( $login ) && $login != "__public__" ) {
    echo "<b>" . translate("Current User") . ":</b>$fullname<br>\n";
  }
  if ($nonuser_enabled == "Y" ) $admincals = get_nonuser_cals ($login);
  if ( $has_boss || $admincals[0] ) {
    echo "<B>";
    etranslate("Manage calendar of");
    echo "</B>: ";
    $grouplist = user_get_boss_list ($login);
    $grouplist = array_merge($admincals,$grouplist);
    $groups = "";
    for ( $i = 0; $i < count ( $grouplist ); $i++ ) {
      $l = $grouplist[$i]['cal_login'];
      $f = $grouplist[$i]['cal_fullname'];
      if ( $i > 0) $groups .= ",&nbsp;";
      $groups .= "<A CLASS=\"navlinks\" HREF=$STARTVIEW.php?user=$l>$f</A>";
    }
    print $groups;
  }
?>
</FONT>
