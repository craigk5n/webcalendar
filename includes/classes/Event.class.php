<?php
/**
 * Declares the Event class.
 *
 * @author Adam Roben <adam.roben@gmail.com>
 * @copyright Craig Knudsen, <cknudsen@cknudsen.com>, http://www.k5n.us/cknudsen
 * @license http://www.gnu.org/licenses/gpl.html GNU GPL
 * @version $Id$
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
   * The event's date (UNIX Timestamp format).
   * @var string
   * @access private
   */
  var $_date;
  /**
   * The event's modified date (in YYYYMMDD format).
   * @var string
   * @access private
   */
  var $_moddate;

  /**
   * The event's ID.
   * @var int
   * @access private
   */
  var $_eid;
  /**
   * Remote Address.
   *
   * If this event is created by a NUC, we'll store the $_SERVER['REMOTE_ADDR']
   * as <var>$_rmtaddr</var>.
   *
   * @var mixed
   * @access private
   */
  var $_rmtaddr;
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
   * The event's time.
   * @var mixed
   * @access private
   */
  var $_time;
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
   * Completed TS for tasks
   * @var int
   * @access private
   */
  var $_completed;
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
   * Is of Parent event of this entry if an exception to a repeat series
   * @var int
   * @access private
   */
  var $_parentId;
  /**
   * Creates a new Event.
   *
   * @param string $name        Name of the event
   * @param string $description Event's description
   * @param string $date        Event's date (UNIX Timestamp format)
   * @parm  string $moddate     Event's mod date (UNIX Timestamp format)
   * @param int    $eid         Event's ID
   * @param mixed  $rmtaddr     Event's creator IP
   * @param int    $priority    Event's priority
   * @param string $access      Event's access level
   * @param int    $duration    Event's duration (in minutes)
   * @param string $status      Event's status
   * @param string $owner       Event's cal_create_by
   * @param string $login       Event's owner
   * @param string $calType     Event's type
   * @param string $location    Event's location
   * @param string $url         Event's url
   * @parm  int    $dueDate     Task's due date (UNIX Timestamp format)
   * @parm  int    $percent     Task's percentage of completion
   * @param int    $completed   Task's completion date TS
   * @param int    $parentId    Id of parent event if exception to a repeating series
   *
   * @return Event The new Event
   *
   * @access public
   */

  function Event ( $name, $description, $date, $eid, $rmtaddr,
          $priority, $access, $duration, $status, $owner, $login, 
					$category, $calType, $location, $url, $dueDate, $percent, 
					$moddate, $completed, $parent ) 
 {

    $this->_name = $name;
    $this->_description = $description;
    $this->_date = $date;
    $this->_moddate = $moddate;
    $this->_eid = $eid;
    $this->_rmtaddr = $rmtaddr;
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
    $this->_due = $dueDate;
    $this->_percent = $percent;
    $this->_completed = $completed;
    $this->_clone = '';
    $this->_parentId = $parent;
		
    global $WC;
    // If public access override event name/description is enabled, then
    // hide the event name and description
    if ( $WC->isLogin( '__public__' ) &&
      getPref ( 'OVERRIDE_PUBLIC', 2 ) ) {
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
	 * @param  string Format of returned date, Timestamp if blank
   * @return string The event's date (in YYYYMMDD format)
   *
   * @access public
   */
  function getDate ( $mask = '' ) {
	  if ( $mask )
      return date ( $mask, $this->_date );
		else
		  return $this->_date;
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
   * Gets the task due date 
   *
   * @return string The task due time (in $mask format)
   *
   * @access public
   */
  function getDueDate ( $mask='' ) {
	  if ( $mask )
      return date ( $mask, $this->_dueDate );  
		else
		  return $this->_dueDate;
  }
 
    /**
   * Gets the event's date/time
   *
   * @return string The event's date/time (in YYYYMMDDHHMMSS format)
   *
   * @access public
   */
  function getDateTime () {
    $this->_DateTime = date ( 'YmdHis', $this->_date );
    return $this->_DateTime;
  }

 
  /**
   * Gets the event's ID
   *
   * @return int The event's ID
   *
   * @access public
   */
  function getId () {
    return $this->_eid;
  }

  /**
   * Gets the event's extension ID
   *
   * @return mixed The event's extension ID (or '' for none)
   *
   * @access public
   */
  function getRmtAddr () {
    return $this->_rmtaddr;
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
   * Gets the parent id
   *
   * @return int The event's parent id
   *
   * @access public
   */
  function getParent () {
    return $this->_parentId;
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
   * Gets the event's access level translated name
   *
   * @return string The event's access label
   *
   * @access public
   */
  function getAccessName () {
	  if ( $this->_access == 'P') 
		  return translate ( 'Public' );
		elseif ( $this->_access == 'C') 
		  return translate ( 'Confidential' );
		elseif ( $this->_access == 'R') 
		  return translate ( 'Private' );
    else 
		  return false;
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
   * Gets the event's owner id
   *
   * @return string The event's owner id
   *
   * @access public
   */
  function getLoginId () {
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
   * Gets the task's completion date
   *
   * @return int The task's completion date
   *
   * @access public
   */
  function getCompleted () {
    return $this->_completed;
  }
 /**
   * Gets the event's end date
   *
   * @return string The event's end datein $mask Format
   *
   * @access public
   */
  function getEndDate ( $mask = '' ) {
	  $end_date = $this->_date + ( $this->_duration * 60 );
	  if ( $mask )
		  return date ( $mask, $end_date );
		else 
      return $end_date;
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
  /**
   * Is Repeat
   *
   *
   * @access public
   */
  function isRepeat () {
    return ( $this->_calType == 'M' || $this->_calType == 'N' ||
	  $this->_calType == 'O' ? true : false ); 
  }
  /**
   * Is Mine determines if user is a paprticipant of event
   *
   *
   * @access public
   */
  function isMine ( $user='' ) {
	  global $WC;
     
		$is_my_event = false;
	  $user = ( ! empty ( $user ) ? $user : $WC->loginId() );
	  $res = dbi_execute ( 'SELECT we.cal_id, we.cal_create_by
      FROM webcal_entry we, webcal_entry_user weu
      WHERE we.cal_id = weu.cal_id AND we.cal_id = ?
      AND ( we.cal_create_by = ? OR weu.cal_login_id = ? )',
      array ( $this->_eid, $user, $user ) );
    if ( $res ) {
      $row = dbi_fetch_row ( $res );
      if ( $row && $row[0] > 0 ) {
        $is_my_event = true;
      }
     dbi_free_result ( $res );
    }	  
    return $is_my_event; 
  }
}
?>
