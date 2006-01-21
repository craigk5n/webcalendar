<?php
/*
 * $Id$
 *
 * Description:
 * Purge events page and handler.
 * When an event is deleted from a user's calendar, it is marked
 * as deleted (webcal_entry_user.cal_status = 'D').  This page
 * will actually clean out the database rather than just mark an
 * event as deleted.
 *
 * Security:
 * Events will only be deleted if they were created by the selected
 * user.  Events where the user was a participant (but not did not
 * create) will remain unchanged.
 *
 */
include_once 'includes/init.php';

// Set this to true do show the SQL at the bottom of the page
$purgeDebug = true;
$sqlLog = '';

if ( ! $is_admin ) {
  // must be admin...
  do_redirect ( "index.php" );
  exit;
}

$ALL = 0;

$action = getPostValue ( "action" );
$do_purge = false;
if ( $action == translate("Delete") ) {
 $do_purge = true;
}

$purge_all = getPostValue ( "purge_all" );
$end_year = getPostValue ( "end_year" );
$end_month = getPostValue ( "end_month" );
$end_day = getPostValue ( "end_day" );
$user = getPostValue ( "user" );
$preview = getPostValue ( "preview" );
$preview = ( empty ( $preview ) ? false : true );

$INC = array('js/purge.php','js/visible.php');

if ( $do_purge ) {
  $BodyX = '';
} else {
  $BodyX = 'onload="all_handler();"';
}

print_header ( $INC, '', $BodyX );
?>

<table style="border-width:0px;">
<tr><td style="vertical-align:top; width:50%;">
<?php
echo "<h2>" . translate("Delete Events" );
if ( $preview )
  echo "[ " . translate("Preview" ) . "]";
echo "</h2>\n";

?>
<a title="<?php etranslate("Admin") ?>" class="nav" href="adminhome.php">&laquo;&nbsp;<?php etranslate("Admin") ?></a><br /><br />
<?php


if ( $do_purge ) {
  if ( $preview ) {
    echo "<h2> [" .  translate("Preview") . "] " .
      translate("Purging events for") . " $user...</h2>\n";
  } else {
    echo "<h2>" .  translate("Purging events for") . ": $user</h2>\n";
  }
  $ids = '';
  $end_date = sprintf ( "%04d%02d%02d", $end_year, $end_month, $end_day );
  if ( $purge_all == "Y" ) {
    if ( $user == 'ALL' ) {
      $ids = array ( 'ALL' );
    } else {
      $ids = get_ids (
        "SELECT cal_id FROM webcal_entry WHERE cal_create_by = '$user'" );
    }
  } elseif ( $end_date ) {
    if ( $user != 'ALL' ) {
      $tail = " AND webcal_entry.cal_create_by = '$user'";
    } else {
      $tail = '';
      $ALL = 1;  // Need this to tell get_ids to ignore participant check
    }
    $E_ids = get_ids ( "SELECT cal_id FROM webcal_entry " .
      "WHERE cal_type = 'E' AND cal_date < '$end_date' $tail",
      $ALL );
    $M_ids = get_ids ( "SELECT webcal_entry.cal_id FROM webcal_entry " .
      "INNER JOIN webcal_entry_repeats ON " .
      "webcal_entry.cal_id = webcal_entry_repeats.cal_id " .
      "WHERE webcal_entry.cal_type = 'M' AND " .
      "cal_end IS NOT NULL AND cal_end < '$end_date' $tail",
      $ALL );
    $ids = array_merge ( $E_ids, $M_ids );
  }
  //echo "event ids: <ul><li>" . implode ( "</li><li>", $ids ) . "</li></ul>\n";
  if ( count ( $ids ) > 0 ) {
    purge_events ( $ids );
  } else {
    echo translate("None");
  }
  echo "<h2>..." .  translate("Finished") . ".</h2>\n";
  if ( $purgeDebug ) {
    echo "<div style=\"border: 1px solid #000;background-color: #fff;\"><tt>$sqlLog</tt></div>\n";
  }
} else {
?>

<form action="purge.php" method="post" name="purgeform">
<table>
 <tr><td><label for="user">
  <?php etranslate("User");?>:</label></td>
 <td><select name="user">
<?php
  $userlist = get_my_users ();
  if ($NONUSER_ENABLED == "Y" ) {
    $nonusers = get_nonuser_cals ();
    $userlist = ($NONUSER_AT_TOP == "Y") ? array_merge($nonusers, $userlist) : array_merge($userlist, $nonusers);
  }
  for ( $i = 0; $i < count ( $userlist ); $i++ ) {
    echo '<option value="' . $userlist[$i]['cal_login'] . '"';
 if ( $login == $userlist[$i]['cal_login'] ) {
  echo ' selected="selected"';
 } 
 echo '>' . $userlist[$i]['cal_fullname'] . "</option>\n";
  }
?>
<option value="ALL"><?php etranslate("All")?></option>
  </select>
 </td></tr>
 <tr><td><label for="purge_all">
  <?php etranslate("Check box to delete <b>ALL</b> events for a user")?>:</label></td>
  <td valign="bottom">
  <input type="checkbox" name="purge_all" value="Y"
                onclick="all_handler()" />
 </td></tr>
 <tr id="dateArea"><td><label>
  <?php etranslate("Delete all events before");?>:</label></td><td>
  <?php print_date_selection ( "end_", date ( "Ymd" ) ) ?>
 </td></tr>
 <tr><td><label for="preview">
  <?php etranslate("Preview delete")?>:</label></td>
  <td valign="bottom">
  <input type="checkbox" name="preview" value="Y" checked="checked" />
 </td></tr>
 <tr><td colspan="2">
  <input type="submit" name="action" value="<?php etranslate("Delete")?>" onclick="return confirm('<?php etranslate("Are you sure you want to delete events for", true);?> ' + document.forms[0].user.value + '?')" />
 </td></tr>
</table>
</form>

<?php } ?>
</td></tr></table>

<?php print_trailer(); ?>
</body>
</html>
<?php
function purge_events ( $ids ) {
  global $preview, $c; // db connection
  global $sqlLog;

  $tables = array (
    array ( 'webcal_entry_user', 'cal_id' ),
    array ( 'webcal_entry_repeats', 'cal_id' ),
    array ( 'webcal_entry_repeats_not', 'cal_id' ),
    array ( 'webcal_entry_log', 'cal_entry_id' ),
    array ( 'webcal_entry_categories', 'cal_id' ),
    array ( 'webcal_import_data', 'cal_id' ),
    array ( 'webcal_site_extras', 'cal_id' ),
    array ( 'webcal_reminder_log', 'cal_id' ),
    array ( 'webcal_entry_ext_user', 'cal_id' ),
    array ( 'webcal_blob', 'cal_id' ),
    array ( 'webcal_entry', 'cal_id' )
  );

  //var_dump($tables);exit;
  $num = array();
  for ( $i = 0; $i < count ( $tables ); $i++ ) {
    $num[$i] = 0;
  }
  foreach ( $ids as $cal_id ) {
    for ( $i = 0; $i < count ( $tables ); $i++ ) {
      $clause = ( $cal_id == 'ALL' ? '' :
        " WHERE {$tables[$i][1]} = $cal_id" );
      if ( $preview ) {
        $sql = "SELECT COUNT(" . $tables[$i][1] .
          ") FROM {$tables[$i][0]}" . $clause;
        //echo "cal_id = '$cal_id'<br />clause = '$clause'<br />";
        //echo "$sql <br />\n";
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
        if ( $cal_id == 'ALL' ) {
          $num[$i] = translate ( "All" );
        } else {
          $num[$i] += dbi_affected_rows ( $c, $res );
        }
      }
    }
  }
  for ( $i = 0; $i < count ( $tables ); $i++ ) {
    $table = $tables[$i][0];
    echo "[" . translate ( "Preview" ) . "] " .
      translate("Records deleted from") .
      " $table: $num[$i]<br />\n";
  }
}

function get_ids ( $sql, $ALL = '' ) {
  global $sqlLog;
  $ids = array ();
  //echo "SQL: $sql <br />\n";
  $sqlLog .= $sql . "<br />\n";
  $res = dbi_execute ( $sql );
  if ( $res ) {
    while ( $row = dbi_fetch_row ( $res ) ) {
      if ($ALL == 1) {
        $ids[] = $row[0];
      } else {
        //ONLY Delete event if no other participants.
        $ID = $row[0];
        $res2 = dbi_execute ( "SELECT COUNT(*) FROM webcal_entry_user " .
          "WHERE cal_id = ?" , array ( $ID ) );
        if ( $res2 ) {
          if ( $row2 = dbi_fetch_row ( $res2 ) ) {
            if ( $row2[0] == 1 ) $ids[] = $ID;
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
