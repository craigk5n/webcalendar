<?php
// Parse the vcal file and return the data hash.
function parse_vcal($cal_file) {
  global $tz, $errormsg;

  $vcal_data = array();

  //echo "Parsing vcal file... <br />\n";

  if (!$fd=@fopen($cal_file,"r")) {
    $errormsg .= "Can't read temporary file: $cal_file\n";
    exit();
  } else {
    // reflect the section where we are in the file:
    // VCALENDAR, TZ/DAYLIGHT, VEVENT, ALARM
    $state = "NONE";
    $substate = "none"; // reflect the sub section
    $subsubstate = ""; // reflect the sub-sub section
    $error = false;
    $line = 0;
    $event = '';

    while (!feof($fd) && !$error) {
      $line++;
      $buff = fgets($fd, 4096);
      $buff = chop($buff);

      // parser debugging code...
      //echo "line = $line <br />";
      //echo "state = $state <br />";
      //echo "substate = $substate <br />";
      //echo "subsubstate = $subsubstate <br />";
      //echo "buff = " . htmlspecialchars ( $buff ) . "<br /><br />\n";

      if ($state == "VEVENT") {
          if ( ! empty ( $subsubstate ) ) {
            if (preg_match("/^END:(.+)$/i", $buff, $match)) {
              if ( $match[1] == $subsubstate ) {
                $subsubstate = '';
              }
            } else if ( $subsubstate == "VALARM" && 
              preg_match ( "/TRIGGER:(.+)$/i", $buff, $match ) ) {
		//echo "Set reminder to $match[1]<br />";
		//reminder time is $match[1]
            }
          }
          else if (preg_match("/^BEGIN:(.+)$/i", $buff, $match)) {
            $subsubstate = $match[1];
          }
           // we suppose ":" is on the same line as property name, this can perhaps cause problems
	  else if (preg_match("/^SUMMARY.*:(.+)$/i", $buff, $match)) {
              $substate = "summary";
              $event[$substate] = $match[1];
          } elseif (preg_match("/^DESCRIPTION:(.+)$/i", $buff, $match)) {
              $substate = "description";
              $event[$substate] = $match[1];
          } elseif (preg_match("/^DESCRIPTION\S*:(.+)$/i", $buff, $match)) {
              $substate = "description";
              $event[$substate] = $match[1];
              if ( preg_match ( "/encoding=quoted-printable/i", $buff ) ) {
                $event[$substate] = quoted_printable_decode ( $match[1] );
              }
          } elseif (preg_match("/^CLASS.*:(.+)$/i", $buff, $match)) {
              $substate = "class";
              $event[$substate] = $match[1];
          } elseif (preg_match("/^PRIORITY.*:(.+)$/i", $buff, $match)) {
              $substate = "priority";
              $event[$substate] = $match[1];
	  } elseif (preg_match("/^DTSTART.*:(.+)$/i", $buff, $match)) {
              $substate = "dtstart";
              $event[$substate] = $match[1];
          } elseif (preg_match("/^DTEND.*:(.+)$/i", $buff, $match)) {
              $substate = "dtend";
              $event[$substate] = $match[1];
          } elseif (preg_match("/^RRULE.*:(.+)$/i", $buff, $match)) {
              $substate = "rrule";
              $event[$substate] = $match[1];
          } elseif (preg_match("/^EXDATE.*:(.+)$/i", $buff, $match)) {
              $substate = "exdate";
              $event[$substate] = $match[1];
          } elseif (preg_match("/^DALARM.*:(.+)$/i", $buff, $match)) {
              $substate = "dalarm";
              $event[$substate] = $match[1];
          } elseif (preg_match("/^CATEGORIES.*:(.+)$/i", $buff, $match)) {
              $substate = "categories";
              $event[$substate] = $match[1];
          } elseif (preg_match("/^UID.*:(.+)$/i", $buff, $match)) {
              $substate = "uid";
              $event[$substate] = $match[1];
          } elseif (preg_match("/^END:VEVENT$/i", $buff, $match)) {
              $state = "VCALENDAR";
              $substate = "none";
              $subsubstate = '';
	      $vcal_data[] = format_vcal($event);
              // clear out data for new event
              $event = '';

	  // TODO: QUOTED-PRINTABLE descriptions

	  // folded lines
          } elseif (preg_match("/^[ ]{1}(.+)$/", $buff, $match)) {
              if ($substate != "none") {
                  $event[$substate] .= $match[1];
              } else {
                  $errormsg .= "Error in file $cal_file line $line:<br />$buff\n";
                  $error = true;
              }
          // For unsupported properties
	  } else {
            $substate = "none";
          }
      } elseif ($state == "VCALENDAR") {
          if (preg_match("/^TZ.*:(.+)$/i", $buff, $match)) {
            $event['tz'] = $match[1];
          } elseif (preg_match("/^DAYLIGHT.*:(.+)$/i", $buff, $match)) {
            $event['daylight'] = $match[1];
          } elseif (preg_match("/^BEGIN:VEVENT$/i", $buff)) {
            $state = "VEVENT";
          } elseif (preg_match("/^END:VCALENDAR$/i", $buff)) {
            $state = "NONE";
          }
      } elseif ($state == "NONE") {
         if (preg_match("/^BEGIN:VCALENDAR$/i", $buff))
           $state = "VCALENDAR";
         else if (preg_match("/^BEGIN:ALARM$/i", $buff))
           $state = "ALARM";
      }
    } //End while
    fclose($fd);
  }

  return $vcal_data;
}

// Convert vcal format (yyyymmddThhmmssZ) to epoch time
function vcaldate_to_timestamp($vdate,$plus_d = '0',$plus_m = '0', $plus_y = '0') {
  global $TZoffset;

  $y = substr($vdate, 0, 4) + $plus_y;
  $m = substr($vdate, 4, 2) + $plus_m;
  $d = substr($vdate, 6, 2) + $plus_d;
  $H = substr($vdate, 9, 2);
  $M = substr($vdate, 11, 2);
  $S = substr($vdate, 13, 2);
  $Z = substr($vdate, 15, 1);
  if ($Z == 'Z') {
    $TS = gmmktime($H,$M,$S,$m,$d,$y);
  } else {
    // Problem here if server in different timezone
    $TS = mktime($H,$M,$S,$m,$d,$y);
  }

  return $TS;
}

// Put all vcal data into import hash structure
function format_vcal($event) {
  // Start and end time
  $fevent[StartTime] = vcaldate_to_timestamp($event[dtstart]);
  $fevent[EndTime] = vcaldate_to_timestamp($event[dtend]);

  // Calculate duration in minutes
  $fevent[Duration]           = ($fevent[EndTime] - $fevent[StartTime]) / 60;
  if ($fevent[Duration] == '1440') { $fevent[Duration] = '0'; $fevent[Untimed] = 1; } //All day (untimed)

  $fevent[Summary] = $event['summary'];
  $fevent[Description] = $event['description'];
  $fevent['Private'] = preg_match("/private|confidential/i", $event['class']) ? '1' : '0';
  $fevent[UID] = $event['uid'];

  // Repeats
  //
  // vcal 1.0 repeats can be very complicated and the webcalendar doesn't
  // actually support all of the ways repeats can be specified.  We will
  // focus on vcals dumped from Palm Desktop and Lotus Notes, which are simple
  // and the ones webcalendar should fully support.
  if ($event[rrule]) {
    //split into pieces
    $RR = explode(" ", $event[rrule]);

    if (preg_match("/^D(.+)$/i", $RR[0], $match)) {
      $fevent[Repeat][Interval] = '1';
      $fevent[Repeat][Frequency] = $match[1];
    } elseif (preg_match("/^W(.+)$/i", $RR[0], $match)) {
      $fevent[Repeat][Interval] = '2';
      $fevent[Repeat][Frequency] = $match[1];
      $fevent[Repeat][RepeatDays] = rrule_repeat_days($RR);
    } elseif (preg_match("/^MP(.+)$/i", $RR[0], $match)) {
      $fevent[Repeat][Interval] = '3';
      $fevent[Repeat][Frequency] = $match[1];
      if ($RR[1] == '5+') {
        $fevent[Repeat][Interval] = '6'; // Last week (monthlyByDayR)
      }
    } elseif (preg_match("/^MD(.+)$/i", $RR[0], $match)) {
      $fevent[Repeat][Interval] = '4';
      $fevent[Repeat][Frequency] = $match[1];
    } elseif (preg_match("/^YM(.+)$/i", $RR[0], $match)) {
      $fevent[Repeat][Interval] = '5';
      $fevent[Repeat][Frequency] = $match[1];
    } elseif (preg_match("/^YD(.+)$/i", $RR[0], $match)) {
      $fevent[Repeat][Interval] = '5';
      $fevent[Repeat][Frequency] = $match[1];
    }

    $end = end($RR);

    // No end in Palm is 12-31-2031
    if (($end != '20311231') && ($end != '#0')) {
      $fevent[Repeat][EndTime] = rrule_endtime($fevent[Repeat][Interval],$fevent[Repeat][Frequency],$event[dtstart],$end);
    }

    // Repeating exceptions?
    if ($event[exdate]) {
      $fevent[Repeat][Exceptions] = array();
      $EX = explode(",", $event[exdate]);
      foreach ( $EX as $exdate ){
        $fevent[Repeat][Exceptions][] = vcaldate_to_timestamp($exdate);
      }
    }
  } // end if rrule

// TODO
//  $fevent[Category];
//  $fevent[AlarmSet];
//  $fevent[AlarmAdvanceAmount];
//  $fevent[AlarmAdvanceType];

  return $fevent;
}

// Figure out days of week for weekly repeats
function rrule_repeat_days($RA) {
  $T = count($RA);
  $j = $T - 1;
  $sun = $mon = $tue = $wed = $thu = $fri = $sat = 'n';
  for ($i = 1; $i < $j; $i++) {
    if ($RA[$i] == 'SU') {
      $sun = 'y';
    } elseif ($RA[$i] == 'MO') {
      $mon = 'y';
    } elseif ($RA[$i] == 'TU') {
      $tue = 'y';
    } elseif ($RA[$i] == 'WE') {
      $wed = 'y';
    } elseif ($RA[$i] == 'TH') {
      $thu = 'y';
    } elseif ($RA[$i] == 'FR') {
      $fri = 'y';
    } elseif ($RA[$i] == 'SA') {
      $sat = 'y';
    }
  }
  return $sun.$mon.$tue.$wed.$thu.$fri.$sat;
}


// Calculate repeating ending time
function rrule_endtime($int,$freq,$start,$end) {
  // if # then we have to add the difference to the start time
  if (preg_match("/^#(.+)$/i", $end, $M)) {
    $T = $M[1] * $freq;
    $plus_d = $plus_m = $plus_y = '0';
    if ($int == '1') {
      $plus_d = $T;
    } elseif ($int == '2') {
      $plus_d = $T * 7;
    } elseif ($int == '3') {
      $plus_m = $T;
    } elseif ($int == '4') {
      $plus_m = $T;
    } elseif ($int == '5') {
      $plus_y = $T;
    } elseif ($int == '6') {
      $plus_m = $T;
    }
    $endtime = vcaldate_to_timestamp($start,$plus_d,$plus_m,$plus_y);

  // if we have the enddate
  } else {
    $endtime = vcaldate_to_timestamp($end);
  }
  return $endtime;
}
?>
