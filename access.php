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
 *
 *
 */
include_once 'includes/init.php';

$allow_view_other = ( ! empty ( $ALLOW_VIEW_OTHER ) && $ALLOW_VIEW_OTHER == 'Y'
  ? true : false );

if ( ! access_is_enabled () ) {
  echo print_not_auth ();
  exit;  
}
//print_r ( $_POST );
// Are we handling the access form?
// If so, do that, then redirect.
// Handle function access first.
if ( getPostValue ( 'auser' ) != '' && getPostValue ( 'submit' ) != '') { 
  $auser = getPostValue ( 'auser' );
  $perm = '';
  for ( $i = 0; $i < ACCESS_NUMBER_FUNCTIONS; $i++ ) {
    $val = getPostValue ( 'access_' . $i );
    $perm .= ( $val == 'Y' ? 'Y' : 'N' );
  }

  dbi_execute ( 'DELETE FROM webcal_access_function WHERE cal_login = ?',
    array( $auser ) );

  if ( ! dbi_execute ( 'INSERT INTO webcal_access_function ( cal_login,
      cal_permissions ) VALUES ( ?, ? )', array ( $auser, $perm ) ) )
    die_miserable_death ( translate ( 'Database error' ) . ': ' . dbi_error () );
}
$cancelStr = translate ( 'Cancel' );
$defaultStr = translate ( 'DEFAULT CONFIGURATION' );
$saveStr = translate ( 'Save' );
// Are we handling the other user form?
// If so, do that, then redirect.
if ( getPostValue ( 'otheruser' ) != '' && getPostValue ( 'submit' ) != '') { 
  $puser = getPostValue ( 'guser' );
  $pouser = getPostValue ( 'otheruser' );  
  if ( $allow_view_other ) {
    // Handle access to other users' calendars
    // If user is not admin, reverse values so they are granting
    // access to their own calendar.
    if ( ! $is_admin )
      list($puser, $pouser) = array($pouser, $puser);

    dbi_execute ( 'DELETE FROM webcal_access_user WHERE cal_login = ?
      AND cal_other_user = ?', array ( $puser, $pouser ) );
      
    if ( empty ( $pouser ) )
      break;
    $approve_total = $edit_total = $view_total = 0;
    for ( $i=1;$i<=256; ) {
      $approve_total += getPostValue ( 'a_' . $i );
      $edit_total += getPostValue ( 'e_' . $i );
      $view_total += getPostValue ( 'v_' . $i );
      $i += $i;
    }
    
    $email = getPostValue ( 'email' );
    $invite = getPostValue ( 'invite' );
    $time = getPostValue ( 'time' );

    if ( ! dbi_execute ( 'INSERT INTO webcal_access_user ( cal_login,
      cal_other_user, cal_can_view, cal_can_edit, cal_can_approve,
      cal_can_invite, cal_can_email, cal_see_time_only )
      VALUES ( ?, ?, ?, ?, ?, ?, ?, ? )',
        array( $puser, $pouser,
          ( $view_total > 0 ? $view_total : 0 ),
          ( $edit_total > 0 && $puser != '__public__' ? $edit_total : 0 ),
          ( $approve_total > 0 && $puser != '__public__' ? $approve_total : 0 ),
          ( strlen ( $invite ) ? $invite : 'N' ),
          ( strlen ( $email ) ? $email : 'N' ),
          ( strlen ( $time ) ? $time : 'N' ) ) ) ) {
      die_miserable_death ( translate ( 'Database error' ) . ': '
         . dbi_error () );
    }
  }
}
$otheruser = '';
$checked = ' checked="checked" ';
$guser = getPostValue ( 'guser' );
if ( $guser == '__default__' ) {
  $user_fullname = $defaultStr;
  $otheruser = '__default__';
} else
  $otheruser = getPostValue ( 'otheruser' );

if ( $otheruser == '__default__' ) {
  $otheruser_fullname = $defaultStr;
  $otheruser_login  = '__default__';
} else if ( $otheruser == '__public__' ) {
  $otheruser_fullname = translate ( 'Public Access' );
  $otheruser_login  = '__public__';
}
if ( ! empty ( $otheruser ) ) {
  if ( $allow_view_other ) {
    user_load_variables ( $otheruser, 'otheruser_' );
    //turn off admin override so we see the users own settings
    $ADMIN_OVERRIDE_UAC = 'N';
    // Now load all the data from webcal_access_user
    $allPermissions = access_load_user_permissions ( false);
    //load default-default values if exist
    if ( ! empty ( $allPermissions['__default__.__default__'] ) )
      $op = $allPermissions['__default__.__default__'];

    if ( $is_admin ) {
			//load user-default values if exist
      if ( ! empty ( $allPermissions[ $guser . '.__default__' ] ) )
				$op = $allPermissions[ $guser .'.__default__' ];
			//load user-otheruser values if exist
      if ( ! empty ( $allPermissions[ $guser . '.' . $otheruser ] ) )
				$op = $allPermissions[ $guser . '.' . $otheruser ];
	  } else {
			//load defualt-user values if exist
      if ( ! empty ( $allPermissions['__default__.' . $guser] ) )
				$op = $allPermissions['__default__.' . $guser ];
			//load otheruser-user values if exist
      if ( ! empty ( $allPermissions[$otheruser . '.' . $guser] ) )
				$op = $allPermissions[$otheruser . '.' . $guser];
			}
    }
  }
print_header ( '', '',
  ( ! empty ( $op['time'] ) && $op['time'] == 'Y'
    ? 'onload="enableAll ( true );"' : '' ) );

if ( ! empty ( $guser ) || ! $is_admin ) {
 if ( $is_admin ) {
  // Present a page to allow editing a user's rights
  $adminStr = translate( 'Admin' );
    $uacStr = translate ( 'User Access Control' );
    user_load_variables ( $guser, 'user_' );

  $access = access_load_user_functions ( $guser );
  $div = ceil ( ACCESS_NUMBER_FUNCTIONS / 4 );
    ob_start ();

    echo '
    <h2>' . $uacStr . ':' . $user_fullname . '</h2>
    ' . display_admin_link () . '
    <form action="access.php" method="post" name="accessform">
      <input type="hidden" name="auser" value="' . $guser . '" />
      <input type="hidden" name="guser" value="' . $guser . '" />
      <table border="0" cellspacing="10">
        <tbody>
          <tr>
            <td valign="top">';

  for ( $i = 0; $i < ACCESS_NUMBER_FUNCTIONS; $i++ ){
      // Public access and NUCs can never use some of these functions.
    $show = true;
    if ( $guser == '__public__' || $is_nonuser ) {
      switch ( $i ) {
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
          // skip these...
          $show = false;
          break;
      }
    }
      if ( $show )
        echo '
              <label for="access_' . $i
         . '"><input type="checkbox" name="access_' . $i . '" id="access_' . $i
         . '" value="Y" ' . ( substr ( $access, $i, 1 ) != 'N' ? $checked : '' )
         . '/>' . access_get_function_description ( $i ) . '</label><br />';

    if ( ($i + 1 )%$div == 0 )
        echo '
            </td>
            <td valign="top">';
  }

    echo '
            </td>
          </tr>
        </tbody>
      </table>
      <input type="button" value="' . $cancelStr
     . '" onclick="document.location.href=\'access.php\'" />
      <input type="submit" name="submit" value="' . $saveStr . '" />
    </form>';

    ob_end_flush ();
    $pagetitle = translate ( "Allow Access to Other Users' Calendar" );
 } //end is_admin test
  else {
    // Get list of users that this user can see (may depend on group settings)
    // along with all nonuser calendars
   // if ( $guser != '__default__' ) {
        $guser = $login;
        $pagetitle = translate ( 'Grant This User Access to My Calendar' );
      }

      if ( $guser == '__default__' ) {
        $userlist = array ( '__default__' );
    $otheruser = $otheruser_login = '__default__';
        $otheruser_fullname = $defaultStr;
  } else
  if ( $allow_view_other ) {
        $userlist = get_list_of_users ( $guser );
    $str = '
    <h2>' . $pagetitle . '</h2>
    <form action="access.php" method="post" name="SelectOther">
      <input type="hidden" name="guser" value="' . $guser . '" />
      <select name="otheruser" onchange="document.SelectOther.submit()">'
        //add a DEFAULT CONFIGURATION to be used as a mask  
    . '
        <option value="__default__">' . $defaultStr . '</option>';
        
        for ( $i = 0, $cnt = count ( $userlist ); $i < $cnt; $i++ ) {
      if ( $userlist[$i]['cal_login'] != $guser )
        $str .= '
        <option value="' . $userlist[$i]['cal_login'] . '"'
         . ( ! empty ( $otheruser ) && $otheruser == $userlist[$i]['cal_login']
          ? ' selected="selected"' : '' )
         . '>' . $userlist[$i]['cal_fullname'] . '</option>';
       }
    echo $str . '
      </select>
      <input type="submit" value="' . translate ( 'Go' ) . '" />
    </form>';

     if (  empty ( $otheruser ) ) {
       echo print_trailer ();
       exit;
    }
  }
}

if ( ! empty ( $otheruser ) ) {
  if ( $allow_view_other ) {
    $typeStr = translate ( 'Type' );
    echo '
    <form action="access.php" method="post" name="EditOther">
      <input type="hidden" name="guser" value="' . $guser . '" />
      <input type="hidden" name="otheruser" value="' . $otheruser . '" /><br />
    <table cellpadding="5" cellspacing="0">
    <tbody>
    <tr>
            <th class="boxtop boxbottom" width='
     . ( $guser == '__public__'
      ? '"60%" align="center">' . translate ( 'Calendar' ) . '</th>
            <th class="boxtop boxbottom" width="20%">' . $typeStr . '</th>
            <th class="boxtop boxbottom boxright" colspan="3" width="20%">'
       . translate ( 'View Event' )
      : '"25%">' . $otheruser_fullname . '</th>
            <th class="boxtop boxbottom" width="15%">' . $typeStr . '</th>
            <th width="15%" colspan="3" class="boxtop boxbottom">'
       . translate ( 'View' ) . '</th>
            <th width="15%" colspan="3" class="boxtop boxbottom">'
       . translate ( 'Edit' ) . '</th>
            <th width="15%" colspan="3" class="boxtop boxright boxbottom">'
       . translate ( 'Approve/Reject' ) ) . '</th>
          </tr>';

        $access_type = array();
        $access_type[1] = translate ( 'Events' );
        $access_type[2] = translate ( 'Tasks' );
        $access_type[4] = translate ( 'Journals' ); 
        $gridStr = '';     

        for ( $j =1; $j < 5;$j++ ) {
          $bottomedge = ''; 
      if ( $j == 3 )
        continue;
      $gridStr .= '
          <tr>
            <td class="boxleft leftpadded' . ( $j > 3 ? ' boxbottom' : '' )
       . '"><input type="checkbox" value="Y" name=';
      if ( $j == 1 )
        $gridStr .= '"invite"'
         . ( !empty ( $op['invite'] ) && $op['invite'] == 'N' ? '' : $checked )
         . ' />' . translate ( 'Can Invite' );
      elseif ( $j == 2 )
        $gridStr .= '"email"'
         . ( !empty ( $op['email'] ) && $op['email'] == 'N' ? '' : $checked )
         . ' />' . translate ( 'Can Email' );
      else {
        $gridStr .= '"time"'
         . ( ! empty ( $op['time'] ) && $op['time'] == 'Y' ? $checked : '' )
         . ' onclick="enableAll(this.checked);" />'
         . translate ( 'Can See Time Only' );
            $bottomedge = 'boxbottom';          
          }
      $gridStr .= '</td>
            <td align="center" class="boxleft ' . $bottomedge . '">'
       . $access_type[$j] . '</td>
            <td align="center" class="boxleft pub ' . $bottomedge . '">'
       . '<input type="checkbox" value="' . $j . '" name="v_' . $j . '"'
       . ( ! empty ( $op['view'] ) && ( $op['view'] & $j ) ? $checked : '' )
       . ' /></td>
            <td class="conf ' . $bottomedge . '"><input type="checkbox" value="'
       . $j * 8 . '" name="v_' . $j * 8 . '"'
       . ( ! empty ( $op['view'] ) && ( $op['view'] & ( $j * 8 ) )
        ? $checked : '' ) . ' /></td>
            <td class="priv ' . $bottomedge . '"><input type="checkbox" value="'
       . $j * 64 . '" name="v_' . $j * 64 . '"'
       . ( ! empty ( $op['view'] ) && ( $op['view'] & ( $j * 64 ) )
        ? $checked : '' ) . ' /></td>'
       . ( $guser != '__public__' ? '
            <td align="center" class="boxleft pub ' . $bottomedge . '"><input '
         . 'type="checkbox" value="' . $j . '" name="e_' . $j . '"'
         . ( ! empty ( $op['edit'] ) && ( $op['edit'] & $j ) ? $checked : '' )
         . ' /></td>
            <td class="conf ' . $bottomedge . '"><input type="checkbox" value="'
         . $j * 8 . '" name="e_' . $j * 8 . '"'
         . ( ! empty ( $op['edit'] ) && ( $op['edit'] & ( $j * 8 ) )
          ? $checked : '' ) . ' /></td>
            <td class="priv ' . $bottomedge . '"><input type="checkbox" value="'
         . $j * 64 . '" name="e_' . $j * 64 . '"'
         . ( ! empty ( $op['edit'] ) && ( $op['edit'] & ( $j * 64 ) )
          ? $checked : '' ) . ' /></td>
            <td align="center" class="boxleft pub ' . $bottomedge . '"><input '
         . 'type="checkbox" value="' . $j . '" name="a_' . $j . '"'
         . ( ! empty ( $op['approve'] ) && ( $op['approve'] & $j )
          ? $checked : '' ) . ' /></td>
            <td class="conf ' . $bottomedge . '"><input type="checkbox" value="'
         . $j * 8 . '" name="a_' . $j * 8 . '"'
         . ( ! empty ( $op['approve'] ) && ( $op['approve'] & ( $j * 8 ) )
          ? $checked : '' ) . ' /></td>
            <td class="boxright priv ' . $bottomedge
         . '"><input type="checkbox" value="' . $j * 64 . '" name="a_' . $j * 64
         . '"' . ( ! empty ( $op['approve'] ) && ( $op['approve'] & ( $j * 64 ) )
          ? $checked : '' ) . ' /></td>'
        : '' ) . '
          </tr>';
          }
    echo $gridStr . '
          <tr>
            <td colspan="2" class="alignright">'
     . ( $otheruser != '__default__' && $otheruser != '__public__' ? '
              <input type="button" value="' . translate ( 'Assistant' )
       . '" onclick="selectAll(63);" />&nbsp;&nbsp;' : '' ) . '
              <input type="button" value="' . translate ( 'Select All' )
     . '" onclick="selectAll(256);" />&nbsp;&nbsp;
              <input type="button" value="' . translate ( 'Clear All' )
     . '" onclick="selectAll(0);" />
            </td>
            <td colspan="9">
              <table border="0" align="center" cellpadding="5" cellspacing="2">
                <tr>
                  <td class="pub">' . translate ( 'Public' ) . '</td>
                  <td class="conf">' . translate ( 'Confidential' ) . '</td>
                  <td class="priv">' . translate ( 'Private' ) . '</td>
                </tr>
              </table>
            </td>
          </tr>
        </tbody>
      </table>
      <br /><br />';
        }
 
  echo '
      <input type="button" value="' . $cancelStr
   . '" onclick="document.location.href=\'access.php\'" />
      <input type="submit" name="submit" value="' . $saveStr . '" />
    </form>';

?>
<script language="javascript" type="text/javascript">
<!-- <![CDATA[
function selectAll( limit ) {
  if ( limit == 0 )
   document.EditOther.time.checked = false;

  document.EditOther.email.checked =
  document.EditOther.invite.checked = ( limit != 0 )

 for ( i = 1; i <= 256; ) {
    var
      aname = 'a_' + i,
      ename = 'e_' + i,
      vname = 'v_' + i;

    document.forms['EditOther'].elements[vname].checked = (i <= limit);

   if (document.forms['EditOther'].elements[ename])
      document.forms['EditOther'].elements[ename].checked = (i <= limit);

   if (document.forms['EditOther'].elements[aname])
      document.forms['EditOther'].elements[aname].checked = (i <= limit);

   i = parseInt(i+i);   
  } 
}
function enableAll ( on ) {
 for ( i = 1; i <= 256; ) {
    var
      aname = 'a_' + i,
      ename = 'e_' + i,
      vname = 'v_' + i;

   document.forms['EditOther'].elements[vname].disabled = on;

   if (document.forms['EditOther'].elements[ename])
     document.forms['EditOther'].elements[ename].disabled = on;

   if (document.forms['EditOther'].elements[aname])
     document.forms['EditOther'].elements[aname].disabled = on;

   i = parseInt(i+i);   
  }
} 
//]]> -->
</script>
  <?php
  echo print_trailer ();
  exit;
}
if ( $is_admin && ( empty ( $guser ) || $guser != '__default__'  ) ) {
  $userlist = get_my_users ();
  $nonuserlist = get_nonuser_cals ();
  // If we are here... we must need to print out a list of users
  ob_start ();

  echo '
    <h2>' . translate ( 'User Access Control' ) . '</h2>
    ' . display_admin_link () . '
  <form action="access.php" method="post" name="SelectUser">
      <select name="guser" onchange="document.SelectUser.submit()">'
  //add a DEFAULT CONFIGURATION to be used as a mask  
  . '
        <option value="__default__">' . $defaultStr . '</option>';
  for ( $i = 0, $cnt = count ( $userlist ); $i < $cnt; $i++ ) {
    echo '
        <option value="' . $userlist[$i]['cal_login'] . '">'
     . $userlist[$i]['cal_fullname'] . '</option>';
  }
  for ( $i = 0, $cnt = count ( $nonuserlist ); $i < $cnt; $i++ ) {
    echo '
        <option value="' . $nonuserlist[$i]['cal_login'] . '">'
     . $nonuserlist[$i]['cal_fullname'] . ' '
     . ( $nonuserlist[$i]['cal_is_public'] == 'Y' ? '*' : '' ) . '</option>';
  }

  echo '
  </select>
      <input type="submit" value="' . translate ( 'Go' ) . '" />
    </form>';
  
  ob_end_flush ();
} //end admin $guser !- default test
echo print_trailer(); 
// Get the list of users that the specified user can see.
function get_list_of_users ( $user ) {
  global $is_admin, $is_nonuser_admin;
  $u = get_my_users ( $user, 'view');
  if ( $is_admin || $is_nonuser_admin ) {
    // get public NUCs also
    $nonusers = get_my_nonusers ( $user, true);
    $u = array_merge( $nonusers, $u );
  }
  return $u;
}

?>
