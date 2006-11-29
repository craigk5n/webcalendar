<?php
/* $Id$ */

include_once 'includes/init.php';
// Is this user allowed to search the calendars of other users?
$show_others = false; // show "Advanced Search"
if ( $single_user == 'Y' )
  $show_others = false;

if ( $is_admin )
  $show_others = true;
else
if ( access_is_enabled () )
  $show_others = access_can_access_function ( ACCESS_ADVANCED_SEARCH );
else
if ( $login != '__public__' && ! $is_nonuser && ! empty ( $ALLOW_VIEW_OTHER ) &&
  $ALLOW_VIEW_OTHER == 'Y' )
  $show_others = true;
else
if ( $login == '__public__' && !
  empty ( $PUBLIC_ACCESS_OTHERS ) && $PUBLIC_ACCESS_OTHERS == 'Y' )
  $show_others = true;

$advSearchStr = translate ( 'Advanced Search' );
$searchStr = translate ( 'Search' );

print_header ( ( $show_others ? array ( 'js/search.php/true' ) : '' ) );

ob_start ();

echo '    <h2>' . $searchStr . '</h2>
    <form action="search_handler.php" method="post" id="searchformentry" '
 . 'name="searchformentry" style="margin-left: 13px;">
      <p><label for="keywordsadv">' . translate ( 'Keywords' )
 . ':&nbsp;</label>
        <input type="text" name="keywords" id="keywordsadv" size="30" />&nbsp;
        <input type="submit" value="' . $searchStr . '" /></p>';

if ( $show_others ) {
  $users = get_my_users ( '', 'view' );
  // Get non-user calendars (if enabled)
  if ( ! empty ( $NONUSER_ENABLED ) && $NONUSER_ENABLED == 'Y' ) {
    $nonusers = get_my_nonusers ( $login, true, 'view' );
    $users = ( ! empty ( $NONUSER_AT_TOP ) && $NONUSER_AT_TOP == 'Y'
      ? array_merge ( $nonusers, $users )
      : array_merge ( $users, $nonusers ) );
  }
  $cnt = count ( $users );
  if ( $cnt > 50 )
    $size = 15;
  elseif ( $cnt > 10 )
    $size = 10;
  else
    $size = $cnt;

  echo '
      <p id="advlink"><a title="' . $advSearchStr
   . '" href="javascript:show ( \'adv\' ); hide ( \'advlink\' );">'
   . $advSearchStr . '</a></p>
      <table id="adv" style="display:none;">
        <tr>
          <td class="aligntop alignright bold" width="60px"><label for="usersadv">'
   . $translations['Users'] . ':&nbsp;</label></td>
          <td>
            <select name="users[]" id="usersadv" size="' . $size
   . '" multiple="multiple">';

  for ( $i = 0; $i < $cnt; $i++ ) {
    echo '
              <option value="' . $users[$i]['cal_login'] . '"'
     . ( $users[$i]['cal_login'] == $login ? ' selected="selected"' : '' )
     . '>' . $users[$i]['cal_fullname'] . '</option>';
  }

  echo '
            </select>'
   . ( $GROUPS_ENABLED == 'Y'
    ? '<input type="button" onclick="selectUsers ()" value="'
     . $translations['Select'] . '..." />' : '' ) . '
          </td>
        </tr>
      </table>';
}
ob_end_flush ();

echo '
    </form>
    ' . print_trailer ();

?>
