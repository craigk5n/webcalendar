<?php
/* $Id: upgrade_matrix.php,v 1.18.2.3 2007/08/29 00:05:56 cknudsen Exp $ */

//Program Version for this release
$PROGRAM_VERSION = 'v1.1.4';

//array element[0] = sql insertion testy
//array element[1] = sql delete to clean up
//array element[2] = old program version **The last entry will be the $PROGRAM_VERSION
//array element[3] = install point in upgrade-mysql.php
$database_upgrade_matrix = array (
array ( "INSERT INTO webcal_user_pref VALUES ( 'zzz','zzz','zzz' )",
        "DELETE FROM webcal_user_pref WHERE cal_login = 'zzz'",
        'pre-v0.9.07', 'upgrade_v0.9.14'),
array ( "INSERT INTO webcal_entry_repeats ( cal_id ) VALUES ( -999 )",
        "DELETE FROM webcal_entry_repeats WHERE cal_id = -999",
        'v0.9.12 - v0.9.13', 'upgrade_v0.9.14'),
array ( "INSERT INTO webcal_user_layers ( cal_layerid, cal_login, cal_layeruser ) VALUES ( -999,'zzz','zzz' )",
        "DELETE FROM webcal_user_layers WHERE cal_layerid = -999",
        'v0.9.14 - v0.9.21', 'upgrade_v0.9.22'),
array ( "INSERT INTO webcal_site_extras ( cal_id, cal_name )VALUES ( -999,'zzz' )",
        "DELETE FROM webcal_site_extras WHERE cal_id = -999",
        'v0.9.22 - v0.9.26', 'upgrade_v0.9.27'),
array ( "INSERT INTO webcal_group VALUES( -999,'zzz','zzz', -999 )",
        "DELETE FROM webcal_group WHERE cal_group_id = -999",
        'v0.9.27 - v0.9.34', 'upgrade_v0.9.35'),
array ( "INSERT INTO webcal_entry_repeats_not ( cal_id ) VALUES( -999 )",
        "DELETE FROM webcal_entry_repeats_not WHERE cal_id = -999",
        'v0.9.35 - v0.9.36', 'upgrade_v0.9.37'),
array ( "INSERT INTO webcal_categories ( cat_id, cat_owner, cat_name ) VALUES( -999,'zzz','zzz' )",
        "DELETE FROM webcal_categories WHERE cat_id = -999",
        'v0.9.37', 'upgrade_v0.9.38'),
array ( "INSERT INTO webcal_asst ( cal_boss, cal_assistant ) VALUES( 'zzz','zzz' )",
        "DELETE FROM webcal_asst WHERE cal_boss = 'zzz'",
        'v0.9.38 - v0.9.39', 'upgrade_v0.9.40'),
array ( "INSERT INTO webcal_nonuser_cals ( cal_login, cal_admin ) VALUES ( 'zzz','zzz' )",
        "DELETE FROM webcal_nonuser_cals WHERE cal_login = 'zzz'",
        "v0.9.40", "upgrade_v0.9.41"),
array ( "INSERT INTO webcal_report_template ( cal_report_id, cal_template_type ) VALUES ( -999, 'Z' )",
        "DELETE FROM webcal_report_template WHERE cal_report_id = -999",
        'v0.9.41', 'upgrade_v0.9.42'),
array ( "INSERT INTO webcal_import ( cal_import_id, cal_type ) VALUES ( -999, 'zzz' )",
        "DELETE FROM webcal_import WHERE cal_import_id = -999",
        'v0.9.42', 'upgrade_v0.9.43'),
array ( "INSERT INTO webcal_view ( cal_view_id, cal_is_global ) VALUES( -999, 'Z' )",
        "DELETE FROM webcal_view WHERE cal_view_id = -999",
        "v0.9.43 - v1.0RC", "upgrade_v1.0RC3"),
array ( "INSERT INTO webcal_access_function ( cal_login, cal_permissions ) VALUES( 'zzz','zzz' )",
        "DELETE FROM webcal_access_function WHERE cal_login = 'zzz'",
        'v1.0RC3 - v1.0.5', 'upgrade_v1.1.0-CVS'),
array ( "INSERT INTO webcal_user_template ( cal_login, cal_type ) VALUES( 'zzz', 'Z' )",
        "DELETE FROM webcal_user_template WHERE cal_login = 'zzz'",
        'v1.1.0-CVS', 'upgrade_v1.1.0a-CVS'),
array ( "INSERT INTO webcal_entry_categories ( cal_id ) VALUES( -999)",
        "DELETE FROM webcal_entry_categories WHERE cal_id = -999",
        'v1.1.0a-CVS', 'upgrade_v1.1.0b-CVS'),
array ( "INSERT INTO webcal_blob ( cal_blob_id, cal_type ) VALUES( -999, 'Z' )",
        "DELETE FROM webcal_blob WHERE cal_blob_id = -999",
       'v1.1.0b-CVS', 'upgrade_v1.1.0c-CVS'),
array ( "INSERT INTO webcal_access_user ( cal_login, cal_other_user ) VALUES( 'zzz', 'zzz' )",
        "DELETE FROM webcal_access_user WHERE cal_login = 'zzz'",
        'v1.1.0c-CVS', 'upgrade_v1.1.0d-CVS'),
array ( "INSERT INTO webcal_reminders ( cal_id ) VALUES( -999 )",
        "DELETE FROM webcal_reminders WHERE cal_id = -999",
        'v1.1.0d-CVS', 'upgrade_v1.1.0e-CVS'),
array ( "INSERT INTO webcal_nonuser_cals ( cal_login, cal_admin, cal_url ) VALUES ( 'zzz', 'zzz', 'zzz' )",
        "DELETE FROM webcal_nonuser_cals WHERE cal_login = 'zzz'",
        'v1.1.0e-CVS', 'upgrade_v1.1.1'),
array ( "INSERT INTO webcal_categories ( cat_id, cat_name, cat_color ) VALUES ( '999', 'zzz', '#FFFFFF' )",
        "DELETE FROM webcal_categories WHERE cat_id = 999",
        'v1.1.1', 'upgrade_v1.1.2'),
array ( "INSERT INTO webcal_timezones ( tzid ) VALUES ( 'zzz' )",
        "DELETE FROM webcal_timezones WHERE tzid = 'zzz'",
        'v1.1.2', 'upgrade_v1.1.3'),
array ( "INSERT INTO webcal_timezones ( tzid ) VALUES ( 'zzz' )",
        "DELETE FROM webcal_timezones WHERE tzid = 'zzz'",
        'v1.1.3', 'upgrade_v1.1.4'),

//don't change this array element
array ( '','', $PROGRAM_VERSION, '' )
);

?>
