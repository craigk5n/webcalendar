<?php
include_once 'includes/init.php';

if ( $groups_enabled == "Y" ) $INC = array('js/search.php');
print_header($INC);
?>

<H2><FONT COLOR="<?php echo $H2COLOR;?>"><?php if ( empty ( $advanced ) ) { etranslate("Search"); } else { etranslate ( "Advanced Search" ); } ?></FONT></H2>

<FORM ACTION="search_handler.php" METHOD="POST" NAME="searchformentry">

<?php if ( empty ( $advanced ) ) { ?>

<B><?php etranslate("Keywords")?>:</B>
<INPUT NAME="keywords" SIZE=30>
<INPUT TYPE="submit" VALUE="<?php etranslate("Search")?>">

<P>
<A CLASS="navlinks" HREF="search.php?advanced=1"><?php etranslate("Advanced Search") ?></A>

<?php } else {
$show_participants = ( $disable_participants_field != "Y" );
if ( $is_admin )
  $show_participants = true;
if ( $login == "__public__" && $public_access_others != "Y" )
  $show_participants = false;

?>

<TABLE BORDER="0">

<INPUT TYPE="hidden" NAME="advanced" VALUE="1">

<TR><TD><B><?php etranslate("Keywords")?>:</B></TD>
<TD><INPUT NAME="keywords" SIZE=30></TD>
<TD><INPUT TYPE="submit" VALUE="<?php etranslate("Search")?>"></TD></TR>

<?php if ( $show_participants ) { ?>
<TR><TD VALIGN="top"><B><?php etranslate("Users"); ?></B></TD>
<?php
  $users = get_my_users ();
  $size = 0;
  $out = "";
  for ( $i = 0; $i < count ( $users ); $i++ ) {
    $out .= "<OPTION VALUE=\"" . $users[$i]['cal_login'] . "\"";
    if ( $users[$i]['cal_login'] == $login )
      $out .= " SELECTED";
    $out .= "> " . $users[$i]['cal_fullname'];
  }
  if ( count ( $users ) > 50 )
    $size = 15;
  else if ( count ( $users ) > 10 )
    $size = 10;
  else
    $size = count ( $users );
?>
<TD><SELECT NAME="users[]" SIZE="<?php echo $size;?>" MULTIPLE><?php echo $out; ?></SELECT>
<?php 
  if ( $groups_enabled == "Y" ) {
    echo "<INPUT TYPE=\"button\" ONCLICK=\"selectUsers()\" VALUE=\"" .
      translate("Select") . "...\">";
  }
?>
</TD></TR>

<?php } /* if show_participants */ ?>

</TABLE>

<?php } ?>

</FORM>

<?php include_once "includes/trailer.php"; ?>
</BODY>
</HTML>