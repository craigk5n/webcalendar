<?php
// NOTE: This file is included within the print_trailer function found
// in includes/init.php.  If you add a global variable somewhere in this
// file, be sure to declare it global in the print_trialer function.
?>

<br style="clear:both;" />
<hr style="clear:both;" />
<font size="-1">
<table style="border-width:0px; width:100%;" cellpadding="0" cellspacing="0">
<form action="month.php" method="get" name="selectmonth">
<?php
  if ( ! empty ( $user ) && $user != $login )
    echo "<input type=\"hidden\" name=\"user\" value=\"$user\" />\n";
  if ( ! empty ( $cat_id ) && $categories_enabled == "Y"
    && ( ! $user || $user == $login ) )
    echo "<input type=\"hidden\" name=\"cat_id\" value=\"$cat_id\" />\n";
?>
<tr><td style="text-align:left; width:33%;" valign="top"><font size="-1">
<b><?php etranslate("Month")?>:</b>
<select name="date" onchange="document.SelectMonth.submit()">
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
    echo "<option value=\"" . date ( "Ymd", $d ) . "\"";
    if ( date ( "Ymd", $d ) == $thisdate )
      echo " selected=\"selected\"";
    echo ">";
    echo date_to_str ( date ( "Ymd", $d ), $DATE_FORMAT_MY, false, true );
  }
?>
</select>
<input type="submit" value="<?php etranslate("Go")?>" />
</font></td>
</form>
<form action="week.php" method="get" name="SelectWeek">
<?php
  if ( ! empty ( $user ) && $user != $login )
    echo "<input type=\"hidden\" name=\"user\" value=\"$user\" />\n";
  if ( ! empty ( $cat_id ) && $categories_enabled == "Y"
    && ( ! $user || $user == $login ) )
    echo "<input type=\"hidden\" name=\"cat_id\" value=\"$cat_id\" />\n";
?>
<td style="text-align:center; width:33%;" valign="top"><font size="-1">
<b><?php etranslate("Week")?>:</b>
<select name="date" onchange="document.SelectWeek.submit()">
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
    echo "<option value=\"" . date ( "Ymd", $twkstart ) . "\"";
    if ( date ( "Ymd", $twkstart ) <= $thisdate &&
      date ( "Ymd", $twkend ) >= $thisdate )
      echo " selected=\"selected\"";
    echo ">";
    printf ( "%s - %s",
      date_to_str ( date ( "Ymd", $twkstart ), $DATE_FORMAT_MD, false, true ),
      date_to_str ( date ( "Ymd", $twkend ), $DATE_FORMAT_MD, false, true ) );
    echo "</option>\n";
  }
?>
</select>
<input type="submit" value="<?php etranslate("Go")?>" />
</font></td>
</form>
<form action="year.php" method="get" name="selectyear">
<?php
  if ( ! empty ( $user ) && $user != $login )
    echo "<input type=\"hidden\" name=\"user\" value=\"$user\" />\n";
  if ( ! empty ( $cat_id ) && $categories_enabled == "Y"
    && ( ! $user || $user == $login ) )
    echo "<input type=\"hidden\" name=\"cat_id\" value=\"$cat_id\" />\n";
?>
<td style="text-align:right; width:33%;" valign="top"><font size="-1">
<b><?php etranslate("Year")?>:</b>
<select name="year" onchange="document.SelectYear.submit()">
<?php
  if ( ! empty ( $thisyear ) ) {
    $y = $thisyear;
  } else {
    $y = date ( "Y" );
  }
  for ( $i = $y - 4; $i < $y + 4; $i++ ) {
    echo "<option value=\"$i\"";
    if ( $i == $y )
      echo " selected=\"selected\"";
    echo ">$i</option>\n";
  }
?>
</select>
<input type="submit" value="<?php etranslate("Go")?>" />
</font></td>
</form>
</tr>
</table>


<br />
<b><?php etranslate("Go to")?>:</b> 
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
      echo "<a class=\"navlinks\" style=\"font-weight:bold;\" href=\"$mycal\">" .
        translate("Back to My Calendar") . "</a>";
    else
      echo "<a class=\"navlinks\" href=\"$mycal\" style=\"font-weight:bold;\">" .
        translate("My Calendar") . "</a>";
    if ( $login != '__public__' )
      echo " | <a class=\"navlinks\" href=\"adminhome.php\" style=\"font-weight:bold;\">" .
        translate("Admin") . "</a>";
    if ( ! $use_http_auth ) {
      if ( empty ( $login_return_path ) )
        $login_url = "login.php";
      else
        $login_url = "login.php?return_path=$login_return_path";
      echo " | <a class=\"navlinks\" href=\"$login_url\">" .
        translate("Login") . "/" . translate("Logout") . "</a>";
    }
    if ( $login != "__public__" && $readonly == "N" &&
      ( $require_approvals == "Y" || $public_access == "Y" ) ) {
	$url = 'list_unapproved.php';
        if ($is_nonuser_admin) $url .= "?user=$user";
	echo " | <a class=\"navlinks\" href=\"$url\">" .
        translate("Unapproved Events") . "</a>";
    }
    if ( $login == "__public__" && $public_access_others != "Y" ) {
      // don't allow them to see other people's calendar
    } else if ( $allow_view_other == "Y" || $is_admin )
      echo " | <a class=\"navlinks\" href=\"select_user.php\">" .
        translate("Another User's Calendar") . "</a>";
  } else {
    echo "<a class=\"navlinks\" href=\"$mycal\" style=\"font-weight:bold;\">" .
      translate("My Calendar") . "</a>";
    echo " | <a class=\"navlinks\" href=\"adminhome.php\" style=\"font-weight:bold;\">" .
      translate("Admin") . "</a>";
  }
  // only display some links if we're viewing our own calendar.
  if ( empty ( $user ) || $user == $login ) {
    echo " | <a class=\"navlinks\" href=\"search.php\">" .
      translate("Search") . "</a>";
    echo " | <a class=\"navlinks\" href=\"export.php\">" .
      translate("Export") . "</a>";
    echo " | <a class=\"navlinks\" href=\"import.php\">" .
      translate("Import") . "</a>";
    if ( $can_add ) {
      echo " | <a class=\"navlinks\" href=\"edit_entry.php";
      if ( ! empty ( $thisyear ) ) {
        print "?year=$thisyear";
        if ( ! empty ( $thismonth ) )
          print "&month=$thismonth";
        if ( ! empty ( $thisday ) )
          print "&day=$thisday";
      }
      echo "\">" . translate("Add New Entry") . "</a>";
    }
  }
  if ( $login != '__public__' && $single_user != 'Y' ) {
    $url = "assistant_edit.php";
    if ($is_nonuser_admin) $url .= "?user=$user";
    echo " | <a class=\"navlinks\" href=\"$url\">" .
      translate ("Assistants") . "</a>\n";
  }
  if ( $login != '__public__' ) {
    echo " | <a class=\"navlinks\" href=\"#\" onclick=\"window.open ( 'help_index.php', 'cal_help', 'dependent,menubar,scrollbars,height=400,width=400,innerHeight=420,outerWidth=420' );\"" .
      " onmouseover=\"window.status='" . translate("Help") . "'\">" .
      translate("Help") . "</a>";
  }
?>
<br />
<?php if ( ( $login != "__public__" ) &&
         ( $allow_view_other != "N" || $is_admin ) ) { ?>
<b><?php etranslate("Views")?>:</b>
<?php
  for ( $i = 0; $i < count ( $views ); $i++ ) {
    if ( $i > 0 )
      echo " | ";
    echo "<a class=\"navlinks\" href=\"";
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
    echo "\">" . $views[$i]['cal_name'] . "</a>";
  }
  if ( $readonly != "Y" ) {
    if ( count ( $views ) > 0 )
      echo " | ";
    echo "<a class=\"navlinks\" href=\"views.php\">" .
      translate("Manage Views") . "</a>";
  }
?>
<br />
<?php } // if ( $login != "__public__" ) ?>

<?php if ( ! empty ( $reports_enabled ) && $reports_enabled == 'Y' ) { ?>
<b><?php etranslate("Reports")?>:&nbsp;</b>
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
<br />
<?php } ?>

<?php
  if ( strlen ( $login ) && $login != "__public__" ) {
    echo "<span style=\"font-weight:bold;\">" . translate("Current User") . ":&nbsp;</span>$fullname<br />\n";
  }
  if ($nonuser_enabled == "Y" ) $admincals = get_nonuser_cals ($login);
  if ( $has_boss || $admincals[0] ) {
    echo "<span style=\"font-weight:bold;\">";
    etranslate("Manage calendar of");
    echo "</span>:&nbsp;";
    $grouplist = user_get_boss_list ($login);
    $grouplist = array_merge($admincals,$grouplist);
    $groups = "";
    for ( $i = 0; $i < count ( $grouplist ); $i++ ) {
      $l = $grouplist[$i]['cal_login'];
      $f = $grouplist[$i]['cal_fullname'];
      if ( $i > 0) $groups .= ",&nbsp;";
      $groups .= "<a class=\"navlinks\" href=\"$STARTVIEW.php?user=$l\">$f</a>";
    }
    print $groups;
  }
?>
</font>
