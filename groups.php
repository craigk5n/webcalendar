<?php
/* $Id$ */
defined( '_ISVALID' ) or die( 'You cannot access this file directly!' );

$newGroupStr = translate( 'Add New Group' );
$targetStr = 'target="grpiframe" onclick="javascript:show(\'grpiframe\');">';
?>
  <a name="tabgroups"></a>
  <div id="tabscontent_groups">
    <?php
      echo '<a title="' . 
        $newGroupStr . '" href="group_edit.php"' . $targetStr . 
        translate( 'Add New Group' ) . "</a><br />\n";

        $count = 0;
        $lastrow = 0;
        $res = dbi_execute ( 'SELECT cal_group_id, cal_name FROM webcal_group ' .
          'ORDER BY cal_name' );
        if ( $res ) {
          while ( $row = dbi_fetch_row ( $res ) ) {
            if ( $count == 0 ) {
              echo "<ul>\n";
            }
          echo '<li><a title="' . 
            $row[1] . '" href="group_edit.php?id=' . $row[0] . '"' . $targetStr . 
            $row[1] . "</a></li>\n";
            $count++;
            $lastrow = $row[0];
          }
          if ( $count > 0 ) { echo "</ul>\n"; }
         dbi_free_result ( $res );
        }

      echo '<iframe src="group_edit.php?id=' . $lastrow . '" name="grpiframe" id="grpiframe" style="width:90%;border-width:0px; height:325px;"></iframe>';
    ?>
</div>
