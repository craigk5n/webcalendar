<?php
/* $Id: edit_nonusers.php,v 1.23.2.6 2008/05/23 14:14:11 umcesrjones Exp $ */
include_once 'includes/init.php';
print_header ( array ( 'js/edit_nonuser.php/false' ),
  '', '', true, '', true, false );

if ( ! $is_admin ) {
  echo print_not_auth ( 3, true ) . '
  </body>
</html>';
  exit;
}
if ( ! $NONUSER_PREFIX ) {
  echo print_error_header () . translate ( 'NONUSER_PREFIX not set' ) . '.
  </body>
</html>';
  exit;
}
$add = getValue ( 'add' );
$nid = getValue ( 'nid' );

// Adding/Editing nonuser calendar.
if ( ( ( $add == '1' ) || ( ! empty ( $nid ) ) ) && empty ( $error ) ) {
  $userlist = user_get_users ();
  $button = translate ( 'Add', true );
    $buttonAction = 'Add';
  $nid = clean_html ( $nid );

  if ( ! empty ( $nid ) ) {
    nonuser_load_variables ( $nid, 'nonusertemp_' );
    $id_display = $nid . '
      <input type="hidden" name="nid" value="' . $nid . '" />';
    $button = translate ( 'Save', true );
        $buttonAction = 'Save';
    //$nonusertemp_login = substr ( $nonusertemp_login,
   //   strlen ( $NONUSER_PREFIX ) );
  } else
    $id_display = '
      <input type="text" name="nid" id="calid" size="20" '
     . 'onchange="check_name();" maxlength="20" /> '
     . translate ( 'word characters only' );

  ob_start ();

  echo '
    <form action="edit_nonusers_handler.php" name="editnonuser" method="post" '
   . 'onsubmit="return valid_form( this );">'
   . ( empty ( $nonusertemp_admin ) ? '' : '
      <input type="hidden" name="old_admin" value="'
     . $nonusertemp_admin . '" />' ) . '
      <h2>' . ( empty ( $nid )
    ? translate ( 'Add User' ) : translate ( 'Edit User' ) ) . '</h2>
      <table>
        <tr>
          <td><label for="calid">' . translate ( 'Calendar ID' )
   . ':</label></td>
          <td>' . $id_display . '</td>
        </tr>
        <tr>
          <td><label for="nfirstname">' . translate ( 'First Name' )
   . ':</label></td>
          <td><input type="text" name="nfirstname" id="nfirstname" size="20" '
   . 'maxlength="25" value="'
   . ( empty ( $nonusertemp_firstname )
    ? '' : htmlspecialchars ( $nonusertemp_firstname ) ) . '" /></td>
        </tr>
        <tr>
          <td><label for="nlastname">' . translate ( 'Last Name' )
   . ':</label></td>
          <td><input type="text" name="nlastname" id="nlastname" size="20" '
   . 'maxlength="25" value="'
   . ( empty ( $nonusertemp_lastname )
    ? '' : htmlspecialchars ( $nonusertemp_lastname ) ) . '" /></td>
        </tr>
        <tr>
          <td><label for="nadmin">' . translate ( 'Admin' ) . ':</label></td>
          <td>
            <select name="nadmin" id="nadmin">';

  for ( $i = 0, $cnt = count ( $userlist ); $i < $cnt; $i++ ) {
    echo '
              <option value="' . $userlist[$i]['cal_login'] . '"'
     . ( ! empty ( $nonusertemp_admin ) &&
      $nonusertemp_admin == $userlist[$i]['cal_login']
      ? ' selected="selected"' : '' ) . '>' . $userlist[$i]['cal_fullname']
     . '</option>';
  }

  echo '
            </select>
          </td>
        </tr>';

  if ( ! $use_http_auth ) {
    echo '
        <tr>
          <td valign="top"><label for="ispublic">'
     . translate ( 'Is public calendar' ) . ':</td>
          <td>
            <input type="radio" name="ispublic" value="Y" '
     . ( ! empty ( $nonusertemp_is_public ) && $nonusertemp_is_public == 'Y'
      ? ' checked="checked"' : '' ) . ' /> ' . translate ( 'Yes' )
     . '&nbsp;&nbsp;<input type="radio" name="ispublic" value="N" '
     . ( empty ( $nonusertemp_is_public ) || $nonusertemp_is_public != 'Y'
      ? ' checked="checked"' : '' ) . ' /> ' . translate ( 'No' ) . '<br />';

    if ( ! empty ( $nonusertemp_login ) ) {
      $nu_url = $SERVER_URL . 'nulogin.php?login=' . $nonusertemp_login;
      echo  $nu_url;
    }

    echo '
          </td>
        </tr>';
  }

  echo '
      </table><br />
      <input type="submit" name="' . $buttonAction 
            . '" value="' . $button . '" />'
   . ( empty ( $nid ) ? '' : '
      <input type="submit" name="delete" value="' . translate ( 'Delete')
     . '" onclick="return confirm( \''
     . str_replace ( 'XXX', translate ( 'entry' ),
      translate ( 'Are you sure you want to delete this XXX?' ) )
     . '\')" />' ) . '
    </form>
    ';
}

ob_end_flush ();

echo print_trailer ( false, true, true );

?>
