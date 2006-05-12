<?php
/*
 * $Id$
 *
 * Page Description:
 * Display a timebar view of a single day.
 *
 * Input Parameters:
 * id (*) - specify view id in webcal_view table
 * date - specify the starting date of the view.
 *   If not specified, current date will be used.
 * friendly - if set to 1, then page does not include links or
 *   trailer navigation.
 * (*) required field
 *
 * Security:
 * Must have "allow view others" enabled ($ALLOW_VIEW_OTHER) in
 *   System Settings unless the user is an admin user ($is_admin).
 * If the view is not global, the user must be owner of the view.
 * If the view is global, then and user_sees_only_his_groups is
 * enabled, then we remove users not in this user's groups
 * (except for nonuser calendars... which we allow regardless of group).
 */
//$start = microtime();

include_once 'includes/init.php';
include_once 'includes/views.php';

$error = "";

view_init ( $id );

$INC = array ( 'js/view_d.php/true' );
print_header ( $INC );

// get users in this view
$participants = view_get_user_list ( $id );
if ( count ( $participants ) == 0 ) {
  // This could happen if user_sees_only_his_groups  = Y and
  // this user is not a member of any  group assigned to this view
  $error = translate ( 'No users for this view' );
}

if ( ! empty ( $error ) ) {
  echo '<h2>' . translate ( 'Error' ) .
    "</h2>\n" . $error;
  print_trailer ();
  exit;
}

set_today($date);
if (!$date) {
  $date = $thisdate;
}

$wday = strftime ( "%w", mktime ( 0, 0, 0, $thismonth, $thisday, $thisyear ) );
$now = mktime ( 0, 0, 0, $thismonth, $thisday, $thisyear );
$nowYmd = date ( 'Ymd', $now );

$nextdate = date( 'Ymd', $now + ONE_DAY);

$prevdate = date( 'Ymd', $now - ONE_DAY);

?>

<div style="border-width:0px; width:99%;">
<a title="<?php etranslate ( 'Previous' )?>" class="prev" href="view_d.php?id=
<?php echo $id . "&amp;date=" . $prevdate?>"><img src="images/leftarrow.gif" 
  class="prevnext" alt="<?php etranslate ( 'Previous' )?>" /></a>

<a title="<?php etranslate ( 'Next' )?>" class="next" href="view_d.php?id=
<?php echo $id . '&amp;date=' . $nextdate?>"><img src="images/rightarrow.gif" 
  class="prevnext" alt="<?php etranslate ( 'Next' )?>" /></a>
<div class="title">
<span class="date"><?php echo date_to_str ( $nowYmd ); 
?></span><br />
<span class="viewname"><?php echo htmlspecialchars ( $view_name ); ?></span>
</div></div>

<?php
daily_matrix($date,$participants);
?>
<br />

<!-- Hidden form for booking events -->
<form action="edit_entry.php" method="post" name="schedule">
<input type="hidden" name="date" value="<?php echo $thisyear.$thismonth.$thisday;?>" />
<input type="hidden" name="defusers" value="<?php echo implode ( ',', $participants ); ?>" />
<input type="hidden" name="hour" value="" />
<input type="hidden" name="minute" value="" />
</form>

<?php
echo '<br /><a title="' . translate ( 'Generate printer-friendly version' ) . 
  "\" class=\"printer\" href=\"view_d.php?id=$id&amp;";
echo ( empty ( $u_url ) ? '' : $u_url ) . "date=$nowYmd";
echo ( empty ( $caturl ) ? '' : $caturl );
echo '&amp;friendly=1" target="cal_printer_friendly" ' .
  "onmouseover=\"window.status='" .
  translate ( 'Generate printer-friendly version' ) .
  "'\">[" . translate ( 'Printer Friendly' ) . ']</a>';

print_trailer ();?>
</body>
</html>
