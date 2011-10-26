<?php // $Id$
include_once 'includes/init.php';

$groupname = $groupowner = $groupupdated = '';
$newgroup = true;

if ( empty ( $id ) )
  $groupname = translate ( 'Unnamed Group' );
else {
  $newgroup = false;
  // Get group by id.
  $res = dbi_execute ( 'SELECT cal_owner, cal_name, cal_last_update, cal_owner
    FROM webcal_group WHERE cal_group_id = ?', array ( $id ) );
  if ( $res ) {
    if ( $row = dbi_fetch_row ( $res ) ) {
      $groupname = $row[1];
      $groupupdated = $row[2];
      user_load_variables ( $row[3], 'temp' );
      $groupowner = $tempfullname;
    }
    dbi_fetch_row ( $res );
  }
}

ob_start();
print_header( '', '', '', true );

echo '
    <form action="group_edit_handler.php" method="post">
      <input type="hidden" name="';

if ( $newgroup ) {
  $v = array();
  echo 'add" value="1">
      <h2>' .  translate( 'Add Group' ) . '</h2>
} else
  echo 'id" value="' . $id">
      <h2>' . translate( 'Edit Group' ) . '</h2>';

echo '
      <table summary="">
        <tr>
          <td><label for="groupname">'
 . translate( 'Group name' ) . '</label></td>
          <td><input type="text" name="groupname" id="groupname" size="20" '
 . 'value="' . htmlspecialchars( $groupname ) . '"></td>
        </tr>' . ( $newgroup ? '' : '
        <tr>
          <td>' . translate( 'Updated' ) . '</td>
          <td>' . date_to_str ( $groupupdated ) . '</td>
        </tr>
        <tr>
          <td>' . translate( 'Created by' ) . '</td>
          <td>' . $groupowner . '</td>
        </tr>' ) . '
        <tr>
          <td><label for="users">' . translate( 'Users_' ) . '</label></td>
          <td>
            <select name="users[]" id="users" size="10" multiple>';

// Get list of all users.
$users = user_get_users();
if ( $NONUSER_ENABLED == 'Y' ) {
  $nonusers = get_nonuser_cals();
  $users = ( $NONUSER_AT_TOP == 'Y' )
  ? array_merge ( $nonusers, $users ) : array_merge ( $users, $nonusers );
}

// Get list of users for this group.
if ( ! $newgroup ) {
  $res = dbi_execute ( 'SELECT cal_login FROM webcal_group_user
    WHERE cal_group_id = ?', array ( $id ) );
  if ( $res ) {
    while ( $row = dbi_fetch_row ( $res ) ) {
      $groupuser[$row[0]] = 1;
    }
    dbi_free_result ( $res );
  }
}
for ( $i = 0, $cnt = count ( $users ); $i < $cnt; $i++ ) {
  $u = $users[$i]['cal_login'];
  echo '
              <option value="' . $u
   . ( empty( $groupuser[$u] ) ?  '">' : '" selected>' )
   . $users[$i]['cal_fullname'] . '</option>';
}

echo '
            </select>
          </td>
        </tr>
        <tr>
          <td colspan="2"><br>
            <input type="submit" name="action" value="'
 . ( $newgroup ? $addStr . '">' : $saveStr . '">
            <input type="submit" id="delGrpEntry" name="delete" value="'
   . $deleteStr . '">' ) . '
          </td>
        </tr>
      </table>
    </form>' . print_trailer ( false, true, true );

ob_end_flush();

?>
