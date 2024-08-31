<?php
/**
 * Represents a list of Doc attachment objects.
 *
 * @author Craig Knudsen
 * @copyright Craig Knudsen, <cknudsen@cknudsen.com>, http://k5n.us/
 * @license https://gnu.org/licenses/old-licenses/gpl-2.0.html GNU GPL
 *
 * @package WebCalendar\Doc
 */

/**
 * A list of Doc attachment objects.
 */
class AttachmentList extends DocList {

  /**
   * Creates a new attachment list for the specified event.
   *
   * @param int    $event_id  The event id
   * @return AttachmentList The new AttachmentList object
   * @access public
   */
  function __construct ( $event_id )
  {
    parent::__construct ( $event_id, 'A' );
  }

}
?>
