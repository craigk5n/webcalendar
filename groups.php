	<a name="tabgroups"></a>
	<div id="tabscontent_groups">
		<?php
			echo "<a title=\"" . 
				translate("Add New Group") . "\" href=\"group_edit.php\" target=\"grpiframe\" onclick=\"javascript:show('grpiframe');\">" . 
				translate("Add New Group") . "</a><br />\n";
		?>
		<ul>
			<?php
				$res = dbi_query ( "SELECT cal_group_id, cal_name FROM webcal_group ORDER BY cal_name" );
				if ( $res ) {
					while ( $row = dbi_fetch_row ( $res ) ) {
					echo "<li><a title=\"" . 
						$row[1] . "\" href=\"group_edit.php?id=" . $row[0] . "\" target=\"grpiframe\" onclick=\"javascript:show('grpiframe');\">" . 
						$row[1] . "</a></li>\n";
					}
					dbi_free_result ( $res );
				}
			?>
		</ul>
		<?php 
			echo "<iframe name=\"grpiframe\" id=\"grpiframe\" style=\"width:90%;border-width:0px; height:325px;\"></iframe>";
		?>
</div>
