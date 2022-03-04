<?php
include_once 'includes/init.php';

if ( ! $is_admin )
  $user = $login;

print_header();

echo '<h2>' . translate("Manage Views") . '</h2>';

echo display_admin_link();

echo "<ul>\n";
$global_found = false;
for ( $i = 0, $cnt = count ( $views ); $i < $cnt; $i++ ) {
  if ( $views[$i]['cal_is_global'] != 'Y' || $is_admin ) {
    echo '
          <li class="nav"><a title="' . htmlspecialchars ( $views[$i]['cal_name'] )
     . '" href="views_edit.php?id=' . $views[$i]['cal_view_id'] . '">'
     . htmlspecialchars ( $views[$i]['cal_name'] ) . '</a>';
    if ( $views[$i]['cal_is_global'] == 'Y' ) {
      echo '&nbsp;<abbr title="' . translate ( 'Global' ) . '">*</abbr>';
      $global_found = true;
    }
    echo '</li>';
  }
}
echo '</ul><div><br><a title="' . translate ( 'Add New View' )
  . '" class="btn btn-primary active" role="button" aria-pressed="true" href="views_edit.php">' . translate ( 'Add New View' ) . '</a></div>';

echo ( $global_found ? '<br />
        *&nbsp;' . translate ( 'Global' ) : '' ) . '<br />
      </div>
    </div>
    ' . print_trailer();

?>
