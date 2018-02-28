<?php
/**
 * $Id: access.php,v 1.60.2.1 2012/02/28 15:43:09 cknudsen Exp $
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
 *             includes/access.php. Each should be either 'Y' or 'N'.
 */
include_once 'includes/init.php';
require_valid_referring_url ();

$allow_view_other =
  ( ! empty( $ALLOW_VIEW_OTHER ) && $ALLOW_VIEW_OTHER == 'Y' );

if( ! access_is_enabled() ) {
  echo print_not_auth();
  exit;
}

$dbErrStr = translate( 'Database error XXX.' );
$defConfigStr = translate( 'DEFAULT CONFIGURATION' );
$goStr = '
      </select>
      <input type="submit" value="' . translate( 'Go' ) . '" />
    </form>';
$saveStr = translate( 'Save' );
$undoStr = translate( 'Undo' );

$saved = '';

// Are we handling the access form?
// If so, do that, then redirect.
// Handle function access first.
if( getPostValue( 'auser' ) != '' && getPostValue( 'submit' ) == $saveStr ) {
  $auser = getPostValue( 'auser' );
  $perm = '';
  for( $i = 0; $i < ACCESS_NUMBER_FUNCTIONS; $i++ ) {
    $perm .= ( getPostValue( 'access_' . $i ) == 'Y' ? 'Y' : 'N' );
  }

  dbi_execute( 'DELETE FROM webcal_access_function
  WHERE cal_login = ?', [$auser] );

  if( ! dbi_execute( 'INSERT INTO webcal_access_function( cal_login,
      cal_permissions ) VALUES ( ?, ? )', [$auser, $perm] ) )
    die_miserable_death( str_replace( 'XXX', dbi_error(), $dbErrStr ) );

  $saved = true;
}

// Are we handling the other user form? If so, do that, then redirect.
if( getPostValue( 'otheruser' ) != '' && getPostValue( 'submit' ) == $saveStr ) {
  $puser = getPostValue( 'guser' );
  $pouser = getPostValue( 'otheruser' );

  if( $allow_view_other ) {
    // Handle access to other users' calendars.
    // If user is not admin,
    // reverse values so they are granting access to their own calendar.
    if( ! $is_admin )
      list( $puser, $pouser ) = [$pouser, $puser];

    dbi_execute( 'DELETE FROM webcal_access_user WHERE cal_login = ?
    AND cal_other_user = ?', [$puser, $pouser] );

    $approve_total = $edit_total = $view_total = 0;
    for( $i = 1; $i <= 256; ) {
      $approve_total += getPostValue( 'a_' . $i );
      $edit_total    += getPostValue( 'e_' . $i );
      $view_total    += getPostValue( 'v_' . $i );
      $i += $i;
    }

    $email  = getPostValue( 'email' );
    $invite = getPostValue( 'invite' );
    $time   = getPostValue( 'time' );

    if( ! dbi_execute( 'INSERT INTO webcal_access_user ( cal_login,
        cal_other_user, cal_can_view, cal_can_edit, cal_can_approve,
        cal_can_invite, cal_can_email, cal_see_time_only )
        VALUES ( ?, ?, ?, ?, ?, ?, ?, ? )',
        [
          $puser,
          $pouser,
          ( $view_total > 0 ? $view_total : 0 ),
          ( $edit_total > 0 && $puser != '__public__' ? $edit_total : 0 ),
          ( $approve_total > 0 && $puser != '__public__' ? $approve_total : 0 ),
          ( strlen( $invite ) ? $invite : 'N' ),
          ( strlen( $email ) ? $email : 'N' ),
          ( strlen( $time ) ? $time : 'N' )] ) )
      die_miserable_death( str_replace( 'XXX', dbi_error(), $dbErrStr ) );

    $saved = true;
  }
}
$checked = ' checked="checked"';
$guser = getPostValue( 'guser' );
$selected = ' selected="selected"';

if( $guser == '__default__' ) {
  $otheruser = $guser;
  $user_fullname = $defConfigStr;
} else
  $otheruser = getPostValue( 'otheruser' );

if( $otheruser == '__default__' ) {
  $otheruser_fullname = $defConfigStr;
  $otheruser_login = '__default__';
} elseif( $otheruser == '__public__' ) {
  $otheruser_fullname = translate( 'Public Access' );
  $otheruser_login = '__public__';
}
if( ! empty( $otheruser ) ) {
  if( $allow_view_other ) {
    user_load_variables( $otheruser, 'otheruser_' );
    // Turn off admin override so we see the users own settings.
    $ADMIN_OVERRIDE_UAC = 'N';
    // Now load all the data from webcal_access_user.
    $allPermissions = access_load_user_permissions( false );

    // Load default-default values if exist.
    if( ! empty( $allPermissions['__default__.__default__'] ) )
      $op = $allPermissions['__default__.__default__'];

    if( $is_admin ) {
      // Load user-default values if exist.
      if( ! empty( $allPermissions[ $guser . '.__default__' ] ) )
        $op = $allPermissions[ $guser . '.__default__' ];

      // Load user-otheruser values if exist.
      if( ! empty( $allPermissions[ $guser . '.' . $otheruser ] ) )
        $op = $allPermissions[ $guser . '.' . $otheruser ];
    } else {
      // Load default-user values if exist.
      if( ! empty( $allPermissions['__default__.' . $guser] ) )
        $op = $allPermissions['__default__.' . $guser ];

      // Load otheruser-user values if exist.
      if( ! empty( $allPermissions[$otheruser . '.' . $guser] ) )
        $op = $allPermissions[$otheruser . '.' . $guser];
    }
  }
}
print_header( '',
  '<script type="text/javascript" src="includes/js/access.js?'
 . filemtime( 'includes/js/access.js' ) . '"></script>
    <link type="text/css" href="includes/css/access.css?'
 . filemtime( 'includes/css/access.css' ) . '" rel="stylesheet" />',
  ( ! empty( $op['time'] ) && $op['time'] == 'Y'
    ? 'onload="enableAll( true );"' : '' ) );

echo print_success( $saved );

if( ! empty( $guser ) && $is_admin )
  user_load_variables( $guser, 'user_' );

if( $is_admin ) {
  $adminStr = translate( 'Admin' );
  $userlist = get_my_users();
  $nonuserlist = get_nonuser_cals();
  // If we are here... we must need to print out a list of users.
  echo '
    <h2>' . translate( 'User Access Control' )
   . ( empty( $user_fullname ) ? '' : ': ' . $user_fullname ) . '</h2>
    ' . display_admin_link( false ) . '
    <form action="access.php" method="post" name="SelectUser">
      <select name="guser" onchange="document.SelectUser.submit()">'
  // Add a DEFAULT CONFIGURATION to be used as a mask.
  . '
        <option value="__default__"'
   . ( $guser == '__default__' ? $selected : '' )
   . '>' . $defConfigStr . '</option>';
  for( $i = 0, $cnt = count( $userlist ); $i < $cnt; $i++ ) {
    echo '
        <option value="' . $userlist[$i]['cal_login'] . '"'
     . ( $guser == $userlist[$i]['cal_login'] ? $selected : '' )
     . '>' . $userlist[$i]['cal_fullname'] . '</option>';
  }
  for( $i = 0, $cnt = count( $nonuserlist ); $i < $cnt; $i++ ) {
    echo '
        <option value="' . $nonuserlist[$i]['cal_login'] . '"'
     . ( $guser == $nonuserlist[$i]['cal_login'] ? $selected : '' )
     . '>' . $nonuserlist[$i]['cal_fullname'] . ' '
     . ( $nonuserlist[$i]['cal_is_public'] == 'Y' ? '*' : '' ) . '</option>';
  }

  echo $goStr;
} //end admin $guser !- default test

if( ! empty( $guser ) || ! $is_admin ) {
  if( $is_admin ) {
    // Present a page to allow editing a user's rights.
    $access = access_load_user_functions( $guser );
    $div = ceil( ACCESS_NUMBER_FUNCTIONS / 4 );

    // We can reorder the display of user rights here.
    $order = [
      1, 0, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 27,
      15, 16, 17, 18, 19, 20, 21, 22, 23, 24, 25, 26];
    // Make sure that we have defined all the types of access
    // defined in access.php.
    assert( count( $order ) == ACCESS_NUMBER_FUNCTIONS );

    echo '
    <form action="access.php" method="post" id="accessform" name="accessform">
      <input type="hidden" name="auser" value="' . $guser . '" />
      <input type="hidden" name="guser" value="' . $guser . '" />
      <table>
        <tbody>
          <tr>
            <td>';

    for( $i = 0; $i < ACCESS_NUMBER_FUNCTIONS; $i++ ) {
      // Public access and NUCs can never use some of these functions.
      $show = true;

      if( $guser == '__public__'
          || substr( $guser, 0, 5 ) == $NONUSER_PREFIX ) {
        switch( $order[$i] ) {
          case ACCESS_ACCESS_MANAGEMENT:
          case ACCESS_ACCOUNT_INFO:
          case ACCESS_ACTIVITY_LOG:
          case ACCESS_ADMIN_HOME:
          case ACCESS_ASSISTANTS:
          case ACCESS_CATEGORY_MANAGEMENT:
          case ACCESS_IMPORT:
          case ACCESS_PREFERENCES:
          case ACCESS_SYSTEM_SETTINGS:
          case ACCESS_USER_MANAGEMENT:
          case ACCESS_VIEW_MANAGEMENT:
            // Skip these...
            $show = false;
        }
      }
      if( $show )
        echo print_checkbox ( ['access_' . $order[$i], 'Y',
            access_get_function_description( $order[$i] ),
            substr( $access, $order[$i], 1 )], 'dito' ) . '<br />';

      if( ( $i + 1 ) % $div == 0 )
        echo '
            </td>
            <td>';
    }

    echo '
            </td>
          </tr>
        </tbody>
      </table>
      <input type="submit" value="' . $undoStr . '" />
      <input type="submit" name="submit" value="' . $saveStr . '" />
    </form>';

    $pagetitle = translate( 'Allow Access to Other Users Calendar' );
  } else {
    // Get list of users that this user can see (may depend on group settings)
    // along with all nonuser calendars.
    // if( $guser != '__default__' ) {
    $guser = $login;
    $pagetitle = translate( 'Grant This User Access to My Calendar' );
  }

  if( $guser == '__default__' ) {
    $userlist = ['__default__'];
    $otheruser = $otheruser_login = '__default__';
    $otheruser_fullname = $defConfigStr;
  } else
  if( $allow_view_other ) {
    $userlist = get_list_of_users( $guser );
    echo '
    <h2 style="margin-bottom: 2px;">' . $pagetitle . '</h2>
    <form action="access.php" method="post" name="SelectOther">
      <input type="hidden" name="guser" value="' . $guser . '" />
      <select name="otheruser" onchange="document.SelectOther.submit()">'
    // Add a DEFAULT CONFIGURATION to be used as a mask.
    . '
        <option value="__default__"'
     . ( $otheruser == '__default__' ? $selected : '' )
     . '>' . $defConfigStr . '</option>';

    for( $i = 0, $cnt = count( $userlist ); $i < $cnt; $i++ ) {
      if( $userlist[$i]['cal_login'] != $guser )
        echo '
        <option value="' . $userlist[$i]['cal_login'] . '"'
         . ( ! empty( $otheruser ) && $otheruser == $userlist[$i]['cal_login']
          ? $selected : '' )
         . '>' . $userlist[$i]['cal_fullname'] . '</option>';
    }
    echo $goStr;
  }
}

if( ! empty( $otheruser ) ) {
  if( $allow_view_other ) {
    $typeStr = translate( 'Type' );
    echo '
    <form action="access.php" method="post" name="EditOther">
      <input type="hidden" name="guser" value="' . $guser . '" />
      <input type="hidden" name="otheruser" value="' . $otheruser . '" /><br />
      <table cellpadding="5">
        <tbody>
          <tr>
            <th class="boxtop boxbottom boxleft" width='
     . ( $guser == '__public__'
      ? '"60%" class="aligncenter">' . translate( 'Calendar' ) . '</th>
            <th class="boxtop boxbottom" width="20%">' . $typeStr . '</th>
            <th class="boxtop boxright boxbottom" colspan="3" width="20%">'
       . translate( 'View Event' )
      : '"25%">' . $otheruser_fullname . '</th>
            <th class="boxtop boxbottom" width="15%">' . $typeStr . '</th>
            <th width="15%" colspan="3" class="boxtop boxbottom">'
       . translate( 'View' ) . '</th>
            <th width="15%" colspan="3" class="boxtop boxbottom">'
       . translate( 'Edit' ) . '</th>
            <th width="15%" colspan="3" class="boxtop boxright boxbottom">'
       . translate( 'Approve/Reject' ) ) . '</th>
          </tr>';

    $access_type = [
      '',
      translate( 'Events' ),
      translate( 'Tasks' ),
      '',
      translate( 'Journals' )];

    for( $j = 1; $j < 5; $j++ ) {
      $bottomedge = '';
      if( $j == 3 )
        continue;
      echo '
          <tr>
            <td class="boxleft leftpadded' . ( $j > 3 ? ' boxbottom' : '' )
       . '"><input type="checkbox" value="Y" name=';
      if( $j == 1 )
        echo '"invite"'
         . ( ! empty( $op['invite'] ) && $op['invite'] == 'N' ? '' : $checked )
         . ' />' . translate( 'Can Invite' );
      elseif( $j == 2 )
        echo '"email"'
         . ( ! empty( $op['email'] ) && $op['email'] == 'N' ? '' : $checked )
         . ' />' . translate( 'Can Email' );
      else {
        echo '"time"'
         . ( ! empty( $op['time'] ) && $op['time'] == 'Y' ? $checked : '' )
         . ' onclick="enableAll( this.checked );" />'
         . translate( 'Can See Time Only' );
        $bottomedge = 'boxbottom';
      }
      echo '</td>
            <td class="aligncenter boxleft ' . $bottomedge . '">'
       . $access_type[$j] . '</td>
            <td class="aligncenter boxleft pub ' . $bottomedge . '">'
       . '<input type="checkbox" value="' . $j . '" name="v_' . $j . '"'
       . ( ! empty( $op['view'] ) && ( $op['view'] & $j ) ? $checked : '' )
       . ' /></td>
            <td class="conf ' . $bottomedge . '"><input type="checkbox" value="'
       . $j * 8 . '" name="v_' . $j * 8 . '"'
       . ( ! empty( $op['view'] ) && ( $op['view'] & ( $j * 8 ) )
        ? $checked : '' ) . ' /></td>
            <td class="priv ' . $bottomedge . '"><input type="checkbox" value="'
       . $j * 64 . '" name="v_' . $j * 64 . '"'
       . ( ! empty( $op['view'] ) && ( $op['view'] & ( $j * 64 ) )
        ? $checked : '' ) . ' /></td>'
       . ( $guser != '__public__' ? '
            <td class="aligncenter boxleft pub ' . $bottomedge . '"><input '
         . 'type="checkbox" value="' . $j . '" name="e_' . $j . '"'
         . ( ! empty( $op['edit'] ) && ( $op['edit'] & $j ) ? $checked : '' )
         . ' /></td>
            <td class="conf ' . $bottomedge . '"><input type="checkbox" value="'
         . $j * 8 . '" name="e_' . $j * 8 . '"'
         . ( ! empty( $op['edit'] ) && ( $op['edit'] & ( $j * 8 ) )
          ? $checked : '' ) . ' /></td>
            <td class="priv ' . $bottomedge . '"><input type="checkbox" value="'
         . $j * 64 . '" name="e_' . $j * 64 . '"'
         . ( ! empty( $op['edit'] ) && ( $op['edit'] & ( $j * 64 ) )
          ? $checked : '' ) . ' /></td>
            <td class="aligncenter boxleft pub ' . $bottomedge . '"><input '
         . 'type="checkbox" value="' . $j . '" name="a_' . $j . '"'
         . ( ! empty( $op['approve'] ) && ( $op['approve'] & $j )
          ? $checked : '' ) . ' /></td>
            <td class="conf ' . $bottomedge . '"><input type="checkbox" value="'
         . $j * 8 . '" name="a_' . $j * 8 . '"'
         . ( ! empty( $op['approve'] ) && ( $op['approve'] & ( $j * 8 ) )
          ? $checked : '' ) . ' /></td>
            <td class="boxright priv ' . $bottomedge
         . '"><input type="checkbox" value="' . $j * 64 . '" name="a_' . $j * 64
         . '"' . ( ! empty( $op['approve'] ) && ( $op['approve'] & ( $j * 64 ) )
          ? $checked : '' ) . ' /></td>'
        : '' ) . '
          </tr>';
    }
    echo '
          <tr>
            <td colspan="2" class="boxleft alignright">'
     . ( $otheruser != '__default__' && $otheruser != '__public__' ? '
              <input type="button" value="' . translate( 'Assistant' )
       . '" onclick="selectAll(63);" />&nbsp;&nbsp;' : '' ) . '
              <input type="button" value="' . translate( 'Select All' )
     . '" onclick="selectAll(256);" />&nbsp;&nbsp;
              <input type="button" value="' . translate( 'Clear All' )
     . '" onclick="selectAll(0);" />
            </td>
            <td colspan="9" class="boxright">
              <table class="aligncenter" cellpadding="5" cellspacing="2">
                <tr>
                  <td class="pub">' . translate( 'Public' ) . '</td>
                  <td class="conf">' . translate( 'Confidential' ) . '</td>
                  <td class="priv">' . translate( 'Private' ) . '</td>
                </tr>
              </table>
            </td>
          </tr>';
  }

  echo '
          <tr>
            <td colspan="11" class="boxleft boxbottom boxright">
              <input type="submit" value="' . $undoStr . '" />
              <input type="submit" name="submit" value="' . $saveStr . '" />
            </td>
          </tr>
        </tbody>
      </table>
    </form>';
}

echo print_trailer();

/**
 * Get the list of users that the specified user can see.
 */
function get_list_of_users( $user ) {
  global $is_admin, $is_nonuser_admin;

  $u = get_my_users( $user, 'view' );

  if( $is_admin || $is_nonuser_admin ) {
    // Get public NUCs also.
    $nonusers = get_my_nonusers( $user, true );
    $u = array_merge( $nonusers, $u );
  }
  return $u;
}

?>
