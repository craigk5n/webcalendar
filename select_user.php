<?php

include "./includes/config.php";
include "./includes/php-dbi.php";
include "./includes/functions.php";
include "./includes/$user_inc";
include "./includes/validate.php";
include "./includes/connect.php";

load_global_settings ();
load_user_preferences ();
load_user_layers ();

include "./includes/translate.php";

?>
<HTML>
<HEAD>
<TITLE><?php etranslate($application_name)?></TITLE>
<?php include "./includes/styles.php"; ?>
</HEAD>
<BODY BGCOLOR="<?php echo $BGCOLOR;?>" CLASS="defaulttext">


<H2><FONT COLOR="<?php echo $H2COLOR; ?>"><?php etranslate("View Another User's Calendar"); ?></H2></FONT>

<?php
if ( $allow_view_other != "Y" && ! $is_admin ) {
  $error = translate ( "You are not authorized" );
}

if ( ! empty ( $error ) ) {
  echo "<BLOCKQUOTE>$error</BLOCKQUOTE>\n";
} else {
  $userlist = get_my_users ();
  ?>
  <FORM ACTION="<?php echo $STARTVIEW;?>.php" METHOD="GET" NAME="SelectUser">
  <SELECT NAME="user" ONCHANGE="document.SelectUser.submit()">
  <?php
  for ( $i = 0; $i < count ( $userlist ); $i++ ) {
    echo "<OPTION VALUE=\"".$userlist[$i]['cal_login']."\">".$userlist[$i]['cal_fullname']."\n";
  }
  ?>
  </SELECT>
  <INPUT TYPE="submit" VALUE="<?php etranslate("Go")?>"></FORM>
  <?php
}

?>
<P>

<?php include "./includes/trailer.php"; ?>
</BODY>
</HTML>
