<?php
include_once 'includes/init.php';

$error = "";

# update user list
dbi_query ( "DELETE FROM webcal_asst WHERE cal_boss = '$login'" );
for ( $i = 0; $i < count ( $users ); $i++ ) {
  dbi_query ( "INSERT INTO webcal_asst ( cal_boss, cal_assistant ) " .
    "VALUES ( '$login', '$users[$i]' )" );
}

do_redirect ( "assistant_edit.php" );

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
