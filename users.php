<?php
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
$currenttab = getValue ( 'tab', '^(users|groups|nonusers|remotes||)$', true );

$BodyX = 'onload="showTab(\''. $currenttab . '\');"';
print_header ( array ( 'js/visible.php', 'js/users.php/true' ), '',
  $BodyX, '', '', true );

// Craig. Much more efficient to style the default then just change 'tabfor'.
// No need for 'tabbak'.
$taborder = ['tabfor', 'tabbak','tabbak','tabbak','tabbak'];
$i=0;

echo display_admin_link() . '
<!-- TABS -->
    <div id="tabs">'
 .( $doUser || $doUsers? '
      <span class="'.$taborder[$i++].'" id="tab_users"><a href="#tabusers" onclick="return '
 . 'showTab( \'users\' )">'
 . ( $is_admin ? translate ( 'Users' ) : translate ( 'Account' ) )
 . '</a></span>' : '' ) . ( $doUsers && $doGroups ? '
      <span class="'.$taborder[$i++].'" id="tab_groups"><a href="#tabgroups" '
   . 'onclick="return showTab( \'groups\' )">' . translate ( 'Groups' )
   . '</a></span>' : '' ) . ( $doUsers && $doNUCS ? '
      <span class="'.$taborder[$i++].'" id="tab_nonusers"><a href="#tabnonusers" '
   . 'onclick="return showTab( \'nonusers\' )">'
   . translate ( 'NonUser Calendars' ) . '</a></span>' : '' )
 . ( $doRemotes ? '
      <span class="'.$taborder[$i++].'" id="tab_remotes"><a href="#tabremotes" '
   . 'onclick="return showTab( \'remotes\' )">'
   . translate ( 'Remote Calendars' ) . '</a></span>' : '' ) . '
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
          <a href="edit_user.php" target="useriframe" onclick="showFrame'
       . '( \'useriframe\' );">' . translate ( 'Add New User' )
       . '</a><br />' : '' ) . '
          <ul>';

    $userlist = user_get_users();
    for ( $i = 0, $cnt = count ( $userlist ); $i < $cnt; $i++ ) {
      if ( $userlist[$i]['cal_login'] != '__public__' )
        echo '
            <li><a href="edit_user.php?user=' . $userlist[$i]['cal_login']
         . '" target="useriframe" onclick="showFrame(\'useriframe\');">'
         . $userlist[$i]['cal_fullname'] . '</a>'
         . ( $userlist[$i]['cal_is_admin'] == 'Y' ? '&nbsp;<abbr title="'
           . $denotesStr . '">*</abbr>' : '' )
         . '</li>';
    }
    echo '
          </ul>';
  }
}
if ( $is_admin ) {
    echo '
          *&nbsp;' . $denotesStr . '.<br />
          <iframe name="useriframe" id="useriframe"></iframe>';
}
if ($doUser && ! $doUsers ) {
    echo '
          <iframe src="edit_user.php" name="accountiframe" id="accountiframe">'
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
    </div>
    ' . print_trailer();

?>
