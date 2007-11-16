<?php
/* $Id$
 *
 * Page Description:
 * This page will display the month "view" with all users's events
 * on the same calendar.  (The other month "view" displays each user
 * calendar in a separate column, side-by-side.)  This view gives you
 * the same effect as enabling layers, but with layers you can only
 * have one configuration of users.
 *
 * Input Parameters:
 * vid (*) - specify view id in webcal_view table
 * date - specify the starting date of the view.
 *   If not specified, current date will be used.
 * friendly - if set to 1, then page does not include links or
 *   trailer navigation.
 * (*) required field
 *
 * Security:
 * Must have "allow view others" enabled ($ALLOW_VIEW_OTHER) in
 *   System Settings unless the user is an admin user ($WC->isAdmin()).
 * If the view is not global, the user must be owner of the view.
 * If the view is global, then and user_sees_only_his_groups is
 * enabled, then we remove users not in this user's groups
 * (except for nonuser calendars... which we allow regardless of group).
 */
include_once 'includes/init.php';
include_once 'includes/views.php';

$error = '';
$DAYS_PER_TABLE = 7;


view_init ( $vid );
$WC->setToday ($date);

$previousStr = translate ( 'Previous' );
$nextStr = translate ( 'Next' );

$nextdate = date ( 'Ymd', mktime ( 0, 0, 0, $thismonth, $thisday + 7, $thisyear ) );
$prevdate = date ( 'Ymd', mktime ( 0, 0, 0, $thismonth, $thisday - 7, $thisyear ) );

$wkstart = get_weekday_before ( $thisyear, $thismonth, $thisday +1 );

$wkend = $wkstart + ( ONE_DAY * ( ! getPref ( 'DISPLAY_WEEKENDS' )? 5 : 7 ) );
$thisdate = date ( 'Ymd', $wkstart );

$dateStr = date_to_str ( $thisdate, '', false ) 
  . '&nbsp;&nbsp;&nbsp; - &nbsp;&nbsp;&nbsp;' 
  . date_to_str ( date ( 'Ymd', $wkend ), '', false );

$view_name = htmlspecialchars ( $view_name );
$display_weekends = getPref ( 'DISPLAY_WEEKENDS' );
$display_long_days = getPref ( 'DISPLAY_LONG_DAYS' );
$add_link_in_views = getPref ( 'ADD_LINK_IN_VIEWS' );
  
build_header ();

echo <<<EOT
   <div style="width:99%;">
     <a title="{$previousStr}" class="prev" 
  href="view_v.php?vid={$vid}&amp;date={$prevdate}">
       <img src="images/leftarrow.gif" alt="{$previousStr}" /></a>
     <a title="{$nextStr}" class="next" href="view_v.php?vid={$vid}&amp;date={$nextdate}">
	   <img src="images/rightarrow.gif" class="prevnext" alt="{$nextStr}" /></a>
     <div class="title">
       <span class="date">{$dateStr}</span><br />
       <span class="viewname">{$view_name}</span>
     </div>
  </div><br />

EOT;

// The table has names across the top and dates for rows.  Since we need
// to spit out an entire row before we can move to the next date, we'll
// save up all the HTML for each cell and then print it out when we're
// done..
// Additionally, we only want to put at most 6 users in one table since
// any more than that doesn't really fit in the page.

// get users in this view
$viewusers = view_get_user_list ( $vid );
$viewusercnt = count ( $viewusers );
if ( $viewusercnt == 0 ) {
  // This could happen if user_sees_only_his_groups  = Y and
  // this user is not a member of any  group assigned to this view
  $error = translate ( 'No users for this view' );
}

if ( ! empty ( $error ) ) {
  echo print_error ( $error );
  echo print_trailer ();
  exit;
}

$e_save = array ();
$re_save = array ();
for ( $i = 0; $i < $viewusercnt; $i++ ) {
  /* Pre-Load the repeated events for quicker access */
  $re_save[$i] = read_repeated_events ( $viewusers[$i], $wkstart, $wkend, '' );
  /* Pre-load the non-repeating events for quicker access 
     subtracting ONE_WEEK to allow cross-dat events to display*/
  $e_save[$i] = read_events ( $viewusers[$i], $wkstart - ONE_WEEK, $wkend );
}

for ( $j = 0; $j < 7; $j += $DAYS_PER_TABLE ) {
  // since print_date_entries is rather stupid, we can swap the event data
  // around for users by changing what $events points to.

  $tdw = 12; // column width percent
?>

<table class="main">
<tr><th class="empty">&nbsp;</th>
<?php
  $todayYmd = date ( 'Ymd', $today );
  $header = $body = '';
  for ( $i = 0; $i < $viewusercnt; $i++ ) {
    $events = $e_save[$i];
    $repeated_events = $re_save[$i];
    $body .= "\n<tr>\n";
    $user = $viewusers[$i];
    $WC->User->loadVariables ( $user, 'temp' );
    $body .= "<th class=\"row\" style=\"width:$tdw%;\">$tempfullname</th>\n";
    for ( $date = $wkstart; $date < $wkend; $date += ONE_DAY ) {
      $is_weekend = is_weekend ( $date );
      if ( $is_weekend && ! $display_weekends ) continue; 
      $dateYmd = date ( 'Ymd', $date );
      $entryStr = print_date_entries ( $dateYmd, $user, true );
      if ( $dateYmd == $todayYmd )
        $class = 'class="today"';
      else if ( $is_weekend )
        $class = 'class="weekend"';
      else
        $class = '';
      //build header row
      if ( $i == 0 ) {
        $header .= "<th $class style=\"width:$tdw%;\">"
          . weekday_name ( date ( 'w', $date ), $display_long_days ) . " " 
          . date ( 'd', $date ) . "</th>\n";
      }
      // JCJ Correction for today class
      if ( ! empty ( $entryStr ) && $entryStr != '&nbsp;' )
        $class = 'class="hasevents"';
      $body .= "<td $class style=\"width:$tdw%;\">";
      if ( $add_link_in_views ) {
        $body .= html_for_add_icon ( $dateYmd, '', '', $user ) . "\n";
      }
      $body .= $entryStr;
      $body .= "</td>\n";
    }
    $body .= "</tr>\n";
  }

  //output all
  echo $header . "</tr>\n" . $body . "</table>\n";
}


$user = ''; // reset

if ( ! empty ( $eventinfo ) ) {
  echo $eventinfo;
}

echo print_trailer ();
?>

