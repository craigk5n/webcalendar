<?php
/*
 * $Id$
 *
 * This page is used to manage user access rights.
 *
 * It has three different modes:
 * - list users to manage (no parameters)
 * - manage a single user's rights (just "user" parameter)
 *   this will include which functions the user can access and
 *   (if $ALLOW_VIEW_OTHER is 'Y') which calendars thay can view/edit/approve
 * - update the database (form handler)
 *
 * Input Parameters:
 *  user - specifies which user to manage, a form will be presented
 *         that allows editing rights of this user
 *
 *  access_N - where N is 0 to ACCESS_NUMBER_FUNCTIONS as defined in
 *                 includes/access.php.  Each should be either 'Y' or 'N'.
 *  cal_N/v_N/e_N/d_N/a_N
 *       a series of 5 related form variables.  The first
 *             (cal_1, cal_2, etc.) holds the login of a user.
 *             The rest hold Y/N values for read/edit/delete/approve.
 *
 * TODO:
 *  Update the list of users to work properly with groups.
 *
 */
include_once 'includes/init.php';

// Are we handling the form?
// If so, do that, then redirect
if ( getPostValue ( 'user' ) != '' ) {
  $user = getPostValue ( 'user' );

  // Handle function access first
  $perm = '';
  for ( $i = 0; $i < ACCESS_NUMBER_FUNCTIONS; $i++ ) {
    $val = getPostValue ( 'access_' . $i );
    $perm .= ( $val == 'Y' ) ? 'Y' : 'N';
  }

  $sql = "DELETE FROM webcal_access_function WHERE cal_login = '$user'";
  dbi_query ( $sql );

  $sql = "INSERT INTO webcal_access_function ( cal_login, cal_permissions ) " .
    "VALUES ( '$user', '$perm' )";
  //echo "SQL: $sql<br />\n";
  if ( ! dbi_query ( $sql ) ) {
    die_miserable_death ( translate ( "Database error" ) . ": " .
      dbi_error () );
  }

  if ( empty ( $ALLOW_VIEW_OTHER ) || $ALLOW_VIEW_OTHER == 'Y' ) {
    // Handle access to other users' calendars
    dbi_query ( "DELETE FROM webcal_access_user WHERE cal_login = '$user'" );

    for ( $i = 0; true; $i++ ) {
      $other_user = getPostValue ( "cal_" . $i );
      if ( empty ( $other_user ) )
        break;
      $view = ( getPostValue ( "v_" . $i ) == 'Y' ) ? 'Y' : 'N';
      if ( $user == '__public__' ) {
        $edit = $delete = $approve = 'N';
      } else {
        $edit = ( getPostValue ( "e_" . $i ) == 'Y' ) ? 'Y' : 'N';
        $delete = ( getPostValue ( "d_" . $i ) == 'Y' ) ? 'Y' : 'N';
        $approve = ( getPostValue ( "a_" . $i ) == 'Y' ) ? 'Y' : 'N';
      }
      $sql = "INSERT INTO webcal_access_user " .
        "( cal_login, cal_other_user, cal_can_view, cal_can_edit, " .
        "cal_can_delete, cal_can_approve ) VALUES " .
        "( '$user', '$other_user', '$view', '$edit', '$delete', '$approve' )";
      //echo "SQL: $sql<br />\n";
      if ( ! dbi_query ( $sql ) ) {
        die_miserable_death ( translate ( "Database error" ) . ": " .
          dbi_error () );
      }
    }
  }

  do_redirect ( "access.php" );
}

$user = getGetValue ( 'user' );
if ( ! empty ( $user ) ) {
  // Present a page to allow editing a user's rights

  print_header ();

  user_load_variables ( $user, 'user_' );
  
  if ( $user == '__default__' ) $user_fullname = 'DEFAULT CONFIGURATION';
  echo "<h2>" . translate ( "User Access Control" ) . ": " .
    $user_fullname . "</h2>\n";

  echo "<a title=\"" . translate("Admin") .
    "\" class=\"nav\" href=\"adminhome.php\">&laquo;&nbsp;" .
    translate("Admin") . "</a><br /><br />\n";

  ?>
  <form action="access.php" method="POST" name="accessform">
  <input type="hidden" name="user" value="<?php echo $user;?>" />

  <table border="0" cellspacing="10"><tbody><tr><td valign="top">
  <?php

  $access = access_load_user_functions ( $user );

  $div = ceil ( ACCESS_NUMBER_FUNCTIONS / 2 ) - 1;

  for ( $i = 0; $i < ACCESS_NUMBER_FUNCTIONS; $i++ ){
    // Public access can never use some of these functions
    $show = true;
    if ( $user == '__public__' ) {
      switch ( $i ) {
        case ACCESS_VIEW_MANAGEMENT:
        case ACCESS_ACTIVITY_LOG:
        case ACCESS_ADMIN_HOME:
        case ACCESS_USER_MANAGEMENT:
        case ACCESS_ACCESS_MANAGEMENT:
        case ACCESS_PREFERENCES:
        case ACCESS_IMPORT:
        case ACCESS_ASSISTANTS:
        case ACCESS_ACCOUNT_INFO:
        case ACCESS_SYSTEM_SETTINGS:
        case ACCESS_CATEGORY_MANAGEMENT:
          // skip these...
          $show = false;
          break;
      }
    }
    if ( $show ) {
      $yesno = substr ( $access, $i, 1 );
      $checked = ( $yesno != 'N' ) ? "checked=\"checked\"" : '';
      echo "<label for=\"access_" . $i . "\">";
      echo "<input type=\"checkbox\" name=\"access_" . $i .
        "\" value=\"Y\" " . $checked . "/>\n";
      echo access_get_function_description ( $i );
      echo "</label><br />\n";
    }
    if ( $i == $div )
      echo "</td>\n<td valign=\"top\">\n";
  }
  ?>
  </td></tr>
  </tbody></table>
  <?php

  if ( empty ( $ALLOW_VIEW_OTHER ) || $ALLOW_VIEW_OTHER == 'Y' ) {
    // Now load all the data from webcal_access_user
    $res = dbi_query ( "SELECT cal_other_user, cal_can_view, " .
      "cal_can_edit, cal_can_delete, cal_can_approve " .
      "FROM webcal_access_user WHERE cal_login = '$user'" );
    assert ( '$res' );
    $otherperm = array ();
    while ( $row = dbi_fetch_row ( $res ) ) {
      $otherperm[$row[0]] = array (
        "cal_other_user" => $row[0],
        "cal_can_view" => $row[1],
        "cal_can_edit" => $row[2],
        "cal_can_delete" => $row[3],
        "cal_can_approve" => $row[4]
      );
    }
    dbi_free_result ( $res );

    // Get list of users that this user can see (may depend on group settings)
    // along with all nonuser calendars
    if ( $user != '__default__' )
      $userlist = array_merge ( get_list_of_users ( $user ), get_nonuser_cals () );
    ?>

    <br /><br /><br />
    <table border="0">
    <tbody>
    <tr>
<?php if ( $user == '__public__' ) { ?>
      <th width="70%"><?php etranslate("Calendar"); ?></th>
      <th width="30%"><?php etranslate("View Event"); ?></th>
<?php } else if ( $user != '__default__' ) { ?>
      <th width="40%"><?php etranslate("Calendar"); ?></th>
      <th width="15%"><?php etranslate("View Event"); ?></th>
      <th width="15%"><?php etranslate("Edit Event"); ?></th>
      <th width="15%"><?php etranslate("Delete Event"); ?></th>
      <th width="15%"><?php etranslate("Approve/Reject Event"); ?></th>
<?php } ?>
</tr>
    <?php
      for ( $i = $j = 0; $i < count ( $userlist ); $i++ ) {
        $thisuser = $userlist[$i]['cal_login'];
        if ( $thisuser == $user )
          continue;
        $v = $e = $d = $a = '';
        if ( ! empty ( $otherperm[$thisuser] ) ) {
          if ( $otherperm[$thisuser]['cal_can_view'] == 'Y' )
            $v = "checked=\"checked\"";
          if ( $otherperm[$thisuser]['cal_can_edit'] == 'Y' )
            $e = "checked=\"checked\"";
          if ( $otherperm[$thisuser]['cal_can_delete'] == 'Y' )
            $d = "checked=\"checked\"";
          if ( $otherperm[$thisuser]['cal_can_approve'] == 'Y' )
            $a = "checked=\"checked\"";
        }
        echo "<tr><td>" . $userlist[$i]['cal_fullname'] .
          "<input type=\"hidden\" name=\"cal_" . $j .
          "\" value=\"" . $userlist[$i]['cal_login'] . "\"/></td>\n";
        echo "<td align=\"center\"><input type=\"checkbox\" value=\"Y\" " .
          "name=\"v_" . $j . "\" " . $v . "/></td>\n";
        if ( $user != '__public__' ) {
          echo "<td align=\"center\"><input type=\"checkbox\" value=\"Y\" " .
            "name=\"e_" . $j . "\" " . $e . "/></td>\n";
          echo "<td align=\"center\"><input type=\"checkbox\" value=\"Y\" " .
            "name=\"d_" . $j . "\" " . $d . "/></td>\n";
          echo "<td align=\"center\"><input type=\"checkbox\" value=\"Y\" " .
            "name=\"a_" . $j . "\" " . $a . "/></td>\n";
        }
        $j++;
        echo "</tr>\n";
      }
    ?>
    </tbody>
    </table>

    <br /><br />
  <?php } ?>

  <input type="button" value="<?php etranslate("Cancel"); ?>"
    onclick="javascript:history.go(-1)" />
  <input type="submit" value="<?php etranslate("Save"); ?>" />
  </form>
  <?php
  print_trailer ();
  echo "</html></body>\n";
  exit;
}

// If we are here... we must need to print out a list of users

print_header();

echo "<h2>" . translate ( "User Access Control" ) . "</h2>\n";

echo "<a title=\"" . translate("Admin") .
  "\" class=\"nav\" href=\"adminhome.php\">&laquo;&nbsp;" .
  translate("Admin") . "</a><br /><br />\n";

$userlist = get_my_users ();

//echo "<b>Userlist</b>:<pre>"; print_r ( $userlist ); echo "</pre>";

echo "<ul>\n";

for ( $i = 0; $i < count ( $userlist ); $i++ ) {
  echo "<li><a href=\"access.php?user=" . $userlist[$i]['cal_login'] .
    "\">" . $userlist[$i]['cal_fullname'] . "</a></li>\n";
}

$userlist = get_nonuser_cals ();
for ( $i = 0; $i < count ( $userlist ); $i++ ) {
  if ( $userlist[$i]['cal_is_public'] == 'Y' ) {
    echo "<li><a href=\"access.php?user=" . $userlist[$i]['cal_login'] .
      "\">" . $userlist[$i]['cal_fullname'] . "</a></li>\n";
  }
}

//add a DEFAULT CONFIGURATION to be as a mask
echo "<li><a href=\"access.php?user=__default__\" \">" .
  translate ( "DEFAULT CONFIGURATION" ) ."</a></li>\n";
?>
</ul>

<?php print_trailer(); ?>
</body>
</html>
<?php
exit;

// Get the list of users that the specified user can see.
// Note: this function is based on get_my_users in functions.php
function get_list_of_users ( $user )
{
  global $GROUPS_ENABLED, $USER_SEES_ONLY_HIS_GROUPS;

  if ( $GROUPS_ENABLED == "Y" && $USER_SEES_ONLY_HIS_GROUPS == "Y" ) {
    // get groups that user is in
    $res = dbi_query ( "SELECT cal_group_id FROM webcal_group_user " .
      "WHERE cal_login = '$user'" );
    $groups = array ();
    if ( $res ) {
      while ( $row = dbi_fetch_row ( $res ) ) {
        $groups[] = $row[0];
      }
      dbi_fetch_row ( $res );
    }
    $u = user_get_users (); // a complete list of users
    $nonusers = get_nonuser_cals ();
    $u = array_merge( $nonusers, $u );
    $u_byname = array ();
    for ( $i = 0; $i < count ( $u ); $i++ ) {
      $name = $u[$i]['cal_login'];
      $u_byname[$name] = $u[$i];
    }
    $ret = array ();
    if ( count ( $groups ) == 0 ) {
      // Eek.  User is in no groups... Return only themselves
      $ret[] = $u_byname[$user];
      return $ret;
    }
    // get list of users in the same groups as current user
    $sql = "SELECT DISTINCT(webcal_group_user.cal_login), " .
      "cal_lastname, cal_firstname from webcal_group_user " .
      "LEFT JOIN webcal_user ON " .
      "webcal_group_user.cal_login = webcal_user.cal_login " .
      "WHERE cal_group_id ";
    if ( count ( $groups ) == 1 )
      $sql .= "= " . $groups[0];
    else {
      $sql .= "IN ( " . implode ( ", ", $groups ) . " )";
    }
    $sql .= " ORDER BY cal_lastname, cal_firstname, webcal_group_user.cal_login";
    //echo "SQL: $sql <br />\n";
    $res = dbi_query ( $sql );
    if ( $res ) {
      while ( $row = dbi_fetch_row ( $res ) ) {
        $ret[] = $u_byname[$row[0]];
      }
      dbi_free_result ( $res );
    }
    return $ret;
  } else {
    // groups not enabled... return all users
    //echo "No groups. ";
    return user_get_users ();
  }
}

?>
