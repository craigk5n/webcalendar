<?php
include_once 'includes/init.php';

$error = "";

$keywords = getValue ( "keywords" );
$advanced = getValue ( "advanced" );

if ( strlen ( $keywords ) == 0 )
  $error = translate("You must enter one or more search keywords") . ".";

$matches = 0;

$search_others = ( ! empty ( $advanced ) );
if ( $login == "__public__" && $public_access_others != "Y" )
  $search_others = false;
if ( $readonly == "Y" || $single_user == "Y" )
  $search_others = false;

print_header();
?>

<h2><?php etranslate("Search Results")?></h2>

<?php
if ( ! empty ( $error ) ) {
  echo "<span style=\"font-weight:bold;\">" . translate("Error") . ":</span> $error";
} else {
  $ids = array ();
  $words = split ( " ", $keywords );
  for ( $i = 0; $i < count ( $words ); $i++ ) {
    // Note: we only search approved events
    $sql = "SELECT webcal_entry.cal_id, webcal_entry.cal_name, " .
      "webcal_entry.cal_date " .
      "FROM webcal_entry, webcal_entry_user " .
      "WHERE webcal_entry.cal_id = webcal_entry_user.cal_id " .
      "AND webcal_entry_user.cal_status in ('A','W') " .
      "AND webcal_entry_user.cal_login IN ( ";
    if ( $search_others ) {
      if ( empty ( $users[0] ) )
        $users[0] = $login;
      for ( $j = 0; $j < count ( $users ); $j++ ) {
        if ( $j > 0 )
          $sql .= ", ";
        $sql .= " '$users[$j]'";
      }
    } else
      $sql .= " '$login' ";
    $sql .= ") ";
    if ( $search_others ) {
      // Don't search confidential entries of other users.
      $sql .= "AND ( webcal_entry_user.cal_login = '$login' OR " .
        "( webcal_entry_user.cal_login != '$login' AND " .
	"webcal_entry.cal_access = 'P' ) ) ";
    }
    $sql .= "AND ( UPPER(webcal_entry.cal_name) " .
      "LIKE UPPER('%" .  $words[$i] . "%') " .
      "OR UPPER(webcal_entry.cal_description) " .
      "LIKE UPPER('%" .  $words[$i] . "%') ) " .
      "ORDER BY cal_date";
    //echo "SQL: $sql<br />";
    $res = dbi_query ( $sql );
    if ( $res ) {
      while ( $row = dbi_fetch_row ( $res ) ) {
        $matches++;
        $idstr = strval ( $row[0] );
        if ( empty ( $ids[$idstr] ) )
          $ids[$idstr] = 1;
        else
          $ids[$idstr]++;
        $info[$idstr] = "$row[1] (" . date_to_str ($row[2]) .
          ")";
      }
    }
    dbi_free_result ( $res );
  }
}

if ( $matches > 0 )
  $matches = count ( $ids );

if ( $matches == 1 )
  echo "<span style=\"font-weight:bold;\">$matches " . translate("match found") . ".</span><br /><br />";
else if ( $matches > 0 )
  echo "<span style=\"font-weight:bold;\">$matches " . translate("matches found") . ".</span><br /><br />";
else
  echo translate("No matches found") . ".";

// now sort by number of hits
if ( empty ( $error ) ) {
  arsort ( $ids );
  for ( reset ( $ids ); $key = key ( $ids ); next ( $ids ) ) {
    echo "<li><a class=\"nav\" href=\"view_entry.php?id=$key\">" . $info[$key] . "</a></li>\n";
  }
}

?>
<br /><br />

<?php print_trailer(); ?>

</body>
</html>