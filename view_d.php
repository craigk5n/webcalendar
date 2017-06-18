<?php
/* $Id: view_d.php,v 1.58.2.2 2007/08/06 02:28:31 cknudsen Exp $
 *
 * Page Description:
 * Display a timebar view of a single day.
 *
 * Input Parameters:
 * id (*)   - Specify view id in webcal_view table.
 * date     - Specify the starting date of the view.
 *            If not specified, current date will be used.
 * friendly - If set to 1, then page does not include links or
 *            trailer navigation.
 * (*) required field
 *
 * Security:
 * Must have "allow view others" enabled ($ALLOW_VIEW_OTHER) in System Settings
 * unless the user is an admin ($is_admin).
 * If the view is not global, the user must own the view.
 * If the view is global and user_sees_only_his_groups is enabled,
 * then we remove users not in this user's groups
 * (except for nonuser calendars... which we allow regardless of group).
 */
// $start = microtime ();
include_once 'includes/init.php';
include_once 'includes/views.php';

$error = '';

view_init ( $id );

$printerStr = generate_printer_friendly ( 'view_d.php' );
set_today ( $date );

print_header ( array ( 'js/view_d.php/true' ) );

// get users in this view
$participants = view_get_user_list ( $id );
if ( count ( $participants ) == 0 ) {
  // This could happen if user_sees_only_his_groups  = Y and
  // this user is not a member of any group assigned to this view.
  $error = translate ( 'No users for this view' ) . '.';

  echo print_error ( $error ) . print_trailer ();
  exit;
}

if ( ! $date )
  $date = $thisdate;

$now = mktime ( 0, 0, 0, $thismonth, $thisday, $thisyear );
$nowStr = date_to_str ( date ( 'Ymd', $now ) );

$nextdate = date ( 'Ymd', $now + 86400 );
$prevdate = date ( 'Ymd', $now - 86400 );

$matrixStr = daily_matrix ( $date, $participants );
$partStr = implode ( ',', $participants );
$trailerStr = print_trailer ();
$wday = strftime ( '%w', mktime ( 0, 0, 0, $thismonth, $thisday, $thisyear ) );

$nextStr = translate ( 'Next' );
$previousStr = translate ( 'Previous' );

echo <<<EOT
    <div class="viewnav">
      <a title="{$previousStr}" class="prev"
        href="view_d.php?id={$id}&amp;date={$prevdate}">
        <img src="images/leftarrow.gif" class="prev"
          alt="{$previousStr}" /></a>
      <a title="{$nextStr}" class="next"
        href="view_d.php?id={$id}&amp;date={$nextdate}">
        <img src="images/rightarrow.gif" class="next"
          alt="{$nextStr}" /></a>
      <div class="title">
        <span class="date">{$nowStr}</span><br />
        <span class="viewname">{$view_name}</span>
      </div>
    </div>
    {$matrixStr}

    <!-- Hidden form for booking events -->
    <form action="edit_entry.php" method="post" name="schedule">
      <input type="hidden" name="date"
        value="{$thisyear}{$thismonth}{$thisday}" />
      <input type="hidden" name="defusers" value="{$partStr}" />
      <input type="hidden" name="hour" value="" />
      <input type="hidden" name="minute" value="" />
    </form>

    {$printerStr}
    {$trailerStr}
EOT;
