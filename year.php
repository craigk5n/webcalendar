<?php
/* $Id$ */
include_once 'includes/init.php';
send_no_cache_header ();

if (($user != $login) && $is_nonuser_admin)
  load_user_layers ($user);
else
  load_user_layers ();

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
$enddate = mktime ( 0, 0, 0, 12, 31, $year);

if ( $ALLOW_VIEW_OTHER != 'Y' && ! $is_admin )
  $user = '';

$boldDays = false;
if ( ! empty ( $BOLD_DAYS_IN_YEAR ) && $BOLD_DAYS_IN_YEAR == 'Y' ) {
  /* Pre-Load the repeated events for quckier access */
  $repeated_events = read_repeated_events (
    ( ! empty ( $user ) && strlen ( $user ) ) ? $user : $login, $cat_id, $startdate );

  /* Pre-load the non-repeating events for quicker access */
  $events = read_events ( ( ! empty ( $user ) && strlen ( $user ) )
    ? $user : $login, $startdate, $enddate, $cat_id );
  $boldDays = true;
}

//Disable $DISPLAY_ALL_DAYS_IN_MONTH
$DISPLAY_ALL_DAYS_IN_MONTH = 'N';
// Include unapproved events?
$get_unapproved = ( $DISPLAY_UNAPPROVED == 'Y' );

print_header();
?>
 
<div class="title">
 <a title="<?php etranslate ( 'Previous' )?>" class="prev" href="year.php?year=<?php echo $prevYear; if ( ! empty ( $user ) ) echo "&amp;user=$user";?>"><img src="images/leftarrow.gif" alt="<?php etranslate ( 'Previous' )?>" /></a>
 <a title="<?php etranslate ( 'Next' )?>" class="next" href="year.php?year=<?php echo $nextYear; if ( ! empty ( $user ) ) echo "&amp;user=$user";?>"><img src="images/rightarrow.gif" alt="<?php etranslate ( 'Next' )?>" /></a>
 <span class="date"><?php echo $thisyear ?></span>
 <span class="user"><?php
  if ( $single_user == 'N' ) {
   echo "<br />\n";
   if ( ! empty ( $user ) ) {
    user_load_variables ( $user, 'user_' );
    echo $user_fullname;
   } else {
    echo $fullname;
   }
   if ( $is_assistant )
    echo '<br /><strong>-- ' . translate( 'Assistant mode' ) . ' --</strong>';
  }
 ?></span>
</div>
<br />
 
<div align="center">
 <table class="main">
  <tr><td>
   <?php echo display_small_month(1,$year,False); ?></td><td>
   <?php echo display_small_month(2,$year,False); ?></td><td>
   <?php echo display_small_month(3,$year,False); ?></td><td>
   <?php echo display_small_month(4,$year,False); ?>
  </td></tr>
  <tr><td>
   <?php echo display_small_month(5,$year,False); ?></td><td>
   <?php echo display_small_month(6,$year,False); ?></td><td>
   <?php echo display_small_month(7,$year,False); ?></td><td>
   <?php echo display_small_month(8,$year,False); ?>
  </td></tr>
  <tr><td>
   <?php echo display_small_month(9,$year,False); ?></td><td>
   <?php echo display_small_month(10,$year,False); ?></td><td>
   <?php echo display_small_month(11,$year,False); ?></td><td>
   <?php echo display_small_month(12,$year,False); ?>
  </td></tr>
 </table>
</div>

<br />
<?php echo display_unapproved_events ( $login ); ?>
<br />
<?php 
echo generate_printer_friendly ( 'year.php' );
echo print_trailer(); ?>
</body>
</html>
