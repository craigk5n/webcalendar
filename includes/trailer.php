<?php

if ( empty ( $PHP_SELF ) && ! empty ( $_SERVER ) &&
  ! empty ( $_SERVER['PHP_SELF'] ) ) {
  $PHP_SELF = $_SERVER['PHP_SELF'];
}
if ( ! empty ( $PHP_SELF ) && preg_match ( "/\/includes\//", $PHP_SELF ) ) {
    die ( "You can't access this file directly!" );
}

// NOTE: This file is included within the print_trailer function found
// in includes/init.php.  If you add a global variable somewhere in this
// file, be sure to declare it global in the print_trialer function
// or use $GLOBALS[].
?>

<?php if ( access_can_access_function ( ACCESS_TRAILER ) ) { ?>

<div id="trailer">
<?php
  if ( access_can_view_page ( "month.php" ) ) {
    $monthUrl = 'month.php';
    $urlArgs = '';
  } else {
    $monthUrl = $GLOBALS['STARTVIEW'];
    if ( preg_match ( "/[?&](\S+)=(\S+)/", $monthUrl, $match ) ) {
      $monthUrl = $match[0];
      $urlArgs = "<input type=\"hidden\" name=\"$match[1]\" value=\"$match[2]\" />\n";
    }
  }
?>
<form action="<?php echo $monthUrl;?>" method="get" name="SelectMonth" id="monthform">
<?php
  echo $urlArgs;
  if ( ! empty ( $user ) && $user != $login ) {
    echo "<input type=\"hidden\" name=\"user\" value=\"$user\" />\n";
  }
  if ( ! empty ( $cat_id ) && $categories_enabled == "Y"
    && ( ! $user || $user == $login ) ) {
    echo "<input type=\"hidden\" name=\"cat_id\" value=\"$cat_id\" />\n";
  }
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
    echo "<option value=\"" . date ( "Ymd", $d ) . "\"";
    if ( date ( "Ymd", $d ) == $thisdate ) {
      echo " selected=\"selected\"";
    }
    echo ">";
    echo date_to_str ( date ( "Ymd", $d ), $DATE_FORMAT_MY, false, true, 0 );
    echo "</option>\n";
  }
?>
</select>
<input type="submit" value="<?php etranslate("Go")?>" />
</form>

<?php
  if ( access_can_view_page ( "week.php" ) ) {
    $weekUrl = 'week.php';
    $urlArgs = '';
  } else {
    $weekUrl = $GLOBALS['STARTVIEW'];
    if ( preg_match ( "/[?&](\S+)=(\S+)/", $weekUrl, $match ) ) {
      $weekUrl = $match[0];
      $urlArgs = "<input type=\"hidden\" name=\"$match[1]\" value=\"$match[2]\" />\n";
    }
  }
?>
<form action="<?php echo $weekUrl;?>" method="get" name="SelectWeek" id="weekform">
<?php
  echo $urlArgs;
  if ( ! empty ( $user ) && $user != $login ) {
    echo "<input type=\"hidden\" name=\"user\" value=\"$user\" />\n";
  }
  if ( ! empty ( $cat_id ) && $categories_enabled == "Y"
    && ( ! $user || $user == $login ) ) {
    echo "<input type=\"hidden\" name=\"cat_id\" value=\"$cat_id\" />\n";
  }
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
  $d_time = mktime ( 0, 0, 0, $m, $d, $y );
  $thisdate = date ( "Ymd", $d_time );
  $wday = date ( "w", $d_time );
  // $WEEK_START equals 1 or 0 
  $wkstart = mktime ( 0, 0, 0, $m, $d - ( $wday - $WEEK_START ), $y );

  for ( $i = -7; $i <= 7; $i++ ) {
    $twkstart = $wkstart + ( 3600 * 24 * 7 * $i );
    $twkend = $twkstart + ( 3600 * 24 * 6 );
    echo "<option value=\"" . date ( "Ymd", $twkstart ) . "\"";
    if ( date ( "Ymd", $twkstart ) <= $thisdate &&
      date ( "Ymd", $twkend ) >= $thisdate ) {
      echo " selected=\"selected\"";
    }
    echo ">";
    if ( ! empty ( $GLOBALS['PULLDOWN_WEEKNUMBER'] ) && $GLOBALS['PULLDOWN_WEEKNUMBER'] = "Y" ) {
      echo  "(" . week_number ( $twkstart ) . ")&nbsp;&nbsp;";
    }
    printf ( "%s - %s",
      date_to_str ( date ( "Ymd", $twkstart ), $DATE_FORMAT_MD, false, true, 0 ),
      date_to_str ( date ( "Ymd", $twkend ), $DATE_FORMAT_MD, false, true, 0 ) );
    echo "</option>\n";
  }
?>
</select>
<input type="submit" value="<?php etranslate("Go")?>" />
</form>

<?php
  if ( access_can_view_page ( "year.php" ) ) {
    $yearUrl = 'year.php';
    $urlArgs = '';
  } else {
    $yearUrl = $GLOBALS['STARTVIEW'];
    if ( preg_match ( "/[?&](\S+)=(\S+)/", $yearUrl, $match ) ) {
      $yearUrl = $match[0];
      $urlArgs = "<input type=\"hidden\" name=\"$match[1]\" value=\"$match[2]\" />\n";
    }
  }
?>
<form action="<?php echo $yearUrl;?>" method="get" name="SelectYear" id="yearform">
<?php
  echo $urlArgs;
  if ( ! empty ( $user ) && $user != $login ) {
    echo "<input type=\"hidden\" name=\"user\" value=\"$user\" />\n";
  }
  if ( ! empty ( $cat_id ) && $categories_enabled == "Y"
    && ( ! $user || $user == $login ) ) {
    echo "<input type=\"hidden\" name=\"cat_id\" value=\"$cat_id\" />\n";
  }
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
    if ( $i == $y ) {
      echo " selected=\"selected\"";
    }
    echo ">$i</option>\n";
  }
?>
</select>
<input type="submit" value="<?php etranslate("Go")?>" />
</form>
<div id="menu">

<?php
$goto_link = array ( );
$views_link = array ( );
$reports_link = array ( );
$manage_calendar_link = array ( );

// Go To links
$can_add = true;
if ( $readonly == 'Y' )
  $can_add = false;
else if ( access_is_enabled () )
  $can_add = access_can_access_function ( ACCESS_EVENT_EDIT );
else {
  if ( $login == '__public__' )
    $can_add = $GLOBALS['public_access_can_add'] == 'Y';
}

if ( ! empty ( $GLOBALS['STARTVIEW'] ) ) {
  $mycal = $GLOBALS['STARTVIEW'];
} else {
  $mycal = "index.php";
}

// calc URL to today
$todayURL = 'month.php';
$reqURI = 'month.php';
if ( ! empty ( $GLOBALS['SCRIPT_NAME'] ) ) {
  $reqURI = $GLOBALS['SCRIPT_NAME'];
} else if ( ! empty ( $_SERVER['SCRIPT_NAME'] ) ) {
  $reqURI = $_SERVER['SCRIPT_NAME'];
}
if ( ! strstr ( $reqURI, "month.php" ) &&
   ! strstr ( $reqURI, "week.php" ) &&
   ! strstr ( $reqURI, "day.php" ) ) {
  $todayURL = 'day.php';
} else {
  $todayURL = $reqURI;
}
if ( ! access_can_view_page ( $todayURL ) ) {
  $todayURL = '';
}

if ( $single_user != "Y" ) {
  if ( ! empty ( $user ) && $user != $login ) {
    $goto_link[] = "<a title=\"" . 
      translate("My Calendar") . "\" style=\"font-weight:bold;\" " .
      "href=\"$mycal\">" . 
      translate("Back to My Calendar") . "</a>";
  } else {
    $goto_link[] = "<a title=\"" . 
      translate("My Calendar") . "\" style=\"font-weight:bold;\" " .
      "href=\"$mycal\">" . 
      translate("My Calendar") . "</a>";
  }
  if ( ! empty ( $user ) && $user != $login && ! empty ( $todayURL ) ) {
    $todayURL .= '?user=' . $user;
  }
  if ( ! empty ( $todayURL ) ) {
    $goto_link[] = "<a title=\"" . 
      translate("Today") . "\" style=\"font-weight:bold;\" " .
      "href=\"$todayURL\">" . 
      translate("Today") . "</a>";
  }
  if ( $login != '__public__' && ! $is_nonuser && $readonly == 'N' ) {
    if ( ! access_is_enabled () ||
      access_can_access_function ( ACCESS_ADMIN_HOME ) )
      $goto_link[] = "<a title=\"" . 
        translate("Admin") . "\" style=\"font-weight:bold;\" " .
        "href=\"adminhome.php\">" . 
        translate("Admin") . "</a>";
  }
  if ( $login != "__public__" && ! $is_nonuser &&  $readonly == "N" &&
    ( $require_approvals == "Y" || $public_access == "Y" ) ) {
    $url = 'list_unapproved.php';
    if ( $is_nonuser_admin ) {
      $url .= "?user=" . getValue ( 'user' );
    }
    $goto_link[] = "<a title=\"" . 
      translate("Unapproved Events") . "\" href=\"$url\">" . 
      translate("Unapproved Events") . "</a>";
  }
  if ( ( $login == "__public__" && $public_access_others != "Y" ) ||
    ( $is_nonuser && ! access_is_enabled () ) ) {
    // don't allow them to see other people's calendar
  } else if ( $allow_view_other == "Y" || $is_admin ) {
    // Also, make sure they able to access either day/week/month/year view
    // If not, then there is no way to view another user's calendar except
    // a custom view.
    if ( ! access_is_enabled () ||
      access_can_access_function ( ACCESS_DAY ) ||
      access_can_access_function ( ACCESS_WEEK ) ||
      access_can_access_function ( ACCESS_MONTH ) ||
      access_can_access_function ( ACCESS_YEAR ) ) {
      // get count of users this user can see.  if > 1, then...
      $ulist = array_merge ( get_my_users(), get_nonuser_cals () );
      if ( count ( $ulist ) > 1 ) {
        $goto_link[] = "<a title=\"" . 
          translate("Another User's Calendar") .
          "\" href=\"select_user.php\">" . 
          translate("Another User's Calendar") . "</a>";
      }
    }
  }
} else {
  $goto_link[] = "<a title=\"" . 
    translate("My Calendar") . "\" style=\"font-weight:bold;\" " .
    "href=\"$mycal\">" . 
    translate("My Calendar") . "</a>";
  $goto_link[] = "<a title=\"" . 
    translate("Today") . "\" style=\"font-weight:bold;\" " .
    "href=\"$todayURL\">" . 
    translate("Today") . "</a>";
  if ( $readonly == 'N' ) {
    $goto_link[] = "<a title=\"" . 
      translate("Admin") . "\" style=\"font-weight:bold;\" " .
      "href=\"adminhome.php\">" . 
      translate("Admin") . "</a>";
  }
}
// only display some links if we're viewing our own calendar.
if ( empty ( $user ) || $user == $login ) {
  if ( access_can_access_function ( ACCESS_SEARCH ) ) {
    $goto_link[] = "<a title=\"" . 
      translate("Search") . "\" href=\"search.php\">" .
      translate("Search") . "</a>";
  }
  if ( $login != '__public__' && ! $is_nonuser ) {
    if ( access_can_access_function ( ACCESS_IMPORT ) ) {
      $goto_link[] = "<a title=\"" . 
        translate("Import") . "\" href=\"import.php\">" . 
        translate("Import") . "</a>";
    }
    if ( access_can_access_function ( ACCESS_EXPORT ) ) {
      $goto_link[] = "<a title=\"" . 
        translate("Export") . "\" href=\"export.php\">" . 
        translate("Export") . "</a>";
    }
  }
  if ( $can_add ) {
    $url = "<a title=\"" . 
      translate("Add New Entry") . "\" href=\"edit_entry.php";
    if ( ! empty ( $thisyear ) ) {
      $url .= "?year=$thisyear";
      if ( ! empty ( $thismonth ) ) {
        $url .= "&amp;month=$thismonth";
      }
      if ( ! empty ( $thisday ) ) {
        $url .= "&amp;day=$thisday";
      }
    }
    $url .= "\">" . translate("Add New Entry") . "</a>";
    $goto_link[] = $url;
  }
}
if ( access_is_enabled () ) {
  $showHelp = access_can_access_function ( ACCESS_HELP );
} else {
  $showHelp = ( $login != '__public__' && ! $is_nonuser );
}
if ( $showHelp ) {
  $goto_link[] = "<a title=\"" . 
    translate("Help") . "\" href=\"#\" onclick=\"window.open " .
    "( 'help_index.php', 'cal_help', 'dependent,menubar,scrollbars, " .
    "height=400,width=400,innerHeight=420,outerWidth=420' );\"  " .
    "onmouseover=\"window.status='" . 
    translate("Help") . "'\">" . 
    translate("Help") . "</a>";
}

if ( count ( $goto_link ) > 0 ) {
  ?><span class="prefix"><?php etranslate("Go to")?>:</span> <?php
  for ( $i = 0; $i < count ( $goto_link ); $i++ ) {
    if ( $i > 0 )
      echo " | ";
    echo $goto_link[$i];
  }
}
?>

<!-- VIEWS -->
<?php
if ( ( access_can_access_function ( ACCESS_VIEW ) && $allow_view_other != "N" )
  && count ( $views ) > 0 ) {
  for ( $i = 0; $i < count ( $views ); $i++ ) {
    $out = "<a title=\"" .
      htmlspecialchars ( $views[$i]['cal_name'] ) .
      "\" href=\"";
    $out .= $views[$i]['url'];
    if ( ! empty ( $thisdate ) )
      $out .= "&amp;date=$thisdate";
    $out .= "\">" .
      htmlspecialchars ( $views[$i]['cal_name'] ) . "</a>\n";
    $views_link[] = $out;
  }
}
if ( count ( $views_link ) > 0 ) {
  ?><br /><span class="prefix"><?php etranslate("Views")?>:</span>&nbsp;<?php
  for ( $i = 0; $i < count ( $views_link ); $i++ ) {
    if ( $i > 0 )
      echo " | ";
    echo $views_link[$i];
  }
}
?>

<!-- REPORTS -->
<?php
if ( ! empty ( $reports_enabled ) && $reports_enabled == 'Y' &&
  access_can_access_function ( ACCESS_REPORT ) ) {
$reports_link = array ();
  if ( ! empty ( $user ) && $user != $login ) {
    $u_url = "&amp;user=$user";
  } else {
    $u_url = "";
  }
  $res = dbi_query ( "SELECT cal_report_name, cal_report_id " .
    "FROM webcal_report " .
    "WHERE cal_login = '$login' OR " .
    "( cal_is_global = 'Y' AND cal_show_in_trailer = 'Y' ) " .
    "ORDER BY cal_report_id" );
  if ( $res ) {
    while ( $row = dbi_fetch_row ( $res ) ) {
      $reports_link[] = "<a title=\"" . 
        htmlspecialchars ( $row[0] ) . 
        "\" href=\"report.php?report_id=$row[1]$u_url\">" . 
        htmlspecialchars ( $row[0] ) . "</a>";
    }
    dbi_free_result ( $res );
  }

  if ( count ( $reports_link ) > 0 ) {
    ?><br/><span class="prefix"><?php etranslate("Reports");?>:</span>&nbsp;<?php
    for ( $i = 0; $i < count ( $reports_link ); $i++ ) {
      if ( $i > 0 )
        echo " | ";
      echo $reports_link[$i];
    }
  }
}
?>

<!-- CURRENT USER -->
<br />
<?php
if ( ! $use_http_auth ) {
 if ( empty ( $login_return_path ) )
  $login_url = "login.php";
 else
  $login_url = "login.php?return_path=$login_return_path";

  // Should we use another application's login/logout pages?
  if ( substr ( $GLOBALS['user_inc'], 0, 9 ) == 'user-app-' ) {  
    if ( strlen ( $login ) && $login != "__public__" ) {
      $login_url = $GLOBALS['app_logout_page'];
    } else {
      if ($login_return_path != '' && $GLOBALS['app_redir_param'] != '') {
        $GLOBALS['app_login_page'] .= '?'. $GLOBALS['app_redir_param'] .
          '=' . $login_return_path;
      } 
      $login_url = $GLOBALS['app_login_page'];
    }
  }  
    
 if ( strlen ( $login ) && $login != "__public__" ) {
  echo "<span class=\"prefix\">" .
   translate("Current User") . ":</span>&nbsp;$fullname&nbsp;(<a title=\"" . 
   translate("Logout") . "\" href=\"$login_url\">" . 
   translate("Logout") . "</a>)\n";
 } else {
  echo "<span class=\"prefix\">" .
   translate("Current User") . ":</span>&nbsp;" . 
   translate("Public Access") . "&nbsp;(<a title=\"" . 
   translate("Login") . "\" href=\"$login_url\">" . 
   translate("Login") . "</a>)\n";
 }
}

// Manage Calendar links
if ( ! empty ( $nonuser_enabled ) && $nonuser_enabled == "Y" )
  $admincals = get_nonuser_cals ( $login );
// Make sure they have access to either month/week/day view.
// If they do not, then we cannot create a URL that shows just
// the boss' events.  So, we would not include any of the
// "manage calendar of" links.
$have_boss_url = true;
if ( ! access_can_access_function ( ACCESS_MONTH ) &&
  ! access_can_access_function ( ACCESS_WEEK ) &&
  ! access_can_access_function ( ACCESS_DAY ) )
  $have_boss_url = false;
if ( $have_boss_url && ( $has_boss || ! empty ( $admincals[0] ) ||
  ( $is_admin && $public_access ) ) ) {
  $grouplist = user_get_boss_list ( $login );
  if ( ! empty ( $admincals[0] ) ) {
    $grouplist = array_merge ( $admincals, $grouplist );
  }
  if ( $is_admin && $public_access == 'Y' ) {
    $public = array (
      "cal_login" => "__public__",
      "cal_fullname" => translate ( "Public Access" )
    );
    array_unshift ( $grouplist, $public );
  }
  $groups = "";
  for ( $i = 0; $i < count ( $grouplist ); $i++ ) {
    $l = $grouplist[$i]['cal_login'];
    $f = $grouplist[$i]['cal_fullname'];
    // Use the preferred view if it is day/week/month/year.php.  Try
    // not to use a user-created view because it might not display the
    // proper user's events.  (Fallback to month.php if this is true.)
    // Of course, if this user cannot view any of the standard D/W/M/Y
    // pages, that will force us to use the view.
    $xurl = get_preferred_view ( "", "user=$l" );
    if ( strstr ( $xurl, "view_" ) ) {
      if ( access_can_access_function ( ACCESS_MONTH ) )
        $xurl = "month.php?user=$l";
      else if ( access_can_access_function ( ACCESS_WEEK ) )
        $xurl = "week.php?user=$l";
      else if ( access_can_access_function ( ACCESS_DAY ) )
        $xurl = "day.php?user=$l";
      // year does not show events, so you cannot manage someone's cal
    }
    if ( $i > 0 )
      $groups .= ", ";
    $groups .= "<a title=\"$f\" href=\"$xurl\">$f</a>";
  }
  if ( ! empty ( $groups ) ) {
    echo "<br/><span class=\"prefix\">";
    etranslate ( "Manage calendar of" );
    echo ":</span>&nbsp;" . $groups;
  }
}

// WebCalendar Info...
print "<br/><br/><a title=\"" . $GLOBALS['PROGRAM_NAME'] . "\" " .
  "id=\"programname\" href=\"$GLOBALS[PROGRAM_URL]\" target=\"_new\">" .
  $GLOBALS['PROGRAM_NAME'] . "</a>\n";
?>
</div>
</div>
<!-- /TRAILER -->
<?php } ?>
