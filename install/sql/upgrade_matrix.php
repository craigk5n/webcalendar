<?php
//Program Version for this release
$PROGRAM_VERSION = 'v1.9.1';

//array element[0] = sql insertion testy
//array element[1] = sql delete to clean up
//array element[2] = old program version **The last entry will be the $PROGRAM_VERSION
//array element[3] = install point in upgrade-mysql.php
// The following SQL commands will be executed to determine which version
// of the WebCalendar database we are looking at.  This is only required for
// really old version of WebCalendar.  Newer versions (1.1 or later) store the current
// WebCalendar version right in the database.  This is kludge to deal with installations from
// before then.  YMMV since his has not been tested in many years :-)
$database_upgrade_matrix = [
  ['INSERT INTO webcal_user_pref VALUES ( "zzz","zzz","zzz" )',
   'DELETE FROM webcal_user_pref WHERE cal_login = "zzz"',
   'pre-v0.9.07', 'upgrade_v0.9.14'],
  ['INSERT INTO webcal_entry_repeats ( cal_id ) VALUES ( -999 )',
   'DELETE FROM webcal_entry_repeats WHERE cal_id = -999',
   'v0.9.12 - v0.9.13', 'upgrade_v0.9.14'],
  ['INSERT INTO webcal_user_layers ( cal_layerid, cal_login, cal_layeruser ) VALUES ( -999,"zzz","zzz" )',
   'DELETE FROM webcal_user_layers WHERE cal_layerid = -999',
   'v0.9.14 - v0.9.21', 'upgrade_v0.9.22'],
  ['INSERT INTO webcal_site_extras ( cal_id, cal_name )VALUES ( -999,"zzz" )',
   'DELETE FROM webcal_site_extras WHERE cal_id = -999',
   'v0.9.22 - v0.9.26', 'upgrade_v0.9.27'],
  ['INSERT INTO webcal_group VALUES ( -999,"zzz","zzz", -999 )',
   'DELETE FROM webcal_group WHERE cal_group_id = -999',
   'v0.9.27 - v0.9.34', 'upgrade_v0.9.35'],
  ['INSERT INTO webcal_entry_repeats_not ( cal_id ) VALUES ( -999 )',
   'DELETE FROM webcal_entry_repeats_not WHERE cal_id = -999',
   'v0.9.35 - v0.9.36', 'upgrade_v0.9.37'],
  ['INSERT INTO webcal_categories ( cat_id, cat_owner, cat_name ) VALUES ( -999,"zzz","zzz" )',
   'DELETE FROM webcal_categories WHERE cat_id = -999',
   'v0.9.37', 'upgrade_v0.9.38'],
  ['INSERT INTO webcal_asst ( cal_boss, cal_assistant ) VALUES ( "zzz","zzz" )',
   'DELETE FROM webcal_asst WHERE cal_boss = "zzz"',
   'v0.9.38 - v0.9.39', 'upgrade_v0.9.40'],
  ['INSERT INTO webcal_nonuser_cals ( cal_login, cal_admin ) VALUES ( "zzz","zzz" )',
   'DELETE FROM webcal_nonuser_cals WHERE cal_login = "zzz"',
   'v0.9.40', 'upgrade_v0.9.41'],
  ['INSERT INTO webcal_report_template ( cal_report_id, cal_template_type ) VALUES ( -999, "Z" )',
   'DELETE FROM webcal_report_template WHERE cal_report_id = -999',
   'v0.9.41', 'upgrade_v0.9.42'],
  ['INSERT INTO webcal_import ( cal_import_id, cal_type ) VALUES ( -999, "zzz" )',
   'DELETE FROM webcal_import WHERE cal_import_id = -999',
   'v0.9.42', 'upgrade_v0.9.43'],
  ['INSERT INTO webcal_view ( cal_view_id, cal_is_global ) VALUES ( -999, "Z" )',
   'DELETE FROM webcal_view WHERE cal_view_id = -999',
   'v0.9.43 - v1.0RC', 'upgrade_v1.0RC3'],
  ['INSERT INTO webcal_access_function ( cal_login, cal_permissions ) VALUES ( "zzz","zzz" )',
   'DELETE FROM webcal_access_function WHERE cal_login = "zzz"',
   'v1.0RC3 - v1.0.5', 'upgrade_v1.1.0-CVS'],
  ['INSERT INTO webcal_user_template ( cal_login, cal_type ) VALUES ( "zzz", "Z" )',
   'DELETE FROM webcal_user_template WHERE cal_login = "zzz"',
   'v1.1.0-CVS', 'upgrade_v1.1.0a-CVS'],
  ['INSERT INTO webcal_entry_categories ( cal_id ) VALUES ( -999)',
   'DELETE FROM webcal_entry_categories WHERE cal_id = -999',
   'v1.1.0a-CVS', 'upgrade_v1.1.0b-CVS'],
  ['INSERT INTO webcal_blob ( cal_blob_id, cal_type ) VALUES ( -999, "Z" )',
   'DELETE FROM webcal_blob WHERE cal_blob_id = -999',
   'v1.1.0b-CVS', 'upgrade_v1.1.0c-CVS'],
  ['INSERT INTO webcal_access_user ( cal_login, cal_other_user ) VALUES ( "zzz", "zzz" )',
   'DELETE FROM webcal_access_user WHERE cal_login = "zzz"',
   'v1.1.0c-CVS', 'upgrade_v1.1.0d-CVS'],
  ['INSERT INTO webcal_reminders ( cal_id ) VALUES ( -999 )',
   'DELETE FROM webcal_reminders WHERE cal_id = -999',
   'v1.1.0d-CVS', 'upgrade_v1.1.0e-CVS'],
  ['INSERT INTO webcal_nonuser_cals ( cal_login, cal_admin, cal_url ) VALUES ( "zzz", "zzz", "zzz" )',
   'DELETE FROM webcal_nonuser_cals WHERE cal_login = "zzz"',
   'v1.1.0e-CVS', 'upgrade_v1.1.1'],
  ['INSERT INTO webcal_categories ( cat_id, cat_name, cat_color ) VALUES ( "999", "zzz", "#FFFFFF" )',
   'DELETE FROM webcal_categories WHERE cat_id = 999',
   'v1.1.1', 'upgrade_v1.1.2'],
  ['INSERT INTO webcal_timezones ( tzid ) VALUES ( "zzz" )',
   'DELETE FROM webcal_timezones WHERE tzid = "zzz"',
   'v1.1.2', 'upgrade_v1.1.3'],
  // This one is different because it is an index that was added.
  // Upgrade from 1.1.3 -> 1.3.0
  ['DROP INDEX webcal_entry_categories ON webcal_entry_categories',
   'CREATE INDEX webcal_entry_categories ON webcal_entry_categories(cat_id)',
   'v1.1.3', 'upgrade_v1.3.0'],
  // Upgrade from 1.3.0 -> 1.9.0
  ['INSERT INTO webcal_import (cal_import_id, cal_md5, cal_date, cal_type) VALUES (999999, "XXX", 1, "X")',
   'DELETE FROM webcal_import WHERE cal_import_id = 999999',
   'v1.3.0', 'upgrade_v1.9.0'],
//don't change this array element
  ['','', $PROGRAM_VERSION, '']
];

?>
