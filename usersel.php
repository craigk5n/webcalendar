<?php // $Id$
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
$exp = explode( ',', $users );
$groups = $selected = $sql_params = array();
for ( $i = 0, $cnt = count ( $exp ); $i < $cnt; $i++ ) {
  $selected[$exp[$i]] = 1;
}

$groups = get_groups( $user );

ob_start();
print_header( '', '', '', true, false, true );

echo '
    <script>';

include 'includes/js/usersel.php';

echo '
    </script>
    <center>
      <form action="#" name="userselform">
        <table style="border: 0; width: 100%;" summary="">
          <tr>
            <td class="aligntop">
              <b>' . translate( 'Users' ) . ':</b><br>
              <select name="users" size="15" multiple>
              </select><br>
              <input type="button" value="' . translate ( 'All' )
 . '" onclick="selectAll( true )">
              <input type="button" value="' . $noneStr
 . '" onclick="selectAll( false )">
              <input type="reset" value="' . translate( 'Reset' ) . '">
            </td>
            <td valign="top">
              <b>' . $groupsStr . '</b><br>
              <select name="groups" size="15">';

for ( $i = 0, $cnt = count ( $groups ); $i < $cnt; $i++ ) {
  echo '
                <option value="' . $groups[$i]['cal_group_id'] . '">'
   . $groups[$i]['cal_name'] . '</option>';
}

echo '
              </select><br>
              <input type="button" value="' . $addStr
 . '" onclick="toggleGroup( true );">
              <input type="button" value="' . translate ( 'Remove' )
 . '" onclick="toggleGroup( false );">
            </td>
          </tr>
          <tr>
            <td style="text-align:center;" colspan="2"><br><br>
              <input type="button" value="' . $okStr
 . '" onclick="OkButton()">
              <input type="button" value="' . translate ( 'Cancel' )
 . '" onclick="window.close()">
            </td>
          </tr>
        </table>
      </form
    </center>';

ob_end_flush();

echo print_trailer ( false, true, true );

?>
