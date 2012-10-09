<?php /* $Id$ */
/**
 * Page Description:
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
require_valide_referring_url();

// Set this to true do show the SQL at the bottom of the page
$purgeDebug = false;

$sqlLog = '';

if ( ! $is_admin ) {
  // must be admin...
  do_redirect ( 'index.php' );
  exit;
}

$ALL = 0;

$boxPreviewStr = translate( '[Preview]' );
$purgingStr = translate( 'Purging events for XXX' );

$delete       = getPostValue( 'delete' );
$end_day      = getPostValue( 'end_day' );
$end_month    = getPostValue( 'end_month' );
$end_year     = getPostValue( 'end_year' );
$preview      = getPostValue( 'preview' );
$purge_all    = getPostValue( 'purge_all' );
$purge_deleted= getPostValue( 'purge_deleted' );
$username     = getPostValue( 'username' );

$do_purge= ! empty( $delete );
$preview = ! empty( $preview );

print_header();
echo '
    <table summary="">
      <tr>
        <td style="vertical-align:top; width:50%;">
          <h2>' . translate( 'Delete Events' ) . ( $preview ? $boxPreviewStr : '' )
 . '</h2>
          ' . display_admin_link();

if ( $do_purge ) {
  echo '
          <h2>' . ( $preview ? $boxPreviewStr . ' ' : '' )
   . str_replace( 'XXX', $username . ( $preview ? '...' : '' ), $purgingStr )
   . '</h2>';

  $end_date = sprintf ( "%04d%02d%02d", $end_year, $end_month, $end_day );
  $ids = $tail = '';
  if ( $purge_deleted == 'Y' )
    $tail = " AND weu.cal_status = 'D' ";

  if ( $purge_all == 'Y' ) {
    if ( $username == 'ALL' ) {
      $ids = array ( 'ALL' );
    } else {
      $ids = get_ids( 'SELECT cal_id FROM webcal_entry
        WHERE cal_create_by = ' . "'$username' $tail" );
    }
  } elseif ( $end_date ) {
    if ( $username != 'ALL' ) {
      $tail = ' AND we.cal_create_by = ' . "'$username' $tail";
    } else {
      $tail = '';
      $ALL = 1; // Tell get_ids to ignore participant check.
    }
    $E_ids = get_ids( 'SELECT we.cal_id FROM webcal_entry we,
      webcal_entry_user weu WHERE cal_type = \'E\'
      AND cal_date < ' . "'$end_date' $tail", $ALL );
    $M_ids = get_ids ( 'SELECT DISTINCT(we.cal_id) FROM webcal_entry we,
      webcal_entry_user weu, webcal_entry_repeats wer
      WHERE we.cal_type = \'M\'
      AND we.cal_id = wer.cal_id AND we.cal_id = wer.cal_id
      AND cal_end IS NOT NULL AND cal_end < ' . "'$end_date' $tail", $ALL );
    $ids = array_merge ( $E_ids, $M_ids );
  }
  //echo "event ids: <ul><li>" . implode ( "</li><li>", $ids ) . "</li></ul>\n";
  if ( count ( $ids ) > 0 ) {
    purge_events ( $ids );
  } else {
    echo $noneStr;
  }
  echo '
          <h2>' . translate( 'Finished' ) . '</h2>
          <form><input type="button" id="backBtn" value="' . translate( 'Back' )
   . '></form>';
  if ( $purgeDebug ) {
    echo '<div style="border: 1px solid #000;background-color: #ffffff;"><tt>' .
  $sqlLog . '</tt></div>' ."\n";
  }
} else {
?>

<form action="purge.php" method="post" name="purgeform" id="purgeform">
<table>
 <tr><td><label for="user">
  <?php echo translate( 'User_' );?></label></td>
 <td><select name="username">
<?php
  $userlist = get_my_users();
  if ($NONUSER_ENABLED == 'Y' ) {
    $nonusers = get_nonuser_cals();
    $userlist = ($NONUSER_AT_TOP == 'Y' ? array_merge ($nonusers, $userlist) : array_merge ($userlist, $nonusers));
  }
  foreach ( $userlist as $i ) {
    echo $option . $i['cal_login']
      . ( $login == $i['cal_login'] ? '" selected>' : '">' )
      . $i['cal_fullname'] . "</option>\n";
  }
echo $option . 'ALL">' .  $allStr ?></option>
  </select>
 </td></tr>
 <tr><td><label for="purge_all">
  <?php etranslate( 'to delete ALL events for user' )?></label></td>
  <td valign="bottom">
  <input type="checkbox" name="purge_all" value="Y" id="purge_all" onclick="toggle_datefields( 'dateArea', this );">
 </td></tr>
 <tr id="dateArea"><td><label>
  <?php etranslate( 'Delete all events before' )?></label></td><td>
  <?php echo date_selection ( 'end_', date ( 'Ymd' ) ) ?>
 </td></tr>
 <tr><td><label for="purge_deleted">
  <?php etranslate( 'Purge deleted only' )?></label></td>
  <td valign="bottom">
  <input type="checkbox" name="purge_deleted" value="Y">
 </td></tr>
 <tr><td><label for="preview">
  <?php etranslate( 'Preview delete' )?></label></td>
  <td valign="bottom">
  <input type="checkbox" name="preview" value="Y" checked>
 </td></tr>
 <tr><td colspan="2">
  <input type="submit" name="delete" value="<?php
    echo $deleteStr?>" onclick="return confirm( '<?php
    etranslate ( 'really delete events for', true);
    ?> ' + document.forms[0].username.value + '?' )">
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
  global $preview, $boxPreviewStr, $c; // db connection
  global $sqlLog, $allStr;

  $tables = array (
    array ( 'webcal_entry_user', 'cal_id' ),
    array ( 'webcal_entry_repeats', 'cal_id' ),
    array ( 'webcal_entry_repeats_not', 'cal_id' ),
    array ( 'webcal_entry_log', 'cal_entry_id' ),
    array ( 'webcal_entry_categories', 'cal_id' ),
    array ( 'webcal_import_data', 'cal_id' ),
    array ( 'webcal_site_extras', 'cal_id' ),
    array ( 'webcal_reminders', 'cal_id' ),
    array ( 'webcal_entry_ext_user', 'cal_id' ),
    array ( 'webcal_blob', 'cal_id' ),
    array ( 'webcal_entry', 'cal_id' )
  );

  //var_dump($tables);exit;
  $num = array();
  foreach ( $tables as $i ) {
    $i = 0;
  }
  foreach ( $ids as $cal_id ) {
    for ( $i = 0; $tables[$i]; $i++ ) {
      $clause = ( $cal_id == 'ALL' ? '' : " WHERE {$tables[$i][1]} = $cal_id" );
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
  for ( $i = 0; $tables[$i]; $i++ ) {
    $table = $tables[$i][0];
    echo $boxPreviewStr . ' ' .
      str_replace( 'XXX', " $table: {$num[$i]}" , $xxxStr ) .
      "<br>\n";
  }
}
/**
 * get_ids (needs description)
 */
function get_ids ( $sql, $ALL = '' ) {
  global $sqlLog;

  $ids = array();
  $sqlLog .= $sql . "<br>\n";
  $res = dbi_execute ( $sql );
  if ( $res ) {
    while ( $row = dbi_fetch_row ( $res ) ) {
      if ($ALL == 1)
        $ids[] = $row[0];
      else {
        //ONLY Delete event if no other participants.
        $ID = $row[0];
        $res2 = dbi_execute ( 'SELECT COUNT( * ) FROM webcal_entry_user
          WHERE cal_id = ?', array ( $ID ) );
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
