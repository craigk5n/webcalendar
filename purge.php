<?php
include_once 'includes/init.php';

if ( ! $is_admin ) {
  // must be admin...
  do_redirect ( "index.php" );
  exit;
}

$INC = array('js/purge.php');
print_header($INC);
?>

<table style="border-width:0px;">
<tr><td style="vertical-align:top; width:50%;">
<?php
$ALL = 0;

$purge_all = getPostValue ( "purge_all" );
$end_year = getPostValue ( "end_year" );
$end_month = getPostValue ( "end_month" );
$end_day = getPostValue ( "end_day" );
$user = getPostValue ( "user" );

if ( ! empty ( $user ) ) {
  echo "<h2>" .
    translate("Purging events for") . " $user...</h2>\n";
  $ids = '';
  $end_date = sprintf ( "%04d%02d%02d, ", $end_year,$end_month,$end_day);
  if ( $purge_all == "Y" ) {
    if ( $user == 'ALL' ) {
      $ids = array ('%');
    } else {
      $ids = get_ids ( "SELECT cal_id FROM webcal_entry WHERE cal_create_by = '$user'" );
    }
  } elseif ( $end_date ) {
    if ( $user != 'ALL' ) {
      $tail = " AND webcal_entry.cal_create_by = '$user'";
    } else {
      $ALL = 1;  // Need this to tell get_ids to ignore participant check
    }
    $E_ids = get_ids ( "SELECT cal_id FROM webcal_entry WHERE cal_type = 'E' AND cal_date < '$end_date' $tail", $ALL );
    $M_ids = get_ids ( "SELECT webcal_entry.cal_id FROM webcal_entry INNER JOIN webcal_entry_repeats ON webcal_entry.cal_id = webcal_entry_repeats.cal_id WHERE webcal_entry.cal_type = 'M' AND cal_end IS NOT NULL AND cal_end < '$end_date' $tail", $ALL );
    $ids = array_merge ( $E_ids, $M_ids );
  }
  if ( $ids ) purge_events ( $ids );
  echo "<h2>..." .
    translate("Finished") . ".</h2>\n";
} else {
?>

<h2><?php etranslate("Delete Events")?></h2>
<a title="<?php etranslate("Admin") ?>" class="nav" href="adminhome.php">&laquo;&nbsp;<?php etranslate("Admin") ?></a><br /><br />
<form action="<?php echo $PHP_SELF; ?>" method="post" name="purgeform">
<table>
	<tr><td>
		<?php etranslate("User");?>:</td><td>
		<select name="user">
<?php
  $userlist = get_my_users ();
  if ($nonuser_enabled == "Y" ) {
    $nonusers = get_nonuser_cals ();
    $userlist = ($nonuser_at_top == "Y") ? array_merge($nonusers, $userlist) : array_merge($userlist, $nonusers);
  }
  for ( $i = 0; $i < count ( $userlist ); $i++ ) {
    echo "<option value=\"".$userlist[$i]['cal_login']."\">".$userlist[$i]['cal_fullname']."</option>\n";
  }
?>
<option value="ALL" selected="selected"><?php etranslate("All")?></option>
		</select>
	</td></tr>
	<tr><td>
		<?php etranslate("Delete all events before");?>:</td><td>
		<?php print_date_selection ( "end_", date ( "Ymd" ) ) ?>
	</td></tr>
	<tr><td>
		<?php etranslate("Check box to delete <b>ALL</b> events for a user")?>:</td><td valign="bottom">
		<input type="checkbox" name="purge_all" value="Y" />
	</td></tr>
	<tr><td colspan="2">
		<input type="submit" name="action" value="<?php etranslate("Delete")?>" onclick="return confirm('<?php etranslate("Are you sure you want to delete events for");?> ' + document.forms[0].user.value + '?')" />
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
  global $c; // db connection
  $tables = array ();
  $tables[0][T] = 'webcal_entry_user';        $tables[0][C] = 'cal_id';
  $tables[1][T] = 'webcal_entry_repeats';     $tables[1][C] = 'cal_id';
  $tables[2][T] = 'webcal_entry_repeats_not'; $tables[2][C] = 'cal_id';
  $tables[3][T] = 'webcal_entry_log';         $tables[3][C] = 'cal_entry_id';
  $tables[4][T] = 'webcal_import_data';       $tables[4][C] = 'cal_id';
  $tables[5][T] = 'webcal_site_extras';       $tables[5][C] = 'cal_id';
  $tables[6][T] = 'webcal_reminder_log';      $tables[6][C] = 'cal_id';
  $tables[7][T] = 'webcal_entry';             $tables[7][C] = 'cal_id';
  $tables[8][T] = 'webcal_import_data';       $tables[8][C] = 'cal_id';
  $TT = sizeof($tables);

//var_dump($tables);exit;

  foreach ( $ids as $cal_id ) {
    for ( $i = 0; $i < $TT; $i++ ) {
      $res = dbi_query ( "DELETE FROM {$tables[$i][T]} WHERE {$tables[$i][C]} like '$cal_id'" );
      $num[$tables[$i][T]] += dbi_affected_rows ( $c, $res );
    }
  }
  for ( $i = 0; $i < $TT; $i++ ) {
    $table = $tables[$i][T];
    echo translate("Records deleted from") .
      " $table: $num[$table]<br />\n";
  }
}

function get_ids ( $sql, $ALL = '' ) {
  $ids = array();
  $res = dbi_query ( $sql );
  if ( $res ) {
    while ( $row = dbi_fetch_row ( $res ) ) {
      if ($ALL == 1) {
        $ids[] = $row['cal_id'];
      } else {
        //ONLY Delete event if no other participants.
        $ID = $row['cal_id'];
        $res2 = dbi_query ( "SELECT COUNT(*) FROM webcal_entry_user " .
          "WHERE cal_id = $ID" );
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