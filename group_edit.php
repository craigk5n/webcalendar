<?php
/* $Id: group_edit.php,v 1.31 2007/07/28 19:21:57 bbannon Exp $ */
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

print_header ( '', '', '', true );

ob_start ();

echo '
    <form action="group_edit_handler.php" method="post">
      <h2>';

if ( $newgroup ) {
  $v = array ();
  echo translate ( 'Add Group' ) . '</h2>
      <input type="hidden" name="add" value="1';
} else
  echo translate ( 'Edit Group' ) . '</h2>
      <input type="hidden" name="id" value="' . $id;

echo '" />
      <table>
        <tr>
          <td class="bold"><label for="groupname">'
 . translate ( 'Group name' ) . ':</label></td>
          <td><input type="text" name="groupname" id="groupname" size="20" '
 . 'value="' . htmlspecialchars ( $groupname ) . '" /></td>
        </tr>' . ( ! $newgroup ? '
        <tr>
          <td class="aligntop bold">' . translate ( 'Updated' ) . ':</td>
          <td>' . date_to_str ( $groupupdated ) . '</td>
        </tr>
        <tr>
          <td class="aligntop bold">' . translate ( 'Created by' ) . ':</td>
          <td>' . $groupowner . '</td>
        </tr>' : '' ) . '
        <tr>
          <td class="aligntop bold"><label for="users">'
 . translate ( 'Users' ) . ':</label></td>
          <td>
            <select name="users[]" id="users" size="10" multiple="multiple">';

// Get list of all users.
$users = user_get_users ();
if ( $NONUSER_ENABLED == 'Y' ) {
  $nonusers = get_nonuser_cals ();
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
              <option value="' . $u . '" '
   . ( ! empty ( $groupuser[$u] ) ? ' selected="selected"' : '' )
   . '>' . $users[$i]['cal_fullname'] . '</option>';
}

echo '
            </select>
          </td>
        </tr>
        <tr>
          <td colspan="2" class="aligncenter"><br />
            <input type="submit" name="action" value="'
 . ( $newgroup ? translate ( 'Add' ) : translate ( 'Save' ) ) . '" />'
 . ( ! $newgroup ? '
            <input type="submit" name="delete" value="'
   . translate ( 'Delete' ) . '" onclick="return confirm( \''
   . str_replace ( 'XXX', translate ( 'entry' ),
    translate ( 'Are you sure you want to delete this XXX?' ) )
   . '\')" />' : '' ) . '
          </td>
        </tr>
      </table>
    </form>
    ';

ob_end_flush ();

echo print_trailer ( false, true, true );

?>
