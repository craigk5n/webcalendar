<?php php_track_vars?>
<?php

include "includes/config.inc";
include "includes/php-dbi.inc";
include "includes/functions.inc";
include "includes/validate.inc";
include "includes/connect.inc";

load_user_preferences ();
load_user_layers ();

include "includes/translate.inc";

$error = "";

if ( strlen ( $keywords ) == 0 )
  $error = translate("You must enter one or more search keywords") . ".";

$matches = 0;

?>
<HTML>
<HEAD>
<TITLE><?php etranslate("Title")?></TITLE>
<?php include "includes/styles.inc"; ?>
</HEAD>
<BODY BGCOLOR="<?php echo $BGCOLOR; ?>">

<H2><FONT COLOR="<?php echo $H2COLOR;?>"><?php etranslate("Search Results")?></FONT></H2>

<?php
if ( strlen ( $error ) ) {
  echo "<B>" . translate("Error") . ":</B> $error";
} else {
  $ids = array ();
  $words = split ( " ", $keywords );
  for ( $i = 0; $i < count ( $words ); $i++ ) {
    $sql = "SELECT webcal_entry.cal_id, webcal_entry.cal_name, " .
      "webcal_entry.cal_date " .
      "FROM webcal_entry, webcal_entry_user " .
      "WHERE webcal_entry.cal_id = webcal_entry_user.cal_id " .
      "AND webcal_entry_user.cal_login = '" . $login . "' " .
      "AND ( UPPER(webcal_entry.cal_name) " .
      "LIKE UPPER('%" .  $words[$i] . "%') " .
      "OR UPPER(webcal_entry.cal_description) " .
      "LIKE UPPER('%" .  $words[$i] . "%') ) " .
      "ORDER BY cal_date";
    //echo "SQL: $sql<P>";
    $res = dbi_query ( $sql );
    if ( $res ) {
      while ( $row = dbi_fetch_row ( $res ) ) {
        $matches++;
        $ids[strval ( $row[0] )]++;
        $info[strval ( $row[0] )] = "$row[1] (" . date_to_str ($row[2]) .
          ")";
      }
    }
    dbi_free_result ( $res );
  }
}

if ( $matches > 0 )
  $matches = count ( $ids );

if ( $matches == 1 )
  echo "<B>$matches " . translate("match found") . ".</B><P>";
else if ( $matches > 0 )
  echo "<B>$matches " . translate("matches found") . ".</B><P>";
else
  $error = translate("No matches found") . ".";

// now sort by number of hits
if ( ! strlen ( $error ) ) {
  arsort ( $ids );
  for ( reset ( $ids ); $key = key ( $ids ); next ( $ids ) ) {
    echo "<LI><A HREF=\"view_entry.php?id=$key\">" . $info[$key] . "</A>\n";
  }
}

?>

<P>

<?php include "includes/trailer.inc"; ?>

</BODY>
</HTML>
