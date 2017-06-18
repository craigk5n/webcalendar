<?php
/* $Id: groups.php,v 1.28 2007/08/02 12:57:51 umcesrjones Exp $ */
defined ( '_ISVALID' ) or die ( 'You cannot access this file directly!' );

$count = $lastrow = 0;
$newGroupStr = translate ( 'Add New Group' );
$targetStr = 'target="grpiframe" onclick="showFrame( \'grpiframe\' );">';

ob_start ();

echo '
    <a name="tabgroups"></a>
    <div id="tabscontent_groups">
      <a title="' . $newGroupStr . '" href="group_edit.php"' . $targetStr
 . $newGroupStr . '</a><br />';

$res = dbi_execute ( 'SELECT cal_group_id, cal_name FROM webcal_group
  ORDER BY cal_name' );
if ( $res ) {
  while ( $row = dbi_fetch_row ( $res ) ) {
    echo ( $count == 0 ? '
      <ul>' : '' ) . '
        <li><a title="' . $row[1] . '" href="group_edit.php?id=' . $row[0] . '"'
     . $targetStr . $row[1] . '</a></li>';
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
 . '" name="grpiframe" id="grpiframe" style="width: 90%; border: 0; '
 . 'height: 325px;"></iframe>
    </div>';

ob_end_flush ();

?>
