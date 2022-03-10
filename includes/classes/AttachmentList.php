<?php
/**
 * Represents a list of Doc attachment objects.
 *
 * @author Craig Knudsen
 * @copyright Craig Knudsen, <cknudsen@cknudsen.com>, http://www.k5n.us/
 * @license http://www.gnu.org/licenses/gpl.html GNU GPL
 * @package WebCalendar
 * @subpackage Doc
 */

/**
 * A list of Doc attachment objects.
 */
class AttachmentList extends DocList {

  /**
   * Creates a new attachment list for the specified event.
   *
   * @parm  int    $event_id  The event id
   * @return AttachmentList The new AttachmentList object
   * @access public
   */
  function __construct ( $event_id )
  {
    parent::__construct ( $event_id, 'A' );
  }

}
?>
