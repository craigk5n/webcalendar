<?php

if (preg_match("/\/includes\//", $PHP_SELF)) {
    die ("You can't access this file directly!");
}

// NOTE: This file is included within the print_trailer function found
// in includes/init.php.  If you add a global variable somewhere in this
// file, be sure to declare it global in the print_trialer function
// or use $GLOBALS[].
?>

<div id="trailer">
<form action="month.php" method="get" name="SelectMonth" id="monthform">
<?php
  if ( ! empty ( $user ) && $user != $login )
    echo "<input type=\"hidden\" name=\"user\" value=\"$user\" />\n";
  if ( ! empty ( $cat_id ) && $categories_enabled == "Y"
    && ( ! $user || $user == $login ) )
    echo "<input type=\"hidden\" name=\"cat_id\" value=\"$cat_id\" />\n";
?>
<label for="monthselect"><?php etranslate("Month")?>:&nbsp;</label>
<select name="date" id="monthselect" onchange="document.SelectMonth.submit()">
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
    echo "</option>\n";
  }
?>
</select>
<input type="submit" value="<?php etranslate("Go")?>" />
</form>

<form action="week.php" method="get" name="SelectWeek" id="weekform">
<?php
  if ( ! empty ( $user ) && $user != $login )
    echo "<input type=\"hidden\" name=\"user\" value=\"$user\" />\n";
  if ( ! empty ( $cat_id ) && $categories_enabled == "Y"
    && ( ! $user || $user == $login ) )
    echo "<input type=\"hidden\" name=\"cat_id\" value=\"$cat_id\" />\n";
?>
<label for="weekselect"><?php etranslate("Week")?>:&nbsp;</label>
<select name="date" id="weekselect" onchange="document.SelectWeek.submit()">
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
</form>

<form action="year.php" method="get" name="SelectYear" id="yearform">
<?php
  if ( ! empty ( $user ) && $user != $login )
    echo "<input type=\"hidden\" name=\"user\" value=\"$user\" />\n";
  if ( ! empty ( $cat_id ) && $categories_enabled == "Y"
    && ( ! $user || $user == $login ) )
    echo "<input type=\"hidden\" name=\"cat_id\" value=\"$cat_id\" />\n";
?>
<label for="yearselect"><?php etranslate("Year")?>:&nbsp;</label>
<select name="year" id="yearselect" onchange="document.SelectYear.submit()">
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
</form>
<div id="menu">
<!-- GO TO -->
<span class="prefix"><?php etranslate("Go to")?>:</span> 
<?php
  $can_add = ( $readonly == "N" );
  if ( $public_access == "Y" && $public_access_can_add != "Y" &&
    $login == "__public__" )
    $can_add = false;

  if ( strlen ( get_last_view() ) )
    $mycal = get_last_view ();
  else if ( ! empty ( $GLOBALS['STARTVIEW'] ) )
    $mycal = "$GLOBALS[STARTVIEW].php";
  else
    $mycal = "index.php";

  // calc URL to today
  $todayURL = 'month.php';
  $reqURI = 'month.php';
  if ( ! empty ( $GLOBALS['SCRIPT_NAME'] ) )
    $reqURI = $GLOBALS['SCRIPT_NAME'];
  else if ( ! empty ( $_SERVER['SCRIPT_NAME'] ) )
    $reqURI = $_SERVER['SCRIPT_NAME'];
  if ( ! strstr ( $reqURI, "month.php" ) &&
     ! strstr ( $reqURI, "week.php" ) &&
     ! strstr ( $reqURI, "day.php" ) )
    $todayURL = 'day.php';
  else
    $todayURL = $reqURI;

  if ( $single_user != "Y" ) {
    if ( ! empty ( $user ) && $user != $login )
      echo "<a title=\"" . 
	translate("My Calendar") . "\" style=\"font-weight:bold;\" href=\"$mycal\">" . 
	translate("Back to My Calendar") . "</a>";
    else
      echo "<a title=\"" . 
	translate("My Calendar") . "\" style=\"font-weight:bold;\" href=\"$mycal\">" . 
	translate("My Calendar") . "</a>\n";

    if ( ! empty ( $user ) && $user != $login )
      $todayURL .= '?user=' . $user;
    echo " | <a title=\"" . 
    	translate("Today") . "\" style=\"font-weight:bold;\" href=\"$todayURL\">" . 
	translate("Today") . "</a>\n";

    if ( $login != '__public__' && $readonly == 'N' )
      echo " | <a title=\"" . 
	translate("Admin") . "\" style=\"font-weight:bold;\" href=\"adminhome.php\">" . 
	translate("Admin") . "</a>\n";
    if ( $login != "__public__" && $readonly == "N" &&
      ( $require_approvals == "Y" || $public_access == "Y" ) ) {
	$url = 'list_unapproved.php';
        if ($is_nonuser_admin) $url .= "?user=$user";
	echo " | <a title=\"" . 
		translate("Unapproved Events") . "\" href=\"$url\">" . 
		translate("Unapproved Events") . "</a>\n";
    }
    if ( $login == "__public__" && $public_access_others != "Y" ) {
      // don't allow them to see other people's calendar
    } else if ( $allow_view_other == "Y" || $is_admin )
      echo " | <a title=\"" . 
		translate("Another User's Calendar") . "\" href=\"select_user.php\">" . 
		translate("Another User's Calendar") . "</a>\n";
  } else {
    echo "<a title=\"" . 
		translate("My Calendar") . "\" style=\"font-weight:bold;\" href=\"$mycal\">" . 
		translate("My Calendar") . "</a>\n";
    echo " | <a title=\"" . 
		translate("Today") . "\" style=\"font-weight:bold;\" href=\"$todayURL\">" . 
		translate("Today") . "</a>\n";
    if ( $readonly == 'N' ) {
      echo " | <a title=\"" . 
		translate("Admin") . "\" style=\"font-weight:bold;\" href=\"adminhome.php\">" . 
		translate("Admin") . "</a>\n";
    }
  }
  // only display some links if we're viewing our own calendar.
  if ( empty ( $user ) || $user == $login ) {
    echo " | <a title=\"" . 
	translate("Search") . "\" href=\"search.php\">" .
	translate("Search") . "</a>\n";
    if ( $login != '__public__' )
      echo " | <a title=\"" . 
    	translate("Import") . "/" . translate("Export") . "\" href=\"import.php\">" . 
	translate("Import") . "/" . translate("Export") . "</a>\n";
    if ( $can_add ) {
      echo " | <a title=\"" . 
	translate("Add New Entry") . "\" href=\"edit_entry.php";
      if ( ! empty ( $thisyear ) ) {
        print "?year=$thisyear";
        if ( ! empty ( $thismonth ) )
          print "&amp;month=$thismonth";
        if ( ! empty ( $thisday ) )
          print "&amp;day=$thisday";
      }
      echo "\">" . 
	translate("Add New Entry") . "</a>\n";
    }
  }
  if ( $login != '__public__' ) {
    echo " | <a title=\"" . 
	translate("Help") . "\" href=\"#\" onclick=\"window.open ( 'help_index.php', 'cal_help', 'dependent,menubar,scrollbars,height=400,width=400,innerHeight=420,outerWidth=420' );\" onmouseover=\"window.status='" . 
	translate("Help") . "'\">" . 
	translate("Help") . "</a>\n";
  }
?>

<!-- VIEWS -->
<?php if ( ( $login != "__public__" ) &&
         ($allow_view_other != "N") ) { ?>
<br /><span class="prefix"><?php etranslate("Views")?>:</span>&nbsp;
<?php
  for ( $i = 0; $i < count ( $views ); $i++ ) {
    if ( $i > 0 )
      echo " | ";
    echo "<a title=\"" . 
	$views[$i]['cal_name'] . "\" href=\"";
    if ( $views[$i]['cal_view_type'] == 'W' )
      echo "view_w.php?";
    elseif ( $views[$i]['cal_view_type'] == 'D' )
      echo "view_d.php?";
    elseif ( $views[$i]['cal_view_type'] == 'V' )
      echo "view_v.php?";
    elseif ( $views[$i]['cal_view_type'] == 'T' )
      echo "view_t.php?timeb=0&amp;";
    elseif ( $views[$i]['cal_view_type'] == 'M' )
      echo "view_m.php?";
    elseif ( $views[$i]['cal_view_type'] == 'L' )
      echo "view_l.php?";
    elseif ( $views[$i]['cal_view_type'] == 'S' )
      echo "view_t.php?timeb=1&amp;";
    else
      echo "view_m.php?";
    echo "id=" . $views[$i]['cal_view_id'];
    if ( ! empty ( $thisdate ) )
      echo "&amp;date=$thisdate";
    echo "\">" . 
	$views[$i]['cal_name'] . "</a>\n";
  }
?>

<!-- REPORTS -->
<?php } // if ( $login != "__public__" ) ?>
<?php if ( ! empty ( $reports_enabled ) && $reports_enabled == 'Y' ) { ?>
<br />
<?php
$res = dbi_query ( "SELECT cal_report_name, cal_report_id " .
  "FROM webcal_report " .
  "WHERE cal_login = '$login' OR " .
  "( cal_is_global = 'Y' AND cal_show_in_trailer = 'Y' ) " .
  "ORDER BY cal_report_id" );
$found_report = false;
if ( ! empty ( $user ) && $user != $login ) {
  $u_url = "&amp;user=$user";
} else {
  $u_url = "";
}
if ( $res ) {
  while ( $row = dbi_fetch_row ( $res ) ) {
    if ( $found_report )
      echo " | ";
    else
      echo "<span class=\"prefix\">" . 
      	translate("Reports") . ":</span>&nbsp;";
    echo "<a title=\"" . 
	htmlentities ( $row[0] ) . "\" href=\"report.php?report_id=$row[1]$u_url\">" . 
	htmlentities ( $row[0] ) . "</a>\n";
    $found_report = true;
  }
  dbi_free_result ( $res );
}
?>

<!-- CURRENT USER -->
<?php } ?>
<br />
<?php
if ( ! $use_http_auth ) {
	if ( empty ( $login_return_path ) )
		$login_url = "login.php";
	else
		$login_url = "login.php?return_path=$login_return_path";

  // Should we use another application's login/logout pages?
  if ( substr($GLOBALS[user_inc],0,9) == 'user-app-' ) {  
    global $app_logout_page,$app_login_page,$app_redir_param;
    if ( strlen ( $login ) && $login != "__public__" ) {
      $login_url = $app_logout_page;
    } else {
      if ($login_return_path != '' && $app_redir_param != '') {
        $app_login_page .= '?'.$app_redir_param.'='.$login_return_path;
      } 
      $login_url = $app_login_page;
    }
  }  
    
	if ( strlen ( $login ) && $login != "__public__" ) {
		echo "<span class=\"prefix\">" .
			translate("Current User") . ":</span>&nbsp;$fullname&nbsp;(<a title=\"" . 
			translate("Logout") . "\" href=\"$login_url\">" . 
			translate("Logout") . "</a>)<br />\n";
	} else {
		echo "<span class=\"prefix\">" .
			translate("Current User") . ":</span>&nbsp;" . 
			translate("Public Access") . "&nbsp;(<a title=\"" . 
			translate("Login") . "\" href=\"$login_url\">" . 
			translate("Login") . "</a>)<br />\n";
	}
}
  if ($nonuser_enabled == "Y" ) $admincals = get_nonuser_cals ($login);
  if ( $has_boss || ! empty ( $admincals[0] ) ) {
    echo "<span class=\"prefix\">";
    etranslate("Manage calendar of");
    echo ":</span>&nbsp;";
    $grouplist = user_get_boss_list ($login);
    $grouplist = array_merge($admincals,$grouplist);
    $groups = "";
    for ( $i = 0; $i < count ( $grouplist ); $i++ ) {
      $l = $grouplist[$i]['cal_login'];
      $f = $grouplist[$i]['cal_fullname'];
      if ( $i > 0) $groups .= ", ";
		$groups .= "<a title=\"$f\" href=\"$GLOBALS[STARTVIEW].php?user=$l\">$f</a>";
    }
    print $groups;
  }
  print "<br />\n<a title=\"" . $GLOBALS['PROGRAM_NAME'] . "\" id=\"programname\" href=\"$GLOBALS[PROGRAM_URL]\" target=\"_new\">" .
    $GLOBALS['PROGRAM_NAME'] . "</a>\n";
?>
</div>
</div><!-- /TRAILER -->
