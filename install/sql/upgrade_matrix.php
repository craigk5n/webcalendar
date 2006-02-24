<?php
//array element[0] = sql to test for
//array element[1] = old program version **The last entry will be the 
//  $PROGRAM_VERSION
//array element[2] = install point in upgrade-mysql.php
$database_upgrade_matrix = array (
array ( "SELECT * FROM webcal_user_pref", "pre-v0.9.07", "upgrade_v0.9.13"),
array ( "SELECT * FROM webcal_user_pref", "v0.9.07 - v0.9.13", "upgrade_v0.9.13"),
array ( "SELECT * FROM webcal_entry_repeats", "v0.9.14 - v0.9.21", "upgrade_v0.9.22"),
array ( "SELECT * FROM webcal_user_layers", "v0.9.22 - v0.9.26", "upgrade_v0.9.27"),
array ( "SELECT * FROM webcal_site_extras", "v0.9.27 - v0.9.34", "upgrade_v0.9.35"),
array ( "SELECT * FROM webcal_group", "v0.9.35 - v0.9.36", "upgrade_v0.9.37"),
array ( "SELECT * FROM webcal_entry_repeats_not", "v0.9.37", "upgrade_v0.9.38"),
array ( "SELECT * FROM webcal_categories", "v0.9.38", "upgrade_v0.9.40"),
array ( "SELECT * FROM webcal_asst", "v0.9.40", "upgrade_v0.9.41"),
array ( "SELECT * FROM webcal_nonuser_cals", "v0.9.41", "upgrade_v0.9.42"),
array ( "SELECT * FROM webcal_report", "v0.9.42", "upgrade_v0.9.43"),
array ( "SELECT * FROM webcal_import", "v0.9.43 - v1.0RC2", "upgrade_v1.0RC3"),
array ( "SELECT cal_is_global FROM webcal_view", "v1.0RC3 - v1.0.0", "upgrade_v1.1.0"),
array ( "SELECT * FROM webcal_access_user", "v1.1.0", "upgrade_v1.1.0-CVS"),
array ( "SELECT * FROM webcal_tz_list", "v1.1.0-CVS", "upgrade_v1.1.0a-CVS"),
array ( "SELECT * FROM webcal_user_template ", "v1.1.0a-CVS", "upgrade_v1.1.0b-CVS"),
array ( "SELECT * FROM webcal_entry_categories ", "v1.1.0b-CVS", "upgrade_v1.1.0c-CVS"),
array ( "SELECT * FROM webcal_blob ", "v1.1.0c-CVS", "upgrade_v1.1.0d-CVS"),
array ( "SELECT cal_can_invite FROM webcal_access_user ", "v1.1.0d-CVS", "upgrade_v1.1.0e-CVS")
);

//Program Version for this release
//Update this to reflect array element[1] in the database_upgrade_matrix array
//This is probably not the proper number scheme, but we can change as needed
$PROGRAM_VERSION = "v1.1.0d-CVS";

?>
