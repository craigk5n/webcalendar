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
include_once 'includes/init.php';
require_valid_referring_url ();

// Set this to true do show the SQL at the bottom of the page
$purgeDebug = false;

$sqlLog = '';

if ( ! $is_admin ) {
  // must be admin...
  do_redirect ( 'index.php' );
  exit;
}

$ALL = 0;

$previewStr = translate ( 'Preview' );
$allStr = translate ( 'All' );
$purgingStr = translate ( 'Purging events for' );
$deleteStr = translate ( 'Delete' );

$delete = getPostValue ( 'delete' );
$do_purge = false;
if ( ! empty ( $delete ) ) {
 $do_purge = true;
}

$purge_all = getPostValue ( 'purge_all' );
$purge_deleted = getPostValue ( 'purge_deleted' );
$end_year = getPostValue ( 'end_year' );
$end_month = getPostValue ( 'end_month' );
$end_day = getPostValue ( 'end_day' );
$username = getPostValue ( 'username' );
$preview = getPostValue ( 'preview' );
$preview = ( empty ( $preview ) ? false : true );

$INC = array ( 'js/visible.php' );

print_header ( $INC );
?>

<table>
<tr><td style="vertical-align:top; width:50%;">
<?php
echo '<h2>' . translate ( 'Delete Events' );
if ( $preview )
  echo '[ ' . $previewStr . ']';
echo "</h2>\n";
echo display_admin_link();

if ( $do_purge ) {
  if ( $preview )
    echo '<h2> [' . $previewStr . '] ' . $purgingStr . " $username...</h2>\n";
  else
    echo '<h2>' . $purgingStr . ": $username</h2>\n";

  $end_date = sprintf ( "%04d%02d%02d", $end_year, $end_month, $end_day );
  $ids = $tail = '';
  if ( $purge_deleted == 'Y' )
    $tail = " AND weu.cal_status = 'D' ";

  if ( $purge_all == 'Y' ) {
    if ( $username == 'ALL' ) {
      $ids =  ['ALL'];
    } else {
      $ids = get_ids ( 'SELECT cal_id FROM webcal_entry '
        . " WHERE cal_create_by = '$username' $tail" );
    }
  } elseif ( $end_date ) {
    if ( $username != 'ALL' ) {
      $tail = " AND we.cal_create_by = '$username' $tail";
    } else {
      $tail = '';
      $ALL = 1;  // Need this to tell get_ids to ignore participant check
    }
    $E_ids = get_ids ( 'SELECT we.cal_id FROM webcal_entry we, webcal_entry_user weu ' .
      "WHERE cal_type = 'E' AND cal_date < '$end_date' $tail",
      $ALL );
    $M_ids = get_ids ( 'SELECT DISTINCT(we.cal_id) FROM webcal_entry we,
      webcal_entry_user weu, webcal_entry_repeats wer
      WHERE we.cal_type = \'M\'
      AND we.cal_id = wer.cal_id AND we.cal_id = wer.cal_id '
      . "AND cal_end IS NOT NULL AND cal_end < '$end_date' $tail",
      $ALL );
    $ids = array_merge ( $E_ids, $M_ids );
  }
  //echo "event ids: <ul><li>" . implode ( "</li><li>", $ids ) . "</li></ul>\n";
  if ( count ( $ids ) > 0 ) {
    purge_events ( $ids );
  } else {
    echo translate ( 'None' );
  }
  echo '<h2>...' . translate ( 'Finished' ) . ".</h2>\n";
?>
  <form><input type="button" value="<?php etranslate ( 'Back' )?>"
onclick="history.back()" /></form
><?php
  if ( $purgeDebug ) {
    echo '<div style="border: 1px solid #000;background-color: #ffffff;"><tt>' .
  $sqlLog . '</tt></div>' ."\n";
  }
} else {
?>

<form action="purge.php" method="post" name="purgeform" id="purgeform">
<table>
 <tr><td><label for="user" class="colon">
  <?php echo translate ( 'User' );?></label></td>
 <td><select name="username">
<?php
  $userlist = get_my_users();
  if ($NONUSER_ENABLED == 'Y' ) {
    $nonusers = get_nonuser_cals();
    $userlist = ($NONUSER_AT_TOP == 'Y' ? array_merge ($nonusers, $userlist) : array_merge ($userlist, $nonusers));
  }
  for ( $i = 0, $cnt = count ( $userlist ); $i < $cnt; $i++ ) {
    echo '<option value="' . $userlist[$i]['cal_login'] . '"';
    if ( $login == $userlist[$i]['cal_login'] )
      echo ' selected="selected"';
    echo '>' . $userlist[$i]['cal_fullname'] . "</option>\n";
  }
?>
<option value="ALL"><?php echo $allStr ?></option>
  </select>
 </td></tr>
 <tr><td><label for="purge_all" class="colon">
  <?php etranslate ( 'Check box to delete ALL events for a user' )?></label></td>
  <td class="alignbottom">
  <input type="checkbox" name="purge_all" value="Y" id="purge_all" onclick="toggle_datefields( 'dateArea', this );" />
 </td></tr>
 <tr id="dateArea"><td><label class="colon">
  <?php etranslate ( 'Delete all events before' );?></label></td><td>
  <?php echo date_selection ( 'end_', date ( 'Ymd' ) ) ?>
 </td></tr>
 <tr><td><label for="purge_deleted" class="colon">
  <?php etranslate ( 'Purge deleted only' )?></label></td>
  <td class="alignbottom">
  <input type="checkbox" name="purge_deleted" value="Y" />
 </td></tr>
 <tr><td><label for="preview" class="colon">
  <?php etranslate ( 'Preview delete' )?></label></td>
  <td class="alignbottom">
  <input type="checkbox" name="preview" value="Y" checked="checked" />
 </td></tr>
 <tr><td colspan="2">
  <input type="submit" name="delete" value="<?php
    echo $deleteStr?>" onclick="return confirm( '<?php
    etranslate ( 'Are you sure you want to delete events for', true);
    ?> ' + document.forms[0].username.value + '?' )" />
 </td></tr>
</table>
</form>

<?php } ?>
</td></tr></table>

<?php echo print_trailer();
/**
 * purge_events (needs description)
 */
function purge_events ( $ids ) {
  global $preview, $previewStr, $c; // db connection
  global $sqlLog, $allStr;

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
  $num = [];
  $cnt = count ( $tables );
  for ( $i = 0; $i < $cnt; $i++ ) {
    $num[$i] = 0;
  }
  foreach ( $ids as $cal_id ) {
    for ( $i = 0; $i < $cnt; $i++ ) {
      $clause = ( $cal_id == 'ALL' ? '' :
        " WHERE {$tables[$i][1]} = $cal_id" );
      if ( $preview ) {
        $sql = 'SELECT COUNT(' . $tables[$i][1] .
          ") FROM {$tables[$i][0]}" . $clause;

        $res = dbi_execute ( $sql );
        $sqlLog .= $sql . "<br />\n";
        if ( $res ) {
          if ( $row = dbi_fetch_row ( $res ) )
            $num[$i] += $row[0];
          dbi_free_result ( $res );
        }
      } else {
        $sql = "DELETE FROM {$tables[$i][0]}" . $clause;
        $sqlLog .= $sql . "<br />\n";
        $res = dbi_execute ( $sql );
        if ( $cal_id == 'ALL' )
          $num[$i] = $allStr;
        else
          $num[$i] += dbi_affected_rows ( $c, $res );
      }
    }
  }
  $xxxStr = translate( 'Records deleted from XXX' );
  for ( $i = 0; $i < $cnt; $i++ ) {
    $table = $tables[$i][0];
    echo '[' . $previewStr . '] ' .
      str_replace( 'XXX', " $table: {$num[$i]}" , $xxxStr ) .
      "<br />\n";
  }
}
/**
 * get_ids (needs description)
 */
function get_ids ( $sql, $ALL = '' ) {
  global $sqlLog;

  $ids = [];
  $sqlLog .= $sql . "<br />\n";
  $res = dbi_execute ( $sql );
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
