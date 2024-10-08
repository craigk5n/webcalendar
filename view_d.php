<?php
/**
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
// $start = microtime();
require_once 'includes/init.php';
require_once 'includes/views.php';

$error = '';

view_init ( $id );

$printerStr = generate_printer_friendly ( 'view_d.php' );
set_today ( $date );

print_header ( ['js/view_d.php/true'] );

// get users in this view
$participants = view_get_user_list ( $id );
if ( count ( $participants ) == 0 ) {
  // This could happen if user_sees_only_his_groups  = Y and
  // this user is not a member of any group assigned to this view.
  $error = translate( 'No users for this view.' );

  echo print_error ( $error ) . print_trailer();
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
$trailerStr = print_trailer();
$wday = date('w', mktime(0, 0, 0, $thismonth, $thisday, $thisyear));

$nextStr = translate ( 'Next' );
$previousStr = translate ( 'Previous' );

$formKey = csrf_form_key();
echo <<<EOT
    <div class="viewnav">
      <a class="prev"
        href="view_d.php?id={$id}&amp;date={$prevdate}">
        <img src="images/bootstrap-icons/arrow-left-circle.svg" class="prev"
          alt="{$previousStr}"></a>
      <a class="next"
        href="view_d.php?id={$id}&amp;date={$nextdate}">
        <img src="images/bootstrap-icons/arrow-right-circle.svg" class="next"
          alt="{$nextStr}"></a>
      <div class="title">
        <span class="date">{$nowStr}</span><br>
        <span class="viewname">{$view_name}</span>
      </div>
    </div>
    {$matrixStr}

    <!-- Hidden form for booking events -->
    <form action="edit_entry.php" method="post" name="schedule">
      {$formKey}
      <input type="hidden" name="date"
        value="{$thisyear}{$thismonth}{$thisday}">
      <input type="hidden" name="defusers" value="{$partStr}">
      <input type="hidden" name="hour" value="">
      <input type="hidden" name="minute" value="">
    </form>

    {$printerStr}
    {$trailerStr}
EOT;
