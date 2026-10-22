<?php
/**
 * Description:
 * Purge events page and handler.
 * When an event is deleted from a user's calendar, it is marked
 * as deleted (webcal_entry_user.cal_status = 'D'). This page
 * will actually clean out the database rather than just mark an
 * event as deleted.
 *
 * Security:
 * Events will only be deleted if they were created by the selected
 * user. Events where the user was a participant (but not did not
 * create) will remain unchanged.
 */
require_once 'includes/init.php';

if ( ! $is_admin ) {
  // must be admin...
  do_redirect ( 'index.php' );
  exit;
}

// Set this to true do show the SQL at the bottom of the page
$purgeDebug = false;
$sqlLog     = '';

$previewStr = translate ( 'Preview' );
$allStr = translate ( 'All' );
$purgingStr = translate ( 'Purging events for' );
$deleteStr = translate ( 'Delete' );

$delete = getPostValue ( 'delete' );
$do_purge = ( ! empty ( $delete ) );

$purge_all = getPostValue ( 'purge_all' );
$purge_deleted = getPostValue ( 'purge_deleted' );
// date_selection() renders a single HTML5 date input named "end__YMD"
// (prefix "end_" + "_YMD"), not the three end_year/end_month/end_day
// fields this page used to read.
$end_date = str_replace ( '-', '', getPostValue ( 'end__YMD' ) );
$username = getPostValue ( 'username' );
$preview = ( ! empty ( getPostValue ( 'preview' ) ) );

$INC = ['js/visible.php'];

print_header ( $INC );
?>
<table>
  <tr>
    <td style="vertical-align:top; width:50%;">
      <h2><?php etranslate ( 'Delete Events' );

if ( $preview )
  echo "[$previewStr]";

echo "</h2>\n"
  . display_admin_link();

if ( $do_purge ) {
  echo '<h2>';

  if ( $preview )
    echo "[$previewStr] $purgingStr $username...";
  else
    echo "$purgingStr: $username";

  echo "</h2>\n";
  $ids = get_purge_ids ( $purge_all, $username, $end_date, $purge_deleted );
  if ( count ( $ids ) > 0 ) {
    purge_events ( $ids );
  } else {
    echo translate ( 'None' );
  }
  echo '<h2>...' . translate ( 'Finished' ) . ".</h2>\n";
?>
  <form><button class="btn btn-primary" type="button" onclick="history.back()">
<?php etranslate ( 'Back' )?></button></form><?php
  if ( $purgeDebug ) {
    echo '<div style="border: 1px solid #000;background-color: #FFF;"><span class="tt">'
      . "$sqlLog</span></div>\n";
  }
} else {
?>
<form id="purgeform" action="purge.php" method="post" name="purgeform">
<?php echo csrf_form_key(); ?>
<table>
 <tr><td><label for="user" class="colon">
<?php echo translate ( 'User' );?></label></td>
 <td><select class="form-control" name="username">
<?php
  $userlist = get_my_users();
  if ($NONUSER_ENABLED == 'Y' ) {
    $nonusers = get_nonuser_cals();
    $userlist = ($NONUSER_AT_TOP == 'Y' ? array_merge ($nonusers, $userlist) : array_merge ($userlist, $nonusers));
  }
  for ( $i = 0, $cnt = count ( $userlist ); $i < $cnt; $i++ ) {
    echo '<option value="' . $userlist[$i]['cal_login'] . '"';
    if ( $login == $userlist[$i]['cal_login'] )
      echo ' selected';
    echo '>' . $userlist[$i]['cal_fullname'] . "</option>\n";
  }
?>
<option value="ALL"><?php echo $allStr ?></option>
  </select>
 </td></tr>
 <tr><td><label for="purge_all" class="colon">
<?php etranslate ( 'Check box to delete ALL events for a user' )?></label></td>
  <td class="alignbottom">
  <input class="form-control-sm" type="checkbox" name="purge_all" value="Y" id="purge_all" onclick="toggle_datefields( 'dateArea', this );">
 </td></tr>
 <tr id="dateArea"><td><label class="colon">
<?php etranslate ( 'Delete all events before' );?></label></td><td>
<?php echo date_selection ( 'end_', date ( 'Ymd' ) ) ?>
 </td></tr>
 <tr><td><label for="purge_deleted" class="colon">
<?php etranslate ( 'Purge deleted only' )?></label></td>
  <td class="alignbottom">
  <input class="form-control-sm" type="checkbox" name="purge_deleted" value="Y">
 </td></tr>
 <tr><td><label for="preview" class="colon">
<?php etranslate ( 'Preview delete' )?></label></td>
  <td class="alignbottom">
  <input class="form-control-sm" type="checkbox" name="preview" value="Y" checked>
 </td></tr>
 <tr><td colspan="2">
   <button class="btn btn-primary" name="delete" value="Y" type="submit" onclick="return
 confirm('<?php
 etranslate ( 'Are you sure you want to delete events for', true ) ?> ' +
 document.forms[0].username.value + '?')"><?php echo $deleteStr?></button>
 </td></tr>
</table>
</form>
<?php } ?>
</td></tr></table>

<?php echo print_trailer();
/**
 * get_purge_ids
 *
 * Work out which event ids a purge would affect.  Extracted from the page
 * body so the selection rules can be regression-tested directly -- see
 * tests/PurgeEventSelectionTest.php.
 *
 * @param  string $purge_all      'Y' to purge every event for the user
 * @param  string $username       Calendar login, or 'ALL' for every user
 * @param  string $end_date       YYYYMMDD cutoff; events before it are purged
 * @param  string $purge_deleted  'Y' to restrict to events marked deleted
 *
 * @return array  Event ids, or the single entry 'ALL' to purge everything
 */
function get_purge_ids ( $purge_all, $username, $end_date, $purge_deleted ) {
  $ALL = 0;  // Tells get_ids whether to skip the "no other participants" check
  $tail = '';
  // Every query below joins webcal_entry_user as weu, so this stays valid
  // no matter which branch we take.
  if ( $purge_deleted == 'Y' )
    $tail = " AND weu.cal_status = 'D' ";

  if ( $purge_all == 'Y' ) {
    if ( $username == 'ALL' )
      return ['ALL'];

    return get_ids ( 'SELECT DISTINCT we.cal_id
      FROM webcal_entry we, webcal_entry_user weu
      WHERE weu.cal_id = we.cal_id AND we.cal_create_by = ?' . $tail,
      [$username] );
  }

  if ( empty ( $end_date ) )
    return [];

  $params = [$end_date];
  if ( $username == 'ALL' ) {
    $ALL = 1;
  } else {
    // Append, never overwrite: $tail may already carry the "deleted only"
    // restriction the admin asked for.
    $tail .= ' AND we.cal_create_by = ?';
    $params[] = $username;
  }
  $E_ids = get_ids ( 'SELECT DISTINCT we.cal_id
    FROM webcal_entry we, webcal_entry_user weu
    WHERE weu.cal_id = we.cal_id
    AND we.cal_type = \'E\' AND we.cal_date < ?' . $tail,
    $params, $ALL );
  $M_ids = get_ids ( 'SELECT DISTINCT we.cal_id FROM webcal_entry we,
    webcal_entry_user weu, webcal_entry_repeats wer
    WHERE we.cal_type = \'M\'
    AND we.cal_id = wer.cal_id AND weu.cal_id = wer.cal_id
    AND wer.cal_end IS NOT NULL AND wer.cal_end < ?' . $tail,
    $params, $ALL );

  return array_merge ( $E_ids, $M_ids );
}
/**
 * purge_events
 *
 * @param  mixed  $ids
 */
function purge_events ( $ids ) {
  global $allStr, $c, // db connection
  $preview, $previewStr, $sqlLog;

  $tables = [
    ['webcal_entry_user', 'cal_id'],
    ['webcal_entry_repeats', 'cal_id'],
    ['webcal_entry_repeats_not', 'cal_id'],
    ['webcal_entry_log', 'cal_entry_id'],
    ['webcal_entry_categories', 'cal_id'],
    ['webcal_import_data', 'cal_id'],
    ['webcal_site_extras', 'cal_id'],
    ['webcal_reminders', 'cal_id'],
    ['webcal_entry_ext_user', 'cal_id'],
    ['webcal_blob', 'cal_id'],
    ['webcal_entry', 'cal_id']];

  //var_dump($tables);exit;
  $cnt = count ( $tables );
  $num = array_fill ( 0, $cnt, 0 );

  foreach ( $ids as $cal_id ) {
    for ( $i = 0; $i < $cnt; $i++ ) {
      $clause = ( $cal_id == 'ALL' ? '' :
        " WHERE {$tables[$i][1]} = $cal_id" );
      if ( $preview ) {
        $sql = 'SELECT COUNT(' . $tables[$i][1] .
          ") FROM {$tables[$i][0]}" . $clause;

        $res = dbi_execute ( $sql );
        $sqlLog .= $sql . "<br>\n";
        if ( $res ) {
          if ( $row = dbi_fetch_row ( $res ) )
            $num[$i] += $row[0];
          dbi_free_result ( $res );
        }
      } else {
        $sql = "DELETE FROM {$tables[$i][0]}" . $clause;
        $sqlLog .= $sql . "<br>\n";
        $res = dbi_execute ( $sql );
        if ( $cal_id == 'ALL' )
          $num[$i] = $allStr;
        else
          $num[$i] += dbi_affected_rows ( $c, $res );
      }
    }
  }
  $xxxStr = translate( 'Records deleted from XXX' );
  // Only a preview run may claim to be one -- a real purge is irreversible.
  $prefix = ( $preview ? '[' . $previewStr . '] ' : '' );
  for ( $i = 0; $i < $cnt; $i++ ) {
    $table = $tables[$i][0];
    echo $prefix .
      str_replace( 'XXX', " $table: {$num[$i]}" , $xxxStr ) .
      "<br>\n";
  }
}
/**
 * get_ids
 *
 * @param  string $sql     Query returning cal_id in the first column
 * @param  array  $params  Values to bind to the query's placeholders
 * @param  mixed  $ALL     Set to 1 to skip the "no other participants" check
 */
function get_ids ( $sql, $params = [], $ALL = '' ) {
  global $sqlLog;

  $ids = [];
  $sqlLog .= $sql . "<br>\n";
  $res = dbi_execute ( $sql, $params );
  if ( $res ) {
    while ( $row = dbi_fetch_row ( $res ) ) {
      if ($ALL == 1)
        $ids[] = $row[0];
      else {
        //ONLY Delete event if no other participants.
        $ID = $row[0];
        $res2 = dbi_execute ( 'SELECT COUNT( * ) FROM webcal_entry_user
  WHERE cal_id = ?', [$ID] );
        if ( $res2 ) {
          if ( $row2 = dbi_fetch_row ( $res2 ) ) {
            if ( $row2[0] == 1 )
             $ids[] = $ID;
          }
          dbi_free_result ( $res2 );
        }
      } // End if ($ALL)
    } // End while
  }
  dbi_free_result ( $res );
  return $ids;
}

?>
