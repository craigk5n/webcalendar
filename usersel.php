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
print_header($INC,'','',true);
?>

<center>
<form action="#" name="userselform">
<table style="border-width:0px; width:100%;">
<tr><td style="vertical-align:top;">
<b><?php etranslate("Users"); ?>:</b><br />
<select name="users" size="15" multiple="multiple">
<?php

$users = get_my_users ();
if ($nonuser_enabled == "Y" ) {
  $nonusers = get_nonuser_cals ();
  $users = ($nonuser_at_top == "Y") ? array_merge($nonusers, $users) : array_merge($users, $nonusers);
}

for ( $i = 0; $i < count ( $users ); $i++ ) {
  $u = $users[$i]['cal_login'];
  echo "<option value=\"$u\"";
  if ( ! empty ( $selected[$u] ) )
    echo " selected=\"selected\"";
  echo ">" . $users[$i]['cal_fullname'] . "</option>\n";
}
?>
</select><br />
<input type="button" value="<?php etranslate("All");?>" onclick="selectAll()" />
<input type="button" value="<?php etranslate("None");?>" onclick="selectNone()" />
<input type="reset" value="<?php etranslate("Reset");?>" />
</td>

<td valign="top">
<b><?php etranslate("Groups"); ?>:</b><br />
<select name="groups" size="15">
<?php
for ( $i = 0; $i < count ( $groups ); $i++ ) {
  echo "<option value=\"" . $groups[$i]['cal_group_id'] .
      "\">" . $groups[$i]['cal_name'] . "</option>\n";
}
?>
</select><br />
<input type="button" value="<?php etranslate("Add");?>" onclick="selectGroupMembers();" />
<input type="button" value="<?php etranslate("Remove");?>" onclick="deselectGroupMembers();" />
</td></tr>

<tr><td style="text-align:center;" colspan="2">
<br /><br />
<input type="button" value="<?php etranslate("Ok");?>" onclick="OkButton()" />
<input type="button" value="<?php etranslate("Cancel");?>" onclick="window.close()" />
</td></tr>

</table>

<?php print_trailer ( false, true, true ); ?>
</body>
</html>