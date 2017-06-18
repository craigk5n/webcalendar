<?php
/* $Id: usersel.php,v 1.34.2.3 2008/10/15 03:05:11 cknudsen Exp $ */
include_once 'includes/init.php';

// input args in URL
// users:  list of comma-separated users
// form:   name of form on parent page
// listid: element id of user selection object in form
//         ... to be used like form.elements[$listid]
$users = getGetValue ( 'users' );
$form = getGetValue ( 'form' );
$listid = getGetValue ( 'listid' );
$progErrStr = translate ( 'Program Error No XXX specified!' );
if ( empty ( $form ) ) {
  echo str_replace ( 'XXX', translate ( 'form' ), $progErrStr );
  exit;
}
if ( empty ( $listid ) ) {
  echo str_replace ( 'XXX', translate ( 'listid' ), $progErrStr );
  exit;
}

// Parse $users.
$exp = split ( ',', $users );
$groups = $selected = $sql_params = array ();
for ( $i = 0, $cnt = count ( $exp ); $i < $cnt; $i++ ) {
  $selected[$exp[$i]] = 1;
}

$owner = ( $is_nonuser_admin || $is_assistant ? $user : $login );

// Load list of groups.
$sql = 'SELECT wg.cal_group_id, wg.cal_name FROM webcal_group wg';

if ( $USER_SEES_ONLY_HIS_GROUPS == 'Y' ) {
  $sql .= ', webcal_group_user wgu WHERE wg.cal_group_id = wgu.cal_group_id
    AND wgu.cal_login = ?';
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

print_header ( '', '', '', true, false, true );

ob_start ();

echo '
    <script language="javascript" type="text/javascript">';

include 'includes/js/usersel.php';

echo '
    </script>
    <center>
      <form action="#" name="userselform">
        <table style="borderh: 0; width: 100%;">
          <tr>
            <td class="aligntop">
              <b>' . translate ( 'Users' ) . ':</b><br />
              <select name="users" size="15" multiple="multiple">';

$users = get_my_users ();
if ( $NONUSER_ENABLED == 'Y' ) {
  $nonusers = get_my_nonusers ( $login, true );
  $users = ( $NONUSER_AT_TOP == 'Y'
    ? array_merge ( $nonusers, $users ) : array_merge ( $users, $nonusers ) );
}
for ( $i = 0, $cnt = count ( $users ); $i < $cnt; $i++ ) {
  $u = $users[$i]['cal_login'];
  echo '
                <option value="' . $u . '"'
   . ( ! empty ( $selected[$u] ) ? ' selected="selected"' : '' )
   . '>' . $users[$i]['cal_fullname'] . '</option>';
}

echo '
              </select><br />
              <input type="button" value="' . translate ( 'All' )
 . '" onclick="selectAll( true )" />
              <input type="button" value="' . translate ( 'None' )
 . '" onclick="selectAll( false )" />
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
 . '" onclick="toggleGroup( true );" />
              <input type="button" value="' . translate ( 'Remove' )
 . '" onclick="toggleGroup( false );" />
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
      </form
    </center>';

ob_end_flush ();

echo print_trailer ( false, true, true );

?>
