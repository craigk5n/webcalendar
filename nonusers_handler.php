<?php
include_once 'includes/init.php';
load_user_layers ();

if ( ! $is_admin ) {
  echo "<H2><FONT COLOR=\"$H2COLOR\">" . translate("Error") .
    "</FONT></H2>" . translate("You are not authorized") . ".\n";
  include_once "includes/trailer.php";
  echo "</BODY></HTML>\n";
  exit;
}
$error = "";

$id = $NONUSER_PREFIX.$id;

if ( $action == "Delete" || $action == translate ("Delete") ) {
 // delete this nonuser calendar
    if ( ! dbi_query ( "DELETE FROM webcal_nonuser_cals " .
     "WHERE cal_login = '$id'" ) )
     $error = translate ("Database error") . ": " . dbi_error();

} else {
  if ( $action == "Save" || $action == translate ("Save") ) {
  // Updating
    $sql = "UPDATE webcal_nonuser_cals SET cal_lastname = '$nlastname', " .
           "cal_firstname = '$nfirstname', cal_admin = '$nadmin' " .
           "WHERE cal_login = '$id'";
    if ( ! dbi_query ( $sql ) ) {
      $error = translate ("Database error") . ": " . dbi_error();
    }
  } else {
  // Adding
    $sql = "INSERT INTO webcal_nonuser_cals " .
    "( cal_login, cal_firstname, cal_lastname, cal_admin ) " .
    "VALUES ( '$id', '$nfirstname', '$nlastname', '$nadmin' )";
    if ( ! dbi_query ( $sql ) ) {
      $error = translate ("Database error") . ": " . dbi_error();
    }
  }
}
if ( empty ( $error ) ) do_redirect ( "nonusers.php" );

print_header();
?>

<H2><FONT COLOR="<?php echo $H2COLOR;?>"><?php etranslate("Error")?></FONT></H2>

<BLOCKQUOTE>
<?php

echo $error;
//if ( $sql != "" )
//  echo "<P><B>SQL:</B> $sql";
//?>
</BLOCKQUOTE>

<?php include_once "includes/trailer.php"; ?>
</BODY>
</HTML>