<?php
//Program Version for this release; use bump_version.sh to update this.
$PROGRAM_VERSION = 'v1.9.12';

//array element[0] = sql insertion testy
//array element[1] = sql delete to clean up
//array element[2] = old program version; The last entry needs to be the $PROGRAM_VERSION
//array element[3] = install point in upgrade-mysql.php
// The following SQL commands will be executed to determine which version
// of the WebCalendar database we are looking at.  This is only required for
// really old version of WebCalendar.  Newer versions (1.1 or later) store the current
// WebCalendar version right in the database.  This is kludge to deal with installations from
// before then.
$database_upgrade_matrix = [
  ['INSERT INTO webcal_view ( cal_name, cal_view_id, cal_is_global, cal_owner ) VALUES ( "delete-me", -999, "Z", "nobody" )',
   'DELETE FROM webcal_view WHERE cal_view_id = -999',
   'v0.9.43', 'upgrade_v1.0RC3'],
  ['INSERT INTO webcal_access_function ( cal_login, cal_permissions ) VALUES ( "zzz","zzz" )',
   'DELETE FROM webcal_access_function WHERE cal_login = "zzz"',
   'v1.0RC3', 'upgrade_v1.1.0-CVS'],
  ['INSERT INTO webcal_user_template ( cal_login, cal_type ) VALUES ( "zzz", "Z" )',
   'DELETE FROM webcal_user_template WHERE cal_login = "zzz"',
   'v1.1.0-CVS', 'upgrade_v1.1.0a-CVS'],
  ['INSERT INTO webcal_entry_categories ( cal_id, cat_owner ) VALUES ( -999, "nobody" )',
   'DELETE FROM webcal_entry_categories WHERE cal_id = -999',
   'v1.1.0a-CVS', 'upgrade_v1.1.0b-CVS'],
  ['INSERT INTO webcal_blob ( cal_blob_id, cal_type, cal_mod_date, cal_mod_time ) VALUES ( -999, "Z", 20200101, 0 )',
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
  // Upgrade from 1.1.2 -> 1.3.0
  ['INSERT INTO webcal_timezones ( tzid ) VALUES ( "zzz" )',
   'DELETE FROM webcal_timezones WHERE tzid = "zzz"',
   'v1.1.2', 'upgrade_v1.3.0'],
  // Upgrade from 1.3.0 -> 1.9.0
  ['INSERT INTO webcal_import (cal_import_id, cal_md5, cal_date, cal_type) VALUES (999999, "XXX", 1, "X")',
   'DELETE FROM webcal_import WHERE cal_import_id = 999999',
   'v1.3.0', 'upgrade_v1.9.0'],
  // Upgrade from 1.9.0 -> 1.9.6
  // Check to see if we can set cat_owner to NULL in webcal_entry_categories
  // Should get MySQL error: Column 'cat_owner' cannot be null
  ['INSERT INTO webcal_entry_categories (cal_id, cat_id, cat_order, cat_owner) VALUES (999999, 1, -1, "")',
   'DELETE FROM webcal_entry_categories WHERE cal_id = 999999 AND cat_order = -1',
   'v1.9.0', 'upgrade_v1.9.6'],
  // Upgrade from 1.9.10 -> 1.9.11
  ['INSERT INTO webcal_categories (cat_id, cat_name, cat_owner, cat_icon_mime) VALUES (999999, "nocat", "nobody", "image/gif")',
   'DELETE FROM webcal_categories WHERE cat_id = 999999 AND cat_owner = "nobody"',
   'v1.9.10', 'upgrade_v1.9.11'],
  // Upgrade from 1.9.11 -> 1.9.12
  ["insert into webcal_entry (cal_id, cal_name, cal_create_by, cal_date, cal_duration, cal_url) values (-999, 'Test', 'nobody', 20201231, 0, '01234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890123456789')",
   'delete from webcal_entry where cal_id = -999',
   'v1.9.11', 'upgrade_v1.9.12'],
  // don't change this array element
  ['','', $PROGRAM_VERSION, '']
];

?>
