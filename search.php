<?php // $Id: search.php,v 1.58.2.1 2013/01/24 21:15:09 cknudsen Exp $

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

$show_advanced = getValue( 'adv', '[01]' );
$show_advanced = $show_advanced == '1' ? '1' : '0';
$avdStyle = array( 'hidden', 'visible' );
if( access_is_enabled()
    && ! access_can_access_function( ACCESS_ADVANCED_SEARCH ) )
  $show_advanced = false;

load_user_categories();
$selected = ' selected="selected" ';

$advSearchStr = translate( 'Advanced Search' );
$searchStr    = translate( 'Search' );
$INC = array();

$INC[] = 'js/autocomplete.js';

if( $show_advanced ) {
  $INC[] = 'js/translate.js.php';
  $INC[] = 'js/visible.js/true';
  $INC[] = 'js/datesel.php';
}

if( $show_others )
  $INC[] = 'js/search.js/true';

print_header( $INC );

echo '    <h2>' . ( $show_advanced ? $advSearchStr : $searchStr ) . '</h2>
    <form action="search_handler.php" method="GET" id="searchformentry" '
 . 'name="searchformentry" style="margin-left: 13px;">
      <input type="hidden" name="advanced" value="' . $show_advanced . '" />
      <table><tr><td><label for="keywordsadv">' . translate ( 'Keywords' )
 . ':&nbsp;</label></td>
        <td><input type="text" name="keywords" id="keywordsadv" size="30" />&nbsp;
        <input type="submit" value="' . $searchStr . '" /></td></tr>';

echo '<tr height="30px"><td>&nbsp;</td><td class="aligntop">(' .
  translate( 'Enter % for all entries' ) . ')</td></tr>';

if( is_array( $categories ) && $show_advanced ) {
  echo '
        <tr id="catfilter" style="visibility:' . $avdStyle[$show_advanced]
   . ';">
          <td><label for="cat_filter" class="colon">' . translate( 'Categories' )
   . '</label></td>
          <td>
            <select name="cat_filter" id="cat_filter">
              <option value=""' . $selected . '>' . translate( 'All' )
   . '</option>';

  foreach( $categories as $K => $V ) {
    if( $K > 0 )
      echo '
              <option value="' . $K . '">' . htmlentities ( $V['cat_name'] ) . '</option>';
  }

  echo '
            </select>
          </td>
        </tr>';
}
if( count( $site_extras ) > 0 ) {
  echo '
        <tr id="extrafilter" style="visibility:' . $avdStyle[$show_advanced]
   . ';">
          <td><label for="extra_filter" class="colon">'
   . translate( 'Include' ) . '<br />' . translate( 'Site Extras' )
   . '</label></td>
          <td><input type="checkbox" name="extra_filter" value="Y" />
          </td></tr>';
}
if( $show_advanced ) {
  $dateYmd = date( 'Ymd' );
  echo '
        <tr id="datefilter" style="visibility:' . $avdStyle[$show_advanced]
   . ';">
          <td><label for="date_filter" class="colon">' . translate('Filter by Date')
   . '</label></td>
          <td>
            <select name="date_filter" id="date_filter" onchange="toggleDateRange()">
              <option value="0"' . $selected . '>' . translate( 'All Dates' )
   . '</option>
              <option value="1">' . translate( 'Past' ) . '</option>
              <option value="2">' . translate( 'Upcoming' ) . '</option>
              <option value="3">' . translate( 'Range' ) . '</option>
            </select>
          </td>
        </tr>
        <tr id="startDate" style="visibility:hidden">
          <td>&nbsp;&nbsp;<label class="colon">' . translate( 'Start date' )
   . '</label></td>
          <td>'
   . datesel_Print( 'from_', $dateYmd ) . '
          </td>
        </tr>
        <tr id="endDate" style="visibility:hidden">
          <td>&nbsp;&nbsp;<label class="colon">' . translate( 'End date' )
   . '</label></td>
          <td>'
   . datesel_Print( 'until_', $dateYmd ) . '
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
      <tr id="advlink" style="visibility:' . $avdStyle[!$show_advanced]
   . ';"><td colspan="2"><a title="' . $advSearchStr
   . '" href="search.php?adv=1">'
   . $advSearchStr . '</a></td></tr>
        <tr  id="adv" style="visibility:' . $avdStyle[$show_advanced]
   . ';">
          <td class="aligntop"><label for="usersadv">'
   . translate( 'Users' ) . ':&nbsp;</label></td>
          <td>
            <select name="users[]" id="usersadv" size="' . $size
   . '" multiple="multiple">';

  for( $i = 0; $i < $cnt; $i++ ) {
    echo '
              <option value="' . $users[$i]['cal_login'] . '"'
     . ( $users[$i]['cal_login'] == $login ? ' selected="selected"' : '' )
     . '>' . $users[$i]['cal_fullname'] . '</option>';
  }

  echo '
            </select>'
   . ( $GROUPS_ENABLED == 'Y'
    ? '<input type="button" onclick="selectUsers()" value="'
     . translate( 'Select' ) . '..." />' : '' ) . '
          </td>
        </tr>';
}
echo '</table></form>';
?>
<script language="JavaScript">
<!-- <![CDATA[
new Autocomplete('keywordsadv', { serviceUrl:'autocomplete_ajax.php' });

//]]> -->
</script>
<?php
print_trailer ();
?>
