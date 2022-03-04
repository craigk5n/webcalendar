<?php
/**
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
$INC = array('js/views_edit.php');
print_header( $INC, '', $BodyX );
?>

<form action="views_edit_handler.php" method="post" name="editviewform">
<?php
print_form_key();
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
  $error = print_not_auth();
}

// get list of users for this view
$all_users = false;
if ( ! $newview ) {
    $res = dbi_execute ( 'SELECT cal_login FROM webcal_view_user WHERE cal_view_id = ?',
     [$id] );
    if ( $res ) {
      while ( $row = dbi_fetch_row ( $res ) ) {
        $viewuser[$row[0]] = 1;
        if ( $row[0] == '__all__' )
          $all_users = true;
      }
      dbi_free_result ( $res );
    } else
      $error = db_error();

}

if ( ! empty( $error ) ) {
  echo print_error( $error ) . print_trailer();
  exit;
}

if ( $newview ) {
  $v = [];
  echo '<h2>' . translate ( 'Add View' ) . "</h2>\n";
  echo '<input type="hidden" name="add" value="1" />' . "\n";
} else {
  echo '<h2>' . translate ( 'Edit View' ) . "</h2>\n";
  echo "<input type=\"hidden\" name=\"id\" value=\"$id\" />\n";
}
?>

<div class="form-inline">
  <label class="col-sm-2 col-form-label" for="viewname"><?php etranslate ( 'View Name' )?></label>
  <input type="text" class="form-control" name="viewname" id="viewname" placeholder="Enter view name" value="<?php echo htmlspecialchars ( $viewname );?>">
</div>

<div class="form-inline">
  <label class="col-sm-2 col-form-label" for="viewtype"><?php etranslate ( 'View Type' )?></label>
  <select name="viewtype" id="viewtype" class="form-control">
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
  </select>
</div>


<?php if ( $is_admin ) {
  $defIdx = ( ! empty ( $viewisglobal ) && $viewisglobal == 'Y' ? 'Y' : 'N' );
  echo '<div class="form-inline"><label class="col-sm-2 col-form-label" for="is_global">'
  . translate ( 'Global' ) . "</label>"
  . print_radio ( 'is_global', '', '', $defIdx, '' )
  . "</div>\n";
 }

$defIdx = ( ! empty ( $all_users ) && $all_users == true ? 'Y' : 'N' );
echo '<div class="form-inline"><label class="col-sm-2 col-form-label" for="viewuserall">'
  . translate ( 'Users' ) . "</label>"
  . print_radio ( 'viewuserall', ['N'=>'Selected', 'Y'=>'All'],
    'usermode_handler', $defIdx, '' )
  . "</div>\n";
?>


<div class="form-inline" id="viewuserlist">
  <label class="col-sm-2 col-form-label" for="is_global">&nbsp;</label>
  <select class="form-control" name="users[]" id="viewusers" size="10" multiple="multiple">
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
  <input class="btn" type="button" onclick="selectUsers()" value="<?php etranslate ( 'Select' );?>..." />
<?php } ?>
</div>

<br>
<div class="form-group">
<input class="btn btn-primary" type="submit" name="action" value="<?php if ( $newview ) etranslate ( 'Add' ); else etranslate ( 'Save' ); ?>" />
<a href="views.php" class="btn btn-secondary active">Cancel</a>
<?php if ( ! $newview ) { ?>
 <input class="btn btn-danger" type="submit" name="delete" value="<?php etranslate( 'Delete' )?>"
   onclick="return confirm('<?php etranslate( "Are you sure you want to delete this entry?" ); ?>' )" />
<?php } ?>
</div>

</form>

<?php echo print_trailer(); ?>

