<?php
/* $Id$ */
include_once 'includes/init.php';

// input args in URL
// users:  list of comma-separated users
// form:   name of form on parent page
// listid: element id of user selection object in form
//         ... to be used like form.elements[$listid]
$progErrStr=translate ( 'Program Error' ).' ';
if ( empty ( $form ) ) {
  echo $progErrStr . str_replace ( 'XXX',
    translate ( 'form' ), translate ( 'No XXX specified!' ) );
  exit;
}
if ( empty ( $listid ) ) {
  echo $progErrStr . str_replace ( 'XXX',
    translate ( 'listid' ), translate ( 'No XXX specified!' ) );
  exit;
}

// Parse $users.
$exp = split ( ',', $users );
$groups = $selected = $sql_params = array ();
for ( $i = 0, $cnt = count ( $exp ); $i < $cnt; $i++ ) {
  $selected[$exp[$i]] = 1;
}

$owner = ( $WC->isNonuserAdmin() ? $WC->userId() : $WC->loginId() );

// Load list of groups.
$sql = 'SELECT wg.cal_group_id, wg.cal_name FROM webcal_group wg';

if ( getPref ( '_USER_SEES_ONLY_HIS_GROUPS' ) ) {
  $sql .= ', webcal_group_user wgu WHERE wg.cal_group_id = wgu.cal_group_id
    AND wgu.cal_login_id = ?';
  $sql_params[] = $owner;
}

$res = dbi_execute ( $sql . ' ORDER BY wg.cal_name', $sql_params );

if ( $res ) {
  while ( $row = dbi_fetch_row ( $res ) ) {
    $groups[] = array (
      'cal_group_id' => $row[0],
      'cal_name' => $row[1]
      );
  }
  dbi_free_result ( $res );
}

build_header ( '', '', '', 29 );

ob_start ();

echo '
    <script language="javascript" type="text/javascript">';

include 'includes/js/usersel.js';

echo '
    </script>
    <center>
      <form action="#" name="userselform">
        <table style="border: 0; width: 100%;" summary="">
          <tr>
            <td class="alignT">
              <b>' . translate ( 'Users' ) . ':</b><br />
              <select name="users" size="15" multiple="multiple">';

$users = get_my_users ();
if ( getPref ( '_ENABLE_NONUSERS' ) ) {
  $nonusers = get_my_nonusers ( $WC->loginId(), true );
  $users = ( getPref ( '_NONUSER_AT_TOP' )
    ? array_merge ( $nonusers, $users ) : array_merge ( $users, $nonusers ) );
}
for ( $i = 0, $cnt = count ( $users ); $i < $cnt; $i++ ) {
  $u = $users[$i]['cal_login'];
  echo '
                <option value="' . $u . '"'
   . ( ! empty ( $selected[$u] ) ? SELECTED : '' )
   . '>' . $users[$i]['cal_fullname'] . '</option>';
}

echo '
              </select><br />
              <input type="button" value="' . translate ( 'All' )
 . '" onclick="selectAll()" />
              <input type="button" value="' . translate ( 'None' )
 . '" onclick="selectNone()" />
              <input type="reset" value="' . translate ( 'Reset' ) . '" />
            </td>
            <td valign="top">
              <b>' . translate ( 'Groups' ) . ':</b><br />
              <select name="groups" size="15">';

for ( $i = 0, $cnt = count ( $groups ); $i < $cnt; $i++ ) {
  echo '
                <option value="' . $groups[$i]['cal_group_id'] . '">'
   . $groups[$i]['cal_name'] . '</option>';
}

echo '
              </select><br />
              <input type="button" value="' . translate ( 'Add' )
 . '" onclick="selectGroupMembers();" />
              <input type="button" value="' . translate ( 'Remove' )
 . '" onclick="deselectGroupMembers();" />
            </td>
          </tr>
          <tr>
            <td style="text-align:center;" colspan="2"><br /><br />
              <input type="button" value="' . translate ( 'OK' )
 . '" onclick="OkButton()" />
              <input type="button" value="' . translate ( 'Cancel' )
 . '" onclick="window.close()" />
            </td>
          </tr>
        </table>
      </form>
    </center>';

ob_end_flush ();

echo print_trailer ( false, true, true );

?>
