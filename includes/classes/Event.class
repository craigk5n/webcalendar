<?php
/**
 * Declares the Event class.
 *
 * @author Adam Roben <adam.roben@gmail.com>
 * @copyright Craig Knudsen, <cknudsen@cknudsen.com>, http://www.k5n.us/cknudsen
 * @license http://www.gnu.org/licenses/gpl.html GNU GPL
 * @version $Id: Event.class,v 1.20.2.4 2008/04/24 19:28:50 umcesrjones Exp $
 * @package WebCalendar
 * @subpackage Events
 */

/**
 * An event parameter object.
 *
 * This is a parameter object. It only has simple accessors.
 */
class Event {
  /**
   * The event's name.
   * @var string
   * @access private
   */
  var $_name;
  /**
   * The event's description.
   * @var string
   * @access private
   */
  var $_description;
  /**
   * The event's date (in YYYYMMDD format).
   * @var string
   * @access private
   */
  var $_date;
  /**
   * The event's time (in HHMMSS format).
   * @var string
   * @access private
   */
  var $_time;
  /**
   * The event's modified date (in YYYYMMDD format).
   * @var string
   * @access private
   */
  var $_moddate;
  /**
   * The event's modified time (in HHMMSS format).
   * @var string
   * @access private
   */
  var $_modtime;
  /**
   * The event's ID.
   * @var int
   * @access private
   */
  var $_id;
  /**
   * Extension ID.
   *
   * If this event is an extension of an event that carried over into the
   * current date, <var>$_extForID</var> will hold the original event's date.
   *
   * @var mixed
   * @access private
   */
  var $_extForID;
  /**
   * The event's priority.
   * @var int
   * @access private
   */
  var $_priority;
  /**
   * The event's access level.
   * @var string
   * @access private
   */
  var $_access;
  /**
   * The event's duration in minutes.
   * @var int
   * @access private
   */
  var $_duration;
  /**
   * The event's status.
   * @var string
   * @access private
   */
  var $_status;
  /**
   * The event's owner.
   * @var string
   * @access private
   */
  var $_owner;
  /**
   * The event's category ID.
   * @var int
   * @access private
   */
  var $_category;
  /**
   * The event's owner.
   * @var mixed
   * @access private
   */
  var $_login;

 /**
   * The event's type.
   * @var mixed
   * @access private
   */
  var $_calType;
 /**
   * The event's type name.
   * @var mixed
   * @access private
   */
  var $_calTypeName;
 /**
   * The event's location.
   * @var mixed
   * @access private
   */
  var $_location;
 /**
   * The event's url.
   * @var mixed
   * @access private
   */
  var $_url;
 /**
   * The event's dueDate.
   * @var mixed
   * @access private
   */
  var $_dueDate;
 /**
   * The event's dueTime.
   * @var mixed
   * @access private
   */
  var $_dueTime;
 /**
   * The event's percent.
   * @var mixed
   * @access private
   */
  var $_percent;

  /**
   * The event's end time .
   * @var mixed
   * @access private
   */
  var $_endTime;
 /**
   * The event's end datetime .
   * @var mixed
   * @access private
   */
  var $_endDateTime;
 /**
   * Is this an All Day event?
   * @var bool
   * @access private
   */
  var $_allDay;
 /**
   * Is this an Timed event?
   * @var bool
   * @access private
   */
  var $_timed;
 /**
   * Is this an Untimed event?
   * @var bool
   * @access private
   */
  var $_untimed;

 /**
   * Flag to record a cloned event
   * @var int
   * @access private
   */
  var $_clone;
  /**
   * Creates a new Event.
   *
   * @param string $name        Name of the event
   * @param string $description Event's description
   * @param string $date        Event's date (in YYYYMMDD format)
   * @param string $time        Event's time (in HHMMSS format)
   * @parm  string $moddate     Event's mod date (in YYYYMMDD format)
   * @param string $modtime     Event's mod time (in HHMMSS format)
   * @param int    $id          Event's ID
   * @param mixed  $extForID    Event's extension ID (or '' for none)
   * @param int    $priority    Event's priority
   * @param string $access      Event's access level
   * @param int    $duration    Event's duration (in minutes)
   * @param string $status      Event's status
   * @param string $owner       Event's cal_create_by
   * @param string $login       Event's owner
   * @param string $calType     Event's type
   * @param string $location    Event's location
   * @param string $url         Event's url
   * @parm  int    $dueDate     Task's due date (in YYYYMMDD format)
   * @parm  int    $dueTime     Task's due time (in HHMMSS format)
   * @parm  int    $percent     Task's percentage of completion
   *
   * @return Event The new Event
   *
   * @access public
   */

  function Event ( $name, $description, $date, $time, $id, $extForID,
          $priority, $access, $duration, $status, $owner, $category, $login,
          $calType, $location, $url, $dueDate, $dueTime, $percent, $moddate, $modtime ) {

    $this->_name = $name;
    $this->_description = $description;
    $this->_date = $date;
    $this->_time = $time;
    $this->_moddate = $moddate;
    $this->_modtime = $modtime;
    $this->_id = $id;
    $this->_extForID = $extForID;
    $this->_priority = $priority;
    $this->_access = $access;
    $this->_duration = $duration;
    $this->_status = $status;
    $this->_owner = $owner;
    $this->_category = $category;
    $this->_login = $login;
    $this->_calType = $calType;
    $this->_location = $location;
    $this->_url = $url;
    $this->_dueDate  = $dueDate;
    $this->_dueTime  = sprintf ( "%06d", $dueTime );
    $this->_due = $dueDate . sprintf ( "%06d", $dueTime );
    $this->_percent = $percent;
    $this->_clone = '';

    // If public access override event name/description is enabled, then
    // hide the event name and description
    if ( $GLOBALS['login'] == '__public__' &&
      ! empty ( $GLOBALS['override_public'] ) &&
      $GLOBALS['override_public'] == 'Y' ) {
      $this->_name = $GLOBALS['override_public_text'];
      $this->_description = $GLOBALS['override_public_text'];
    }
  }

  /**
   * Gets the event's name
   *
   * @return string The event's name
   *
   * @access public
   */
  function getName () {
    return $this->_name;
  }

  /**
   * Gets the event's description
   *
   * @return string The event's description
   *
   * @access public
   */
  function getDescription () {
    return $this->_description;
  }

  /**
   * Gets the event's date
   *
   * @return string The event's date (in YYYYMMDD format)
   *
   * @access public
   */
  function getDate () {
    return $this->_date;
  }

  function getDateTimeAdjusted () {
    $year = substr ( $this->_date, 0, 4 );
    $month = substr ( $this->_date, 4, 2 );
    $day = substr ( $this->_date, 6, 2 );
  if ( $this->isTimed() ) {
    $h = (int) ( $this->_time / 10000 );
    $m = ( $this->_time / 100 ) % 100;
    $this->_Date = date ( 'Ymd', gmmktime ( $h, $m, 0, $month, $day, $year ) );
  } else {
   $h = $m = 0;
   $this->_Date = date ( 'Ymd', mktime ( $h, $m, 0, $month, $day, $year ) );
  }
    return $this->_Date;
  }
  /**
   * Gets the event's time
   *
   * @return string The event's time (in HHMMSS format)
   *
   * @access public
   */
  function getTime () {
    return $this->_time;
  }

  /**
   * Gets the event's modified date
   *
   * @return string The event's modifieddate (in YYYYMMDD format)
   *
   * @access public
   */
  function getModDate () {
    return $this->_moddate;
  }

  /**
   * Gets the event's modified time
   *
   * @return string The event's modified time (in HHMMSS format)
   *
   * @access public
   */
  function getModTime () {
    return $this->_modtime;
  }

  /**
   * Gets the task due time
   *
   * @return string The task due time (in HHMMSS format)
   *
   * @access public
   */
  function getDueTime () {
      return $this->_dueTime;
  }

  /**
   * Gets the task due date
   *
   * @return string The task due date (in YYYYMMDD format)
   *
   * @access public
   */
  function getDueDate () {
      return $this->_dueDate;
  }

     /**
   * Gets the task's due date/time as a Unix timestamp
   *
   * @return integer The task's due date/time as a timestamp
   *
   * @access public
   */
  function getDueDateTimeTS () {
    $year = substr ( $this->_dueDate, 0, 4 );
    $month = substr ( $this->_dueDate, 4, 2 );
    $day = substr ( $this->_dueDate, 6, 2 );
  if ( $this->_time > 0 ) {
    $h = (int) ( $this->_dueTime / 10000 );
    $m = ( $this->_dueTime / 100 ) % 100;
  } else {
   $h = $m = 0;
  }
    $this->_DueDateTime = gmmktime ( $h, $m, 0, $month, $day, $year );
    return $this->_DueDateTime;
  }

    /**
   * Gets the event's date/time
   *
   * @return string The event's date/time (in YYYYMMDDHHMMSS format)
   *
   * @access public
   */
  function getDateTime () {
    $time = ($this->_time > 0? $this->_time: 0);
    $this->_DateTime = $this->_date . sprintf ( "%06d", $time );
    return $this->_DateTime;
  }

    /**
   * Gets the event's date/time as a Unix timestamp
   *
   * @return integer The event's date/time as a timestamp
   *
   * @access public
   */
  function getDateTimeTS () {
    $year = substr ( $this->_date, 0, 4 );
    $month = substr ( $this->_date, 4, 2 );
    $day = substr ( $this->_date, 6, 2 );
  if ( $this->isTimed() ) {
    $h = (int) ( $this->_time / 10000 );
    $m = ( $this->_time / 100 ) % 100;
	$this->_DateTime = gmmktime ( $h, $m, 0, $month, $day, $year );
  } else {
   $h = $m = 0;
   $this->_DateTime = mktime ( $h, $m, 0, $month, $day, $year );
  }
    return $this->_DateTime;
  }

  /**
   * Gets the event's ID
   *
   * @return int The event's ID
   *
   * @access public
   */
  function getID () {
    return $this->_id;
  }

  /**
   * Gets the event's extension ID
   *
   * @return mixed The event's extension ID (or '' for none)
   *
   * @access public
   */
  function getExtForID () {
    return $this->_extForID;
  }

  /**
   * Gets the event's priority
   *
   * @return int The event's priority
   *
   * @access public
   */
  function getPriority () {
    return $this->_priority;
  }

  /**
   * Gets the event's access level
   *
   * @return string The event's access level
   *
   * @access public
   */
  function getAccess () {
    return $this->_access;
  }

  /**
   * Gets the event's duration
   *
   * @return int The event's duration (in minutes)
   *
   * @access public
   */
  function getDuration () {
    return $this->_duration;
  }

  /**
   * Gets the event's status
   *
   * @return string The event's status
   *
   * @access public
   */
  function getStatus () {
    return $this->_status;
  }

  /**
   * Gets the event's owner
   *
   * @return string The event's owner
   *
   * @access public
   */
  function getOwner () {
    return $this->_owner;
  }

  /**
   * Gets the event's category ID
   *
   * @return mixed The event's category ID (or '' for none)
   *
   * @access public
   */
  function getCategory () {
    return $this->_category;
  }

  /**
   * Gets the event's owner
   *
   * @return string The event's owner
   *
   * @access public
   */
  function getLogin () {
    return $this->_login;
  }

  /**
   * Gets the event's type
   *
   * @return string The event's type
   *
   * @access public
   */
  function getCalType () {
    return $this->_calType;
  }

  /**
   * Gets the event's type name
   *
   * @return string The event's type name
   *
   * @access public
   */
  function getCalTypeName () {
    if ( isset ( $this->_calTypeName ) )
      return $this->_calTypeName;
    if ( $this->_calType == 'E' || $this->_calType == 'M' )
      $this->_calTypeName = 'event';
    if ( $this->_calType == 'T' || $this->_calType == 'N' )
      $this->_calTypeName = 'task';
    if ( $this->_calType == 'J' || $this->_calType == 'O' )
      $this->_calTypeName = 'journal';
    return $this->_calTypeName;
  }

  /**
   * Gets the event's location
   *
   * @return string The event's location
   *
   * @access public
   */
  function getLocation () {
    return $this->_location;
  }
  /**
   * Gets the event's url
   *
   * @return string The event's url
   *
   * @access public
   */
  function getUrl () {
    return $this->_url;
  }
  /**
   * Gets the event's due date and time
   *
   * @return int The event's due date time
   *
   * @access public
   */
  function getDue () {
    return $this->_due;
  }
  /**
   * Gets the task's percent complete
   *
   * @return int The task's percentage
   *
   * @access public
   */
  function getPercent () {
    return $this->_percent;
  }

 /**
   * Gets the event's end date
   *
   * @return string The event's end date YYYYMM Format
   *
   * @access public
   */
  function getEndDate () {
    $year = substr ( $this->_date, 0, 4 );
    $month = substr ( $this->_date, 4, 2 );
    $day = substr ( $this->_date, 6, 2 );
    if ( $this->_time > 0 ) {
      $h = (int) ( $this->_time / 10000 );
      $m = ( $this->_time / 100 ) % 100;
    } else {
      $h = $m = 0;
    }
    $dur = ( $this->_duration > 0 ? $this->_duration : 0 );
    $this->_endDate = gmdate ( "Ymd", gmmktime ( $h, $m + $dur, 0, $month, $day, $year ) );
    return $this->_endDate;
  }

   /**
   * Gets the event's end time
   *
   * @return string The event's end time HHMMSS Format
   *
   * @access public
   */
  function getEndTime () {
    $year = substr ( $this->_date, 0, 4 );
    $month = substr ( $this->_date, 4, 2 );
    $day = substr ( $this->_date, 6, 2 );
    if ( $this->_time > 0 ) {
      $h = (int) ( $this->_time / 10000 );
      $m = ( $this->_time / 100 ) % 100;
    } else {
      $h = $m = 0;
    }
    $dur = ( $this->_duration > 0 ? $this->_duration : 0 );
    $this->_endTime = gmdate ( "His", gmmktime ( $h, $m + $dur, 0, $month, $day, $year ) );
    return $this->_endTime;
  }

 /**
   * Gets the event's end datetime
   *
   * @return string The event's end datetime YYYYMMSSHHMMSS Format
   *
   * @access public
   */
  function getEndDateTime () {
    $this->_endDateTime = gmdate ( 'YmdHis', $this->getEndDateTimeTS () );
    return $this->_endDateTime;
  }

 /**
   * Gets the event's end datetime as UNIX timestamp
   *
   * @return string The event's end datetime UNIX timestamp Format
   *
   * @access public
   */
  function getEndDateTimeTS () {
    $year = substr ( $this->_date, 0, 4 );
    $month = substr ( $this->_date, 4, 2 );
    $day = substr ( $this->_date, 6, 2 );
    if ( $this->_time > 0 ) {
      $h = (int) ( $this->_time / 10000 );
      $m = ( $this->_time / 100 ) % 100;
    } else {
      $h = $m = 0;
    }
    $dur = ( $this->_duration > 0 ? $this->_duration : 0 );
    $this->_endDateTime = gmmktime ( $h, $m + $dur, 0, $month, $day, $year );
    return $this->_endDateTime;
  }

 /**
   * Determine if event is All Day
   *
   * @return bool True if event is All Day
   *
   * @access public
   */
  function isAllDay () {
   $this->_allDay = ( $this->_time == 0 && $this->_duration == 1440? true : false);
    return $this->_allDay;
  }

 /**
   * Determine if event is Timed
   *
   * @return bool True if event is Timed
   *
   * @access public
   */
  function isTimed () {
   $this->_timed = ( $this->_time > 0 || ( $this->_time == 0
     && $this->_duration != 1440 )? true : false);
    return $this->_timed;
  }

 /**
   * Determine if event is Untimed
   *
   * @return bool True if event is Untimed
   *
   * @access public
   */
  function isUntimed () {
   $this->_untimed = ( $this->_time == -1 && $this->_duration == 0? true : false);
    return $this->_untimed;
  }

 /**
   * Set cal_duration
   *
   *
   * @access public
   */
  function setDuration ( $duration ) {
    $this->_duration = $duration;
  }

 /**
   * Set cal_time
   *
   *
   * @access public
   */
  function setTime ( $time ) {
    $this->_time = $time;
  }

 /**
   * Set cal_date
   *
   *
   * @access public
   */
  function setDate ( $date ) {
    $this->_date = $date;
  }
 /**
   * Set cal_name
   *
   *
   * @access public
   */
  function setName ( $name ) {
    $this->_name = $name;
  }
 /**
   * Get clone
   *
   *
   * @access public
   */
  function getClone () {
    return $this->_clone;
  }
 /**
   * Set clone
   *
   *
   * @access public
   */
  function setClone ( $date ) {
    $this->_clone = $date;
  }
}
?>
