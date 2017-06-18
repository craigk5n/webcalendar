<?php
/* $Id: views_edit.php,v 1.58.2.2 2007/11/12 15:40:32 umcesrjones Exp $
 *
 * Page Description:
 * This page displays the views that the user currently owns and
 * allows new ones to be created
 *
 * Input Parameters:
 * id  - specify view id in webcal_view table
 * if blank, a new view is created
 *
 * Security:
 * Must be owner of the viewto edit
 */
include_once 'includes/init.php';

$error = '';

if ( ! $is_admin )
  $user = $login;

$BodyX = 'onload="usermode_handler();"';
$INC = array ( 'js/visible.php', 'js/views_edit.php/true');

$disableCustom = true;

print_header ( $INC, '', $BodyX, $disableCustom );
?>

<form action="views_edit_handler.php" method="post" name="editviewform">
<?php
$newview = true;
$viewname = $viewtype = '';
$viewisglobal = 'N';
$checked = ' checked="checked"';
$selected = ' selected="selected"';

$unnameViewStr = translate ( 'Unnamed View' );

if ( empty ( $id ) ) {
  $viewname = $unnameViewStr;
} else {
  // search for view by id
  for ( $i = 0, $cnt = count ( $views ); $i < $cnt; $i++ ) {
    if ( $views[$i]['cal_view_id'] == $id ) {
      $newview = false;
      $viewname = $views[$i]['cal_name'];
      if ( empty ( $viewname ) )
        $viewname = $unnameViewStr;
      $viewtype = $views[$i]['cal_view_type'];
      $viewisglobal = $views[$i]['cal_is_global'];
    }
  }
}

// If view_name not found, then the specified view id does not
// belong to current user.
if ( empty ( $viewname ) ) {
  $error = print_not_auth (34);
}

// get list of users for this view
$all_users = false;
if ( ! $newview ) {
    $res = dbi_execute ( 'SELECT cal_login FROM webcal_view_user WHERE cal_view_id = ?',
     array ( $id ) );
    if ( $res ) {
      while ( $row = dbi_fetch_row ( $res ) ) {
        $viewuser[$row[0]] = 1;
        if ( $row[0] == '__all__' )
          $all_users = true;
      }
      dbi_free_result ( $res );
    } else
      $error = db_error ();

}

if ( ! empty ( $error ) ) {
  echo print_error ( $error ) . print_trailer ();
  exit;
}

if ( $newview ) {
  $v = array ();
  echo '<h2>' . translate ( 'Add View' ) . "</h2>\n";
  echo '<input type="hidden" name="add" value="1" />' . "\n";
} else {
  echo '<h2>' . translate ( 'Edit View' ) . "</h2>\n";
  echo "<input type=\"hidden\" name=\"id\" value=\"$id\" />\n";
}
?>

<table>
<tr><td>
 <label for="viewname"><?php etranslate ( 'View Name' )?>:</label></td><td colspan="3">
 <input name="viewname" id="viewname" size="20" value="<?php echo htmlspecialchars ( $viewname );?>" />
</td></tr>
<tr><td>
 <label for="viewtype"><?php etranslate ( 'View Type' )?>:</label></td><td colspan="3">
 <select name="viewtype" id="viewtype">
  <option value="D" <?php if ( $viewtype == 'D' )
  echo $selected;?>><?php etranslate ( 'Day' ); ?></option>
  <option value="E" <?php if ( $viewtype == 'E' )
  echo $selected;?>><?php etranslate ( 'Day by Time' ); ?></option>
  <option value="W" <?php if ( $viewtype == 'W' )
  echo $selected;?>><?php etranslate ( 'Week (Users horizontal)' ); ?></option>
  <option value="R" <?php if ( $viewtype == 'R' )
  echo $selected;?>><?php etranslate ( 'Week by Time' ); ?></option>
  <option value="V" <?php if ( $viewtype == 'V' )
  echo $selected;?>><?php etranslate ( 'Week (Users vertical)' ); ?></option>
  <option value="S" <?php if ( $viewtype == 'S' )
  echo $selected;?>><?php etranslate ( 'Week (Timebar)' ); ?></option>
  <option value="T" <?php if ( $viewtype == 'T' )
  echo $selected;?>><?php etranslate ( 'Month (Timebar)' ); ?></option>
  <option value="M" <?php if ( $viewtype == 'M' )
  echo $selected;?>><?php etranslate ( 'Month (side by side)' ); ?></option>
  <option value="L" <?php if ( $viewtype == 'L' )
  echo $selected;?>><?php etranslate ( 'Month (on same calendar)' ); ?></option>
      </select>&nbsp;
  </td></tr>

<?php if ( $is_admin ) {
  $defIdx = ( ! empty ( $viewisglobal ) && $viewisglobal == 'Y' ? 'Y' : 'N' );
  echo '<tr><td><label>'
  . translate ( 'Global' ) . ":</label></td>\n<td>"
  . print_radio ( 'is_global', '', '', $defIdx, '</td><td>' )
  . "</td></tr>\n";
 }

$defIdx = ( ! empty ( $all_users ) && $all_users == true ? 'Y' : 'N' );
echo '<tr><td><label>'
  . translate ( 'Users' ) . ":</label></td>\n<td>"
  . print_radio ( 'viewuserall', array ( 'N'=>'Selected', 'Y'=>'All'),
    'usermode_handler', $defIdx, '</td><td>' )
  . "</td></tr>\n";
?>

<tr><td colspan="4">
<div id="viewuserlist">
&nbsp;&nbsp;
 <select name="users[]" id="viewusers" size="10" multiple="multiple">
<?php
  // get list of all users
  $users = get_my_users ( '', 'view' );
  if ($NONUSER_ENABLED == 'Y' ) {
    $nonusers = get_my_nonusers ( $user, true, 'view' );
    $users = ( $NONUSER_AT_TOP == 'Y'
     ? array_merge ( $nonusers, $users ) : array_merge ( $users, $nonusers ) );
  }
  for ( $i = 0, $cnt = count ( $users ); $i < $cnt; $i++ ) {
    $u = $users[$i]['cal_login'];
    echo "<option value=\"$u\"";
    if ( ! empty ( $viewuser[$u] ) ) {
      echo $selected;
    }
    echo '>' . $users[$i]['cal_fullname'] . "</option>\n";
  }
?>
</select>
<?php if ( $GROUPS_ENABLED == 'Y' ) { ?>
 <input type="button" onclick="selectUsers()" value="<?php etranslate ( 'Select' );?>..." />
<?php } ?>
</div>
</td></tr>
<tr><td colspan="4" class="aligncenter">
<br />
<input type="submit" name="action" value="<?php if ( $newview ) etranslate ( 'Add' ); else etranslate ( 'Save' ); ?>" />
<?php if ( ! $newview ) { ?>
 <input type="submit" name="delete" value="<?php etranslate ( 'Delete' )?>" onclick="return confirm( '<?php str_replace ( 'XXX',
  translate ( 'entry' ),
  translate ( 'Are you sure you want to delete this XXX?' ) ); ?>' )" />
<?php } ?>
</td></tr>
</table>

</form>

<?php echo print_trailer ( false, true, true ); ?>

