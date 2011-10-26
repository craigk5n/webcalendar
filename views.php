<?php // $Id$
include_once 'includes/init.php';

if ( ! $is_admin )
  $user = $login;

if ( ! $date )
  $date = $thisdate;

set_today( $date );
view_init( $id );

// get users in this view
$viewusers = view_get_user_list( $id );
$viewusercnt = count( $viewusers );
// This could happen if user_sees_only_his_groups = Y and
// this user is not a member of any group assigned to this view.
$error = ( $viewusercnt == 0 ? $noVuUsers : '' );

ob_start();
print_header();

echo display_admin_link() . '
<!-- TABS -->
    <div id="tabs">
      <span class="tabfor" id="tab_views"><a href="#tabviews">'
 . translate( 'Views' ) . '</a></span>
    </div>

<!-- TABS BODY -->
    <div id="tabscontent">
<!-- VIEWS -->
      <a name="tabviews"></a>
      <div id="tabscontent_views">
        <a href="views_edit.php">' . translate( 'Add New View' ) . '</a>
        <ul>';

$global_found = false;
for ( $i = 0, $cnt = count ( $views ); $i < $cnt; $i++ ) {
  if ( $views[$i]['cal_is_global'] != 'Y' || $is_admin ) {
    echo '
          <li><a href="views_edit.php?id=' . $views[$i]['cal_view_id'] . '">'
     . htmlspecialchars( $views[$i]['cal_name'] ) . '</a>';
    if ( $views[$i]['cal_is_global'] == 'Y' ) {
      echo '&nbsp;<abbr title="' . $globalStr . '">*</abbr>';
      $global_found = true;
    }
    echo '</li>';
  }
}

echo '
        </ul>' . ( $global_found ? '<br>
        *&nbsp;' . $globalStr : '' ) . '<br>
        <iframe name="viewiframe" id="viewiframe"></iframe>
      </div>
    </div>';

?>
