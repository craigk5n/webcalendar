<?php
include_once 'includes/init.php';
$INC = array('js/users.php','js/visible.php');
print_header($INC);
?>
<a title="<?php etranslate("Admin") ?>" class="nav" href="adminhome.php">&laquo;&nbsp;<?php etranslate("Admin") ?></a><br /><br />

<!-- TABS -->
<div id="tabs">
	<?php if ( $is_admin ) { ?>
		<span class="tabfor" id="tab_users"><a href="#tabusers" onclick="return showTab('users')"><?php etranslate("Users")?></a></span>
	<?php }	
	if (( $groups_enabled == "Y" ) && ($is_admin)) { ?>
		<span class="tabbak" id="tab_groups"><a href="#tabgroups" onclick="return showTab('groups')"><?php etranslate("Groups")?></a></span>
	<?php } ?>
</div>

<!-- TABS BODY -->
<div id="tabscontent">
	<!-- USERS -->
	<a name="tabusers"></a>
	<div id="tabscontent_users">
	<?php if ( $is_admin ) { ?>
		<?php
			if ( $admin_can_add_user )
				echo "<a title=\"" . 
					translate("Add New User") . "\" href=\"edit_user.php\" target=\"useriframe\" onclick=\"javascript:show('useriframe');\">" . 
					translate("Add New User") . "</a><br />\n";
		?>
		<ul>
			<?php
				$userlist = user_get_users ();
				for ( $i = 0; $i < count ( $userlist ); $i++ ) {
					if ( $userlist[$i]['cal_login'] != '__public__' ) {
						echo "<li><a title=\"" . 
							$userlist[$i]['cal_fullname'] . "\" href=\"edit_user.php?user=" . 
							$userlist[$i]["cal_login"] . "\" target=\"useriframe\" onclick=\"javascript:show('useriframe');\">";
						echo $userlist[$i]['cal_fullname'];
						echo "</a>";
						if (  $userlist[$i]["cal_is_admin"] == 'Y' )
							echo "&nbsp;<abbr title=\"" . translate("Administrative user") . "\">*</abbr>";
						echo "</li>\n";
					}
				}
			?>
		</ul>
	*&nbsp;<?php etranslate("denotes administrative user")?><br />

		<?php 
			echo "<iframe src=\"edit_user.php?user=" . 
				$userlist[$i]["cal_login"] . "\" name=\"useriframe\" id=\"useriframe\" style=\"width:90%;border-width:0px; height:280px;\"></iframe>";
		?>
<?php } else { ?>
<iframe src="edit_user.php" name="accountiframe" id="accountiframe" style="width:90%;border-width:0px; height:210px;\"></iframe>
<?php } ?>
</div>


<?php if (( $groups_enabled == "Y" ) && ($is_admin)) { 
	include_once 'groups.php';
} ?>
</div>

<?php print_trailer(); ?>
</body>
</html>
