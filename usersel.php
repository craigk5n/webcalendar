<?php
include_once 'includes/init.php';

// input args in URL
// users: list of comma-separated users
// form: name of form on parent page
// listid: element id of user selection object in form
//   ... to be used like form.elements[$listid]
if ( empty ( $form ) ) {
  echo "Program Error: No form specified!"; exit;
}
if ( empty ( $listid ) ) {
  echo "Program Error: No listid specified!"; exit;
}

// parse $users
$exp = split ( ",", $users );
$selected = array ();
for ( $i = 0; $i < count ( $exp ); $i++ ) {
  $selected[$exp[$i]] = 1;
}

// load list of groups
if ( $user_sees_only_his_groups == "Y" ) {
  $sql =
    "SELECT webcal_group.cal_group_id, webcal_group.cal_name " .
    "FROM webcal_group, webcal_group_user " .
    "WHERE webcal_group.cal_group_id = webcal_group_user.cal_group_id " .
    "AND webcal_group_user.cal_login = '$login' " .
    "ORDER BY webcal_group.cal_name";
} else {
  // show all groups
  $sql = "SELECT cal_group_id, cal_name FROM webcal_group " .
    "ORDER BY cal_name";
}

$res = dbi_query ( $sql );
$groups = array ();
if ( $res ) {
  while ( $row = dbi_fetch_row ( $res ) ) {
    $groups[] = array (
      "cal_group_id" => $row[0],
      "cal_name" => $row[1]
      );
  }
  dbi_free_result ( $res );
}

$INC = array('js/usersel.php');
print_header($INC);
?>

<CENTER>
<FORM ACTION="#">


<TABLE BORDER="0" WIDTH="100%">
<TR><TD VALIGN="top">
<B><?php etranslate("Users"); ?>:</B><BR>
<SELECT NAME="users" SIZE="15" MULTIPLE>
<?php

$users = get_my_users ();
if ($nonuser_enabled == "Y" ) {
  $nonusers = get_nonuser_cals ();
  $users = ($nonuser_at_top == "Y") ? array_merge($nonusers, $users) : array_merge($users, $nonusers);
}

for ( $i = 0; $i < count ( $users ); $i++ ) {
  $u = $users[$i]['cal_login'];
  echo "<OPTION VALUE=\"$u\" ";
  if ( ! empty ( $selected[$u] ) )
    echo "SELECTED";
  echo "> " . $users[$i]['cal_fullname'];
}
?>
</SELECT><BR>
<INPUT TYPE="button" VALUE="<?php etranslate("All");?>"
  ONCLICK="selectAll()">
<INPUT TYPE="button" VALUE="<?php etranslate("None");?>"
  ONCLICK="selectNone()">
<INPUT TYPE="reset" VALUE="<?php etranslate("Reset");?>">
</TD>

<TD VALIGN="top">
<B><?php etranslate("Groups"); ?>:<B><BR>
<SELECT NAME="groups" SIZE="15">
<?php
for ( $i = 0; $i < count ( $groups ); $i++ ) {
  echo "<OPTION VALUE=\"" . $groups[$i]['cal_group_id'] .
      "\">" . $groups[$i]['cal_name'] . "</OPTION>\n";
}
?>
</SELECT><BR>
<INPUT TYPE="button" VALUE="<?php etranslate("Add");?>"
  ONCLICK="selectGroupMembers();">
<INPUT TYPE="button" VALUE="<?php etranslate("Remove");?>"
  ONCLICK="deselectGroupMembers();">
</TD></TR>

<TR><TD COLSPAN="2"><CENTER>
<BR><BR>
<INPUT TYPE="button" VALUE="<?php etranslate("Ok");?>"
  ONCLICK="OkButton()">
<INPUT TYPE="button" VALUE="<?php etranslate("Cancel");?>"
  ONCLICK="window.close()">
</CENTER></TD></TR>

</TABLE>

</BODY>
</HTML>
