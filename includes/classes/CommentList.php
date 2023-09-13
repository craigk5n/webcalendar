<?php
/**
 * Represents a list of Doc comment objects.
 *
 * @author Craig Knudsen
 * @copyright Craig Knudsen, <cknudsen@cknudsen.com>, http://k5n.us/
 * @license https://gnu.org/licenses/old-licenses/gpl-2.0.html GNU GPL
 * @package WebCalendar
 * @subpackage Doc
 */

/**
 * A list of Doc comment objects.
 */
class CommentList extends DocList {

  /**
   * Creates a new attachment list for the specified event.
   *
   * @param int    $event_id  The event id
   * @return AttachmentList The new AttachmentList object
   * @access public
   */
  function __construct ( $event_id )
  {
    parent::__construct ( $event_id, 'C' );
  }

}
?>
