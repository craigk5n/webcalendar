<?php // $Id$

include_once 'includes/init.php';
// Is this user allowed to search the calendars of other users?
$show_others = false; // show "Advanced Search"

if( $single_user == 'Y' )
  $show_others = false;

if( $is_admin )
  $show_others = true;
elseif( access_is_enabled() )
  $show_others = access_can_access_function( ACCESS_ADVANCED_SEARCH );
elseif( $login != '__public__' && ! $is_nonuser && ! empty( $ALLOW_VIEW_OTHER )
    && $ALLOW_VIEW_OTHER == 'Y' )
  $show_others = true;
elseif( $login == '__public__' && ! empty( $PUBLIC_ACCESS_OTHERS )
    && $PUBLIC_ACCESS_OTHERS == 'Y' )
  $show_others = true;

$show_advanced = ( getValue( 'adv', '[01]' ) == '1' ? '1' : '0' );
$avdStyle = array( 'hidden', 'visible' );
if( access_is_enabled()
    && ! access_can_access_function( ACCESS_ADVANCED_SEARCH ) )
  $show_advanced = '0';

load_user_categories();

$advSearchStr = translate( 'Advanced Search' );
$searchStr    = translate( 'Search' );

ob_start();
setcookie( 'show_adv', $show_advanced );
setcookie( 'vis', $avdStyle[$show_advanced] );
print_header();

echo '
    <h2>' . ( $show_advanced ? $advSearchStr : $searchStr ) . '</h2>
    <form action="search_handler.php" method="post" id="searchformentry" '
 . 'name="searchformentry">
      <input type="hidden" name="advanced" value="' . $show_advanced . '">
      <table summary="">
        <tr>
          <td><label for="keywordsadv">' . translate( 'Keywords' )
 . '&nbsp;</label></td>
          <td><input type="text" id="keywordsadv" name="keywords" size="30">&nbsp;
            <input type="submit" value="' . $searchStr . '"></td>
        </tr>
        <tr height="30px">
          <td>&nbsp;</td>
          <td valign="top">' . translate( 'Enter % for all entries' ) . '</td>
        </tr>';

if( is_array( $categories ) && $show_advanced ) {
  echo '
        <tr id="catfilter">
          <td><label for="cat_filter">' . translate( 'Categories_' )
   . '</label></td>
          <td>
            <select id="cat_filter" name="cat_filter">' . $option . '" selected>'
   . $allStr . '</option>';

  foreach( $categories as $K => $V ) {
    if( $K > 0 )
      echo $option . $K . '">' . $V['cat_name'] . '</option>';
  }

  echo '
            </select>
          </td>
        </tr>';
}
if( count( $site_extras ) > 0 ) {
  echo '
        <tr id="extrafilter">
          <td><label for="extra_filter">' . translate( 'Include Site Extras' )
   . '</label></td>
          <td><input type="checkbox" name="extra_filter" value="Y"></td>
        </tr>';
}
if( $show_advanced ) {
  $dateYmd = date( 'Ymd' );
  echo '
        <tr id="datefilter">
          <td><label for="date_filter">' . translate( 'Filter by Date' )
   . '</label></td>
          <td>
            <select id="date_filter" name="date_filter">'
   . $option . '0" selected>' . translate( 'All Dates' ) . '</option>'
   . $option . '1">' . translate( 'Past' ) . '</option>'
   . $option . '2">' . translate( 'Upcoming' ) . '</option>'
   . $option . '3">' . translate( 'Range' ) . '</option>
            </select>
          </td>
        </tr>
        <tr id="startDate">
          <td>&nbsp;&nbsp;<label>' . translate( 'Start date_' ) . '</label></td>
          <td>' . datesel_Print( 'from_', $dateYmd ) . '
          </td>
        </tr>
        <tr id="endDate">
          <td>&nbsp;&nbsp;<label>' . translate( 'End date_' ) . '</label></td>
          <td>' . datesel_Print( 'until_', $dateYmd ) . '
          </td>
        </tr>';
}
if( $show_others ) {
  $users = get_my_users( '', 'view' );
  // Get non-user calendars (if enabled)
  if( ! empty( $NONUSER_ENABLED ) && $NONUSER_ENABLED == 'Y' ) {
    $nonusers = get_my_nonusers( $login, true, 'view' );
    $users = ( ! empty( $NONUSER_AT_TOP ) && $NONUSER_AT_TOP == 'Y'
      ? array_merge( $nonusers, $users )
      : array_merge( $users, $nonusers ) );
  }
  $cnt = count( $users );
  if( $cnt > 50 )
    $size = 15;
  elseif( $cnt > 10 )
    $size = 10;
  else
    $size = $cnt;

  echo '
        <tr id="advlink">
          <td colspan="2"><a href="search.php?adv=1">' . $advSearchStr
   . '</a></td>
        </tr>
        <tr id="adv">
          <td class="aligntop"><label for="usersadv">'
   . translate( 'Users_' ) . '&nbsp;</label></td>
          <td>
            <select name="users[]" id="usersadv" size="' . $size
   . '" multiple>';

  for( $i = 0; $i < $cnt; $i++ ) {
    echo $option . $users[$i]['cal_login']
     . ( $users[$i]['cal_login'] == $login ? '" selected>' : '">' )
     . $users[$i]['cal_fullname'] . '</option>';
  }

  echo '
            </select>'
   . ( $GROUPS_ENABLED == 'Y'
    ? '<input type="button" id="searchUsers" value="' . $selectStr . '...">'
    : '' ) . '
          </td>
        </tr>';
}

echo '</table>
    </form>' . print_trailer();
ob_end_flush();

?>
