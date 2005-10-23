<?php
/*
 * $Id$
 *
 * File Description:
 * This file incudes functions for parsing CSV files generated from
 * MS Outlook.
 *
 * It will be included by import_handler.php.
 *
 * Limitations:
 * This only works when the user does not "Map Custom Fields" during
 * the export from Outlook.
 *
 */



// Parse the Outlook CSV file and return the data hash.
function parse_outlookcsv ( $cal_file ) {
  global $tz, $errormsg;

  $outlookcsv_data = array();

  if (!$fd=@fopen($cal_file,"r")) {
    $errormsg .= "Can't read temporary file: $cal_file\n";
    exit();
  } else {

    #Burn First Row of Headers
    $data = fgetcsv($fd, filesize($cal_file), ",");
  
    while ($data = fgetcsv($fd, filesize($cal_file)) ) {
  
      $subject = addslashes($data[0]);
      $start = date("F d, Y H:i:s",strtotime($data[1]." ".$data[2]));
      $end = date("F d, Y H:i:s",strtotime($data[3]." ".$data[4]));
      $all_day_event = (int)toBoolean($data[5]);
      $remind_on_off = (int)toBoolean($data[6]);
      $reminder = date("Y-m-d H:i:s",strtotime($data[7]." ".$data[8]));
      $meeting_organizer = $data[9];
      $required_attendies = $data[10];
      $optional_attendies = $data[11];
      $meeting_resources = $data[12];
      $billing_information = $data[13];
      $categories = addslashes($data[14]);
      $description = addslashes($data[15]); 
      $location = addslashes($data[16]); 
      $mileage = $data[17]; 
      $priority = $data[18]; 
      $class = (int)toBoolean($data[19]); 
      $sensitivity = $data[20]; 
      $show_time_as = $data[21];
    
      // parser debugging code...
      //print_r($data); exit;

      /*
       * Start New Section For Outlook CSV
       */
      //$tmp_data['RecordID']           =  ;
      $tmp_data['StartTime']          =  strtotime($start); //In seconds since 1970 (Unix Epoch)
      $tmp_data['EndTime']            =  strtotime($end);//In seconds since 1970 (Unix Epoch)
      $tmp_data['Summary']            =  $subject; //Summary of event (string)
      $tmp_data['Duration']           =  dateDifference(strtotime($start),strtotime($end),1); //How long the event lasts (in minutes)
      $tmp_data['Description']        =  $description; //Full Description (string)
      $tmp_data['Location']           =  $location; //Location (string)
      $tmp_data['AllDay']             =  $all_day_event; //1 = true  0 = false
      $tmp_data['Class']              =  ( $class == 1 ? "Private":"Public" );
      $tmp_data['Category']           =  $categories; //comma seperated string of categories
      $tmp_data['AlarmSet']           =  0; //1 = true  0 = false
      $tmp_data['AlarmAdvanceAmount'] =  -1; //How many units in AlarmAdvanceType (-1 means not set)
      $tmp_data['AlarmAdvanceType']   =  -1; //Units: (0=minutes, 1=hours, 2=days)
      
      /*
      $tmp_data['Repeat']             =  Array containing repeat information (if repeat)
      $tmp_data['Repeat']['Interval']   =  1=daily,2=weekly,3=MonthlyByDay,4=MonthlyByDate,5=Yearly,6=monthlyBySetPos
      $tmp_data['Repeat']['Frequency']  =  How often event occurs. (1=every, 2=every other,etc.)
      $tmp_data['Repeat']['EndTime']    =  When the repeat ends (In seconds since 1970 (Unix Epoch))
      $tmp_data['Repeat']['Exceptions'] =  Exceptions to the repeat (In seconds since 1970 (Unix Epoch))
      $tmp_data['Repeat']['RepeatDays'] =  For Weekly: What days to repeat on (7 characters...y or n for each day)
       */
    
      $outlookcsv_data[] = $tmp_data;
      
      //print_r($tmp_data);
  
    } // End while 
    fclose($fd);
  }

  //print_r($outlookcsv_data); exit;
  
  return $outlookcsv_data;
}



function dateDifference($start_timestamp,$end_timestamp,$unit= 0){
  $days_seconds_star= (23 * 56 * 60) + 4.091; // Star Day
  $days_seconds_sun= 24 * 60 * 60; // Sun Day
  $difference_seconds= $end_timestamp - $start_timestamp;
  switch($unit){
   case 3: // Days
     $difference_days= round(($difference_seconds / $days_seconds_sun),2);
     return 'approx. '.$difference_hours.' Days';
   case 2: // Hours
     $difference_hours= round(($difference_seconds / 3600),2);
     return 'approx. '.$difference_hours.' Hours';
   break;
   case 1: // Minutes
     $difference_minutes= round(($difference_seconds / 60),2);
     return $difference_minutes;
   break;
   default: // Seconds
     if($difference_seconds > 1){
       return $difference_seconds.' Seconds';
     }
     else{
       return $difference_seconds.' Second';
     }
  }
}

function toBoolean($string) {
  $string = strtoupper($string);
  $true_array = array("TRUE","T","1","TR");
  if(in_array($string,$true_array)) return true;
  else return false;
}
?>
