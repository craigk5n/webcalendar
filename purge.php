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
    if ( $user != 'ALL' ) $tail = " AND webcal_entry.cal_create_by = '$user'";
    $E_ids = get_ids ( "SELECT cal_id FROM webcal_entry WHERE cal_type = 'E' AND cal_date < '$end_date' $tail" );
    $M_ids = get_ids ( "SELECT webcal_entry.cal_id FROM webcal_entry INNER JOIN webcal_entry_repeats ON webcal_entry.cal_id = webcal_entry_repeats.cal_id WHERE webcal_entry.cal_type = 'M' AND cal_end IS NOT NULL AND cal_end < '$end_date' $tail" );
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

<?php include_once "includes/trailer.php"; ?>
</BODY>
</HTML>

<?
function purge_events ( $ids ) {
  $tables = array (
    'webcal_entry_user',
    'webcal_entry_repeats',
    'webcal_entry_repeats_not',
    'webcal_import_data',
    'webcal_site_extras',
    'webcal_reminder_log',
    'webcal_entry');
  $TT = sizeof($tables);

  foreach ( $ids as $cal_id ) {
    for ( $i = 0; $i < $TT; $i++ ) {
      dbi_query ( "DELETE FROM $tables[$i] WHERE cal_id like '$cal_id'" );
      $num[$tables[$i]] += mysql_affected_rows();
    }
  }
  for ( $i = 0; $i < $TT; $i++ ) {
    $table = $tables[$i];
    echo "Records deleted from $table: $num[$table]<BR>\n";
  }
}

function get_ids ( $sql ) {
  $ids = array();
  $res = dbi_query ( $sql );
  if ( $res ) {
     while ( $row = dbi_fetch_row ( $res ) ) {
       $ids[] = $row['cal_id'];
     }
  }
  dbi_free_result ( $res );
  return $ids;
}
?>
