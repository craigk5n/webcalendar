<?php

include "includes/config.php";
include "includes/php-dbi.php";
include "includes/functions.php";
include "includes/$user_inc";

include "includes/validate.php";
include "includes/connect.php";

load_global_settings ();
load_user_preferences ();
load_user_layers ();

include "includes/translate.php";

$error = "";

# update user list
dbi_query ( "DELETE FROM webcal_asst WHERE cal_boss = '$login'" );
for ( $i = 0; $i < count ( $users ); $i++ ) {
  dbi_query ( "INSERT INTO webcal_asst ( cal_boss, cal_assistant ) " .
    "VALUES ( '$login', '$users[$i]' )" );
}

do_redirect ( "assistant_edit.php" );
?>
<HTML>
<HEAD>
<TITLE><?php etranslate($application_name)?></TITLE>
<?php include "includes/styles.php"; ?>
</HEAD>
<BODY BGCOLOR="<?php echo $BGCOLOR;?>" CLASS="defaulttext">

<H2><FONT COLOR="<?php echo $H2COLOR;?>"><?php etranslate("Error")?></FONT></H2>

<BLOCKQUOTE>
<?php

echo $error;
//if ( $sql != "" )
//  echo "<P><B>SQL:</B> $sql";
//?>
</BLOCKQUOTE>

<?php include "includes/trailer.php"; ?>
</BODY>
</HTML>
