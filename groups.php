<?php // $Id$
defined ( '_ISVALID' ) or die ( 'You cannot access this file directly!' );

$count = $lastrow = 0;

echo '
      <a name="tabgroups"></a>
      <div id="tabscontent_groups">
        <a href="group_edit.php">' . translate( 'Add New Group' ) . '</a><br>';

$res = dbi_execute ( 'SELECT cal_group_id, cal_name FROM webcal_group
  ORDER BY cal_name' );
if ( $res ) {
  while ( $row = dbi_fetch_row ( $res ) ) {
    echo ( $count == 0 ? '
        <ul>' : '' ) . '
          <li><a href="group_edit.php?id=' . $row[0] . '">'
     . $row[1] . '</a></li>';
    $count++;
    $lastrow = $row[0];
  }
  if ( $count > 0 )
    echo '
        </ul>';

  dbi_free_result ( $res );
}

echo '
        <iframe src="group_edit.php?id=' . $lastrow
 . '" id="grpiframe" name="grpiframe"></iframe>
      </div>';

?>
