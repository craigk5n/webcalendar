<?php
/* $Id$ */
include_once 'includes/init.php';
send_no_cache_header ();

if (($user != $login) && $is_nonuser_admin)
  load_user_layers ($user);
else
  load_user_layers ();

load_user_categories ();

if ( empty ( $year ) )
  $year = date ( 'Y' );

$thisyear = $year;
if ( $year != date ( 'Y' ) )
  $thismonth = 1;
//set up global $today value for highlighting current date
set_today($date);
if ( $year > '1903' )
  $prevYear = $year - 1;
else
  $prevYear=$year;

$nextYear= $year + 1;

$startdate = mktime ( 0, 0, 0, 1, 1, $year);
$enddate = mktime ( 23, 59, 59, 12, 31, $year);

if ( $ALLOW_VIEW_OTHER != 'Y' && ! $is_admin )
  $user = '';

$boldDays = false;
$catSelectStr = '';
if ( ! empty ( $BOLD_DAYS_IN_YEAR ) && $BOLD_DAYS_IN_YEAR == 'Y' ) {
  /* Pre-Load the repeated events for quckier access */
  $repeated_events = read_repeated_events (
    ( ! empty ( $user ) && strlen ( $user ) ) ? $user : $login
    , $cat_id, $startdate, $enddate );

  /* Pre-load the non-repeating events for quicker access */
  $events = read_events ( ( ! empty ( $user ) && strlen ( $user ) )
    ? $user : $login, $startdate, $enddate, $cat_id );
  $boldDays = true;
  $catSelectStr = print_category_menu( 'year', $thisyear, $cat_id );
}

//Disable $DISPLAY_ALL_DAYS_IN_MONTH
$DISPLAY_ALL_DAYS_IN_MONTH = 'N';
// Include unapproved events?
$get_unapproved = ( $DISPLAY_UNAPPROVED == 'Y' );

$month1  = display_small_month(1,$year,False);
$month2  = display_small_month(2,$year,False);
$month3  = display_small_month(3,$year,False);
$month4  = display_small_month(4,$year,False);
$month5  = display_small_month(5,$year,False);
$month6  = display_small_month(6,$year,False);
$month7  = display_small_month(7,$year,False);
$month8  = display_small_month(8,$year,False);
$month9  = display_small_month(9,$year,False);
$month10 = display_small_month(10,$year,False);
$month11 = display_small_month(11,$year,False);
$month12 = display_small_month(12,$year,False);

$prevStr = translate ( 'Previous' );
$nextStr = translate ( 'Next' );
$userStr = ( ! empty ( $user ) ? "&amp;user=$user" : '' );
if ( $single_user == 'N' ) {
  if ( ! empty ( $user ) ) {
    user_load_variables ( $user, 'user_' );
  $fullnameStr =  $user_fullname;
  } else {
  $fullnameStr = $fullname;
  }
}
$asstModeStr = ( $is_assistant ? '-- ' . 
  translate( 'Assistant mode' ) . ' --' : '' );
if ( empty ( $friendly ) ) {
  $unapprovedStr = display_unapproved_events ( ( $is_assistant || 
    $is_nonuser_admin ? $user : $login ) );
  $printerStr = generate_printer_friendly ( 'year.php' );
} else {
  $unapprovedStr = $printerStr = '';
}
$trailerStr = print_trailer ();
print_header();

echo <<<EOT
  <div class="title">
    <a title="{$prevStr}" class="prev" href="year.php?year={$prevYear}{$userStr}">
    <img src="images/leftarrow.gif" alt="{$prevStr}" /></a>
    <a title="{$nextStr}" class="next" href="year.php?year={$nextYear}{$userStr}">
    <img src="images/rightarrow.gif" alt="{$nextStr}" /></a>
    <span class="date">{$thisyear}</span>
    <br />
    <span class="user">{$fullnameStr}</span>
    <br />
    <span class="asstmode">{$asstModeStr}</span>
    {$catSelectStr}
  </div>
  <br />
 
  <div align="center">
  <table>
    <tr>
      <td>{$month1}</td>
      <td>{$month2}</td>
      <td>{$month3}</td>
      <td>{$month4}</td>
    </tr>
    <tr>
      <td>{$month5}</td>
      <td>{$month6}</td>
      <td>{$month7}</td>
      <td>{$month8}</td>
    </tr>
    <tr>
      <td>{$month9}</td>
      <td>{$month10}</td>
      <td>{$month11}</td>
      <td>{$month12}</td>
    </tr>
   </table>
  </div>

  <br />
  {$unapprovedStr}
  <br />
  {$printerStr}
  {$trailerStr}
EOT;
?>
