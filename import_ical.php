<?php
/*
 * $Id$
 *
 * File Description:
 *	This file incudes functions for parsing iCal data files during
 *	an import.
 *
 *	It will be included by import_handler.php.
 *
 * The iCal specification is available online at:
 *	http://www.ietf.org/rfc/rfc2445.txt
 *
 */

// Parse the ical file and return the data hash.
//
function parse_ical ( $cal_file ) {
  global $tz, $errormsg;

  $ical_data = array();

  if (!$fd=@fopen($cal_file,"r")) {
    $errormsg .= "Can't read temporary file: $cal_file\n";
    exit();
  } else {
    // reflect the section where we are in the file:
    // VEVENT, VTODO, VJORNAL, VFREEBUSY, VTIMEZONE
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
      //echo "line = $line <br>";
      //echo "state = $state <br>";
      //echo "substate = $substate <br>";
      //echo "subsubstate = $subsubstate <br>";
      //echo "buff = " . htmlspecialchars ( $buff ) . "<br><br>\n";

      if ($state == "VEVENT") {
          if ( ! empty ( $subsubstate ) ) {
            if (preg_match("/^END:(.+)$/i", $buff, $match)) {
              if ( $match[1] == $subsubstate ) {
                $subsubstate = '';
              }
            } else if ( $subsubstate == "VALARM" && 
              preg_match ( "/TRIGGER:(.+)$/i", $buff, $match ) ) {
              // Example: TRIGGER;VALUE=DATE-TIME:19970317T133000Z
              //echo "Set reminder to $match[1]<br>";
              // reminder time is $match[1]
              // TODO: 
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
          } elseif (preg_match("/^DESCRIPTION.*:(.+)$/i", $buff, $match)) {
              $substate = "description";
              $event[$substate] = $match[1];
          } elseif (preg_match("/^CLASS.*:(.+)$/i", $buff, $match)) {
              $substate = "class";
              $event[$substate] = $match[1];
          } elseif (preg_match("/^PRIORITY.*:(.+)$/i", $buff, $match)) {
              $substate = "priority";
              $event[$substate] = $match[1];
	  } elseif (preg_match("/^DTSTART.*:(\d+T\d+)$/i", $buff, $match)) {
              $substate = "dtstart";
              $event[$substate] = $match[1];
	  } elseif (preg_match("/^DTSTART.*:(\d+)$/i", $buff, $match)) {
              $substate = "dtstart";
              $event[$substate] = $match[1];
          } elseif (preg_match("/^DTEND.*:(.+)$/i", $buff, $match)) {
              $substate = "dtend";
              $event[$substate] = $match[1];
          } elseif (preg_match("/^DURATION.*:(.+)$/i", $buff, $match)) {
              $substate = "duration";
              $durH = $durM = 0;
              if ( preg_match ( "/PT.*([0-9]+)H/", $match[1], $submatch ) )
                $durH = $submatch[1];
              if ( preg_match ( "/PT.*([0-9]+)M/", $match[1], $submatch ) )
                $durM = $submatch[1];
              $event[$substate] = $durH * 60 + $durM;
          } elseif (preg_match("/^RRULE.*:(.+)$/i", $buff, $match)) {
              $substate = "rrule";
              $event[$substate] = $match[1];
          } elseif (preg_match("/^EXDATE.*:(.+)$/i", $buff, $match)) {
              $substate = "exdate";
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
	      $ical_data[] = format_ical($event);
              // clear out data for new event
              $event = '';

	  // TODO: QUOTED-PRINTABLE descriptions

	  // folded lines
          } elseif (preg_match("/^[ ]{1}(.+)$/", $buff, $match)) {
              if ($substate != "none") {
                  $event[$substate] .= $match[1];
              } else {
                  $errormsg .= "Error in file $cal_file line $line:<br>$buff\n";
                  $error = true;
              }
          // For unsupported properties
	  } else {
            $substate = "none";
          }
      } elseif ($state == "VCALENDAR") {
          if (preg_match("/^BEGIN:VEVENT$/i", $buff)) {
            $state = "VEVENT";
          } elseif (preg_match("/^END:VCALENDAR$/i", $buff)) {
            $state = "NONE";
          } else if (preg_match("/^BEGIN:VTIMEZONE$/i", $buff)) {
            $state = "VTIMEZONE";
          } else if (preg_match("/^BEGIN:VALARM$/i", $buff)) {
            $state = "VALARM";
          }
      } elseif ($state == "NONE") {
         if (preg_match("/^BEGIN:VCALENDAR$/i", $buff))
           $state = "VCALENDAR";
      }
    } // End while
    fclose($fd);
  }

  return $ical_data;
}

// Convert ical format (yyyymmddThhmmssZ) to epoch time
//
function icaldate_to_timestamp($vdate,$plus_d = '0',$plus_m = '0', $plus_y = '0') {
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


// Put all ical data into import hash structure
//
function format_ical($event) {

  // Start and end time
  $fevent[StartTime] = icaldate_to_timestamp($event[dtstart]);
  if ( isset ( $event[dtend] ) ) {
    $fevent[EndTime] = icaldate_to_timestamp($event[dtend]);
  } else {
    if ( isset ( $event[duration] ) ) {
      $fevent[EndTime] = $fevent[StartTime] + $event[duration] * 60;
    } else {
      $fevent[EndTime] = $fevent[StartTime];
    }
  }

  // Calculate duration in minutes
  if ( isset ( $event[duration] ) ) {
    $fevent[Duration] = $event[duration];
  } else if ( empty ( $fevent[Duration] ) ) {
    $fevent[Duration] = ($fevent[EndTime] - $fevent[StartTime]) / 60;
  }
  if ($fevent[Duration] == '1440') {
    //All day (untimed)
    $fevent[Duration] = '0';
    $fevent[Untimed] = 1;
  }

  $fevent[Summary] = $event['summary'];
  $fevent[Description] = $event['description'];
  $fevent[Private] = preg_match("/private|confidential/i", $event['class']) ? '1' : '0';
  $fevent[UID] = $event['uid'];

  // Repeats
  //
  // For now, we just handle the case were the event spans multiple
  // dates, not the kind spelled out in RRULE.
  //

  return $fevent;
}

// Figure out days of week for weekly repeats
//
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
//
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
    $endtime = icaldate_to_timestamp($start,$plus_d,$plus_m,$plus_y);

  // if we have the enddate
  } else {
    $endtime = icaldate_to_timestamp($end);
  }
  return $endtime;
}

?>
