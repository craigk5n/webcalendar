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

<TABLE BORDER=0>
<TR><TD VALIGN="top" WIDTH=50%>

<?php
$ALL = 0;
if ( ! empty ( $user ) ) {
  echo "<H2><FONT COLOR=\"$H2COLOR\">Purging events for $user...</FONT></H2>\n";
  $ids = '';
  $end_date = sprintf ( "%04d%02d%02d, ", $end_year,$end_month,$end_day);
  if ( $purge_all == "Y" ) {
    if ( $user == 'ALL' ) {
      $ids = array ('%');
    } else {
      $ids = get_ids ( "SELECT cal_id FROM webcal_entry  WHERE cal_create_by = '$user'" );
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
  echo "<H2><FONT COLOR=\"$H2COLOR\">...Finished.</FONT></H2>\n";
} else {
?>
<H2><FONT COLOR="<?=$H2COLOR?>">Delete Events</FONT></H2>
<FORM ACTION="<?=$PHP_SELF;?>" METHOD="POST" NAME="purgeform">
<TABLE>
 <TR><TD>User:</TD><TD>
<SELECT NAME="user">

<?
  $userlist = get_my_users ();
  if ($nonuser_enabled == "Y" ) {
    $nonusers = get_nonuser_cals ();
    $userlist = ($nonuser_at_top == "Y") ? array_merge($nonusers, $userlist) : array_merge($userlist, $nonusers);
  }
  for ( $i = 0; $i < count ( $userlist ); $i++ ) {
    echo "<OPTION VALUE=\"".$userlist[$i]['cal_login']."\">".$userlist[$i]['cal_fullname']."\n";
  }
?>

<OPTION VALUE="ALL" SELECTED>ALL USERS
</SELECT></TD></TR>
<TR><TD>Delete all events before:</TD><TD>
<? print_date_selection ( "end_", date ( "Ymd" ) ) ?>
</TD></TR>
<TR><TD>Check box to delete<BR><B>ALL</B> events for a user:</TD><TD valign="bottom"><INPUT TYPE="checkbox" NAME="purge_all" VALUE="Y"></TD></TR>
<TR><TD colspan='2'>
<INPUT TYPE="submit" NAME="action" VALUE="<?php etranslate("Delete")?>" ONCLICK="return confirm('Are you sure you want to delete events for ' + document.forms[0].user.value + '?')">
</TD></TR></TABLE>
</FORM>

<?php } ?>
</TD></TR></TABLE>

<?php print_trailer(); ?>
</BODY>
</HTML>

<?
function purge_events ( $ids ) {
  $tables = array ();
  $tables[0][T] = 'webcal_entry_user';        $tables[0][C] = 'cal_id';
  $tables[1][T] = 'webcal_entry_repeats';     $tables[1][C] = 'cal_id';
  $tables[2][T] = 'webcal_entry_repeats_not'; $tables[2][C] = 'cal_id';
  $tables[3][T] = 'webcal_entry_log';         $tables[3][C] = 'cal_entry_id';
  $tables[4][T] = 'webcal_import_data';       $tables[4][C] = 'cal_id';
  $tables[5][T] = 'webcal_site_extras';       $tables[5][C] = 'cal_id';
  $tables[6][T] = 'webcal_reminder_log';      $tables[6][C] = 'cal_id';
  $tables[7][T] = 'webcal_entry';             $tables[7][C] = 'cal_id';
  $TT = sizeof($tables);

//var_dump($tables);exit;

  foreach ( $ids as $cal_id ) {
    for ( $i = 0; $i < $TT; $i++ ) {
      dbi_query ( "DELETE FROM {$tables[$i][T]} WHERE {$tables[$i][C]} like '$cal_id'" );
      $num[$tables[$i][T]] += mysql_affected_rows();
    }
  }
  for ( $i = 0; $i < $TT; $i++ ) {
    $table = $tables[$i][T];
    echo "Records deleted from $table: $num[$table]<BR>\n";
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
