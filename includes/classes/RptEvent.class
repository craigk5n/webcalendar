<?php
/**
 * Declares the RepeatingEvent class.
 *
 * @author Adam Roben <adam.roben@gmail.com>
 * @copyright Craig Knudsen, <cknudsen@cknudsen.com>, http://www.k5n.us/cknudsen
 * @license http://www.gnu.org/licenses/gpl.html GNU GPL
 * @version $Id: RptEvent.class,v 1.13.2.2 2007/08/06 02:28:27 cknudsen Exp $
 * @package WebCalendar
 * @subpackage Events
 */

/**
 * A repeating event parameter object.
 *
 * This is a parameter object. It only has simple accessors.
 */
class RepeatingEvent extends Event {
  /**
   * The type of repeat.
   * @var string
   * @access private
   */
  var $_repeatType;
  /**
   * The end date of the repetition.
   * @var string
   * @access private
   */
  var $_repeatEnd;
  /**
   * The end time of the repetition.
   * @var string
   * @access private
   */
  var $_repeatEndTime;

  /**
   * The end date/time of the repetition.
   * @var string
   * @access private
   */
  var $_repeatEndDateTime;
 /**
   * The frequency of the repetition.
   * @var int
   * @access private
   */
  var $_repeatFrequency;
  /**
   * The days each week the event occurs
   * @var string in the format (nnnnnnn)
   * @access private
   */
  var $_repeatDays;
  /**
   * The months in which the event occurs
   * @var string in the format (1,2,3,4,5,6...)
   * @access private
   */
  var $_repeatByMonth;
  /**
   * Dates to apply to monthByDate
   * @var string in the format (1,2,3,4...31,-31,-30,-29...-1 )
   * @access private
   */
  var $_repeatByMonthDay;
  /**
   * Days of the week the events occur (Replaces $_repeatDays )
   * @var string in the format (MO,TU,WE,TH,FR,SA,SU,-1MO,-2SU...)
   * @access private
   */
  var $_repeatByDay;
  /**
   * Specified the nth occurance within the existing event date set
   * @var string in the format (1,2,3,4...366,-366,-365...-1 )
   * @access private
   */
  var $_repeatBySetPos;
  /**
   * Week on the year that event occurs (#1 = first week with 4 days)
   * @var string in the format (1,2,3,4...53,-53,-52...-1 )
   * @access private
   */
  var $_repeatByWeekNo;
  /**
   * Days of the year that event occurs
   * @var string in the format (1,2,3,4...366,-366,-365...-1 )
   * @access private
   */
  var $_repeatByYearDay;
  /**
   * Specified the start day of the week
   * @var string in the format (MO || TU || WE || TH || FR || SA || SU)
   * @access private
   */
  var $_repeatWkst;
  /**
   * Specified the number of repeats
   * @var integer
   * @access private
   */
  var $_repeatCount;
 /**
   * Dates on which the event should not fall
   * @var array
   * @access private
   */
  var $_repeatExceptions;
  /**
   * Additional dates on which the event should  fall
   * @var array
   * @access private
   */
  var $_repeatInclusions;
  /**
   * All dates on which the event should  fall
   * @var array
   * @access private
   */
  var $_repeatAllDates;

  /**
   * Creates a new RepeatingEvent.
   *
   * @param string $name           Name of the event
   * @param string $description    Event's description
   * @param string $date           Event's date (in YYYYMMDD format)
   * @param string $time           Event's time (in HHMMSS format)
   * @param string $moddate        Event's mod date (in YYYYMMDD format)
   * @param string $modtime        Event's mod time (in HHMMSS format)
   * @param int    $id             Event's ID
   * @param mixed  $extForID       Event's extension ID (or '' for none)
   * @param int    $priority       Event's priority
   * @param string $access         Event's access level
   * @param int    $duration       Event's duration (in minutes)
   * @param string $status         Event's status
   * @param string $owner          Event's cal_create_by
   * @param mixed  $category       Event's category ID
   * @param string $login          Event's owner
   * @param string $rpt_type       Event's repeat type
   * @param string $end            End date for repetition
   * @param string $endtime        End time for repetition
   * @param int    $frequency      Frequency of repetition
   * @param string $days           Days on which the event falls (for weekly events)
   * @param string $rpt_bymonth    Months that events occur
   * @param string $rpt_bymonthday Days of month that events occur
   * @param string $rpt_byday      Days on which the event falls (replaces $days)
   * @param string $rpt_bysetpos   Nth occurance within existing event set
   * @param string $rpt_byweekno   Weeks that events occur
   * @param string $rpt_byyearday  Days of the year that events occur
   * @param string $rpt_wkst       Start day of week for ByDay events
   * @param string $rpt_count      Number of repeat occurances (including orginal date)
   * @param array  $exceptions     Dates on which the event should not occur
   * @param array  $inclusions     Additional dates on which the event should occur
   * @param array  $rpt_all_dates  All dates on which the event should occur

   *
   * @return Event The new Event
   *
   * @access public
   */
  function RepeatingEvent ( $name, $description, $date, $time, $id, $extForID,
    $priority, $access, $duration, $status, $owner, $category, $login,
    $cal_type, $location, $url, $due_date, $due_time, $percent,$moddate, $modtime,
    $rpt_type, $end, $frequency, $days, $rpt_bymonth,
    $rpt_bymonthday, $rpt_byday, $rpt_bysetpos, $rpt_byweekno,
    $rpt_byyearday, $rpt_wkst, $rpt_count, $endtime,
    $exceptions, $inclusions, $rpt_all_dates ) {

    /* Silly PHP4 hack */
    $parent = get_parent_class ( $this );

    parent::$parent ( $name, $description, $date, $time, $id, $extForID,
      $priority, $access, $duration, $status, $owner, $category, $login,
      $cal_type, $location, $url, $due_date, $due_time, $percent, $moddate, $modtime );

    $this->_repeatType = $rpt_type;
    $this->_repeatEnd = $end;
    $this->_repeatEndTime = $endtime;
    $this->_repeatFrequency = $frequency;
    $this->_repeatDays = $days;
    $this->_repeatByMonth = $rpt_bymonth;
    $this->_repeatByMonthDay = $rpt_bymonthday;
    $this->_repeatByDay = $rpt_byday;
    $this->_repeatBySetPos = $rpt_bysetpos;
    $this->_repeatByWeekNo = $rpt_byweekno;
    $this->_repeatByYearDay = $rpt_byyearday;
    $this->_repeatWkst = $rpt_wkst;
    $this->_repeatCount = $rpt_count;
    $this->_repeatExceptions = $exceptions;
   $this->_repeatInclusions = $inclusions;
   $this->_repeatAllDates = $rpt_all_dates;
  }

 /**
   * Gets the event's repeat type
   *
   * Can be one of:
   * - daily
   * - weekly
   * - monthlyByDay
   * - monthlyBySetPos
   * - monthlyByDate
   * - yearly
   *
   * @return string The event's repeat type
   *
   * @access public
   */
  function getRepeatType () {
    return $this->_repeatType;
  }

  /**
   * Gets the event's end date
   *
   *
   * @return int The event's end date
   *
   * @access public
   */
  function getRepeatEnd () {
    return $this->_repeatEnd;
  }

  /**
   * Gets the event's end time
   *
   *
   * @return int The event's end time
   *
   * @access public
   */
  function getRepeatEndTime () {
    return $this->_repeatEndTime;
  }

    /**
   * Gets the event's end date/time
   *
   * @return string The event's end date/time (in YYYYMMDDHHMMSS format)
   *
   * @access public
   */
  function getRepeatEndDateTime () {
    $time = ($this->_repeatEndTime ? $this->_repeatEndTime: 0);
    $this->_repeatEndDateTime = $this->_repeatEnd . sprintf ( "%06d", $time );
    return $this->_repeatEndDateTime;
  }
    /**
   * Gets the event's end date/time
   *
   * @return string The event's end date/time (in UNIX Timestamp format)
   *
   * @access public
   */
  function getRepeatEndDateTimeTS () {
    if ( $this->_repeatEnd > 0 ) {
      $year = substr ( $this->_repeatEnd, 0, 4 );
      $month = substr ( $this->_repeatEnd, 4, 2 );
      $day = substr ( $this->_repeatEnd, 6, 2 );
      if ( $this->_repeatEndTime > 0 ) {
        $h = (int) ( $this->_repeatEndTime / 10000 );
        $m = ( $this->_repeatEndTime / 100 ) % 100;
      } else {
        $h = $m = 0;
      }
      $edt = gmmktime ( $h, $m, 0, $month, $day, $year );
      $this->_repeatEndDateTimeTS = ( $edt > 0 ? $edt : '' );
    } else {
      $this->_repeatEndDateTimeTS = '';
    }
    return $this->_repeatEndDateTimeTS;
  }

  /**
   * Gets the event's repeat frequency
   *
   * @return int The event's repeat frequency
   *
   * @access public
   */
  function getRepeatFrequency () {
    return $this->_repeatFrequency;
  }

  /**
   * Gets the days on which the event falls.
   *
   * @return string The days on which the event falls
   *
   * @access public
   */
  function getRepeatDays () {
    return $this->_repeatDays;
  }
  /**
   * Gets the months in which the event falls.
   *
   * @return string The months on which the event falls
   *
   * @access public
   */
  function getRepeatByMonth () {
    return $this->_repeatByMonth;
  }
  /**
   * Gets the days of the month in which the event falls.
   *
   * @return string The days on which the event falls
   *
   * @access public
   */
  function getRepeatByMonthDay () {
    return $this->_repeatByMonthDay;
  }
  /**
   * Gets the days in which the event falls.
   *
   * @return string The days on which the event falls
   *
   * @access public
   */
  function getRepeatByDay () {
    return $this->_repeatByDay;
  }
  /**
   * Gets the Nth occurance of the event set.
   *
   * @return string The Nth occurance of the  event set
   *
   * @access public
   */
  function getRepeatBySetPos () {
    return $this->_repeatBySetPos;
  }
  /**
   * Gets the weeks in which the event falls.
   *
   * @return string The weeks on which the event falls
   *
   * @access public
   */
  function getRepeatByWeekNo () {
    return $this->_repeatByWeekNo;
  }
  /**
   * Gets the days of the year in which the event falls.
   *
   * @return string The days of the year on which the event falls
   *
   * @access public
   */
  function getRepeatByYearDay () {
    return $this->_repeatByYearDay;
  }
  /**
   * Gets the start of the week for ByDay events.
   *
   * @return string The start of the week for ByDay events
   *
   * @access public
   */
  function getRepeatWkst () {
    return $this->_repeatWkst;
  }
  /**
   * Gets the Count value for this event
   *
   * @return integer The number of repeat events
   *
   * @access public
   */
  function getRepeatCount () {
    return $this->_repeatCount;
  }
  /**
   * Gets the event's exception dates
   *
   * @return array The event's exception dates
   *
   * @access public
   */
  function getRepeatExceptions () {
    return $this->_repeatExceptions;
  }

  /**
   * Adds an exception to this event
   *
  * Add the ID to the end to aid in matching up with the events class
  *
   * @param string $exception Date of exception (in YYYYMMDD format)
   * @param int $id ID of exception
   *
   * @access public
   */
  function addRepeatException ( $exception, $id ) {
    $this->_repeatExceptions[] = $exception . $id;
  }

  /**
   * Gets the event's inclusion dates
   *
   * @return array The event's inclusion dates
   *
   * @access public
   */
  function getRepeatInclusions () {
    return $this->_repeatInclusions;
  }

  /**
   * Adds an inclusion to this event
   *
   * @param string $inclusion Date of inclusion (in YYYYMMDD format)
   *
   * @access public
   */
  function addRepeatInclusion ( $inclusion ) {
    $this->_repeatInclusions[] = $inclusion;
  }

  /**
   * Gets the event's complete date list
   *
   * @return array The event's total dates
   *
   * @access public
   */
  function getRepeatAllDates () {
    return $this->_repeatAllDates;
  }

  /**
   * Adds the event's complete date list
   *
   * @param array $dates All date of event (in YYYYMMDD format)
   *
   * @access public
   */
  function addRepeatAllDates ( $dates ) {
    $this->_repeatAllDates = $dates;
  }
}
?>
