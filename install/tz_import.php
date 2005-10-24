<?php


if ( ! empty ( $PHP_SELF ) && preg_match ( "/\/includes\//", $PHP_SELF ) ) {
    die ( "You can't access this file directly!" );
}

$months = array (
  "Jan" => 1,
  "Feb" => 2,
  "Mar" => 3,
  "Apr" => 4,
  "May" => 5,
  "Jun" => 6,
  "Jul" => 7,
  "Aug" => 8,
  "Sep" => 9,
  "Oct" => 10,
  "Nov" => 11,
  "Dec" => 12
);

$days_of_week =  array (
  "Sun" => 0,
  "Mon" => 1,
  "Tue" => 2,
  "Wed" => 3,
  "Thu" => 4,
  "Fri" => 5,
  "Sat" => 6
);

$min_date = mktime ( 0, 0, 0, 1, 2, 1970 );
$max_date = mktime ( 0, 0, 0, 1, 1, 2038 );

//This function will apply a user supplied offset to all 
// webcal_entry, webcal_entry_logs
// cal_date and cal_time values
// We could get this from PHP date( "Z")
// Also, if the user specifies, we will use the server's
// Timezone and determine if DST is in effect for each date
function convert_server_to_GMT ( $offset=0, $server_tz='' ) {
  // Current max values in tz database are -12 to 15
 // but we add 1 to account for possible DST
 // Note this is not scientific
  if ( ( $offset < -13 ) || ( $offset > 16 ) ){
   return;
 }
 //Defauly value 
 $error = "<b>Conversion Successful</b>";
 // Do webcal_entry update
  $res = dbi_query ( "SELECT cal_date, cal_time, cal_id, cal_duration FROM webcal_entry" );
  if ( $res ) {
    while ( $row = dbi_fetch_row ( $res ) ) {
      $cal_date = $row[0];
      $cal_time = sprintf ( "%06d", $row[1] );
   $cal_id = $row[2];
   $cal_duration = $row[3];
   //  Skip Untimed or All Day events
   if ( ( $cal_time == -1 ) || ( $cal_time == 0 && $cal_duration == 1440 ) ){
     continue;
   } else {
     $sy = substr ( $cal_date, 0, 4 );
     $sm = substr ( $cal_date, 4, 2 );
     $sd = substr ( $cal_date, 6, 2 );
     $sh = substr ( $cal_time, 0, 2 );
     $si = substr ( $cal_time, 2, 2 );
     $ss = substr ( $cal_time, 4, 2 );   
        $new_datetime = mktime ( $sh, $si, $ss, $sm, $sd, $sy );
        $new_datetime -= ( $offset * 3600 );
     $new_cal_date = date ( "Ymd", $new_datetime );
     $new_cal_time = date ( "His", $new_datetime );
     // Now update row with new data
     if ( ! dbi_query ( "UPDATE webcal_entry SET cal_date = '" . $new_cal_date  . "', " .
       " cal_time = '" . $new_cal_time . "' ".
          "WHERE cal_id = $cal_id" ) ){
          $error = "Error updating table 'webcal_entry' " . dbi_error ();
     return $error;
     }
    }
    }
    dbi_free_result ( $res );
  }
 
  // Do webcal_entry_logs update
  $res = dbi_query ( "SELECT cal_date, cal_time, cal_log_id FROM webcal_entry_log" );
  if ( $res ) {
    while ( $row = dbi_fetch_row ( $res ) ) {
      $cal_date = $row[0];
      $cal_time = sprintf ( "%06d", $row[1] );
   $cal_log_id = $row[2];
   $sy = substr ( $cal_date, 0, 4 );
   $sm = substr ( $cal_date, 4, 2 );
   $sd = substr ( $cal_date, 6, 2 );
   $sh = substr ( $cal_time, 0, 2 );
   $si = substr ( $cal_time, 2, 2 );
   $ss = substr ( $cal_time, 4, 2 );   
      $new_datetime = mktime ( $sh, $si, $ss, $sm, $sd, $sy );
      $new_datetime += ( $offset * 3600 );
   $new_cal_date = date ( "Ymd", $new_datetime );
   $new_cal_time = date ( "His", $new_datetime );
   // Now update row with new data
   if ( ! dbi_query ( "UPDATE webcal_entry_log SET cal_date = '" . $new_cal_date  . "', " .
     " cal_time = '" . $new_cal_time . "' ".
        "WHERE cal_log_id = $cal_log_id" ) ){
        $error = "Error updating table 'webcal_entry_log' " . dbi_error ();
    return $error;
   }
    }
    dbi_free_result ( $res );
  }
    // Update Conversion Flag in webcal_config
   //Delete any existing entry
   $sql = "DELETE FROM webcal_config WHERE cal_setting = 'webcal_tz_conversion'";
   if ( ! dbi_query ( $sql ) ) {
    $error = "Database error: " . dbi_error ();
    return $error;
   }
  $sql = "INSERT INTO webcal_config ( cal_setting, cal_value ) " .
   "VALUES ( 'webcal_tz_conversion', 'Y' )";
  if ( ! dbi_query ( $sql ) ) {
    $error = "Database error: " . dbi_error ();
   return $error;
  } 
 return $error;
}


function get_lastDay ( $year, $month = 'Jan',  $which, $time = '' ) {
 global $days_of_week, $months;
 if ( $time <> '' ) {
  $hours = substr ( $time, 0, strpos( $time, ":"));
  $minutes = substr ( $time, strpos( $time, ":") +1, 2);
 } else {
  $hours = $minutes = 0;
 }
  $lastday = date ( "w", mktime ( 0, 0, 0, $months[$month] +1 , 0, $year ) );
  $offset = -( ( $lastday +7 - $days_of_week[ substr( $which, 4, 3) ] ) % 7);
  $newdate = mktime ( $hours, $minutes, 0, $months[$month] + 1, $offset,  $year);

  return $newdate;
}
 
function get_ltgtDay (  $year, $month = 'Jan',  $which, $time = '' ) {
 global $days_of_week, $months;
  if ( $time <> '' ) {
  $hours = substr ( $time, 0, strpos( $time, ":"));
  $minutes = substr ( $time, strpos( $time, ":") +1, 2);
 } else {
  $hours = $minutes = 0;
 }
  $which_day = substr( $which, 5, strlen( $which) -5);
  $givenday = date ( "w", mktime ( 0, 0, 0, $months[$month] , $which_day, $year ) );
  if ( substr ( $which, 3,2) == "<=" ) {
    $offset = -( ( $givenday  + 7  - $days_of_week[ substr($which,0,3)]) % 7);
  } else {
    $offset = ( ( $days_of_week[ substr($which,0,3)] + 7 - $givenday   ) % 7);
  }
  $newdate = mktime ( $hours, $minutes, 0, $months[$month] , $which_day + $offset,  $year );

  return $newdate;
}

function get_seconds ( $time_part ) {
global $min_date, $max_date;
  if ( $time_part == "Link" ) {
    $ret = "Link";
  } else if ( $time_part == "0:00" ) {
    $ret = 0;
  } else {
    $neg_gmtoff = "";
    if( substr( $time_part , 0, 1) == "-" ) {
      $time_part =  substr($time_part ,1);
      $neg_gmtoff = "-";
    }
    $ret = (date("G", strtotime($time_part)) * 3600) +  
      (date( "i",strtotime( $time_part)) * 60) + 
      (date( "s", strtotime($time_part)));
    $ret = $neg_gmtoff . $ret;
  }
  return $ret;
}

function do_tz_import ( $file_path= "timezone/") {
 global $months, $min_date, $max_date;
 $error = "<b>Import Successful</b>";
 // You could delete any of these that you are sure your users will not need.
 // You can always rerun this script update your selection.
 $tz_file_array = array( 
   "africa", 
   "asia", 
   "australasia", 
   "europe", 
   "northamerica", 
   "southamerica"
  );
 //Delete any existing data
 $sql = "DELETE FROM webcal_tz_rules";
 if ( ! dbi_query ( $sql, false, false ) ) {
  $error = "Database error: " . dbi_error ();
  return $error;
 }
 $sql = "DELETE FROM webcal_tz_zones";
 if ( ! dbi_query ( $sql, false, false  ) ) {
  $error = "Database error: " . dbi_error ();
  return $error;
 }
  $sql = "DELETE FROM webcal_tz_list";
 if ( ! dbi_query ( $sql, false, false  ) ) {
  $error = "Database error: " . dbi_error ();
  return $error;
 }         
 $valid_tags = array( "Link", "Rule", "Zone" );
 for ($i = 0 ; $i < count ( $tz_file_array ); $i++ ) {
  if (!$fd=@fopen( $file_path . $tz_file_array[$i],"r", false)) {
  $error = "Can't read temporary file: $tz_file_array[$i]\n";
  return $error;
  } else {
   $line = 0;
 
  while (($data = fgets($fd, 1000)) !== FALSE) {
  //echo strlen( $data ) . "<br>" . $data . "<br>";
    if ( ( substr (trim($data),0,1) == "#" ) || strlen( $data ) <=2 ) {
     continue;
    } else if ( substr (trim($data),0,4) == "Link" ){
     //Ignore Links for now
     continue;
    } else {
     $data = trim ( $data, strrchr( $data, "#" ) ) ;
     $data = preg_split("/[\s,]+/", trim ($data ) ) ;
     if ( $data[0] == "Zone" ) {
      $data0 = "Zone";
      $data1 = $data[1];
     }
     if ( ! array_search ( $data[0], $valid_tags  )) {
      $zone_from = ( empty  ( $zone_until ) || 
       ($zone_until == $max_date || 
       $zone_until == "" )? $min_date : $zone_until +1);
      array_unshift($data, $data0, $data1);
     } else {
       $zone_from = $min_date;
     }
     //set rule_to to rule_from if 'only'
     $data[3] = ( $data[3] == "only"? $data[2] : $data[3]);
     //set rule_to to 2038 id max or maximum
     $data[3] = ( substr( $data[3],0,3)  == "max"? '2038' : $data[3]);
     if ( $data[0] == "Rule" && (
      ! ( $data[2] < 1970 && $data[3] < 1970 ) ) ) {
      $rule_at_suffix = '';
   
     if ( ! empty( $data[7] ) && preg_match ( "/(\D)$/i", $data[7], $match ) ){
      $data[7]  = substr( $data[7], 0, strlen($data[7]) -1);
      $rule_at_suffix = $match[0];
     }
     $rule_at = get_seconds ( $data[7] );
     $rule_save = get_seconds ( $data[8] );
     $sql = "INSERT INTO webcal_tz_rules ( rule_name, rule_from, rule_to, rule_type, " .
      "rule_in, rule_on, rule_at, rule_at_suffix, rule_save, rule_letter ) " .
      "VALUES ( '$data[1]', $data[2], $data[3], '$data[4]', " . $months[$data[5]] . 
      ", '$data[6]', $rule_at, '$rule_at_suffix',$rule_save,'" .
      ( $data[9] == "-" ?"":$data[9]) . "' )";
     if ( ! dbi_query ( $sql ) ) {
      $error = "Database error: " . dbi_error ();
     }
    }
    if ( $data[0] == "Zone" 
     && ( ! isset( $data[5]) ||  substr($data[5],0,4) >= 1970  )) {
     if ( isset ( $data[7] )  && substr( $data[7], 0, 4) == "last" ) {
      $zone_until =  get_lastDay ( $data[5], ( ! empty ($data[6])? $data[6]:""), 
       $data[7], ( ! empty ($data[8])? $data[8]:"") );
     } else if ( isset ( $data[7] )  && substr( $data[7], 4, 1) == "=" )  {
      $zone_until =  get_ltgtDay ( $data[5], ( ! empty ($data[6])? $data[6]:""), 
       $data[7], ( ! empty ($data[8])? $data[8]:"") );
     } else if (! isset( $data[5] ) ){
      $zone_until = $max_date;
     } else {
      $zone_until = strtotime( ( ! empty ($data[7])? $data[7]:"1") . 
       " ". ( ! empty ($data[6])? $data[6]:"Jan")  . 
       " " . ( ! empty ($data[5])? $data[5]:"2038"). 
       " " . ( ! empty ($data[8])? $data[8]:"") );
     }
     $zone_gmtoff = get_seconds( $data[2]);
   
     if ( $data[3] == "-"  || $data[3] == "1:00" ) {
      $data[3] = '';
     }
     $sql = "INSERT INTO webcal_tz_zones ( zone_name, zone_gmtoff, zone_rules, " .
      "zone_format, zone_from, zone_until ) " .
       "VALUES ( '$data[1]', $zone_gmtoff, '$data[3]', '$data[4]', $zone_from, $zone_until )";
     if ( ! dbi_query ( $sql ) ) {
      $error = "Database error: " . dbi_error ();
     }  
    }
   }
  }
  fclose($fd);
  }
 } // Next tz_file
   
 //Import Country Code and Coordinate Data
 $tz_file = "zone.tab";
 if (!$fd=@fopen(  $file_path . $tz_file,"r", false )) {
  $error = "Can't read temporary file: $tz_file\n";
  return $error;
 } else {
  $line = 0;
 
  while (($data = fgets($fd, 1000)) !== FALSE) {
   if ( ( substr (trim($data),0,1) == "#" ) || strlen( $data ) <=2 ) {
    continue;
   } else {
    $data = trim ( $data, strrchr( $data, "#" ) ) ;
    $data = preg_split("/[\s,]+/", trim ($data ) ) ;
    $sql = "UPDATE webcal_tz_zones  " .
     " SET zone_cc = '$data[0]', zone_coord = '$data[1]' " .
     " WHERE zone_name = '" . trim( $data[2] ) . "'";
    if ( ! dbi_query ( $sql ) ) {
     $error = "Database error: " . dbi_error ();
    }
   }   
  }
  fclose($fd);
 }
 
 //Import Country Name
 $tz_file = "iso3166.tab";
 if (!$fd=@fopen( $file_path .  $tz_file,"r", false )) {
  $error = "Can't read temporary file: $tz_file\n";
  return $error;
 } else {
  $line = 0;
 
  while (($data = fgets($fd, 1000)) !== FALSE) {
   if ( ( substr (trim($data),0,1) == "#" ) || strlen( $data ) <=2 ) {
    continue;
   } else {
    $data = trim ( $data, strrchr( $data, "#" ) ) ;
    $data = preg_split("/[\t]+/", trim ($data ) ) ;
    $sql = "UPDATE webcal_tz_zones  " .
     " SET zone_country = '" . addslashes( trim ( $data[1] ) )  . "' " .
     " WHERE zone_cc = '$data[0]'";
    if ( ! dbi_query ( $sql ) ) {
     $error = "Database error: " . dbi_error ();
    }
   }   
  }
  fclose($fd);
 }

 //Import Country Name
 $tz_file = "gmt.txt";
 if (!$fd=@fopen( $file_path .  $tz_file,"r", false )) {
  $error = "Can't read temporary file: $tz_file\n";
  return $error;
 } else {
  $line = 0;
 
  while (($data = fgets($fd, 1000)) !== FALSE) {
   if ( ( substr (trim($data),0,1) == "#" ) || strlen( $data ) <=2 ) {
    continue;
   } else {
    $data = trim ( $data, strrchr( $data, "#" ) ) ;
    $data = preg_split("/[\t]+/", trim ($data ) ) ;
    $sql = "INSERT INTO webcal_tz_list ( tz_list_id, tz_list_name, tz_list_text )  " .
     " VALUES ( $data[0], '$data[1]', '" . addslashes ( $data[2] . " " . $data[3] ) . "' )";
    if ( ! dbi_query ( $sql ) ) {
     $error = "Database error: " . dbi_error ();
    }
   }   
  }
  fclose($fd);
 }
 
  //Update version info
 $tz_file = "tz_version.txt";
 if (!$fd=@fopen( $file_path .  $tz_file,"r", false )) {
  $error = "Can't read temporary file: $tz_file\n";
  return $error;
 } else {
   //Delete any existing entry
   $sql = "DELETE FROM webcal_config WHERE cal_setting = 'WEBCAL_TZ_VERSION'";
   if ( ! dbi_query ( $sql ) ) {
    $error = "Database error: " . dbi_error ();
    return $error;
   }
  while (($data = fgets($fd, 1000)) !== FALSE) {
   if ( ( substr (trim($data),0,1) == "#" ) || strlen( $data ) <=2 ) {
    continue;
   } else {
    $data = trim ( $data ) ;
     $sql = "INSERT INTO webcal_config ( cal_setting, cal_value ) " .
       "VALUES ( 'WEBCAL_TZ_VERSION', '" . $data . "' )";
      if ( ! dbi_query ( $sql ) ) {
        $error = "Database error: " . dbi_error ();
       return $error;
    }
   }   
  }
  fclose($fd);
 }


return $error;
} // end function do_tz_import
?>
