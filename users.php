<?php /* $Id$ */
/**
 * NOTE:
 * There are THREE components that make up the functionality of users.php.
 * 1. users.php
 *  - contains the tabs
 *  - lists users
 *  - has an iframe for adding/editing users
 *  - include statements for groups.php and nonusers.php
 * 2. edit_user.php
 *  - the contents of the iframe (i.e. a form for adding/editing users)
 * 3. edit_user_handler.php
 *  - handles form submittal from edit_user.php
 *  - provides user with confirmation of successful operation
 *  - refreshes the parent frame (users.php)
 *
 * This structure is mirrored for groups & nonusers
 */

include_once 'includes/init.php';

if ( empty ( $login ) || $login == '__public__' ) {
  // Do not allow public access.
  do_redirect ( empty ( $STARTVIEW ) ? 'month.php' : $STARTVIEW );
  exit;
}

$doUser = $doUsers = $doGroups = $doNUCS = false;
$doUser = ( ! access_is_enabled() ||
  access_can_access_function ( ACCESS_ACCOUNT_INFO ) );
$doUsers = ( $is_admin ||
  ( access_is_enabled() &&
  access_can_access_function ( ACCESS_USER_MANAGEMENT ) ) );
$doRemotes = ( ! empty ( $REMOTES_ENABLED ) && $REMOTES_ENABLED == 'Y' &&
  ( ! access_is_enabled() || access_can_access_function ( ACCESS_IMPORT ) ) );
if ( $is_admin ) {
  $doGroups = ( ! empty ( $GROUPS_ENABLED ) && $GROUPS_ENABLED == 'Y' );
  $doNUCS = ( ! empty ( $NONUSER_ENABLED ) && $NONUSER_ENABLED == 'Y' );
}
$currenttab = getValue ( 'tab', 'users' );

ob_start();
setcookie( 'ctab', $currenttab );
setcookie( 'grps', ( $doUsers && $doGroups ) );
setcookie( 'nucs', ( $doUsers && $doNUCS ) );
setcookie( 'rems', $doRemotes );
print_header( '', '', '', false, false, true );

$taborder = array ( 'tabfor', 'tabbak','tabbak','tabbak','tabbak');
$i=0;

echo display_admin_link() . '
<!-- TABS -->
    <div id="tabs">'
 . ( $doUser || $doUsers ? '
      <span class="'.$taborder[$i++].'" id="tab_users"><a href="#tabusers">'
   . ( $is_admin ? translate( 'Users' ) : translate( 'Account' ) )
   . '</a></span>' : '' ) . ( $doUsers && $doGroups ? '
      <span class="'.$taborder[$i++].'" id="tab_groups"><a href="#tabgroups">'
   . translate( 'Groups' ) . '</a></span>' : '' ) . ( $doUsers && $doNUCS ? '
      <span class="'.$taborder[$i++].'" id="tab_nonusers"><a href="#tabnonusers">'
   . translate( 'NUCs' ) . '</a></span>' : '' ) . ( $doRemotes ? '
      <span class="'.$taborder[$i++].'" id="tab_remotes"><a href="#tabremotes">'
   . translate( 'Remote Calendars' ) . '</a></span>' : '' ) . '
    </div>
<!-- TABS BODY -->
    <div id="tabscontent">
<!-- USERS -->
      <a name="tabusers"></a>
      <div id="tabscontent_users">';
if ( $doUsers ) {
  $denotesStr = translate ( 'denotes administrative user' );
  if ( $is_admin ) {
    echo ( $admin_can_add_user ? '
          <a href="edit_user.php">' . translate( 'Add New User' )
     . '</a><br>' : '' ) . '
          <ul>';

    $userlist = user_get_users();
    foreach ( $userlist as $i ) {
      if ( $i['cal_login'] != '__public__' )
        echo '
            <li><a href="edit_user.php?user=' . $i['cal_login'] . '">'
          . $i['cal_fullname'] . '</a>'
          . ( $i['cal_is_admin'] == 'Y'
            ? '&nbsp;<abbr title="' . $denotesStr . '">*</abbr>' : '' ) . '</li>';
    }
    echo '
          </ul>';
  }
}
if ( $is_admin ) {
    echo '
          *&nbsp;' . $denotesStr . '.<br>
          <iframe id="useriframe" name="useriframe"></iframe>';
}
if ( $doUser && ! $doUsers ) {
    echo '
          <iframe src="edit_user.php" id="accountiframe" name="accountiframe">'
    . '</iframe>';
}

echo '
      </div>';

if ( $doUsers && $doGroups )
  include_once 'groups.php';

if ( $doUsers && $doNUCS )
  include_once 'nonusers.php';

if ( $doRemotes )
  include_once 'remotes.php';

echo '
    </div>' . print_trailer();
ob_end_flush();

?>
