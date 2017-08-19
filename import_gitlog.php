<?php
/**
 * File Description:
 * This file incudes functions for parsing output from the 'git log' command.
 *
 * It will be included by import_handler.php.
 * Note that the code here is really just parsing the git log data.  The
 * actual import into the database happens in import_data which is in
 * includes/xcal.php.  It was originally intended for VCAL and ICAL, so
 * the array names of the parsed data will reflect that.
 */

/**
 * Parse the git log file and return the data hash.
 * Tested with:
 *   - git log
 *   - git log --stat
 */
function parse_gitlog ( $cal_file ) {
  global $errormsg, $tz;

  $import_data = [];

  if ( ! $fd = @fopen ( $cal_file, 'r' ) ) {
    $errormsg .= 'Cannot read temporary file: ' . "$cal_file\n";
    exit();
  } else {
    $commitId = $author = $date = $message = '';
    $inMessage = false;
    $matches = [];
    while ( ( $line = fgets ( $fd ) ) != false ) {
      $line = rtrim ( $line );
      if ( preg_match ( "/^commit\s+(\S+)/", $line, $matches ) ) {
        if ( $inMessage ) {
          // Add previous commit
          $obj = create_event_object ( $commitId, $author, $date, $message );
          $import_data[] = $obj;
          $inMessage = false;
          $commitId = $author = $date = $message = '';
        }
        $commitId = $matches[1];
      } else if ( preg_match ( "/^Author:\s+(\S.*)/", $line, $matches ) ) {
        $author = $matches[1];
      } else if ( preg_match ( "/^Date:\s+(\S.*)/", $line, $matches ) ) {
        $date = parseGitDate ( $matches[1] );
      } else {
        // Everything else is the commit message
        $inMessage = true;
        // skip any leading blank lines
        if ( strlen ( $message ) > 0 || strlen ( trim ( $line ) ) > 0 ) {
          if ( strlen ( $message ) > 0 )
            $message .= "\n";
          $message .= $line;
        }
      }
    }
    if ( strlen ( $commitId ) > 0 ) {
      $obj = create_event_object ( $commitId, $author, $date, $message );
      $import_data[] = $obj;
    }
  }
  return $import_data;
}

// Convert the date from a git log into unix timestamp.
function parseGitDate ( $dateStr ) {
  // This is where PHP shines :-)
  return strtotime ( $dateStr );
}

function create_event_object ( $commitId, $author, $date, $message ) {
  $obj = [];

  $obj['UID'] = $commitId;
  $obj['CalendarType'] = 'VEVENT'; // The default type
  $obj['StartTime'] = $date; // In seconds since 1970 (Unix Epoch)
  $obj['EndTime'] = $date; // In seconds since 1970 (Unix Epoch)
  // Summary should be first 6 chars of commit id and first line of
  // commit message.
  $messageLines = explode ( "\n", $message );
  $summary = substr ( $commitId, 0, 6 ) . ' ' . $messageLines[0];
  $obj['Summary'] = $summary; // Summary of event (string)
  $obj['Description'] = nl2br ( $message ); // Full Description (string)
  $obj['AlarmSet'] = 0; // 1 = true  0 = false
  $obj['Duration'] = 0;
  $obj['AllDay'] = 0; // 1 = true  0 = false

  return $obj;
}

?>
