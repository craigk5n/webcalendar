<?php
define ( 'Y', 'Y' );
//this is already defined
//define ( 'N', 'N' );
define ( 'INT', 'INTEGER' );
define ( 'CHAR', 'CHAR' );
define ( 'VARCHAR', 'VARCHAR' );
define ( 'TEXT', 'TEXT' );
define ( 'LONGBLOB', 'LONGBLOB' );

$sql_array = array();

$dbHash = array ( 
  'mysql'=>array (
    'CREATE'=>'CREATE TABLE IF NOT EXISTS ',
	  'PRIMARY'=>'PRIMARY KEY',
	  'INDEX'=>'CREATE INDEX ',
	  'INTEGER'=>'INTEGER',
	  'CHAR'=>'CHAR',
	  'TEXT'=>'TEXT',
	  'LONGBLOB'=>'LONGBLOB',
	  'VARCHAR'=>'VARCHAR'
	),
  'oracle'=>array (
    'CREATE'=>'CREATE TABLE ',
	  'PRIMARY'=>'PRIMARY KEY',
	  'INDEX'=>'CREATE INDEX ',
	  'INTEGER'=>'INTEGER',
	  'CHAR'=>'CHAR',
	  'TEXT'=>'TEXT',
	  'LONGBLOB'=>'BLOB',
	  'VARCHAR'=>'VARCHAR2'
	),
  'mssql'=>array (
    'CREATE'=>'CREATE TABLE ',
	  'PRIMARY'=>'PRIMARY KEY',
	  'INDEX'=>'CREATE INDEX ',
	  'INTEGER'=>'INTEGER',
	  'CHAR'=>'CHAR',
	  'TEXT'=>'TEXT',
	  'LONGBLOB'=>'IMAGE',
	  'VARCHAR'=>'VARCHAR'
	),
  'psql'=>array (
    'CREATE'=>'CREATE TABLE ',
	  'PRIMARY'=>'PRIMARY KEY',
	  'INDEX'=>'CREATE INDEX ',
	  'INTEGER'=>'INTEGER',
	  'CHAR'=>'CHAR',
	  'TEXT'=>'TEXT',
	  'LONGBLOB'=>'BTYEA',
	  'VARCHAR'=>'VARCHAR'
	),
  'sqlite'=>array (
    'CREATE'=>'CREATE TABLE ',
	  'PRIMARY'=>'PRIMARY KEY',
	  'INDEX'=>'CREATE INDEX ',
	  'INTEGER'=>'INTEGER',
	  'CHAR'=>'CHAR',
	  'TEXT'=>'TEXT',
	  'LONGBLOB'=>'BLOB',
	  'VARCHAR'=>'VARCHAR'
	)
);




//field array (column name, data_type, size, NULL , default  )

$install_sql = array (
  array ( 'name'=>'access_function',
	 'action'=>'CREATE',
   'fields'=>array (
     array ( 'cal_login_id', INT, 11, N ),
     array ( 'cal_permissions', VARCHAR, 64, N )
    ),
   'primary'=>array ( 'cal_login_id' ),
   'index'=>array ( 'cal_permissions' )
  ),

  array ( 'name'=>'access_user',
	 'action'=>'CREATE',
   'fields'=>array (
     array ('cal_login_id',  INT, 11, N ),
     array ('cal_other_user_id', INT, 11, N ),
     array ('cal_can_view', INT, 11, N, 0 ),
     array ('cal_can_edit', INT, 11, N, 0 ),
     array ('cal_can_approve',  INT, 11, N, 0 ),
     array ('cal_can_invite', CHAR, 1, N, 'Y' ),
     array ('cal_can_email', CHAR, 1, N, 'Y' ),
     array ('cal_see_time_only', CHAR, 1, N, 'N' ),
     array ('cal_assistant', CHAR, 1, N, 'Y' )
    ),
   'primary'=>array ( 'cal_login_id' ),
   'index'=>array ( 'cal_other_user_id' )
  ),

  array ( 'name'=>'blob',
	 'action'=>'CREATE',
   'fields'=>array (
     array ( 'cal_blob_id', INT, 11, N ),
     array ( 'cal_id',  INT, 11, Y, NULL ),
     array ( 'cal_login_id', INT, 11, N ),
     array ( 'cal_name', VARCHAR, 30, Y, NULL ),
     array ( 'cal_description', VARCHAR, 128, Y, NULL ),
     array ( 'cal_size', INT, 11, Y, NULL ),
     array ( 'cal_mime_type', VARCHAR, 50, Y, NULL ),
     array ( 'cal_type', CHAR, 1, N ),
     array ( 'cal_mod_date', INT, 11, N ),
     array ( 'cal_blob', LONGBLOB )
    ),
   'primary'=>array ( 'cal_blob_id' ),
   'index'=>array ( 'cal_id', 'cal_login_id' )
  ),

  array ( 'name'=>'categories',
	 'action'=>'CREATE',
   'fields'=>array (
     array ( 'cat_id', INT, 11, N ),
     array ( 'cat_owner', INT, 11, Y, NULL ),
     array ( 'cat_name',  VARCHAR, 80, N ),
     array ( 'cat_color', VARCHAR, 8, Y, NULL ),
    ),
   'primary'=>array ( 'cat_id'),
   'index'=>array ( 'cat_owner', 'cat_name' )
   ),


  array ( 'name'=>'config',
	 'action'=>'CREATE',
   'fields'=>array (
     array ( 'cal_setting', VARCHAR, 50, N ),
     array ( 'cal_value', VARCHAR, 100, Y, NULL )
   ),
  'primary'=>array ( 'cal_setting' ),
  'index'=>array ( 'cal_value' )
  ),

  array ( 'name'=>'entry',
	 'action'=>'CREATE',
    'fields'=>array (
     array ( 'cal_id', INT, 11, N ),       
     array ( 'cal_parent_id', INT, 11, Y, NULL ),    
     array ( 'cal_rmt_addr', INT, 11, Y, NULL ),       
     array ( 'cal_create_by', INT, 11, N ),       
     array ( 'cal_date', INT, 11, N ),       
     array ( 'cal_mod_date', INT, 11, Y, NULL ),      
     array ( 'cal_duration', INT, 11, N ),       
     array ( 'cal_due_date', INT, 11, Y, NULL ),       
     array ( 'cal_priority', INT, 11, N, '5' ),       
     array ( 'cal_type', CHAR, 1, N, 'E' ),       
     array ( 'cal_access', CHAR, 1, N, 'P' ),     
     array ( 'cal_name', VARCHAR, 80, N ),       
     array ( 'cal_location', VARCHAR, 100, Y ),       
     array ( 'cal_url', VARCHAR, 100, Y ),       
     array ( 'cal_completed', INT, 11, Y ),       
     array ( 'cal_description', TEXT )
    ),
  'primary'=>array ( 'cal_id' ),
  'index'=>array ( 'cal_create_by', 'cal_type', 'cal_name' )
  ),

  array ( 'name'=>'entry_categories',
	 'action'=>'CREATE',
   'fields'=>array (       
     array ( 'cal_id', INT, 11, N ),
     array ( 'cat_id', INT, 11, N, 0 ),
     array ( 'cat_order', INT, 11, N, 0 ),
     array ( 'cat_owner', INT, 11, Y, NULL )
    ),
  'primary'=>array ( 'cal_id' ),
  'index'=>array ( 'cat_id', 'cat_order' )
  ),

  array ( 'name'=>'entry_ext_user',
	 'action'=>'CREATE',
   'fields'=>array (       
     array ( 'cal_id', INT, 11, N ),       
     array ( 'cal_fullname', VARCHAR, 50, N ),       
     array ( 'cal_email', VARCHAR, 75, Y, NULL )
    ),
  'primary'=>array ( 'cal_id' ),
  'index'=>array ( 'cal_fullname', 'cal_email' )
  ),

  array ( 'name'=>'entry_log',
	 'action'=>'CREATE',
   'fields'=>array (       
     array ( 'cal_log_id', INT, 11, N ),       
     array ( 'cal_entry_id', INT, 11, N ),       
     array ( 'cal_login_id', INT, 11, N ),       
     array ( 'cal_owner_id', INT, 11, Y ),       
     array ( 'cal_type', CHAR, 2, N ),       
     array ( 'cal_date', INT, 11, N ),       
     array ( 'cal_text', TEXT )
   ),
  'primary'=>array ( 'cal_log_id' ),
  'index'=>array ( 'cal_entry_id', 'cal_login_id', 'cal_owner_id' )
  ),

  array ( 'name'=>'entry_repeats',
	 'action'=>'CREATE',
   'fields'=>array (       
     array ( 'cal_id', INT, 11, N, 0 ),       
     array ( 'cal_type', VARCHAR, 20, Y ),       
     array ( 'cal_end', INT, 11, Y ),       
     array ( 'cal_frequency', INT, 11, N, 1 ),      
     array ( 'cal_bymonth', VARCHAR, 50, Y ),       
     array ( 'cal_bymonthday', VARCHAR, 100, Y ),       
     array ( 'cal_byday', VARCHAR, 100, Y ),       
     array ( 'cal_bysetpos', VARCHAR, 50, Y ),       
     array ( 'cal_byweekno', VARCHAR, 50, Y ),       
     array ( 'cal_byyearday', VARCHAR, 50, Y ),       
     array ( 'cal_wkst', CHAR, 2, N, 'MO' ),       
     array ( 'cal_count', INT, 11, Y )
    ), 
  'primary'=>array ('cal_id'),
  'index'=>array ( 'cal_type', 'cal_end' )	
  ),

  array ( 'name'=>'entry_repeats_not',
	 'action'=>'CREATE',
   'fields'=>array (       
     array ( 'cal_id', INT, 11, N, 0 ),       
     array ( 'cal_date', INT, 11, N ),       
     array ( 'cal_exdate', INT, 1, N, '1' ) 
    ),
  'primary'=>array ( 'cal_id' ),
  'index'=>array ( 'cal_date', 'cal_exdate' )
  ),

  array ( 'name'=>'entry_user',
	 'action'=>'CREATE',
   'fields'=>array (       
     array ( 'cal_id', INT, 11, N, 0 ),       
     array ( 'cal_login_id', INT, 11, N ),       
     array ( 'cal_status', CHAR, 1, N, 'A' ),       
     array ( 'cal_percent', INT, 11, N, 0 )
    ), 
  'primary'=>array ( 'cal_id' ),
  'index'=>array ( 'cal_login_id', 'cal_status' )
  ),

  array ( 'name'=>'group',
	 'action'=>'CREATE',
   'fields'=>array (       
     array ( 'cal_group_id', INT, 11, N ),       
     array ( 'cal_owner', INT, 11, N ),       
     array ( 'cal_name', VARCHAR, 50, N ),       
     array ( 'cal_last_update', INT, 11, N )
    ), 
  'primary'=>array ( 'cal_group_id' ),
  'index'=>array ( 'cal_owner', 'cal_name' )
  ),

  array ( 'name'=>'group_user',
	 'action'=>'CREATE',
   'fields'=>array (       
     array ( 'cal_group_id', INT, 11, N ),       
     array ( 'cal_login_id', INT, 11, N )
    ),
  'primary'=>array ( 'cal_group_id' ),
  'index'=>array ( 'cal_login_id' )
  ),

  array ( 'name'=>'import',
	 'action'=>'CREATE',
   'fields'=>array (       
     array ( 'cal_import_id', INT, 11, N ),       
     array ( 'cal_name', VARCHAR, 50, Y ),       
     array ( 'cal_date', INT, 11, N ),       
     array ( 'cal_type', VARCHAR, 10, N ),       
     array ( 'cal_login_id', INT, 11, Y ) 
    ),
  'primary'=>array ( 'cal_import_id' ),
  'index'=>array ( 'cal_name', 'cal_login_id' )
  ),

  array ( 'name'=>'import_data',
	 'action'=>'CREATE',
   'fields'=>array ( 
     array ( 'cal_id', INT, 11, N ),       
     array ( 'cal_import_id', INT, 11, N ),             
     array ( 'cal_login_id', INT, 11, N ),       
     array ( 'cal_import_type', VARCHAR, 15, N ),       
     array ( 'cal_external_id', VARCHAR, 200, Y )
    ),
  'primary'=>array ( 'cal_id' ),
  'index'=>array ( 'cal_import_id', 'cal_login_id' )
  ),

  array ( 'name'=>'reminders',
	 'action'=>'CREATE',
   'fields'=>array (       
     array ( 'cal_id', INT, 11, N, 0 ),       
     array ( 'cal_date', INT, 11, N, 0 ),       
     array ( 'cal_offset', INT, 11, N, 0 ),       
     array ( 'cal_related', CHAR, 1, N, 'S' ),       
     array ( 'cal_before', CHAR, 1, N, 'Y' ),       
     array ( 'cal_last_sent', INT, 11, N, 0 ),       
     array ( 'cal_repeats', INT, 11, N, 0 ),       
     array ( 'cal_duration', INT, 11, N, 0 ),       
     array ( 'cal_times_sent', INT, 11, N, 0 ),       
     array ( 'cal_action', VARCHAR, 12, N, 'EMAIL' ) 
    ),
  'primary'=>array ('cal_id'),
  'index'=>array ( 'cal_times_sent' )	
  ),

  array ( 'name'=>'report',
	 'action'=>'CREATE',
   'fields'=>array (   
     array ( 'cal_report_id', INT, 11, N ),     
     array ( 'cal_login_id', INT, 11, N ),             
     array ( 'cal_is_global', CHAR, 1, N, 'N' ),       
     array ( 'cal_report_type', VARCHAR, 20, N ),       
     array ( 'cal_include_header', CHAR, 1, N,  'Y' ),       
     array ( 'cal_report_name', VARCHAR, 50, N ),       
     array ( 'cal_time_range', INT, 11, N ),       
     array ( 'cal_user_id', INT, 11, N ),      
     array ( 'cal_allow_nav', CHAR, 1, N, 'Y' ),       
     array ( 'cal_cat_id', INT, 11, Y ),       
     array ( 'cal_include_empty', CHAR, 1, N, 'N' ),       
     array ( 'cal_show_in_trailer', CHAR, 1, N, 'N' ),       
     array ( 'cal_update_date', INT, 11, N )
    ),
  'primary'=>array ( 'cal_report_id' ),
  'index'=>array ( 'cal_login_id', 'cal_user' )
  ),

  array ( 'name'=>'report_template',
	 'action'=>'CREATE',
   'fields'=>array (       
     array ( 'cal_report_id', INT, 11, N ),       
     array ( 'cal_template_type', CHAR, 1, N ),       
     array ( 'cal_template_text', TEXT ) 
    ),
  'primary'=>array ( 'cal_report_id', 'cal_template_type' )
  ),

  array ( 'name'=>'site_extras',
	 'action'=>'CREATE',
   'fields'=>array (       
     array ( 'cal_id', INT, 11, N, 0 ),       
     array ( 'cal_name', VARCHAR, 25, N ),       
     array ( 'cal_type', INT, 11, N ),       
     array ( 'cal_date', INT, 11, N, 0 ),       
     array ( 'cal_data', TEXT ) 
    ),
  'primary'=>array ('cal_id'),
  'index'=>array ( 'cal_name', 'cal_type' ) 
  ),

  array ( 'name'=>'timezones',
	 'action'=>'CREATE',
   'fields'=>array (
     array ( 'tzid', VARCHAR, 100, N,  '' ),
     array ( 'dtstart', INT, 11, Y ),
     array ( 'dtend', INT, 11, Y ),
     array ( 'vtimezone', TEXT ) 
    ),
  'primary'=>array ('tzid'),
  'index'=>array ( 'dtstart', 'dtend' )
  ),

  array ( 'name'=>'user',
	 'action'=>'CREATE',
   'fields'=>array (       
     array ( 'cal_login_id', INT, 11, N ),       
     array ( 'cal_login', VARCHAR, 25, N ),       
     array ( 'cal_passwd', VARCHAR, 32, Y ),       
     array ( 'cal_lastname', VARCHAR, 25, Y ),       
     array ( 'cal_firstname', VARCHAR, 25, Y ),       
     array ( 'cal_is_admin', CHAR, 1, N, 'N' ),       
     array ( 'cal_email', VARCHAR, 75, Y ),       
     array ( 'cal_enabled', CHAR, 1, N, 'Y' ),       
     array ( 'cal_telephone', VARCHAR, 50, Y ),       
     array ( 'cal_address', VARCHAR, 75, Y ),       
     array ( 'cal_title', VARCHAR, 75, Y ),       
     array ( 'cal_birthday', INT, 11, Y ),       
     array ( 'cal_last_login', INT, 11, Y ),       
     array ( 'cal_is_nuc', CHAR, 1, N, 'N' ),       
     array ( 'cal_admin', INT, 11, Y ),       
     array ( 'cal_is_public', CHAR, 1, N, 'N' ),       
     array ( 'cal_url', VARCHAR, 255, Y ),       
     array ( 'cal_selected', CHAR, 1, N, 'N' ),       
     array ( 'cal_view_part', CHAR, 1, N, 'N' )
    ), 
  'primary'=>array ( 'cal_login_id' ),
  'index'=>array ( 'cal_login', 'cal_lastname', 'cal_firstname', 'cal_email', 'cal_is_admin' )
  ),

  array ( 'name'=>'user_layers',
	 'action'=>'CREATE',
   'fields'=>array (       
     array ( 'cal_layerid', INT, 11, N ),       
     array ( 'cal_login_id', INT, 11, N ),       
     array ( 'cal_layeruser', VARCHAR, 25, N ),       
     array ( 'cal_color', VARCHAR, 25, Y ),       
     array ( 'cal_dups', CHAR, 1, N, 'N' ) 
    ),
  'primary'=>array ( 'cal_layerid' ),
  'index'=>array ( 'cal_login_id', 'cal_layeruser' )
  ),

  array ( 'name'=>'user_pref',
	 'action'=>'CREATE',
   'fields'=>array (       
     array ( 'cal_login_id', INT, 11, N ),       
     array ( 'cal_setting', VARCHAR, 25, N ),       
     array ( 'cal_value', VARCHAR, 100, Y ) 
    ),
  'primary'=>array ( 'cal_login_id' ),
  'index'=>array ( 'cal_setting', 'cal_value' )
  ),

  array ( 'name'=>'user_template',
	 'action'=>'CREATE',
   'fields'=>array (       
     array ( 'cal_login_id', INT, 11, N ),       
     array ( 'cal_type', CHAR, 1, N ),       
     array ( 'cal_template_text', TEXT ) 
    ),
  'primary'=>array ( 'cal_login_id' ),
  'index'=>array ( 'cal_type' )
  ),

  array ( 'name'=>'view',
	 'action'=>'CREATE',
   'fields'=>array (       
     array ( 'cal_view_id', INT, 11, N ),       
     array ( 'cal_owner', INT, 11, N ),       
     array ( 'cal_name', VARCHAR, 50, N ),       
     array ( 'cal_view_type', CHAR, 1, Y ),       
     array ( 'cal_is_global', CHAR, 1, N, 'N' )
    ), 
  'primary'=>array ( 'cal_view_id' ),
  'index'=>array ( 'cal_owner', 'cal_name', 'cal_view_type', 'cal_is_global' )
  ),

  array ( 'name'=>'view_user',
	 'action'=>'CREATE',
	 'fields'=>array (       
     array ( 'cal_view_id', INT, 11, N ),       
     array ( 'cal_login_id', INT, 11, N )
    ),
  'primary'=>array ( 'cal_view_id' ),
  'index'=>array ( 'cal_login_id' )
  )
);

$sql_displayStr = '';
foreach ( $install_sql as $table_sql ) {
$columnSql = $primarySql = $indexSql = '';
$fieldsCnt = $primaryCnt = $indexCnt = 1;

$tmp = "\n" . $dbHash[$dbType][$table_sql['action']] 
  . $dbPrefix . $table_sql['name'] . " (\n";
	foreach ( $table_sql['fields'] as $fields ) {
		$pricnt = count ( $table_sql['fields'] );
	  $columnSql .=  $fields[0] . " " . $dbHash[$dbType][$fields[1]];
		if ( isset ( $fields[2] ) )
		  $columnSql .= '(' . $fields[2] . ')';
		if ( isset ( $fields[3] )	)	
		  $columnSql .= ( $fields[3] == 'N' ? ' NOT ' : ' ' ) . 'NULL';
		if ( isset ( $fields[4] ) )			 
			$columnSql .= ' DEFAULT \'' . $fields[4] . '\'';
		$columnSql .= ( $fieldsCnt++ < $pricnt || 
		  isset ( $table_sql['primary'] )? ",\n" : '' );	
  }
	if ( isset ( $table_sql['primary'] ) ) {
	  $primarySql = $dbHash[$dbType]['PRIMARY'] . "(";
		$pricnt = count ( $table_sql['primary'] );
	  foreach ( $table_sql['primary'] as $primary ) {	
	    $primarySql .= $primary;
	    $primarySql .= ( $primaryCnt++ < $pricnt ? ", " : ")" );						  
		}
	}
	if ( isset ( $table_sql['index'] ) ) {
	  $indexSql = $dbHash[$dbType]['INDEX'] 
		  . "IDX_" . $table_sql['name'] . ' ON ' 
			. $dbPrefix . $table_sql['name'] . "(";
		$idxcnt = count ( $table_sql['index'] );
	  foreach ( $table_sql['index'] as $index ) {	
	    $indexSql .= $index;
	    $indexSql .= ( $indexCnt++ < $idxcnt ? ", " : ")" );						  
		}
	}
	$tmp .= $columnSql . $primarySql . "\n);\n" ;
	$sql_array[] = str_replace ( "\n", " ", $tmp );
	if ( ! empty ( $indexSql ) ) {
	  $tmp .= $indexSql . ";\n" ;
	  $sql_array[] = str_replace ( "\n", " ", $indexSql );		
	} 		
  $sql_displayStr .= $tmp;
}


?>